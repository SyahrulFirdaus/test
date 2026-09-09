<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jawaban pendaftaran milik tiap pelanggan.
 *
 * Satu baris menampung satu nilai jawaban, bukan satu kolom JSON berisi semua
 * jawaban sekaligus. Bentuk ini yang membuat admin dapat menyaring pelanggan
 * lewat kueri biasa — mis. seluruh perusahaan yang menjawab "PA12-CF" pada
 * pertanyaan material.
 *
 * Karena itu pertanyaan berjawaban ganda (mis. material yang biasa dipakai)
 * menghasilkan beberapa baris untuk satu pertanyaan: satu baris per pilihan
 * yang dicentang. Tidak ada batasan unik pada pasangan user + pertanyaan.
 *
 * Pertanyaan yang kemudian dihapus ikut membawa jawabannya; akun yang dihapus
 * juga menghapus seluruh jawabannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('registration_questions')->cascadeOnDelete();
            // Sengaja varchar, bukan text: kolom ini ikut diindeks agar dapat
            // disaring, dan MySQL tidak menerima indeks pada kolom TEXT tanpa
            // panjang prefix. Jawaban terpanjang hanyalah alamat website.
            $table->string('answer', 500);
            $table->timestamps();

            $table->index(['user_id', 'question_id']);
            // Menyaring pelanggan berdasarkan jawaban tertentu, mis. seluruh
            // perusahaan yang memilih teknologi SLM.
            $table->index(['question_id', 'answer'], 'customer_answers_question_answer_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_answers');
    }
};
