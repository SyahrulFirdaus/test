<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Material menunjuk mesin dari Machine Cost.
 *
 * Rantainya menjadi: teknologi → mesin → material → harga. Tidak ada tabel
 * baru dan tidak ada daftar mesin kedua — `machine_costs` tetap satu-satunya
 * sumber nama mesin, tinggal ditunjuk dari sini.
 *
 * Boleh kosong. Material yang sudah ada — termasuk MJF dan SLM yang mesinnya
 * memang belum terdaftar di Machine Cost — tetap sah tanpa diisi lebih dulu
 * dan berkumpul di kelompok "Tanpa Mesin" paling bawah.
 *
 * KUNCI UNIK BERUBAH. Sebelumnya satu teknologi tidak boleh punya dua material
 * bernama sama; sekarang batas itu berlaku PER MESIN, supaya "PLA+" dapat
 * berdiri sendiri di tiap mesin seperti yang diminta. Konsekuensinya dijaga di
 * dua tempat lain:
 *   - App\Http\Requests\StorePrintMaterialRequest — nama tetap tidak boleh
 *     bertabrakan di dalam satu mesin (termasuk sesama "Tanpa Mesin", yang
 *     tidak dijaga indeks unik karena NULL selalu dianggap berbeda);
 *   - App\Models\PrintTechnology::toEstimatorArray() — Calculator mengenali
 *     material lewat NAMANYA, jadi bila satu nama dipakai beberapa mesin yang
 *     berlaku adalah baris tertua. Harga penawaran yang sudah berjalan karena
 *     itu tidak bergeser saat mesin lain ditambahkan kemudian.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tanpa foreign key, sama seperti `machine_costs.print_technology_id`:
        // SQLite tidak dapat menambahkan constraint pada tabel yang sudah
        // terbentuk, sedangkan pengujian berjalan di atasnya. Mesin yang
        // dihapus meninggalkan materialnya di kelompok "Tanpa Mesin".
        Schema::table('print_materials', function (Blueprint $table) {
            $table->unsignedBigInteger('machine_cost_id')->nullable()->after('print_technology_id')->index();
        });

        // Indeks unik yang baru dipasang LEBIH DULU, barulah yang lama dibuang.
        // MySQL menolak membuang indeks terakhir yang menopang foreign key
        // `print_technology_id`; karena indeks baru ini juga diawali kolom yang
        // sama, foreign key-nya tetap tertopang sepanjang perpindahan.
        Schema::table('print_materials', function (Blueprint $table) {
            $table->unique(['print_technology_id', 'machine_cost_id', 'material'], 'print_materials_technology_machine_material_unique');
        });

        Schema::table('print_materials', function (Blueprint $table) {
            $table->dropUnique('print_materials_print_technology_id_material_unique');
        });
    }

    /**
     * Kembalikan batas lama.
     *
     * Berhasil hanya bila tidak ada nama material yang terlanjur dipakai dua
     * mesin dalam satu teknologi — batas lama memang tidak mengizinkannya.
     */
    public function down(): void
    {
        Schema::table('print_materials', function (Blueprint $table) {
            $table->unique(['print_technology_id', 'material']);
        });

        Schema::table('print_materials', function (Blueprint $table) {
            $table->dropUnique('print_materials_technology_machine_material_unique');
            $table->dropIndex(['machine_cost_id']);
            $table->dropColumn('machine_cost_id');
        });
    }
};
