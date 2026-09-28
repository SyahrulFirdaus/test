<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * Mode tampilan dashboard pengelola: Personal atau Business.
 *
 * Switch di header hanya mengubah APA YANG DILIHAT — segmen pelanggan yang
 * diringkas dashboard beserta menu pembayaran yang relevan baginya. Mode ini
 * TIDAK menyentuh role maupun hak akses: Admin tetap Admin, dan menu yang tidak
 * berhak dibukanya tetap tertutup di kedua mode (lihat App\Support\AdminPermission).
 *
 * Pilihannya disimpan di session, jadi bertahan saat halaman dimuat ulang dan
 * kembali ke Personal begitu sesi login berakhir.
 */
class DashboardMode
{
    /** Pelanggan perorangan: hobi, prototype, tugas, custom object. */
    public const PERSONAL = 'personal';

    /** Pelanggan perusahaan: engineering, produksi, procurement. */
    public const BUSINESS = 'business';

    private const KEY = 'dashboard_mode';

    /** @return array<int, string> */
    public static function keys(): array
    {
        return [self::PERSONAL, self::BUSINESS];
    }

    public static function exists(?string $mode): bool
    {
        return $mode !== null && in_array($mode, self::keys(), true);
    }

    public static function default(): string
    {
        return self::PERSONAL;
    }

    /** Mode yang sedang berlaku; Personal bila belum pernah dipilih. */
    public static function current(): string
    {
        $mode = Session::get(self::KEY);

        return self::exists($mode) ? (string) $mode : self::default();
    }

    /** Simpan pilihan switch. Nilai yang tidak dikenal jatuh ke Personal. */
    public static function set(?string $mode): string
    {
        $mode = self::exists($mode) ? (string) $mode : self::default();

        Session::put(self::KEY, $mode);

        return $mode;
    }

    public static function isBusiness(?string $mode = null): bool
    {
        return ($mode ?? self::current()) === self::BUSINESS;
    }

    public static function label(?string $mode = null): string
    {
        return self::isBusiness($mode) ? 'Business' : 'Personal';
    }

    /**
     * Tipe pelanggan yang diringkas mode ini.
     *
     * Nilainya sengaja sama persis dengan App\Support\CustomerType supaya dapat
     * langsung dipakai menyaring pertanyaan basis data tanpa pemetaan kedua.
     */
    public static function customerType(?string $mode = null): string
    {
        return self::isBusiness($mode) ? CustomerType::BUSINESS : CustomerType::PERSONAL;
    }
}
