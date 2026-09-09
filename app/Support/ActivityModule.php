<?php

namespace App\Support;

/**
 * Bagian sistem yang dicatat Activity Log.
 *
 * Modul dipakai penyaring pada halaman Activity Logs sekaligus penanda warna
 * pada tabelnya, jadi daftarnya sengaja pendek: satu modul mewakili satu alur
 * kerja yang dikenali admin, bukan satu controller.
 */
class ActivityModule
{
    /** Pendaftaran, masuk, keluar, dan perubahan data akun. */
    public const ACCOUNT = 'account';

    /** Berkas 3D beserta spesifikasi cetaknya. */
    public const MODELS = 'models';

    /** Permintaan penawaran dan perpindahan statusnya. */
    public const QUOTATION = 'quotation';

    /** Bukti transfer, verifikasi, dan skema pembayaran. */
    public const PAYMENT = 'payment';

    /** Perubahan data dan konfigurasi yang dilakukan pengelola. */
    public const ADMIN = 'admin';

    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            self::ACCOUNT => 'Account',
            self::MODELS => '3D Models',
            self::QUOTATION => 'Quotation',
            self::PAYMENT => 'Payment',
            self::ADMIN => 'Admin',
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function exists(?string $module): bool
    {
        return $module !== null && array_key_exists($module, self::all());
    }

    public static function label(?string $module): string
    {
        return self::all()[$module] ?? (string) $module;
    }

    /** @return array<string, string> daftar kunci => label, untuk dropdown filter */
    public static function options(): array
    {
        return self::all();
    }
}
