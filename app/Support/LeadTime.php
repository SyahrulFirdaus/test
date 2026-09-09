<?php

namespace App\Support;

/**
 * Penerjemah jam mesin menjadi lead time pengerjaan.
 *
 * Estimasi tetap dihitung dalam menit mesin — angka itulah yang dipakai
 * perhitungan biaya dan perencanaan produksi. Yang berubah hanya cara
 * menyampaikannya kepada pelanggan: rentang hari kerja sampai pesanan selesai,
 * bukan lama satu file dicetak.
 *
 * Tingkatannya diatur `lead_time` di config/printing.php, dan salinan yang sama
 * dikirim ke browser lewat `printingConfig` sehingga estimasi di halaman
 * 3D Models tidak pernah berbeda dengan yang dihitung ulang di server.
 */
class LeadTime
{
    /** @return array<int, array<string, int|null>> */
    public static function tiers(): array
    {
        return config('printing.lead_time.tiers', []);
    }

    public static function unit(): string
    {
        return config('printing.lead_time.unit', 'Hari Kerja');
    }

    /**
     * Rentang hari kerja untuk sekian menit mesin.
     *
     * @return array{min: int, max: int}
     */
    public static function days(?float $minutes): array
    {
        $minutes = max(0.0, (float) $minutes);
        $tiers = self::tiers();

        foreach ($tiers as $tier) {
            if ($tier['max_minutes'] === null || $minutes <= $tier['max_minutes']) {
                return ['min' => (int) $tier['min_days'], 'max' => (int) $tier['max_days']];
            }
        }

        $last = end($tiers);

        return $last === false
            ? ['min' => 3, 'max' => 5]
            : ['min' => (int) $last['min_days'], 'max' => (int) $last['max_days']];
    }

    /** Label siap tampil, mis. "3–5 Hari Kerja". */
    public static function label(?float $minutes): string
    {
        ['min' => $min, 'max' => $max] = self::days($minutes);

        return $min === $max
            ? $min.' '.self::unit()
            : $min.'–'.$max.' '.self::unit();
    }

    /**
     * Salinan aturan untuk perhitungan yang sama di browser.
     *
     * @return array<string, mixed>
     */
    public static function browserPayload(): array
    {
        return [
            'unit' => self::unit(),
            'tiers' => array_map(fn (array $tier) => [
                'maxMinutes' => $tier['max_minutes'],
                'minDays' => $tier['min_days'],
                'maxDays' => $tier['max_days'],
            ], self::tiers()),
        ];
    }
}
