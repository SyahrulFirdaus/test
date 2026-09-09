<?php

use App\Support\QuotationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perlebar kolom `status` penawaran.
 *
 * Kolom dibuat selebar 20 karakter saat alur statusnya masih pendek, sehingga
 * status pembatalan seperti `cancellation_requested` (22 karakter) ditolak
 * MySQL dengan galat "Data too long for column 'status'". Lebarnya disamakan
 * dengan `quotation_histories.status` dan `status_before_cancellation` yang
 * sudah 40 karakter.
 *
 * Hanya tipe kolomnya yang diubah — tidak ada baris yang dihapus atau diubah
 * isinya. Nilai bawaannya sekalian diselaraskan ke tahap pertama alur, karena
 * bawaan lama (`new`) sudah tidak ada lagi dalam daftar status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->string('status', 40)->default(QuotationStatus::first())->change();
        });
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->string('status', 20)->default('new')->change();
        });
    }
};
