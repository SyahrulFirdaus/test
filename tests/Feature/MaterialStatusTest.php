<?php

namespace Tests\Feature;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PrintEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Switch Status pada tabel material (Teknologi & Material): aktif = tampil
 * sebagai pilihan Material di Edit Specification.
 */
class MaterialStatusTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    /** @return array<int, string> nama material FDM yang ditawarkan */
    private function offeredFdm(): array
    {
        PrintTechnology::forgetCache();

        return array_column(app(PrintEstimator::class)->browserPayload()['FDM']['materials'], 'name');
    }

    private function fdmMaterial(): PrintMaterial
    {
        return PrintTechnology::where('code', 'FDM')->sole()->materials()->orderBy('id')->firstOrFail();
    }

    public function test_tabel_material_memiliki_kolom_status_dengan_switch(): void
    {
        $material = $this->fdmMaterial();

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technology', ['slug' => 'fdm']))
            ->assertOk()
            ->assertSee('Status')
            ->assertSee(route('superadmin.price-list.materials.status', [$material->technology, $material]))
            ->assertSee('role="switch"', false);
    }

    public function test_menonaktifkan_menyembunyikan_material_dari_edit_specification(): void
    {
        $superAdmin = $this->superAdmin();
        $material = $this->fdmMaterial();
        $technology = $material->technology;

        $this->assertContains($material->material, $this->offeredFdm());

        $this->actingAs($superAdmin)
            ->from(route('superadmin.price-list.technology', ['slug' => 'fdm', 'fdm_page' => 1]))
            ->patch(route('superadmin.price-list.materials.status', [$technology, $material]), ['is_active' => '0'])
            ->assertRedirect(route('superadmin.price-list.technology', ['slug' => 'fdm', 'fdm_page' => 1]))
            ->assertSessionHas('status');

        $this->assertFalse($material->fresh()->is_active);
        $this->assertNotContains($material->material, $this->offeredFdm());
        $this->assertFalse(app(PrintEstimator::class)->supports('FDM', $material->material));

        // Tetap terdaftar di Price List supaya dapat dinyalakan lagi.
        $this->actingAs($superAdmin)
            ->get(route('superadmin.price-list.technology', ['slug' => 'fdm']))
            ->assertSee($material->material);

        $this->actingAs($superAdmin)
            ->patch(route('superadmin.price-list.materials.status', [$technology, $material]), ['is_active' => '1']);

        $this->assertTrue($material->fresh()->is_active);
        $this->assertContains($material->material, $this->offeredFdm());
    }

    public function test_material_teknologi_lain_tidak_dapat_diubah_lewat_alamat_ini(): void
    {
        $material = $this->fdmMaterial();
        $mjf = PrintTechnology::where('code', 'MJF')->sole();

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.materials.status', [$mjf, $material]), ['is_active' => '0'])
            ->assertNotFound();

        $this->assertTrue($material->fresh()->is_active);
    }
}
