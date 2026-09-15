<?php

namespace App\Models;

use App\Models\Concerns\HasMaterialPricing;
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

        // Trait menetapkan kolom komersialnya; kepemilikan teknologi
        // ditambahkan di sini agar trait itu tetap dipakai bersama apa adanya.
        $this->mergeFillable(['print_technology_id']);
    }

    public function technology(): BelongsTo
    {
        return $this->belongsTo(PrintTechnology::class, 'print_technology_id');
    }
}
