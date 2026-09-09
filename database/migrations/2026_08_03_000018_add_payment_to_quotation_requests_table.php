<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom pembayaran penawaran.
 *
 * Menyimpan batas waktu 24 jam sejak status berpindah ke "Menunggu
 * Pembayaran", berkas bukti transfer yang diunggah pemiliknya, serta jejak
 * keputusan verifikasi admin beserta alasan penolakannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->timestamp('payment_due_at')->nullable()->after('estimated_finish');
            $table->string('payment_proof_path')->nullable()->after('payment_due_at');
            $table->string('payment_proof_name', 190)->nullable()->after('payment_proof_path');
            $table->timestamp('payment_proof_uploaded_at')->nullable()->after('payment_proof_name');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_proof_uploaded_at');
            $table->text('payment_rejection_reason')->nullable()->after('payment_verified_at');

            // Daftar "Verifikasi Pembayaran" mengurut bukti yang paling lama
            // menunggu, jadi kolomnya diindeks.
            $table->index('payment_proof_uploaded_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropIndex(['payment_proof_uploaded_at']);

            $table->dropColumn([
                'payment_due_at',
                'payment_proof_path',
                'payment_proof_name',
                'payment_proof_uploaded_at',
                'payment_verified_at',
                'payment_rejection_reason',
            ]);
        });
    }
};
