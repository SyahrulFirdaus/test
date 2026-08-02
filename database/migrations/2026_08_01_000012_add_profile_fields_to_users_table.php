<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data pelanggan pada akun pengguna.
 *
 * Sebelum ini tabel `users` hanya menampung akun admin, sehingga kolomnya
 * mengikuti bawaan Laravel. Sejak penawaran wajib dibuat lewat akun, formulir
 * pendaftaran ikut meminta data pengiriman — nomor telepon, kota, kode pos,
 * dan alamat lengkap — supaya penawaran tidak perlu menanyakannya berulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('email')->comment('admin | user');
            $table->string('phone', 32)->nullable()->after('role');
            $table->string('city', 120)->nullable()->after('phone');
            $table->string('postal_code', 12)->nullable()->after('city');
            $table->text('address')->nullable()->after('postal_code');

            $table->index('role');
        });

        // Seluruh akun yang sudah ada dibuat sebelum pendaftaran publik dibuka,
        // jadi semuanya adalah akun admin.
        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone', 'city', 'postal_code', 'address']);
        });
    }
};
