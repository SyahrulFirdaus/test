<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master data harga material FDM, dikelola admin lewat halaman Price List.
 *
 * `purchase_price` dan `sale_price` diisi manual admin; kolom turunan (harga
 * per gram, pembulatan harga, harga per 10 gram) dihitung sebagai accessor di
 * model `FdmMaterial`, bukan disimpan, supaya formulanya tetap satu sumber
 * kebenaran. `technical_spec` menampung data teknis (densitas, batas ukuran,
 * warna, keterangan) yang dipakai estimator tapi tidak diedit lewat form
 * Price List — Price List hanya mengelola kolom komersialnya.
 *
 * Baris awalnya disisipkan langsung di sini (bukan lewat Seeder) karena
 * material FDM adalah satu-satunya sumber Calculator/Quotation untuk
 * teknologi ini — tanpa baris ini, `RefreshDatabase` pada test akan membuat
 * tabel kosong dan seluruh alur FDM gagal. Datanya persis mengikuti daftar
 * harga NUSAMA3D; `technical_spec` (densitas, batas ukuran, warna) tidak ada
 * di daftar harga aslinya sehingga diisi perkiraan berdasarkan keluarga bahan
 * (nilai yang sama dengan material lama untuk keluarga yang sudah dikenal
 * sistem — PLA/PETG/ABS/TPU — dan nilai umum industri untuk keluarga baru).
 */
