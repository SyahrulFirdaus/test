<?php

namespace Tests\Feature;

use App\Models\PaymentInstallment;
use App\Models\PaymentTerm;
use App\Models\PaymentTermSetting;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\InstallmentProofUploaded;
use App\Notifications\InstallmentUpdated;
use App\Notifications\PaymentTermDecided;
use App\Notifications\PaymentTermRequested;
use App\Services\PaymentTermFlow;
use App\Services\PaymentTermPlanner;
use App\Support\CustomerType;
use App\Support\InstallmentStatus;
use App\Support\PaymentTermStatus;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pembayaran bertahap untuk pelanggan Business: pemilihan skema, persetujuan
 * admin, pembagian nominal, aktivasi termin, verifikasi bukti, dan pengingat.
 *
 * Alur pembayaran sekali bayar milik pelanggan Personal ikut diuji di sini
 * hanya untuk memastikan fitur ini tidak mengubahnya.
 */
class PaymentTermTest extends TestCase
{
    use RefreshDatabase;

    private User $business;

    private User $personal;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->business = User::factory()->create([
            'name' => 'Budi Santoso',
            'customer_type' => CustomerType::BUSINESS,
        ]);

        $this->personal = User::factory()->create([
            'name' => 'Andi Saputra',
            'customer_type' => CustomerType::PERSONAL,
        ]);

