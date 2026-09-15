<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActorType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Masuk dan keluar untuk pelanggan.
 *
 * Dashboard admin punya halaman masuknya sendiri di /admin/login; keduanya
 * memakai guard yang sama, hanya tujuan setelah berhasil masuk yang berbeda
 * mengikuti role akun.
 */
class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Batasi percobaan login agar kata sandi tidak dapat ditebak paksa.
        $throttleKey = 'login|'.strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            /*
             * Percobaan yang ditolak justru yang paling perlu terbaca pada
             * jejak audit. Yang dicatat hanya emailnya — kata sandi tidak
             * pernah ikut, benar maupun salah.
             *
             * Tipe akunnya dicari dari email yang diketik: percobaan terhadap
             * akun yang benar-benar ada tampil dengan tipe sebenarnya,
             * sehingga admin dapat melihat akun mana yang sedang disasar.
             */
            $target = User::where('email', $credentials['email'])->first();

            $this->activity->logFailure(
                action: ActivityAction::LOGIN_FAILED,
                description: 'Percobaan masuk ditolak untuk email '.$credentials['email'].'.',
                new: ['email' => $credentials['email']],
                userName: $target?->name ?? $credentials['email'],
                userType: ActorType::forUser($target),
            );

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $this->activity->log(
            action: ActivityAction::LOGIN,
            description: 'Berhasil masuk ke akun.',
            actor: $request->user(),
        );

        return redirect()->intended($this->homeFor($request));
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Pelakunya dicatat sebelum sesi ditutup; setelah logout tidak ada
        // lagi akun yang dapat dikenali.
        $this->activity->log(
            action: ActivityAction::LOGOUT,
            description: 'Keluar dari akun.',
            actor: $request->user(),
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Anda telah keluar dari akun.');
    }

    /** Halaman tujuan setelah masuk, mengikuti role akun. */
    private function homeFor(Request $request): string
    {
        return match (true) {
            $request->user()->isSuperAdmin() => route('superadmin.dashboard'),
            $request->user()->isAdmin() => route('admin.dashboard'),
            default => route('dashboard'),
        };
    }
}
