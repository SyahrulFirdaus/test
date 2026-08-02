<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu model dicetak pada satu mesin: 1 printer = 1 build plate = 1 objek.
     *
     * Sebelumnya seluruh model dalam satu penawaran berbagi satu mesin, jadi
     * pilihan printer cukup disimpan di `quotation_requests`. Sekarang setiap
     * model punya mesin dan area cetaknya sendiri sehingga kolomnya pindah ke
     * `quotation_items`.
     *
     * Kolom printer pada `quotation_requests` sengaja dipertahankan sebagai
     * ringkasan — diisi mesin model pertama — supaya daftar admin, halaman
     * tracking, dan PDF lama tetap punya satu nilai yang mewakili tanpa perlu
     * memuat seluruh itemnya.
     */
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('printer', 40)->nullable()->after('material');
            $table->string('printer_name')->nullable()->after('printer');
            $table->json('build_volume')->nullable()->after('printer_name')
                ->comment('Area cetak mesin model ini: x lebar, y kedalaman, z tinggi');
        });

        // Model lama mewarisi mesin yang tercatat pada penawarannya.
        DB::table('quotation_items')->orderBy('id')->each(function ($item) {
            $quotation = DB::table('quotation_requests')
                ->where('id', $item->quotation_request_id)
                ->first(['printer', 'printer_name', 'build_volume']);

            if ($quotation === null) {
                return;
            }

            DB::table('quotation_items')->where('id', $item->id)->update([
                'printer' => $quotation->printer,
                'printer_name' => $quotation->printer_name,
                'build_volume' => $quotation->build_volume,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['printer', 'printer_name', 'build_volume']);
        });
    }
};
