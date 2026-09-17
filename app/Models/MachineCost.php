<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris Price List biaya mesin.
 *
 * Belum dipakai menghitung biaya Calculator (tarif mesin yang berjalan masih
 * `machine_rate_per_hour` per printer di config/printing.php) — untuk saat
 * ini murni dikelola di halaman Price List, menunggu aturan pemakaiannya
 * ditentukan (mis. pemetaan printer ke baris Machine Cost).
 *
 * Formula (dicocokkan persis dengan data NUSAMA3D):
 *   listrik/jam   = watt_kwh x harga_listrik
 *   machine cost  = round((listrik/jam + depresiasi) x 1,5)
 *   pembulatan    = ceil(machine_cost / 1000) x 1000
 */
class MachineCost extends Model
{
    /** Ditampilkan menggantikan spesifikasi yang belum diisi. */
    public const UNSET = '-';

    protected $fillable = [
        'mesin',
        'print_technology_id',
        'printer_key',
        'watt_kwh',
        'harga_listrik',
        'depresiasi',
        'width_mm',
        'depth_mm',
        'height_mm',
        'weight_kg',
        'build_volume_x',
        'build_volume_y',
        'build_volume_z',
    ];

    protected function casts(): array
    {
        return [
            'watt_kwh' => 'decimal:3',
            'harga_listrik' => 'decimal:2',
            'depresiasi' => 'decimal:2',
            'width_mm' => 'decimal:1',
            'depth_mm' => 'decimal:1',
            'height_mm' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'build_volume_x' => 'integer',
            'build_volume_y' => 'integer',
            'build_volume_z' => 'integer',
        ];
    }

    /**
     * Teknologi tempat mesin ini dikelompokkan pada Price List.
     *
     * Boleh kosong: baris lama belum ditentukan teknologinya, dan teknologi
     * yang dihapus meninggalkan mesinnya tanpa induk. Keduanya tampil di
     * kelompok "Tanpa Teknologi", bukan hilang dari daftar.
     */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(PrintTechnology::class, 'print_technology_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where('mesin', 'like', "%{$term}%"));
    }

    /**
     * Urutan tampil: mengikuti urutan teknologi, lalu nama mesin.
     *
     * Barisnya perlu berkelompok rapat supaya judul teknologi pada tabel tidak
     * terpecah — mesin tanpa teknologi selalu jatuh paling bawah.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->select('machine_costs.*')
            ->leftJoin('print_technologies', 'print_technologies.id', '=', 'machine_costs.print_technology_id')
            ->orderByRaw('CASE WHEN print_technologies.id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('print_technologies.sort_order')
            ->orderBy('print_technologies.code')
            ->orderBy('machine_costs.mesin');
    }

    /* ------------------------------------------------ spesifikasi fisik --- */

    /**
     * Isi tabel detail mesin pada Price List.
     *
     * Disusun di sini, bukan di Blade, supaya labelnya sejalan dengan kolom
     * basis datanya dan tidak ada satu angka pun yang ditulis di tampilan.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function getSpecRowsAttribute(): array
    {
        return [
            ['label' => 'Lebar (W)', 'value' => $this->millimetres($this->width_mm)],
            ['label' => 'Kedalaman (D)', 'value' => $this->millimetres($this->depth_mm)],
            ['label' => 'Tinggi (H)', 'value' => $this->millimetres($this->height_mm)],
            ['label' => 'Berat', 'value' => $this->weight_label],
            ['label' => 'Volume cetak', 'value' => $this->build_volume_label],
        ];
    }

    /** Apakah ada satu saja spesifikasi yang sudah diisi. */
    public function hasSpecs(): bool
    {
        foreach ($this->spec_rows as $row) {
            if ($row['value'] !== self::UNSET) {
                return true;
            }
        }

        return false;
    }

    public function getWeightLabelAttribute(): string
    {
        return $this->weight_kg === null
            ? self::UNSET
            : number_format((float) $this->weight_kg, 2, ',', '.').' kg';
    }

    /**
     * Volume cetak sebagai {x, y, z}, atau null bila belum lengkap.
     *
     * Bentuknya sengaja sama dengan `build_volume` pada print_technologies dan
     * dengan `maxSize` material, sehingga nilai ini dapat langsung dipakai
     * sebagai batas ukuran cetak tanpa penerjemahan di mana pun.
     *
     * @return array{x: int, y: int, z: int}|null
     */
    public function getBuildVolumeAttribute(): ?array
    {
        $sides = [
            'x' => $this->build_volume_x,
            'y' => $this->build_volume_y,
            'z' => $this->build_volume_z,
        ];

        // Ketiga sisinya harus ada: dua sisi saja bukan volume.
        if (in_array(null, $sides, true)) {
            return null;
        }

        return array_map('intval', $sides);
    }

    /** Volume cetak sebagai "256 x 256 x 256 mm"; butuh ketiga sisinya. */
    public function getBuildVolumeLabelAttribute(): string
    {
        $volume = $this->build_volume;

        return $volume === null ? self::UNSET : implode(' × ', $volume).' mm';
    }

    /** Angka milimeter tanpa nol di belakang koma: 389 mm, 389,5 mm. */
    private function millimetres(int|string|float|null $value): string
    {
        if ($value === null) {
            return self::UNSET;
        }

        $number = (float) $value;
        $decimals = fmod($number, 1.0) === 0.0 ? 0 : 1;

        return number_format($number, $decimals, ',', '.').' mm';
    }

    /** Biaya listrik per jam pemakaian. */
    public function getElectricityPerHourAttribute(): float
    {
        return ((float) $this->watt_kwh) * ((float) $this->harga_listrik);
    }

    /** Biaya mesin per jam: listrik + depresiasi, dikali margin 1,5x. */
    public function getMachineCostAttribute(): int
    {
        return (int) round(($this->electricity_per_hour + (float) $this->depresiasi) * 1.5);
    }

    /** Biaya mesin dibulatkan ke atas kelipatan seribu rupiah. */
    public function getRoundedMachineCostAttribute(): int
    {
        return (int) (ceil($this->machine_cost / 1000) * 1000);
    }

    /**
     * Nama printer Calculator yang memakai Machine Cost baris ini, mis.
     * "Creality Ender 3", atau null bila belum dipetakan.
     *
     * Pemetaannya eksplisit (kolom `printer_key`) dan dibaca Pricing Engine —
     * lihat App\Services\SellingPriceEstimator::machineFor().
     */
    public function getPrinterLabelAttribute(): ?string
    {
        return \App\Support\Printer::exists($this->printer_key)
            ? \App\Support\Printer::name($this->printer_key)
            : null;
    }
}
