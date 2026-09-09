<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak audit seluruh aktivitas penting.
 *
 * Satu baris menjawab lima pertanyaan sekaligus: siapa pelakunya, apa yang
 * dilakukan, kapan, terhadap data apa, dan apa yang berubah. Dua kolom
 * terakhir — `old_values` dan `new_values` — disimpan sebagai JSON sehingga
 * bentuk datanya bebas mengikuti modul yang mencatatnya.
 *
 * Pelakunya sengaja tidak hanya disimpan sebagai relasi: `user_name` dan
 * `user_type` ikut dibekukan pada baris log agar riwayat tetap terbaca apa
 * adanya meski akunnya kemudian dihapus atau berganti tipe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Akun yang dihapus menyisakan lognya dengan user_id kosong;
            // nama dan tipe yang dibekukan di bawah yang menjaga log tetap
            // bermakna.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 190)->nullable();
            $table->string('user_type', 20);

            $table->string('action', 60);
            $table->string('module', 40);
            $table->string('description', 500)->nullable();

            // Data yang disentuh aktivitas ini, disimpan sebagai pasangan
            // kelas Eloquent dan id-nya supaya modul mana pun dapat memakainya.
            $table->string('subject_type', 190)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            // Penanda siap tampil, mis. nomor penawaran atau nama berkas —
            // dibekukan agar daftar log tidak perlu memuat relasinya.
            $table->string('subject_label', 190)->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->string('status', 20);

            $table->timestamps();

            // Daftar log selalu terbaru di atas, dan tiap penyaring pada
            // halaman Activity Logs memakai salah satu indeks berikut.
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'created_at']);
            $table->index(['user_type', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
