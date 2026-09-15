<?php

namespace App\Support;

/**
 * Basic Fee — biaya dasar penanganan menurut ukuran 3D object.
 *
 * Yang menentukan tingkatnya adalah sisi TERPANJANG model (Panjang/Lebar/
 * Tinggi, mana pun yang terbesar), bukan volumenya. Tingkat beserta tarifnya
 * diatur di `config/printing.php` pada `cost.basic_fee.tiers`, sehingga
 * perhitungan di browser, estimasi penawaran, rincian Harga Jual penawaran,
 * dan simulasi Price List semuanya membaca satu daftar yang sama.
 *
 * Dimensi yang dipakai diukur browser dari model yang SUDAH diskalakan dan
 * diputar (lihat `PrinterCard.applyOrientation()`), jadi angkanya dipakai apa
 * adanya — skala tidak boleh dikalikan lagi di sini.
 */
class BasicFee
{
    /** @return array<int, array<string, mixed>> */
    public static function tiers(): array
    {
        return (array) config('printing.cost.basic_fee.tiers', []);
    }

    /**
     * Tingkat yang berlaku untuk sebuah ukuran.
     *
     * Tingkat terakhir sengaja tidak berbatas sehingga selalu ada yang cocok;
     * bila konfigurasinya kosong, hasilnya tingkat nol rupiah.
     *
     * @return array<string, mixed>
     */
    public static function tierFor(float $largestMm): array
    {
        foreach (self::tiers() as $tier) {
            if (isset($tier['below_mm'])) {
                if ($largestMm < (float) $tier['below_mm']) {
                    return $tier;
                }

                continue;
            }

            if (isset($tier['up_to_mm'])) {
                if ($largestMm <= (float) $tier['up_to_mm']) {
                    return $tier;
                }

                continue;
            }

            return $tier;
        }

        return ['key' => 'kecil', 'label' => 'Kecil', 'fee' => 0];
    }

    /** Tarif yang berlaku untuk sebuah ukuran, dalam rupiah. */
    public static function amount(float $largestMm): float
    {
        return (float) (self::tierFor($largestMm)['fee'] ?? 0);
    }

    /** Nama tingkatnya, mis. "Sedang". */
    public static function label(float $largestMm): string
    {
        return (string) (self::tierFor($largestMm)['label'] ?? '-');
    }

    /**
     * Sisi terpanjang dari sebuah dimensi model, dalam milimeter.
     *
     * Model tanpa catatan dimensi menghasilkan 0 — masuk tingkat terkecil,
     * sehingga tidak ada biaya yang dikenakan tanpa dasar ukuran.
     *
     * @param  array<string, mixed>|null  $dimensions
     */
    public static function largestDimension(?array $dimensions): float
    {
        if (! is_array($dimensions) || $dimensions === []) {
            return 0.0;
        }

        $sides = array_map(
            fn ($side) => is_numeric($side) ? (float) $side : 0.0,
            [$dimensions['x'] ?? 0, $dimensions['y'] ?? 0, $dimensions['z'] ?? 0],
        );

        return max(0.0, max($sides));
    }

    /**
     * Tarif untuk sebuah dimensi model sekaligus.
     *
     * @param  array<string, mixed>|null  $dimensions
     */
    public static function forDimensions(?array $dimensions): float
    {
        return self::amount(self::largestDimension($dimensions));
    }

    /**
     * Daftar tingkat untuk estimator di browser.
     *
     * Kuncinya sengaja camelCase mengikuti bentuk payload `cost` lainnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function browserPayload(): array
    {
        return array_map(fn (array $tier) => [
            'key' => $tier['key'] ?? null,
            'label' => $tier['label'] ?? null,
            'belowMm' => $tier['below_mm'] ?? null,
            'upToMm' => $tier['up_to_mm'] ?? null,
            'fee' => (float) ($tier['fee'] ?? 0),
        ], self::tiers());
    }
}
