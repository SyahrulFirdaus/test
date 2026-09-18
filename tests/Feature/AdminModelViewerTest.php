<?php

namespace Tests\Feature;

use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\AdminPermission as P;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kolom "Lihat 3D" pada "Printer dalam Penawaran Ini" dan halaman viewernya.
 */
class AdminModelViewerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function quotation(string $tracking = 'QT-VIEW01'): QuotationRequest
    {
        return QuotationRequest::create([
            'tracking_number' => $tracking,
            'name' => 'Rani', 'email' => 'rani@contoh.test', 'whatsapp' => '081200000000',
            'quantity' => 1, 'file_name' => 'produk-a.stl', 'file_path' => 'quotations/2026-09/a.stl',
            'file_format' => 'STL', 'file_size' => 4096,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM', 'material' => 'PLA Plus Standart ESUN', 'estimated_minutes' => 60,
            'status' => QuotationStatus::REVIEWING,
        ]);
    }

    private function item(QuotationRequest $quotation, string $name, string $path, string $printer = 'ender3'): QuotationItem
    {
        return $quotation->items()->create([
            'position' => $quotation->items()->count() + 1,
            'file_name' => $name, 'file_path' => $path, 'file_format' => strtoupper(pathinfo($name, PATHINFO_EXTENSION)), 'file_size' => 4096,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM', 'material' => 'PLA Plus Standart ESUN',
            'printer' => $printer, 'printer_name' => \App\Support\Printer::name($printer),
            'quantity' => 1, 'estimated_minutes' => 60, 'estimated_cost' => 100000,
        ]);
    }

    public function test_tabel_printer_menampilkan_kolom_lihat_3d_dan_download_per_baris(): void
    {
        $quotation = $this->quotation();
        Storage::disk('local')->put('quotations/2026-09/a.stl', 'solid a');
        Storage::disk('local')->put('quotations/2026-09/b.obj', '# b');
        $a = $this->item($quotation, 'produk-a.stl', 'quotations/2026-09/a.stl');
        $b = $this->item($quotation, 'produk-b.obj', 'quotations/2026-09/b.obj');

        $this->actingAs(User::factory()->withPermissions([P::QUOTATION_VIEW])->create())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Lihat 3D')
            ->assertSee(route('admin.quotations.items.viewer', [$quotation, $a]), false)
            ->assertSee(route('admin.quotations.items.viewer', [$quotation, $b]), false)
            ->assertSee(route('admin.quotations.items.download', [$quotation, $b]), false);
    }

    public function test_viewer_menampilkan_informasi_dan_berkas_baris_yang_benar(): void
    {
        $quotation = $this->quotation();
        Storage::disk('local')->put('quotations/2026-09/a.stl', 'solid a');
        $this->item($quotation, 'produk-a.stl', 'quotations/2026-09/a.stl');
        $b = $this->item($quotation, 'produk-b.stl', 'quotations/2026-09/b.stl');
        Storage::disk('local')->put('quotations/2026-09/b.stl', 'solid b');

        $admin = User::factory()->withPermissions([P::QUOTATION_VIEW])->create();

        $this->actingAs($admin)
            ->get(route('admin.quotations.items.viewer', [$quotation, $b]))
            ->assertOk()
            ->assertSee('produk-b.stl')
            ->assertSee('Nama File')
            ->assertSee('Mesin')
            ->assertSee('Technology')
            ->assertSee('Material')
            ->assertSee('FDM')
            ->assertSee('Kembali ke Penawaran')
            ->assertSee('data-file-url="'.route('admin.quotations.items.download', [$quotation, $b]).'"', false)
            ->assertSee(route('admin.quotations.show', $quotation).'#model-'.$b->id, false);

        // Berkas yang diambil viewer adalah berkas baris itu sendiri, tanpa salinan.
        $response = $this->actingAs($admin)->get(route('admin.quotations.items.download', [$quotation, $b]))->assertOk();
        $this->assertSame('solid b', $response->streamedContent());
        $this->assertCount(2, Storage::disk('local')->allFiles('quotations'));
    }

    public function test_berkas_hilang_menampilkan_pesan_yang_jelas(): void
    {
        $quotation = $this->quotation();
        $item = $this->item($quotation, 'hilang.stl', 'quotations/2026-09/hilang.stl');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('superadmin.quotations.items.viewer', [$quotation, $item]))
            ->assertOk()
            ->assertSee('Object 3D tidak dapat ditampilkan. Silakan unduh file untuk melihatnya secara lokal.')
            ->assertSee('data-file-url=""', false);
    }

    public function test_superadmin_memakai_alamatnya_sendiri(): void
    {
        $quotation = $this->quotation();
        Storage::disk('local')->put('quotations/2026-09/a.stl', 'solid a');
        $item = $this->item($quotation, 'produk-a.stl', 'quotations/2026-09/a.stl');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('superadmin.quotations.items.viewer', [$quotation, $item]))
            ->assertOk()
            ->assertSee(route('superadmin.quotations.items.download', [$quotation, $item]), false);
    }

    public function test_hak_akses_viewer_sama_dengan_melihat_penawaran(): void
    {
        $quotation = $this->quotation();
        $item = $this->item($quotation, 'produk-a.stl', 'quotations/2026-09/a.stl');
        $url = route('admin.quotations.items.viewer', [$quotation, $item]);

        $this->actingAs(User::factory()->withPermissions([P::PAYMENT_VIEW])->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertRedirect(route('dashboard'));

        auth()->logout();
        $this->get($url)->assertRedirect(route('admin.login'));
    }

    public function test_model_penawaran_lain_tidak_dapat_dibuka_lewat_penawaran_ini(): void
    {
        $own = $this->quotation('QT-VIEW01');
        $other = $this->quotation('QT-VIEW02');
        $foreign = $this->item($other, 'rahasia.stl', 'quotations/2026-09/r.stl');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.quotations.items.viewer', [$own, $foreign]))
            ->assertNotFound();
    }
}
