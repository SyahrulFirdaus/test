<?php

namespace App\Support;

/**
 * Pilihan kecepatan pengerjaan beserta rentang hari kerjanya.
 *
 * Kecepatannya DIPILIH pelanggan, tidak lagi disimpulkan dari jam mesin:
 * Standard selalu tersedia, Express hanya bila pesanannya berisi satu part DAN
 * total waktu mesinnya di bawah 18 jam. Keduanya harus terpenuhi sekaligus —
 * pesanan dua part berjumlah 9 jam tetap tidak mendapat Express.
 *
 * Batas waktunya tegas DI BAWAH, bukan sampai dengan: 17 jam 59 menit masih
 * Express, 18 jam tepat tidak lagi.
 *
 * Aturan dan angkanya diatur `lead_time` di config/printing.php, dan salinan
 * yang sama dikirim ke browser lewat `printingConfig` sehingga pilihan yang
 * tampil di halaman 3D Models tidak pernah berbeda dengan yang diperiksa ulang
 * server saat penawarannya disimpan.
 */
class LeadTime
{
    public const STANDARD = 'standard';

    public const EXPRESS = 'express';

    public static function unit(): string
    {
        return config('printing.lead_time.unit', 'Hari Kerja');
    }

    /** @return array<string, mixed> */
    public static function standard(): array
    {
        return config('printing.lead_time.standard', ['name' => 'Standard', 'min_days' => 5, 'max_days' => 7]);
    }

    /** @return array<string, mixed> */
    public static function express(): array
    {
        return config('printing.lead_time.express', [
            'name' => 'Express',
            'min_days' => 1,
            'max_days' => 2,
            'max_parts' => 1,
            'below_minutes' => 1080,
            'surcharge_percent' => 25,
        ]);
    }

    /**
     * Rentang tetap bagi pekerjaan berharga Rumus Harga Manual.
     *
     * Partnya menunggu kuotasi vendor lebih dahulu, jadi jam mesinnya tidak
     * menentukan apa pun — lihat App\Support\PricingMethod.
     *
     * @return array<string, mixed>
     */
    public static function manualTier(): array
    {
        return config('printing.lead_time.manual', ['name' => 'Standard', 'min_days' => 5, 'max_days' => 7]);
    }

    public static function default(): string
    {
        return self::STANDARD;
    }

    public static function exists(?string $speed): bool
    {
        return in_array($speed, [self::STANDARD, self::EXPRESS], true);
    }

    /** Banyaknya part maksimum yang masih boleh Express. */
    public static function maxParts(): int
    {
        return max(1, (int) (self::express()['max_parts'] ?? 1));
    }

    /** Batas waktu mesin Express, dalam menit — harus DI BAWAH angka ini. */
    public static function belowMinutes(): float
    {
        return max(0.0, (float) (self::express()['below_minutes'] ?? 1080));
    }

    public static function surchargePercent(): float
    {
        return max(0.0, (float) (self::express()['surcharge_percent'] ?? 25));
    }

    /**
     * Express tersedia untuk pesanan ini.
     *
     * KEDUA syaratnya harus terpenuhi: jumlah partnya tidak melebihi batas DAN
     * total waktu mesinnya di bawah batas. Pekerjaan berharga Rumus Harga
     * Manual tidak pernah mendapat Express.
     */
    public static function expressAvailable(int $partCount, ?float $minutes, bool $manualPricing = false): bool
    {
        if ($manualPricing || $partCount < 1) {
            return false;
        }

        return $partCount <= self::maxParts()
            && max(0.0, (float) $minutes) < self::belowMinutes();
    }

    /**
     * Kecepatan yang benar-benar berlaku bagi pesanan ini.
     *
     * Express yang diminta padahal syaratnya tidak terpenuhi diturunkan menjadi
     * Standard. Dipakai server sebelum harga ditetapkan, sehingga kiriman yang
     * menyebut Express tidak dapat memotong antrean maupun mengubah harganya
     * tanpa memenuhi syaratnya.
     */
    public static function resolve(?string $speed, int $partCount, ?float $minutes, bool $manualPricing = false): string
    {
        return $speed === self::EXPRESS && self::expressAvailable($partCount, $minutes, $manualPricing)
            ? self::EXPRESS
            : self::STANDARD;
    }

    /** Pengali harga printing: 1,25 untuk Express, 1,0 untuk Standard. */
    public static function surchargeFactor(?string $speed): float
    {
        return $speed === self::EXPRESS
            ? 1 + (self::surchargePercent() / 100)
            : 1.0;
    }

    public static function unavailableNote(): string
    {
        return (string) config(
            'printing.lead_time.unavailable_note',
            'Express is unavailable for this order. Please select Standard Production (5–7 working days).',
        );
    }

    /**
     * Rentang hari kerja sebuah kecepatan.
     *
     * @return array{min: int, max: int}
     */
    public static function days(?string $speed, bool $manualPricing = false): array
    {
        $tier = self::tier($speed, $manualPricing);

        return ['min' => (int) $tier['min_days'], 'max' => (int) $tier['max_days']];
    }

    /** Nama kecepatannya, mis. "Express". */
    public static function name(?string $speed, bool $manualPricing = false): string
    {
        return (string) (self::tier($speed, $manualPricing)['name'] ?? '');
    }

    /** Label siap tampil, mis. "Express (1–2 Hari Kerja)". */
    public static function label(?string $speed, bool $manualPricing = false): string
    {
        $tier = self::tier($speed, $manualPricing);
        $min = (int) $tier['min_days'];
        $max = (int) $tier['max_days'];

        $range = ($min === $max ? $min : $min.'–'.$max).' '.self::unit();
        $name = (string) ($tier['name'] ?? '');

        return $name === '' ? $range : $name.' ('.$range.')';
    }

    /** @return array<string, mixed> */
    private static function tier(?string $speed, bool $manualPricing): array
    {
        if ($manualPricing) {
            return self::manualTier();
        }

        return $speed === self::EXPRESS ? self::express() : self::standard();
    }

    /**
     * Salinan aturan untuk perhitungan yang sama di browser.
     *
     * @return array<string, mixed>
     */
    public static function browserPayload(): array
    {
        $standard = self::standard();
        $express = self::express();
        $manual = self::manualTier();

        return [
            'unit' => self::unit(),
            'default' => self::default(),
            'standard' => [
                'key' => self::STANDARD,
                'name' => $standard['name'] ?? 'Standard',
                'minDays' => (int) $standard['min_days'],
                'maxDays' => (int) $standard['max_days'],
            ],
            'express' => [
                'key' => self::EXPRESS,
                'name' => $express['name'] ?? 'Express',
                'minDays' => (int) $express['min_days'],
                'maxDays' => (int) $express['max_days'],
                'maxParts' => self::maxParts(),
                'belowMinutes' => self::belowMinutes(),
                'surchargePercent' => self::surchargePercent(),
            ],
            'manual' => [
                'name' => $manual['name'] ?? 'Standard',
                'minDays' => (int) $manual['min_days'],
                'maxDays' => (int) $manual['max_days'],
            ],
            'unavailableNote' => self::unavailableNote(),
        ];
    }
}
