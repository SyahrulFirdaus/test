<?php

namespace App\Services\PriceList\Excel;

/**
 * Hasil pembacaan satu berkas Import, sebelum apa pun disimpan.
 *
 * Seluruh berkas dibaca dan diperiksa lebih dulu, lalu hasilnya dikembalikan
 * utuh seperti ini — bukan disimpan baris demi baris sambil dibaca. Itulah yang
 * membuat pratinjau mungkin: pengelola melihat apa yang akan terjadi sebelum
 * satu baris pun menyentuh basis data.
 */
class MaterialImportResult
{
    /**
     * @param  array<int, MaterialImportRow>  $rows
     * @param  array<int, string>  $fatal  alasan berkasnya tidak dapat dipakai sama sekali
     */
    public function __construct(
        public readonly array $rows = [],
        public readonly array $fatal = [],
        public readonly ?string $fileName = null,
    ) {}

    /** Berkasnya sendiri bermasalah — header salah, kosong, atau tidak terbaca. */
    public function isFatal(): bool
    {
        return $this->fatal !== [];
    }

    public static function fatal(string $reason, ?string $fileName = null): self
    {
        return new self(fatal: [$reason], fileName: $fileName);
    }

    public function total(): int
    {
        return count($this->rows);
    }

    /** @return array<int, MaterialImportRow> */
    public function valid(): array
    {
        return array_values(array_filter($this->rows, fn (MaterialImportRow $row) => $row->isValid()));
    }

    /** @return array<int, MaterialImportRow> */
    public function invalid(): array
    {
        return array_values(array_filter($this->rows, fn (MaterialImportRow $row) => ! $row->isValid()));
    }

    /** Baris sah yang namanya sudah ada — menunggu keputusan Skip atau Update. */
    public function duplicates(): array
    {
        return array_values(array_filter(
            $this->valid(),
            fn (MaterialImportRow $row) => $row->isDuplicate(),
        ));
    }

    public function hasAnythingToImport(): bool
    {
        return $this->valid() !== [];
    }

    /** Bentuk yang disimpan di sesi antara pratinjau dan penyimpanan. */
    public function toArray(): array
    {
        return [
            'file_name' => $this->fileName,
            'fatal' => $this->fatal,
            'rows' => array_map(fn (MaterialImportRow $row) => $row->toArray(), $this->rows),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            rows: array_map(
                fn (array $row) => MaterialImportRow::fromArray($row),
                (array) ($data['rows'] ?? []),
            ),
            fatal: (array) ($data['fatal'] ?? []),
            fileName: $data['file_name'] ?? null,
        );
    }
}
