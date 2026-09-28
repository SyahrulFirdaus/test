<?php

namespace App\Services\PriceList\Excel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mesin Import/Export Excel yang dipakai bersama halaman Price List.
 *
 * Alurnya sama persis dengan Import Material (lihat MaterialImportReader dan
 * MaterialImporter), hanya dibuat umum supaya Teknologi dan Machine Cost tidak
 * masing-masing menulis ulang pembaca, pemeriksa, dan penyimpannya:
 *
 *   Pilih File → Validasi File → Baca Data → Validasi Kolom & Data →
 *   Pratinjau → Konfirmasi → Simpan dalam satu transaksi.
 *
 * Tiap turunan hanya menyebut apa yang memang miliknya: model dan tabelnya,
 * definisi kolom beserta batas nilainya (diselaraskan dengan Form Request
 * CRUD-nya), kunci pengenal baris, dan pemeriksaan tambahan yang tidak dapat
 * dinyatakan per kolom. Hasil pembacaan memakai MaterialImportResult dan
 * MaterialImportRow yang sama dengan Import Material, jadi sesi, pratinjau,
 * dan penanganan Skip/Update pun berperilaku sama.
 *
 * Definisi kolom (lihat columns()):
 *
 *   key         nama kolom basis data, atau penanda internal;
 *   heading     judul kolom di berkas; `aliases` judul lain yang diterima;
 *   type        text | integer | decimal | money | boolean;
 *   required    wajib diisi pada setiap baris;
 *   min/max     batas nilai angka; `max_length` batas panjang teks;
 *   blank       'null' (bawaan): sel kosong menyimpan kosong;
 *               'keep': sel kosong mempertahankan nilai yang ada, dan baris
 *               baru memakai `default`;
 *   stored      false bila nilainya diterjemahkan dulu sebelum disimpan
 *               (mis. kode teknologi → print_technology_id);
 *   derived     dihitung dari kolom lain lewat `compute`; hanya dicocokkan,
 *               tidak pernah disimpan;
 *   money/width/wrap  tampilan di berkas, dibaca MaterialSheetWriter.
 */
abstract class SpreadsheetDataset
{
    use ReadsSpreadsheet;

    /** Sama dengan Import Material, supaya pilihan pada pratinjau bernilai sama. */
    public const ON_DUPLICATE_SKIP = MaterialImporter::ON_DUPLICATE_SKIP;

    public const ON_DUPLICATE_UPDATE = MaterialImporter::ON_DUPLICATE_UPDATE;

    /** Sama dengan pembaca material. */
    private const HEADER_SEARCH_ROWS = 10;

    private const MAX_ROWS = 2000;

    /* ======================================================= identitas === */

    /** Penanda pendek untuk sesi dan log, mis. "technology". */
    abstract public function key(): string;

    /** Nama halaman untuk tampilan, mis. "Teknologi". */
    abstract public function title(): string;

    /** Sebutan satu baris, mis. "teknologi" atau "mesin". */
    abstract public function noun(): string;

    /** Awal nama berkas unduhan, mis. "Teknologi" → Teknologi_2026-09-27.xlsx. */
    abstract public function fileStem(): string;

    /** Kolom yang menjadi pengenal baris — dipakai mendeteksi data kembar. */
    abstract public function identityKey(): string;

    /** @return array<int, array<string, mixed>> */
    abstract public function columns(): array;

    /** Seluruh baris yang dikelola halaman ini, urut seperti tabelnya. */
    abstract protected function query(): Builder;

    /**
     * Data acuan berkas Contoh, sebagai [kolom => nilai].
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function exampleRecords(): array;

    /** Nilai yang dicatat Activity Log untuk satu baris. */
    abstract protected function snapshot(Model $model): array;

    /**
     * Dua kolom yang bersama-sama menandai baris judul — satu kata yang
     * kebetulan sama tidak cukup.
     *
     * @return array{0: string, 1: string}
     */
    abstract protected function headerAnchors(): array;

    /** Warna judul kolom (ARGB): latar dan tulisan. */
    public function headerTheme(): array
    {
        return ['FF1F3864', 'FFFFFFFF'];
    }

