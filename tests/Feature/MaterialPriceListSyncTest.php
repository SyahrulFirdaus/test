<?php

namespace Tests\Feature;

use App\Models\FdmMaterial;
use App\Models\SlaMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
use App\Support\Printer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Price List sebagai satu-satunya sumber material FDM & SLA.
 *
 * Yang dijaga di sini adalah RANTAINYA, bukan tampilannya:
 *
 *     Price List → Technology → Material → Harga → Edit Specification
 *                                                → Perhitungan Quotation
 *
 * Menambah baris di Price List harus langsung menambah pilihan, menghapusnya
 * harus langsung menghilangkan pilihan, dan mengubah harganya harus langsung
 * dipakai perhitungan berikutnya — semuanya tanpa satu baris kode pun berubah.
 */
class MaterialPriceListSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /** @return array<int, string> nama material yang ditawarkan ke pelanggan */
    private function offered(string $technology): array
    {
        return array_column(app(PrintEstimator::class)->browserPayload()[$technology]['materials'], 'name');
    }

    /* ------------------------------------------------- tambah material --- */

    public function test_material_fdm_baru_langsung_muncul_di_edit_specification(): void
    {
        $this->assertNotContains('PLA Galaxy', $this->offered('FDM'));

        FdmMaterial::create([
            'material' => 'PLA Galaxy',
            'brand' => 'ESUN',
            'purchase_price' => 240000,
            'sale_price' => 900,
            'remark' => 'Standard Material',
        ]);

        $this->assertContains('PLA Galaxy', $this->offered('FDM'));
    }

    public function test_material_sla_baru_langsung_muncul_di_edit_specification(): void
    {
        SlaMaterial::create([
            'material' => 'Flexible Resin',
            'brand' => 'Sunlu',
            'purchase_price' => 500000,
            'sale_price' => 1875,
            'remark' => 'Engineering Material',
        ]);

        $this->assertContains('Flexible Resin', $this->offered(SlaMaterial::TECHNOLOGY));
    }

    /* ------------------------------------------------- hapus material --- */

    public function test_material_fdm_yang_dihapus_langsung_hilang(): void
    {
        $material = FdmMaterial::query()->orderBy('material')->first();

        $this->assertContains($material->material, $this->offered('FDM'));

        $material->delete();

        $this->assertNotContains($material->material, $this->offered('FDM'));
    }

    public function test_material_sla_yang_dihapus_langsung_hilang(): void
    {
        $material = SlaMaterial::query()->orderBy('material')->first();

        $material->delete();

        $this->assertNotContains($material->material, $this->offered(SlaMaterial::TECHNOLOGY));
    }

    /** Penghapusan massal pun ikut, karena sumbernya tabel yang sama. */
    public function test_penghapusan_massal_ikut_menghilangkan_pilihannya(): void
    {
        $dihapus = FdmMaterial::query()->orderBy('id')->take(3)->get();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('FDM')), ['ids' => $dihapus->modelKeys()]);

        foreach ($dihapus as $material) {
            $this->assertNotContains($material->material, $this->offered('FDM'));
        }
    }

    /* ------------------------------------------------- relasi teknologi --- */

    public function test_material_fdm_tidak_pernah_muncul_pada_sla(): void
    {
        $fdm = $this->offered('FDM');
        $sla = $this->offered(SlaMaterial::TECHNOLOGY);

        $this->assertNotEmpty($fdm);
        $this->assertNotEmpty($sla);
        $this->assertSame([], array_intersect($fdm, $sla));

        // Daftarnya benar-benar berasal dari tabel masing-masing.
        $this->assertSame(FdmMaterial::orderBy('material')->pluck('material')->all(), $fdm);
        $this->assertSame(SlaMaterial::orderBy('material')->pluck('material')->all(), $sla);
    }

    public function test_material_baru_hanya_masuk_ke_teknologinya_sendiri(): void
    {
        FdmMaterial::create([
            'material' => 'Material Uji FDM',
            'brand' => 'ESUN',
            'purchase_price' => 200000,
            'sale_price' => 700,
        ]);

        $this->assertContains('Material Uji FDM', $this->offered('FDM'));
        $this->assertNotContains('Material Uji FDM', $this->offered(SlaMaterial::TECHNOLOGY));
    }

    /* ------------------------------------------------------------ harga --- */

    public function test_harga_material_dibaca_dari_price_list(): void
    {
        $material = FdmMaterial::create([
            'material' => 'PLA Uji Harga',
            'brand' => 'ESUN',
            'purchase_price' => 200000,
            'sale_price' => 2310,
        ]);

        // Harga yang dikirim ke browser sama dengan yang dihitung baris itu.
        $payload = collect(app(PrintEstimator::class)->browserPayload()['FDM']['materials'])
            ->firstWhere('name', 'PLA Uji Harga');

        $this->assertSame($material->rounded_price, $payload['pricePerGram']);
    }

    /**
     * Mengubah harga di Price List langsung dipakai penawaran berikutnya —
     * inti dari "tanpa perlu mengubah kode program".
     */
    public function test_perubahan_harga_langsung_dipakai_perhitungan_berikutnya(): void
    {
        $material = FdmMaterial::create([
            'material' => 'PLA Uji Perubahan',
            'brand' => 'ESUN',
            'purchase_price' => 200000,
            'sale_price' => 500,
        ]);

        $context = [
            'technology' => 'FDM',
            'material' => 'PLA Uji Perubahan',
            'printer_name' => Printer::name(null),
            'quantity' => 1,
            'total_weight_g' => 100,
            'minutes' => 60,
            'dimensions' => ['x' => 50, 'y' => 40, 'z' => 30],
        ];

        $sebelum = app(SellingPriceEstimator::class)->calculate($context);

        $this->assertSame((float) $material->rounded_price, $sebelum['material_price_per_g']);
        $this->assertSame(round(100 * $material->rounded_price, 2), $sebelum['material_cost']);

        // Superadmin menaikkan harga jualnya.
        $material->update(['sale_price' => 1500]);

        // Estimator baru: katalognya dibaca ulang dari basis data.
        $sesudah = app()->make(SellingPriceEstimator::class)->calculate($context);

        $this->assertSame((float) $material->fresh()->rounded_price, $sesudah['material_price_per_g']);
        $this->assertGreaterThan($sebelum['material_cost'], $sesudah['material_cost']);
        $this->assertGreaterThan($sebelum['selling_price'], $sesudah['selling_price']);
    }

    /* ----------------------------------------------- halaman pelanggan --- */

    public function test_halaman_ubah_spesifikasi_menawarkan_seluruh_baris_price_list(): void
    {
        FdmMaterial::create([
            'material' => 'PLA Terbaru',
            'brand' => 'ESUN',
            'purchase_price' => 200000,
            'sale_price' => 700,
        ]);

        $quotation = $this->penawaran();

        $response = $this->actingAs($quotation->user)
            ->get(route('dashboard.quotations.edit', $quotation))
            ->assertOk();

        preg_match('/<select[^>]*data-material-select.*?<\/select>/s', $response->getContent(), $select);
        preg_match_all('/<option[^>]*data-technology="FDM"[^>]*>\s*(.*?)\s*<\/option>/s', $select[0] ?? '', $options);

        $this->assertSame(FdmMaterial::orderBy('material')->pluck('material')->all(), $options[1]);
        $this->assertContains('PLA Terbaru', $options[1]);
    }

    private function penawaran(): \App\Models\QuotationRequest
    {
        $user = User::factory()->create();

        $quotation = \App\Models\QuotationRequest::create([
            'user_id' => $user->id,
            'tracking_number' => 'QT-SYNC01',
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '081211112222',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => \App\Models\QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => FdmMaterial::orderBy('material')->first()->material,
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
            'estimated_price' => 300000,
            'status' => \App\Support\QuotationStatus::REVIEWING,
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => \App\Models\QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => FdmMaterial::orderBy('material')->first()->material,
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 1,
            'scale_percent' => 100,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'infill_density' => 0.2,
            'model_volume_cm3' => 120,
            'estimated_weight_g' => 100,
            'support_weight_g' => 0,
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
        ]);

        return $quotation->fresh()->load('items');
    }

    /* ------------------------------------------------------- MJF & SLM --- */

    /**
     * MJF dan SLM belum punya tab Price List, jadi daftarnya masih dikelola
     * config — dan kurasinya di sana tetap berlaku.
     */
    public function test_mjf_dan_slm_masih_mengikuti_config(): void
    {
        $this->assertSame(['PA12'], $this->offered('MJF'));
        $this->assertSame(['Stainless Steel', 'Titanium'], $this->offered('SLM'));
    }
}
