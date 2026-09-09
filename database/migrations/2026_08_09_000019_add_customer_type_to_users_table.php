<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tipe pelanggan: perorangan atau perusahaan.
 *
 * Ditanyakan sejak halaman pendaftaran karena kebutuhan keduanya berbeda —
 * pelanggan perorangan menjawab lima pertanyaan singkat, pelanggan perusahaan
 * melewati lima langkah berisi lima belas pertanyaan.
 *
 * Akun yang sudah ada dibuat sebelum pilihan ini tersedia, jadi seluruhnya
 * diisi `personal`: nilai paling aman yang tidak mengubah apa pun pada alur
 * penawaran mereka. Tidak ada data lama yang dihapus atau ditulis ulang selain
 * kolom baru ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('customer_type', 20)
                ->default('personal')
                ->after('role')
                ->comment('personal | business');

            $table->index('customer_type');
        });

        DB::table('users')->whereNull('customer_type')->update(['customer_type' => 'personal']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['customer_type']);
            $table->dropColumn('customer_type');
        });
    }
};