    /** Label nilai boolean untuk kolom tertentu: [benar, salah]. */
    protected function booleanLabels(array $column): array
    {
        return $column['labels'] ?? ['Ya', 'Tidak'];
    }

    /* ==================================================== pemeriksaan === */

    /**
     * Pemeriksaan tambahan satu baris — antar kolom, atau terhadap tabel lain.
     *
     * Dipanggil setelah seluruh kolomnya terbaca dan dinyatakan bertipe benar.
     *
     * @param  array<string, mixed>  $values
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $notes
     */
    protected function validateRow(array &$values, array &$errors, array &$notes, ?Model $current): void {}

    /** Dipanggil sekali sebelum baris pertama dibaca — untuk menyetel ulang penanda. */
    protected function beforeRead(): void {}

    /**
     * Pemeriksaan terakhir di dalam transaksi, setelah seluruh baris tersimpan.
     *
     * Lempar SpreadsheetImportException untuk membatalkan seluruhnya.
     */
    protected function afterRows(): void {}

    /** Dipanggil setelah transaksi berhasil, mis. untuk menggugurkan cache. */
    protected function afterImport(): void {}

    /**
     * Kolom tambahan yang disimpan meski bukan kolom berkas, mis. hasil
     * penerjemahan kode teknologi menjadi id-nya.
     *
     * @return array<int, string>
     */
    protected function extraStoredKeys(): array
    {
        return [];
    }

    /** Baris baru. Turunan boleh menimpanya, mis. untuk menghidupkan baris arsip. */
    protected function createRecord(array $values): Model
    {
        return $this->query()->getModel()->newQuery()->create($values);
    }

