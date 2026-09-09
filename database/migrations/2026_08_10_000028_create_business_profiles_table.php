<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil perusahaan milik pelanggan Business.
 *
 * Data ini dikumpulkan sekali saat pendaftaran lalu dipakai berulang: admin
 * membacanya sebelum memproses penawaran, sehingga pelanggan tidak perlu
 * mengetikkan data perusahaannya lagi setiap kali meminta penawaran.
 *
 * Disimpan sebagai kolom tersendiri — bukan sebagai jawaban pertanyaan —
 * karena bentuknya memang tetap dan sering dibaca satu per satu oleh halaman
 * admin: nama perusahaan, industri, PIC, dan jabatan masing-masing tampil di
 * tempatnya sendiri, bukan sebagai daftar tanya-jawab.
 *
 * `segment` diisi sistem dari jawaban kebutuhan bisnis (lihat
 * App\Services\CustomerSegmenter) supaya admin dapat langsung mengenali jenis
 * pelanggannya tanpa membaca lima belas jawaban satu per satu.
 *
 * Alamat perusahaan memakai wilayah berjenjang yang sama dengan buku alamat,
 * dan alamat itu pula yang menjadi alamat pengiriman pertama akun tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('company_name', 160);
            $table->string('pic_name', 120);
            $table->string('position', 120);
            $table->string('phone', 32);
            $table->string('email', 160);
            $table->string('website', 255)->nullable();
            $table->string('industry', 120);

            $table->text('address');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('regency_id')->nullable()->constrained('regencies')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('postal_code', 12);

            $table->string('segment', 60)->nullable()->comment('Hasil pengelompokan otomatis, mis. Industrial Customer');

            $table->timestamps();

            $table->index('industry');
            $table->index('segment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
