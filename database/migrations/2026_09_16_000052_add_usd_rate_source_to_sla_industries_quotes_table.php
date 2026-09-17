<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak asal kurs pada kuotasi yang sudah dihitung.
 *
 * `usd_rate` sudah tersimpan sejak awal, tetapi angka saja tidak cukup untuk
 * menjelaskan sebuah harga di kemudian hari: perlu diketahui kurs itu datang
 * dari mana dan terbit kapan.
 *
 * Inilah yang membuat penawaran lama tetap dapat dipertanggungjawabkan.
 * Membuka kembali penawaran yang harganya sudah ditetapkan TIDAK BOLEH menarik
 * kurs terbaru — yang berlaku adalah kurs yang benar-benar dipakai saat
 * perhitungannya disimpan, beserta keterangan sumbernya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sla_industries_quotes', function (Blueprint $table) {
            $table->string('usd_rate_source')->nullable()->after('usd_rate');
            $table->timestamp('usd_rate_published_at')->nullable()->after('usd_rate_source');
        });
    }

    public function down(): void
    {
        Schema::table('sla_industries_quotes', function (Blueprint $table) {
            $table->dropColumn(['usd_rate_source', 'usd_rate_published_at']);
        });
    }
};
