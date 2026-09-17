<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status teknologi kini diatur Superadmin lewat switch pada menu Teknologi
 * (`is_active`). Teknologi yang sudah DIGABUNG ke teknologi lain — SLA lama,
 * yang materialnya pindah ke SLA (kode SLAI) — bukan sekadar nonaktif: ia
 * diarsipkan supaya tidak tampil sebagai baris ganda "SLA" yang dapat
 * dinyalakan kembali tanpa sengaja. Barisnya tetap ada untuk penawaran lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_technologies', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('is_active');
        });

        if (DB::table('print_technologies')->where('code', 'SLAI')->exists()) {
            DB::table('print_technologies')
                ->where('code', 'SLA')
                ->where('is_active', false)
                ->update(['archived_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('print_technologies', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