        $this->admin = User::factory()->admin()->create();
    }

    private function quotation(array $overrides = []): QuotationRequest
    {
        $quotation = QuotationRequest::create(array_merge([
            'user_id' => $this->business->id,
            'tracking_number' => 'QTN-20260812-'.strtoupper(fake()->bothify('??####')),
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.test',
            'whatsapp' => '081234567890',
            'company' => 'PT ABC Manufacturing',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-08/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA',
            'estimated_minutes' => 200,
            'estimated_cost' => 30000000,
            'estimated_price' => 30000000,
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
            'estimated_cost' => 30000000,
            'estimated_price' => 30000000,
        ]);

        return $quotation->load('items');
    }

    /** Penawaran dengan skema termin yang sudah disetujui dan Termin 1 aktif. */
    private function approvedTerm(int $count = 3, array $overrides = []): PaymentTerm
    {
        $quotation = $this->quotation($overrides);

        $flow = app(PaymentTermFlow::class);
        $term = $flow->request($quotation, $count, $this->business);

        return $flow->approve($term, $this->admin);
    }

    /* ------------------------------------------- batas nominal & pilihan --- */

    public function test_pilihan_payment_term_mengikuti_batas_nominal(): void
    {
        $planner = app(PaymentTermPlanner::class);

        $this->assertSame([1], $planner->allowedCounts(4_000_000));
        $this->assertSame([1, 3], $planner->allowedCounts(10_000_000));
        $this->assertSame([1, 3, 4], $planner->allowedCounts(30_000_000));
        $this->assertSame([1, 3, 4, 5], $planner->allowedCounts(60_000_000));
    }

    public function test_pilihan_yang_dinonaktifkan_admin_tidak_ditawarkan(): void
    {
        PaymentTermSetting::where('installment_count', 4)->update(['enabled' => false]);

        $this->assertSame([1, 3, 5], app(PaymentTermPlanner::class)->allowedCounts(60_000_000));
    }

    public function test_admin_dapat_mengubah_batas_nominal_payment_term(): void
    {
        $setting = PaymentTermSetting::where('installment_count', 3)->first();

        $this->actingAs($this->admin)
            ->patch(route('admin.payment-terms.settings.update'), [
                'settings' => [
                    $setting->id => ['enabled' => '1', 'minimum_amount' => '1000000'],
                ],
            ])
            ->assertSessionHas('status');

        $this->assertSame('1000000.00', $setting->fresh()->minimum_amount);
        $this->assertContains(3, app(PaymentTermPlanner::class)->allowedCounts(1_000_000));
    }

    public function test_halaman_pilihan_skema_menampilkan_opsi_yang_tersedia(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->business)
            ->get(route('dashboard.quotations.payment-term', $quotation))
            ->assertOk()
            ->assertSee('Pilih Skema Pembayaran')
            ->assertSee('1x Pembayaran — Lunas')
            ->assertSee('3x Pembayaran — 3 Termin')
            ->assertSee('4x Pembayaran — 4 Termin')
            // Nilainya Rp30 juta, jadi 5x belum terbuka.
            ->assertDontSee('5x Pembayaran — 5 Termin');
    }

    /* --------------------------------------------- khusus akun Business --- */

    public function test_pelanggan_personal_tidak_dapat_memakai_payment_term(): void
    {
        $quotation = $this->quotation(['user_id' => $this->personal->id]);

        $this->actingAs($this->personal)
            ->get(route('dashboard.quotations.payment-term', $quotation))
            ->assertRedirect(route('dashboard.quotations.show', $quotation))
            ->assertSessionHas('error');

        $this->actingAs($this->personal)
            ->post(route('dashboard.quotations.payment-term.store', $quotation), ['installment_count' => 3])
            ->assertSessionHas('error');

        $this->assertNull($quotation->fresh()->paymentTerm);
    }

    public function test_pembayaran_personal_tetap_memakai_alur_sekali_bayar(): void
    {
        $quotation = $this->quotation([
            'user_id' => $this->personal->id,
            'estimated_price' => 175000,
        ]);

        // Halaman pembayaran lamanya tetap tampil apa adanya.
        $this->actingAs($this->personal)
            ->get(route('dashboard.quotations.payment', $quotation))
            ->assertOk()
            ->assertSee('Sisa Waktu Pembayaran')
            ->assertSee('Upload Bukti Pembayaran')
            ->assertDontSee('Jadwal Pembayaran');
    }

    public function test_skema_1x_business_memakai_halaman_pembayaran_biasa(): void
    {
        $quotation = $this->quotation();

        // Memilih 1x tetap tercatat sebagai payment term, tetapi pembayarannya
        // mengikuti alur sekali bayar yang sudah ada — bukan jadwal termin.
        $term = app(PaymentTermFlow::class)->request($quotation, 1, $this->business);
        app(PaymentTermFlow::class)->approve($term, $this->admin);

        $this->assertFalse($quotation->fresh()->usesInstallments());
        $this->assertCount(0, $term->fresh()->installments);

        $this->actingAs($this->business)
            ->get(route('dashboard.quotations.payment', $quotation))
            ->assertOk()
            ->assertSee('Sisa Waktu Pembayaran')
            ->assertSee('Upload Bukti Pembayaran');
    }

    public function test_skema_di_luar_batas_nominal_ditolak(): void
    {
        // Rp30 juta belum membuka pilihan 5x.
        $quotation = $this->quotation();

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.payment-term.store', $quotation), ['installment_count' => 5])
            ->assertSessionHasErrors('installment_count');

        $this->assertNull($quotation->fresh()->paymentTerm);
    }

    /* ------------------------------------------------ pengajuan & keputusan --- */

    public function test_pengajuan_payment_term_menunggu_persetujuan_admin(): void
    {
        Notification::fake();

        $quotation = $this->quotation();

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.payment-term.store', $quotation), ['installment_count' => 3])
            ->assertRedirect(route('dashboard.quotations.payment', $quotation))
            ->assertSessionHas('status');

        $term = $quotation->fresh()->paymentTerm;

        $this->assertNotNull($term);
        $this->assertSame(PaymentTermStatus::PENDING, $term->status);
        $this->assertSame(3, $term->installment_count);
        $this->assertSame('30000000.00', $term->total_amount);

        // Belum disetujui berarti jadwalnya belum terbentuk.
        $this->assertCount(0, $term->installments);

        Notification::assertSentTo($this->admin, PaymentTermRequested::class);
    }

    public function test_admin_menyetujui_dan_jadwal_termin_terbentuk(): void
    {
        Notification::fake();

        $quotation = $this->quotation();
        $term = app(PaymentTermFlow::class)->request($quotation, 3, $this->business);

        $this->actingAs($this->admin)
            ->post(route('admin.payment-terms.approve', $term))
            ->assertSessionHas('status');

        $term->refresh()->load('installments');

        $this->assertSame(PaymentTermStatus::APPROVED, $term->status);
        $this->assertCount(3, $term->installments);

        // Rp30.000.000 dibagi tiga tepat Rp10.000.000 per termin.
        $this->assertSame(
            ['10000000.00', '10000000.00', '10000000.00'],
            $term->installments->pluck('amount')->all()
        );

        // Hanya termin pertama yang aktif; sisanya menunggu giliran.
        $this->assertSame(InstallmentStatus::PENDING, $term->installments[0]->status);
        $this->assertSame(InstallmentStatus::INACTIVE, $term->installments[1]->status);
        $this->assertSame(InstallmentStatus::INACTIVE, $term->installments[2]->status);

        Notification::assertSentTo($this->business, PaymentTermDecided::class);
    }

    public function test_admin_menolak_payment_term_beserta_alasannya(): void
    {
        Notification::fake();

        $quotation = $this->quotation();
        $term = app(PaymentTermFlow::class)->request($quotation, 3, $this->business);

        $this->actingAs($this->admin)
            ->post(route('admin.payment-terms.reject', $term), [
                'reason' => 'Riwayat pembayaran belum mencukupi untuk cicilan.',
            ])
            ->assertSessionHas('status');

        $term->refresh();

        $this->assertSame(PaymentTermStatus::REJECTED, $term->status);
        $this->assertSame('Riwayat pembayaran belum mencukupi untuk cicilan.', $term->rejection_reason);
        $this->assertCount(0, $term->installments);

        Notification::assertSentTo($this->business, PaymentTermDecided::class);
    }

    public function test_payment_term_yang_sudah_disetujui_tidak_dapat_diubah_pelanggan(): void
    {
        $term = $this->approvedTerm();

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.payment-term.store', $term->quotation), ['installment_count' => 4])
            ->assertSessionHas('error');

        $this->assertSame(3, $term->fresh()->installment_count);
    }

    /* ------------------------------------------------- pembagian nominal --- */

    public function test_pembagian_termin_selalu_berjumlah_sama_dengan_total(): void
    {
        $planner = app(PaymentTermPlanner::class);

        // Nominal yang tidak habis dibagi tiga tetap harus berjumlah utuh.
        foreach ([30_000_000, 10_000_000, 7_777_777, 5_000_001] as $total) {
            foreach ([3, 4, 5] as $count) {
                $amounts = $planner->splitEvenly((float) $total, $count);

                $this->assertCount($count, $amounts);
                $this->assertTrue(
                    $planner->amountsMatchTotal((float) $total, $amounts),
                    "Pembagian {$total} menjadi {$count} termin tidak berjumlah sama dengan totalnya."
                );
            }
        }
    }

    public function test_admin_dapat_mengubah_persentase_termin(): void
    {
        $term = $this->approvedTerm();

        $this->actingAs($this->admin)
            ->patch(route('admin.payment-terms.schedule', $term), [
                'installments' => [
                    ['percentage' => 50, 'milestone' => 'DP / Order Confirmed', 'due_date' => '2026-08-20'],
                    ['percentage' => 30, 'milestone' => 'Produksi Dimulai', 'due_date' => '2026-08-27'],
                    ['percentage' => 20, 'milestone' => 'Sebelum Pengiriman', 'due_date' => '2026-09-03'],
                ],
            ])
            ->assertSessionHas('status');

        $installments = $term->fresh()->installments;

        $this->assertSame(
            ['15000000.00', '9000000.00', '6000000.00'],
            $installments->pluck('amount')->all()
        );
        $this->assertSame('Produksi Dimulai', $installments[1]->milestone);
        $this->assertSame('2026-09-03', $installments[2]->due_date->format('Y-m-d'));
    }

    public function test_pembagian_ditolak_bila_total_persentase_bukan_100(): void
    {
        $term = $this->approvedTerm();

        $this->actingAs($this->admin)
            ->patch(route('admin.payment-terms.schedule', $term), [
                'installments' => [
                    ['percentage' => 50, 'milestone' => 'DP', 'due_date' => null],
                    ['percentage' => 30, 'milestone' => 'Produksi', 'due_date' => null],
                    ['percentage' => 10, 'milestone' => 'Pelunasan', 'due_date' => null],
                ],
            ])
            ->assertSessionHas('error');

        // Tidak satu pun termin berubah.
        $this->assertSame(
            ['10000000.00', '10000000.00', '10000000.00'],
            $term->fresh()->installments->pluck('amount')->all()
        );
    }

    /* -------------------------------------------------- aktivasi termin --- */

    public function test_termin_yang_belum_aktif_tidak_dapat_dibayar(): void
    {
        $term = $this->approvedTerm();
        $second = $term->installments[1];

        $this->actingAs($this->business)
            ->get(route('dashboard.quotations.installments.show', [$term->quotation, $second]))
            ->assertRedirect(route('dashboard.quotations.payment', $term->quotation))
            ->assertSessionHas('error');

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.installments.store', [$term->quotation, $second]), [
                'proof' => UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            ])
            ->assertSessionHas('error');

        $this->assertSame(InstallmentStatus::INACTIVE, $second->fresh()->status);
    }

    public function test_termin_berikutnya_aktif_setelah_termin_sebelumnya_diterima(): void
    {
        Notification::fake();

        $term = $this->approvedTerm();
        $first = $term->installments[0];

        app(PaymentTermFlow::class)->submitProof(
            $first,
            UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            $this->business
        );

        $this->actingAs($this->admin)
            ->post(route('admin.payments.installments.approve', $first))
            ->assertSessionHas('status');

        $installments = $term->fresh()->installments;

        $this->assertSame(InstallmentStatus::RECEIVED, $installments[0]->status);
        $this->assertSame(InstallmentStatus::PENDING, $installments[1]->status);
        $this->assertSame(InstallmentStatus::INACTIVE, $installments[2]->status);

        // Termin pertama lunas menandai pembayaran penawaran sudah berjalan.
        $this->assertSame(QuotationStatus::PAYMENT_RECEIVED, $term->quotation->fresh()->status);

        Notification::assertSentTo($this->business, InstallmentUpdated::class);
    }

    public function test_seluruh_termin_lunas_menandai_pembayaran_selesai(): void
    {
        Notification::fake();

        $term = $this->approvedTerm();
        $flow = app(PaymentTermFlow::class);

        foreach ([0, 1, 2] as $index) {
            $installment = $term->fresh()->installments[$index];

            $flow->submitProof(
                $installment,
                UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
                $this->business
            );

            $flow->approveProof($installment->fresh(), $this->admin);
        }

        $term->refresh();

        $this->assertSame(PaymentTermStatus::COMPLETED, $term->status);
        $this->assertNotNull($term->completed_at);
        $this->assertSame(30000000.0, $term->load('installments')->paidAmount());
        $this->assertSame(0.0, $term->outstandingAmount());
    }

    public function test_admin_dapat_mengaktifkan_termin_lebih_awal(): void
    {
        Notification::fake();

        $term = $this->approvedTerm();
        $second = $term->installments[1];

        $this->actingAs($this->admin)
            ->post(route('admin.payment-terms.installments.activate', [$term, $second]))
            ->assertSessionHas('status');

        $this->assertSame(InstallmentStatus::PENDING, $second->fresh()->status);
    }

    /* --------------------------------------------------- bukti pembayaran --- */

    public function test_bukti_termin_tersimpan_dan_menunggu_verifikasi(): void
    {
        Notification::fake();

        $term = $this->approvedTerm();
        $first = $term->installments[0];

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.installments.store', [$term->quotation, $first]), [
                'proof' => UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            ])
            ->assertRedirect(route('dashboard.quotations.installments.show', [$term->quotation, $first]))
            ->assertSessionHas('status', 'Bukti pembayaran berhasil diunggah. Mohon tunggu verifikasi dari Admin.');

        $first->refresh();

        $this->assertSame(InstallmentStatus::VERIFICATION, $first->status);
        $this->assertSame('transfer.jpg', $first->latestProof->file_name);
        Storage::disk('local')->assertExists($first->latestProof->file_path);

        Notification::assertSentTo($this->admin, InstallmentProofUploaded::class);
    }

    public function test_format_bukti_termin_dibatasi(): void
    {
        $term = $this->approvedTerm();
        $first = $term->installments[0];

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.installments.store', [$term->quotation, $first]), [
                'proof' => UploadedFile::fake()->create('catatan.docx', 100),
            ])
            ->assertSessionHasErrors('proof');

        $this->assertSame(InstallmentStatus::PENDING, $first->fresh()->status);
    }

    public function test_bukti_baru_ditolak_selama_bukti_lama_masih_diperiksa(): void
    {
        $term = $this->approvedTerm();
        $first = $term->installments[0];

        app(PaymentTermFlow::class)->submitProof(
            $first,
            UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            $this->business
        );

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.installments.store', [$term->quotation, $first->fresh()]), [
                'proof' => UploadedFile::fake()->create('transfer-2.jpg', 120, 'image/jpeg'),
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, $first->fresh()->proofs()->count());
    }

    public function test_bukti_termin_yang_sudah_diterima_tidak_dapat_diunggah_ulang(): void
    {
        $term = $this->approvedTerm();
        $first = $term->installments[0];
        $flow = app(PaymentTermFlow::class);

        $flow->submitProof($first, UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'), $this->business);
        $flow->approveProof($first->fresh(), $this->admin);

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.installments.store', [$term->quotation, $first->fresh()]), [
                'proof' => UploadedFile::fake()->create('lagi.jpg', 120, 'image/jpeg'),
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, $first->fresh()->proofs()->count());
    }

    public function test_admin_dapat_menolak_bukti_termin_dan_pelanggan_mengunggah_ulang(): void
    {
        Notification::fake();

        $term = $this->approvedTerm();
        $first = $term->installments[0];

        app(PaymentTermFlow::class)->submitProof(
            $first,
            UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            $this->business
        );

        $this->actingAs($this->admin)
            ->post(route('admin.payments.installments.reject', $first->fresh()), [
                'reason' => 'Nominal transfer kurang dari nominal termin.',
            ])
            ->assertSessionHas('status');

        $first->refresh();

        $this->assertSame(InstallmentStatus::REJECTED, $first->status);
        $this->assertSame('Nominal transfer kurang dari nominal termin.', $first->latestProof->rejection_reason);

        // Unggahan ulang diterima dan tidak menimpa jejak bukti sebelumnya.
        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.installments.store', [$term->quotation, $first]), [
                'proof' => UploadedFile::fake()->create('transfer-benar.pdf', 200, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $first->fresh()->proofs()->count());
        $this->assertSame(InstallmentStatus::VERIFICATION, $first->fresh()->status);
    }

    public function test_penolakan_bukti_termin_menuntut_alasan(): void
    {
        $term = $this->approvedTerm();
        $first = $term->installments[0];

        app(PaymentTermFlow::class)->submitProof(
            $first,
            UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            $this->business
        );

        $this->actingAs($this->admin)
            ->post(route('admin.payments.installments.reject', $first->fresh()), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(InstallmentStatus::VERIFICATION, $first->fresh()->status);
    }

    public function test_termin_milik_akun_lain_tidak_dapat_dibuka(): void
    {
        $term = $this->approvedTerm();

        $this->actingAs($this->personal)
            ->get(route('dashboard.quotations.installments.show', [$term->quotation, $term->installments[0]]))
            ->assertNotFound();
    }

    /* --------------------------------------------- keterlambatan & pengingat --- */

    public function test_penawaran_bertahap_tidak_ikut_dibatalkan_jendela_24_jam(): void
    {
        // Batas 24 jam sekali bayar sudah lewat, tetapi penawaran bertahap
        // punya jadwal terminnya sendiri dan tidak boleh dibatalkan otomatis.
        $term = $this->approvedTerm(3, ['payment_due_at' => now()->subDay()]);

        $this->artisan('quotations:expire-payments')->assertSuccessful();

        $this->assertNotSame(QuotationStatus::PAYMENT_EXPIRED, $term->quotation->fresh()->status);
    }

    public function test_termin_yang_lewat_jatuh_tempo_ditandai_terlambat(): void
    {
        Notification::fake();

        $term = $this->approvedTerm();
        $term->installments[0]->forceFill(['due_date' => now()->subDays(2)])->save();

        $this->artisan('payments:installment-reminders')->assertSuccessful();

        $this->assertSame(InstallmentStatus::OVERDUE, $term->fresh()->installments[0]->status);

        // Terlambat membayar termin tidak membatalkan penawarannya.
        $this->assertFalse($term->quotation->fresh()->isClosed());
    }

    public function test_pengingat_jatuh_tempo_dikirim_sekali_per_tahap(): void
    {
        $term = $this->approvedTerm();
        $first = $term->installments[0];
        $first->forceFill(['due_date' => now()->addDays(3)])->save();

        // Dipasang setelah skemanya berjalan agar yang terhitung hanya
        // pengingatnya, bukan pemberitahuan "Termin 1 aktif" saat persetujuan.
        Notification::fake();

        $this->artisan('payments:installment-reminders')->assertSuccessful();

        $this->assertSame(3, $first->fresh()->last_reminder_days);
        Notification::assertSentToTimes($this->business, InstallmentUpdated::class, 1);

        // Dijalankan lagi pada hari yang sama tidak mengirim ulang tahap ini.
        $this->artisan('payments:installment-reminders')->assertSuccessful();
        Notification::assertSentToTimes($this->business, InstallmentUpdated::class, 1);
    }

    /* ---------------------------------------------------- dashboard admin --- */

    public function test_admin_melihat_daftar_payment_terms(): void
    {
        $quotation = $this->quotation();
        app(PaymentTermFlow::class)->request($quotation, 3, $this->business);

        $this->actingAs($this->admin)
            ->get(route('admin.payment-terms.index'))
            ->assertOk()
            ->assertSee('Payment Terms')
            ->assertSee($quotation->tracking_number)
            ->assertSee('PT ABC Manufacturing')
            ->assertSee('Rp30.000.000')
            ->assertSee('3x Pembayaran');
    }

    public function test_admin_melihat_antrean_verifikasi_bukti_termin(): void
    {
        $term = $this->approvedTerm();

        app(PaymentTermFlow::class)->submitProof(
            $term->installments[0],
            UploadedFile::fake()->create('transfer.jpg', 120, 'image/jpeg'),
            $this->business
        );

        $this->actingAs($this->admin)
            ->get(route('admin.payments.index', ['filter' => 'installments']))
            ->assertOk()
            ->assertSee($term->quotation->tracking_number)
            ->assertSee('Termin 1 dari 3')
            ->assertSee('Rp10.000.000')
            ->assertSee('Terima Pembayaran')
            ->assertSee('Tolak Pembayaran');
    }

    public function test_pelanggan_tidak_dapat_membuka_menu_payment_terms(): void
    {
        $this->actingAs($this->business)
            ->get(route('admin.payment-terms.index'))
            ->assertRedirect(route('dashboard'));
    }

    /* ------------------------------------------------ halaman pelanggan --- */

    public function test_timeline_pembayaran_tampil_untuk_pelanggan(): void
    {
        $term = $this->approvedTerm();

        $this->actingAs($this->business)
            ->get(route('dashboard.quotations.payment', $term->quotation))
            ->assertOk()
            ->assertSee('Jadwal Pembayaran')
            ->assertSee('3x Pembayaran')
            ->assertSee('Termin 1')
            ->assertSee('Termin 3')
            ->assertSee('Rp10.000.000')
            ->assertSee('Bayar Sekarang')
            ->assertSee('Belum Aktif');
    }

    public function test_halaman_pembayaran_termin_menampilkan_rekening_dan_form(): void
    {
        $term = $this->approvedTerm();

        $this->actingAs($this->business)
            ->get(route('dashboard.quotations.installments.show', [$term->quotation, $term->installments[0]]))
            ->assertOk()
            ->assertSee($term->quotation->tracking_number)
            ->assertSee('Termin 1 dari 3')
            ->assertSee('Rp10.000.000')
            // Rekeningnya sama dengan pembayaran sekali bayar yang sudah ada.
            ->assertSee('Bank Mandiri')
            ->assertSee('156 00 2510878 0')
            ->assertSee('Upload Bukti Pembayaran');
    }
}
