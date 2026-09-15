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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Trait menetapkan kolom komersialnya; kepemilikan teknologi dan mesin
        // ditambahkan di sini agar trait itu tetap dipakai bersama apa adanya.
        $this->mergeFillable(['print_technology_id', 'machine_cost_id']);
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
