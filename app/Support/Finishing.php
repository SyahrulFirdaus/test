<?php

namespace App\Support;

/**
 * Pilihan finishing part setelah selesai dicetak.
 *
 * Harganya diturunkan dari Harga Jual part itu sendiri, dengan dasar minimum:
 *
 *   Raw / No Finishing = Rp0
 *   Sanding            = MAX(Harga Jual x 30%, Rp30.000)
 *   Sanding + Painting = MAX(Harga Jual x 70%, Rp75.000)
 *
 * Angkanya diatur `finishing` di config/printing.php; kelas ini hanya
 * menerapkannya. Yang MENGHITUNG harga penawaran tetap
 * App\Services\SellingPriceEstimator — satu-satunya tempat komponen harga
 * dijumlahkan — sehingga tidak ada dua sumber perhitungan.
 *
 * Custom Finishing (multi-color, masking, airbrush, gradasi, metallic,
 * weathering) ditandai `manual`: harganya tidak dihitung otomatis melainkan
 * ditetapkan tim lewat kuotasi, sama seperti material Kalkulator Manual.
 */
class Finishing
{
    public const NONE = 'none';

    public const SANDING = 'sanding';

    public const PAINTING = 'painting';

    public const CUSTOM = 'custom';

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('printing.finishing.options', []);
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, string> daftar kunci => label, untuk dropdown */
    public static function options(): array
    {
        return array_map(fn (array $option) => $option['label'], self::all());
    }

    public static function default(): string
    {
        $default = (string) config('printing.finishing.default', self::NONE);

        return self::exists($default) ? $default : (self::keys()[0] ?? self::NONE);
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    /** @return array<string, mixed> */
    public static function resolve(?string $key): array
    {
        $options = self::all();

        return $options[$key] ?? $options[self::default()] ?? [
            'label' => 'Raw / No Finishing',
            'percent' => 0,
            'min_price' => 0,
            'hours_per_unit' => 0.0,
        ];
    }

    public static function label(?string $key): string
    {
        return self::resolve($key)['label'];
    }

    public static function description(?string $key): ?string
    {
        return self::resolve($key)['description'] ?? null;
    }

    /** Persentase Harga Jual part yang menjadi biaya finishing. */
    public static function percent(?string $key): float
    {
        return max(0.0, (float) (self::resolve($key)['percent'] ?? 0));
    }

    /** Biaya minimum finishing: pekerjaan persiapannya sama untuk part kecil. */
    public static function minPrice(?string $key): float
    {
        return max(0.0, (float) (self::resolve($key)['min_price'] ?? 0));
    }

    /**
     * Harganya tidak dapat dihitung otomatis — ditetapkan tim per project.
     *
     * Penawaran yang memilihnya menunggu perhitungan, sama seperti material
     * Kalkulator Manual; lihat App\Support\PricingMethod.
     */
    public static function isManual(?string $key): bool
    {
        return (bool) (self::resolve($key)['manual'] ?? false);
    }

    /** Finishing ini menuntut pelanggan memilih warna cat. */
    public static function needsColor(?string $key): bool
    {
        return (bool) (self::resolve($key)['needs_color'] ?? false);
    }

    /** Keterangan tambahan, mis. ajakan meminta kuotasi khusus. */
    public static function note(?string $key): ?string
    {
        return self::resolve($key)['note'] ?? null;
    }

    /**
     * Biaya finishing untuk sebuah Harga Jual part.
     *
     *   MAX(Harga Jual x persen, harga minimum)
     *
     * Finishing tanpa persen maupun minimum — Raw — berharga nol, dan Custom
     * Finishing mengembalikan null karena harganya memang belum ada.
     */
    public static function priceFor(?string $key, float $printingPrice): ?float
    {
        if (self::isManual($key)) {
            return null;
        }

        $percent = self::percent($key);
        $minimum = self::minPrice($key);

        if ($percent <= 0 && $minimum <= 0) {
            return 0.0;
        }

        return round(max(max(0.0, $printingPrice) * ($percent / 100), $minimum), 2);
    }

    /** Tambahan waktu pengerjaan untuk setiap unit, dalam jam. */
    public static function hoursPerUnit(?string $key): float
    {
        return max(0.0, (float) (self::resolve($key)['hours_per_unit'] ?? 0.0));
    }

    /**
     * Bentuk ringkas untuk estimator di browser.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function browserPayload(): array
    {
        return collect(self::all())
            ->map(fn (array $option) => [
                'label' => $option['label'],
                'description' => $option['description'] ?? null,
                'note' => $option['note'] ?? null,
                'percent' => (float) ($option['percent'] ?? 0),
                'minPrice' => (float) ($option['min_price'] ?? 0),
                'manual' => (bool) ($option['manual'] ?? false),
                'needsColor' => (bool) ($option['needs_color'] ?? false),
                'hoursPerUnit' => (float) ($option['hours_per_unit'] ?? 0.0),
            ])
            ->all();
    }
}
