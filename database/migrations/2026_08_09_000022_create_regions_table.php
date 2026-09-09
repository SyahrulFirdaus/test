<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wilayah administratif Indonesia: provinsi beserta kota/kabupatennya.
 *
 * Disimpan sebagai satu tabel berjenjang — baris provinsi tidak punya induk,
 * baris kota/kabupaten menunjuk provinsinya lewat `parent_id`. Bentuk ini
 * membuat penambahan tingkat berikutnya (kecamatan, kelurahan) kelak cukup
 * menambah nilai `type` baru tanpa mengubah struktur.
 *
 * CATATAN: tabel ini sudah digantikan wilayah empat tingkat (provinces,
 * regencies, districts, villages) pada migrasi 2026_08_09_000027, yang sekaligus
 * membuangnya. Berkasnya tetap disimpan agar riwayat migrasi utuh dan basis data
 * yang terlanjur memakainya dapat naik bertahap; jangan dipakai untuk fitur baru.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->comment('province | city');
            $table->foreignId('parent_id')->nullable()->constrained('regions')->cascadeOnDelete();
            $table->string('name', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'parent_id', 'name']);
            // Nama wilayah unik dalam satu induk: "Kabupaten Bandung" hanya
            // boleh ada satu kali di Jawa Barat.
            $table->unique(['type', 'parent_id', 'name'], 'regions_unique_within_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
