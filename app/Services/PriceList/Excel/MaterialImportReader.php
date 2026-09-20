<?php

namespace App\Services\PriceList\Excel;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Support\PricingMethod;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Throwable;

/**
 * Pembaca sekaligus pemeriksa berkas Import material.
 *
 * SELURUH berkas dibaca dan diperiksa lebih dulu; tidak ada satu pun baris yang
 * disimpan di sini. Yang dikembalikan hanya laporan — lihat
 * MaterialImportResult — dan penyimpanannya dikerjakan MaterialImporter setelah
 * pengelola menyetujui pratinjaunya.
 *
 * Tiga kolom harga turunan (Harga per gram, Pembulatan Harga, Harga/10 gram)
 * ikut dibaca tetapi tidak pernah disimpan: nilainya dicocokkan dengan hasil
 * rumus Pricing Engine, dan selisihnya dilaporkan sebagai catatan. Dengan
 * begitu berkas yang angka turunannya keliru tetap dapat dipakai, dan yang
 * masuk ke basis data tetap angka yang benar.
 */
class MaterialImportReader
{
    /**
     * Judul kolom dicari pada beberapa baris pertama.
     *
     * Berkas Price List yang beredar di tim memberi judul sheet dan baris
     * kosong sebelum tabelnya, jadi menuntut judul tepat di baris pertama akan
     * menolak berkas yang isinya sebenarnya benar.
     */
    private const HEADER_SEARCH_ROWS = 10;

    /** Batas wajar satu berkas, menjaga memori tetap terkendali. */
    private const MAX_ROWS = 2000;

    public function read(string $path, PrintTechnology $technology, ?string $fileName = null): MaterialImportResult
    {
        // Diperiksa lebih dulu supaya yang muncul bukan `Class "ZipArchive"
        // not found`, yang tidak memberi tahu pengelola apa pun.
        if (! ExcelRuntime::available()) {
            return MaterialImportResult::fatal(ExcelRuntime::unavailableMessage(), $fileName);
        }

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $sheet = $reader->load($path)->getActiveSheet();
            $grid = $sheet->toArray(null, true, false, false);
        } catch (ReaderException $exception) {
            return MaterialImportResult::fatal(
                'File tidak dapat dibaca sebagai Excel (.xlsx). Pastikan filenya tidak rusak dan bukan hasil ganti nama dari format lain.',
                $fileName,
            );
        } catch (Throwable $exception) {
            return MaterialImportResult::fatal('File gagal dibaca: '.$exception->getMessage(), $fileName);
        }

        $header = $this->locateHeader($grid, $technology);

        if ($header === null) {
            return MaterialImportResult::fatal(
                'Header kolom tidak ditemukan. Gunakan Template Excel: '.implode(', ', MaterialSheet::headings($technology)).'.',
                $fileName,
            );
        }

        [$headerLine, $map, $missing] = $header;

        if ($missing !== []) {
            return MaterialImportResult::fatal(
                'Kolom wajib tidak ada pada file: '.implode(', ', $missing).'. Unduh Template Excel lalu isi ulang.',
                $fileName,
            );
        }

        $rows = $this->readRows($grid, $headerLine, $map, $technology);

        if ($rows === []) {
            return MaterialImportResult::fatal('File tidak berisi satu baris data pun di bawah headernya.', $fileName);
        }