return new class extends Migration
{
    private const STANDARD_COLORS = ['putih', 'hitam', 'abu', 'merah', 'biru'];

    private const CF_COLORS = ['hitam', 'abu'];

    public function up(): void
    {
        Schema::create('fdm_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material');
            $table->string('brand');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->string('remark')->nullable();
            $table->json('technical_spec')->nullable();
            $table->timestamps();
        });

        $plaSpec = fn (string $description) => $this->spec(1.24, ['x' => 250, 'y' => 250, 'z' => 300], self::STANDARD_COLORS, $description);
        $petgSpec = fn (string $description) => $this->spec(1.27, ['x' => 500, 'y' => 500, 'z' => 600], self::STANDARD_COLORS, $description);
        $absSpec = fn (string $description) => $this->spec(1.04, ['x' => 500, 'y' => 480, 'z' => 480], self::STANDARD_COLORS, $description);
        $tpuSpec = fn (float $density, string $description) => $this->spec($density, ['x' => 250, 'y' => 250, 'z' => 300], self::STANDARD_COLORS, $description);
        $engineeringSpec = fn (float $density, string $description) => $this->spec($density, ['x' => 300, 'y' => 300, 'z' => 350], self::STANDARD_COLORS, $description);
        $cfSpec = fn (float $density, string $description) => $this->spec($density, ['x' => 300, 'y' => 300, 'z' => 350], self::CF_COLORS, $description);

        $rows = [
            ['material' => 'PLA Plus Standart ESUN', 'brand' => 'ESUN', 'purchase_price' => 185000, 'sale_price' => 463, 'remark' => 'Standard Material', 'technical_spec' => $plaSpec('Filamen PLA dengan tambahan aditif, kekuatan sedikit lebih baik dari PLA biasa.')],
            ['material' => 'PLA Basic ESUN', 'brand' => 'ESUN', 'purchase_price' => 150000, 'sale_price' => 563, 'remark' => 'Standard Material', 'technical_spec' => $plaSpec('Filamen PLA standar, mudah dicetak dan ekonomis.')],
            ['material' => 'PETG High Speed ESUN', 'brand' => 'ESUN', 'purchase_price' => 210000, 'sale_price' => 788, 'remark' => 'Standard Material', 'technical_spec' => $petgSpec('Filamen PETG kecepatan tinggi, cocok untuk part fungsional harian.')],
            ['material' => 'ABS ESUN', 'brand' => 'ESUN', 'purchase_price' => 200000, 'sale_price' => 750, 'remark' => 'Standard Material', 'technical_spec' => $absSpec('Filamen ABS standar, tahan panas dan benturan lebih baik dari PLA.')],
            ['material' => 'ASA ESUN', 'brand' => 'ESUN', 'purchase_price' => 300000, 'sale_price' => 938, 'remark' => 'Standard Material', 'technical_spec' => $engineeringSpec(1.07, 'Filamen ASA, tahan cuaca dan sinar UV — cocok untuk part outdoor.')],
            ['material' => 'PA 12 ESUN', 'brand' => 'ESUN', 'purchase_price' => 1000000, 'sale_price' => 3125, 'remark' => 'Engineering Material', 'technical_spec' => $engineeringSpec(1.01, 'Filamen nylon PA12, liat dan tahan lelah untuk part fungsional.')],
            ['material' => 'PA12 CF ESUN', 'brand' => 'ESUN', 'purchase_price' => 1500000, 'sale_price' => 4688, 'remark' => 'Engineering Material', 'technical_spec' => $cfSpec(1.09, 'Filamen nylon PA12 dengan serat karbon, kaku dan sangat kuat.')],
            ['material' => 'PET CF ESUN', 'brand' => 'ESUN', 'purchase_price' => 350000, 'sale_price' => 1094, 'remark' => 'Engineering Material', 'technical_spec' => $cfSpec(1.30, 'Filamen PET dengan serat karbon, kaku dan ringan.')],
            ['material' => 'TPU 95A ESUN', 'brand' => 'ESUN', 'purchase_price' => 600000, 'sale_price' => 1875, 'remark' => 'Engineering Material', 'technical_spec' => $tpuSpec(1.21, 'Filamen TPU tingkat kekerasan 95A, elastis namun cukup kaku untuk gasket.')],
            ['material' => 'TPU 85A ESUN', 'brand' => 'ESUN', 'purchase_price' => 600000, 'sale_price' => 1875, 'remark' => 'Engineering Material', 'technical_spec' => $tpuSpec(1.19, 'Filamen TPU tingkat kekerasan 85A, lebih lentur dari TPU 95A.')],
            ['material' => 'ABS CF ESUN', 'brand' => 'ESUN', 'purchase_price' => 450000, 'sale_price' => 1406, 'remark' => 'Engineering Material', 'technical_spec' => $cfSpec(1.08, 'Filamen ABS dengan serat karbon, kaku dan tahan panas.')],
            ['material' => 'PET CF SUNLU', 'brand' => 'SUNLU', 'purchase_price' => 320000, 'sale_price' => 1000, 'remark' => 'Engineering Material', 'technical_spec' => $cfSpec(1.30, 'Filamen PET dengan serat karbon, kaku dan ringan.')],
            ['material' => 'TPU 95A SUNLU', 'brand' => 'SUNLU', 'purchase_price' => 250000, 'sale_price' => 625, 'remark' => 'Standard Material', 'technical_spec' => $tpuSpec(1.21, 'Filamen TPU tingkat kekerasan 95A, elastis namun cukup kaku untuk gasket.')],
            ['material' => 'ASA SUNLU', 'brand' => 'SUNLU', 'purchase_price' => 275000, 'sale_price' => 688, 'remark' => 'Standard Material', 'technical_spec' => $engineeringSpec(1.07, 'Filamen ASA, tahan cuaca dan sinar UV — cocok untuk part outdoor.')],
            ['material' => 'PA12 CF SUNLU', 'brand' => 'SUNLU', 'purchase_price' => 950000, 'sale_price' => 2969, 'remark' => 'Engineering Material', 'technical_spec' => $cfSpec(1.09, 'Filamen nylon PA12 dengan serat karbon, kaku dan sangat kuat.')],
        ];

        foreach ($rows as &$row) {
            $row['technical_spec'] = json_encode($row['technical_spec']);
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        DB::table('fdm_materials')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('fdm_materials');
    }

    /** @return array<string, mixed> */
    private function spec(float $density, array $maxSize, array $colors, string $description): array
    {
        return [
            'density' => $density,
            'maxSize' => $maxSize,
            'minSize' => ['x' => 30, 'y' => 30, 'z' => 10],
            'minSizeSlender' => null,
            'colors' => $colors,
            'description' => $description,
            'characteristics' => [],
            'pros' => [],
            'cons' => [],
        ];
    }
};
