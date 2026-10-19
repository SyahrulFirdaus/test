<?php

namespace App\Support;

/**
 * Status lead pada Lead Management.
 *
 * Sengaja berdiri sendiri: mengubah status lead TIDAK mengubah status
 * quotation, pembayaran, maupun produksi — begitu pula sebaliknya, kecuali
 * satu hal: lead yang masih "Lead" naik menjadi "Quotation" saat kontak yang
 * sama mengirim quotation (lihat App\Services\LeadRecorder).
 */
class LeadStatus
{
    public const LEAD = 'lead';

    public const QUOTATION = 'quotation';

    public const DEAL = 'deal';

    public const CANCELLED = 'batal';

    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            self::LEAD => 'Lead',
            self::QUOTATION => 'Quotation',
            self::DEAL => 'Deal',
            self::CANCELLED => 'Batal',
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function label(?string $status): string
    {
        return self::all()[$status] ?? '—';
    }

    /** Lead yang masih berjalan; kontak yang sama digabung ke lead ini. */
    public static function open(): array
    {
        return [self::LEAD, self::QUOTATION];
    }

    /** Kelas warna badge, mengikuti badge lain di panel. */
    public static function tone(?string $status): string
    {
        return match ($status) {
            self::QUOTATION => 'bg-indigo-100 text-indigo-800',
            self::DEAL => 'bg-emerald-100 text-emerald-800',
            self::CANCELLED => 'bg-brand-100 text-brand-800',
            default => 'bg-amber-100 text-amber-800',
        };
    }
}
