<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun nonaktif tidak dapat memakai sistem sama sekali.
 *
 * Dipasang pada seluruh grup `web`, jadi berlaku untuk setiap halaman,
 * endpoint JSON (mis. polling notifikasi), dan aksi yang dipanggil langsung —
 * bukan hanya di halaman login. Sesi akun yang dinonaktifkan Superadmin saat
 * masih masuk langsung diakhiri pada permintaan berikutnya.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isActive()) {
            return $next($request);
        }

        $staff = $user->isAdmin();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = 'Akun tersebut sedang dinonaktifkan. Hubungi Superadmin.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()
            ->route($staff ? 'admin.login' : 'login')
            ->withErrors(['email' => $message]);
    }
}
