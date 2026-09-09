<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard admin: statistik, manajemen user, notifikasi, dan persetujuan
 * pembatalan.
 */
class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Administrator']);
        $this->customer = User::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'city' => 'Bandung',
        ]);
    }

    private function quotation(array $overrides = []): QuotationRequest
    {
        $quotation = QuotationRequest::create(array_merge([
            'user_id' => $this->customer->id,
            'tracking_number' => 'QTN-20260801-'.strtoupper(fake()->bothify('??####')),
            'name' => $this->customer->name,
            'email' => $this->customer->email,
            'whatsapp' => '081234567890',
            'quantity' => 2,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-08/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA',
            'model_volume_cm3' => 100,
            'estimated_weight_g' => 55.8,
            'estimated_minutes' => 200,
            'estimated_cost' => 150000,
            'status' => QuotationStatus::RECEIVED,
        ], $overrides));

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-08/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA',
            'quantity' => 2,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'model_volume_cm3' => 100,
            'estimated_weight_g' => 55.8,
            'estimated_minutes' => 200,
            'estimated_cost' => 150000,
        ]);

        return $quotation->load('items');
    }

    /* -------------------------------------------------------- statistik --- */

    public function test_dashboard_menampilkan_seluruh_angka_statistik(): void
    {
        $this->quotation(['status' => QuotationStatus::COMPLETED, 'estimated_price' => 500000]);
        $this->quotation();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Penawaran')
            ->assertSee('Pesanan Selesai')
            ->assertSee('Object 3D Dicetak')
            ->assertSee('Total Pendapatan')
            ->assertSee('User Terdaftar')
            ->assertSee('Statistik 12 Bulan Terakhir')
            // Pendapatan memakai harga penawaran yang sudah ditetapkan admin.
            ->assertSee('Rp500.000', false);
    }

    /* --------------------------------------------- filter status dashboard --- */

    public function test_dashboard_dapat_disaring_per_status(): void
    {
        $selesai = $this->quotation(['status' => QuotationStatus::COMPLETED, 'name' => 'Pelanggan Selesai']);
        $produksi = $this->quotation(['status' => 'production', 'name' => 'Pelanggan Produksi']);

        // Tanpa filter, keduanya tampil.
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee($selesai->tracking_number)
            ->assertSee($produksi->tracking_number);

        // Dengan filter, hanya yang berstatus tersebut.
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['status' => 'production']))
            ->assertOk()
            ->assertSee($produksi->tracking_number)
            ->assertDontSee($selesai->tracking_number);
    }

    public function test_filter_status_tidak_mengubah_kartu_statistik(): void
    {
        $this->quotation(['status' => QuotationStatus::COMPLETED, 'estimated_price' => 400000]);
        $this->quotation(['status' => 'production']);

        // Kartu statistik memotret keseluruhan, jadi menyaring daftar penawaran
        // tidak boleh ikut menggeser angka pendapatan maupun total penawaran.
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['status' => 'production']))
            ->assertOk()
            ->assertSee('Rp400.000', false)
            ->assertSee('Total Penawaran');
    }

    public function test_status_yang_tidak_dikenal_diabaikan(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['status' => 'status-karangan']))
            ->assertOk()
            ->assertSee($quotation->tracking_number)
            ->assertSee('Seluruh status');
    }

    public function test_sebaran_status_menautkan_ke_filternya(): void
    {
        $this->quotation(['status' => 'production']);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.dashboard', ['status' => 'production']).'#penawaran-terbaru', false);
    }

    public function test_tautan_lihat_semua_membawa_filter_ke_halaman_penawaran(): void
    {
        $this->quotation(['status' => 'production']);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['status' => 'production']))
            ->assertOk()
            ->assertSee(route('admin.quotations.index', ['status' => 'production']), false);
    }

    public function test_pendapatan_hanya_menghitung_penawaran_yang_selesai(): void
    {
        $this->quotation(['status' => QuotationStatus::COMPLETED, 'estimated_price' => 400000]);
        $this->quotation(['status' => 'production', 'estimated_price' => 900000]);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Rp400.000', false)
            ->assertDontSee('Rp1.300.000', false);
    }

    /* --------------------------------------------------- manajemen user --- */

    public function test_admin_dapat_melihat_daftar_user(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Bandung')
            ->assertSee(route('admin.users.show', $this->customer))
            // Akun admin tidak ikut terdaftar sebagai pelanggan.
            ->assertDontSee(route('admin.users.show', $this->admin));
    }

    public function test_detail_user_menampilkan_riwayat_penawarannya(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->customer))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('081234567890')
            ->assertSee($quotation->tracking_number)
            ->assertSee('Riwayat Penawaran');
    }

    public function test_akun_admin_tidak_dapat_dibuka_sebagai_detail_user(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->admin))
            ->assertNotFound();
    }

    public function test_pencarian_user_bekerja(): void
    {
        User::factory()->create(['name' => 'Citra Dewi', 'city' => 'Surabaya']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['q' => 'Surabaya']))
            ->assertOk()
            ->assertSee('Citra Dewi')
            ->assertDontSee('Budi Santoso');
    }

    /* ------------------------------------------ persetujuan pembatalan --- */

    private function requestedCancellation(): QuotationRequest
    {
        $quotation = $this->quotation(['status' => QuotationStatus::REVIEWING]);

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.cancel', $quotation), ['reason' => 'Proyek ditunda.']);

        return $quotation->fresh();
    }

    public function test_admin_dapat_menyetujui_permintaan_pembatalan(): void
    {
        $quotation = $this->requestedCancellation();

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.cancellation.approve', $quotation), ['note' => 'Sudah dikonfirmasi via WhatsApp.'])
            ->assertRedirect();

        $quotation->refresh();

        $this->assertSame(QuotationStatus::CANCELLATION_APPROVED, $quotation->status);
        $this->assertSame('Pembatalan Disetujui', $quotation->status_label);
        $this->assertNotNull($quotation->cancellation_resolved_at);
        $this->assertSame('Sudah dikonfirmasi via WhatsApp.', $quotation->cancellation_admin_note);

        // Pelanggan menerima pemberitahuan keputusannya.
        $notification = $this->customer->notifications()->first();
        $this->assertSame('cancellation.decided', $notification->data['type']);
        $this->assertSame('Permintaan pembatalan diterima', $notification->data['title']);
    }

    public function test_admin_dapat_menolak_dan_penawaran_kembali_ke_tahap_semula(): void
    {
        $quotation = $this->requestedCancellation();

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.cancellation.reject', $quotation), ['note' => 'Produksi sudah berjalan.'])
            ->assertRedirect();

        $quotation->refresh();

        $this->assertSame(QuotationStatus::REVIEWING, $quotation->status);
        $this->assertNull($quotation->status_before_cancellation);

        // Riwayat mencatat penolakan sekaligus tahap yang dilanjutkan.
        $statuses = $quotation->histories()->pluck('status')->all();
        $this->assertContains(QuotationStatus::CANCELLATION_REJECTED, $statuses);

        $notification = $this->customer->notifications()->first();
        $this->assertSame('Permintaan pembatalan ditolak', $notification->data['title']);
    }

    public function test_keputusan_pembatalan_ditolak_bila_tidak_ada_pengajuan(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.cancellation.approve', $quotation))
            ->assertSessionHas('error');

        $this->assertSame(QuotationStatus::RECEIVED, $quotation->fresh()->status);
    }

    public function test_pengajuan_pembatalan_tampil_pada_daftar_penawaran(): void
    {
        $this->requestedCancellation();

        $this->actingAs($this->admin)
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->assertSee('permintaan pembatalan menunggu persetujuan');
    }

    /* ------------------------------------------------ notifikasi admin --- */

    public function test_notifikasi_admin_dapat_dibuka_dan_ditandai_terbaca(): void
    {
        $this->requestedCancellation();

        $this->actingAs($this->admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Permintaan pembatalan dari Budi Santoso');

        $this->actingAs($this->admin)
            ->getJson(route('admin.notifications.latest'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($this->admin)
            ->post(route('admin.notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
    }

    public function test_notifikasi_akun_lain_tidak_dapat_ditandai(): void
    {
        $this->requestedCancellation();

        $notification = $this->admin->notifications()->first();

        $this->actingAs($this->customer)
            ->post(route('dashboard.notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertSame(1, $this->admin->unreadNotifications()->count());
    }

    /* ------------------------------------------------ status & whatsapp --- */

    public function test_status_pembatalan_tidak_dapat_dipasang_lewat_dropdown_status(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin)
            ->patch(route('admin.quotations.update', $quotation), [
                'status' => QuotationStatus::CANCELLATION_APPROVED,
            ])->assertSessionHasErrors('status');

        $this->assertSame(QuotationStatus::RECEIVED, $quotation->fresh()->status);
    }

    public function test_detail_penawaran_menyediakan_tombol_whatsapp_pelanggan(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin)
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Hubungi Pelanggan via WhatsApp')
            ->assertSee('https://wa.me/6281234567890');
    }
}
