<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak akses Admin ke menu atau tindakan yang tidak diizinkan Superadmin.
 *
 * Dipasang per route: `->middleware('admin.permission:quotation.delete')`.
 * Menyembunyikan menu dan tombol saja tidak cukup; setiap route — termasuk
 * endpoint yang dipanggil manual — diperiksa di sini sehingga yang tidak
 * berhak selalu mendapat 403. Hak MELIHAT (`*.view`) dan hak TINDAKAN
 * (`*.edit`, `*.delete`, …) diperiksa terpisah, route demi route.
 *
 * Hak aksesnya dibaca dari basis data pada setiap permintaan (lihat
 * User::hasAdminPermission()), jadi perubahan dari Superadmin langsung berlaku
 * tanpa menunggu Admin keluar dan masuk lagi. Superadmin selalu lolos.
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->can($permission),
            403,
            'Anda tidak memiliki hak akses untuk tindakan ini. Hubungi Superadmin.'
        );

        return $next($request);
    }
}
