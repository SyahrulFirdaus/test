<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pertanyaan yang diajukan saat pendaftaran.
 *
 * Daftarnya disimpan di basis data, bukan ditulis di dalam halaman registrasi,
 * supaya pertanyaan dapat ditambah, diubah urutannya, atau dinonaktifkan tanpa
 * menyentuh kode formulir. Halaman registrasi menyusun langkah-langkahnya
 * sendiri dari isi tabel ini.
 *
 * Beberapa kolom di luar daftar dasar:
 *
 *   key            rujukan tetap untuk pertanyaan yang perlu diperlakukan
 *                  khusus di kode — mis. kebutuhan dokumen resmi yang
 *                  memunculkan pertanyaan lanjutan. Nama pertanyaannya boleh
 *                  berubah tanpa memutus rujukan itu.
 *   step           pengelompokan langkah pada wizard pelanggan perusahaan.
 *                  Pelanggan perorangan seluruhnya berada di satu langkah.
 *   step_label     judul langkah, mis. "Informasi Perusahaan".
 *   options_source pertanyaan yang pilihannya diambil dari data sistem, bukan
 *                  ditulis tetap di kolom `options` — mis. daftar teknologi
 *                  dan material yang benar-benar tersedia.
 *   depends_on_*   pertanyaan lanjutan yang hanya muncul bila pertanyaan
 *                  induknya dijawab dengan nilai tertentu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_questions', function (Blueprint $table) {
            $table->id();
            $table->string('customer_type', 20)->comment('personal | business');
            $table->string('key')->unique()->comment('Rujukan tetap di kode, mis. business_official_documents');

            $table->text('question');
            $table->string('help')->nullable()->comment('Keterangan singkat di bawah pertanyaan');
            $table->string('type', 20)->comment('radio | checkbox | select | text | email | url');
            $table->json('options')->nullable()->comment('Pilihan tetap; kosong bila memakai options_source');
            $table->string('options_source')->nullable()->comment('technologies | materials');
            $table->string('placeholder')->nullable();

            $table->unsignedSmallInteger('step')->default(1)->comment('Langkah wizard; perorangan selalu 1');
            $table->string('step_label')->nullable();

            $table->string('depends_on_key')->nullable()->comment('Key pertanyaan induk');
            $table->string('depends_on_value')->nullable()->comment('Nilai induk yang memunculkan pertanyaan ini');

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['customer_type', 'is_active', 'step', 'sort_order'], 'reg_questions_flow_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_questions');
    }
};
