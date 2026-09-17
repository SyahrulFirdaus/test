<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status material pada Price List (switch pada tabel Teknologi & Material).
 *
 * Aktif = dapat dipilih pada Edit Specification; nonaktif = tidak tampil dan
 * ditolak saat penawaran baru dikirim. Seluruh material yang sudah ada tetap
 * aktif, sehingga tidak ada pilihan yang tiba-tiba hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_materials', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('pricing_method');
        });
    }

    public function down(): void
    {
        Schema::table('print_materials', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
