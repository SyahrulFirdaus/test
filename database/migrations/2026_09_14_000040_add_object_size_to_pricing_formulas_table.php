<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ukuran 3D object untuk simulasi Basic Fee pada tab "Harga" Price List.
 *
 * Basic Fee sendiri tidak disimpan: tarifnya selalu diturunkan dari sisi
 * terpanjang object lewat App\Support\BasicFee, persis seperti pada penawaran
 * sungguhan. Yang perlu disimpan hanya ukuran yang sedang disimulasikan admin,
 * karena tab Harga tidak punya model 3D untuk diukur.
 *
 * Nilai awal 100 mm masuk tingkat "Sedang" (Rp25.000) — cukup mewakili part
 * kebanyakan, dan admin bebas menggantinya per teknologi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_formulas', function (Blueprint $table) {
            $table->decimal('object_size_mm', 8, 2)->default(100)->after('overtime_cost');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_formulas', function (Blueprint $table) {
            $table->dropColumn('object_size_mm');
        });
    }
};
