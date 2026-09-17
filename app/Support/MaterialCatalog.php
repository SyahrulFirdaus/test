<?php

namespace App\Support;

use App\Services\PrintEstimator;
use App\Support\SlaIndustries;
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
        return collect(app(PrintEstimator::class)->technologies())
            ->map(fn (array $technology, string $code) => [
                'code' => $code,
                'name' => $technology['name'],
                'family' => $technology['family'] ?? null,
                'label' => self::technologyLabel($code, $technology['family'] ?? null),
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
     * Tulisan pilihan Technology, mis. "FDM (Plastic)".
     *
     * Teknologi dipanggil dengan KODENYA karena itulah yang dikenal pelanggan
     * maupun tim — kecuali SLA Industries, yang dipanggil dengan namanya:
     * "SLAI" hanya singkatan teknis yang tidak dipakai siapa pun.
     */
    public static function technologyLabel(string $code, ?string $family = null): string
    {
        $name = SlaIndustries::is($code) ? SlaIndustries::NAME : $code;

        return $family ? $name.' ('.$family.')' : $name;
    }

    /**
     * Material milik satu teknologi.
     *
     * @param  array<string, mixed>|null  $technology
     * @return array<int, array<string, mixed>>
     */
    public static function materialsOf(string $code, ?array $technology = null): array
    {
        $technology ??= app(PrintEstimator::class)->technology($code);

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

    /* ------------------------------------- nama yang dilihat pelanggan --- */

    /**
     * Peta nama katalog → nama yang ditampilkan untuk satu teknologi.
     *
     * Peta kosong berarti seluruh material teknologi itu ditawarkan apa
     * adanya — inilah yang berlaku bagi FDM dan SLA, yang daftar materialnya
     * dikelola Superadmin lewat Price List. Lihat `material_display` di
     * config/printing.php.
     *
     * @return array<string, string>
     */
    public static function displayMap(string $technology): array
    {
        return (array) config('printing.material_display.'.strtoupper($technology), []);
    }

    /**
     * Nama material yang dilihat pelanggan, mis. "PLA+".
     *
     * Material di luar peta — termasuk yang tidak lagi ditawarkan tetapi masih
     * tercatat pada penawaran lama — dikembalikan apa adanya, sehingga tidak
     * ada penawaran yang kehilangan keterangan materialnya.
     */
    public static function displayName(string $technology, string $material): string
    {
        return self::displayMap($technology)[$material] ?? $material;
    }

    /**
     * Material yang ditawarkan kepada pelanggan, urut sesuai petanya.
     *
     * Menerima daftar material apa adanya dari katalog dan menyaringnya.
     * Teknologi tanpa peta mengembalikan daftarnya utuh — dan itulah yang
     * membuat FDM/SLA mengikuti Price List sepenuhnya: menambah baris di sana
     * langsung menambah pilihan di Edit Specification, menghapusnya langsung
     * menghilangkan pilihan itu, tanpa menyentuh kode mana pun.
     *
     * @param  array<string, mixed>  $materials  nama katalog => data material
     * @return array<string, mixed>
     */
    public static function offered(string $technology, array $materials): array
    {
        $map = self::displayMap($technology);

        if ($map === []) {
            return $materials;
        }

        $offered = [];

        // Urutan peta yang menentukan urutan pilihan, bukan urutan katalog.
        foreach ($map as $name => $label) {
            if (array_key_exists($name, $materials)) {
                $offered[$name] = $materials[$name];
            }
        }

        // Peta yang tidak cocok sama sekali dengan katalog — misalnya baris
        // Price List-nya baru saja diganti nama admin — tidak boleh membuat
        // pilihan materialnya kosong sama sekali.
        return $offered === [] ? $materials : $offered;
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