    /** Bentuk pengenal yang dibandingkan: tanpa beda huruf besar-kecil. */
    protected function normalizeIdentity(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /* ========================================================= kolom === */

    /** @return array<int, string> */
    public function headings(): array
    {
        return array_column($this->columns(), 'heading');
    }

    /**
     * Kolom yang tampil pada tabel pratinjau: seluruh isian, tanpa No dan
     * tanpa kolom turunan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function previewColumns(): array
    {
        return array_values(array_filter(
            $this->columns(),
            fn (array $column) => $column['key'] !== 'no' && ! ($column['derived'] ?? false),
        ));
    }

    /** Nilai satu sel untuk tabel pratinjau. */
    public function display(array $column, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return match ($column['type'] ?? 'text') {
            'money' => $this->rupiah((float) $value),
            'boolean' => $this->booleanLabels($column)[$value ? 0 : 1],
            'integer', 'decimal' => $this->formatNumber((float) $value),
            default => (string) $value,
        };
    }

    /* ======================================================== unduhan === */

    /** @return array<int, array<int, mixed>> */
    public function exportRows(): array
    {
        return $this->query()->get()
            ->values()
            ->map(fn (Model $model, int $index) => $this->line($this->exportRecord($model), $index + 1))
            ->all();
    }

    /** @return array<int, array<int, mixed>> */
    public function exampleRows(): array
    {
        return array_map(
            fn (array $record, int $index) => $this->line($record, $index + 1),
            $this->exampleRecords(),
            array_keys($this->exampleRecords()),
        );
    }

    public function exportCount(): int
    {
        return $this->query()->count();
    }

    /**
     * Nilai satu model untuk berkas Export, sebagai [kolom => nilai].
     *
     * Bawaannya membaca atribut bernama sama; turunan menimpanya untuk kolom
     * yang perlu diterjemahkan (mis. id teknologi → kodenya).
     */
    protected function exportRecord(Model $model): array
    {
        $record = [];

        foreach ($this->columns() as $column) {
            if ($column['key'] !== 'no' && ! ($column['derived'] ?? false)) {
                $record[$column['key']] = $model->getAttribute($column['key']);
            }
        }

        return $record;
    }

    /**
     * Satu baris berkas dari [kolom => nilai]. Angka tetap angka; boolean
     * ditulis sebagai labelnya; kolom turunan dihitung dari baris itu sendiri.
     *
     * @return array<int, mixed>
     */
    private function line(array $record, int $number): array
    {
        $line = [];

        foreach ($this->columns() as $column) {
            $key = $column['key'];
            $record[$key] ??= null;

            $line[] = match (true) {
                $key === 'no' => $number,
                (bool) ($column['derived'] ?? false) => ($column['compute'])($record),
                ($column['type'] ?? 'text') === 'boolean' => $record[$key] === null ? '' : $this->booleanLabels($column)[$record[$key] ? 0 : 1],
                in_array($column['type'] ?? 'text', ['integer', 'decimal', 'money'], true) => $record[$key] === null ? null : (float) $record[$key],
                default => $record[$key] === null ? '' : (string) $record[$key],
            };
        }

        return $line;
    }

    /* ========================================================= membaca === */

    /**
     * Baca dan periksa seluruh berkas tanpa menyimpan apa pun.
     */
    public function read(string $path, ?string $fileName = null): MaterialImportResult
    {
        $grid = $this->loadGrid($path);

        if (is_string($grid)) {
            return MaterialImportResult::fatal($grid, $fileName);
        }

        $header = $this->locateHeader($grid);

        if ($header === null) {
            return MaterialImportResult::fatal(
                'Header kolom tidak ditemukan. Gunakan Template Excel: '.implode(', ', $this->headings()).'.',
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

        $this->beforeRead();

        $existing = $this->query()->get()
            ->groupBy(fn (Model $model) => $this->normalizeIdentity((string) $model->getAttribute($this->identityKey())));

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

            $rows[] = $this->readRow($cells, $line + 1, $map, $existing, $seen);
        }

        if ($rows === []) {
            return MaterialImportResult::fatal('File tidak berisi satu baris data pun di bawah headernya.', $fileName);
        }

        return new MaterialImportResult(rows: $rows, fileName: $fileName);
    }

    /**
     * @param  array<int, array<int, mixed>>  $grid
     * @return array{0: int, 1: array<string, int>, 2: array<int, string>}|null
     */
    private function locateHeader(array $grid): ?array
    {
        $accepted = [];

        foreach ($this->columns() as $column) {
            $accepted[$column['key']] = array_map(
                fn (string $heading) => MaterialSheet::normalizeHeading($heading),
                [$column['heading'], ...($column['aliases'] ?? [])],
            );
        }

        [$first, $second] = $this->headerAnchors();

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

            if (isset($map[$first], $map[$second])) {
                $missing = [];

                foreach ($this->columns() as $column) {
                    if (($column['required'] ?? false) && ! isset($map[$column['key']])) {
                        $missing[] = $column['heading'];
                    }
                }

                return [$line, $map, $missing];
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @param  array<string, int>  $map
     * @param  Collection<string, Collection<int, Model>>  $existing
     * @param  array<string, int>  $seen
     */
    private function readRow(array $cells, int $line, array $map, Collection $existing, array &$seen): MaterialImportRow
    {
        $errors = [];
        $notes = [];
        $values = [];
        $blank = [];

        $raw = fn (string $key) => isset($map[$key]) ? ($cells[$map[$key]] ?? null) : null;

        foreach ($this->columns() as $column) {
            if ($column['key'] === 'no' || ($column['derived'] ?? false)) {
                continue;
            }

            $value = $this->parseCell($column, $raw($column['key']), $errors);

            if ($value === null) {
                $blank[] = $column['key'];
            }

            $values[$column['key']] = $value;
        }

        // Data kembar: di dalam berkas sendiri, lalu terhadap basis data.
        $identity = $values[$this->identityKey()] ?? null;
        $state = MaterialImportRow::NEW;
        $current = null;

        if (is_string($identity) && $identity !== '') {
            $key = $this->normalizeIdentity($identity);
            $label = $this->headingOf($this->identityKey());

            if (isset($seen[$key])) {
                $errors[] = $label.' "'.$identity.'" ditulis dua kali di file ini (baris '.$seen[$key].').';
            } else {
                $seen[$key] = $line;
            }

            $matches = $existing->get($key);

            if ($matches !== null && $matches->count() > 1) {
                // Tidak dapat ditentukan baris mana yang dimaksud; menebaknya
                // berarti mengubah data yang salah.
                $errors[] = 'Ada '.$matches->count().' '.$this->noun().' dengan '.mb_strtolower($label).' "'.$identity
                    .'", jadi tidak dapat ditentukan mana yang dimaksud. Rapikan dulu lewat form Ubah.';
            } elseif ($matches !== null && $matches->isNotEmpty()) {
                $current = $matches->first();
                $state = MaterialImportRow::DUPLICATE;
            }
        }

        // Sel kosong pada kolom opsional.
        foreach ($blank as $key) {
            $column = $this->column($key);

            if (($column['blank'] ?? 'null') !== 'keep') {
                continue;
            }

            $values[$key] = $current !== null
                ? $current->getAttribute($key)
                : (is_callable($column['default'] ?? null) ? ($column['default'])() : ($column['default'] ?? null));
        }

        if ($errors === []) {
            $this->validateRow($values, $errors, $notes, $current);
        }

        // Kolom turunan hanya dicocokkan, tidak pernah disimpan.
        if ($errors === []) {
            foreach ($this->columns() as $column) {
                if (! ($column['derived'] ?? false) || $this->text($raw($column['key'])) === null) {
                    continue;
                }

                $inFile = $this->number($raw($column['key']));
                $computed = ($column['compute'])($values);

                if ($inFile !== null && (int) round($inFile) !== (int) round($computed)) {
                    $notes[] = $column['heading'].' di file ('.$this->display($column, $inFile).') berbeda dari hasil rumus ('
                        .$this->display($column, $computed).'); yang dipakai hasil rumus.';
                }
            }
        }

        return new MaterialImportRow(
            line: $line,
            material: is_string($identity) ? $identity : null,
            values: $values,
            errors: $errors,
            notes: $notes,
            state: $state,
            existingId: $current?->getKey(),
        );
    }

    /**
     * Nilai satu sel sesuai tipe kolomnya, atau null bila kosong/tidak sah.
     *
     * @param  array<int, string>  $errors
     */
    private function parseCell(array $column, mixed $raw, array &$errors): mixed
    {
        $label = $column['heading'];
        $type = $column['type'] ?? 'text';

        if ($raw === null || trim((string) $raw) === '') {
            if ($column['required'] ?? false) {
                $errors[] = $label.' tidak boleh kosong.';
            }

            return null;
        }

        if ($type === 'text') {
            $text = (string) $this->text($raw);

            if ($column['uppercase'] ?? false) {
                $text = mb_strtoupper($text);
            }

            if (isset($column['max_length']) && mb_strlen($text) > $column['max_length']) {
                $errors[] = $label.' terlalu panjang, maksimal '.$column['max_length'].' karakter.';

                return null;
            }

            if (isset($column['pattern']) && ! preg_match($column['pattern'], $text)) {
                $errors[] = $column['pattern_message'] ?? $label.' tidak valid.';

                return null;
            }

            return $text;
        }

        if ($type === 'boolean') {
            $value = $this->booleanFrom((string) $raw, $column);

            if ($value === null) {
                [$yes, $no] = $this->booleanLabels($column);
                $errors[] = $label.' "'.trim((string) $raw).'" tidak dikenali. Isi dengan '.$yes.' atau '.$no.'.';
            }

            return $value;
        }

        $value = $type === 'decimal' ? $this->decimal($raw) : $this->number($raw);

        if ($value === null) {
            $errors[] = $label.' tidak valid: "'.trim((string) $raw).'" bukan angka.';

            return null;
        }

        if ($type === 'integer' && floor($value) !== $value) {
            $errors[] = $label.' harus bilangan bulat.';

            return null;
        }

        $min = $column['min'] ?? null;
        $max = $column['max'] ?? null;

        if ($min !== null && $value < $min) {
            $errors[] = $min == 0
                ? $label.' tidak boleh negatif.'
                : $label.' minimal '.$this->display($column, $min).'.';

            return null;
        }

        if ($max !== null && $value > $max) {
            $errors[] = $label.' terlalu besar, maksimal '.$this->display($column, $max).'.';

            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'money' => round($value, 2),
            // Skala kolomnya dijaga basis data; `decimals` hanya bila perlu dipastikan.
            default => isset($column['decimals']) ? round($value, (int) $column['decimals']) : $value,
        };
    }

    /**
     * Angka desimal kecil (rasio, milimeter, jam).
     *
     * Berbeda dengan rupiah: "0,313" atau "0.313" di sini berarti nol koma
     * tiga, bukan tiga ratus tiga belas. Satu tanda pemisah saja karena itu
     * dianggap koma desimal; selebihnya dibaca seperti angka rupiah.
     */
    protected function decimal(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        $text = preg_replace('/\s|\x{00A0}/u', '', trim((string) $raw));

        if (preg_match('/^-?\d*[.,]\d+$/', $text) || preg_match('/^-?\d+$/', $text)) {
            return (float) str_replace(',', '.', $text);
        }

        return $this->number($raw);
    }

    private function booleanFrom(string $raw, array $column): ?bool
    {
        $text = mb_strtolower(trim($raw));
        [$yes, $no] = array_map('mb_strtolower', $this->booleanLabels($column));

        return match (true) {
            in_array($text, [$yes, 'ya', 'yes', 'y', 'true', '1', 'aktif', 'active'], true) => true,
            in_array($text, [$no, 'tidak', 'no', 'n', 'false', '0', 'nonaktif', 'non-aktif', 'inactive'], true) => false,
            default => null,
        };
    }

    /* ======================================================= menyimpan === */

    /**
     * Simpan baris sah dari pratinjau, seluruhnya dalam satu transaksi.
     *
     * @param  array<int, MaterialImportRow>  $rows
     * @return array{created: int, updated: int, skipped: int, changes: array<int, array<string, mixed>>}
     *
     * @throws SpreadsheetImportException
     */
    public function import(array $rows, string $onDuplicate): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $changes = [];

        DB::transaction(function () use ($rows, $onDuplicate, &$created, &$updated, &$skipped, &$changes) {
            foreach ($rows as $row) {
                if (! $row->isValid()) {
                    continue;
                }

                $values = $this->storable($row->values);

                if ($row->isDuplicate()) {
                    if ($onDuplicate !== self::ON_DUPLICATE_UPDATE) {
                        $skipped++;

                        continue;
                    }

                    // Dicari ulang lewat id dari pratinjau. Bila barisnya sudah
                    // dihapus orang lain sejak itu, diperlakukan sebagai baru.
                    $model = $this->query()->getModel()->newQuery()->find($row->existingId);

                    if ($model !== null) {
                        $before = $this->snapshot($model);
                        $model->update($values);
                        $after = $this->snapshot($model->refresh());

                        if ($before !== $after) {
                            $changes[] = [$this->noun() => $row->material, 'sebelum' => $before, 'sesudah' => $after];
                        }

                        $updated++;

                        continue;
                    }
                }

                $model = $this->createRecord($values);
                $changes[] = [$this->noun() => $row->material, 'sebelum' => null, 'sesudah' => $this->snapshot($model)];
                $created++;
            }

            $this->afterRows();
        });

        $this->afterImport();

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'changes' => $changes];
    }

    /** Hanya kolom yang memang tersimpan di tabelnya. */
    private function storable(array $values): array
    {
        $keys = $this->extraStoredKeys();

        foreach ($this->columns() as $column) {
            if ($column['key'] !== 'no' && ! ($column['derived'] ?? false) && ($column['stored'] ?? true)) {
                $keys[] = $column['key'];
            }
        }

        return Arr::only($values, $keys);
    }

    /* ======================================================== utilitas === */

    protected function column(string $key): array
    {
        foreach ($this->columns() as $column) {
            if ($column['key'] === $key) {
                return $column;
            }
        }

        return ['key' => $key, 'heading' => $key];
    }

    protected function headingOf(string $key): string
    {
        return $this->column($key)['heading'];
    }

    /** 0,313 · 12.000 · 1,5 — tanpa nol yang tidak perlu. */
    protected function formatNumber(float $value): string
    {
        $formatted = number_format($value, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
