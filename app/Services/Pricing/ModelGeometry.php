<?php

namespace App\Services\Pricing;

/**
 * Geometri baku sebuah model 3D: volume, luas permukaan, dan dimensi pada
 * skala 100%, dalam satuan internal (cm³, cm², mm).
 *
 * `model_stats` pada penawaran berasal dari dua pengukur yang berbeda:
 *
 *  - browser (halaman 3D Models) — dimensinya diukur SETELAH skala dan
 *    orientasi pilihan pengguna diberlakukan;
 *  - server (tambah model dari dashboard, App\Services\MeshInspector) —
 *    dimensinya pada skala 100%.
 *
 * Tanpa penyeragaman, dashboard yang mengubah skala model hasil browser akan
 * menskalakan dimensi yang sudah terskalakan (atau sebaliknya lupa
 * menskalakannya), sehingga Basic Fee dan ukuran kardus bergantung pada dari
 * mana model itu diunggah. Kelas ini satu-satunya tempat aturan pembacaannya.
 */
final class ModelGeometry
{
    /**
     * Susun kolom `model_stats` baku untuk disimpan.
     *
     * `base_dimensions` selalu pada skala 100%; `dimensions` dipertahankan
     * apa adanya bagi pembaca lama (tampilan dimensi, pemeriksaan area cetak).
     *
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>
     */
    public static function normalizeStats(array $stats, float $modelVolumeCm3 = 0.0): array
    {
        $base = self::fromStats($stats, $modelVolumeCm3);

        return [
            ...$stats,
            'volume_cm3' => $base['volume_cm3'],
            'surface_area_cm2' => $base['surface_area_cm2'],
            'base_dimensions' => $base['dimensions'],
            'units' => ['length' => 'mm', 'area' => 'cm2', 'volume' => 'cm3'],
        ];
    }

    /**
     * Geometri pada skala 100% dari `model_stats` tersimpan.
     *
     * @param  array<string, mixed>  $stats
     * @return array{volume_cm3: float, surface_area_cm2: float, dimensions: array{x: float, y: float, z: float}|null}
     */
    public static function fromStats(array $stats, float $modelVolumeCm3 = 0.0): array
    {
        $volume = is_numeric($stats['volume_cm3'] ?? null) && (float) $stats['volume_cm3'] > 0
            ? (float) $stats['volume_cm3']
            : $modelVolumeCm3;

        return [
            'volume_cm3' => $volume,
            'surface_area_cm2' => is_numeric($stats['surface_area_cm2'] ?? null) ? (float) $stats['surface_area_cm2'] : 0.0,
            'dimensions' => self::baseDimensions($stats, $volume),
        ];
    }

    /**
     * Dimensi pada skala 100%.
     *
     * Urutan sumbernya: `base_dimensions` (data baru) → dimensi hasil ukur
     * server (memang skala 100%) → dimensi browser dibagi skala saat diukur.
     * Skala saat diukur dibaca dari perbandingan volume terskalakan dan volume
     * aslinya yang tersimpan bersamanya — bukan dari `scale_percent` model,
     * karena skala itu bisa sudah diubah lagi setelah pengukuran.
     *
     * @param  array<string, mixed>  $stats
     * @return array{x: float, y: float, z: float}|null
     */
    public static function baseDimensions(array $stats, float $volumeCm3): ?array
    {
        if (self::validDimensions($stats['base_dimensions'] ?? null)) {
            return self::floats($stats['base_dimensions']);
        }

        if (! self::validDimensions($stats['dimensions'] ?? null)) {
            return null;
        }

        $dimensions = self::floats($stats['dimensions']);

        if (($stats['measured_by'] ?? null) === 'server') {
            return $dimensions;
        }

        $scaled = (float) ($stats['scaled_volume_cm3'] ?? 0);

        if ($scaled > 0 && $volumeCm3 > 0) {
            $scale = ($scaled / $volumeCm3) ** (1 / 3);

            if ($scale > 0 && abs($scale - 1.0) > 0.0001) {
                return array_map(fn (float $side) => $side / $scale, $dimensions);
            }
        }

        return $dimensions;
    }

    private static function validDimensions(mixed $dimensions): bool
    {
        return is_array($dimensions)
            && is_numeric($dimensions['x'] ?? null)
            && is_numeric($dimensions['y'] ?? null)
            && is_numeric($dimensions['z'] ?? null);
    }

    /** @return array{x: float, y: float, z: float} */
    private static function floats(array $dimensions): array
    {
        return [
            'x' => (float) $dimensions['x'],
            'y' => (float) $dimensions['y'],
            'z' => (float) $dimensions['z'],
        ];
    }
}
