<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengelompokan pertanyaan menjadi beberapa bagian di dalam satu langkah.
 *
 * Pendaftaran Business menanyakan lima belas hal sekaligus pada langkah
 * "Kebutuhan Bisnis". Ditampilkan berderet, halamannya terasa seperti formulir
 * panjang; dipecah menjadi bagian bertajuk — Kebutuhan 3D Printing, Kebutuhan
 * Produksi, Teknologi & Material, dan seterusnya — halaman yang sama terbaca
 * seperti proses pengenalan pelanggan.
 *
 * `category` menentukan tajuk bagiannya, `category_order` urutan bagiannya.
 * Pertanyaan tanpa kategori (pendaftaran Personal) tampil apa adanya seperti
 * sebelumnya, jadi alur Personal tidak berubah sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_questions', function (Blueprint $table) {
            $table->string('category', 80)->nullable()->after('step_label');
            $table->unsignedSmallInteger('category_order')->default(0)->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('registration_questions', function (Blueprint $table) {
            $table->dropColumn(['category', 'category_order']);
        });
    }
};
