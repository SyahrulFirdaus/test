<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            // Kunci pilihan disimpan agar label dan pengalinya dapat dibaca dari
            // config, sementara tebal lapisannya ikut dicatat sebagai angka supaya
            // data lama tetap terbaca meski daftar pilihan di config berubah.
            $table->string('resolution', 10)->nullable()->after('material');
            $table->decimal('layer_height_mm', 5, 3)->nullable()->after('resolution');
        });

        $default = (string) config('printing.resolutions.default', '0.25');
        $layerHeight = (float) config("printing.resolutions.options.{$default}.layer_height", 0.25);

        DB::table('quotation_requests')->whereNull('resolution')->update([
            'resolution' => $default,
            'layer_height_mm' => $layerHeight,
        ]);
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn(['resolution', 'layer_height_mm']);
        });
    }
};
