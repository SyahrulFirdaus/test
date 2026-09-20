<?php

namespace App\Services\PriceList\Excel;

/**
 * Satu baris berkas Import setelah diperiksa.
 *
 * Membawa nomor barisnya di dalam berkas (bukan nomor pada kolom "No", yang
 * hanya hiasan) supaya pesan kesalahan menunjuk tempat yang benar-benar dapat
 * dibuka pengelola di Excel.
 */
class MaterialImportRow
{
    /** Baris ini akan MENAMBAH material baru. */
    public const NEW = 'new';

    /** Namanya sudah ada pada teknologi ini. */
    public const DUPLICATE = 'duplicate';

    /**
     * @param  int  $line  nomor baris di dalam berkas Excel
     * @param  array<string, mixed>  $values  nilai kolom yang disimpan
     * @param  array<int, string>  $errors  alasan baris ini tidak dapat dipakai
     * @param  array<int, string>  $notes  catatan yang tidak menggugurkan baris
     */
    public function __construct(
        public readonly int $line,
        public readonly ?string $material,
        public readonly array $values,
        public readonly array $errors,
        public readonly array $notes,
        public readonly string $state,
        public readonly ?int $existingId,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function isDuplicate(): bool
    {
        return $this->state === self::DUPLICATE;
    }

    /** Bentuk ringkas untuk tabel pratinjau dan untuk disimpan di sesi. */
    public function toArray(): array
    {
        return [
            'line' => $this->line,
            'material' => $this->material,
            'values' => $this->values,
            'errors' => $this->errors,
            'notes' => $this->notes,
            'state' => $this->state,
            'existing_id' => $this->existingId,
        ];
    }

    public static function fromArray(array $row): self
    {
        return new self(
            line: (int) $row['line'],
            material: $row['material'] ?? null,
            values: (array) ($row['values'] ?? []),
            errors: (array) ($row['errors'] ?? []),
            notes: (array) ($row['notes'] ?? []),
            state: (string) ($row['state'] ?? self::NEW),
            existingId: isset($row['existing_id']) ? (int) $row['existing_id'] : null,
        );
    }
}
