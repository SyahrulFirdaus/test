<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('code')->comment('Singkatan teknologi, mis. FDM');
            $table->string('name')->comment('Nama panjang, mis. Fused Deposition Modeling');
            $table->string('tagline');
            $table->text('description');
            $table->string('image')->nullable()->comment('Gambar mesin / hasil cetak');
            $table->json('advantages')->comment('Daftar kelebihan');
            $table->json('applications')->comment('Contoh aplikasi');
            $table->json('materials')->nullable()->comment('Material yang didukung');
            $table->json('specs')->nullable()->comment('Spesifikasi ringkas: label => nilai');
            $table->string('accent_color', 7)->default('#95271D');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technologies');
    }
};
