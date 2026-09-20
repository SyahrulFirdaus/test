<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Siapa yang memutuskan pembayaran sekali bayar, bukan hanya kapan.
 *
 * Bukti tiap termin sudah mencatatnya sejak awal (`payment_proofs.verified_by`);
 * pembayaran sekali bayar belum. Sejak verifikasinya pindah ke Detail Penawaran
 * — tempat lebih banyak orang lewat — pertanyaan "siapa yang menerima ini?"
 * menjadi pertanyaan yang benar-benar perlu dapat dijawab.
 *
 * Sekalian membetulkan satu hal yang selama ini rancu: `payment_verified_at`
 * ikut terisi saat pembayaran DITOLAK, sehingga penawaran yang buktinya ditolak
 * terbaca "Paid" pada dashboard Business. Penolakan kini punya kolom waktunya
 * sendiri, dan baris lama yang berstatus ditolak dipindahkan ke sana.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            // Tanpa foreign key, mengikuti kolom penunjuk pengguna lain di
            // basis data ini: akun pengelola yang dihapus tidak boleh ikut
            // menghapus atau mengunci jejak keputusannya.
            $table->unsignedBigInteger('payment_verified_by')->nullable()->after('payment_verified_at');
            $table->timestamp('payment_rejected_at')->nullable()->after('payment_verified_by');
            $table->unsignedBigInteger('payment_rejected_by')->nullable()->after('payment_rejected_at');
        });

        /*
         * Penolakan yang sudah tercatat dipindahkan ke kolomnya sendiri.
         *
         * Hanya baris yang benar-benar berstatus ditolak: penawaran yang
         * buktinya pernah ditolak lalu diterima ulang sudah tidak berstatus itu
         * lagi, dan waktu verifikasinya memang waktu penerimaan.
         */
        DB::table('quotation_requests')
            ->where('status', 'payment_rejected')
            ->whereNotNull('payment_verified_at')
            ->update([
                'payment_rejected_at' => DB::raw('payment_verified_at'),
                'payment_verified_at' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('quotation_requests')
            ->whereNotNull('payment_rejected_at')
            ->whereNull('payment_verified_at')
            ->update(['payment_verified_at' => DB::raw('payment_rejected_at')]);

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn(['payment_verified_by', 'payment_rejected_at', 'payment_rejected_by']);
        });
    }
};
