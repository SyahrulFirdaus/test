<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;

if (! function_exists('staff_route_name')) {
    /**
     * Nama route menu pengelola menurut siapa yang sedang membukanya.
     *
     * Penawaran, Verifikasi Pembayaran, Payment Term, Notifikasi, Profil, dan
     * Ganti Password punya dua alamat: `/admin/...` bernama `admin.*` dan
     * `/superadmin/...` bernama `superadmin.*`. Isinya sama — yang berbeda
     * hanya wilayah tempat halamannya berada.
     *
     * Halaman dan controller tidak perlu tahu keduanya: cukup menyebut nama
     * pendeknya ("quotations.index"), dan fungsi ini memilih wilayah yang
     * sesuai dengan akun yang sedang masuk. Dengan begitu Superadmin yang
     * membuka daftar penawaran dari /superadmin tetap berada di /superadmin
     * saat mengeklik salah satu barisnya, dan Admin tetap di /admin.
     *
     * Nama yang tidak punya padanan di wilayah Superadmin — halaman masuk,
     * misalnya — dikembalikan ke wilayah admin apa adanya.
     */
    function staff_route_name(string $name): string
    {
        $name = ltrim($name, '.');

        if (Auth::user()?->isSuperAdmin() && RouteFacade::has('superadmin.'.$name)) {
            return 'superadmin.'.$name;
        }

        return 'admin.'.$name;
    }
}

if (! function_exists('staff_route')) {
    /**
     * URL menu pengelola pada wilayah yang sesuai.
     *
     * Pengganti langsung `route('admin.…')` di halaman-halaman pengelola.
     *
     * @param  mixed  $parameters
     */
    function staff_route(string $name, $parameters = [], bool $absolute = true): string
    {
        return route(staff_route_name($name), $parameters, $absolute);
    }
}
