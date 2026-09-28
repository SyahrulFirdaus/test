<?php

namespace App\Http\Middleware;

use App\Support\AdminPresence;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Perpanjang tanda "sedang login" Admin selama sesinya masih dipakai.
 *
 * Tidak menolak maupun mengubah permintaan apa pun — hanya mencatat. Lihat
 * App\Support\AdminPresence.
 */
class TrackAdminPresence
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (AdminPresence::tracks($user)) {
            AdminPresence::touch($user);
        }

        return $next($request);
    }
}
