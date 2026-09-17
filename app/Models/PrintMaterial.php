<?php

namespace App\Models;

use App\Models\Concerns\HasMaterialPricing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris material pada Price List, milik satu teknologi.
 *
 * Menggantikan `fdm_materials` dan `sla_materials` sebagai tempat tinggal
 * seluruh material — termasuk milik teknologi yang ditambahkan Superadmin
 * kemudian. Rumus harganya tidak berubah sedikit pun: tetap
 * App\Models\Concerns\HasMaterialPricing yang sama.
 *
 * App\Models\FdmMaterial dan App\Models\SlaMaterial kini hanyalah pandangan
 * tersaring atas tabel ini, supaya halaman Price List FDM/SLA yang sudah ada
 * beserta seluruh pengujiannya tetap berjalan apa adanya.
 */
class PrintMaterial extends Model
{
    use HasMaterialPricing;

    protected $table = 'print_materials';

    /** Kalkulator Otomatis: rumus Harga Jual yang sama dengan FDM. */
    public const PRICING_AUTOMATIC = 'automatic';

    /** Kalkulator Manual: harga ditetapkan tim lewat kuotasi JLC. */
    public const PRICING_MANUAL = 'manual';

    /** @var array<string, string> */
    public const PRICING_METHODS = [
        self::PRICING_AUTOMATIC => 'Kalkulator Otomatis',
        self::PRICING_MANUAL => 'Kalkulator Manual',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Trait menetapkan kolom komersialnya; kepemilikan teknologi dan mesin
        // ditambahkan di sini agar trait itu tetap dipakai bersama apa adanya.
        $this->mergeFillable(['print_technology_id', 'machine_cost_id', 'pricing_method', 'is_active']);
    }

    /**
     * Metode penentuan harga material ini.
     *
     * Hanya dibaca untuk teknologi SLA (lihat App\Support\SlaIndustries);
     * teknologi lain selalu dihitung otomatis apa pun isinya.
     */
    public function usesAutomaticPricing(): bool
    {
        return $this->pricing_method === self::PRICING_AUTOMATIC;
    }

    public function getPricingMethodLabelAttribute(): string
    {
        return self::PRICING_METHODS[$this->pricing_method] ?? self::PRICING_METHODS[self::PRICING_MANUAL];
    }

    /**
     * Metode harga material bernama `$material` milik satu teknologi.
     *
     * Satu nama boleh dipakai beberapa mesin; yang berlaku baris TERTUA, sama
     * seperti PrintTechnology::toEstimatorArray(). Null bila tidak ditemukan.
     */
    public static function pricingMethodFor(string $technology, string $material): ?string
    {
        return PrintTechnology::findByCode($technology)?->materials
            ->sortBy('id')
            ->firstWhere('material', $material)
            ?->pricing_method;
    }

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'technical_spec' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** Aktif: dapat dipilih pada Edit Specification. */
    public function isOffered(): bool
    {
        return $this->is_active !== false;
    }

    public function technology(): BelongsTo
    {
        return $this->belongsTo(PrintTechnology::class, 'print_technology_id');
    }

    /**
     * Mesin tempat material ini dipakai, satu baris dari Machine Cost.
     *
     * Boleh kosong — material lama belum ditentukan mesinnya, dan mesin yang
     * dihapus meninggalkan materialnya di kelompok "Tanpa Mesin".
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(MachineCost::class, 'machine_cost_id');
    }

    /** Nama mesin untuk judul kelompok pada Price List. */
    public function getMachineLabelAttribute(): string
    {
        return $this->machine?->mesin ?? 'Tanpa Mesin';
    }

    /**
     * Urutan tampil Price List: per mesin, lalu nama material.
     *
     * `reorder()` membuang urutan bawaan relasi `materials()` (nama material
     * saja) lebih dulu — kalau tidak, nama material justru mengalahkan mesin
     * dan kelompoknya terpecah-pecah. Material tanpa mesin selalu paling bawah.
     */
    public function scopeOrderedByMachine(Builder $query): Builder
    {
        return $query
            ->reorder()
            ->select('print_materials.*')
            ->leftJoin('machine_costs', 'machine_costs.id', '=', 'print_materials.machine_cost_id')
            ->orderByRaw('CASE WHEN machine_costs.id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('machine_costs.mesin')
            ->orderBy('print_materials.material');
    }
}
