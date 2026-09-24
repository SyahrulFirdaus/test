<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kecepatan pengerjaan yang dipilih pelanggan: Standard atau Express.
 *
 * Sebelumnya lead time disimpulkan sendiri dari total jam mesin — di bawah 20
 * jam otomatis disebut Express. Kini pelanggan MEMILIH, dan Express hanya
 * tersedia bila pesanannya berisi satu part dan waktu mesinnya di bawah 18 jam
 * (lihat App\Support\LeadTime). Pilihannya menaikkan harga printing 25%, jadi
 * harus tersimpan bersama penawarannya, bukan dihitung ulang kemudian.
 *
 * Seluruh penawaran yang sudah ada diberi `standard`: harga yang sudah
 * ditawarkan kepada mereka tidak memuat tambahan Express, jadi menandainya
 * Express justru akan salah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->string('production_speed', 20)->default('standard')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn('production_speed');
        });
    }
};
