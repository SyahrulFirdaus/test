<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pemetaan status lama ke alur status yang baru.
 *
 * Alur sebelumnya memisahkan "Analisis Model Selesai" dan "Penawaran Dibuat",
 * serta menyatukan pembayaran dalam satu tahap. Alur baru memecah pembayaran
 * menjadi menunggu dan diterima, lalu mengganti tahap "Dikirim" menjadi
 * "Siap Dikirim" sebelum "Selesai".
 *
 * Baris penawaran maupun riwayatnya ikut dipetakan supaya timeline penawaran
 * lama tetap terbaca pada halaman tracking.
 */
return new class extends Migration
{
    /** @var array<string, string> */
    private array $map = [
        'analyzed' => 'reviewing',
        'quoted' => 'awaiting_approval',
        'payment' => 'awaiting_payment',
        'shipped' => 'ready_to_ship',
    ];

    public function up(): void
    {
        $this->apply($this->map);
    }

    public function down(): void
    {
        // Pemetaan tidak sepenuhnya berbalik satu-satu (dua status lama dapat
        // bermuara ke satu status baru), jadi yang dikembalikan hanya kunci
        // yang tidak ambigu.
        $this->apply([
            'awaiting_payment' => 'payment',
            'ready_to_ship' => 'shipped',
        ]);
    }

    /** @param  array<string, string>  $map */
    private function apply(array $map): void
    {
        foreach ($map as $from => $to) {
            DB::table('quotation_requests')->where('status', $from)->update(['status' => $to]);
            DB::table('quotation_histories')->where('status', $from)->update(['status' => $to]);
        }
    }
};
