<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Katalog teknologi dan material dalam satu bentuk siap tampil.
 *
 * Halaman 3D Printing Guide, modal Edit Specification, dan estimator di browser
 * membaca katalog yang sama dari config/printing.php lewat kelas ini, sehingga
 * daftar teknologi, material, warna, keterangan, dan batas ukurannya tidak
 * pernah ditulis dua kali.
 *
 * `slug` dipakai sebagai anchor pada halaman panduan — tombol "Learn More" di
 * dalam Edit Specification menuju langsung ke bagian material tersebut.
 */
class MaterialCatalog
{
    /**
     * Seluruh teknologi beserta materialnya.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function technologies(): array
    {
        return collect(config('printing.technologies', []))
            ->map(fn (array $technology, string $code) => [
                'code' => $code,
                'name' => $technology['name'],
                'family' => $technology['family'] ?? null,
                'label' => isset($technology['family'])
                    ? $code.' ('.$technology['family'].')'
                    : $code,
                'description' => $technology['description'],
                'buildVolume' => $technology['build_volume'],
                'minWallMm' => $technology['min_wall_thickness_mm'],
                // MJF tidak memerlukan support: part tertopang serbuk di
                // sekelilingnya, jadi faktornya nol pada config.
                'needsSupport' => (float) ($technology['support_volume_factor'] ?? 0) > 0,
                'materials' => self::materialsOf($code, $technology),
            ])
            ->values()
            ->all();
    }

    /**
     * Material milik satu teknologi.
     *
     * @param  array<string, mixed>|null  $technology
     * @return array<int, array<string, mixed>>
     */
    public static function materialsOf(string $code, ?array $technology = null): array
    {
        $technology ??= config('printing.technologies.'.$code);

        if (! is_array($technology)) {
            return [];
        }

        $palette = MaterialColor::all();

        return collect($technology['materials'] ?? [])
            ->map(function (array $material, string $name) use ($code, $palette) {
                // Material tanpa daftar warna menerima seluruh warna palet.
                $allowed = (array) ($material['colors'] ?? []);
                $keys = $allowed === [] ? array_keys($palette) : $allowed;

                return [
                    'name' => $name,
                    'technology' => $code,
                    'slug' => self::slug($code, $name),
                    'description' => $material['description'] ?? null,
                    'characteristics' => $material['characteristics'] ?? [],
                    'pros' => $material['pros'] ?? [],
                    'cons' => $material['cons'] ?? [],
                    'density' => $material['density'],
                    'pricePerGram' => $material['price_per_gram'],
                    'colors' => collect($keys)
                        ->filter(fn (string $key) => isset($palette[$key]))
                        ->map(fn (string $key) => [
                            'key' => $key,
                            'label' => $palette[$key]['label'],
                            'hex' => $palette[$key]['hex'],
                        ])
                        ->values()
                        ->all(),
                    'maxSize' => $material['max_size'] ?? null,
                    'minSize' => $material['min_size'] ?? null,
                    // Batas alternatif untuk part memanjang; model yang lolos
                    // salah satu dari keduanya dianggap memenuhi syarat.
                    'minSizeSlender' => $material['min_size_slender'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    /** Anchor material pada halaman panduan, mis. "sla-standard-resin". */
    public static function slug(string $technology, string $material): string
    {
        return Str::slug($technology.'-'.$material);
    }

    /** Tulisan ukuran "250 × 250 × 300 mm"; null bila batasnya belum diatur. */
    public static function sizeLabel(?array $size): ?string
    {
        if (! is_array($size)) {
            return null;
        }

        return $size['x'].' × '.$size['y'].' × '.$size['z'].' mm';
    }
}
