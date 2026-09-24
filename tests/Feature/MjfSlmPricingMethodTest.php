<?php

namespace Tests\Feature;

use App\Models\PricingFormula;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
use App\Support\PricingMethod;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * MJF dan SLM memakai mekanisme metode harga yang sama dengan SLA
 * (App\Support\PricingMethod): Kalkulator Otomatis memakai rumus Harga Jual
 * FDM dengan parameter teknologinya sendiri, Kalkulator Manual memakai Form
 * Perhitungan yang sudah dipakai SLA. FDM tidak tersentuh.
 */
class MjfSlmPricingMethodTest extends TestCase
{
    use RefreshDatabase;

    private ?User $superAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(fn () => Http::response([
            'rates' => ['IDR' => 17690],
            'time_last_update_unix' => strtotime('2026-09-16T00:02:31+00:00'),
        ]));
    }

    public static function technologies(): array
    {
        return ['MJF' => ['MJF'], 'SLM' => ['SLM']];
    }

    private function superAdmin(): User
    {
        return $this->superAdmin ??= User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function technology(string $code): PrintTechnology
    {
        return PrintTechnology::where('code', $code)->firstOrFail();
    }

    /**
     * Material yang benar-benar ditawarkan kepada pelanggan.
     *
     * MJF/SLM menyaring pilihannya lewat `printing.material_display`, jadi
     * material uji memakai baris yang sudah ada, bukan baris baru.
     */
    private const OFFERED = ['MJF' => 'PA12', 'SLM' => 'Stainless Steel'];

    private function material(string $code, string $method): PrintMaterial
    {
        $material = $this->technology($code)->materials()->where('material', self::OFFERED[$code])->firstOrFail();
        $material->update(['pricing_method' => $method]);

        PrintTechnology::forgetCache();

        return $material;
    }

    private function konteks(string $code): array
    {
        return [
            'technology' => $code,
            'material' => self::OFFERED[$code],
            'printer_name' => 'Creality Ender 3',
            'quantity' => 1,
            'total_weight_g' => 120,
            'minutes' => 300,
            'dimensions' => ['x' => 100, 'y' => 80, 'z' => 60],
        ];
    }

    #[DataProvider('technologies')]
    public function test_material_lama_tetap_dihitung_otomatis(string $code): void
    {
        $methods = $this->technology($code)->materials()->pluck('pricing_method')->unique()->values()->all();

        $this->assertSame([PrintMaterial::PRICING_AUTOMATIC], $methods);
        $this->assertTrue(PricingMethod::appliesTo($code));
        $this->assertFalse(PricingMethod::appliesTo('FDM'));
    }

    #[DataProvider('technologies')]
    public function test_form_tambah_menampilkan_menentukan_harga_dan_preview(string $code): void
    {
        $formula = PricingFormula::general();

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.create', $this->technology($code)))
            ->assertOk()
            ->assertSee('Menentukan Harga')
            ->assertSee('type="radio" name="pricing_method" value="automatic"', false)
            ->assertSee('type="radio" name="pricing_method" value="manual"', false)
            ->assertSee('Machine Time × Machine Cost')
            ->assertSee('Subtotal + Profit + Basic Fee')
            ->assertSee('HPP × Risk % ('.rtrim(rtrim(number_format((float) $formula->risk_percent, 2, ',', '.'), '0'), ',').'%)')
            ->assertSee('HPP + Profit');
    }

    #[DataProvider('technologies')]
    public function test_metode_wajib_dan_otomatis_menuntut_harga(string $code): void
    {
        $technology = $this->technology($code);

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $technology), ['material' => 'Baru', 'brand' => 'Uji'])
            ->assertSessionHasErrors('pricing_method');

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $technology), [
                'material' => 'Baru', 'brand' => 'Uji', 'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
            ])
            ->assertSessionHasErrors(['purchase_price', 'sale_price']);

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $technology), [
                'material' => 'Baru', 'brand' => 'Uji', 'pricing_method' => PrintMaterial::PRICING_MANUAL,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(PrintMaterial::PRICING_MANUAL, $technology->materials()->where('material', 'Baru')->value('pricing_method'));
    }

    #[DataProvider('technologies')]
    public function test_form_ubah_memilih_metode_tersimpan(string $code): void
    {
        $material = $this->material($code, PrintMaterial::PRICING_AUTOMATIC);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.edit', [$this->technology($code), $material]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/value="automatic" required\s+class="[^"]*"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="manual" required\s+class="[^"]*"\s+checked/', $html);

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.materials.update', [$this->technology($code), $material]), [
                'material' => $material->material,
                'brand' => $material->brand,
                'purchase_price' => 2800000,
                'sale_price' => 3500,
                'pricing_method' => PrintMaterial::PRICING_MANUAL,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(PrintMaterial::PRICING_MANUAL, $material->fresh()->pricing_method);
    }

    #[DataProvider('technologies')]
    public function test_otomatis_memakai_rumus_harga_otomatis_umum(string $code): void
    {
        $this->material($code, PrintMaterial::PRICING_AUTOMATIC);

        $hasil = app(SellingPriceEstimator::class)->calculate($this->konteks($code));
        $formula = PricingFormula::general();

        $this->assertArrayNotHasKey('manual_pricing', $hasil);
        $this->assertGreaterThan(0, $hasil['selling_price']);
        $this->assertEquals((float) $formula->risk_percent, $hasil['risk_percent']);
        $this->assertEquals((float) $formula->profit_percent, $hasil['profit_percent']);

        $payload = app(PrintEstimator::class)->browserPayload();
        $this->assertFalse(collect($payload[$code]['materials'])->firstWhere('name', self::OFFERED[$code])['manualPricing']);
    }

    #[DataProvider('technologies')]
    public function test_manual_menahan_harga_sampai_tim_menetapkannya(string $code): void
    {
        $this->material($code, PrintMaterial::PRICING_MANUAL);

        $hasil = app(SellingPriceEstimator::class)->calculate($this->konteks($code));
        $this->assertTrue($hasil['manual_pricing']);
        $this->assertNull($hasil['selling_price']);

        $payload = app(PrintEstimator::class)->browserPayload();
        $this->assertTrue(collect($payload[$code]['materials'])->firstWhere('name', self::OFFERED[$code])['manualPricing']);

        $quotation = QuotationRequest::create([
            'tracking_number' => 'QT-'.$code.'01',
            'name' => 'Rani', 'email' => 'rani@contoh.test', 'whatsapp' => '081200000000',
            'quantity' => 1, 'file_name' => 'part.stl', 'file_path' => 'quotations/part.stl',
            'file_format' => 'STL', 'file_size' => 4096,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => $code, 'material' => self::OFFERED[$code], 'estimated_minutes' => 0,
            'estimated_cost' => null, 'estimated_price' => null,
            'status' => QuotationStatus::REVIEWING,
        ]);

        $item = $quotation->items()->create([
            'position' => 1, 'file_name' => 'part.stl', 'file_path' => 'quotations/part.stl',
            'file_format' => 'STL', 'file_size' => 4096,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => $code, 'material' => self::OFFERED[$code],
            'printer' => 'ender3', 'printer_name' => 'Creality Ender 3',
            'quantity' => 1, 'scale_percent' => 100, 'model_volume_cm3' => 90,
            'estimated_weight_g' => 0, 'support_weight_g' => 0, 'estimated_minutes' => 0,
            'estimated_cost' => null,
            'cost_breakdown' => $hasil,
        ]);

        $quotation = $quotation->fresh();
        $this->assertTrue($quotation->awaitsPricing());
        $this->assertNull($quotation->display_price);

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('Harga Perlu Dicek Terlebih Dahulu')
            ->assertDontSee('Rp0');

        // Form Perhitungan Kalkulator Manual yang sama dengan SLA.
        $admin = User::factory()->admin()->create(['email' => 'admin@nusama3d.com']);

        $this->actingAs($admin)
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Form Perhitungan Kalkulator Manual');

        $this->actingAs($admin)
            ->patch(route('admin.quotations.items.sla-industries', [$quotation, $item]), [
                'jlc_price_usd' => 115.76,
                'jlc_shipping_usd' => 2.70,
                'customs_idr' => 828000,
                'margin_percent' => 50,
            ])
            ->assertSessionHasNoErrors();

        $quotation = $quotation->fresh()->load('items');
        $this->assertFalse($quotation->awaitsPricing());
        $this->assertSame(4385337.0, (float) $quotation->display_price);
    }

    public function test_fdm_tidak_menampilkan_menentukan_harga(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.create', $this->technology('FDM')))
            ->assertOk()
            ->assertDontSee('Menentukan Harga');
    }
}
