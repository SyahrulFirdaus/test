<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak Price Snapshot pada setiap model penawaran.
 *
 * Rincian perhitungannya sendiri sudah tersimpan utuh di `cost_breakdown`.
 * Kolom ini menambahkan tiga hal yang dapat dicari dan disaring langsung:
 *
 *   pricing_method   automatic | manual — dibekukan saat harga dihitung
 *   pricing_version  versi Pricing Engine yang menghitungnya
 *   priced_at        kapan harganya dihitung / ditetapkan
 *
 * Model yang sudah ada diisi dari rincian tersimpannya dan diberi versi
 * `legacy`; angkanya tidak dihitung ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('pricing_method', 20)->nullable()->after('cost_breakdown');
            $table->string('pricing_version', 40)->nullable()->after('pricing_method');
            $table->timestamp('priced_at')->nullable()->after('pricing_version');
        });

        DB::table('quotation_items')->orderBy('id')->select(['id', 'cost_breakdown', 'updated_at'])
            ->chunkById(200, function ($items) {
                foreach ($items as $item) {
                    $breakdown = json_decode((string) $item->cost_breakdown, true);

                    if (! is_array($breakdown) || ! array_key_exists('selling_price', $breakdown)) {
                        continue;
                    }

                    DB::table('quotation_items')->where('id', $item->id)->update([
                        'pricing_method' => ($breakdown['manual_pricing'] ?? false) ? 'manual' : 'automatic',
                        'pricing_version' => 'legacy',
                        'priced_at' => $item->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['pricing_method', 'pricing_version', 'priced_at']);
        });
    }
};
