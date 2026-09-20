<?php

namespace Tests\Feature;

use App\Models\MachineCost;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PriceList\Excel\MaterialSheet;
use App\Support\SlaIndustries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import & Export Excel material SLA.
 *
 * Mekanismenya sama persis dengan FDM — penulis, pembaca, pemeriksa, dan
 * penyimpannya satu, lihat Tests\Feature\MaterialExcelTest. Yang diuji di sini
 * hanyalah yang memang BERBEDA pada SLA, dan yang harus tetap sama meski
 * berbeda:
 *
 *   - kodenya "SLAI" tetapi berkasnya bernama SLA;
 *   - materialnya memilih sendiri Kalkulator Otomatis atau Manual, jadi
 *     berkasnya punya satu kolom tambahan dan metode itu tidak boleh rusak;
 *   - material Kalkulator Manual harganya ditetapkan tim, jadi kolom harganya
 *     boleh kosong dan tersimpan nol seperti pada form CRUD;
 *   - seluruh hasil import tetap milik teknologi SLA, tidak pernah FDM.
 */
class MaterialExcelSlaTest extends TestCase
{
    use RefreshDatabase;

    private ?User $superAdmin = null;

    private function superAdmin(): User
    {
        return $this->superAdmin ??= User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function sla(): PrintTechnology
    {
        return PrintTechnology::where('code', SlaIndustries::CODE)->firstOrFail();
    }

    private function fdm(): PrintTechnology
    {
        return PrintTechnology::where('code', 'FDM')->firstOrFail();
    }

    /** @param  array<int, array<int, mixed>>  $rows */
    private function xlsx(array $rows, string $name = 'SLA_Material.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $line => $cells) {
            foreach ($cells as $column => $value) {
                $sheet->setCellValue(chr(65 + $column).($line + 1), $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /** @param  array<int, array<int, mixed>>  $dataRows */
    private function fileWithHeader(array $dataRows): UploadedFile
    {
        return $this->xlsx([MaterialSheet::headings($this->sla()), ...$dataRows]);
    }

    private function sheetOf(string $contents): array
    {
        $path = tempnam(sys_get_temp_dir(), 'dl').'.xlsx';
        file_put_contents($path, $contents);

        return IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
    }

    /** Unggah berkas, lalu simpan hasil pratinjaunya. */
    private function import(UploadedFile $file, string $onDuplicate = 'skip'): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $this->sla()), ['file' => $file])
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $this->sla()), ['on_duplicate' => $onDuplicate])
            ->assertRedirect();
    }

    /* ============================================================ halaman === */

    public function test_tombol_excel_tampil_di_halaman_sla(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technology', ['slug' => 'sla']))
            ->assertOk()
            ->assertSee('Import Excel')
            ->assertSee('Export Excel')
            ->assertSee('Template Excel')
            ->assertSee('Contoh Excel')
            // CRUD SLA yang sudah ada tetap pada tempatnya.
            ->assertSee('Tambah Material SLA');
    }

    /* ============================================================= export === */

    /**
     * Berkasnya bernama SLA, bukan SLAI.
     *
     * "SLAI" hanya kode teknis pada `print_technologies.code`; yang dikenal tim
     * dan yang diminta adalah SLA.
     */
    public function test_nama_berkas_memakai_sla_bukan_kode_teknisnya(): void
    {
        $sla = $this->sla();
        $superAdmin = $this->superAdmin();

        $this->assertSame(SlaIndustries::CODE, $sla->code);

        foreach ([
            'export' => 'SLA_Material_'.now()->format('Y-m-d').'.xlsx',
            'template' => 'Template_SLA_Material.xlsx',
            'example' => 'Contoh_SLA_Material.xlsx',
        ] as $action => $filename) {
            $response = $this->actingAs($superAdmin)
                ->get(route('superadmin.price-list.materials.excel.'.$action, $sla))
                ->assertOk();

            $this->assertStringContainsString('filename="'.$filename.'"', $response->headers->get('content-disposition'), $action);
        }
    }

    /**
     * Sembilan kolom pertama sama dengan FDM; SLA menambahkan Metode Harga.
     *
     * Tanpa kolom itu, material Kalkulator Manual yang diimpor akan diam-diam
     * berubah menjadi Otomatis atau sebaliknya.
     */
    public function test_kolom_sla_menambahkan_metode_harga(): void
    {
        $standard = [
            'No', 'Material', 'Brand', 'Harga Beli', 'Harga per gram',
            'Harga Jual', 'Pembulatan Harga', 'Harga/10 gram', 'Remark',
        ];

        $this->assertSame($standard, MaterialSheet::headings($this->fdm()));
        $this->assertSame([...$standard, 'Metode Harga'], MaterialSheet::headings($this->sla()));
    }

    public function test_export_mengambil_data_sla_dari_basis_data(): void
    {
        $sla = $this->sla();

        $otomatis = $sla->materials()->create([
            'material' => 'Standard Resin Plus Sunlu',
            'brand' => 'Sunlu',
            'purchase_price' => 275000,
            'sale_price' => 1031,
            'remark' => 'Standard Material',
            'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
        ]);

        $sla->materials()->create([
            'material' => 'Resin Kuotasi Vendor',
            'brand' => 'Sunlu',
            'purchase_price' => 0,
            'sale_price' => 0,
            'pricing_method' => PrintMaterial::PRICING_MANUAL,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.excel.export', $sla))
            ->assertOk();

        $rows = $this->sheetOf($response->getContent());

        $this->assertSame(MaterialSheet::headings($sla), $rows[0]);

        $line = collect($rows)->firstWhere(1, 'Standard Resin Plus Sunlu');

        $this->assertNotNull($line);

        // Angka numerik, dan tiga kolom turunannya dari Pricing Engine.
        $this->assertSame(275000.0, (float) $line[3]);
        $this->assertSame(344, (int) $line[4]);
        $this->assertSame(1031.0, (float) $line[5]);
        $this->assertSame(1100, (int) $line[6]);
        $this->assertSame(11000, (int) $line[7]);
        $this->assertSame($otomatis->price_per_gram, (int) $line[4]);
        $this->assertSame('Kalkulator Otomatis', $line[9]);

        $manual = collect($rows)->firstWhere(1, 'Resin Kuotasi Vendor');

        $this->assertSame('Kalkulator Manual', $manual[9]);
    }

    /** Contoh SLA berisi enam resin acuan, persis seperti yang diminta. */
    public function test_contoh_sla_berisi_enam_resin_acuan(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.excel.example', $this->sla()))
            ->assertOk();

        $rows = $this->sheetOf($response->getContent());

        $this->assertSame(MaterialSheet::headings($this->sla()), $rows[0]);
        $this->assertCount(7, $rows); // judul + enam baris

        $expected = [
            ['Standard Resin Plus Sunlu', 275000, 344, 1031, 1100, 11000, 'Standard Material'],
            ['Standard Resin High Clear Sunlu', 450000, 563, 1688, 1700, 17000, 'Standard Material'],
            ['Standard Resin Nylon like Resin Sunlu', 550000, 688, 2063, 2100, 21000, 'Engineering Material'],
            ['Standard Resin ABS like resin Sunlu', 450000, 563, 1688, 1700, 17000, 'Engineering Material'],
            ['Standard Resin High Temp Sunlu', 550000, 688, 2063, 2100, 21000, 'Engineering Material'],
            ['Standard Resin High Toughness Sunlu', 450000, 563, 1688, 1700, 17000, 'Engineering Material'],
        ];

        foreach ($expected as $index => [$material, $purchase, $perGram, $sale, $rounded, $per10, $remark]) {
            $row = $rows[$index + 1];

            $this->assertSame($index + 1, (int) $row[0], $material);
            $this->assertSame($material, $row[1]);
            $this->assertSame('Sunlu', $row[2]);
            $this->assertSame((float) $purchase, (float) $row[3], $material);
            $this->assertSame($perGram, (int) $row[4], $material);
            $this->assertSame((float) $sale, (float) $row[5], $material);
            $this->assertSame($rounded, (int) $row[6], $material);
            $this->assertSame($per10, (int) $row[7], $material);
            $this->assertSame($remark, $row[8]);
        }
    }

    public function test_template_sla_kosong(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.excel.template', $this->sla()))
            ->assertOk();

        $rows = $this->sheetOf($response->getContent());

        $this->assertSame(MaterialSheet::headings($this->sla()), $rows[0]);
        $this->assertNull(collect($rows)->skip(1)->first(fn (array $row) => trim((string) ($row[1] ?? '')) !== ''));
    }

    /* ============================================================= import === */

    /** Seluruh hasil import tetap milik SLA — tidak pernah mendarat di FDM. */
    public function test_import_selalu_masuk_ke_teknologi_sla(): void
    {
        $sla = $this->sla();
        $fdm = $this->fdm();
        $sebelumFdm = $fdm->materials()->count();

        $this->import($this->fileWithHeader([
            [1, 'Standard Resin Plus Sunlu', 'Sunlu', 275000, 344, 1031, 1100, 11000, 'Standard Material', 'Kalkulator Otomatis'],
        ]));

        $material = $sla->materials()->where('material', 'Standard Resin Plus Sunlu')->firstOrFail();

        $this->assertSame($sla->getKey(), $material->print_technology_id);
        $this->assertSame($sebelumFdm, $fdm->materials()->count());
        $this->assertSame(0, $fdm->materials()->where('material', 'Standard Resin Plus Sunlu')->count());

        // Harga turunannya tetap dari Pricing Engine yang sama.
        $this->assertSame(344, $material->price_per_gram);
        $this->assertSame(1100, $material->rounded_price);
        $this->assertSame(11000, $material->price_per_10_gram);
    }

    public function test_metode_harga_terbaca_dari_berkas(): void
    {
        $this->import($this->fileWithHeader([
            [1, 'Resin Otomatis', 'Sunlu', 275000, 344, 1031, 1100, 11000, 'Standard Material', 'Kalkulator Otomatis'],
            [2, 'Resin Manual', 'Sunlu', '', '', '', '', '', 'Engineering Material', 'Kalkulator Manual'],
        ]));

        $sla = $this->sla();

        $otomatis = $sla->materials()->where('material', 'Resin Otomatis')->firstOrFail();
        $manual = $sla->materials()->where('material', 'Resin Manual')->firstOrFail();

        $this->assertSame(PrintMaterial::PRICING_AUTOMATIC, $otomatis->pricing_method);
        $this->assertSame(275000.0, (float) $otomatis->purchase_price);

        // Harga material Kalkulator Manual ditetapkan tim per penawaran, jadi
        // kolomnya boleh kosong dan tersimpan nol — sama seperti form CRUD.
        $this->assertSame(PrintMaterial::PRICING_MANUAL, $manual->pricing_method);
        $this->assertSame(0.0, (float) $manual->purchase_price);
        $this->assertSame(0.0, (float) $manual->sale_price);
    }

    /**
     * Update Existing tidak merusak metode harga yang sudah ada.
     *
     * Kolom Metode Harga yang dikosongkan berarti "biarkan seperti sekarang",
     * bukan "kembalikan ke bawaan".
     */
    public function test_metode_harga_dipertahankan_saat_kolomnya_kosong(): void
    {
        $sla = $this->sla();
        $machine = MachineCost::first();

        $material = $sla->materials()->create([
            'material' => 'Resin Terdaftar',
            'brand' => 'Sunlu',
            'purchase_price' => 275000,
            'sale_price' => 1031,
            'remark' => 'Lama',
            'machine_cost_id' => $machine?->id,
            'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
        ]);

        $this->import($this->fileWithHeader([
            [1, 'Resin Terdaftar', 'Sunlu', 300000, 375, 1200, 1200, 12000, 'Baru', ''],
        ]), 'update');

        $material->refresh();

        $this->assertSame(1, $sla->materials()->where('material', 'Resin Terdaftar')->count());
        $this->assertSame(300000.0, (float) $material->purchase_price);
        $this->assertSame('Baru', $material->remark);

        // Yang tidak disebut berkas tidak berubah.
        $this->assertSame(PrintMaterial::PRICING_AUTOMATIC, $material->pricing_method);
        $this->assertSame($machine?->id, $material->machine_cost_id);
        $this->assertSame($sla->getKey(), $material->print_technology_id);
    }

    public function test_metode_harga_dapat_diubah_lewat_import(): void
    {
        $sla = $this->sla();

        $material = $sla->materials()->create([
            'material' => 'Resin Pindah Metode',
            'brand' => 'Sunlu',
            'purchase_price' => 0,
            'sale_price' => 0,
            'pricing_method' => PrintMaterial::PRICING_MANUAL,
        ]);

        $this->import($this->fileWithHeader([
            [1, 'Resin Pindah Metode', 'Sunlu', 450000, 563, 1688, 1700, 17000, 'Standard Material', 'Kalkulator Otomatis'],
        ]), 'update');

        $material->refresh();

        $this->assertSame(PrintMaterial::PRICING_AUTOMATIC, $material->pricing_method);
        $this->assertSame(450000.0, (float) $material->purchase_price);
        $this->assertSame(17000, $material->price_per_10_gram);
    }

    public function test_metode_harga_yang_tidak_dikenali_ditolak(): void
    {
        $sla = $this->sla();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $sla), [
                'file' => $this->fileWithHeader([
                    [1, 'Resin Metode Ngawur', 'Sunlu', 275000, 344, 1031, 1100, 11000, 'Standard Material', 'Kalkulator Ajaib'],
                ]),
            ])
            ->assertOk()
            ->assertSee('tidak dikenali');

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $sla), ['on_duplicate' => 'skip'])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertSame(0, $sla->materials()->where('material', 'Resin Metode Ngawur')->count());
    }

    /** Kalkulator Otomatis tetap menuntut harga: tanpa itu tidak ada yang dihitung. */
    public function test_material_otomatis_wajib_berharga(): void
    {
        $sla = $this->sla();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $sla), [
                'file' => $this->fileWithHeader([
                    [1, 'Resin Tanpa Harga', 'Sunlu', '', '', '', '', '', 'Standard Material', 'Kalkulator Otomatis'],
                ]),
            ])
            ->assertOk()
            ->assertSee('Harga Beli tidak boleh kosong.');

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $sla), ['on_duplicate' => 'skip'])
            ->assertRedirect();

        $this->assertSame(0, $sla->materials()->where('material', 'Resin Tanpa Harga')->count());
    }

    /**
     * Nama yang dipakai beberapa mesin tidak dapat ditebak.
     *
     * Kunci unik tabelnya (teknologi, mesin, material) mengizinkan satu nama
     * berdiri di beberapa mesin, sedangkan berkas Excel tidak menyebut mesin.
     * Menebak berarti mengubah harga material yang salah, jadi barisnya
     * ditolak — bukan diterapkan pada salah satunya.
     */
    public function test_nama_yang_dipakai_dua_mesin_ditolak(): void
    {
        $sla = $this->sla();

        // Dua mesin pada Machine Cost, satu-satunya sumber nama mesin.
        $machines = collect(['Resin Printer A', 'Resin Printer B'])->map(fn (string $nama) => MachineCost::create([
            'mesin' => $nama,
            'print_technology_id' => $sla->getKey(),
            'watt_kwh' => 0.35,
            'harga_listrik' => 1700,
            'depresiasi' => 5300,
        ]));

        foreach ($machines as $mesin) {
            $sla->materials()->create([
                'material' => 'Resin Dua Mesin',
                'brand' => 'Sunlu',
                'purchase_price' => 275000,
                'sale_price' => 1031,
                'machine_cost_id' => $mesin->id,
                'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
            ]);
        }

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $sla), [
                'file' => $this->fileWithHeader([
                    [1, 'Resin Dua Mesin', 'Sunlu', 999000, 1249, 3000, 3000, 30000, 'Diubah', 'Kalkulator Otomatis'],
                ]),
            ])
            ->assertOk()
            ->assertSee('ada pada lebih dari satu mesin');

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $sla), ['on_duplicate' => 'update'])
            ->assertRedirect();

        // Keduanya tidak tersentuh, dan tidak ada baris ketiga yang dibuat.
        $this->assertSame(2, $sla->materials()->where('material', 'Resin Dua Mesin')->count());

        foreach ($sla->materials()->where('material', 'Resin Dua Mesin')->get() as $material) {
            $this->assertSame(275000.0, (float) $material->purchase_price);
        }
    }

    /**
     * Berkas Contoh yang diunduh dapat diunggah kembali tanpa menggandakan apa
     * pun.
     *
     * Keenam resinnya memang material Price List SLA yang sungguhan, jadi
     * berkas ini sekaligus menjadi kasus terburuknya: seluruh barisnya sudah
     * ada. Yang benar adalah keenamnya dikenali sebagai "sudah ada" dan tidak
     * satu pun baris baru dibuat — bukan enam salinan kedua.
     */
    public function test_berkas_contoh_tidak_menggandakan_material_yang_sudah_ada(): void
    {
        $sla = $this->sla();

        $contents = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.excel.example', $sla))
            ->assertOk()
            ->getContent();

        $path = tempnam(sys_get_temp_dir(), 'contoh').'.xlsx';
        file_put_contents($path, $contents);

        $file = fn () => new UploadedFile(
            $path,
            'Contoh_SLA_Material.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $sebelum = $sla->materials()->count();

        // Contoh memang berisi material yang sudah terdaftar.
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $sla), ['file' => $file()])
            ->assertOk()
            ->assertSee('6 material sudah ada di Price List');

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $sla), ['on_duplicate' => 'skip'])
            ->assertRedirect();

        $this->assertSame($sebelum, $sla->materials()->count());
        $this->assertSame(1, $sla->materials()->where('material', 'Standard Resin Plus Sunlu')->count());

        // Update Existing pun tidak menambah baris, hanya menyamakan nilainya.
        $this->import($file(), 'update');

        $this->assertSame($sebelum, $sla->materials()->count());

        $material = $sla->materials()->where('material', 'Standard Resin Plus Sunlu')->firstOrFail();

        $this->assertSame(275000.0, (float) $material->purchase_price);
        $this->assertSame(PrintMaterial::PRICING_AUTOMATIC, $material->pricing_method);
        $this->assertSame(11000, $material->price_per_10_gram);
    }
}
