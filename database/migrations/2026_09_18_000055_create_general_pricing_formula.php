<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Satu Rumus Harga Otomatis yang berlaku umum untuk seluruh teknologi.
 *
 * Sebelumnya `pricing_formulas` punya satu baris per teknologi. Kini
 * Kalkulator Otomatis seluruh teknologi membaca satu baris saja, berkode
 * `UMUM` (lihat App\Models\PricingFormula::GENERAL).
 *
 * Nilai awalnya disalin dari baris FDM — rumus yang selama ini menjadi acuan
 * Kalkulator Otomatis dan sudah dipakai SLA — sehingga harga FDM dan SLA tidak
 * berubah. Baris per teknologi yang lama TIDAK dihapus; hanya tidak dibaca lagi.
 */
return new class extends Migration
{
    private const GENERAL = 'UMUM';

    public function up(): void
    {
        if (DB::table('pricing_formulas')->where('technology', self::GENERAL)->exists()) {
            return;
        }

        $source = DB::table('pricing_formulas')->where('technology', 'FDM')->first();

        $columns = [
            'machine_time_hours', 'machine_cost', 'material_qty_g', 'material_price_per_g',
            'risk_percent', 'packaging_cost', 'overtime_cost', 'profit_percent', 'object_size_mm',
        ];

        $defaults = [
            'machine_time_hours' => 2, 'machine_cost' => 0, 'material_qty_g' => 300, 'material_price_per_g' => 500,
            'risk_percent' => 25, 'packaging_cost' => 5000, 'overtime_cost' => 0, 'profit_percent' => 50, 'object_size_mm' => 100,
        ];

        $values = [];

        foreach ($columns as $column) {
            $values[$column] = $source->{$column} ?? $defaults[$column];
        }

        DB::table('pricing_formulas')->insert([
            'technology' => self::GENERAL,
            ...$values,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('pricing_formulas')->where('technology', self::GENERAL)->delete();
    }
};
