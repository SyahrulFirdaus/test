<?php

namespace Tests\Feature;

use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PrintEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Switch Status pada menu Teknologi: aktif = tampil di Edit Specification.
 */
class TechnologyStatusTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function offered(): array
    {
        PrintTechnology::forgetCache();

        return array_keys(app(PrintEstimator::class)->browserPayload());
    }

    public function test_tabel_menampilkan_kolom_status_dengan_switch(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technologies.index'))
            ->assertOk()
            ->assertSee('Status')
            ->getContent();

        foreach (['FDM', 'SLAI', 'MJF', 'SLM'] as $code) {
            $technology = PrintTechnology::where('code', $code)->sole();
            $this->assertStringContainsString(route('superadmin.price-list.technologies.status', $technology), $html);
        }

        $this->assertStringContainsString('role="switch"', $html);

        // SLA lama yang sudah digabung diarsipkan, bukan baris yang dapat dinyalakan.
        $archived = PrintTechnology::where('code', 'SLA')->sole();
        $this->assertStringNotContainsString(route('superadmin.price-list.technologies.status', $archived), $html);
    }

    public function test_menonaktifkan_menyembunyikan_dari_edit_specification(): void
    {
        $superAdmin = $this->superAdmin();
        $mjf = PrintTechnology::where('code', 'MJF')->sole();

        $this->assertContains('MJF', $this->offered());

        $this->actingAs($superAdmin)
            ->patch(route('superadmin.price-list.technologies.status', $mjf), ['is_active' => '0'])
            ->assertRedirect(route('superadmin.price-list.technologies.index'))
            ->assertSessionHas('status');

        $this->assertFalse($mjf->fresh()->is_active);
        $this->assertNotContains('MJF', $this->offered());
        $this->assertNotContains('MJF', PrintTechnology::codes());

        // Tetap tampil (nonaktif) di menu Teknologi supaya dapat dinyalakan lagi.
        $this->actingAs($superAdmin)->get(route('superadmin.price-list.technologies.index'))
            ->assertSee(route('superadmin.price-list.technologies.status', $mjf));

        $this->actingAs($superAdmin)
            ->patch(route('superadmin.price-list.technologies.status', $mjf), ['is_active' => '1'])
            ->assertSessionHas('status');

        $this->assertTrue($mjf->fresh()->is_active);
        $this->assertContains('MJF', $this->offered());
    }

    public function test_teknologi_aktif_terakhir_tidak_dapat_dinonaktifkan(): void
    {
        $superAdmin = $this->superAdmin();
        PrintTechnology::whereNotIn('code', ['FDM'])->update(['is_active' => false]);
        $fdm = PrintTechnology::where('code', 'FDM')->sole();

        $this->actingAs($superAdmin)
            ->patch(route('superadmin.price-list.technologies.status', $fdm), ['is_active' => '0'])
            ->assertSessionHas('error');

        $this->assertTrue($fdm->fresh()->is_active);
    }

    public function test_hanya_superadmin(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->sole();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('superadmin.price-list.technologies.status', $fdm), ['is_active' => '0']);

        $this->assertTrue($fdm->fresh()->is_active);
    }
}
