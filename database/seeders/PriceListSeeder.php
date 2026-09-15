<?php

namespace Database\Seeders;

use App\Models\MachineCost;
use App\Models\PackagingItem;
use App\Models\PrintTechnology;
use Illuminate\Database\Seeder;

/**
 * Data awal Packaging dan Machine Cost pada Price List, persis mengikuti
 * daftar harga NUSAMA3D.
 *
 * Material FDM dan SLA TIDAK diseed di sini — keduanya disisipkan langsung di
 * migrasi masing-masing (`2026_08_21_000035_...`, `2026_08_21_000036_...`)
 * karena berstatus data wajib bagi Calculator/Quotation, sedangkan Packaging
 * dan Machine Cost di sini murni master data admin yang belum dipakai
 * menghitung biaya, jadi aman diseed terpisah lewat `db:seed`.
 */
class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPackagingItems();
        $this->seedMachineCosts();
    }

    private function seedPackagingItems(): void
    {
        $rows = [
            ['item' => 'Kardus', 'ukuran' => 'XS', 'dimensi' => '10 x 10 x 5 cm', 'price' => 2000, 'price_unit' => PackagingItem::UNIT_FLAT],
            ['item' => 'Kardus', 'ukuran' => 'S', 'dimensi' => '20 x 20 x 8 cm', 'price' => 3000, 'price_unit' => PackagingItem::UNIT_FLAT],
            ['item' => 'Kardus', 'ukuran' => 'M', 'dimensi' => '30 x 15 x 15 cm', 'price' => 5000, 'price_unit' => PackagingItem::UNIT_FLAT],
            ['item' => 'Kardus', 'ukuran' => 'L', 'dimensi' => '40 x 40 x 20 cm', 'price' => 12000, 'price_unit' => PackagingItem::UNIT_FLAT],
            ['item' => 'Kardus', 'ukuran' => 'XL', 'dimensi' => '50 x 35 x 30 cm', 'price' => 15000, 'price_unit' => PackagingItem::UNIT_FLAT],
            ['item' => 'Foam', 'ukuran' => '-', 'dimensi' => 'Tebal 5 cm', 'price' => 2000, 'price_unit' => PackagingItem::UNIT_PER_CM],
            ['item' => 'Foam', 'ukuran' => '-', 'dimensi' => 'Tebal 2 cm', 'price' => 1000, 'price_unit' => PackagingItem::UNIT_PER_CM],
            ['item' => 'Bubble', 'ukuran' => '-', 'dimensi' => '-', 'price' => 1000, 'price_unit' => PackagingItem::UNIT_PER_CM],
        ];

        foreach ($rows as $row) {
            PackagingItem::updateOrCreate(
                ['item' => $row['item'], 'ukuran' => $row['ukuran'], 'dimensi' => $row['dimensi']],
                $row
            );
        }
    }

    /**
     * Mesin beserta teknologi tempatnya dikelompokkan di Price List.
     *
     * Dimensi fisiknya sengaja TIDAK diseed: angka lebar/tinggi/berat tiap
     * mesin bukan milik repositori ini, jadi dibiarkan kosong dan diisi
     * Superadmin lewat Ubah Mesin. Yang kosong tampil "—" pada expand.
     *
     * Daftar teknologi yang sama dipakai migrasi
     * `..._000046_add_machine_specs_to_machine_costs_table` untuk mengisi baris
     * yang sudah terlanjur ada.
     */
    private function seedMachineCosts(): void
    {
        $rows = [
            ['mesin' => 'Elegoo Neptune Max 4', 'technology' => 'FDM', 'watt_kwh' => 0.55, 'harga_listrik' => 1700, 'depresiasi' => 5000],
            ['mesin' => 'Ender 3 V2', 'technology' => 'FDM', 'watt_kwh' => 0.25, 'harga_listrik' => 1700, 'depresiasi' => 2100],
            ['mesin' => 'Elegoo Saturn 4 12 K', 'technology' => 'SLA', 'watt_kwh' => 0.144, 'harga_listrik' => 1700, 'depresiasi' => 5100],
            ['mesin' => 'Bambu Lab P1S', 'technology' => 'FDM', 'watt_kwh' => 0.35, 'harga_listrik' => 1700, 'depresiasi' => 5300],
        ];

        foreach ($rows as $row) {
            $code = $row['technology'];
            unset($row['technology']);

            $row['print_technology_id'] = PrintTechnology::idFor($code);

            MachineCost::updateOrCreate(['mesin' => $row['mesin']], $row);
        }
    }
}
