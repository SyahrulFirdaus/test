<?php

namespace App\Services\PriceList\Excel;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Support\PricingMethod;
use App\Support\SlaIndustries;

/**
 * Bentuk lembar Excel material Price List: satu sumber untuk seluruh berkasnya.
 *
 * Export, Template, Contoh, dan pembacaan berkas Import sama-sama membaca
 * definisi di sini, sehingga berkas yang diunduh pengelola selalu berbentuk
 * persis seperti yang diterima kembali saat diunggah. Menambah kolom cukup
 * dilakukan di satu tempat ini.
 *
 * Satu definisi untuk SELURUH teknologi — FDM, SLA, dan nanti MJF/SLM — bukan
 * satu berkas per teknologi. Yang membedakan hanya apa yang memang berbeda di
 * basis datanya, dan itu ditentukan teknologinya sendiri:
 *
 *   Sembilan kolom pertama sama bagi semuanya. Teknologi yang materialnya
 *   memilih sendiri metode harganya (SLA, MJF, SLM — lihat
 *   App\Support\PricingMethod) mendapat satu kolom tambahan di paling kanan,
 *   "Metode Harga", karena tanpa itu material Kalkulator Manual yang diimpor
 *   akan diam-diam berubah menjadi Otomatis atau sebaliknya. FDM tidak
 *   memilikinya sama sekali, jadi berkasnya tetap sembilan kolom seperti
 *   sebelumnya.
 *
 * Tiga kolom harga TIDAK tersimpan di basis data melainkan diturunkan dari
 * Harga Beli dan Harga Jual (lihat App\Models\Concerns\HasMaterialPricing):
 *
 *   Harga per gram    = round(Harga Beli / 800)
 *   Pembulatan Harga  = ceil(Harga Jual / 100) x 100
 *   Harga/10 gram     = Pembulatan Harga x 10
 *
 * Kolomnya tetap ikut ditulis karena itulah bentuk berkas yang dipakai tim,
 * tetapi saat Import nilainya hanya DICOCOKKAN — yang disimpan selalu hasil
 * hitungan rumus di atas, supaya angka di basis data tidak pernah menyimpang
 * dari Pricing Engine hanya karena sebuah sel diketik ulang.
 */
class MaterialSheet
{
    /** Kolom yang benar-benar disimpan; sisanya diturunkan. */
    public const STORED = ['material', 'brand', 'purchase_price', 'sale_price', 'remark'];

    /**
     * Warna latar judul kolom per teknologi.
     *
     * Berkas Price List tiap teknologi beredar berdampingan di tim, jadi
     * warnanya sekaligus menjadi penanda cepat berkas mana yang sedang dibuka.
     * Nilainya ARGB: latar, lalu warna tulisan yang masih terbaca di atasnya.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const HEADER_THEME = [
        // SLA: hijau muda, mengikuti berkas acuan timnya.
        SlaIndustries::CODE => ['FFC6E0B4', 'FF1F3864'],
    ];

    /** Biru tua, dipakai teknologi yang tidak menentukan warnanya sendiri. */
    private const HEADER_THEME_DEFAULT = ['FF1F3864', 'FFFFFFFF'];

    /** @return array{0: string, 1: string} latar dan warna tulisan judul kolom */
    public static function headerTheme(PrintTechnology $technology): array
    {
        return self::HEADER_THEME[strtoupper((string) $technology->code)] ?? self::HEADER_THEME_DEFAULT;
    }

    /**
     * Judul kolom, urut kiri ke kanan.
     *
     * @return array<int, string>
     */
    public static function headings(PrintTechnology $technology): array
    {
        return array_column(self::columns($technology), 'heading');
    }

    /**
     * Teknologi ini menyimpan metode harga per materialnya.
     *
     * Satu-satunya pembeda bentuk berkas antar teknologi — dan penentunya
     * App\Support\PricingMethod, bukan daftar kedua di sini.
     */
    public static function hasPricingMethod(PrintTechnology $technology): bool
    {
        return PricingMethod::appliesTo($technology->code);
    }

