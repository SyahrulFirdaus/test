<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActorType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route($this->homeFor(Auth::user()));
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Batasi percobaan login agar tidak dapat ditebak paksa.
        $throttleKey = strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            $this->activity->logFailure(
                action: ActivityAction::LOGIN_FAILED,
                description: 'Percobaan masuk admin ditolak untuk email '.$credentials['email'].'.',
                new: ['email' => $credentials['email']],
                userName: $credentials['email'],
                userType: ActorType::ADMIN,
            );

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok.',
            ]);
        }

        // Halaman ini khusus pengelola. Akun pelanggan yang kredensialnya benar
        // tetap ditolak di sini dan diarahkan ke halaman masuk pelanggan.
        if (! Auth::user()->isAdmin()) {
            $this->activity->logFailure(
                action: ActivityAction::LOGIN_FAILED,
                description: 'Akun pelanggan mencoba masuk lewat halaman login admin.',
                actor: Auth::user(),
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun tersebut bukan akun administrator. Silakan masuk melalui halaman login pelanggan.',
            ]);
        }

        // Akun pengelola yang dinonaktifkan Superadmin tetap ada beserta
        // jejaknya, tetapi tidak lagi berhak masuk.
        if (! Auth::user()->isActive()) {
            $this->activity->logFailure(
                action: ActivityAction::LOGIN_FAILED,
                description: 'Akun pengelola nonaktif mencoba masuk.',
                actor: Auth::user(),
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun tersebut sedang dinonaktifkan. Hubungi Superadmin.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $this->activity->log(
            action: ActivityAction::LOGIN,
            description: $request->user()->isSuperAdmin()
                ? 'Superadmin berhasil masuk ke dashboard.'
                : 'Admin berhasil masuk ke dashboard.',
            actor: $request->user(),
        );

        return redirect()->intended(route($this->homeFor($request->user())));
    }

    /**
     * Halaman pertama sesudah masuk.
     *
     * Superadmin mendarat di dashboardnya sendiri — gambaran keseluruhan
     * sistem — bukan di antrean kerja harian milik Admin.
     */
    private function homeFor(\App\Models\User $user): string
    {
        return match (true) {
            $user->isSuperAdmin() => 'superadmin.dashboard',
            $user->isAdmin() => 'admin.dashboard',
            default => 'dashboard',
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->activity->log(
            action: ActivityAction::LOGOUT,
            description: ($request->user()?->isSuperAdmin() ? 'Superadmin' : 'Admin').' keluar dari dashboard.',
            actor: $request->user(),
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
