<?php

namespace Tests\Feature;

use App\Models\MachineCost;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PriceList\Excel\MaterialSheet;
use App\Support\AdminPermission;
use App\Support\SlaIndustries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import & Export Excel material Price List.
 *
 * Pelengkap CRUD material, bukan penggantinya. Yang dijaga pengujian ini:
 *
 *   - berkas yang diunduh berbentuk seperti yang dijanjikan, angkanya numerik
 *     dan berasal dari basis data;
 *   - berkas yang diunggah diperiksa SELURUHNYA lebih dulu, dan pratinjaunya
 *     tidak menyimpan apa pun;
 *   - baris bermasalah tidak pernah masuk basis data;
 *   - material yang sudah ada tidak pernah berganda — hanya dilewati atau
 *     diperbarui;
 *   - teknologi, mesin, dan metode harganya tidak ikut berubah;
 *   - harga turunan tetap mengikuti Pricing Engine, bukan angka di berkas.
 */
class MaterialExcelTest extends TestCase
{
    use RefreshDatabase;

    private ?User $superAdmin = null;

    private function superAdmin(): User
    {
        return $this->superAdmin ??= User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function fdm(): PrintTechnology
    {
        return PrintTechnology::where('code', 'FDM')->firstOrFail();
    }

    /**
     * Berkas .xlsx dari sederet baris; baris pertama menjadi judul kolom.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function xlsx(array $rows, string $name = 'FDM_Material.xlsx'): UploadedFile
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
    private function fileWithHeader(array $dataRows, ?PrintTechnology $technology = null): UploadedFile
    {
        return $this->xlsx([MaterialSheet::headings($technology ?? $this->fdm()), ...$dataRows]);
    }

    private function sla(): PrintTechnology
    {
        return PrintTechnology::where('code', SlaIndustries::CODE)->firstOrFail();
    }

    /** Baca kembali isi berkas unduhan sebagai tabel. */
    private function sheetOf(string $contents): array
    {
        $path = tempnam(sys_get_temp_dir(), 'dl').'.xlsx';
        file_put_contents($path, $contents);

        return IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
    }

    /* ============================================================ export === */

    public function test_export_mengambil_data_dari_basis_data(): void
    {
        $technology = $this->fdm();

        $material = $technology->materials()->create([
            'material' => 'Material Export Uji',
            'brand' => 'ESUN',
            'purchase_price' => 185000,
            'sale_price' => 463,
            'remark' => 'Standard Material',
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.excel.export', $technology))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertStringContainsString(
            'filename="FDM_Material_'.now()->format('Y-m-d').'.xlsx"',
            $response->headers->get('content-disposition'),
        );

        $rows = $this->sheetOf($response->getContent());

        $this->assertSame(MaterialSheet::headings($technology), $rows[0]);

        $line = collect($rows)->firstWhere(1, 'Material Export Uji');

        $this->assertNotNull($line);

        // Angka ditulis sebagai angka, bukan teks berawalan "Rp" — dan tiga
        // kolom turunannya persis hasil rumus Pricing Engine.
        $this->assertSame(185000.0, (float) $line[3]);
        $this->assertSame($material->price_per_gram, (int) $line[4]);
        $this->assertSame(463.0, (float) $line[5]);
        $this->assertSame($material->rounded_price, (int) $line[6]);
        $this->assertSame($material->price_per_10_gram, (int) $line[7]);
        $this->assertSame('Standard Material', $line[8]);
    }

    public function test_template_kosong_dan_contoh_terisi(): void
    {
        $technology = $this->fdm();
        $superAdmin = $this->superAdmin();

        $template = $this->actingAs($superAdmin)
            ->get(route('superadmin.price-list.materials.excel.template', $technology))
            ->assertOk();

        $this->assertStringContainsString('filename="Template_FDM_Material.xlsx"', $template->headers->get('content-disposition'));

        $rows = $this->sheetOf($template->getContent());

        $this->assertSame(MaterialSheet::headings($technology), $rows[0]);

        // Template TIDAK boleh membawa material nyata: baris contoh yang
        // tertinggal akan ikut terimpor sebagai material sungguhan.
        $this->assertNull(collect($rows)->skip(1)->first(fn (array $row) => trim((string) ($row[1] ?? '')) !== ''));

        $example = $this->actingAs($superAdmin)
            ->get(route('superadmin.price-list.materials.excel.example', $technology))
            ->assertOk();

        $this->assertStringContainsString('filename="Contoh_FDM_Material.xlsx"', $example->headers->get('content-disposition'));

        $exampleRows = $this->sheetOf($example->getContent());

        $this->assertSame(MaterialSheet::headings($technology), $exampleRows[0]);
        $this->assertSame('PLA Plus Standart ESUN', $exampleRows[1][1]);
        $this->assertSame(185000.0, (float) $exampleRows[1][3]);
        $this->assertSame(231.0, (float) $exampleRows[1][4]);
        $this->assertSame(500.0, (float) $exampleRows[1][6]);
        $this->assertSame(5000.0, (float) $exampleRows[1][7]);
    }

    /* ========================================================= pratinjau === */

    /**
     * Pratinjau membaca dan memeriksa, tetapi TIDAK menyimpan apa pun —
     * termasuk ketika seluruh barisnya sah.
     */
    public function test_pratinjau_tidak_menyimpan_apa_pun(): void
    {
        $technology = $this->fdm();
        $sebelum = $technology->materials()->count();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), [
                'file' => $this->fileWithHeader([
                    [1, 'Material Pratinjau', 'ESUN', 185000, 231, 463, 500, 5000, 'Standard Material'],
                ]),
            ])
            ->assertOk()
            ->assertSee('Material Pratinjau')
            ->assertSee('Preview Data');

        $this->assertSame($sebelum, $technology->materials()->count());
    }

