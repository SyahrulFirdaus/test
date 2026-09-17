<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi area admin hanya untuk akun ber-role admin.
 *
 * Sejak pelanggan juga memiliki akun, `auth` saja tidak lagi cukup untuk
 * menjaga dashboard admin: pelanggan yang sudah login tetap harus tertahan dan
 * diarahkan kembali ke dashboardnya sendiri.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->isAdmin()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Halaman tersebut hanya dapat diakses oleh administrator.');
        }

        // Admin yang dinonaktifkan Superadmin saat masih masuk langsung
        // dikeluarkan pada permintaan berikutnya — tidak menunggu sesinya habis.
        if (! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Akun tersebut sedang dinonaktifkan. Hubungi Superadmin.']);
        }

        return $next($request);
    }
}
