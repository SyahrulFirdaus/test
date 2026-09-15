<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi area Superadmin hanya untuk akun ber-role superadmin.
 *
 * Menyembunyikan menu dari sidebar tidak cukup — URL-nya tetap dapat diketik
 * langsung. Penjaga ini yang membuat /superadmin/* benar-benar tertutup:
 * pelanggan dikembalikan ke dashboardnya, dan Admin biasa dikembalikan ke
 * dashboard admin dengan keterangan bahwa halaman itu bukan haknya.
 */
class EnsureUserIsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('admin.login'));
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Admin yang tersesat dikembalikan ke wilayahnya sendiri; pelanggan
        // tidak boleh tahu bahwa halaman ini ada, jadi keduanya ditolak dengan
        // cara yang sama seperti penjaga admin.
        return $user->isAdmin()
            ? redirect()
                ->route('admin.dashboard')
                ->with('error', 'Halaman tersebut hanya dapat diakses oleh Superadmin.')
            : redirect()
                ->route('dashboard')
                ->with('error', 'Halaman tersebut hanya dapat diakses oleh Superadmin.');
    }
}
