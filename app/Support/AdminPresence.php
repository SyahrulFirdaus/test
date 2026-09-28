<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Status login akun Admin, untuk kolom "Status Login" pada menu Akun Admin.
 *
 * Sesi aplikasi ini disimpan sebagai berkas (SESSION_DRIVER=file), jadi tidak
 * ada tabel sesi yang dapat ditanyai "siapa yang sedang masuk". Status login
 * karena itu dicatat langsung dari sistem autentikasi yang sudah ada:
 *
 *   - event Login   → Admin ditandai Aktif;
 *   - setiap permintaan web yang diautentikasi → tandanya diperpanjang
 *     (lihat App\Http\Middleware\TrackAdminPresence);
 *   - event Logout  → tandanya dihapus, Admin menjadi Nonaktif. Ini termasuk
 *     logout paksa saat akunnya dinonaktifkan (EnsureAccountIsActive).
 *
 * Tandanya berumur sama dengan umur sesi (`session.lifetime`), sehingga
 * sesi yang kedaluwarsa karena lama tidak dipakai ikut terbaca Nonaktif
 * tanpa perlu logout. Disimpan di cache, bukan basis data: tidak ada tabel
 * maupun kolom yang ditambahkan.
 */
class AdminPresence
{
    /** Perpanjangan tanda paling sering sekali per menit, supaya cache tidak ditulis setiap permintaan. */
    private const REFRESH_SECONDS = 60;

    /** Hanya Admin biasa — yang tampil di menu Akun Admin — yang dicatat. */
    public static function tracks(?User $user): bool
    {
        return $user !== null && $user->role === User::ROLE_ADMIN;
    }

    public static function markOnline(User $user): void
    {
        Cache::put(self::key($user), now()->getTimestamp(), now()->addMinutes(self::lifetime()));
    }

    /** Perpanjang tanda bila sudah lebih dari semenit sejak terakhir ditulis. */
    public static function touch(User $user): void
    {
        $last = Cache::get(self::key($user));

        if (! is_int($last) || now()->getTimestamp() - $last >= self::REFRESH_SECONDS) {
            self::markOnline($user);
        }
    }

    public static function markOffline(User $user): void
    {
        Cache::forget(self::key($user));
    }

    public static function isOnline(User $user): bool
    {
        return Cache::has(self::key($user));
    }

    private static function lifetime(): int
    {
        return max(1, (int) config('session.lifetime', 120));
    }

    private static function key(User $user): string
    {
        return 'admin-presence:'.$user->getKey();
    }
}