        return new MaterialImportResult(rows: $rows, fileName: $fileName);
    }

    /**
     * Cari baris judul, dan petakan tiap kolom ke posisinya.
     *
     * @param  array<int, array<int, mixed>>  $grid
     * @return array{0: int, 1: array<string, int>, 2: array<int, string>}|null
     */
    private function locateHeader(array $grid, PrintTechnology $technology): ?array
    {
        $accepted = MaterialSheet::acceptedHeadings($technology);

        // "No" hanya nomor urut di Excel, bukan penanda apa pun — berkas yang
        // tidak menyertakannya tetap sah.
        $required = ['material', 'brand', 'purchase_price', 'sale_price'];

        foreach (array_slice($grid, 0, self::HEADER_SEARCH_ROWS, true) as $line => $cells) {
            $map = [];

            foreach ((array) $cells as $index => $cell) {
                $heading = MaterialSheet::normalizeHeading(is_scalar($cell) ? (string) $cell : '');

                if ($heading === '') {
                    continue;
                }

                foreach ($accepted as $key => $variants) {
                    if (! isset($map[$key]) && in_array($heading, $variants, true)) {
                        $map[$key] = $index;
                    }
                }
            }

            // Baris judul dikenali bila memuat Material dan Brand sekaligus;
            // satu kata yang kebetulan sama tidak cukup.
            if (isset($map['material'], $map['brand'])) {
                $missing = array_values(array_filter(
                    $required,
                    fn (string $key) => ! isset($map[$key]),
                ));

                $missing = array_map(
                    fn (string $key) => $this->headingFor($key, $technology),
                    $missing,
                );

                return [$line, $map, $missing];
            }
        }

        return null;
    }

    /**
     * Periksa tiap baris data.
     *
     * @param  array<int, array<int, mixed>>  $grid
     * @param  array<string, int>  $map
     * @return array<int, MaterialImportRow>
     */
    private function readRows(array $grid, int $headerLine, array $map, PrintTechnology $technology): array
    {
        /*
         * Material yang sudah ada, dikelompokkan menurut namanya.
         *
         * Sengaja SELURUH baris yang senama dikumpulkan, bukan satu saja: kunci
         * unik tabelnya (teknologi, mesin, material) mengizinkan satu nama
         * dipakai beberapa mesin. Berkas Excel tidak menyebut mesin, jadi nama
         * yang menunjuk lebih dari satu baris tidak dapat ditentukan mana yang
         * dimaksud — lihat readRow(), yang menolaknya alih-alih menebak.
         */
        $existing = $technology->materials()
            ->get(['id', 'material', 'pricing_method', 'machine_cost_id'])
            ->groupBy(fn (PrintMaterial $material) => mb_strtolower(trim($material->material)));

        // Nama yang kembar DI DALAM berkas itu sendiri juga ditolak: dua baris
        // bernama sama akan saling menimpa tanpa pengelola menyadarinya.
        $seen = [];
        $rows = [];

        foreach ($grid as $line => $cells) {
            if ($line <= $headerLine) {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                break;
            }

            $cells = (array) $cells;

            if ($this->isBlank($cells)) {
                continue;
            }

            $rows[] = $this->readRow($cells, $line + 1, $map, $existing, $seen, $technology);
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @param  array<string, int>  $map
     * @param  Collection<string, Collection<int, PrintMaterial>>  $existing
     * @param  array<string, int>  $seen  nama yang sudah muncul => nomor barisnya
     */
    private function readRow(array $cells, int $line, array $map, $existing, array &$seen, PrintTechnology $technology): MaterialImportRow
    {
        $errors = [];
        $notes = [];

        $read = fn (string $key) => isset($map[$key]) ? ($cells[$map[$key]] ?? null) : null;

        $material = $this->text($read('material'));
        $brand = $this->text($read('brand'));
        $remark = $this->text($read('remark'));

        if ($material === null) {
            $errors[] = 'Material tidak boleh kosong.';
        } elseif (mb_strlen($material) > 255) {
            $errors[] = 'Material terlalu panjang, maksimal 255 karakter.';
        }

        if ($brand === null) {
            $errors[] = 'Brand tidak boleh kosong.';
        } elseif (mb_strlen($brand) > 255) {
            $errors[] = 'Brand terlalu panjang, maksimal 255 karakter.';
        }

        if ($remark !== null && mb_strlen($remark) > 255) {
            $errors[] = 'Remark terlalu panjang, maksimal 255 karakter.';
        }

        // Nama kembar: di dalam berkas sendiri, lalu terhadap basis data.
        $state = MaterialImportRow::NEW;
        $existingId = null;
        $current = null;

        if ($material !== null) {
            $key = mb_strtolower($material);

            if (isset($seen[$key])) {
                $errors[] = 'Material "'.$material.'" ditulis dua kali di file ini (baris '.$seen[$key].').';
            } else {
                $seen[$key] = $line;
            }

            $matches = $existing->get($key);

            if ($matches !== null && $matches->count() > 1) {
                /*
                 * Satu nama dipakai beberapa mesin.
                 *
                 * Kunci unik tabelnya (teknologi, mesin, material) memang
                 * mengizinkannya. Berkas Excel tidak menyebut mesin, jadi tidak
                 * ada cara mengetahui baris mana yang dimaksud — dan menebak
                 * berarti mengubah harga material yang salah. Barisnya karena
                 * itu ditolak sambil menyebut mesinnya, supaya pengelola dapat
                 * menyelesaikannya lewat form Ubah Material.
                 */
                $machines = $matches
                    ->map(fn (PrintMaterial $found) => $found->machine_label)
                    ->unique()
                    ->values();

                /*
                 * Dua bentuk yang berbeda, karena penyebabnya berbeda: nama
                 * yang sengaja dipakai di beberapa mesin, atau beberapa baris
                 * kembar pada mesin yang sama. Yang kedua hanya mungkin pada
                 * material "Tanpa Mesin" — kunci uniknya tidak menjangkau
                 * NULL — dan memang perlu dirapikan lebih dulu.
                 */
                $errors[] = $machines->count() > 1
                    ? 'Material "'.$material.'" ada pada lebih dari satu mesin ('.$machines->implode(', ')
                        .'), jadi tidak dapat ditentukan mana yang dimaksud. Ubah lewat form Ubah Material.'
                    : 'Ada '.$matches->count().' material bernama "'.$material.'" pada '.$machines->first()
                        .', jadi tidak dapat ditentukan mana yang dimaksud. Rapikan dulu lewat form Ubah Material.';
            } elseif ($matches !== null && $matches->isNotEmpty()) {
                $current = $matches->first();
                $state = MaterialImportRow::DUPLICATE;
                $existingId = $current->id;
            }
        }

        /*
         * Metode harga, hanya bagi teknologi yang memakainya (SLA/MJF/SLM).
         *
         * Kolomnya boleh tidak ada: material yang sudah ada mempertahankan
         * metodenya, dan material baru memakai bawaan teknologinya. Yang tidak
         * boleh adalah tulisan yang tidak dikenali — diam-diam menjadikannya
         * Otomatis dapat memasang harga pada material yang seharusnya menunggu
         * kuotasi tim, dan sebaliknya.
         */
        $method = null;

        if (MaterialSheet::hasPricingMethod($technology)) {
            $raw = $this->text($read('pricing_method'));

            if ($raw !== null) {
                $method = MaterialSheet::pricingMethodFrom($raw);

                if ($method === null) {
                    $errors[] = 'Metode Harga "'.$raw.'" tidak dikenali. Isi dengan salah satu: '
                        .implode(', ', PrintMaterial::PRICING_METHODS).'.';
                }
            } else {
                $method = $current?->pricing_method ?? PricingMethod::fallbackFor($technology->code);
            }
        }

        /*
         * Harga hanya wajib bagi material yang harganya memang dihitung.
         *
         * Material Kalkulator Manual harganya ditetapkan tim per penawaran,
         * jadi kolomnya boleh kosong dan tersimpan nol — persis seperti yang
         * dilakukan form Tambah/Ubah Material, lihat
         * App\Http\Requests\StorePrintMaterialRequest.
         */
        $pricesApply = $method !== PrintMaterial::PRICING_MANUAL;

        $purchase = $this->money($read('purchase_price'), 'Harga Beli', $errors, $pricesApply);
        $sale = $this->money($read('sale_price'), 'Harga Jual', $errors, $pricesApply);

        if ($pricesApply) {
            // Kolom turunan hanya dicocokkan, tidak pernah disimpan.
            if ($purchase !== null && $sale !== null) {
                $notes = array_merge($notes, $this->derivedNotes($read, $purchase, $sale));
            }
        } elseif (($purchase ?? 0.0) > 0 || ($sale ?? 0.0) > 0) {
            /*
             * Material Kalkulator Manual tersimpan berharga nol.
             *
             * Harganya ditetapkan tim per penawaran, jadi angka di berkas tidak
             * akan pernah dipakai — dan menyimpannya akan terbaca sebagai harga
             * yang berlaku. Form Tambah/Ubah Material melakukan hal yang sama;
             * yang ditambahkan di sini hanya keterangannya, supaya angka yang
             * hilang tidak mengejutkan.
             */
            $notes[] = 'Metode Harga Kalkulator Manual, jadi Harga Beli dan Harga Jual disimpan nol — '
                .'harganya ditetapkan tim per penawaran.';

            $purchase = 0.0;
            $sale = 0.0;
        }

        $values = [
            'material' => $material,
            'brand' => $brand,
            // Kolomnya NOT NULL; material Kalkulator Manual tersimpan nol.
            'purchase_price' => $purchase ?? 0.0,
            'sale_price' => $sale ?? 0.0,
            'remark' => $remark,
        ];

        if ($method !== null) {
            $values['pricing_method'] = $method;
        }

        return new MaterialImportRow(
            line: $line,
            material: $material,
            values: $values,
            errors: $errors,
            notes: $notes,
            state: $state,
            existingId: $existingId,
        );
    }

    /**
     * Selisih antara kolom turunan di berkas dan hasil rumus Pricing Engine.
     *
     * Bukan kesalahan: yang menentukan harga tetap Harga Beli dan Harga Jual,
     * jadi barisnya tetap dapat diimpor. Selisihnya dilaporkan supaya pengelola
     * tahu angka mana yang akan berlaku — dan menyadari bila berkasnya ternyata
     * disusun dengan rumus yang berbeda.
     *
     * @param  array<int, string>  $notes
     * @return array<int, string>
     */
    private function derivedNotes(callable $read, float $purchase, float $sale): array
    {
        $notes = [];

        $expected = [
            'price_per_gram' => ['Harga per gram', MaterialSheet::pricePerGram($purchase)],
            'rounded_price' => ['Pembulatan Harga', MaterialSheet::roundedPrice($sale)],
            'price_per_10_gram' => ['Harga/10 gram', MaterialSheet::pricePer10Gram($sale)],
        ];

        foreach ($expected as $key => [$label, $computed]) {
            $raw = $read($key);

            if ($raw === null || $raw === '') {
                continue;
            }

            $value = $this->number($raw);

            if ($value === null || (int) round($value) === $computed) {
                continue;
            }

            $notes[] = $label.' di file ('.$this->rupiah($value).') berbeda dari hasil rumus ('
                .$this->rupiah($computed).'); yang dipakai hasil rumus.';
        }

        return $notes;
    }

    /**
     * @param  array<int, string>  $errors
     * @param  bool  $required  false bagi material yang harganya ditetapkan tim
     */
    private function money(mixed $raw, string $label, array &$errors, bool $required = true): ?float
    {
        if ($raw === null || trim((string) $raw) === '') {
            if ($required) {
                $errors[] = $label.' tidak boleh kosong.';
            }

            return null;
        }

        $value = $this->number($raw);

        if ($value === null) {
            $errors[] = $label.' tidak valid: "'.trim((string) $raw).'" bukan angka.';

            return null;
        }

        if ($value < 0) {
            $errors[] = $label.' tidak boleh negatif.';

            return null;
        }

        // Kolomnya `decimal(12,2)`; nilai di atas ini baru gagal di basis data
        // sebagai galat 500, bukan sebagai pesan yang dapat dibaca.
        if ($value > 9999999999.99) {
            $errors[] = $label.' terlalu besar, maksimal Rp9.999.999.999.';

            return null;
        }

        return round($value, 2);
    }

    /**
     * Angka dari sel yang mungkin sudah terlanjur berupa teks.
     *
     * Sel yang ditulis sebagai angka datang apa adanya. Yang berupa teks —
     * hasil menyalin dari tampilan, mis. "Rp185.000" atau "185,000" — tetap
     * diterima selama bentuknya memang satu angka; teks yang tidak menyisakan
     * angka sama sekali dikembalikan null agar dilaporkan sebagai kesalahan.
     */
    private function number(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        $text = trim((string) $raw);

        if ($text === '') {
            return null;
        }

        // Buang lambang mata uang dan spasi, sisakan angka beserta pemisahnya.
        $text = preg_replace('/(?i)\brp\b|rp\.?|\s|\x{00A0}/u', '', $text);
        $text = str_replace(['(', ')'], '', $text);

        if (! preg_match('/^-?[\d.,]+$/', $text)) {
            return null;
        }

        /*
         * Pemisah ribuan dan desimal ditulis berbeda-beda. Yang menentukan
         * adalah tanda TERAKHIR: bila sisanya tepat dua digit, tanda itu
         * desimal; selain itu seluruh tanda dianggap pemisah ribuan.
         */
        $lastDot = strrpos($text, '.');
        $lastComma = strrpos($text, ',');
        $last = max($lastDot === false ? -1 : $lastDot, $lastComma === false ? -1 : $lastComma);

        if ($last >= 0 && strlen($text) - $last - 1 === 2 && substr_count($text, $text[$last]) === 1) {
            $text = str_replace(['.', ','], ['', ''], substr($text, 0, $last)).'.'.substr($text, $last + 1);
        } else {
            $text = str_replace(['.', ','], '', $text);
        }

        return is_numeric($text) ? (float) $text : null;
    }

    private function text(mixed $raw): ?string
    {
        $text = trim((string) ($raw ?? ''));

        return $text === '' ? null : $text;
    }

    /** @param  array<int, mixed>  $cells */
    private function isBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) ($cell ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function headingFor(string $key, PrintTechnology $technology): string
    {
        foreach (MaterialSheet::columns($technology) as $column) {
            if ($column['key'] === $key) {
                return $column['heading'];
            }
        }

        return $key;
    }

    private function rupiah(float|int $value): string
    {
        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }
}
