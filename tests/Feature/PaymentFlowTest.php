<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\PaymentProofUploaded;
use App\Notifications\QuotationStatusUpdated;
use App\Services\PaymentFlow;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur pembayaran: jendela 24 jam, unggah bukti, verifikasi admin, dan
 * pembatalan otomatis saat batas waktunya lewat.
 */
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->customer = User::factory()->create(['name' => 'Andi Saputra']);
        $this->admin = User::factory()->admin()->create();
    }

    private function quotation(array $overrides = []): QuotationRequest
    {
        $quotation = QuotationRequest::create(array_merge([
            'user_id' => $this->customer->id,
            'tracking_number' => 'QTN-20260801-'.strtoupper(fake()->bothify('??####')),
            'name' => 'Andi Saputra',
            'email' => 'andi@contoh.test',
            'whatsapp' => '081234567890',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-08/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA',
            'estimated_minutes' => 200,
            'estimated_cost' => 150000,
            'estimated_price' => 175000,
            'status' => QuotationStatus::AWAITING_PAYMENT,
            'payment_due_at' => now()->addHours(24),
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
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 1,
            'estimated_minutes' => 200,
            'estimated_cost' => 150000,
        ]);

        return $quotation->load('items');
    }

    /* ------------------------------------------------ halaman pembayaran --- */

    public function test_halaman_pembayaran_menampilkan_rekening_countdown_dan_form_unggah(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.payment', $quotation))
            ->assertOk()
            ->assertSee($quotation->tracking_number)
            ->assertSee('Rp175.000')
            ->assertSee('Bank Mandiri')
            ->assertSee('PT. Nusantara Addictive Manufactur')
            ->assertSee('156 00 2510878 0')
            ->assertSee('Sisa Waktu Pembayaran')
            ->assertSee('Upload Bukti Pembayaran')
            ->assertSee('Pembayaran harus dilakukan dalam waktu 24 jam.');
    }

    public function test_halaman_pembayaran_milik_akun_lain_tidak_dapat_dibuka(): void
    {
        $quotation = $this->quotation(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.payment', $quotation))
            ->assertNotFound();
    }

    public function test_penawaran_di_luar_tahap_pembayaran_dialihkan_ke_detail(): void
    {
        $quotation = $this->quotation(['status' => QuotationStatus::REVIEWING]);

        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.payment', $quotation))
            ->assertRedirect(route('dashboard.quotations.show', $quotation));
    }

    /* --------------------------------------------------- unggah bukti --- */

    public function test_bukti_pembayaran_tersimpan_dan_status_menjadi_pengecekan_pembayaran(): void
    {
        Notification::fake();

        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.payment.store', $quotation), [
                'proof' => UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            ])
            ->assertRedirect(route('dashboard.quotations.payment', $quotation))
            ->assertSessionHas('status', 'Bukti pembayaran berhasil diunggah. Mohon tunggu proses verifikasi dari Admin.');

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->status);
        $this->assertSame('transfer.jpg', $quotation->payment_proof_name);
        $this->assertNotNull($quotation->payment_proof_uploaded_at);
        Storage::disk('local')->assertExists($quotation->payment_proof_path);

        // Riwayatnya ikut tercatat agar terbaca di halaman tracking.
        $this->assertTrue($quotation->histories()->where('status', QuotationStatus::PAYMENT_REVIEW)->exists());

        Notification::assertSentTo($this->admin, PaymentProofUploaded::class);
    }

    public function test_format_berkas_bukti_pembayaran_dibatasi(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.payment.store', $quotation), [
                'proof' => UploadedFile::fake()->create('catatan.docx', 100),
            ])
            ->assertSessionHasErrors('proof');

        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $quotation->fresh()->status);
    }

    public function test_bukti_tidak_dapat_diunggah_saat_sedang_diperiksa(): void
    {
        $quotation = $this->quotation(['status' => QuotationStatus::PAYMENT_REVIEW]);

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.payment.store', $quotation), [
                'proof' => UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            ])
            ->assertRedirect(route('dashboard.quotations.show', $quotation))
            ->assertSessionHas('error');
    }

    public function test_bukti_dapat_diunggah_ulang_setelah_ditolak(): void
    {
        $quotation = $this->quotation([
            'status' => QuotationStatus::PAYMENT_REJECTED,
            'payment_rejection_reason' => 'Nominal transfer tidak sesuai.',
        ]);

        $this->actingAs($this->customer)
            ->post(route('dashboard.quotations.payment.store', $quotation), [
                'proof' => UploadedFile::fake()->create('transfer.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->status);
        $this->assertNull($quotation->payment_rejection_reason);
    }

    /* ------------------------------------------------ verifikasi admin --- */

    public function test_admin_melihat_daftar_verifikasi_pembayaran(): void
    {
        $quotation = $this->quotation([
            'status' => QuotationStatus::PAYMENT_REVIEW,
            'payment_proof_path' => 'payments/2026-08/bukti.jpg',
            'payment_proof_name' => 'bukti.jpg',
            'payment_proof_uploaded_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Verifikasi Pembayaran')
            ->assertSee($quotation->tracking_number)
            ->assertSee('Andi Saputra')
            ->assertSee('Rp175.000')
            ->assertSee('Terima Pembayaran')
            ->assertSee('Tolak Pembayaran');
    }

    public function test_admin_dapat_menerima_pembayaran(): void
    {
        Notification::fake();

        $quotation = $this->quotation(['status' => QuotationStatus::PAYMENT_REVIEW]);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.approve', $quotation))
            ->assertSessionHas('status');

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_RECEIVED, $quotation->status);
        $this->assertNotNull($quotation->payment_verified_at);

        Notification::assertSentTo($this->customer, QuotationStatusUpdated::class);
    }

    public function test_admin_dapat_menolak_pembayaran_beserta_alasannya(): void
    {
        Notification::fake();

        $quotation = $this->quotation(['status' => QuotationStatus::PAYMENT_REVIEW]);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.reject', $quotation), [
                'reason' => 'Nominal transfer kurang dari total tagihan.',
            ])
            ->assertSessionHas('status');

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_REJECTED, $quotation->status);
        $this->assertSame('Nominal transfer kurang dari total tagihan.', $quotation->payment_rejection_reason);

        Notification::assertSentTo($this->customer, QuotationStatusUpdated::class);
    }

    public function test_penolakan_pembayaran_menuntut_alasan(): void
    {
        $quotation = $this->quotation(['status' => QuotationStatus::PAYMENT_REVIEW]);

        $this->actingAs($this->admin)
            ->post(route('admin.payments.reject', $quotation), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }

    public function test_pelanggan_tidak_dapat_membuka_menu_verifikasi_pembayaran(): void
    {
        $this->actingAs($this->customer)
            ->get(route('admin.payments.index'))
            ->assertRedirect(route('dashboard'));
    }

    /* -------------------------------------------------- batas waktu 24 jam --- */

    public function test_status_awaiting_payment_membuka_jendela_pembayaran_24_jam(): void
    {
        $quotation = $this->quotation([
            'status' => QuotationStatus::REVIEWING,
            'payment_due_at' => null,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.quotations.update', $quotation), [
                'status' => QuotationStatus::AWAITING_PAYMENT,
            ])
            ->assertSessionHasNoErrors();

        $quotation->refresh();

        $this->assertNotNull($quotation->payment_due_at);
        $this->assertEqualsWithDelta(24 * 60, now()->diffInMinutes($quotation->payment_due_at), 2);
    }

    public function test_penawaran_yang_melewati_batas_waktu_dibatalkan_otomatis(): void
    {
        Notification::fake();

        $quotation = $this->quotation(['payment_due_at' => now()->subMinute()]);

        // Membuka halamannya sudah cukup untuk memicu pemeriksaan batas waktu.
        $this->actingAs($this->customer)
            ->get(route('dashboard.quotations.payment', $quotation))
            ->assertRedirect(route('dashboard.quotations.show', $quotation));

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_EXPIRED, $quotation->status);
        $this->assertTrue($quotation->isClosed());
        $this->assertSame('Penawaran Dibatalkan (Expired)', $quotation->status_label);
    }

    public function test_perintah_terjadwal_membatalkan_penawaran_yang_kedaluwarsa(): void
    {
        Notification::fake();

        $expired = $this->quotation(['payment_due_at' => now()->subHour()]);
        $active = $this->quotation(['payment_due_at' => now()->addHour()]);

        $this->artisan('quotations:expire-payments')->assertSuccessful();

        $this->assertSame(QuotationStatus::PAYMENT_EXPIRED, $expired->fresh()->status);
        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $active->fresh()->status);
    }

    public function test_bukti_yang_sudah_masuk_tidak_ikut_kedaluwarsa(): void
    {
        // Setelah bukti diunggah, giliran admin yang memeriksa — penawarannya
        // tidak boleh dibatalkan sistem hanya karena verifikasinya lama.
        $quotation = $this->quotation([
            'status' => QuotationStatus::PAYMENT_REVIEW,
            'payment_due_at' => now()->subHour(),
            'payment_proof_path' => 'payments/2026-08/bukti.jpg',
            'payment_proof_uploaded_at' => now()->subHours(2),
        ]);

        $this->assertFalse(app(PaymentFlow::class)->expireIfDue($quotation));
        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }
}
