<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rumus & parameter simulasi Harga Jual, satu baris per teknologi cetak
 * (FDM/SLA/MJF/SLM), dikelola admin lewat tab "Harga" pada Price List.
 *
 * Ini murni referensi/simulasi milik admin — TIDAK dibaca oleh
 * PrintEstimator/Calculator maupun alur Quotation manapun. Keempat baris
 * memakai satu pola perhitungan yang sama (lihat accessor di model
 * `PricingFormula`); yang berbeda per teknologi hanya nilai parameternya.
 *
 * Baris awalnya disisipkan langsung di sini (bukan lewat Seeder terpisah)
 * mengikuti konvensi tabel referensi tetap lain di aplikasi ini (lih.
 * `payment_term_settings`, `fdm_materials`) — supaya `RefreshDatabase` pada
 * test selalu punya keempat baris tanpa perlu seeding tambahan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('technology')->unique();
            $table->decimal('machine_time_hours', 8, 2)->default(0);
            $table->decimal('machine_cost', 12, 2)->default(0);
            $table->decimal('material_qty_g', 10, 2)->default(0);
            $table->decimal('material_price_per_g', 12, 2)->default(0);
            $table->decimal('risk_percent', 5, 2)->default(0);
            $table->decimal('packaging_cost', 12, 2)->default(0);
            $table->decimal('overtime_cost', 12, 2)->default(0);
            $table->decimal('profit_percent', 5, 2)->default(0);
            $table->timestamps();
        });

        // Nilai awal sekadar contoh yang masuk akal per teknologi — admin
        // bebas menggantinya kapan saja lewat tab Harga.
        $now = now();

        DB::table('pricing_formulas')->insert([
            [
                'technology' => 'FDM',
                'machine_time_hours' => 2.00,
                'machine_cost' => 61000,
                'material_qty_g' => 800,
                'material_price_per_g' => 280,
                'risk_percent' => 30,
                'packaging_cost' => 5000,
                'overtime_cost' => 0,
                'profit_percent' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'technology' => 'SLA',
                'machine_time_hours' => 4.00,
                'machine_cost' => 30000,
                'material_qty_g' => 150,
                'material_price_per_g' => 700,
                'risk_percent' => 25,
                'packaging_cost' => 5000,
                'overtime_cost' => 0,
                'profit_percent' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'technology' => 'MJF',
                'machine_time_hours' => 6.00,
                'machine_cost' => 45000,
                'material_qty_g' => 300,
                'material_price_per_g' => 3500,
                'risk_percent' => 15,
                'packaging_cost' => 8000,
                'overtime_cost' => 0,
                'profit_percent' => 45,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'technology' => 'SLM',
                'machine_time_hours' => 10.00,
                'machine_cost' => 250000,
                'material_qty_g' => 100,
                'material_price_per_g' => 9000,
                'risk_percent' => 20,
                'packaging_cost' => 10000,
                'overtime_cost' => 0,
                'profit_percent' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_formulas');
    }
};
