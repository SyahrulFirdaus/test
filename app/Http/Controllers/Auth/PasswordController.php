<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\PasswordPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Ganti kata sandi dari dalam dashboard.
 *
 * Dipakai user maupun admin; tampilannya mengikuti dashboard yang sedang
 * dibuka, tetapi aturan dan pemeriksaan kata sandi lamanya sama.
 */
class PasswordController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function edit(Request $request): View
    {
        return view(match (true) {
            $this->isSuperAdminArea($request) => 'superadmin.password',
            $request->user()->isAdmin() => 'admin.password',
            default => 'dashboard.password',
        });
    }

    public function update(Request $request): RedirectResponse
    {
        $superAdminArea = $this->isSuperAdminArea($request);

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', ...PasswordPolicy::rules()],

            // Halaman Superadmin menampilkan kesalahan konfirmasi di bawah
            // kolom konfirmasinya sendiri, bukan di bawah kolom kata sandi baru.
            ...($superAdminArea ? ['password_confirmation' => ['required', 'same:password']] : []),
        ], [
            'password_confirmation.required' => 'Konfirmasi kata sandi baru wajib diisi.',
            'password_confirmation.same' => 'Konfirmasi kata sandi baru belum sama.',
            ...PasswordPolicy::messages(),
            'current_password.required' => 'Kata sandi lama wajib diisi.',
            'current_password.current_password' => 'Kata sandi lama tidak cocok.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru belum sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
        ]);

        $request->user()->update([
            'password' => $request->string('password')->toString(),
        ]);

        // Yang dicatat hanya bahwa penggantian terjadi. Kata sandi lama maupun
        // baru tidak pernah ikut ke jejak audit dalam bentuk apa pun.
        $this->activity->log(
            action: ActivityAction::PASSWORD_UPDATE,
            description: 'Mengganti kata sandi akun.',
            subject: $request->user(),
            actor: $request->user(),
        );

        // Superadmin wajib masuk ulang dengan kata sandi barunya: sesinya
        // diakhiri dan dihapus, lalu langsung dialihkan (tanpa jeda) ke halaman
        // masuk Superadmin yang menampilkan pesan suksesnya.
        if ($superAdminArea) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('superadmin.login')
                ->with('status', 'Password berhasil diubah. Silakan login kembali.');
        }

        return back()->with('status', 'Kata sandi berhasil diperbarui.');
    }

    /** Permintaan datang dari wilayah /superadmin oleh akun Superadmin. */
    private function isSuperAdminArea(Request $request): bool
    {
        return $request->routeIs('superadmin.*') && (bool) $request->user()?->isSuperAdmin();
    }
}
