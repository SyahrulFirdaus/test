<?php

namespace Tests\Feature;

use App\Models\PaymentInstallment;
use App\Models\PaymentTerm;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\QuotationStatusUpdated;
use App\Services\PaymentTermFlow;
use App\Support\ActivityAction;
use App\Support\AdminPermission;
use App\Support\CustomerType;
use App\Support\InstallmentStatus;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lokasi verifikasi pembayaran.
 *
 * Aturannya satu kalimat: SETIAP pembayaran punya tepat satu tempat
 * verifikasi, dan yang menentukan tempatnya adalah bentuk pembayarannya.
 *
 *   sekali bayar   → Penawaran › Detail Penawaran
 *   bertahap       → Pembayaran › Verifikasi Pembayaran, per termin
 *
 * Yang memisahkan bukan tipe akunnya: pelanggan Business yang membayar sekali
 * tetap diputuskan dari Detail Penawaran, karena pembayarannya memang satu.
 * Yang membutuhkan antrean tersendiri hanyalah penawaran yang benar-benar
 * punya jadwal termin.
 */
class PaymentVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $personal;

    private User $business;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->personal = User::factory()->create([
            'name' => 'Andi Saputra',
            'customer_type' => CustomerType::PERSONAL,
        ]);

        $this->business = User::factory()->create([
            'name' => 'Budi Santoso',
            'customer_type' => CustomerType::BUSINESS,
        ]);

        $this->admin = User::factory()->admin()->create(['email' => 'admin@nusama3d.com']);
    }

    /** @param  array<string, mixed>  $overrides */
    private function quotation(array $overrides = [], ?User $owner = null): QuotationRequest
    {
        $owner ??= $this->personal;

        $quotation = QuotationRequest::create(array_merge([
            'user_id' => $owner->id,
            'tracking_number' => 'QTN-20260920-'.strtoupper(fake()->bothify('??####')),
            'name' => $owner->name,
            'email' => 'pelanggan@contoh.test',
            'whatsapp' => '081234567890',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA',
            'estimated_minutes' => 200,
            'estimated_cost' => 400000,
            'estimated_price' => 425000,
            'status' => QuotationStatus::AWAITING_PAYMENT,
            'payment_due_at' => now()->addHours(24),
        ], $overrides));

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 1,
            'estimated_minutes' => 200,
            'estimated_cost' => 400000,
            'estimated_price' => 425000,
        ]);

        return $quotation->load('items');
    }

    /** Penawaran Business dengan skema termin yang sudah disetujui. */
    private function approvedTerm(int $count = 3): PaymentTerm
    {
        $quotation = $this->quotation([
            'user_id' => $this->business->id,
            'name' => 'Budi Santoso',
            'company' => 'PT ABC Manufacturing',
            'estimated_cost' => 50000000,
            'estimated_price' => 50000000,
        ], $this->business);

        $quotation->items()->update(['estimated_cost' => 50000000, 'estimated_price' => 50000000]);

        $flow = app(PaymentTermFlow::class);

        return $flow->approve($flow->request($quotation->fresh(), $count, $this->business), $this->admin);
    }

    /** Unggah bukti pembayaran sebagai pelanggan. */
    private function uploadProof(QuotationRequest $quotation, User $owner): void
    {
        $this->actingAs($owner)
            ->post(route('dashboard.quotations.payment.store', $quotation), [
                'proof' => UploadedFile::fake()->create('bukti_transfer.jpg', 120, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();
    }

    /* ================================================== TEST 1 — B2C unggah === */

    public function test_1_bukti_pembayaran_b2c_masuk_pengecekan_pembayaran(): void
    {
        $quotation = $this->quotation();

        $this->uploadProof($quotation, $this->personal);

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->status);
        $this->assertNotNull($quotation->payment_proof_uploaded_at);
        $this->assertTrue($quotation->awaitsPaymentDecision());
    }

    /* ============================================ TEST 2 — tombol di detail === */

    public function test_2_detail_penawaran_menyediakan_tombol_verifikasi(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $this->actingAs($this->admin)
            ->get(route('admin.quotations.show', $quotation->fresh()))
            ->assertOk()
            ->assertSee('bukti_transfer.jpg')
            ->assertSee('Lihat Bukti Pembayaran')
            ->assertSee('Terima Pembayaran')
            ->assertSee('Tolak Pembayaran')
            ->assertSee('Alasan Penolakan');
    }

    /** Tanpa hak Verifikasi Pembayaran, tombolnya tidak ditawarkan. */
    public function test_2b_tanpa_hak_verifikasi_tombolnya_tidak_muncul(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $admin = User::factory()
            ->withPermissions([AdminPermission::QUOTATION_VIEW])
            ->create(['email' => 'lihat-saja@nusama3d.com']);

        $this->actingAs($admin)
            ->get(route('admin.quotations.show', $quotation->fresh()))
            ->assertOk()
            ->assertDontSee(route('admin.quotations.payment.accept', $quotation))
            ->assertSee('tidak memiliki hak Verifikasi Pembayaran');
    }

    /* ================================================= TEST 3 — terima B2C === */

    public function test_3_admin_menerima_pembayaran_b2c_dari_detail(): void
    {
        Notification::fake();

        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.payment.accept', $quotation))
            ->assertSessionHas('status');

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_RECEIVED, $quotation->status);
        $this->assertNotNull($quotation->payment_verified_at);
        $this->assertSame($this->admin->id, $quotation->payment_verified_by);
        $this->assertNull($quotation->payment_rejected_at);

        // Riwayat, jejak audit, dan pemberitahuan ke pelanggan ikut tercatat.
        $this->assertSame(
            QuotationStatus::PAYMENT_RECEIVED,
            $quotation->histories()->latest()->first()->status,
        );

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityAction::PAYMENT_APPROVE,
            'user_id' => $this->admin->id,
        ]);

        Notification::assertSentTo($this->personal, QuotationStatusUpdated::class);

        // Tahap berikutnya tetap milik alur produksi, bukan dipaksa di sini.
        $this->assertSame(QuotationStatus::PRODUCTION, QuotationStatus::next($quotation->status));
    }

    /** Alur berlanjut ke Diproses lewat Tindak Lanjut seperti biasa. */
    public function test_3b_status_berikutnya_mengikuti_workflow_existing(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $this->actingAs($this->admin)->post(route('admin.quotations.payment.accept', $quotation));

        $this->actingAs($this->admin)
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::PRODUCTION])
            ->assertSessionHasNoErrors();

        $this->assertSame(QuotationStatus::PRODUCTION, $quotation->fresh()->status);
    }

    /* ================================================== TEST 4 — tolak B2C === */

    public function test_4_admin_menolak_pembayaran_b2c_beserta_alasannya(): void
    {
        Notification::fake();

        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.payment.reject', $quotation), [
                'reason' => 'Nominal transfer tidak sesuai total penawaran.',
            ])
            ->assertSessionHas('status');

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_REJECTED, $quotation->status);
        $this->assertSame('Nominal transfer tidak sesuai total penawaran.', $quotation->payment_rejection_reason);
        $this->assertSame($this->admin->id, $quotation->payment_rejected_by);
        $this->assertNotNull($quotation->payment_rejected_at);

        // Penolakan BUKAN verifikasi: tanpa ini penawaran terbaca "Paid".
        $this->assertNull($quotation->payment_verified_at);

        Notification::assertSentTo($this->personal, QuotationStatusUpdated::class);
    }

    public function test_4b_penolakan_tanpa_alasan_ditolak(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.payment.reject', $quotation), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }

    /** Aturan existing tidak berubah: pelanggan boleh mengunggah ulang. */
    public function test_4c_pelanggan_dapat_mengunggah_ulang_setelah_ditolak(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.payment.reject', $quotation), ['reason' => 'Bukti tidak terbaca.']);

        $this->uploadProof($quotation->fresh(), $this->personal);

        $quotation->refresh();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->status);
        $this->assertNull($quotation->payment_rejection_reason);
        $this->assertNull($quotation->payment_rejected_at);
    }

    /* ========================================== TEST 5 — B2C bukan tugas menu === */

    public function test_5_pembayaran_b2c_tidak_mengantre_di_menu_pembayaran(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        /*
         * Antreannya kosong: tidak ada termin yang menunggu.
         *
         * Nomor penawarannya sendiri TIDAK dijadikan penanda — bukti yang baru
         * masuk memunculkan notifikasi untuk admin, dan lonceng di header
         * memang menyebut nomor itu. Yang diperiksa keadaan antreannya.
         */
        $this->actingAs($this->admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Tidak ada bukti pembayaran termin yang menunggu verifikasi')
            ->assertSee('diverifikasi dari Penawaran');

        // Dan endpoint lamanya memang sudah tidak ada.
        $this->assertFalse(Route::has('admin.payments.approve'));
        $this->assertFalse(Route::has('admin.payments.reject'));
    }

    /**
     * Pelanggan Business yang membayar SEKALI juga diputuskan dari Detail.
     *
     * Yang memisahkan tempat verifikasi adalah bentuk pembayarannya, bukan
     * tipe akunnya — kalau tidak, pembayaran tunggal milik Business akan
     * terdampar tanpa tempat keputusan sama sekali.
     */
    public function test_5b_business_tanpa_termin_tetap_diverifikasi_dari_detail(): void
    {
        $quotation = $this->quotation(['name' => 'Budi Santoso'], $this->business);
        $this->uploadProof($quotation, $this->business);

        $quotation->refresh();

        $this->assertTrue($quotation->verifiedFromDetail());

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.payment.accept', $quotation))
            ->assertSessionHas('status');

        $this->assertSame(QuotationStatus::PAYMENT_RECEIVED, $quotation->fresh()->status);
    }

    /* ============================================= TEST 6 — B2B di menu === */

    public function test_6_bukti_termin_b2b_mengantre_di_menu_pembayaran(): void
    {
        $term = $this->approvedTerm(3);
        $installment = $term->installments()->orderBy('installment_number')->first();

        app(PaymentTermFlow::class)->submitProof(
            $installment,
            UploadedFile::fake()->create('termin_1.jpg', 120, 'image/jpeg'),
            $this->business,
        );

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee($term->quotation->tracking_number)
            ->assertSee('Termin 1')
            ->assertSee('Terima Pembayaran')
            ->assertSee('Tolak Pembayaran');
    }

    /** Penawaran bertahap tidak menawarkan keputusan di Detail Penawaran. */
    public function test_6b_penawaran_bertahap_tidak_diputuskan_dari_detail(): void
    {
        $term = $this->approvedTerm(3);
        $quotation = $term->quotation;

        $this->actingAs($this->admin)
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Payment Term')
            ->assertDontSee(route('admin.quotations.payment.accept', $quotation));

        // Endpoint-nya pun menolak, bukan hanya tombolnya yang disembunyikan.
        $this->actingAs($this->admin)
            ->post(route('admin.quotations.payment.accept', $quotation))
            ->assertSessionHas('error');

        $this->assertNull($quotation->fresh()->payment_verified_at);
    }

    /* ======================================== TEST 7 — termin berdiri sendiri === */

    public function test_7_menerima_termin_1_tidak_menyentuh_termin_lain(): void
    {
        $term = $this->approvedTerm(3);
        $installments = $term->installments()->orderBy('installment_number')->get();

        app(PaymentTermFlow::class)->submitProof(
            $installments[0],
            UploadedFile::fake()->create('termin_1.jpg', 120, 'image/jpeg'),
            $this->business,
        );

        $this->actingAs($this->admin)
            ->post(route('admin.payments.installments.approve', $installments[0]))
            ->assertSessionHas('status');

        $this->assertSame(InstallmentStatus::RECEIVED, $installments[0]->fresh()->status);

        // Termin 2 dan 3 belum tersentuh sama sekali.
        $this->assertNotSame(InstallmentStatus::RECEIVED, $installments[1]->fresh()->status);
        $this->assertNotSame(InstallmentStatus::RECEIVED, $installments[2]->fresh()->status);

        /*
         * Penawarannya BERPINDAH ke "Pembayaran Diterima" — dan itu memang
         * aturan Payment Term yang sudah berlaku, bukan efek samping: begitu
         * termin pertama lunas, pembayaran sudah berjalan sehingga pekerjaan
         * tidak lagi tertahan menunggu termin berikutnya. Lihat
         * App\Services\PaymentTermFlow::markQuotationPaymentReceived().
         */
        $this->assertSame(QuotationStatus::PAYMENT_RECEIVED, $term->quotation->fresh()->status);

        // Skemanya sendiri belum lunas: masih ada termin yang menunggu.
        $this->assertFalse($term->fresh()->isCompleted());
    }

    /* ============================================== TEST 8 — tanpa hak === */

    public function test_8_admin_tanpa_hak_verifikasi_ditolak(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $admin = User::factory()
            ->withPermissions([AdminPermission::QUOTATION_VIEW, AdminPermission::PAYMENT_VIEW])
            ->create(['email' => 'tanpa-verify@nusama3d.com']);

        $this->actingAs($admin)
            ->post(route('admin.quotations.payment.accept', $quotation))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.quotations.payment.reject', $quotation), ['reason' => 'x'])
            ->assertForbidden();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }

    public function test_8b_admin_tanpa_hak_verifikasi_tidak_dapat_memutuskan_termin(): void
    {
        $term = $this->approvedTerm(3);
        $installment = $term->installments()->orderBy('installment_number')->first();

        app(PaymentTermFlow::class)->submitProof(
            $installment,
            UploadedFile::fake()->create('termin_1.jpg', 120, 'image/jpeg'),
            $this->business,
        );

        $admin = User::factory()
            ->withPermissions([AdminPermission::PAYMENT_VIEW])
            ->create(['email' => 'lihat-pembayaran@nusama3d.com']);

        $this->actingAs($admin)
            ->post(route('admin.payments.installments.approve', $installment))
            ->assertForbidden();

        $this->assertNotSame(InstallmentStatus::RECEIVED, $installment->fresh()->status);
    }

    /* ============================================ TEST 9 — pelanggan ditolak === */

    public function test_9_pelanggan_tidak_dapat_memanggil_endpoint_verifikasi(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        // Wilayah /admin dijaga middleware `admin`: pelanggan dikembalikan ke
        // dashboardnya sendiri, tidak pernah sampai ke controllernya.
        $this->actingAs($this->personal)
            ->post(route('admin.quotations.payment.accept', $quotation))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($this->personal)
            ->post(route('admin.quotations.payment.reject', $quotation), ['reason' => 'lunas kok'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
        $this->assertNull($quotation->fresh()->payment_verified_at);
    }

    /* ==================================== TEST 10 — pembayaran milik orang lain === */

    public function test_10_pelanggan_tidak_dapat_menyentuh_pembayaran_orang_lain(): void
    {
        $milikOrangLain = $this->quotation([], $this->business);
        $this->uploadProof($milikOrangLain, $this->business);

        // Sebagai pelanggan lain: ditolak di gerbang wilayah admin.
        $this->actingAs($this->personal)
            ->post(route('admin.quotations.payment.accept', $milikOrangLain))
            ->assertRedirect(route('dashboard'));

        // Dan bukti pembayarannya pun bukan miliknya untuk dibuka.
        $this->actingAs($this->personal)
            ->get(route('dashboard.quotations.payment.proof', $milikOrangLain))
            ->assertNotFound();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $milikOrangLain->fresh()->status);
    }

    /* ========================================== keadaan yang tidak mungkin === */

    /** Bukti yang sudah diputuskan tidak dapat diputuskan dua kali. */
    public function test_keputusan_ganda_ditolak(): void
    {
        $quotation = $this->quotation();
        $this->uploadProof($quotation, $this->personal);

        $this->actingAs($this->admin)->post(route('admin.quotations.payment.accept', $quotation));

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.payment.accept', $quotation))
            ->assertSessionHas('error');

        $this->assertSame(QuotationStatus::PAYMENT_RECEIVED, $quotation->fresh()->status);
    }

    /** Tanpa bukti yang diunggah, tidak ada yang dapat diverifikasi. */
    public function test_tanpa_bukti_tidak_dapat_diverifikasi(): void
    {
        $quotation = $this->quotation(['status' => QuotationStatus::PAYMENT_REVIEW]);

        $this->actingAs($this->admin)
            ->post(route('admin.quotations.payment.accept', $quotation))
            ->assertSessionHas('error');

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }

    /** Jenis akun tampil pada daftar penawaran, dibaca dari data registrasi. */
    public function test_daftar_penawaran_menampilkan_jenis_akun(): void
    {
        $this->quotation();
        $this->quotation(['name' => 'Budi Santoso', 'company' => 'PT ABC'], $this->business);

        $this->actingAs($this->admin)
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->assertSee('Jenis Akun')
            ->assertSee('Personal')
            ->assertSee('Business');
    }

    /** Termin milik Payment Term yang belum disetujui belum mengantre. */
    public function test_menu_pembayaran_hanya_memuat_termin_yang_berjalan(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Tidak ada bukti pembayaran termin yang menunggu verifikasi');

        $this->assertSame(0, PaymentInstallment::where('status', InstallmentStatus::VERIFICATION)->count());
    }
}