    /**
     * Definisi tiap kolom.
     *
     * `key`      penanda internal, bukan nama kolom basis data;
     * `heading`  judul yang tertulis di berkas;
     * `aliases`  judul lain yang masih diterima saat Import — berkas Price List
     *            yang beredar di tim menulis kolom terakhir dengan keterangan
     *            tambahan, dan menolaknya hanya akan membuat pengelola
     *            mengetik ulang judul yang sudah benar isinya;
     * `money`    ditampilkan sebagai rupiah;
     * `derived`  dihitung dari kolom lain, tidak pernah disimpan apa adanya.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function columns(PrintTechnology $technology): array
    {
        $columns = [
            ['key' => 'no', 'heading' => 'No', 'width' => 6, 'money' => false, 'derived' => true],
            ['key' => 'material', 'heading' => 'Material', 'width' => 32, 'money' => false, 'derived' => false, 'wrap' => true],
            ['key' => 'brand', 'heading' => 'Brand', 'width' => 14, 'money' => false, 'derived' => false],
            ['key' => 'purchase_price', 'heading' => 'Harga Beli', 'width' => 16, 'money' => true, 'derived' => false],
            ['key' => 'price_per_gram', 'heading' => 'Harga per gram', 'width' => 16, 'money' => true, 'derived' => true],
            ['key' => 'sale_price', 'heading' => 'Harga Jual', 'width' => 16, 'money' => true, 'derived' => false],
            ['key' => 'rounded_price', 'heading' => 'Pembulatan Harga', 'width' => 18, 'money' => true, 'derived' => true],
            [
                'key' => 'price_per_10_gram',
                'heading' => 'Harga/10 gram',
                'aliases' => ['Harga/10 gram (Masukan ke Calculator)', 'Harga per 10 gram'],
                'width' => 20,
                'money' => true,
                'derived' => true,
            ],
            ['key' => 'remark', 'heading' => 'Remark', 'width' => 24, 'money' => false, 'derived' => false, 'wrap' => true],
        ];

        /*
         * Kolom kesepuluh, hanya bagi teknologi yang memakainya.
         *
         * Ditempatkan PALING KANAN dengan sengaja: sembilan kolom pertama
         * karena itu tetap sama persis untuk seluruh teknologi, sehingga satu
         * berkas tetap terbaca meski dibuka dengan template teknologi lain.
         */
        if (self::hasPricingMethod($technology)) {
            $columns[] = [
                'key' => 'pricing_method',
                'heading' => 'Metode Harga',
                'aliases' => ['Metode Penentuan Harga', 'Pricing Method'],
                'width' => 20,
                'money' => false,
                'derived' => false,
            ];
        }

