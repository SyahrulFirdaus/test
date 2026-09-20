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
     * Tingkat tetap bagi pekerjaan berharga Rumus Harga Manual.
     *
     * Bukan bagian dari `tiers`: pekerjaan seperti ini menunggu kuotasi vendor
     * lebih dahulu, jadi jam mesinnya tidak menentukan apa pun — lihat
     * App\Support\PricingMethod.
     *
     * @return array<string, mixed>
     */
    public static function manualTier(): array
    {
        return config('printing.lead_time.manual', ['name' => 'Standard', 'min_days' => 5, 'max_days' => 7]);
    }

    /**
     * Tingkat yang berlaku untuk sekian menit mesin.
     *
     * Menit yang masuk adalah TOTAL seluruh object dalam satu penawaran —
     * penentunya keseluruhan pesanan, bukan object yang paling lama.
     *
     * `$manualPricing` menandai pekerjaan berharga Rumus Harga Manual, yang
     * memakai rentang tetapnya sendiri berapa pun menit mesinnya.
     *
     * @return array<string, mixed>
     */
    public static function tierFor(?float $minutes, bool $manualPricing = false): array
    {
        if ($manualPricing) {
            return self::manualTier();
        }

        $minutes = max(0.0, (float) $minutes);
        $tiers = self::tiers();

        foreach ($tiers as $tier) {
            if ($tier['max_minutes'] === null || $minutes <= $tier['max_minutes']) {
                return $tier;
            }
        }

        $last = end($tiers);

        return $last === false
            ? ['name' => 'Standard', 'min_days' => 3, 'max_days' => 5]
            : $last;
    }

    /**
     * Rentang hari kerja untuk sekian menit mesin.
     *
     * @return array{min: int, max: int}
     */
    public static function days(?float $minutes, bool $manualPricing = false): array
    {
        $tier = self::tierFor($minutes, $manualPricing);

        return ['min' => (int) $tier['min_days'], 'max' => (int) $tier['max_days']];
    }

    /** Nama tingkatnya, mis. "Express". */
    public static function name(?float $minutes, bool $manualPricing = false): string
    {
        return (string) (self::tierFor($minutes, $manualPricing)['name'] ?? '');
    }

    /**
     * Label siap tampil, mis. "Express — 1 Hari Kerja".
     *
     * Yang sampai ke pelanggan hanya nama tingkat beserta rentang hari
     * kerjanya. Jam dan menit mesin tetap dihitung dan tersimpan, tetapi
     * dipakai sebagai data internal — di sini hanya menentukan tingkat mana
     * yang berlaku.
     */
    public static function label(?float $minutes, bool $manualPricing = false): string
    {
        $tier = self::tierFor($minutes, $manualPricing);
        $min = (int) $tier['min_days'];
        $max = (int) $tier['max_days'];

        $range = ($min === $max ? $min : $min.'–'.$max).' '.self::unit();
        $name = (string) ($tier['name'] ?? '');

        return $name === '' ? $range : $name.' ('.$range.')';
    }

    /**
     * Salinan aturan untuk perhitungan yang sama di browser.
     *
     * @return array<string, mixed>
     */
    public static function browserPayload(): array
    {
        $manual = self::manualTier();

        return [
            'unit' => self::unit(),
            'tiers' => array_map(fn (array $tier) => [
                'name' => $tier['name'] ?? null,
                'maxMinutes' => $tier['max_minutes'],
                'minDays' => $tier['min_days'],
                'maxDays' => $tier['max_days'],
            ], self::tiers()),
            'manual' => [
                'name' => $manual['name'] ?? null,
                'maxMinutes' => null,
                'minDays' => $manual['min_days'],
                'maxDays' => $manual['max_days'],
            ],
        ];
    }
}
