<?php

namespace Tests\Feature;

use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->admin()->create([
            'email' => 'admin@nusama3d.com',
            'password' => Hash::make('rahasia123'),
        ]);
    }

    private function quotation(array $overrides = []): QuotationRequest
    {
        $quotation = QuotationRequest::create(array_merge([
            'tracking_number' => 'QTN-20260729-TEST01',
            'name' => 'Rangga Prasetya',
            'email' => 'rangga@contoh.test',
            'whatsapp' => '081234567890',
            'company' => 'PT Contoh Sejahtera',
            'quantity' => 2,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-07/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['triangles' => 12, 'vertices' => 36],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'analysis' => [['label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.']],
            'technology' => 'FDM',
            'material' => 'PLA',
            'model_volume_cm3' => 120.5,
            'material_volume_cm3' => 54.2,
            'estimated_weight_g' => 67.2,
            'estimated_minutes' => 380,
            'estimated_cost' => 185000,
            'status' => 'reviewing',
        ], $overrides));

        $this->addItem($quotation, 'bracket.stl', [
            'file_path' => 'quotations/2026-07/bracket.stl',
            'technology' => $quotation->technology,
            'material' => $quotation->material,
        ]);

        return $quotation->load('items');
    }

    /** Tambahkan satu model ke dalam penawaran. */
    private function addItem(QuotationRequest $quotation, string $fileName, array $overrides = []): QuotationItem
    {
        return $quotation->items()->create(array_merge([
            'position' => $quotation->items()->count() + 1,
            'file_name' => $fileName,
            'file_path' => 'quotations/2026-07/'.$fileName,
            'file_format' => strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)),
            'file_size' => 2048,
            'model_stats' => ['triangles' => 12, 'vertices' => 36],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'analysis' => [['label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.']],
            'technology' => 'FDM',
            'material' => 'PLA',
            'quantity' => 2,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'model_volume_cm3' => 120.5,
            'material_volume_cm3' => 54.2,
            'estimated_weight_g' => 67.2,
            'estimated_minutes' => 380,
            'estimated_cost' => 185000,
        ], $overrides));
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get(route('admin.quotations.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_dapat_masuk_dan_melihat_daftar_permintaan(): void
    {
        $this->admin();
        $this->quotation();

        $this->post(route('admin.login.store'), [
            'email' => 'admin@nusama3d.com',
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.quotations.index'))
            ->assertOk()
            ->assertSee('Rangga Prasetya')
            ->assertSee('bracket.stl')
            ->assertSee('FDM')
            ->assertSee('Ready to Print');
    }

    public function test_kata_sandi_salah_ditolak(): void
    {
        $this->admin();

        $this->post(route('admin.login.store'), [
            'email' => 'admin@nusama3d.com',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_dapat_melihat_detail_dan_mengubah_status(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('QTN-20260729-TEST01')
            ->assertSee('Mesh tertutup');

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.update', $quotation), [
                'status' => 'awaiting_payment',
                'admin_note' => 'Penawaran sudah dikirim via email.',
            ])->assertRedirect();

        $this->assertSame('awaiting_payment', $quotation->fresh()->status);
        $this->assertSame('Penawaran sudah dikirim via email.', $quotation->fresh()->admin_note);
    }

    public function test_admin_dapat_mengunduh_berkas_model(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('quotations/2026-07/bracket.stl', 'solid test');

        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.download', $quotation))
            ->assertOk()
            ->assertDownload('bracket.stl');
    }

    public function test_menghapus_permintaan_juga_menghapus_berkasnya(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('quotations/2026-07/bracket.stl', 'solid test');

        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->delete(route('admin.quotations.destroy', $quotation))
            ->assertRedirect(route('admin.quotations.index'));

        $this->assertSame(0, QuotationRequest::count());
        Storage::disk('local')->assertMissing('quotations/2026-07/bracket.stl');
    }

    public function test_filter_status_dan_teknologi_bekerja(): void
    {
        $this->quotation(['tracking_number' => 'QTN-20260729-AAAAAA', 'name' => 'Pemohon FDM', 'technology' => 'FDM', 'status' => 'reviewing']);
        $this->quotation(['tracking_number' => 'QTN-20260729-BBBBBB', 'name' => 'Pemohon SLM', 'technology' => 'SLM', 'material' => 'Titanium', 'status' => 'awaiting_payment']);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.index', ['technology' => 'SLM']))
            ->assertOk()
            ->assertSee('Pemohon SLM')
            ->assertDontSee('Pemohon FDM');

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.index', ['status' => 'reviewing']))
            ->assertOk()
            ->assertSee('Pemohon FDM')
            ->assertDontSee('Pemohon SLM');
    }

    /* ------------------------------------ penawaran dengan banyak model --- */

    public function test_detail_menampilkan_seluruh_model_dalam_satu_penawaran(): void
    {
        $quotation = $this->quotation();
        $this->addItem($quotation, 'cover.obj', ['technology' => 'SLA', 'material' => 'Standard Resin']);
        $this->addItem($quotation, 'gear.stl');

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('3 model')
            ->assertSee('bracket.stl')
            ->assertSee('cover.obj')
            ->assertSee('gear.stl')
            ->assertSee('Standard Resin');
    }

    public function test_admin_dapat_mengunduh_berkas_tiap_model(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('quotations/2026-07/cover.obj', 'v 0 0 0');

        $quotation = $this->quotation();
        $item = $this->addItem($quotation, 'cover.obj');

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.items.download', [$quotation, $item]))
            ->assertOk()
            ->assertDownload('cover.obj');
    }

    public function test_model_milik_penawaran_lain_tidak_dapat_diunduh(): void
    {
        $quotation = $this->quotation();
        $lain = $this->quotation(['tracking_number' => 'QTN-20260729-BBBBBB']);
        $item = $lain->items->first();

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.items.download', [$quotation, $item]))
            ->assertNotFound();
    }

    public function test_catatan_dapat_diubah_per_model_tanpa_mengubah_harga(): void
    {
        $quotation = $this->quotation();
        $kedua = $this->addItem($quotation, 'cover.obj', ['estimated_cost' => 90000]);
        $pertama = $quotation->items->first();
        $hargaSebelum = (float) $quotation->fresh()->estimated_price;

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.items.update', [$quotation, $kedua]), [
                // Harga sengaja ikut dikirim: admin tidak lagi boleh mengubahnya.
                'estimated_price' => 120000,
                'admin_note' => 'Dinding terlalu tipis, disarankan 1,2 mm.',
            ])->assertRedirect();

        $kedua->refresh();
        $pertama->refresh();

        $this->assertSame('Dinding terlalu tipis, disarankan 1,2 mm.', $kedua->admin_note);

        // Harga model tetap memakai estimasi sistem, kiriman admin diabaikan.
        $this->assertNull($kedua->estimated_price);

        // Model lain sama sekali tidak tersentuh.
        $this->assertNull($pertama->estimated_price);
        $this->assertNull($pertama->admin_note);

        // Harga penawaran pun tidak berubah.
        $this->assertEqualsWithDelta($hargaSebelum, (float) $quotation->fresh()->estimated_price, 0.01);
    }

    public function test_menghapus_penawaran_menghapus_berkas_seluruh_model(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('quotations/2026-07/bracket.stl', 'solid test');
        Storage::disk('local')->put('quotations/2026-07/cover.obj', 'v 0 0 0');

        $quotation = $this->quotation();
        $this->addItem($quotation, 'cover.obj');

        $this->actingAs($this->admin())
            ->delete(route('admin.quotations.destroy', $quotation))
            ->assertRedirect(route('admin.quotations.index'));

        Storage::disk('local')->assertMissing('quotations/2026-07/bracket.stl');
        Storage::disk('local')->assertMissing('quotations/2026-07/cover.obj');
        $this->assertSame(0, QuotationItem::count());
    }

    public function test_pencarian_menjangkau_nama_file_model_kedua(): void
    {
        $quotation = $this->quotation();
        $this->addItem($quotation, 'flange-khusus.obj');

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.index', ['q' => 'flange-khusus']))
            ->assertOk()
            ->assertSee('Rangga Prasetya');
    }

    public function test_filter_teknologi_menjangkau_model_kedua(): void
    {
        $quotation = $this->quotation(['name' => 'Pemohon Campuran']);
        $this->addItem($quotation, 'cover.obj', ['technology' => 'SLA', 'material' => 'Standard Resin']);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.index', ['technology' => 'SLA']))
            ->assertOk()
            ->assertSee('Pemohon Campuran');
    }
}