        return $columns;
    }

    /**
     * Judul yang diterima untuk tiap kolom, dalam huruf kecil tanpa spasi tepi.
     *
     * @return array<string, array<int, string>>
     */
    public static function acceptedHeadings(PrintTechnology $technology): array
    {
        $accepted = [];

        foreach (self::columns($technology) as $column) {
            $accepted[$column['key']] = array_map(
                fn (string $heading) => self::normalizeHeading($heading),
                [$column['heading'], ...($column['aliases'] ?? [])],
            );
        }

        return $accepted;
    }

    /** Judul dibandingkan tanpa memedulikan huruf besar-kecil dan spasi berlebih. */
    public static function normalizeHeading(?string $heading): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $heading)));
    }

    /**
     * Satu baris berkas untuk satu material.
     *
     * Angka ditulis sebagai angka, bukan teks berawalan "Rp" — formatnya
     * dipasang lembar kerjanya sendiri (lihat MaterialSheetWriter). Berkas yang
     * berisi "Rp185.000" sebagai teks tidak dapat dijumlahkan Excel dan tidak
     * dapat dibaca kembali sebagai angka saat Import.
     *
     * @return array<int, mixed>
     */
    public static function row(PrintMaterial $material, int $number, PrintTechnology $technology): array
    {
        $row = [
            $number,
            $material->material,
            $material->brand,
            (float) $material->purchase_price,
            $material->price_per_gram,
            (float) $material->sale_price,
            $material->rounded_price,
            $material->price_per_10_gram,
            $material->remark ?? '',
        ];

        if (self::hasPricingMethod($technology)) {
            $row[] = $material->pricing_method_label;
        }

        return $row;
    }

    /**
     * Data contoh, dipakai berkas Contoh.
     *
     * Angkanya sengaja data nyata Price List NUSAMA3D: pengelola yang membuka
     * berkas ini perlu melihat bentuk isian yang benar-benar dipakai, termasuk
     * hubungan antar kolom harganya.
     *
     * @return array<int, array<int, mixed>>
     */
    public static function exampleRows(PrintTechnology $technology): array
    {
        $rows = self::examplesFor($technology);
        $withMethod = self::hasPricingMethod($technology);

        return array_map(
            function (int $index, array $row) use ($withMethod) {
                [$material, $brand, $purchase, $sale, $remark, $method] = [...$row, null];

                $line = [
                    $index + 1,
                    $material,
                    $brand,
                    $purchase,
                    self::pricePerGram($purchase),
                    $sale,
                    self::roundedPrice($sale),
                    self::pricePer10Gram($sale),
                    $remark,
                ];

                if ($withMethod) {
                    $line[] = PrintMaterial::PRICING_METHODS[$method ?? PrintMaterial::PRICING_AUTOMATIC];
                }

                return $line;
            },
            array_keys($rows),
            $rows,
        );
    }

    /**
     * Baris contoh milik satu teknologi.
     *
     * Teknologi yang belum punya daftarnya sendiri memakai daftar FDM: yang
     * dicari pengelola dari berkas ini adalah BENTUK isiannya, dan bentuk itu
     * sama untuk semuanya.
     *
     * @return array<int, array<int, mixed>>
     */
    private static function examplesFor(PrintTechnology $technology): array
    {
        $examples = [
            'FDM' => [
                ['PLA Plus Standart ESUN', 'ESUN', 185000, 463, 'Standard Material'],
                ['PLA Basic ESUN', 'ESUN', 150000, 563, 'Standard Material'],
                ['PETG High Speed ESUN', 'ESUN', 210000, 788, 'Standard Material'],
                ['ABS ESUN', 'ESUN', 200000, 750, 'Standard Material'],
                ['ASA ESUN', 'ESUN', 300000, 938, 'Standard Material'],
                ['PA 12 ESUN', 'ESUN', 1000000, 3125, 'Engineering Material'],
                ['PA12 CF ESUN', 'ESUN', 1500000, 4688, 'Engineering Material'],
                ['PET CF ESUN', 'ESUN', 350000, 1094, 'Engineering Material'],
                ['TPU 95A ESUN', 'ESUN', 600000, 1875, 'Engineering Material'],
                ['TPU 85A ESUN', 'ESUN', 600000, 1875, 'Engineering Material'],
                ['ABS CF ESUN', 'ESUN', 450000, 1406, 'Engineering Material'],
                ['PET CF SUNLU', 'SUNLU', 320000, 1000, 'Engineering Material'],
                ['TPU 95A SUNLU', 'SUNLU', 250000, 625, 'Standard Material'],
                ['ASA SUNLU', 'SUNLU', 275000, 688, 'Standard Material'],
                ['PA12 CF SUNLU', 'SUNLU', 950000, 2970, 'Engineering Material'],
            ],

            /*
             * Resin SLA. Seluruhnya Kalkulator Otomatis: harganya memang
             * diturunkan dari Harga Beli dan Harga Jual seperti contoh ini.
             * Material SLA yang harganya ditetapkan tim per penawaran diisi
             * "Kalkulator Manual" pada kolom terakhir, dan kolom harganya
             * boleh dibiarkan kosong.
             */
            SlaIndustries::CODE => [
                ['Standard Resin Plus Sunlu', 'Sunlu', 275000, 1031, 'Standard Material', PrintMaterial::PRICING_AUTOMATIC],
                ['Standard Resin High Clear Sunlu', 'Sunlu', 450000, 1688, 'Standard Material', PrintMaterial::PRICING_AUTOMATIC],
                ['Standard Resin Nylon like Resin Sunlu', 'Sunlu', 550000, 2063, 'Engineering Material', PrintMaterial::PRICING_AUTOMATIC],
                ['Standard Resin ABS like resin Sunlu', 'Sunlu', 450000, 1688, 'Engineering Material', PrintMaterial::PRICING_AUTOMATIC],
                ['Standard Resin High Temp Sunlu', 'Sunlu', 550000, 2063, 'Engineering Material', PrintMaterial::PRICING_AUTOMATIC],
                ['Standard Resin High Toughness Sunlu', 'Sunlu', 450000, 1688, 'Engineering Material', PrintMaterial::PRICING_AUTOMATIC],
            ],
        ];

        return $examples[strtoupper((string) $technology->code)] ?? $examples['FDM'];
    }

    /* ------------------------------------------------------------- rumus */

    /**
     * Rumus turunan, dipakai Contoh dan pemeriksaan Import.
     *
     * Ditulis sekali di App\Models\Concerns\HasMaterialPricing dan dipanggil
     * lewat model sementara di sini, supaya tidak ada salinan rumus kedua yang
     * perlahan berbeda dari yang dipakai Calculator.
     */
    public static function pricePerGram(float $purchasePrice): int
    {
        return (new PrintMaterial(['purchase_price' => $purchasePrice]))->price_per_gram;
    }

    public static function roundedPrice(float $salePrice): int
    {
        return (new PrintMaterial(['sale_price' => $salePrice]))->rounded_price;
    }

    public static function pricePer10Gram(float $salePrice): int
    {
        return (new PrintMaterial(['sale_price' => $salePrice]))->price_per_10_gram;
    }

    /**
     * Metode harga dari tulisan di berkas.
     *
     * Menerima labelnya ("Kalkulator Otomatis") maupun nilai yang tersimpan
     * ("automatic"), karena berkas hasil Export menulis label sedangkan berkas
     * yang disusun sendiri kerap menulis nilainya. Null bila tidak dikenali.
     */
    public static function pricingMethodFrom(?string $raw): ?string
    {
        $text = mb_strtolower(trim((string) $raw));

        if ($text === '') {
            return null;
        }

        foreach (PrintMaterial::PRICING_METHODS as $value => $label) {
            if ($text === $value || $text === mb_strtolower($label)) {
                return $value;
            }
        }

        // "Otomatis" dan "Manual" saja juga diterima.
        return match ($text) {
            'otomatis', 'auto' => PrintMaterial::PRICING_AUTOMATIC,
            default => null,
        };
    }
}
