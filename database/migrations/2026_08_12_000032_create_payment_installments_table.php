<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal termin pembayaran.
 *
 * Setiap baris adalah satu tagihan: nomor termin, persentase, nominal, batas
 * waktu, milestone pekerjaan, dan keadaannya. Nominalnya disimpan apa adanya —
 * bukan dihitung ulang dari persentase saat dibaca — supaya penjumlahan seluruh
 * termin selalu persis sama dengan total penawaran tanpa selisih pembulatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_installments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_term_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('installment_number');

            // Persentase disimpan dengan dua angka desimal agar pembagian rata
            // seperti 33,33% tetap terbaca sesuai yang ditetapkan admin.
            $table->decimal('percentage', 6, 2);
            $table->decimal('amount', 15, 2);

            $table->string('milestone', 190)->nullable();
            $table->date('due_date')->nullable();

            $table->string('status', 40);

            // Termin hanya dapat dibayar setelah diaktifkan — baik oleh sistem
            // saat termin sebelumnya lunas, maupun manual oleh admin.
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('admin_note')->nullable();

            // Pengingat terakhir yang sudah dikirim, dalam hitungan hari sebelum
            // jatuh tempo. Menahan pengingat yang sama terkirim berulang kali
            // setiap kali perintah terjadwal berjalan.
            $table->unsignedTinyInteger('last_reminder_days')->nullable();

            $table->timestamps();

            // Satu nomor termin hanya sekali dalam satu skema pembayaran.
            $table->unique(['payment_term_id', 'installment_number']);

            // Penyapu jatuh tempo dan pengingat menelusuri lewat dua kolom ini.
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_installments');
    }
};
