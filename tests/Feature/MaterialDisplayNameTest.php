<?php

namespace Tests\Feature;

use App\Models\FdmMaterial;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Support\MaterialCatalog;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Nama material yang dilihat pelanggan.
 *
 * Ada DUA sumber, dan keduanya berlaku bersamaan:
 *
 * 1. FDM & SLA — dikelola Superadmin lewat Price List. Nama yang tampil adalah
 *    kolom `material` pada barisnya, apa adanya; tidak ada peta penamaan yang
 *    menengahi, karena peta seperti itu akan membuat material baru pada Price
 *    List tidak pernah muncul. Sinkronisasinya diuji di
 *    Tests\Feature\MaterialPriceListSyncTest.
 *
 * 2. MJF & SLM — belum punya tab Price List, jadi daftarnya masih di
 *    config/printing.php. Di sinilah peta `material_display` masih bekerja:
 *    mengurasi mana yang ditawarkan sekaligus memberinya nama yang dibaca
 *    pelanggan.
 */
class MaterialDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /* ------------------------------------------- dikelola config (MJF/SLM) --- */

    public function test_mjf_dan_slm_memakai_nama_tampilan_dari_peta(): void
    {
        $payload = app(PrintEstimator::class)->browserPayload();

        $labels = fn (string $code) => array_column($payload[$code]['materials'], 'label');

        $this->assertSame(['PA 12 Nylon'], $labels('MJF'));
        $this->assertSame(['Stainless BJ 316L', 'Titanium TC4 Metal'], $labels('SLM'));
    }

    public function test_nilai_yang_dikirim_tetap_nama_katalog(): void
    {
        $payload = app(PrintEstimator::class)->browserPayload();

        // Yang dikirim balik ke server tetap nama katalognya; hanya labelnya
        // yang berbeda, sehingga harga dan penawaran lama tidak tergeser.
        $this->assertSame(['PA12'], array_column($payload['MJF']['materials'], 'name'));
        $this->assertSame(['Stainless Steel', 'Titanium'], array_column($payload['SLM']['materials'], 'name'));
    }

    public function test_material_config_yang_tidak_ditawarkan_tetap_ada_untuk_internal(): void
    {
        $estimator = app(PrintEstimator::class);

        $offered = array_column($estimator->browserPayload()['SLM']['materials'], 'name');
        $this->assertNotContains('Aluminum', $offered);

        // Katalognya utuh, jadi penawaran lama yang memakainya tetap terhitung.
        $this->assertNotNull($estimator->material('SLM', 'Aluminum'));
        $this->assertTrue($estimator->supports('MJF', 'PA11'));
    }

    /* ----------------------------------------- dikelola Price List (FDM/SLA) --- */

    /**
     * Tidak ada peta penamaan untuk FDM/SLA — dengan sengaja.
     *
     * Kalau ada, menambah material di Price List tidak akan pernah muncul di
     * Edit Specification, dan itu persis yang ingin dihindari.
     */
    public function test_fdm_dan_sla_tidak_memakai_peta_penamaan(): void
    {
        $this->assertSame([], MaterialCatalog::displayMap('FDM'));
        $this->assertSame([], MaterialCatalog::displayMap('SLA'));

        $material = FdmMaterial::query()->orderBy('material')->first();
        $this->assertSame($material->material, MaterialCatalog::displayName('FDM', $material->material));
    }

    public function test_label_fdm_sama_dengan_nama_pada_price_list(): void
    {
        $payload = app(PrintEstimator::class)->browserPayload()['FDM']['materials'];

        foreach ($payload as $material) {
            $this->assertSame($material['name'], $material['label']);
        }

        $this->assertSame(
            FdmMaterial::orderBy('material')->pluck('material')->all(),
            array_column($payload, 'label'),
        );
    }

    /* ------------------------------------------------ penawaran tersimpan --- */

    public function test_material_label_pada_model(): void
    {
        $item = $this->penawaran()->items->first();

        // Nama pada penawaran dibaca apa adanya dari yang tersimpan.
        $this->assertSame('PLA Plus Standart ESUN', $item->material);
        $this->assertSame('PLA Plus Standart ESUN', $item->material_label);
        $this->assertInstanceOf(QuotationItem::class, $item);
    }

    /**
     * Penawaran lama tetap terbaca meski materialnya sudah dihapus Superadmin
     * dari Price List — namanya dibekukan pada penawarannya sendiri.
     */
    public function test_material_yang_sudah_dihapus_tetap_terbaca_pada_penawaran_lama(): void
    {
        $quotation = $this->penawaran();

        FdmMaterial::where('material', 'PLA Plus Standart ESUN')->delete();

        $item = $quotation->fresh()->load('items')->items->first();

        $this->assertSame('PLA Plus Standart ESUN', $item->material_label);

        // Halaman pelanggan hanya menampilkan volume model; nama material
        // tetap terbaca pada detail penawaran di dashboard admin.
        $this->actingAs(\App\Models\User::factory()->admin()->create())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('PLA Plus Standart ESUN');
    }

    private function penawaran(): QuotationRequest
    {
        $user = User::factory()->create([
            'name' => 'Rani Prameswari',
            'email' => 'rani@contoh.test',
            'phone' => '081211112222',
        ]);

        $quotation = QuotationRequest::create([
            'user_id' => $user->id,
            'tracking_number' => 'QT-MAT001',
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => (string) $user->phone,
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
            'estimated_price' => 300000,
            'status' => QuotationStatus::REVIEWING,
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['dimensions' => ['x' => 100, 'y' => 80, 'z' => 50]],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
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
}
