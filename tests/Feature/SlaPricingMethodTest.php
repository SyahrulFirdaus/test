<?php

namespace Tests\Feature;

use App\Models\PricingFormula;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
use App\Support\MaterialCatalog;
use App\Support\SlaIndustries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Teknologi SLA (dahulu SLA Industries) dengan metode harga per material:
 * Kalkulator Otomatis (rumus Harga Jual FDM) atau Kalkulator Manual (kuotasi
 * JLC yang sudah ada).
 */
class SlaPricingMethodTest extends TestCase
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

    private function material(string $method, string $name = 'Resin Uji'): PrintMaterial
    {
        $material = $this->sla()->materials()->create([
            'material' => $name,
            'brand' => 'Uji',
            'purchase_price' => 400000,
            'sale_price' => 1500,
            'pricing_method' => $method,
        ]);

        PrintTechnology::forgetCache();

        return $material;
    }

    private function konteks(string $material): array
    {
        return [
            'technology' => SlaIndustries::CODE,
            'material' => $material,
            'printer_name' => 'Creality Ender 3',
            'quantity' => 2,
            'total_weight_g' => 120,
            'minutes' => 300,
            'dimensions' => ['x' => 100, 'y' => 80, 'z' => 60],
        ];
    }

    /* ======================================================= penamaan === */

    public function test_hanya_ada_satu_pilihan_teknologi_bernama_sla(): void
    {
        $labels = collect(app(PrintEstimator::class)->browserPayload())
            ->map(fn (array $technology) => $technology['label']);

        $this->assertSame(1, $labels->filter(fn (string $label) => str_starts_with($label, 'SLA'))->count());
        $this->assertFalse($labels->contains(fn (string $label) => str_contains($label, 'SLA Industries')));
        $this->assertSame('SLA', MaterialCatalog::technologyLabel(SlaIndustries::CODE));
        $this->assertNotContains('SLA', PrintTechnology::codes());
    }

    public function test_tab_price_list_bernama_sla(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technology', ['slug' => 'sla']))
            ->assertOk()
            ->assertDontSee('SLA Industries')
            ->assertSee('Material SLA');

        $this->assertSame('SLA', $this->sla()->tabLabel());
    }

    public function test_material_sla_lama_digabung_sebagai_kalkulator_otomatis(): void
    {
        $sla = $this->sla();

        $this->assertTrue($sla->materials()->where('material', 'Standard Resin Plus Sunlu')->exists());
        $this->assertSame(
            [PrintMaterial::PRICING_AUTOMATIC],
            $sla->materials()->pluck('pricing_method')->unique()->values()->all(),
        );
        $this->assertFalse((bool) PrintTechnology::where('code', 'SLA')->value('is_active'));
    }

    /* ============================================== Tambah/Edit Material === */

    public function test_metode_harga_wajib_dipilih(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $this->sla()), [
                'material' => 'Resin Baru',
                'brand' => 'Uji',
            ])
            ->assertSessionHasErrors('pricing_method');

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $this->sla()), [
                'material' => 'Resin Baru',
                'brand' => 'Uji',
                'pricing_method' => 'lainnya',
            ])
            ->assertSessionHasErrors('pricing_method');
    }

    public function test_kalkulator_otomatis_menuntut_harga_material(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $this->sla()), [
                'material' => 'Resin Baru',
                'brand' => 'Uji',
                'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
            ])
            ->assertSessionHasErrors(['purchase_price', 'sale_price']);

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $this->sla()), [
                'material' => 'Resin Baru',
                'brand' => 'Uji',
                'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
                'purchase_price' => 400000,
                'sale_price' => 1500,
            ])
            ->assertSessionHasNoErrors();

        $material = PrintMaterial::where('material', 'Resin Baru')->firstOrFail();
        $this->assertSame(PrintMaterial::PRICING_AUTOMATIC, $material->pricing_method);
        $this->assertEqualsWithDelta(1500, (float) $material->sale_price, 0.01);
    }

    public function test_form_menampilkan_pilihan_dan_preview_rumus(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.create', $this->sla()))
            ->assertOk()
            ->assertSee('Menentukan Harga')
            ->assertSee('type="radio" name="pricing_method" value="automatic"', false)
            ->assertSee('type="radio" name="pricing_method" value="manual"', false)
            ->assertSee('Machine Time × Machine Cost')
            ->assertSee('Subtotal + Profit + Basic Fee')
            ->assertSee('HPP + Profit');
    }

    public function test_form_ubah_memilih_metode_tersimpan_dan_dapat_diganti(): void
    {
        $material = $this->material(PrintMaterial::PRICING_AUTOMATIC);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.edit', [$this->sla(), $material]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/value="automatic" required\s+class="[^"]*"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="manual" required\s+class="[^"]*"\s+checked/', $html);

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.materials.update', [$this->sla(), $material]), [
                'material' => $material->material,
                'brand' => $material->brand,
                'pricing_method' => PrintMaterial::PRICING_MANUAL,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(PrintMaterial::PRICING_MANUAL, $material->fresh()->pricing_method);
    }

    public function test_teknologi_lain_tidak_menampilkan_metode_harga(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.materials.create', $fdm))
            ->assertOk()
            ->assertDontSee('Menentukan Harga');
    }

    /* ================================================= perhitungan harga === */

    public function test_kalkulator_otomatis_memakai_rumus_dan_parameter_fdm(): void
    {
        $this->material(PrintMaterial::PRICING_AUTOMATIC);
        $estimator = app(SellingPriceEstimator::class);

        $hasil = $estimator->calculate($this->konteks('Resin Uji'));

        $this->assertArrayNotHasKey('manual_pricing', $hasil);
        $this->assertGreaterThan(0, $hasil['selling_price']);

        $fdm = PricingFormula::general();
        $this->assertEquals((float) $fdm->risk_percent, $hasil['risk_percent']);
        $this->assertEquals((float) $fdm->profit_percent, $hasil['profit_percent']);
        $this->assertEquals(1500.0, $hasil['material_price_per_g']);
        $this->assertEqualsWithDelta(
            $hasil['subtotal'] + $hasil['profit'] + $hasil['basic_fee'],
            $hasil['selling_price'],
            0.02,
        );

        $payload = app(PrintEstimator::class)->browserPayload();
        $this->assertFalse(collect($payload[SlaIndustries::CODE]['materials'])->firstWhere('name', 'Resin Uji')['manualPricing']);
        // Satu Rumus Harga Otomatis untuk seluruh teknologi: browser menerima
        // satu `formula` umum, jadi SLA otomatis dan FDM memakai parameter sama.
        $browser = $estimator->browserPayload();
        $this->assertArrayHasKey('formula', $browser);
        $this->assertArrayNotHasKey('formulas', $browser);
    }

    public function test_kalkulator_manual_menahan_harga(): void
    {
        $this->material(PrintMaterial::PRICING_MANUAL);

        $hasil = app(SellingPriceEstimator::class)->calculate($this->konteks('Resin Uji'));

        $this->assertTrue($hasil['manual_pricing']);
        $this->assertNull($hasil['selling_price']);

        $payload = app(PrintEstimator::class)->browserPayload();
        $this->assertTrue(collect($payload[SlaIndustries::CODE]['materials'])->firstWhere('name', 'Resin Uji')['manualPricing']);
    }
}
