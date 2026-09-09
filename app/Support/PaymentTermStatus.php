<?php

namespace App\Support;

/**
 * Keadaan skema pembayaran bertahap milik satu penawaran Business.
 *
 * Terpisah dari App\Support\QuotationStatus: yang ini menerangkan nasib
 * pengajuan cicilannya, sedangkan status penawaran tetap mengikuti alur
 * produksi yang sudah ada. Nilainya disimpan sebagai string sepanjang 40
 * karakter, bukan ENUM, agar keadaan baru dapat ditambahkan tanpa migrasi
 * pengubah kolom.
 */
class PaymentTermStatus
{
    /** Pelanggan sudah memilih jumlah termin, menunggu keputusan admin. */
    public const PENDING = 'payment_term_pending';

    /** Admin menyetujui; jadwal termin terbentuk dan termin pertama aktif. */
    public const APPROVED = 'payment_term_approved';

    /** Admin menolak; pelanggan dapat mengajukan skema lain. */
    public const REJECTED = 'payment_term_rejected';

    /** Seluruh termin sudah lunas. */
    public const COMPLETED = 'payment_completed';

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return [
            self::PENDING => [
                'label' => 'Menunggu Persetujuan Payment Term',
                'description' => 'Pengajuan skema pembayaran Anda sedang ditinjau admin.',
                'tone' => 'amber',
            ],
            self::APPROVED => [
                'label' => 'Payment Term Disetujui',
                'description' => 'Skema pembayaran disetujui dan jadwal terminnya sudah terbentuk.',
                'tone' => 'emerald',
            ],
            self::REJECTED => [
                'label' => 'Payment Term Ditolak',
                'description' => 'Skema pembayaran yang Anda ajukan ditolak admin.',
                'tone' => 'rose',
            ],
            self::COMPLETED => [
                'label' => 'Pembayaran Lunas',
                'description' => 'Seluruh termin pembayaran sudah diterima.',
                'tone' => 'emerald',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, string> daftar kunci => label, untuk filter admin */
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

    /** Warna penanda pada antarmuka, mengikuti palet yang sudah dipakai. */
    public static function tone(?string $status): string
    {
        return self::all()[$status]['tone'] ?? 'ink';
    }
}