    public function test_pratinjau_melaporkan_tiap_baris_bermasalah(): void
    {
        $technology = $this->fdm();

        $technology->materials()->create([
            'material' => 'Material Sudah Ada',
            'brand' => 'ESUN',
            'purchase_price' => 200000,
            'sale_price' => 750,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), [
                'file' => $this->fileWithHeader([
                    [1, 'Material Sah', 'ESUN', 185000, 231, 463, 500, 5000, 'Standard Material'],
                    [2, '', 'ESUN', 100000, 125, 500, 500, 5000, 'Tanpa nama'],
                    [3, 'Harga Rusak', 'ESUN', 'abc', 0, 500, 500, 5000, 'Bukan angka'],
                    [4, 'Harga Negatif', 'ESUN', -5000, 0, 500, 500, 5000, 'Negatif'],
                    [5, 'Tanpa Brand', '', 120000, 150, 400, 400, 4000, 'Brand kosong'],
                    [6, 'Material Sudah Ada', 'ESUN', 250000, 313, 900, 900, 9000, 'Kembar dengan basis data'],
                ]),
            ])
            ->assertOk();

        $response->assertSee('Material tidak boleh kosong.');
        $response->assertSee('Harga Beli tidak valid: "abc" bukan angka.');
        $response->assertSee('Harga Beli tidak boleh negatif.');
        $response->assertSee('Brand tidak boleh kosong.');

        // Yang kembar dengan basis data BUKAN kesalahan — menunggu keputusan.
        $response->assertSee('Sudah Ada');
        $response->assertSee('Update data existing');
    }

    public function test_header_yang_salah_ditolak_sebelum_dibaca(): void
    {
        $technology = $this->fdm();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), [
                'file' => $this->xlsx([
                    ['Nama', 'Merek', 'Harga'],
                    ['PLA', 'ESUN', 185000],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('file');

        $this->assertSame(0, $technology->materials()->where('material', 'PLA')->count());
    }

    public function test_hanya_xlsx_yang_diterima(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $this->fdm()), [
                'file' => UploadedFile::fake()->create('material.csv', 10, 'text/csv'),
            ])
            ->assertSessionHasErrors('file');
    }

    /* ============================================================ import === */

