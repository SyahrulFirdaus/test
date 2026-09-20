<?php

namespace App\Services\PriceList\Excel;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Support\PricingMethod;
use Illuminate\Support\Facades\DB;

/**
 * Penyimpan hasil Import yang sudah disetujui pengelola.
 *
 * Dipanggil hanya setelah pratinjau: yang sampai ke sini adalah baris yang
 * SUDAH dinyatakan sah oleh MaterialImportReader. Seluruhnya berjalan dalam
 * satu transaksi, jadi kegagalan di baris mana pun mengembalikan basis data ke
 * keadaan semula — bukan meninggalkan separuh data masuk tanpa keterangan.
 *
 * Yang disimpan hanya Harga Beli dan Harga Jual; Harga per gram, Pembulatan
 * Harga, dan Harga/10 gram tetap diturunkan Pricing Engine seperti sebelumnya.
 * Import karena itu tidak dapat membuat angka yang tidak konsisten dengan
 * Calculator, sekalipun berkasnya menuliskan angka turunan yang keliru.
 *
 * Teknologi, mesin, dan metode harga TIDAK disentuh: material baru mewarisi
 * teknologi halamannya dan berdiri tanpa mesin sampai ditetapkan lewat form
 * CRUD, sedangkan material yang diperbarui mempertahankan mesin dan metode
 * harganya. Excel hanya media pertukaran data komersial, bukan tempat
 * mengubah relasi.
 */
class MaterialImporter
{
    /** Baris kembar dilewati, material yang sudah ada dibiarkan apa adanya. */
    public const ON_DUPLICATE_SKIP = 'skip';

    /** Baris kembar menimpa material yang sudah ada. */
    public const ON_DUPLICATE_UPDATE = 'update';

    /**
     * Simpan baris sah.
     *
     * @param  array<int, MaterialImportRow>  $rows
     * @return array{created: int, updated: int, skipped: int, changes: array<int, array<string, mixed>>}
     */
    public function import(PrintTechnology $technology, array $rows, string $onDuplicate): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $changes = [];

        DB::transaction(function () use ($technology, $rows, $onDuplicate, &$created, &$updated, &$skipped, &$changes) {
            foreach ($rows as $row) {
                if (! $row->isValid()) {
                    continue;
                }

                if ($row->isDuplicate()) {
                    if ($onDuplicate !== self::ON_DUPLICATE_UPDATE) {
                        $skipped++;

                        continue;
                    }

                    /*
                     * Dicari ulang lewat id yang tercatat saat pratinjau, DAN
                     * dibatasi pada teknologi halamannya. Bila materialnya
                     * sudah dihapus orang lain sejak pratinjau dibuka, barisnya
                     * diperlakukan sebagai material baru — bukan gagal.
                     */
                    $material = $technology->materials()->find($row->existingId);

                    if ($material !== null) {
                        $before = $this->snapshot($material);
                        $material->update($row->values);
                        $after = $this->snapshot($material->refresh());

                        if ($before !== $after) {
                            $changes[] = ['material' => $material->material, 'sebelum' => $before, 'sesudah' => $after];
                        }

                        $updated++;

                        continue;
                    }
                }

                /*
                 * Material baru selalu milik teknologi halamannya.
                 *
                 * Dibuat lewat relasi `materials()`, jadi `print_technology_id`
                 * terisi teknologi itu sendiri — material SLA tidak mungkin
                 * mendarat di FDM, dan tidak ada teknologi baru yang dibuat.
                 * Mesinnya dibiarkan kosong ("Tanpa Mesin") sampai ditetapkan
                 * lewat form Ubah Material: Excel tidak menyebut mesin, dan
                 * menebaknya akan membuat mesin atau kaitan yang tidak diminta.
                 */
                $material = $technology->materials()->create($row->values);
                $changes[] = ['material' => $material->material, 'sebelum' => null, 'sesudah' => $this->snapshot($material)];
                $created++;
            }
        });

        // Daftar material yang dipakai Edit Specification dan Calculator
        // di-cache; isinya baru saja berubah.
        PrintTechnology::forgetCache();

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'changes' => $changes];
    }

    /**
     * Nilai yang dicatat Activity Log.
     *
     * Hanya kolom komersial — tidak ada kata sandi, token, maupun rahasia lain
     * yang tersentuh fitur ini.
     *
     * @return array<string, mixed>
     */
    private function snapshot(PrintMaterial $material): array
    {
        return [
            'material' => $material->material,
            'brand' => $material->brand,
            'purchase_price' => (float) $material->purchase_price,
            'sale_price' => (float) $material->sale_price,
            'remark' => $material->remark,
            // Hanya dicatat bagi teknologi yang memakainya, supaya jejak
            // material FDM tidak berisi kolom yang tidak berlaku baginya.
            ...(PricingMethod::appliesTo($material->technology?->code)
                ? ['pricing_method' => $material->pricing_method]
                : []),
        ];
    }
}
