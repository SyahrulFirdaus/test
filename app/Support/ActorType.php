<?php

namespace App\Support;

/**
 * Tipe akun pelaku aktivitas pada Activity Log.
 *
 * Nilai yang tersimpan mengikuti `customer_type` pada tabel pengguna —
 * `personal` dan `business` — ditambah `admin` untuk pengelola. Dengan begitu
 * penyaring pada halaman Activity Logs berbicara dalam istilah yang sama
 * dengan data akunnya, tanpa tabel padanan tersendiri.
 *
 * Khusus halaman ini labelnya membawa singkatan B2C/B2B karena Activity Logs
 * memang halaman internal admin; antarmuka pelanggan tetap hanya menyebut
 * "Personal" dan "Business" seperti sebelumnya.
 */
class ActorType
{
    public const PERSONAL = CustomerType::PERSONAL;

    public const BUSINESS = CustomerType::BUSINESS;

    public const ADMIN = 'admin';

    public const SUPERADMIN = 'superadmin';

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return [
            self::PERSONAL => ['label' => 'B2C', 'long' => 'Personal (B2C)'],
            self::BUSINESS => ['label' => 'B2B', 'long' => 'Business (B2B)'],
            self::ADMIN => ['label' => 'Admin', 'long' => 'Administrator'],
            self::SUPERADMIN => ['label' => 'Superadmin', 'long' => 'Superadmin'],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function exists(?string $type): bool
    {
        return $type !== null && array_key_exists($type, self::all());
    }

    /** Singkatan untuk kolom tabel, mis. "B2B". */
    public static function label(?string $type): string
    {
        return self::all()[$type]['label'] ?? (string) $type;
    }

    /** Sebutan lengkap untuk halaman detail, mis. "Business (B2B)". */
    public static function longLabel(?string $type): string
    {
        return self::all()[$type]['long'] ?? self::label($type);
    }

    /** @return array<string, string> daftar kunci => label, untuk dropdown filter */
    public static function options(): array
    {
        return array_map(fn (array $type) => $type['long'], self::all());
    }

    /**
     * Tipe akun milik satu pengguna.
     *
     * Admin selalu dicatat sebagai `admin` tanpa memandang `customer_type`
     * yang menempel pada akunnya, karena yang dinilai di sini adalah peran
     * saat aktivitasnya dilakukan.
     */
    public static function forUser(?\App\Models\User $user): string
    {
        if ($user === null) {
            return self::PERSONAL;
        }

        if ($user->isSuperAdmin()) {
            return self::SUPERADMIN;
        }

        if ($user->isAdmin()) {
            return self::ADMIN;
        }

        return $user->isBusiness() ? self::BUSINESS : self::PERSONAL;
    }
}
