<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spesifikasi fisik mesin pada Price List.
 *
 * Tidak dibuat tabel baru: `machine_costs` SUDAH menjadi daftar mesin yang
 * dikelola Superadmin, jadi dimensinya menumpang di sana. Seluruh kolom lama
 * (watt, harga listrik, depresiasi, beserta accessor Machine Cost dan
 * Pembulatan) tidak disentuh sama sekali.
 *
 * Semuanya `nullable` supaya baris yang sudah ada tetap sah tanpa diisi lebih
 * dulu; detail yang kosong tampil sebagai "—" pada expand dan dilengkapi lewat
 * formulir Ubah Mesin.
 *
 * `print_technology_id` sengaja TANPA foreign key: SQLite tidak dapat
 * menambahkan constraint pada tabel yang sudah terbentuk, sedangkan pengujian
 * berjalan di atasnya. Akibatnya ditangani tampilan — mesin yang teknologinya
 * terhapus jatuh ke kelompok "Tanpa Teknologi", bukan menghilang dari daftar.
 */
return new class extends Migration
{
    /**
     * Teknologi bagi mesin yang kita seed sendiri.
     *
     * Hanya untuk mengisi baris yang sudah terlanjur ada; daftar yang sama ada
     * di database/seeders/PriceListSeeder.php bagi pemasangan baru. Mesin lain
     * yang ditambahkan admin dibiarkan kosong — menebak teknologinya dari nama
     * justru berisiko salah kelompok.
     */
    private const TECHNOLOGY_BY_MACHINE = [
        'Elegoo Neptune Max 4' => 'FDM',
        'Ender 3 V2' => 'FDM',
        'Bambu Lab P1S' => 'FDM',
        'Elegoo Saturn 4 12 K' => 'SLA',
    ];

    public function up(): void
    {
        Schema::table('machine_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('print_technology_id')->nullable()->after('id')->index();

            // Dimensi luar mesin, dalam milimeter.
            $table->decimal('width_mm', 8, 1)->nullable()->after('mesin');
            $table->decimal('depth_mm', 8, 1)->nullable()->after('width_mm');
            $table->decimal('height_mm', 8, 1)->nullable()->after('depth_mm');
            $table->decimal('weight_kg', 8, 2)->nullable()->after('height_mm');

            // Volume cetak memakai konvensi yang sama dengan `build_volume`
            // pada print_technologies: x = lebar meja, y = kedalaman, z = tinggi.
            $table->unsignedInteger('build_volume_x')->nullable()->after('weight_kg');
            $table->unsignedInteger('build_volume_y')->nullable()->after('build_volume_x');
            $table->unsignedInteger('build_volume_z')->nullable()->after('build_volume_y');
        });

        foreach (self::TECHNOLOGY_BY_MACHINE as $mesin => $code) {
            $technologyId = DB::table('print_technologies')->where('code', $code)->value('id');

            if ($technologyId !== null) {
                DB::table('machine_costs')->where('mesin', $mesin)->update([
                    'print_technology_id' => $technologyId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('machine_costs', function (Blueprint $table) {
            $table->dropColumn([
                'print_technology_id',
                'width_mm',
                'depth_mm',
                'height_mm',
                'weight_kg',
                'build_volume_x',
                'build_volume_y',
                'build_volume_z',
            ]);
        });
    }
};
