<?php

use App\Support\SlaIndustries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Parameter bawaan Rumus Harga SLA Industries.
 *
 * Satu baris saja — inilah yang disunting Superadmin pada tab SLA Industries di
 * Price List, dan yang mengisi form perhitungan tiap model saat Admin membukanya
 * pertama kali. Kuotasi sungguhan per model tinggal di `sla_industries_quotes`,
 * jadi mengubah nilai bawaan di sini tidak menggeser penawaran yang harganya
 * sudah ditetapkan.
 *
 * Nilai awalnya sengaja nol, bukan angka contoh dari spesifikasi: harga JLC,
 * ongkir, dan kurs berubah tiap hari, dan angka contoh yang tertinggal di basis
 * data akan terbaca seolah-olah harga yang berlaku.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_industries_formulas', function (Blueprint $table) {
            $table->id();

            // Kurs yang dipakai mengubah kuotasi JLC ke rupiah.
            $table->decimal('usd_rate', 14, 2)->default(0);

            $table->decimal('jlc_price_usd', 14, 2)->default(0);
            $table->decimal('jlc_shipping_usd', 14, 2)->default(0);

            // Bea masuk dihitung Admin lewat kalkulator pabean Bea Cukai lalu
            // diketik di sini; sistem tidak mengambilnya otomatis.
            $table->decimal('customs_idr', 14, 2)->default(0);

            $table->decimal('margin_percent', 5, 2)->default(SlaIndustries::MAX_MARGIN);

            $table->timestamps();
        });

        DB::table('sla_industries_formulas')->insert([
            'usd_rate' => 0,
            'jlc_price_usd' => 0,
            'jlc_shipping_usd' => 0,
            'customs_idr' => 0,
            'margin_percent' => SlaIndustries::MAX_MARGIN,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_industries_formulas');
    }
};
