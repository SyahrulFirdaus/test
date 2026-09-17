<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * MJF dan SLM kini ikut menentukan metode harga per material, sama seperti SLA
 * (lihat App\Support\PricingMethod).
 *
 * Material yang sudah ada selama ini selalu dihitung otomatis, jadi diberi
 * `automatic` — harga yang dilihat pelanggan tidak berubah sedikit pun. Tanpa
 * ini, nilai bawaan kolomnya (`manual`) akan menahan seluruh harganya.
 */
return new class extends Migration
{
    private const CODES = ['MJF', 'SLM'];

    public function up(): void
    {
        $ids = DB::table('print_technologies')->whereIn('code', self::CODES)->pluck('id');

        DB::table('print_materials')
            ->whereIn('print_technology_id', $ids)
            ->update(['pricing_method' => 'automatic']);
    }

    public function down(): void
    {
        $ids = DB::table('print_technologies')->whereIn('code', self::CODES)->pluck('id');

        DB::table('print_materials')
            ->whereIn('print_technology_id', $ids)
            ->update(['pricing_method' => 'manual']);
    }
};
