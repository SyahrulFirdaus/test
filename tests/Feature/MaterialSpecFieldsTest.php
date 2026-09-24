<?php

namespace Tests\Feature;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Support\Finishing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kelebihan, kekurangan, dan pilihan finishing pada form Material.
 *
 * Ketiganya tinggal di dalam `technical_spec` — tempat yang sejak awal dibaca
 * halaman spesifikasi dan estimator — sehingga tidak ada kolom kedua bagi data
 * yang sama, dan Superadmin dapat mengubahnya sendiri tanpa menyentuh kode.
 */
class MaterialSpecFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private PrintTechnology $fdm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->fdm = PrintTechnology::where('code', 'FDM')->sole();

        PrintMaterial::query()->delete();
        PrintTechnology::forgetCache();
        app()->forgetInstance(PrintEstimator::class);
    }

    /* ---------------------------------------------------------- formulir --- */

    public function test_form_material_memuat_kelebihan_kekurangan_dan_finishing(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.materials.create', $this->fdm))
            ->assertOk()
            ->assertSee('Kelebihan')
            ->assertSee('Kekurangan')
            ->assertSee('name="advantages"', false)
            ->assertSee('name="disadvantages"', false)
            ->assertSee('name="finishings[]"', false)
            // Keempat pilihan finishing tampil beserta formulanya.
            ->assertSee('Raw / No Finishing')
            ->assertSee('Sanding + Painting')
            ->assertSee('Custom Finishing');
    }

    /* --------------------------------------------------------- menyimpan --- */

    public function test_kelebihan_dan_kekurangan_tersimpan_satu_baris_satu_butir(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload(),
                'advantages' => "Mudah dicetak\nHasil permukaan cukup baik\n\nCocok untuk prototype",
                'disadvantages' => "Ketahanan terhadap panas terbatas\nTidak cocok untuk temperatur tinggi",
            ])
            ->assertSessionHasNoErrors();

        $spec = PrintMaterial::where('material', 'PLA Plus')->sole()->technical_spec;

        // Baris kosong tidak ikut tersimpan.
        $this->assertSame(
            ['Mudah dicetak', 'Hasil permukaan cukup baik', 'Cocok untuk prototype'],
            $spec['pros'],
        );
        $this->assertSame(
            ['Ketahanan terhadap panas terbatas', 'Tidak cocok untuk temperatur tinggi'],
            $spec['cons'],
        );
    }

    public function test_pilihan_finishing_material_tersimpan(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload(),
                'finishings' => [Finishing::NONE, Finishing::SANDING],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [Finishing::NONE, Finishing::SANDING],
            PrintMaterial::where('material', 'PLA Plus')->sole()->technical_spec['finishings'],
        );
    }

    public function test_finishing_tak_dikenal_ditolak(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload(),
                'finishings' => ['chrome-plating'],
            ])
            ->assertSessionHasErrors('finishings.0');

        $this->assertSame(0, PrintMaterial::count());
    }

    /** Menyunting material tidak menghapus isi `technical_spec` yang lain. */
    public function test_menyimpan_tidak_menghapus_isi_spec_lainnya(): void
    {
        $material = $this->fdm->materials()->create([
            ...$this->payload(),
            'technical_spec' => ['density' => 1.24, 'minSize' => ['x' => 5, 'y' => 5, 'z' => 5]],
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.price-list.materials.update', [$this->fdm, $material]), [
                ...$this->payload(),
                'advantages' => 'Mudah dicetak',
            ])
            ->assertSessionHasNoErrors();

        $spec = $material->refresh()->technical_spec;

        $this->assertSame(1.24, $spec['density']);
        $this->assertSame(['x' => 5, 'y' => 5, 'z' => 5], $spec['minSize']);
        $this->assertSame(['Mudah dicetak'], $spec['pros']);
    }

    /* -------------------------------------------------------- sisi user --- */

    /**
     * Kelebihan dan kekurangan sampai ke Edit Specification.
     *
     * Inti keluhannya: bagiannya sudah ada di halaman, tetapi datanya tidak
     * pernah terisi karena form material belum punya kolomnya.
     */
    public function test_kelebihan_dan_kekurangan_terkirim_ke_browser(): void
    {
        $this->fdm->materials()->create([
            ...$this->payload(),
            'technical_spec' => [
                'pros' => ['Mudah dicetak', 'Cocok untuk prototype'],
                'cons' => ['Ketahanan terhadap panas terbatas'],
            ],
        ]);

        app()->forgetInstance(PrintEstimator::class);
        PrintTechnology::forgetCache();

        $material = collect(app(PrintEstimator::class)->browserPayload()['FDM']['materials'])
            ->firstWhere('name', 'PLA Plus');

        $this->assertSame(['Mudah dicetak', 'Cocok untuk prototype'], $material['pros']);
        $this->assertSame(['Ketahanan terhadap panas terbatas'], $material['cons']);
    }

    /** Daftar finishing tiap material ikut terkirim; kosong berarti semuanya. */
    public function test_pilihan_finishing_terkirim_ke_browser(): void
    {
        $this->fdm->materials()->create([
            ...$this->payload(),
            'technical_spec' => ['finishings' => [Finishing::NONE, Finishing::SANDING]],
        ]);

        $this->fdm->materials()->create([
            ...$this->payload(),
            'material' => 'PETG',
            'technical_spec' => [],
        ]);

        app()->forgetInstance(PrintEstimator::class);
        PrintTechnology::forgetCache();

        $materials = collect(app(PrintEstimator::class)->browserPayload()['FDM']['materials']);

        $this->assertSame([Finishing::NONE, Finishing::SANDING], $materials->firstWhere('name', 'PLA Plus')['finishings']);
        $this->assertSame([], $materials->firstWhere('name', 'PETG')['finishings']);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'material' => 'PLA Plus',
            'brand' => 'eSUN',
            'purchase_price' => 185000,
            'sale_price' => 231,
        ];
    }
}
