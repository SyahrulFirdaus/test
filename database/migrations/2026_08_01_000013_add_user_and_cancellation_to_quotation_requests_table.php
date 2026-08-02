<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kepemilikan penawaran dan jejak pembatalan.
 *
 * Penawaran kini selalu dibuat dari akun yang login, sehingga tiap baris
 * menyimpan pemiliknya. Penawaran lama (dibuat saat halaman Cek Barang masih
 * terbuka untuk tamu) dicocokkan berdasarkan email; yang tidak menemukan
 * pasangan dibiarkan tanpa pemilik dan tetap dapat dilacak lewat nomor
 * tracking seperti sebelumnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            // Jejak pembatalan. `status_before_cancellation` dipakai untuk
            // mengembalikan penawaran ke tahapnya semula bila admin menolak
            // permintaan pembatalan.
            $table->text('cancellation_reason')->nullable()->after('admin_note');
            $table->string('status_before_cancellation', 40)->nullable()->after('cancellation_reason');
            $table->timestamp('cancellation_requested_at')->nullable()->after('status_before_cancellation');
            $table->timestamp('cancellation_resolved_at')->nullable()->after('cancellation_requested_at');
            $table->text('cancellation_admin_note')->nullable()->after('cancellation_resolved_at');
        });

        // Penawaran lama dihubungkan ke akun dengan email yang sama.
        DB::table('quotation_requests')
            ->whereNull('user_id')
            ->orderBy('id')
            ->chunkById(100, function ($quotations) {
                foreach ($quotations as $quotation) {
                    $userId = DB::table('users')
                        ->where('email', $quotation->email)
                        ->where('role', 'user')
                        ->value('id');

                    if ($userId) {
                        DB::table('quotation_requests')
                            ->where('id', $quotation->id)
                            ->update(['user_id' => $userId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'cancellation_reason',
                'status_before_cancellation',
                'cancellation_requested_at',
                'cancellation_resolved_at',
                'cancellation_admin_note',
            ]);
        });
    }
};
