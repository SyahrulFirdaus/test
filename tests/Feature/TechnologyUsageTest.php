<?php

namespace Tests\Feature;

use App\Models\PrintTechnology;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\ActivityAction;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kolom "Dipakai oleh" pada menu Teknologi: daftar penawaran yang menahan
 * sebuah teknologi dari penghapusan, lengkap dengan tombol hapus penawaran.
 */
class TechnologyUsageTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    /** Teknologi sendiri, supaya tidak bergantung pada data bawaan migrasi. */
    private function teknologi(string $code = 'DLP'): PrintTechnology
    {
        return PrintTechnology::create([
            'code' => $code, 'name' => 'Digital Light Processing', 'family' => 'Resin',
            'build_volume_x' => 190, 'build_volume_y' => 120, 'build_volume_z' => 245,
            'shell_ratio' => 1, 'default_infill' => 1, 'min_wall_thickness_mm' => 0.6,
            'support_volume_factor' => 0.14, 'layer_height_min' => 0.02, 'layer_height_max' => 0.1,
            'throughput_cm3_per_hour' => 12, 'setup_hours' => 0.5, 'setup_fee' => 40000,
            'machine_rate_per_hour' => 28000, 'sort_order' => 50,
        ]);
    }

    /** @param  array<int, string>  $technologies  teknologi tiap model */
    private function penawaran(string $tracking, array $technologies): QuotationRequest
    {
        $quotation = QuotationRequest::create([
            'tracking_number' => $tracking,
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.test',
            'whatsapp' => '081211112222',
            'quantity' => count($technologies),
            'file_name' => 'part.stl',
            'file_path' => 'quotations/2026-09/part.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => $technologies[0],
            'material' => 'Resin Uji',
            'status' => QuotationStatus::REVIEWING,
        ]);

        foreach ($technologies as $index => $code) {
            $quotation->items()->create([
                'position' => $index + 1,
                'file_name' => 'part-'.($index + 1).'.stl',
                'file_path' => 'quotations/2026-09/part-'.($index + 1).'.stl',
                'file_format' => 'STL',
                'file_size' => 2048,
                'analysis_status' => QuotationRequest::ANALYSIS_READY,
                'technology' => $code,
                'material' => 'Resin Uji',
                'quantity' => 1,
                'model_volume_cm3' => 10,
                'estimated_cost' => 100000,
            ]);
        }

        return $quotation;
    }

    public function test_kolom_dipakai_oleh_dan_modal_daftar_penawaran(): void
    {
        $dlp = $this->teknologi();
        $this->penawaran('QTN-20260928-DLP001', ['DLP', 'FDM']);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technologies.index'))
            ->assertOk()
            ->assertSee('Dipakai Oleh')
            ->assertSee('data-dialog-open="usage-'.$dlp->id.'"', false)
            ->assertSee('<dialog id="usage-'.$dlp->id.'"', false)
            ->assertSee('Hapus Penawaran?')
            ->getContent();

        $start = strpos($html, '<dialog id="usage-'.$dlp->id.'"');
        $modal = substr($html, $start, strpos($html, '</dialog>', $start) - $start);

        $this->assertStringContainsString('QTN-20260928-DLP001', $modal);
        $this->assertStringContainsString('Budi Santoso', $modal);
        $this->assertStringContainsString('juga memakai FDM', $modal);
        $this->assertStringContainsString('name="return_to" value="technologies"', $modal);
    }

    public function test_teknologi_yang_belum_dipakai_ditandai(): void
    {
        $this->teknologi();

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technologies.index'))
            ->assertOk()
            ->assertSee('Belum dipakai');
    }

    public function test_menghapus_penawaran_kembali_ke_teknologi_lalu_teknologinya_dapat_dihapus(): void
    {
        $dlp = $this->teknologi();
        $quotation = $this->penawaran('QTN-20260928-DLP002', ['DLP']);
        $superAdmin = $this->superAdmin();

        $this->assertTrue($dlp->isInUse());

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.quotations.destroy', $quotation), ['return_to' => 'technologies'])
            ->assertRedirect(route('superadmin.price-list.technologies.index'))
            ->assertSessionHas('status', 'Penawaran QTN-20260928-DLP002 berhasil dihapus.');

        $this->assertModelMissing($quotation);
        $this->assertDatabaseHas('activity_logs', ['action' => ActivityAction::QUOTATION_DELETE]);

        // Tidak ada lagi yang memakainya: teknologinya kini dapat dihapus.
        $this->assertFalse($dlp->fresh()->isInUse());

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.price-list.technologies.destroy', $dlp))
            ->assertSessionHas('status');

        $this->assertModelMissing($dlp);
    }

    /** Hapus penawaran dari menu Penawaran tetap seperti sebelumnya. */
    public function test_hapus_penawaran_biasa_tidak_berubah(): void
    {
        $this->teknologi();
        $quotation = $this->penawaran('QTN-20260928-DLP003', ['DLP']);

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.quotations.destroy', $quotation))
            ->assertRedirect(route('admin.quotations.index'))
            ->assertSessionHas('status', 'Permintaan penawaran berhasil dihapus.');

        $this->assertModelMissing($quotation);
    }
}
