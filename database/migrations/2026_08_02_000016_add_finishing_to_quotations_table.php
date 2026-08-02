<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan finishing per model.
 *
 * Finishing sudah lama menjadi salah satu komponen biaya, tetapi jenisnya belum
 * dapat dipilih pelanggan. Kolom ini menyimpan pilihannya (`none`, `sanding`,
 * `primer`, `painting`, `polishing`) sesuai `finishing.options` di
 * config/printing.php.
 *
 * Penawaran lama diisi `none` — nilai yang pengalinya 1,0 — sehingga estimasi
 * yang sudah tercatat tidak berubah sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('finishing', 30)->default('none')->after('material_color');
        });

        // Kolom ringkasan pada penawaran mengikuti model pertama, sama seperti
        // kolom pilihan produksi lainnya.
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->string('finishing', 30)->default('none')->after('material');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('finishing');
        });

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn('finishing');
        });
    }
};
