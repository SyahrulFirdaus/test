<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti pembayaran tiap termin.
 *
 * Berbeda dari bukti pembayaran sekali bayar yang menempel sebagai kolom pada
 * `quotation_requests`, bukti termin disimpan sebagai baris tersendiri sehingga
 * unggahan ulang setelah penolakan tidak menimpa jejak sebelumnya — seluruh
 * percobaan pembayaran beserta keputusan adminnya tetap terbaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_installment_id')->constrained()->cascadeOnDelete();

            $table->string('file_path');
            $table->string('file_name', 190);

            $table->string('status', 40);

            $table->timestamp('uploaded_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // Antrean "Verifikasi Pembayaran" mengurut bukti yang paling lama
            // menunggu keputusan admin.
            $table->index(['status', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
