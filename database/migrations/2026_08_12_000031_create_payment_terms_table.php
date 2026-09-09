<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema pembayaran bertahap milik satu penawaran Business.
 *
 * Barisnya dibuat saat pelanggan Business memilih jumlah terminnya dan menunggu
 * persetujuan admin; jadwal terminnya sendiri berada di `payment_installments`.
 * Pelanggan Personal tidak pernah punya baris di sini — alur pembayarannya
 * tetap yang lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();

            // Satu penawaran hanya punya satu skema pembayaran yang berlaku.
            $table->foreignId('quotation_request_id')->unique()->constrained()->cascadeOnDelete();

            // Total penawaran saat skema ini diajukan. Disalin ke sini supaya
            // jumlah seluruh termin tetap dapat dicocokkan meski harga
            // penawarannya kemudian disesuaikan admin.
            $table->decimal('total_amount', 15, 2);

            $table->unsignedTinyInteger('installment_count');

            // Lebar 40 karakter menyamai kolom status penawaran: status
            // pembayaran masih mungkin bertambah, jadi bukan ENUM.
            $table->string('status', 40);

            $table->text('rejection_reason')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Daftar "Payment Terms" di dashboard admin menyaring per status.
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_terms');
    }
};
