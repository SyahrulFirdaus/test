<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alamat pengiriman yang dipilih pada sebuah permintaan penawaran.
 *
 * Dua kolom sekaligus, dan keduanya memang diperlukan:
 *
 *   address_id        menunjuk alamat di buku alamat, dipakai selama alamatnya
 *                     masih ada — mis. untuk menandai penawaran mana saja yang
 *                     dikirim ke alamat tertentu.
 *   shipping_address  salinan isi alamat pada saat penawaran dibuat.
 *
 * Salinannya wajib ada karena pelanggan boleh mengubah atau menghapus alamatnya
 * kapan saja. Tanpa salinan, penawaran lama akan ikut berubah tujuan
 * pengirimannya — atau kehilangan alamatnya sama sekali — padahal barangnya
 * sudah terlanjur dikirim ke alamat yang lama.
 *
 * Keduanya boleh kosong: penawaran yang dibuat sebelum buku alamat ada, dan
 * pelanggan yang belum sempat mengisi alamat, tetap tercatat seperti biasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->foreignId('address_id')->nullable()->after('whatsapp')->constrained('addresses')->nullOnDelete();
            $table->json('shipping_address')->nullable()->after('address_id')->comment('Salinan alamat saat penawaran dibuat');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('address_id');
            $table->dropColumn('shipping_address');
        });
    }
};