    public function test_import_menambah_material_baru(): void
    {
        $technology = $this->fdm();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), [
                'file' => $this->fileWithHeader([
                    [1, 'Material Import Baru', 'ESUN', 185000, 231, 463, 500, 5000, 'Standard Material'],
                ]),
            ])
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $technology), ['on_duplicate' => 'skip'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $material = $technology->materials()->where('material', 'Material Import Baru')->firstOrFail();

        $this->assertSame(185000.0, (float) $material->purchase_price);
        $this->assertSame(463.0, (float) $material->sale_price);
        $this->assertSame('Standard Material', $material->remark);

        // Relasi teknologi tetap terjaga, dan harga turunannya tetap dari rumus.
        $this->assertSame($technology->getKey(), $material->print_technology_id);
        $this->assertSame(231, $material->price_per_gram);
        $this->assertSame(500, $material->rounded_price);
        $this->assertSame(5000, $material->price_per_10_gram);
    }

    public function test_baris_bermasalah_tidak_pernah_masuk_basis_data(): void
    {
        $technology = $this->fdm();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), [
                'file' => $this->fileWithHeader([
                    [1, 'Baris Sah', 'ESUN', 185000, 231, 463, 500, 5000, 'Standard Material'],
                    [2, 'Baris Rusak', 'ESUN', 'abc', 0, 500, 500, 5000, 'Bukan angka'],
                    [3, 'Baris Negatif', 'ESUN', -1, 0, 500, 500, 5000, 'Negatif'],
                ]),
            ])
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $technology), ['on_duplicate' => 'skip'])
            ->assertRedirect();

        $this->assertSame(1, $technology->materials()->where('material', 'Baris Sah')->count());
        $this->assertSame(0, $technology->materials()->where('material', 'Baris Rusak')->count());
        $this->assertSame(0, $technology->materials()->where('material', 'Baris Negatif')->count());
    }

    /**
     * Material yang sudah ada tidak pernah berganda.
     *
     * Yang menentukan adalah nama material pada teknologi itu — bukan kolom
     * "No" di berkas, yang memang hanya nomor urut.
     */
    public function test_material_kembar_dilewati_atau_diperbarui(): void
    {
        $technology = $this->fdm();
        $machine = MachineCost::first();

        $material = $technology->materials()->create([
            'material' => 'Material Kembar',
            'brand' => 'ESUN',
            'purchase_price' => 185000,
            'sale_price' => 463,
            'remark' => 'Lama',
            'machine_cost_id' => $machine?->id,
            'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
        ]);

        $file = fn () => $this->fileWithHeader([
            [1, 'Material Kembar', 'ESUN', 190000, 238, 500, 500, 5000, 'Baru'],
        ]);

        /* ---------------------------------------------------------- skip */
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), ['file' => $file()])
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $technology), ['on_duplicate' => 'skip'])
            ->assertRedirect();

        $material->refresh();

        $this->assertSame(1, $technology->materials()->where('material', 'Material Kembar')->count());
        $this->assertSame(185000.0, (float) $material->purchase_price);
        $this->assertSame('Lama', $material->remark);

        /* -------------------------------------------------------- update */
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), ['file' => $file()])
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $technology), ['on_duplicate' => 'update'])
            ->assertRedirect();

        $material->refresh();

        $this->assertSame(1, $technology->materials()->where('material', 'Material Kembar')->count());
        $this->assertSame(190000.0, (float) $material->purchase_price);
        $this->assertSame('Baru', $material->remark);

        // Mesin, teknologi, dan metode harganya TIDAK ikut berubah: Excel
        // hanya media pertukaran data komersial.
        $this->assertSame($machine?->id, $material->machine_cost_id);
        $this->assertSame($technology->getKey(), $material->print_technology_id);
        $this->assertSame(PrintMaterial::PRICING_AUTOMATIC, $material->pricing_method);
    }

    /**
     * Angka turunan yang keliru di berkas tidak pernah tersimpan.
     *
     * Yang disimpan hanya Harga Beli dan Harga Jual; Harga per gram,
     * Pembulatan Harga, dan Harga/10 gram selalu dihitung ulang, jadi Import
     * tidak dapat membuat harga yang tidak konsisten dengan Calculator.
     */
    public function test_kolom_turunan_selalu_mengikuti_pricing_engine(): void
    {
        $technology = $this->fdm();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), [
                'file' => $this->fileWithHeader([
                    // Tiga kolom terakhir sengaja diisi angka yang salah.
                    [1, 'Material Turunan', 'ESUN', 185000, 999, 463, 111, 222, 'Standard Material'],
                ]),
            ])
            ->assertOk()
            ->assertSee('yang dipakai hasil rumus.', false);

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $technology), ['on_duplicate' => 'skip'])
            ->assertRedirect();

        $material = $technology->materials()->where('material', 'Material Turunan')->firstOrFail();

        $this->assertSame(231, $material->price_per_gram);
        $this->assertSame(500, $material->rounded_price);
        $this->assertSame(5000, $material->price_per_10_gram);
    }

    /** Menyimpan tanpa pratinjau tidak mungkin: tidak ada yang tersimpan di sesi. */
    public function test_import_tanpa_pratinjau_ditolak(): void
    {
        $technology = $this->fdm();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $technology), ['on_duplicate' => 'skip'])
            ->assertRedirect()
            ->assertSessionHasErrors('file');
    }

    public function test_membatalkan_import_tidak_mengubah_apa_pun(): void
    {
        $technology = $this->fdm();
        $sebelum = $technology->materials()->count();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), [
                'file' => $this->fileWithHeader([
                    [1, 'Material Dibatalkan', 'ESUN', 185000, 231, 463, 500, 5000, 'Standard Material'],
                ]),
            ])
            ->assertOk();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.cancel', $technology))
            ->assertRedirect();

        $this->assertSame($sebelum, $technology->materials()->count());

        // Pratinjaunya sudah dilupakan, jadi Import tidak dapat menyusul.
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.excel.import', $technology), ['on_duplicate' => 'skip'])
            ->assertSessionHasErrors('file');
    }

    /* ======================================================== halaman === */

    public function test_tombol_excel_tampil_di_halaman_material(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technology', ['slug' => 'fdm']))
            ->assertOk()
            ->assertSee('Import Excel')
            ->assertSee('Export Excel')
            ->assertSee('Template Excel')
            ->assertSee('Contoh Excel')
            // CRUD yang sudah ada tetap pada tempatnya.
            ->assertSee('Tambah Material FDM');
    }

    /* ======================================================= hak akses === */

    /**
     * Hak akses diperiksa di backend, bukan sekadar menyembunyikan tombol.
     *
     * Dua lapis, dan keduanya benar-benar ada:
     *
     *   1. seluruh Price List berada di wilayah Superadmin, jadi Admin
     *      dikembalikan ke dashboardnya sendiri (lihat
     *      App\Http\Middleware\EnsureUserIsSuperAdmin);
     *   2. route-nya masih menuntut `price_list.export` / `price_list.import`,
     *      sehingga pemeriksaannya sudah pada tempatnya bila menu ini suatu
     *      saat dibuka juga untuk Admin.
     */
    public function test_admin_tidak_dapat_import_export(): void
    {
        $admin = User::factory()
            ->withPermissions([AdminPermission::PROFILE_EDIT])
            ->create(['email' => 'tanpa-excel@nusama3d.com']);

        $technology = $this->fdm();

        $this->actingAs($admin)
            ->get(route('superadmin.price-list.materials.excel.export', $technology))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->post(route('superadmin.price-list.materials.excel.preview', $technology), [
                'file' => $this->fileWithHeader([[1, 'X', 'ESUN', 1000, 2, 500, 500, 5000, '']]),
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, $technology->materials()->where('material', 'X')->count());
    }

    /** Hak aksesnya sendiri memang menjaga route-nya, bukan sekadar terdaftar. */
    public function test_hak_akses_price_list_menjaga_route_nya(): void
    {
        $this->assertContains(AdminPermission::PRICE_LIST_EXPORT, AdminPermission::keys());
        $this->assertContains(AdminPermission::PRICE_LIST_IMPORT, AdminPermission::keys());

        foreach ([
            'price-list.materials.excel.export' => AdminPermission::PRICE_LIST_EXPORT,
            'price-list.materials.excel.template' => AdminPermission::PRICE_LIST_EXPORT,
            'price-list.materials.excel.example' => AdminPermission::PRICE_LIST_EXPORT,
            'price-list.materials.excel.preview' => AdminPermission::PRICE_LIST_IMPORT,
            'price-list.materials.excel.import' => AdminPermission::PRICE_LIST_IMPORT,
            'price-list.materials.excel.cancel' => AdminPermission::PRICE_LIST_IMPORT,
        ] as $name => $permission) {
            $middleware = Route::getRoutes()
                ->getByName('superadmin.'.$name)
                ->gatherMiddleware();

            $this->assertContains('admin.permission:'.$permission, $middleware, $name);
            $this->assertContains('superadmin', $middleware, $name);
        }
    }

    public function test_tamu_ditolak(): void
    {
        $this->get(route('superadmin.price-list.materials.excel.export', $this->fdm()))
            ->assertRedirect(route('admin.login'));
    }
}
