<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu penawaran kini dapat berisi banyak model.
     *
     * `quotation_requests` tetap menjadi tabel penawaran (satu nomor tracking,
     * satu pemohon, satu alur status), sedangkan setiap berkas model beserta
     * pengaturan printing, hasil analisis, dan estimasinya pindah ke
     * `quotation_items`.
     *
     * Kolom model pada `quotation_requests` sengaja tidak dihapus: kolom berkas
     * (`file_name`, `technology`, dan seterusnya) tetap diisi dari model pertama
     * sebagai ringkasan, dan kolom estimasi diisi penjumlahan seluruh model,
     * sehingga daftar admin, pencarian, halaman tracking, dan PDF yang sudah ada
     * tidak perlu diubah cara membacanya.
     */
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1)->comment('Urutan model di dalam satu penawaran');

            // Berkas model yang diunggah
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_format', 10);
            $table->unsignedBigInteger('file_size');

            // Ringkasan geometri hasil pembacaan model di browser
            $table->json('model_stats')->nullable()->comment('vertex, triangle, dimensi, bounding box, volume');

            // Hasil analisis kelayakan cetak, berdiri sendiri per model
            $table->string('analysis_status', 20)->default('warning')->comment('ready | warning | not_printable');
            $table->json('analysis')->nullable();

            // Pengaturan printing per model
            $table->string('technology', 10);
            $table->string('material', 60);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('resolution', 10)->nullable();
            $table->decimal('layer_height_mm', 5, 3)->nullable();
            $table->boolean('support_enabled')->default(false);
            $table->string('support_type', 20)->nullable();

            // Estimasi per model (dihitung ulang di server)
            $table->decimal('model_volume_cm3', 12, 3)->nullable();
            $table->decimal('material_volume_cm3', 12, 3)->nullable();
            $table->decimal('support_volume_cm3', 12, 3)->nullable();
            $table->decimal('estimated_weight_g', 12, 2)->nullable();
            $table->decimal('support_weight_g', 12, 2)->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->decimal('estimated_cost', 14, 2)->nullable();

            // Penyesuaian admin, dapat berbeda untuk tiap model
            $table->decimal('estimated_price', 14, 2)->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->index(['quotation_request_id', 'position']);
            $table->index('technology');
        });

        // Permintaan lama berisi tepat satu model — dipindahkan apa adanya
        // supaya seluruh riwayat penawaran ikut memakai struktur yang baru.
        DB::table('quotation_requests')->orderBy('id')->each(function ($row) {
            DB::table('quotation_items')->insert([
                'quotation_request_id' => $row->id,
                'position' => 1,

                'file_name' => $row->file_name,
                'file_path' => $row->file_path,
                'file_format' => $row->file_format,
                'file_size' => $row->file_size,

                'model_stats' => $row->model_stats,
                'analysis_status' => $row->analysis_status,
                'analysis' => $row->analysis,

                'technology' => $row->technology,
                'material' => $row->material,
                'quantity' => $row->quantity,
                'resolution' => $row->resolution,
                'layer_height_mm' => $row->layer_height_mm,
                'support_enabled' => $row->support_enabled,
                'support_type' => $row->support_type,

                'model_volume_cm3' => $row->model_volume_cm3,
                'material_volume_cm3' => $row->material_volume_cm3,
                'support_volume_cm3' => $row->support_volume_cm3,
                'estimated_weight_g' => $row->estimated_weight_g,
                'support_weight_g' => $row->support_weight_g,
                'estimated_minutes' => $row->estimated_minutes,
                'estimated_cost' => $row->estimated_cost,

                'estimated_price' => $row->estimated_price,
                'admin_note' => null,

                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
