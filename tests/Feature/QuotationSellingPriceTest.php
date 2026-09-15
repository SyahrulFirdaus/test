<?php

namespace Tests\Feature;

use App\Models\MachineCost;
use App\Models\PackagingItem;
use App\Models\PricingFormula;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\SellingPriceEstimator;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Rincian Harga Jual penawaran: perhitungannya, sumber parameternya, dan
 * batasan bahwa rinciannya hanya boleh terlihat admin.
 */
class QuotationSellingPriceTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->customer = User::factory()->create(['name' => 'David Kurniawan']);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create([
            'email' => 'admin@nusama3d.com',
            'password' => Hash::make('rahasia123'),
        ]);
    }

    private function quotation(array $overrides = []): QuotationRequest
    {
        return QuotationRequest::create(array_merge([
            'user_id' => $this->customer->id,
            'tracking_number' => 'QT-'.strtoupper(fake()->bothify('####')),
            'name' => 'David Kurniawan',
            'email' => 'david@contoh.test',
            'whatsapp' => '081234567890',
            'quantity' => 2,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['triangles' => 12, 'vertices' => 36],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'model_volume_cm3' => 120,
            'material_volume_cm3' => 54,
            'estimated_weight_g' => 100,
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
            'estimated_price' => 300000,
            'status' => QuotationStatus::REVIEWING,
        ], $overrides));
    }

    private function addItem(QuotationRequest $quotation, array $overrides = []): QuotationItem
    {
        return $quotation->items()->create(array_merge([
            'position' => $quotation->items()->count() + 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['triangles' => 12, 'vertices' => 36],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 2,
            'scale_percent' => 100,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'infill_density' => 0.2,
            'infill_pattern' => 'grid',
            'model_volume_cm3' => 120,
            'material_volume_cm3' => 54,
            'estimated_weight_g' => 100,
            'support_weight_g' => 0,
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
        ], $overrides));
    }

    private function estimator(): SellingPriceEstimator
    {
        return app(SellingPriceEstimator::class);
    }

    /* ------------------------------------------------------ perhitungan --- */

    public function test_harga_jual_dihitung_dari_parameter_price_list(): void
    {
        $quotation = $this->quotation();
        $item = $this->addItem($quotation);

        $calculation = $this->estimator()->forItem($item);

        // Machine Cost jatuh ke parameter FDM karena tabel Machine Cost kosong:
        // 2 jam x Rp61.000.
        $this->assertSame(2.0, $calculation['machine_time_hours']);
        $this->assertSame(61000.0, $calculation['machine_cost']);
        $this->assertSame(122000.0, $calculation['machine_operational_cost']);

        // Harga material mengikuti Price List FDM: PLA Basic ESUN dijual 563,
        // dibulatkan ke Rp600/gram. 100 gram x 2 unit.
        $this->assertSame(200.0, $calculation['material_qty_g']);
        $this->assertSame(600.0, $calculation['material_price_per_g']);
        $this->assertSame(120000.0, $calculation['material_cost']);

        $this->assertSame(242000.0, $calculation['hpp']);
        $this->assertSame(72600.0, $calculation['risk_cost']);          // 30%
        $this->assertSame(314600.0, $calculation['subtotal_hpp_risk']);
        $this->assertSame(10000.0, $calculation['packaging']);          // Rp5.000 x 2 unit
        $this->assertSame(0.0, $calculation['overtime']);
        $this->assertSame(324600.0, $calculation['subtotal']);
        $this->assertSame(162300.0, $calculation['profit']);            // 50%
        $this->assertSame(486900.0, $calculation['selling_price']);
    }

    public function test_mengubah_parameter_price_list_mengubah_harga_jual(): void
    {
        $quotation = $this->quotation();
        $item = $this->addItem($quotation);

        PricingFormula::where('technology', 'FDM')->update(['profit_percent' => 100]);

        $calculation = $this->estimator()->forItem($item);

        $this->assertSame(324600.0, $calculation['profit']);
        $this->assertSame(649200.0, $calculation['selling_price']);
    }

    public function test_machine_cost_memakai_baris_price_list_yang_cocok_dengan_printer(): void
    {
        MachineCost::create([
            'mesin' => 'Ender 3 V2',
            'watt_kwh' => 0.25,
            'harga_listrik' => 1700,
            'depresiasi' => 2100,
        ]);

        $quotation = $this->quotation();
        $item = $this->addItem($quotation);

        $calculation = $this->estimator()->forItem($item);

        // (0,25 x 1700 + 2100) x 1,5 = 3.788, dibulatkan ke atas jadi Rp4.000/jam.
        $this->assertSame('Ender 3 V2', $calculation['machine_source']);
        $this->assertSame(4000.0, $calculation['machine_cost']);
        $this->assertSame(8000.0, $calculation['machine_operational_cost']);
    }

    public function test_mesin_yang_tidak_terdaftar_memakai_machine_cost_parameter_teknologinya(): void
    {
        MachineCost::create([
            'mesin' => 'Elegoo Saturn 4 12 K',
            'watt_kwh' => 0.144,
            'harga_listrik' => 1700,
            'depresiasi' => 5100,
        ]);

        $quotation = $this->quotation();
        $item = $this->addItem($quotation, ['printer' => 'prusa_mk4', 'printer_name' => 'Prusa MK4']);

        $calculation = $this->estimator()->forItem($item);

        $this->assertNull($calculation['machine_source']);
        $this->assertSame(61000.0, $calculation['machine_cost']);
    }

    public function test_packaging_memakai_kardus_terkecil_yang_memuat_model(): void
    {
        PackagingItem::create(['item' => 'Kardus', 'ukuran' => 'XS', 'dimensi' => '10 x 10 x 5 cm', 'price' => 2000, 'price_unit' => PackagingItem::UNIT_FLAT]);
        PackagingItem::create(['item' => 'Kardus', 'ukuran' => 'M', 'dimensi' => '30 x 15 x 15 cm', 'price' => 5000, 'price_unit' => PackagingItem::UNIT_FLAT]);
        PackagingItem::create(['item' => 'Foam', 'ukuran' => '-', 'dimensi' => 'Tebal 5 cm', 'price' => 2000, 'price_unit' => PackagingItem::UNIT_PER_CM]);

        $quotation = $this->quotation();

        $kecil = $this->addItem($quotation, [
            'model_stats' => ['dimensions' => ['x' => 100, 'y' => 80, 'z' => 40]],
        ]);

        $besar = $this->addItem($quotation, [
            'model_stats' => ['dimensions' => ['x' => 200, 'y' => 120, 'z' => 60]],
        ]);

        $this->assertSame('Kardus XS', $this->estimator()->forItem($kecil)['packaging_source']);
        $this->assertSame(4000.0, $this->estimator()->forItem($kecil)['packaging']);

        $this->assertSame('Kardus M', $this->estimator()->forItem($besar)['packaging_source']);
        $this->assertSame(10000.0, $this->estimator()->forItem($besar)['packaging']);
    }

    public function test_skala_tidak_dikalikan_lagi_ke_dimensi_yang_tersimpan(): void
    {
        PackagingItem::create(['item' => 'Kardus', 'ukuran' => 'XS', 'dimensi' => '10 x 10 x 5 cm', 'price' => 2000, 'price_unit' => PackagingItem::UNIT_FLAT]);
        PackagingItem::create(['item' => 'Kardus', 'ukuran' => 'M', 'dimensi' => '30 x 15 x 15 cm', 'price' => 5000, 'price_unit' => PackagingItem::UNIT_FLAT]);

        $quotation = $this->quotation();

        // Dimensi pada `model_stats` diukur browser dari model yang sudah
        // diskalakan, jadi `scale_percent` tidak boleh dikalikan lagi: model
        // 100 x 80 x 40 mm tetap muat pada kardus terkecil meski skalanya 200%.
        $item = $this->addItem($quotation, [
            'scale_percent' => 200,
            'model_stats' => ['dimensions' => ['x' => 100, 'y' => 80, 'z' => 40]],
        ]);

        $this->assertSame('Kardus XS', $this->estimator()->forItem($item)['packaging_source']);
    }

    public function test_setiap_teknologi_memakai_parameternya_sendiri(): void
    {
        $quotation = $this->quotation(['technology' => 'SLA', 'material' => 'Standard Resin']);

        $item = $this->addItem($quotation, [
            'technology' => 'SLA',
            'material' => 'Standard Resin',
        ]);

        $calculation = $this->estimator()->forItem($item);

        // Parameter SLA: machine cost 30.000/jam, risk 25%, profit 50%.
        $this->assertSame('SLA', $calculation['technology']);
        $this->assertSame(30000.0, $calculation['machine_cost']);
        $this->assertSame(25.0, $calculation['risk_percent']);
        $this->assertSame(60000.0, $calculation['machine_operational_cost']);
    }

    public function test_penawaran_banyak_model_dijumlahkan_per_teknologi(): void
    {
        $quotation = $this->quotation();

        $this->addItem($quotation);
        $this->addItem($quotation);
        $this->addItem($quotation, ['technology' => 'SLA', 'material' => 'Standard Resin']);

        $result = $this->estimator()->forQuotation($quotation->fresh()->load('items'));

        $this->assertCount(2, $result['groups']);
        $this->assertSame(['FDM', 'SLA'], $result['groups']->pluck('technology')->all());

        $fdm = $result['groups']->firstWhere('technology', 'FDM');
        $sla = $result['groups']->firstWhere('technology', 'SLA');

        // Dua model FDM yang identik: tepat dua kali harga jual satu model.
        $this->assertSame(973800.0, $fdm['totals']['selling_price']);
        $this->assertSame(
            round($fdm['totals']['selling_price'] + $sla['totals']['selling_price'], 2),
            $result['selling_price'],
        );

        // Rincian gabungan tetap berjumlah benar: Subtotal + Profit = Harga Jual.
        $totals = $result['totals'];
        $this->assertSame($totals['selling_price'], round($totals['subtotal'] + $totals['profit'], 2));
    }

    public function test_rincian_harga_jual_tidak_mengubah_harga_penawaran_pelanggan(): void
    {
        $quotation = $this->quotation();
        $this->addItem($quotation);

        $this->estimator()->forQuotation($quotation->load('items'));

        $this->assertSame(300000.0, (float) $quotation->fresh()->estimated_price);
        $this->assertSame(300000.0, (float) $quotation->fresh()->estimated_cost);
    }

    /* ------------------------------- harga tersimpan & halaman admin --- */

    /** Kirim permintaan penawaran lewat alur sungguhan, lalu kembalikan hasilnya. */
    private function submit(array $models): QuotationRequest
    {
        $this->actingAs($this->customer);

        $this->postJson(route('quotations.store'), [
            'name' => 'David Kurniawan',
            'email' => 'david@contoh.test',
            'whatsapp' => '0812 3456 7890',
            'items' => collect($models)->map(fn (array $model) => [
                'model' => UploadedFile::fake()->createWithContent($model['file'], 'solid test'),
                'quantity' => $model['quantity'] ?? 1,
                'technology' => 'FDM',
                'material' => 'PLA Plus Standart ESUN',
                'model_volume_cm3' => $model['volume'] ?? 100,
                'analysis_status' => QuotationRequest::ANALYSIS_READY,
                'analysis' => json_encode([['id' => 'watertight', 'label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.']]),
                'model_stats' => json_encode([
                    'vertices' => 36,
                    'triangles' => 12,
                    'dimensions' => $model['dimensions'] ?? ['x' => 200, 'y' => 200, 'z' => 100],
                    'watertight' => true,
                ]),
            ])->all(),
        ])->assertCreated();

        return QuotationRequest::sole()->load('items');
    }

    public function test_harga_penawaran_ditetapkan_rumus_harga_jual(): void
    {
        $quotation = $this->submit([['file' => 'bracket.stl']]);
        $item = $quotation->items->sole();

        $breakdown = $item->cost_breakdown;

        // Perhitungannya tersimpan utuh - komponen sekaligus parameternya.
        foreach ([
            'material_cost', 'machine_operational_cost', 'hpp', 'risk_cost',
            'packaging', 'overtime', 'subtotal', 'profit', 'basic_fee', 'selling_price',
            'material_qty_g', 'material_price_per_g', 'machine_time_hours', 'machine_cost',
            'risk_percent', 'profit_percent', 'largest_dimension_mm', 'basic_fee_label',
        ] as $key) {
            $this->assertArrayHasKey($key, $breakdown, $key.' tidak ikut tersimpan');
        }

        // Harga Jual = Subtotal + Profit + Basic Fee, dan itulah harga penawarannya.
        $this->assertSame(
            round($breakdown['subtotal'] + $breakdown['profit'] + $breakdown['basic_fee'], 2),
            (float) $breakdown['selling_price'],
        );
        $this->assertSame((float) $breakdown['selling_price'], (float) $item->estimated_cost);
        $this->assertSame((float) $breakdown['selling_price'], (float) $quotation->estimated_price);

        // Dimensi 200 x 200 x 100 mm menghasilkan tingkat Sedang: Rp25.000.
        $this->assertSame(200.0, (float) $breakdown['largest_dimension_mm']);
        $this->assertSame('Sedang', $breakdown['basic_fee_label']);
        $this->assertSame(25000.0, (float) $breakdown['basic_fee']);
    }

    public function test_total_penawaran_adalah_penjumlahan_harga_jual_tiap_model(): void
    {
        $quotation = $this->submit([
            ['file' => 'bracket.stl', 'quantity' => 2],
            ['file' => 'cover.obj', 'volume' => 80, 'dimensions' => ['x' => 60, 'y' => 40, 'z' => 30]],
        ]);

        $result = $this->estimator()->forQuotation($quotation);

        $this->assertCount(2, $result['models']);
        $this->assertSame(
            round($quotation->items->sum(fn (QuotationItem $item) => (float) $item->estimated_cost), 2),
            $result['selling_price'],
        );
        $this->assertSame((float) $quotation->estimated_price, $result['quotation_total']);
        $this->assertSame(0.0, $result['difference']);
        $this->assertFalse($result['reconstructed']);

        // Model 200 mm kena Basic Fee Sedang, model 60 mm tidak kena sama sekali.
        $this->assertSame(25000.0, (float) $result['models'][0]['calculation']['basic_fee']);
        $this->assertSame(0.0, (float) $result['models'][1]['calculation']['basic_fee']);
    }

    public function test_membuka_halaman_admin_tidak_mengubah_harga_walau_price_list_berubah(): void
    {
        $quotation = $this->submit([['file' => 'bracket.stl']]);

        $hargaAwal = (float) $quotation->estimated_price;
        $breakdownAwal = $quotation->items->sole()->cost_breakdown;

        // Parameter Price List digeser drastis SETELAH penawaran dibuat.
        PricingFormula::where('technology', 'FDM')->update([
            'profit_percent' => 100,
            'risk_percent' => 90,
            'machine_cost' => 999000,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Rp'.number_format($hargaAwal, 0, ',', '.'));

        $quotation->refresh()->load('items');

        $this->assertSame($hargaAwal, (float) $quotation->estimated_price);
        $this->assertSame($hargaAwal, (float) $quotation->items->sole()->estimated_cost);
        $this->assertSame($breakdownAwal, $quotation->items->sole()->cost_breakdown);

        // Rinciannya pun tetap memakai parameter saat penawaran dibuat.
        $calculation = $this->estimator()->forItem($quotation->items->sole());

        $this->assertSame(30.0, (float) $calculation['risk_percent']);
        $this->assertSame(50.0, (float) $calculation['profit_percent']);
        $this->assertSame((float) $calculation['selling_price'], $hargaAwal);
    }

    public function test_penawaran_tanpa_perhitungan_tersimpan_ditandai_disusun_ulang(): void
    {
        $quotation = $this->quotation();
        $this->addItem($quotation);

        $result = $this->estimator()->forQuotation($quotation->load('items'));

        $this->assertTrue($result['reconstructed']);
        $this->assertTrue($result['models'][0]['calculation']['reconstructed']);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('disusun ulang');
    }

    /**
     * Harga Estimasi dan Harga Jual adalah satu angka yang sama.
     *
     * Seluruh kolom harga penawaran dan setiap halaman yang menampilkannya
     * dibaca sekaligus di sini, supaya tidak ada satu pun tempat yang diam-diam
     * memakai sumber harga lain.
     */
    public function test_harga_estimasi_sama_dengan_harga_jual_di_setiap_tampilan(): void
    {
        $quotation = $this->submit([['file' => 'bracket.stl', 'quantity' => 2]]);
        $item = $quotation->items->sole();

        $hargaJual = (float) $item->cost_breakdown['selling_price'];
        $rupiah = 'Rp'.number_format($hargaJual, 0, ',', '.');

        // Kolom penawaran, termasuk nilai yang ditagihkan pada tahap pembayaran.
        $this->assertSame($hargaJual, (float) $item->estimated_cost);
        $this->assertSame($hargaJual, (float) $quotation->estimated_cost);
        $this->assertSame($hargaJual, (float) $quotation->estimated_price);
        $this->assertSame($hargaJual, $quotation->display_price);
        $this->assertSame($hargaJual, $quotation->payment_amount);

        // Dashboard pelanggan: daftar penawaran dan detailnya.
        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.index'))
            ->assertOk()
            ->assertSee($rupiah);

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.show', $quotation))
            ->assertOk()
            ->assertSee($rupiah);

        // Halaman tracking publik.
        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee($rupiah);

        // Halaman admin: kartu harga sekaligus baris Harga Jual pada rinciannya.
        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Harga Jual')
            ->assertSee($rupiah);
    }

    /* ------------------------------------------- parameter ke browser --- */

    /**
     * Calculator di browser menghitung harga dengan rumus yang sama, jadi
     * seluruh parameternya harus ikut terkirim. Bila salah satu hilang, angka
     * yang dilihat pelanggan akan berbeda dari yang disimpan server.
     */
    public function test_parameter_harga_ikut_dikirim_ke_browser(): void
    {
        MachineCost::create(['mesin' => 'Ender 3 V2', 'watt_kwh' => 0.25, 'harga_listrik' => 1700, 'depresiasi' => 2100]);
        PackagingItem::create(['item' => 'Kardus', 'ukuran' => 'M', 'dimensi' => '30 x 15 x 15 cm', 'price' => 5000, 'price_unit' => PackagingItem::UNIT_FLAT]);

        $payload = $this->estimator()->browserPayload();

        foreach (PricingFormula::technologies() as $technology) {
            $this->assertArrayHasKey($technology, $payload['formulas'], $technology.' tidak ikut terkirim');

            foreach (['machineCost', 'materialPricePerG', 'riskPercent', 'packagingCost', 'overtimeCost', 'profitPercent'] as $key) {
                $this->assertArrayHasKey($key, $payload['formulas'][$technology]);
            }
        }

        // Pencocokan nama mesin diselesaikan di server, browser tinggal membaca
        // Machine Cost yang berlaku untuk printer yang dipilih.
        $this->assertSame(['name' => 'Ender 3 V2', 'cost' => 4000.0], $payload['machines']['ender3']);
        $this->assertArrayNotHasKey('prusa_mk4', $payload['machines']);

        $this->assertSame([['label' => 'Kardus M', 'price' => 5000.0, 'sides' => [30.0, 15.0, 15.0]]], $payload['packaging']);
    }

    /* ------------------------------------------------------ hak melihat --- */

    public function test_admin_melihat_rincian_harga_jual_pada_detail_penawaran(): void
    {
        $quotation = $this->quotation();
        $this->addItem($quotation);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Detail Perhitungan Harga')
            ->assertSee('Lihat Detail Perhitungan')
            ->assertSee('Detail Harga — bracket.stl', false)
            ->assertSee('Operasional Mesin')
            ->assertSee('Risk Cost')
            ->assertSee('Basic Fee')
            ->assertSee('Subtotal + Profit + Basic Fee')
            ->assertSee('Total Penawaran')
            ->assertSee('Rp122.000')
            ->assertSee('Rp486.900');
    }

    public function test_dashboard_pelanggan_tidak_menampilkan_rincian_internal(): void
    {
        $quotation = $this->quotation();
        $this->addItem($quotation);

        $response = $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('File Sedang Direview');

        foreach ([
            'Detail Perhitungan Harga',
            'Lihat Detail Perhitungan',
            'Operasional Mesin',
            'Risk Cost',
            'HPP',
            'Machine Cost',
            'Basic Fee',
        ] as $internal) {
            $response->assertDontSee($internal);
        }
    }

    public function test_pelanggan_lain_tidak_dapat_membuka_detail_penawaran(): void
    {
        $quotation = $this->quotation();
        $this->addItem($quotation);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard.quotations.show', $quotation))
            ->assertNotFound();
    }

    public function test_tamu_tidak_dapat_membuka_detail_admin(): void
    {
        $quotation = $this->quotation();
        $this->addItem($quotation);

        $this->get(route('admin.quotations.show', $quotation))
            ->assertRedirect(route('admin.login'));
    }
}
