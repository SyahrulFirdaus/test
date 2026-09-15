<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sederhanakan alur status penawaran.
 *
 * Dua tahap dihapus dari alur:
 *
 *   - "Menunggu Review" (`received`) — antrean sebelum ditinjau. Penawaran baru
 *     kini langsung berstatus "File Sedang Direview", jadi tahap ini tidak lagi
 *     punya isi.
 *   - "Menunggu Persetujuan Penawaran" (`awaiting_approval`) — digantikan
 *     "Penawaran Dikirim" (`quote_sent`) yang menempati posisi sama pada alur.
 *
 * Baris yang sudah terlanjur memakai kedua status itu DIPINDAHKAN, bukan
 * dihapus: penawaran yang sedang berjalan tidak boleh kehilangan posisinya di
 * timeline. Pemetaannya diberlakukan pada tiga tempat sekaligus — status
 * penawaran, status sebelum pembatalan, dan seluruh riwayat perpindahannya —
 * sehingga halaman tracking tidak menyisakan tahap yang sudah tidak dikenal.
 */
return new class extends Migration
{
    /** Status lama => penggantinya. */
    private const MAP = [
        'received' => 'reviewing',
        'awaiting_approval' => 'quote_sent',
    ];

    public function up(): void
    {
        $this->remap(self::MAP);
    }

    /**
     * Dikembalikan apa adanya: `reviewing` yang berasal dari `received` tidak
     * lagi dapat dibedakan dari yang memang sudah direview, jadi pembalikan
     * hanya mengembalikan penamaan `quote_sent`.
     */
    public function down(): void
    {
        $this->remap(['quote_sent' => 'awaiting_approval']);
    }

    /** @param  array<string, string>  $map */
    private function remap(array $map): void
    {
        foreach ($map as $from => $to) {
            DB::table('quotation_requests')->where('status', $from)->update(['status' => $to]);

            if (Schema::hasColumn('quotation_requests', 'status_before_cancellation')) {
                DB::table('quotation_requests')
                    ->where('status_before_cancellation', $from)
                    ->update(['status_before_cancellation' => $to]);
            }

            // Riwayat perpindahan status yang ditampilkan halaman tracking.
            if (Schema::hasTable('quotation_histories')) {
                DB::table('quotation_histories')->where('status', $from)->update(['status' => $to]);
            }
        }
    }
};
