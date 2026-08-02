<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique()->comment('Nomor referensi yang ditampilkan ke pelanggan');

            // Data pemohon
            $table->string('name');
            $table->string('email');
            $table->string('whatsapp');
            $table->string('company')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();

            // Berkas model yang diunggah
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_format', 10);
            $table->unsignedBigInteger('file_size');

            // Ringkasan geometri hasil pembacaan model di browser
            $table->json('model_stats')->nullable()->comment('vertex, triangle, dimensi, bounding box, volume');

            // Hasil analisis kelayakan cetak
            $table->string('analysis_status', 20)->default('warning')->comment('ready | warning | not_printable');
            $table->json('analysis')->nullable()->comment('Daftar pemeriksaan beserta status dan penjelasannya');

            // Pilihan produksi & estimasi (dihitung ulang di server)
            $table->string('technology', 10);
            $table->string('material', 60);
            $table->decimal('model_volume_cm3', 12, 3)->nullable();
            $table->decimal('material_volume_cm3', 12, 3)->nullable();
            $table->decimal('estimated_weight_g', 12, 2)->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->decimal('estimated_cost', 14, 2)->nullable()->comment('Total untuk seluruh jumlah cetak');

            // Penanganan oleh admin
            $table->string('status', 20)->default('new');
            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('technology');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_requests');
    }
};
