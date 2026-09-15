<?php

namespace Tests\Feature;

use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Dashboard pelanggan: "Penawaran Saya", penyuntingan selama masih menunggu
 * review, dan alur pembatalan.
 */
class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->customer = User::factory()->create(['name' => 'Andi Saputra']);
    }

    private function quotation(array $overrides = [], ?User $owner = null): QuotationRequest
    {
        $quotation = QuotationRequest::create(array_merge([
            'user_id' => ($owner ?? $this->customer)->id,
            'tracking_number' => 'QTN-20260801-'.strtoupper(fake()->bothify('??####')),
            'name' => 'Andi Saputra',
            'email' => 'andi@contoh.test',
            'whatsapp' => '081234567890',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-08/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => [
                'triangles' => 12,
                'vertices' => 36,
                'dimensions' => ['x' => 50, 'y' => 40, 'z' => 30],
                'volume_cm3' => 100,
                'surface_area_cm2' => 120,
            ],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'build_volume' => ['x' => 220, 'y' => 220, 'z' => 250],
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'model_volume_cm3' => 100,
            'material_volume_cm3' => 45,
            'estimated_weight_g' => 55.8,
            'estimated_minutes' => 200,
            'estimated_cost' => 150000,
            'cost_breakdown' => ['material' => 50000, 'machine_time' => 100000, 'total' => 150000],
            'status' => QuotationStatus::REVIEWING,
        ], $overrides));

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-08/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => $quotation->model_stats,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'build_volume' => ['x' => 220, 'y' => 220, 'z' => 250],
            'quantity' => 1,
            'scale_percent' => 100,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'infill_density' => 0.20,
            'infill_pattern' => 'grid',
            'material_color' => 'merah',
            'model_volume_cm3' => 100,
            'material_volume_cm3' => 45,
            'estimated_weight_g' => 55.8,
            'estimated_minutes' => 200,
            'estimated_cost' => 150000,
            'cost_breakdown' => ['material' => 50000, 'machine_time' => 100000, 'total' => 150000],
        ]);

        return $quotation->load('items');
    }

    /** Kubus ASCII STL bersisi 10 mm — volumenya tepat 1 cm³. */
    private function cubeStl(float $size = 10.0): string
    {
        $corners = [
            [0, 0, 0], [$size, 0, 0], [$size, $size, 0], [0, $size, 0],
            [0, 0, $size], [$size, 0, $size], [$size, $size, $size], [0, $size, $size],
        ];

        $faces = [
            [0, 1, 2], [0, 2, 3],
            [4, 6, 5], [4, 7, 6],
            [0, 5, 1], [0, 4, 5],
            [1, 6, 2], [1, 5, 6],
            [2, 7, 3], [2, 6, 7],
            [3, 4, 0], [3, 7, 4],
        ];

        $stl = "solid cube\n";

        foreach ($faces as $face) {
            $stl .= "  facet normal 0 0 0\n    outer loop\n";

            foreach ($face as $index) {
                [$x, $y, $z] = $corners[$index];
                $stl .= "      vertex {$x} {$y} {$z}\n";
            }

            $stl .= "    endloop\n  endfacet\n";
        }

        return $stl."endsolid cube\n";
    }

    /* --------------------------------------------------- penawaran saya --- */

    public function test_dashboard_menampilkan_ringkasan_dan_penawaran_terbaru(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Andi Saputra')
            ->assertSee($quotation->tracking_number);
    }

    public function test_daftar_penawaran_hanya_memuat_milik_akun_sendiri(): void
    {
        $milikSendiri = $this->quotation();
        $milikOrangLain = $this->quotation([], User::factory()->create());

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.index'))
            ->assertOk()
            ->assertSee($milikSendiri->tracking_number)
            ->assertDontSee($milikOrangLain->tracking_number);
    }

    public function test_penawaran_akun_lain_tidak_dapat_dibuka(): void
    {
        $lain = $this->quotation([], User::factory()->create());

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.show', $lain))
            ->assertNotFound();
    }

    public function test_detail_menampilkan_nomor_status_dan_daftar_file(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.show', $quotation))
            ->assertOk()
            ->assertSee($quotation->tracking_number)
            ->assertSee('File Sedang Direview')
            ->assertSee('bracket.stl');
    }

    /* ------------------------------------------------ penyuntingan isi --- */

    public function test_halaman_ubah_menampilkan_form_pengaturan_dan_tambah_file(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.edit', $quotation))
            ->assertOk()
            ->assertSee('Ubah Penawaran')
            ->assertSee('Tambah File 3D')
            ->assertSee('bracket.stl')
            ->assertSee('Jumlah Cetak')
            ->assertSee('Simpan Pengaturan')
            // Batas unggah disebutkan apa adanya kepada pengguna.
            ->assertSee('file dalam satu penawaran');
    }

    public function test_pengaturan_model_dapat_diubah_selama_menunggu_review(): void
    {
        $quotation = $this->quotation();
        $item = $quotation->items->first();

        $this->actingAs($this->customer)
            ->patch(route('dashboard.quotations.items.update', [$quotation, $item]), [
                'quantity' => 4,
                'technology' => 'FDM',
                'material' => 'PETG High Speed ESUN',
                'printer' => 'bambu_x1c',
                'resolution' => '0.10',
                'scale_percent' => 100,
                'infill_density' => 0.60,
                'infill_pattern' => 'gyroid',
                'material_color' => 'biru',
                'support_enabled' => '1',
            ])->assertRedirect();

        $item->refresh();

        $this->assertSame(4, $item->quantity);
        $this->assertSame('PETG High Speed ESUN', $item->material);
        $this->assertSame('bambu_x1c', $item->printer);
        $this->assertSame('0.10', $item->resolution);
        $this->assertSame('biru', $item->material_color);
        $this->assertTrue($item->support_enabled);

        // Estimasi dihitung ulang di server, bukan disalin dari nilai lama.
        $this->assertGreaterThan(150000, (float) $item->estimated_cost);

        // Ringkasan penawaran ikut menyesuaikan isi modelnya.
        $quotation->refresh();
        $this->assertSame(4, $quotation->quantity);
        $this->assertEqualsWithDelta((float) $item->estimated_cost, (float) $quotation->estimated_cost, 0.01);
    }

    public function test_finishing_dan_warna_dapat_diubah_dari_dashboard(): void
    {
        $quotation = $this->quotation();
        $item = $quotation->items->first();
        $sebelum = (float) $item->estimated_cost;

        $this->actingAs($this->customer)
            ->patch(route('dashboard.quotations.items.update', [$quotation, $item]), [
                'quantity' => 1,
                'technology' => 'FDM',
                'material' => 'PLA Plus Standart ESUN',
                'printer' => 'ender3',
                'resolution' => '0.25',
                'material_color' => 'biru',
                'finishing' => 'painting',
            ])->assertRedirect();

        $item->refresh();

        $this->assertSame('painting', $item->finishing);
        $this->assertSame('biru', $item->material_color);
        $this->assertGreaterThan($sebelum, (float) $item->estimated_cost);
        $this->assertStringContainsString('Painting', $item->specification_summary);
    }

    public function test_warna_yang_tidak_tersedia_diperbaiki_mengikuti_material(): void
    {
        $quotation = $this->quotation();
        $item = $quotation->items->first();

        $this->actingAs($this->customer)
            ->patch(route('dashboard.quotations.items.update', [$quotation, $item]), [
                'quantity' => 1,
                'technology' => 'SLM',
                'material' => 'Titanium',
                'printer' => 'ender3',
                'material_color' => 'merah',
            ])->assertRedirect();

        // Part logam hanya tersedia dalam warna aslinya.
        $this->assertSame('logam', $item->fresh()->material_color);
    }

    public function test_kombinasi_teknologi_dan_material_yang_tidak_tersedia_ditolak(): void
    {
        $quotation = $this->quotation();
        $item = $quotation->items->first();

        $this->actingAs($this->customer)
            ->patch(route('dashboard.quotations.items.update', [$quotation, $item]), [
                'quantity' => 1,
                'technology' => 'FDM',
                'material' => 'Titanium',
                'printer' => 'ender3',
            ])->assertSessionHasErrors('material');

        $this->assertSame('PLA Plus Standart ESUN', $item->fresh()->material);
    }

    public function test_file_baru_dapat_ditambahkan_dan_diukur_di_server(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.items.store', $quotation), [
                'model' => UploadedFile::fake()->createWithContent('kubus.stl', $this->cubeStl()),
            ])->assertRedirect();

        $quotation->refresh()->load('items');

        $this->assertSame(2, $quotation->items->count());

        $baru = $quotation->items->last();

        $this->assertSame('kubus.stl', $baru->file_name);
        $this->assertSame(2, $baru->position);
        // Kubus 10 mm: volume 1000 mm³ = 1 cm³, luas permukaan 600 mm² = 6 cm².
        $this->assertEqualsWithDelta(1.0, $baru->model_stats['volume_cm3'], 0.001);
        $this->assertEqualsWithDelta(6.0, $baru->model_stats['surface_area_cm2'], 0.001);
        $this->assertSame(12, $baru->model_stats['triangles']);
        $this->assertGreaterThan(0, (float) $baru->estimated_cost);

        Storage::disk('local')->assertExists($baru->file_path);
    }

    public function test_format_file_selain_stl_dan_obj_ditolak_saat_menambah(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.items.store', $quotation), [
                'model' => UploadedFile::fake()->create('gambar.png', 10),
            ])->assertSessionHasErrors('model');

        $this->assertSame(1, $quotation->items()->count());
    }

    public function test_file_dapat_dihapus_selama_masih_menyisakan_satu(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.items.store', $quotation), [
                'model' => UploadedFile::fake()->createWithContent('kubus.stl', $this->cubeStl()),
            ]);

        $kedua = $quotation->items()->orderBy('position')->get()->last();

        $this->actingAs($this->customer)
            ->delete(route('dashboard.quotations.items.destroy', [$quotation, $kedua]))
            ->assertRedirect();

        $this->assertSame(1, $quotation->items()->count());
        Storage::disk('local')->assertMissing($kedua->file_path);
    }

    public function test_file_terakhir_tidak_dapat_dihapus(): void
    {
        $quotation = $this->quotation();
        $item = $quotation->items->first();

        $this->actingAs($this->customer)
            ->delete(route('dashboard.quotations.items.destroy', [$quotation, $item]))
            ->assertSessionHas('error');

        $this->assertSame(1, $quotation->items()->count());
    }

    public function test_penawaran_yang_sudah_lewat_tahap_review_menjadi_read_only(): void
    {
        // Sejak "Menunggu Review" dihapus, tahap pertama sekaligus tahap yang
        // masih dapat diubah adalah "File Sedang Direview"; yang read only
        // adalah tahap sesudahnya.
        $quotation = $this->quotation(['status' => QuotationStatus::AWAITING_PAYMENT]);
        $item = $quotation->items->first();

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.edit', $quotation))
            ->assertRedirect(route('dashboard.quotations.show', $quotation));

        $this->actingAs($this->customer)
            ->patch(route('dashboard.quotations.items.update', [$quotation, $item]), [
                'quantity' => 9,
                'technology' => 'FDM',
                'material' => 'PLA Plus Standart ESUN',
                'printer' => 'ender3',
            ])->assertSessionHasErrors('status');

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.items.store', $quotation), [
                'model' => UploadedFile::fake()->createWithContent('kubus.stl', $this->cubeStl()),
            ])->assertSessionHasErrors('status');

        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame(1, $quotation->items()->count());
    }

    /* ------------------------------------------------------- pembatalan --- */

    public function test_pembatalan_sebelum_review_langsung_berlaku(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.cancel', $quotation), ['reason' => 'Desain masih direvisi.'])
            ->assertRedirect(route('dashboard.quotations.show', $quotation));

        $quotation->refresh();

        $this->assertSame(QuotationStatus::CANCELLED_BY_USER, $quotation->status);
        $this->assertSame('Dibatalkan oleh User', $quotation->status_label);
        $this->assertSame('Desain masih direvisi.', $quotation->cancellation_reason);
        $this->assertNotNull($quotation->cancellation_resolved_at);
    }

    public function test_pembatalan_setelah_review_menjadi_permintaan_yang_menunggu_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $quotation = $this->quotation(['status' => QuotationStatus::AWAITING_PAYMENT]);

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.cancel', $quotation), ['reason' => 'Proyek ditunda.'])
            ->assertRedirect(route('dashboard.quotations.show', $quotation));

        $quotation->refresh();

        $this->assertSame(QuotationStatus::CANCELLATION_REQUESTED, $quotation->status);
        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $quotation->status_before_cancellation);
        $this->assertNull($quotation->cancellation_resolved_at);

        // Admin diberi tahu supaya pengajuannya tidak terlewat.
        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->assertSame(
            'cancellation.requested',
            $admin->notifications()->first()->data['type']
        );
    }

    public function test_penawaran_yang_sudah_selesai_tidak_dapat_dibatalkan(): void
    {
        $quotation = $this->quotation(['status' => QuotationStatus::COMPLETED]);

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.cancel', $quotation))
            ->assertSessionHas('error');

        $this->assertSame(QuotationStatus::COMPLETED, $quotation->fresh()->status);
    }

    /* ------------------------------------------------------- notifikasi --- */

    public function test_notifikasi_perubahan_status_tampil_di_dashboard_user(): void
    {
        $admin = User::factory()->admin()->create();
        $quotation = $this->quotation();

        $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
            'status' => QuotationStatus::AWAITING_PAYMENT,
        ])->assertRedirect();

        $this->assertSame(1, $this->customer->unreadNotifications()->count());

        $this->actingAs($this->customer)
            ->get(route('dashboard.notifications.index'))
            ->assertOk()
            ->assertSee('Menunggu Pembayaran');
    }

    public function test_endpoint_notifikasi_terbaru_mengembalikan_json(): void
    {
        $admin = User::factory()->admin()->create();
        $quotation = $this->quotation();

        $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
            'status' => QuotationStatus::AWAITING_PAYMENT,
        ]);

        $this->actingAs($this->customer)
            ->getJson(route('dashboard.notifications.latest'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Menunggu Pembayaran');
    }

    public function test_notifikasi_dapat_ditandai_sudah_dibaca(): void
    {
        $admin = User::factory()->admin()->create();
        $quotation = $this->quotation();

        $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
            'status' => QuotationStatus::AWAITING_PAYMENT,
        ]);

        $this->actingAs($this->customer)
            ->post(route('dashboard.notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $this->customer->unreadNotifications()->count());
    }

    public function test_admin_tidak_membuka_dashboard_pelanggan(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_menghapus_penawaran_ikut_membersihkan_itemnya(): void
    {
        $quotation = $this->quotation();

        $quotation->delete();

        $this->assertSame(0, QuotationItem::count());
    }
}
