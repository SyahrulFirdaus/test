<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Metode harga (Kalkulator Otomatis / Manual) kini dipilih per material untuk
 * SELURUH teknologi, bukan hanya SLA, MJF, dan SLM.
 *
 * Material teknologi lain — FDM dan teknologi yang ditambahkan Superadmin —
 * selama ini selalu dihitung otomatis karena kolom `pricing_method`-nya tidak
 * dibaca, padahal isinya kerap `manual` (bawaan kolomnya). Tanpa migrasi ini
 * seluruh material FDM akan tiba-tiba menunggu Kalkulator Manual. Karena itu
 * nilainya disamakan dengan yang memang berlaku: `automatic`. Harga yang
 * dihitung tidak berubah sedikit pun.
 *
 * Material SLA (SLAI), MJF, dan SLM tidak disentuh: metodenya sudah dipilih.
 */
return new class extends Migration
{
    /** Teknologi yang sejak awal memilih metode per material. */
    private const ALREADY_CHOOSING = ['SLAI', 'MJF', 'SLM'];

    public function up(): void
    {
        $technologyIds = DB::table('print_technologies')
            ->whereNotIn(DB::raw('UPPER(code)'), self::ALREADY_CHOOSING)
            ->pluck('id');

        if ($technologyIds->isEmpty()) {
            return;
        }

        DB::table('print_materials')
            ->whereIn('print_technology_id', $technologyIds)
            ->where('pricing_method', '!=', 'automatic')
            ->update(['pricing_method' => 'automatic']);
    }

    /**
     * Tidak dikembalikan: pada kode lama kolom ini tidak dibaca untuk
     * teknologi tersebut, jadi `automatic` tetap berperilaku sama.
     */
    public function down(): void
    {
        //
    }
};
