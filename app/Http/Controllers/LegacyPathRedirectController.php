<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Meneruskan alamat lama menu staf ke alamat barunya.
 *
 * Menu sidebar dikelompokkan (Akun, Penawaran, Pembayaran) dan alamatnya ikut
 * pindah, mis. /admin/permintaan/12 → /admin/penawaran/penawaran/12. Tautan
 * lama tidak boleh putus: notifikasi yang sudah tersimpan di basis data dan
 * bookmark pengguna masih menunjuk alamat lama.
 *
 * Route-nya menyetel `from` dan `to` (potongan alamat setelah awalan wilayah
 * /admin atau /superadmin); sisa alamat dan query string ikut dibawa.
 */
class LegacyPathRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $from = (string) $request->route()->defaults['from'];
        $to = (string) $request->route()->defaults['to'];

        $path = preg_replace(
            '#^(admin|superadmin)/'.preg_quote($from, '#').'(?=/|$)#',
            '$1/'.$to,
            $request->path(),
        );

        $query = $request->getQueryString();

        return redirect()->to('/'.$path.($query ? '?'.$query : ''), 301);
    }
}
