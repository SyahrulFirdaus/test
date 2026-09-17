<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kuotasi JLC satu model SLA Industries di dalam sebuah penawaran.
 *
 * Satu baris per model, bukan per penawaran: tiap part dikuotasi JLC sendiri,
 * dan satu penawaran boleh mencampur SLA Industries dengan teknologi lain.
 * Harga penawarannya tetap penjumlahan harga seluruh modelnya, persis seperti
 * teknologi lain.
 *
 * Yang disimpan hanya PARAMETER yang diketik Admin. Total Bayar ke JLC, HPP,
 * Profit, dan Final Price tidak ikut disimpan — seluruhnya diturunkan
 * App\Support\SlaIndustries::compute(), sehingga tidak mungkin ada angka
 * tersimpan yang tidak lagi cocok dengan parameternya.
 *
 * Kecualinya `quotation_items.estimated_cost`: Final Price disalin ke sana saat
 * kuotasi disimpan, karena seluruh sistem lama — ringkasan penawaran, tagihan,
 * PDF, tracking — sudah membaca kolom itu sebagai harga yang berlaku.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_industries_quotes', function (Blueprint $table) {
            $table->id();

            // Satu model hanya punya satu kuotasi yang berlaku; mengisi ulang
            // formnya memperbarui baris yang sama.
            $table->foreignId('quotation_item_id')->unique()->constrained()->cascadeOnDelete();

            // Nama Produk/Model pada form, mis. "Impeller". Boleh kosong —
            // kalau tidak diisi, nama berkas modelnya yang dipakai.
            $table->string('product_name')->nullable();

            $table->decimal('usd_rate', 14, 2)->default(0);
            $table->decimal('jlc_price_usd', 14, 2)->default(0);
            $table->decimal('jlc_shipping_usd', 14, 2)->default(0);
            $table->decimal('customs_idr', 14, 2)->default(0);
            $table->decimal('margin_percent', 5, 2)->default(0);

            // Siapa yang terakhir menetapkan harganya, untuk penelusuran.
            // Akun yang dihapus tidak boleh ikut menghapus kuotasinya.
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_industries_quotes');
    }
};
