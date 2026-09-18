<?php

namespace Tests\Feature;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Harga Beli & Harga Jual pada form material tiap teknologi tampil dalam
 * format Rupiah ("Rp 185.000"), sedangkan yang terkirim tetap angka murni.
 */
class MaterialPriceRupiahInputTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    /** @return array<int, string> kode teknologi yang formnya memuat harga material */
    private function technologiesWithPrice(): array
    {
        return PrintTechnology::query()->get()
            ->reject(fn (PrintTechnology $technology) => $technology->isSlaIndustries())
            ->pluck('code')
            ->all();
    }

    public function test_form_ubah_menampilkan_harga_dalam_format_rupiah_di_setiap_teknologi(): void
    {
        foreach ($this->technologiesWithPrice() as $code) {
            $technology = PrintTechnology::where('code', $code)->sole();
            $material = $technology->materials()->create([
                'material' => 'Uji Rupiah '.$code,
                'brand' => 'ESUN',
                'purchase_price' => 185000,
                'sale_price' => 500,
                'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
            ]);

            $this->actingAs($this->superAdmin)
                ->get(route('superadmin.price-list.materials.edit', [$technology, $material]))
                ->assertOk()
                ->assertSee('value="Rp 185.000"', false)
                ->assertSee('value="Rp 500"', false)
                // Nilai yang terkirim tetap angka murni.
                ->assertSee('name="purchase_price" value="185000"', false)
                ->assertSee('name="sale_price" value="500"', false)
                // Tidak ada lagi input angka biasa untuk kedua kolom ini.
                ->assertDontSee('type="number" step="1" min="0" max="9999999999" id="purchase_price"', false);
        }
    }

    public function test_harga_jual_per_gram_disertai_harga_per_10_gram_yang_terkunci(): void
    {
        foreach ($this->technologiesWithPrice() as $code) {
            $technology = PrintTechnology::where('code', $code)->sole();
            $material = $technology->materials()->create([
                'material' => 'Uji Sepuluh '.$code,
                'brand' => 'ESUN',
                'purchase_price' => 185000,
                // Dibulatkan rumus menjadi Rp500/gram → Rp5.000 per 10 gram.
                'sale_price' => 450,
                'pricing_method' => PrintMaterial::PRICING_AUTOMATIC,
            ]);

            $html = $this->actingAs($this->superAdmin)
                ->get(route('superadmin.price-list.materials.edit', [$technology, $material]))
                ->assertOk()
                ->assertSee('Harga Jual per Gram (Rp)')
                ->assertSee('Harga Jual per 10 Gram (Rp)')
                ->assertDontSee('>Harga Jual (Rp)<', false)
                ->getContent();

            // Terkunci, tidak punya `name` (tidak ikut terkirim), berisi harga × 10.
            $this->assertMatchesRegularExpression(
                '/<input[^>]*id="sale_price_per_10_gram"[^>]*value="Rp 5\.000"[^>]*disabled/s',
                $html,
            );
            $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="sale_price_per_10_gram"/', $html);
        }
    }

    public function test_harga_per_10_gram_tidak_mengubah_data_yang_tersimpan(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->sole();

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $fdm), [
                'material' => 'PLA Sepuluh', 'brand' => 'ESUN',
                'purchase_price' => '185000', 'sale_price' => '500',
                // Kiriman palsu untuk kolom tampilan diabaikan.
                'sale_price_per_10_gram' => '1',
            ])
            ->assertSessionHasNoErrors();

        $material = PrintMaterial::where('material', 'PLA Sepuluh')->sole();
        $this->assertEquals(500, (float) $material->sale_price);
        $this->assertSame(500, $material->rounded_price);
    }

    public function test_form_tambah_membiarkan_harga_kosong_bukan_rp0(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->sole();

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.materials.create', $fdm))
            ->assertOk()
            ->assertSee('name="purchase_price" value=""', false)
            ->assertSee('name="sale_price" value=""', false)
            ->assertSee('data-nullable', false);
    }

    public function test_harga_kosong_tetap_ditolak_dan_angka_murni_tersimpan(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->sole();

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $fdm), [
                'material' => 'PLA Kosong', 'brand' => 'ESUN',
                'purchase_price' => '', 'sale_price' => '',
            ])
            ->assertSessionHasErrors(['purchase_price', 'sale_price']);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $fdm), [
                'material' => 'PLA Rupiah', 'brand' => 'ESUN',
                'purchase_price' => '185000', 'sale_price' => '500',
            ])
            ->assertSessionHasNoErrors();

        $material = PrintMaterial::where('material', 'PLA Rupiah')->sole();
        $this->assertEquals(185000, (float) $material->purchase_price);
        $this->assertEquals(500, (float) $material->sale_price);
    }
}
