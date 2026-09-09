<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman yang hanya berlaku bagi akun Business.
 *
 * Dashboard Personal dan Business berbagi satu alamat `/dashboard` sehingga
 * tidak ada alamat dashboard tipe lain yang dapat dicoba. Yang perlu dijaga
 * adalah kemampuan tambahan milik akun perusahaan — pemesanan ulang dan data
 * perusahaan — agar tidak terbuka lewat penebakan alamat oleh akun Personal.
 *
 * Pelanggan Personal yang tersasar ke sini dikembalikan ke dashboardnya
 * beserta penjelasan, bukan halaman galat.
 */
class EnsureUserIsBusiness
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isBusiness()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Halaman tersebut hanya tersedia untuk akun Business.');
        }

        return $next($request);
    }
}
