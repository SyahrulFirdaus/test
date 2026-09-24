<?php

namespace Tests\Feature;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\SlaIndustriesFormula;
use App\Models\SlaIndustriesQuote;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
use App\Support\QuotationStatus;
use App\Support\SlaIndustries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Teknologi SLA Industries beserta alur harganya.
 *
 * Yang dijaga bukan sekadar "rumusnya benar", melainkan perbedaan pokoknya
 * dengan teknologi lain:
 *
 *  - pelanggan TIDAK pernah melihat harga sebelum tim menghitungnya;
 *  - harganya ditetapkan per model dari kuotasi vendor, bukan dari berat dan
 *    waktu mesin;
 *  - rumus FDM/SLA/MJF/SLM tidak ikut tersentuh.
 */
class SlaIndustriesTest extends TestCase
{
    use RefreshDatabase;

    /** Kurs yang dipakai seluruh pengujian, supaya angkanya pasti. */
    private const KURS = 17690;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Penyedia kurs TIDAK pernah benar-benar dihubungi dari pengujian:
        // angkanya akan berubah tiap hari dan membuat seluruh perbandingan
        // dengan contoh spesifikasi menjadi rapuh.
        Http::fake(fn () => Http::response([
            'rates' => ['IDR' => self::KURS],
            'time_last_update_unix' => strtotime('2026-09-16T00:02:31+00:00'),
        ]));
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@nusama3d.com')->first()
            ?? User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create(['email' => 'admin@nusama3d.com']);
    }

    private function technology(): PrintTechnology
    {
        return PrintTechnology::where('code', SlaIndustries::CODE)->firstOrFail();
    }

    /** Parameter contoh dari spesifikasi, dipakai ulang di beberapa pengujian. */
    private function contohParameter(float $margin = 50): array
    {
        // `usd_rate` sengaja TIDAK ada: kurs tidak lagi dikirim formulir,
        // melainkan diambil server sendiri. Lihat setUp().
        return [
            'jlc_price_usd' => 115.76,
            'jlc_shipping_usd' => 2.70,
            'customs_idr' => 828000,
            'margin_percent' => $margin,
        ];
    }

    /** Parameter lengkap beserta kursnya, untuk menguji rumusnya langsung. */
    private function contohHitung(float $margin = 50): array
    {
        return ['usd_rate' => self::KURS, ...$this->contohParameter($margin)];
    }

    /* ======================================================== 1. rumus === */

    /**
     * Angka dari spesifikasi harus keluar persis, bukan sekadar mendekati.
     *
     * Kalau pembulatannya bergeser sedikit saja, Final Price yang ditagihkan
     * ke pelanggan tidak lagi sama dengan yang dihitung tim di luar sistem.
     */
    public function test_rumus_menghasilkan_angka_persis_seperti_spesifikasi(): void
    {
        $hasil = SlaIndustries::compute($this->contohHitung());

        $this->assertSame(2047795.0, $hasil['jlc_price_idr']);
        $this->assertSame(47763.0, $hasil['jlc_shipping_idr']);
        $this->assertSame(118.46, $hasil['total_jlc_usd']);
        $this->assertSame(2095558.0, $hasil['total_jlc_idr']);
        $this->assertSame(2923558.0, $hasil['hpp']);
        $this->assertSame(1461779.0, $hasil['profit']);
        $this->assertSame(4385337.0, $hasil['final_price']);
    }

    /** Mengubah margin harus menggeser Profit DAN Final Price, tanpa menyentuh HPP. */
    public function test_margin_berbeda_menghitung_ulang_profit_dan_final_price(): void
    {
        $limaPuluh = SlaIndustries::compute($this->contohHitung(50));
        $tigaPuluh = SlaIndustries::compute($this->contohHitung(30));

        $this->assertSame($limaPuluh['hpp'], $tigaPuluh['hpp']);
        $this->assertSame(877068.0, $tigaPuluh['profit']);
        $this->assertSame(3800626.0, $tigaPuluh['final_price']);
    }

    /** Kurs adalah satu-satunya sumber nilai rupiah kuotasi vendor. */
    public function test_kurs_menentukan_nilai_rupiah_kuotasi(): void
    {
        $hasil = SlaIndustries::compute([...$this->contohHitung(), 'usd_rate' => 16000]);

        $this->assertSame((float) ceil(115.76 * 16000), $hasil['jlc_price_idr']);
        $this->assertSame((float) ceil(2.70 * 16000), $hasil['jlc_shipping_idr']);
    }

    /* ============================================= 2. Price List & tab === */

    public function test_tab_sla_industries_tampil_beserta_rumusnya(): void
    {
        // Rumus Harga SLA kini berada di menu Harga → Rumus Harga Manual.
        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.harga-manual'))
            ->assertOk();

        $response->assertSee('Rumus Harga Manual');
        $response->assertSee('Total Bayar ke JLC');
        $response->assertSee('DHL Beacukai (Pajak)');
        $response->assertSee('Final Price');

        // Halaman Teknologi SLA tidak lagi memuat rumusnya.
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technology', ['slug' => 'sla']))
            ->assertOk()
            ->assertSee('Material SLA')
            ->assertDontSee('Total Bayar ke JLC');
    }

    public function test_superadmin_menyimpan_parameter_bawaan_rumus(): void
    {
        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.sla-industries.update'), $this->contohParameter(40))
            ->assertRedirect();

        $formula = SlaIndustriesFormula::current();

        // Kurs tidak ikut dikirim formulir; server yang mengisinya.
        $this->assertEqualsWithDelta(self::KURS, (float) $formula->usd_rate, 0.01);
        $this->assertEqualsWithDelta(40, (float) $formula->margin_percent, 0.01);
        $this->assertSame(2923558.0, $formula->hpp);
    }

    /* ================================================ 3. validasi margin === */

    /** @return array<string, array{float}> */
    public static function marginDitolak(): array
    {
        return ['dua puluh' => [20.0], 'dua puluh lima' => [25.0], 'lima puluh lima' => [55.0], 'tujuh puluh' => [70.0]];
    }

    /** @dataProvider marginDitolak */
    public function test_margin_di_luar_rentang_ditolak_server(float $margin): void
    {
        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.sla-industries.update'), $this->contohParameter($margin))
            ->assertSessionHasErrors('margin_percent');
    }

    /** @return array<string, array{float}> */
    public static function marginDiterima(): array
    {
        return ['tiga puluh' => [30.0], 'tiga puluh lima' => [35.0], 'empat puluh' => [40.0], 'lima puluh' => [50.0]];
    }

    /** @dataProvider marginDiterima */
    public function test_margin_di_dalam_rentang_diterima(float $margin): void
    {
        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.sla-industries.update'), $this->contohParameter($margin))
            ->assertSessionHasNoErrors();
    }

    /* ==================================================== 4. material === */

    public function test_material_sla_industries_tidak_menuntut_harga(): void
    {
        $technology = $this->technology();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $technology), [
                'material' => 'Industrial Resin',
                'brand' => 'JLC',
                'remark' => 'Untuk part fungsional',
                'pricing_method' => PrintMaterial::PRICING_MANUAL,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $material = PrintMaterial::where('material', 'Industrial Resin')->firstOrFail();

        $this->assertSame($technology->getKey(), $material->print_technology_id);
        $this->assertEqualsWithDelta(0, (float) $material->purchase_price, 0.01);
        $this->assertSame(PrintMaterial::PRICING_MANUAL, $material->pricing_method);
    }

    /**
     * Material yang ditambahkan Superadmin harus langsung menjadi pilihan pada
     * Edit Specification, dan hilang lagi begitu dihapus.
     */
    public function test_material_price_list_menjadi_pilihan_edit_specification(): void
    {
        $technology = $this->technology();
        $material = $technology->materials()->create([
            'material' => 'Industrial Resin',
            'brand' => 'JLC',
            'purchase_price' => 0,
            'sale_price' => 0,
        ]);

        PrintTechnology::forgetCache();
        $payload = app(PrintEstimator::class)->browserPayload();

        $this->assertContains(
            'Industrial Resin',
            collect($payload[SlaIndustries::CODE]['materials'])->pluck('name')->all(),
        );

        $material->delete();
        PrintTechnology::forgetCache();

        $this->assertNotContains(
            'Industrial Resin',
            collect(app(PrintEstimator::class)->browserPayload()[SlaIndustries::CODE]['materials'])->pluck('name')->all(),
        );
    }

    /** Browser diberi tahu bahwa teknologi ini tidak boleh dihitung sendiri. */
    public function test_browser_diberi_penanda_harga_ditetapkan_tim(): void
    {
        $this->technology()->materials()->create([
            'material' => 'Industrial Resin',
            'brand' => 'JLC',
            'purchase_price' => 0,
            'sale_price' => 0,
            'pricing_method' => PrintMaterial::PRICING_MANUAL,
        ]);
        PrintTechnology::forgetCache();

        $payload = app(PrintEstimator::class)->browserPayload();
        $material = collect($payload[SlaIndustries::CODE]['materials'])->firstWhere('name', 'Industrial Resin');

        // Penandanya kini melekat pada material, bukan teknologinya.
        $this->assertTrue($material['manualPricing']);
        $this->assertFalse($payload['FDM']['manualPricing']);
        $this->assertFalse(collect($payload['FDM']['materials'])->contains('manualPricing', true));
    }

    /* =========================================== 5. harga ditahan dulu === */

    private function penawaranSlaIndustries(): QuotationRequest
    {
        $this->technology()->materials()->firstOrCreate(
            ['material' => 'Industrial Resin'],
            ['brand' => 'JLC', 'purchase_price' => 0, 'sale_price' => 0],
        );

        $quotation = QuotationRequest::create([
            'tracking_number' => 'QT-SLAI01',
            'name' => 'Rani Puspita',
            'email' => 'rani@contoh.test',
            'whatsapp' => '081200000000',
            'quantity' => 1,
            'file_name' => 'impeller.stl',
            'file_path' => 'quotations/2026-09/impeller.stl',
            'file_format' => 'STL',
            'file_size' => 4096,
            'model_stats' => ['dimensions' => ['x' => 120, 'y' => 120, 'z' => 80]],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => SlaIndustries::CODE,
            'material' => 'Industrial Resin',
            'estimated_minutes' => 0,

            // Permintaan SLA Industries masuk TANPA harga sama sekali.
            'estimated_cost' => null,
            'estimated_price' => null,
            'status' => QuotationStatus::REVIEWING,
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'impeller.stl',
            'file_path' => 'quotations/2026-09/impeller.stl',
            'file_format' => 'STL',
            'file_size' => 4096,
            'model_stats' => ['dimensions' => ['x' => 120, 'y' => 120, 'z' => 80]],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => SlaIndustries::CODE,
            'material' => 'Industrial Resin',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 1,
            'scale_percent' => 100,
            'model_volume_cm3' => 90,
            'estimated_weight_g' => 0,
            'support_weight_g' => 0,
            'estimated_minutes' => 0,
            'estimated_cost' => null,
            'cost_breakdown' => ['manual_pricing' => true, 'selling_price' => null, 'technology' => SlaIndustries::CODE],
        ]);

        return $quotation->fresh();
    }

    /** Estimator TIDAK boleh menghasilkan harga untuk teknologi ini. */
    public function test_estimator_tidak_menghitung_harga_sla_industries(): void
    {
        $hasil = app(SellingPriceEstimator::class)->calculate([
            'technology' => SlaIndustries::CODE,
            'material' => 'Industrial Resin',
            'quantity' => 2,
            'total_weight_g' => 500,
            'minutes' => 600,
        ]);

        $this->assertTrue($hasil['manual_pricing']);
        $this->assertNull($hasil['selling_price']);
    }

    public function test_pelanggan_melihat_menunggu_perhitungan_bukan_angka(): void
    {
        $quotation = $this->penawaranSlaIndustries();

        $this->assertTrue($quotation->awaitsPricing());
        $this->assertNull($quotation->display_price);

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('Harga Perlu Dicek Terlebih Dahulu')
            ->assertDontSee('Rp0');
    }

    /** Penawaran tidak boleh maju ke tahap pembayaran tanpa harga. */
    public function test_status_tidak_dapat_maju_selama_harga_belum_ditetapkan(): void
    {
        $quotation = $this->penawaranSlaIndustries();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.update', $quotation), [
                'status' => QuotationStatus::AWAITING_PAYMENT,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(QuotationStatus::REVIEWING, $quotation->fresh()->status);
    }

    /* ================================ 6. admin menetapkan harga akhirnya === */

    public function test_admin_menetapkan_harga_dan_pelanggan_melihat_final_price(): void
    {
        $quotation = $this->penawaranSlaIndustries();
        $item = $quotation->items->first();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.items.sla-industries', [$quotation, $item]), [
                ...$this->contohParameter(50),
                'product_name' => 'Impeller',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $item->refresh();
        $quotation->refresh()->load('items');

        $this->assertInstanceOf(SlaIndustriesQuote::class, $item->slaIndustriesQuote);
        $this->assertSame(4385337.0, $item->slaIndustriesQuote->final_price);

        // Kurs yang dipakai ikut dibekukan beserta asal dan waktu terbitnya.
        $this->assertEqualsWithDelta(self::KURS, (float) $item->slaIndustriesQuote->usd_rate, 0.01);
        $this->assertSame('ExchangeRate-API', $item->slaIndustriesQuote->usd_rate_source);
        $this->assertNotNull($item->slaIndustriesQuote->usd_rate_published_at);

        // Final Price menjadi harga model, dan karenanya harga penawaran.
        $this->assertEqualsWithDelta(4385337, (float) $item->estimated_cost, 0.01);
        $this->assertFalse($quotation->awaitsPricing());
        $this->assertEqualsWithDelta(4385337, (float) $quotation->display_price, 0.01);

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('Rp4.385.337')
            ->assertDontSee('Menunggu Perhitungan');
    }

    /** Rincian internal tidak boleh bocor ke halaman pelanggan. */
    public function test_pelanggan_tidak_melihat_rincian_internal(): void
    {
        $quotation = $this->penawaranSlaIndustries();
        $item = $quotation->items->first();

        $this->actingAs($this->admin())->patch(
            route('admin.quotations.items.sla-industries', [$quotation, $item]),
            [...$this->contohParameter(50), 'product_name' => 'Impeller'],
        );

        $response = $this->get(route('tracking.show', $quotation->fresh()->tracking_number))->assertOk();

        foreach (['Harga JLC', 'Ongkir JLC', 'DHL Beacukai', 'HPP', 'Margin Profit'] as $rahasia) {
            $response->assertDontSee($rahasia);
        }

        // Angka antaranya pun tidak boleh muncul.
        $response->assertDontSee('Rp2.923.558');
        $response->assertDontSee('Rp1.461.779');
    }

    public function test_margin_di_luar_rentang_ditolak_pada_penawaran(): void
    {
        $quotation = $this->penawaranSlaIndustries();
        $item = $quotation->items->first();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.items.sla-industries', [$quotation, $item]), $this->contohParameter(20))
            ->assertSessionHasErrors('margin_percent');

        $this->assertNull($item->fresh()->slaIndustriesQuote);
    }

    /** Alamat ini tidak boleh dipakai menimpa harga teknologi lain. */
    public function test_model_teknologi_lain_tidak_dapat_dihitung_lewat_form_ini(): void
    {
        $quotation = $this->penawaranSlaIndustries();
        $item = $quotation->items->first();
        $item->update(['technology' => 'FDM']);

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.items.sla-industries', [$quotation, $item]), $this->contohParameter())
            ->assertNotFound();
    }

    /* ==================================== 7. teknologi lain tidak berubah === */

    /**
     * Rumus FDM/SLA/MJF/SLM harus tetap menghasilkan angka seperti semula.
     *
     * Inilah jaring pengaman requirement "jangan merusak fitur yang sudah ada":
     * cabang SLA Industries ditambahkan DI DEPAN perhitungan lama, jadi yang
     * perlu dibuktikan adalah perhitungan lama tidak ikut terpengaruh.
     */
    public function test_rumus_teknologi_lain_tidak_ikut_berubah(): void
    {
        $estimator = app(SellingPriceEstimator::class);

        $konteks = [
            'material' => 'PLA Basic ESUN',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 2,
            'total_weight_g' => 120,
            'minutes' => 300,
            'dimensions' => ['x' => 100, 'y' => 80, 'z' => 60],
        ];

        foreach (['FDM', 'SLA', 'MJF', 'SLM'] as $code) {
            $hasil = $estimator->calculate([...$konteks, 'technology' => $code]);

            $this->assertArrayNotHasKey('manual_pricing', $hasil, $code.' ikut terkena cabang SLA Industries.');
            $this->assertIsFloat($hasil['selling_price']);
            $this->assertGreaterThan(0, $hasil['selling_price'], $code.' kehilangan harganya.');

            // Komponen rumus lamanya harus utuh.
            foreach (['material_cost', 'machine_operational_cost', 'hpp', 'risk_cost', 'basic_fee'] as $komponen) {
                $this->assertArrayHasKey($komponen, $hasil, $code.' kehilangan komponen '.$komponen.'.');
            }
        }
    }

    /** Tab "Harga" tetap milik teknologi yang dicetak sendiri saja. */
    public function test_sla_industries_tidak_muncul_di_tab_harga_generik(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.harga'))
            ->assertOk();

        // Hanya satu rumus umum — tidak ada rumus per teknologi.
        $response->assertDontSee('id="formula-', false)
            ->assertSee('Rincian Harga Jual');
    }
}
