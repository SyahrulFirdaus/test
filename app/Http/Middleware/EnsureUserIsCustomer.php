<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard pelanggan.
 *
 * Admin yang membukanya diarahkan ke dashboard admin supaya keduanya tidak
 * saling tertukar — data yang ditampilkan memang berbeda sama sekali.
 */
class EnsureUserIsCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if ($user->isAdmin()) {
            // Superadmin dikembalikan ke dashboardnya sendiri, bukan ke antrean
            // kerja harian milik Admin.
            return redirect()->route($user->isSuperAdmin() ? 'superadmin.dashboard' : 'admin.dashboard');
        }

        return $next($request);
    }
}
