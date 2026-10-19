<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Ulasan Google di Home yang mudah diperbarui dari admin:
 *  - testimonials.reviewed_at : tanggal ulasan, ditampilkan relatif
 *    ("2 minggu lalu") sehingga tidak pernah basi seperti teks tetap
 *    `reviewed_label`. Ulasan lama dibiarkan kosong (tanggal aslinya tidak
 *    tercatat) dan tetap memakai `reviewed_label`-nya sampai diisi admin.
 *  - company_profiles.google_reviews_url : tautan "Lihat semua ulasan di Google".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->date('reviewed_at')->nullable()->after('reviewed_label');
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('google_reviews_url', 500)->nullable()->after('maps_url');
        });
    }

    public function down(): void
    {
        Schema::table('testimonials', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
        });

        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('google_reviews_url');
        });
    }
};
