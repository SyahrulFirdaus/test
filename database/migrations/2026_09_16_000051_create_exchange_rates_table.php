<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kurs terakhir yang berhasil diambil dari penyedia.
 *
 * Bukan simpanan sementara — itu sudah ditangani cache. Tabel ini adalah
 * KURS TERAKHIR YANG DIKETAHUI, dan gunanya satu: ketika penyedia sedang tidak
 * dapat dihubungi, sistem masih punya angka yang masuk akal untuk dipakai
 * sementara alih-alih menghitung Final Price dengan kurs nol.
 *
 * Karena itu ia harus bertahan lebih lama daripada cache: `php artisan
 * cache:clear`, mulai ulang server, atau ganti driver cache tidak boleh
 * membuat sistem kehilangan kurs terakhirnya.
 *
 * Satu baris per pasangan mata uang; untuk sekarang hanya USD/IDR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();

            // mis. "USD/IDR" — satu baris saja per pasangan.
            $table->string('pair', 16)->unique();

            // Kurs butuh desimal yang lapang: satu dollar bernilai puluhan ribu
            // rupiah, dan penyedia mengirimkan pecahan sampai delapan angka.
            $table->decimal('rate', 20, 8);

            // Kunci penyedia pada config/printing.php beserta namanya saat
            // diambil, supaya baris lama tetap terbaca meski konfigurasinya
            // kemudian diganti.
            $table->string('provider', 60);
            $table->string('source_label');

            // Kapan KURSNYA terbit menurut penyedia — berbeda dari kapan sistem
            // menariknya. Inilah yang berarti bagi pengguna, dan pada sumber
            // harian keduanya bisa terpaut belasan jam.
            $table->timestamp('published_at')->nullable();
            // `useCurrent()` bukan sekadar kenyamanan: MySQL dalam mode ketat
            // menolak kolom TIMESTAMP NOT NULL tanpa nilai bawaan.
            $table->timestamp('fetched_at')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
