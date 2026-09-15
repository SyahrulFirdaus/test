<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus tahap "Penawaran Dikirim" (`quote_sent`) dari alur.
 *
 * Tahap ini baru saja menggantikan "Menunggu Persetujuan Penawaran", tetapi
 * ternyata tidak dibutuhkan: begitu penawaran selesai direview, pelanggan
 * langsung masuk ke "Menunggu Pembayaran". Alur tersisa delapan tahap.
 *
 * Baris yang terlanjur memakainya dipindahkan MAJU ke "Menunggu Pembayaran",
 * bukan mundur ke review: penawarannya memang sudah dikirimkan, jadi yang
 * ditunggu berikutnya adalah pembayarannya. Sama seperti migrasi sebelumnya,
 * pemetaannya diberlakukan pada status penawaran, status sebelum pembatalan,
 * dan seluruh riwayat perpindahannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->remap('quote_sent', 'awaiting_payment');
    }

    /**
     * Tidak dapat dibalik dengan setia: `awaiting_payment` yang berasal dari
     * `quote_sent` tidak lagi dapat dibedakan dari yang memang sudah menunggu
     * pembayaran sejak semula, jadi membalikkannya justru akan memundurkan
     * penawaran yang sah. Dibiarkan kosong dengan sengaja.
     */
    public function down(): void
    {
        //
    }

    private function remap(string $from, string $to): void
    {
        DB::table('quotation_requests')->where('status', $from)->update(['status' => $to]);

        if (Schema::hasColumn('quotation_requests', 'status_before_cancellation')) {
            DB::table('quotation_requests')
                ->where('status_before_cancellation', $from)
                ->update(['status_before_cancellation' => $to]);
        }

        if (Schema::hasTable('quotation_histories')) {
            DB::table('quotation_histories')->where('status', $from)->update(['status' => $to]);
        }
    }
};
