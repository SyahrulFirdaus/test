<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom status aktif untuk akun pengelola.
 *
 * Superadmin dapat menonaktifkan akun Admin tanpa menghapusnya, sehingga
 * aksesnya hilang tetapi seluruh jejak aktivitasnya tetap utuh dan Activity
 * Log masih dapat dibaca. Seluruh akun yang sudah ada dianggap aktif.
 *
 * Akun Superadmin awalnya sendiri TIDAK dibuat di sini melainkan lewat
 * Database\Seeders\SuperAdminUserSeeder, mengikuti AdminUserSeeder. Menyisipkan
 * akun lewat migrasi akan membuatnya ikut muncul pada setiap basis data
 * pengujian dan menggeser perhitungan yang mengandalkan jumlah pengguna.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_active')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });

        DB::table('users')->update(['is_active' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'is_active')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};