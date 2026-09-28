<?php

namespace Tests\Feature;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\SellingPriceEstimator;
use App\Support\PricingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Metode harga (Kalkulator Otomatis / Manual) dipilih pada SETIAP Tambah
 * Material — teknologi apa pun, termasuk yang baru ditambahkan Superadmin.
 */
class PricingMethodAllTechnologiesTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    /** Teknologi baru buatan Superadmin. */
    private function teknologiBaru(): PrintTechnology
    {
        return PrintTechnology::create([
            'code' => 'DLP', 'name' => 'Digital Light Processing', 'family' => 'Resin',
            'build_volume_x' => 220, 'build_volume_y' => 220, 'build_volume_z' => 250,
            'shell_ratio' => 1, 'default_infill' => 1, 'min_wall_thickness_mm' => 0.6,
            'support_volume_factor' => 0.15, 'layer_height_min' => 0.05, 'layer_height_max' => 0.1,
            'throughput_cm3_per_hour' => 12, 'setup_hours' => 0.5, 'setup_fee' => 25000,
            'machine_rate_per_hour' => 15000, 'sort_order' => 50,
        ]);
    }

    private function formMaterial(array $overrides = []): array
    {
        return array_merge([
            'material' => 'Resin Standard',
            'brand' => 'Sunlu',
            'purchase_price' => 275000,
            'sale_price' => 1031,
        ], $overrides);
    }

    public function test_setiap_teknologi_menampilkan_pilihan_metode_harga(): void
    {
        $superAdmin = $this->superAdmin();

        foreach ([PrintTechnology::where('code', 'FDM')->firstOrFail(), $this->teknologiBaru()] as $technology) {
            $this->actingAs($superAdmin)
                ->get(route('superadmin.price-list.materials.create', $technology))
                ->assertOk()
                ->assertSee('Menentukan Harga')
                ->assertSee('Kalkulator Otomatis')
                ->assertSee('Kalkulator Manual');
        }
    }

    public function test_metode_harga_wajib_dipilih(): void
    {
        $technology = $this->teknologiBaru();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $technology), $this->formMaterial())
            ->assertSessionHasErrors('pricing_method');

        $this->assertSame(0, $technology->materials()->count());
    }

    public function test_material_manual_menahan_harga_sampai_ditetapkan_tim(): void
    {
        $technology = $this->teknologiBaru();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $technology), $this->formMaterial([
                'pricing_method' => PrintMaterial::PRICING_MANUAL,
                'purchase_price' => '',
                'sale_price' => '',
            ]))
            ->assertSessionHasNoErrors();

        $material = $technology->materials()->sole();
        PrintTechnology::forgetCache();

        $this->assertSame(PrintMaterial::PRICING_MANUAL, $material->pricing_method);
        $this->assertTrue(PricingMethod::usesManualPricing('DLP', 'Resin Standard'));
    }

    public function test_material_otomatis_dihitung_rumus(): void
    {
        $technology = $this->teknologiBaru();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.materials.store', $technology), $this->formMaterial([
                'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
            ]))
            ->assertSessionHasNoErrors();

        PrintTechnology::forgetCache();

        $this->assertSame(PrintMaterial::PRICING_AUTOMATIC, $technology->materials()->sole()->pricing_method);
        $this->assertFalse(PricingMethod::usesManualPricing('DLP', 'Resin Standard'));
    }

    /** Material FDM yang sudah ada tetap Kalkulator Otomatis, seperti selama ini. */
    public function test_material_fdm_yang_ada_tetap_otomatis(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->firstOrFail();

        $this->assertSame(
            [PrintMaterial::PRICING_AUTOMATIC],
            $fdm->materials()->pluck('pricing_method')->unique()->values()->all(),
        );

        // Material yang dibuat tanpa memilih (mis. lewat kode) memakai bawaan teknologinya.
        $material = $fdm->materials()->create(['material' => 'PLA Uji', 'brand' => 'ESUN', 'purchase_price' => 185000, 'sale_price' => 463]);
        $this->assertSame(PrintMaterial::PRICING_AUTOMATIC, $material->pricing_method);

        PrintTechnology::forgetCache();
        $this->assertFalse(app(SellingPriceEstimator::class)->calculate([
            'technology' => 'FDM', 'material' => 'PLA Uji', 'printer_name' => 'Creality Ender 3',
            'quantity' => 1, 'total_weight_g' => 50, 'minutes' => 120, 'dimensions' => ['x' => 50, 'y' => 50, 'z' => 50],
        ])['manual_pricing'] ?? false);
    }
}
