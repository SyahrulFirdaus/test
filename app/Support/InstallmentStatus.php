<?php

namespace App\Support;

/**
 * Keadaan satu termin pembayaran.
 *
 * Termin berjalan satu per satu: hanya termin yang sudah diaktifkan yang boleh
 * dibayar, sisanya menunggu giliran pada keadaan `INACTIVE`. Urutan yang
 * dipakai antarmuka timeline adalah urutan nomor termin, bukan urutan keadaan
 * di sini.
 */
class InstallmentStatus
{
    /** Belum aktif — menunggu termin sebelumnya lunas atau diaktifkan admin. */
    public const INACTIVE = 'payment_inactive';

    /** Aktif dan menunggu pembayaran dari pelanggan. */
    public const PENDING = 'payment_pending';

    /** Bukti sudah diunggah dan menunggu verifikasi admin. */
    public const VERIFICATION = 'payment_verification';

    /** Admin menerima pembayarannya. */
    public const RECEIVED = 'payment_received';

    /** Bukti ditolak admin; pelanggan mengunggah ulang. */
    public const REJECTED = 'payment_rejected';

    /** Aktif namun batas waktunya sudah lewat. */
    public const OVERDUE = 'payment_overdue';

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return [
            self::INACTIVE => [
                'label' => 'Belum Aktif',
                'description' => 'Termin ini belum dapat dibayar.',
                'tone' => 'ink',
                'marker' => '○',
            ],
            self::PENDING => [
                'label' => 'Menunggu Pembayaran',
                'description' => 'Termin ini aktif dan menunggu pembayaran Anda.',
                'tone' => 'brand',
                'marker' => '●',
            ],
            self::VERIFICATION => [
                'label' => 'Pengecekan Pembayaran',
                'description' => 'Bukti pembayaran sedang diverifikasi admin.',
                'tone' => 'amber',
                'marker' => '●',
            ],
            self::RECEIVED => [
                'label' => 'Pembayaran Diterima',
                'description' => 'Pembayaran termin ini sudah diterima.',
                'tone' => 'emerald',
                'marker' => '✓',
            ],
            self::REJECTED => [
                'label' => 'Pembayaran Ditolak',
                'description' => 'Bukti pembayaran ditolak admin, silakan unggah ulang.',
                'tone' => 'rose',
                'marker' => '●',
            ],
            self::OVERDUE => [
                'label' => 'Pembayaran Terlambat',
                'description' => 'Termin ini melewati batas waktu pembayaran.',
                'tone' => 'rose',
                'marker' => '!',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_map(fn (array $state) => $state['label'], self::all());
    }

    public static function label(?string $status): string
    {
        return self::all()[$status]['label'] ?? (string) $status;
    }

    public static function description(?string $status): ?string
    {
        return self::all()[$status]['description'] ?? null;
    }

    public static function tone(?string $status): string
    {
        return self::all()[$status]['tone'] ?? 'ink';
    }

    /** Penanda ringkas pada timeline: ✓ lunas, ● berjalan, ○ belum aktif. */
    public static function marker(?string $status): string
    {
        return self::all()[$status]['marker'] ?? '○';
    }

    /**
     * Termin sedang menunggu pembayaran pelanggan.
     *
     * Bukti yang ditolak ikut di sini: terminnya tetap aktif dan pelanggan
     * masih harus menyelesaikannya.
     */
    public static function isPayable(?string $status): bool
    {
        return in_array($status, [self::PENDING, self::REJECTED, self::OVERDUE], true);
    }

    /** Termin yang sudah aktif — apa pun keadaan pembayarannya. */
    public static function isActive(?string $status): bool
    {
        return $status !== self::INACTIVE && $status !== self::RECEIVED;
    }
}
