<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alur status penawaran sesudah disederhanakan.
 *
 * Tiga tahap dihapus:
 *   - "Menunggu Review" (`received`) — penawaran baru langsung masuk
 *     "File Sedang Direview";
 *   - "Menunggu Persetujuan Penawaran" (`awaiting_approval`);
 *   - "Penawaran Dikirim" (`quote_sent`) yang sempat menggantikannya.
 *
 * Sesudah review, pelanggan langsung masuk "Menunggu Pembayaran".
 *
 * Yang dijaga di sini bukan hanya daftarnya, melainkan bahwa ketiganya benar-
 * benar tidak dapat lagi muncul: tidak pada timeline, tidak pada dropdown
 * admin, tidak pada filter, dan tidak sebagai status yang dapat dipasang.
 */
class QuotationStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /* ------------------------------------------------------- daftar status --- */

    public function test_ketiga_status_lama_tidak_ada_lagi(): void
    {
        foreach (['received', 'awaiting_approval', 'quote_sent'] as $status) {
            $this->assertFalse(QuotationStatus::exists($status), $status.' seharusnya sudah tidak ada');
            $this->assertNotContains($status, QuotationStatus::keys());
            $this->assertNotContains($status, QuotationStatus::flowKeys());
            $this->assertArrayNotHasKey($status, QuotationStatus::options());
            $this->assertArrayNotHasKey($status, QuotationStatus::manualOptions());
        }

        // Labelnya pun tidak lagi terdaftar di mana pun.
        $labels = array_values(QuotationStatus::options());
        $this->assertNotContains('Menunggu Review', $labels);
        $this->assertNotContains('Menunggu Persetujuan Penawaran', $labels);
        $this->assertNotContains('Penawaran Dikirim', $labels);
    }

    public function test_urutan_alur_baru(): void
    {
        $this->assertSame([
            'reviewing',
            'awaiting_payment',
            'payment_review',
            'payment_received',
            'production',
            'quality_control',
            'ready_to_ship',
            'completed',
        ], QuotationStatus::flowKeys());

        $this->assertSame('reviewing', QuotationStatus::first());
        $this->assertSame('File Sedang Direview', QuotationStatus::label(QuotationStatus::REVIEWING));
        $this->assertSame('Menunggu Pembayaran', QuotationStatus::label(QuotationStatus::AWAITING_PAYMENT));
    }

    /** Pembatalan tetap dipertahankan seperti sebelumnya. */
    public function test_status_pembatalan_tetap_ada(): void
    {
        foreach ([
            QuotationStatus::CANCELLED_BY_USER,
            QuotationStatus::CANCELLATION_REQUESTED,
            QuotationStatus::CANCELLATION_APPROVED,
            QuotationStatus::CANCELLATION_REJECTED,
            QuotationStatus::PAYMENT_EXPIRED,
        ] as $status) {
            $this->assertTrue(QuotationStatus::exists($status));
        }
    }

    /* ---------------------------------------------------- penawaran baru --- */

    public function test_penawaran_baru_langsung_berstatus_file_sedang_direview(): void
    {
        $user = User::factory()->create([
            'name' => 'Rani Prameswari',
            'email' => 'rani@contoh.test',
            'phone' => '081211112222',
        ]);

        $this->actingAs($user)
            ->postJson(route('quotations.store'), [
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => (string) $user->phone,
                'items' => [[
                    'model' => UploadedFile::fake()->createWithContent('bracket.stl', 'solid test'),
                    'quantity' => 1,
                    'technology' => 'FDM',
                    'material' => 'PLA Plus Standart ESUN',
                    'model_volume_cm3' => 100,
                    'analysis_status' => QuotationRequest::ANALYSIS_READY,
                    'model_stats' => json_encode(['dimensions' => ['x' => 100, 'y' => 80, 'z' => 50]]),
                ]],
            ])
            ->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertSame(QuotationStatus::REVIEWING, $quotation->status);

        // Riwayat pertamanya pun langsung tahap review, bukan antrean sebelumnya.
        $this->assertSame(QuotationStatus::REVIEWING, $quotation->histories()->first()->status);
        $this->assertSame(1, $quotation->histories()->count());
    }

    /* ------------------------------------------------------------ timeline --- */

    public function test_timeline_dimulai_dari_file_sedang_direview(): void
    {
        $timeline = QuotationStatus::timeline(QuotationStatus::REVIEWING);

        $this->assertSame('reviewing', $timeline[0]['key']);
        $this->assertSame('current', $timeline[0]['state']);
        $this->assertSame('awaiting_payment', $timeline[1]['key']);

        // Tidak ada satu pun langkah "done" sebelum tahap pertama.
        $this->assertSame([], array_filter($timeline, fn (array $step) => $step['state'] === 'done'));
    }

    public function test_halaman_tracking_tidak_menyebut_status_lama(): void
    {
        $quotation = $this->penawaran(QuotationStatus::REVIEWING);

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('File Sedang Direview')
            ->assertDontSee('Menunggu Review')
            ->assertDontSee('Menunggu Persetujuan Penawaran')
            ->assertDontSee('Penawaran Dikirim');
    }

    /* --------------------------------------------------------------- admin --- */

    public function test_dropdown_dan_filter_admin_tidak_memuat_status_lama(): void
    {
        $quotation = $this->penawaran(QuotationStatus::REVIEWING);
        $admin = User::factory()->admin()->create();

        foreach ([
            route('admin.quotations.index'),
            route('admin.quotations.show', $quotation),
            route('admin.dashboard'),
        ] as $url) {
            $this->actingAs($admin)->get($url)
                ->assertOk()
                ->assertDontSee('Menunggu Review')
                ->assertDontSee('Menunggu Persetujuan Penawaran')
                ->assertDontSee('Penawaran Dikirim')
                ->assertDontSee('value="received"', false)
                ->assertDontSee('value="awaiting_approval"', false);
        }
    }

    public function test_admin_tidak_dapat_memasang_status_yang_sudah_dihapus(): void
    {
        $quotation = $this->penawaran(QuotationStatus::REVIEWING);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.quotations.update', $quotation), ['status' => 'awaiting_approval'])
            ->assertSessionHasErrors('status');

        $this->assertSame(QuotationStatus::REVIEWING, $quotation->fresh()->status);
    }

    /* ------------------------------------------------------- dapat diubah --- */

    /**
     * Tahap yang isinya masih boleh diubah pemiliknya ikut bergeser ke tahap
     * pertama yang baru — kalau tidak, Edit Specification tidak akan pernah
     * dapat dibuka lagi.
     */
    public function test_tahap_pertama_masih_dapat_diubah_pemiliknya(): void
    {
        $this->assertTrue(QuotationStatus::isEditable(QuotationStatus::REVIEWING));
        $this->assertFalse(QuotationStatus::isEditable(QuotationStatus::AWAITING_PAYMENT));

        $quotation = $this->penawaran(QuotationStatus::REVIEWING);

        $this->actingAs($quotation->user)
            ->get(route('dashboard.quotations.edit', $quotation))
            ->assertOk();
    }

    /* --------------------------------------------- alur berurutan (step) --- */

    /**
     * Dari tiap tahap hanya ada dua pilihan: bertahan di tahap sekarang, atau
     * maju tepat satu langkah.
     */
    public function test_pilihan_status_hanya_tahap_sekarang_dan_satu_tahap_sesudahnya(): void
    {
        $this->assertSame(
            ['reviewing', 'awaiting_payment'],
            QuotationStatus::selectableFrom(QuotationStatus::REVIEWING)
        );

        $this->assertSame(
            ['awaiting_payment', 'payment_review'],
            QuotationStatus::selectableFrom(QuotationStatus::AWAITING_PAYMENT)
        );

        // Tahap terakhir tidak punya lanjutan.
        $this->assertSame([QuotationStatus::COMPLETED], QuotationStatus::selectableFrom(QuotationStatus::COMPLETED));
        $this->assertNull(QuotationStatus::next(QuotationStatus::COMPLETED));
    }

    /** Seluruh tahap tetap terlihat; yang membedakan hanya keadaannya. */
    public function test_keadaan_tiap_tahap_pada_dropdown(): void
    {
        $choices = collect(QuotationStatus::manualChoices(QuotationStatus::PAYMENT_REVIEW))->keyBy('key');

        $this->assertCount(8, $choices);

        $this->assertSame('done', $choices['reviewing']['state']);
        $this->assertSame('done', $choices['awaiting_payment']['state']);
        $this->assertSame('current', $choices['payment_review']['state']);
        $this->assertSame('next', $choices['payment_received']['state']);
        $this->assertSame('locked', $choices['production']['state']);
        $this->assertSame('locked', $choices['completed']['state']);

        // Hanya tahap sekarang dan tahap berikutnya yang dapat dipilih — tahap
        // yang sudah dilewati tetap tampil, tetapi ikut terkunci.
        $this->assertSame(
            ['payment_review', 'payment_received'],
            $choices->filter(fn (array $choice) => $choice['selectable'])->keys()->all()
        );
    }

    /**
     * Bukti pembayaran yang ditolak tidak melempar penawaran keluar dari alur:
     * tahapnya kembali dihitung dari "Menunggu Pembayaran".
     */
    public function test_pembayaran_ditolak_melanjutkan_alur_dari_menunggu_pembayaran(): void
    {
        $this->assertSame(
            ['awaiting_payment', 'payment_review'],
            QuotationStatus::selectableFrom(QuotationStatus::PAYMENT_REJECTED)
        );
    }

    /** Pengajuan pembatalan meneruskan alur dari tahap yang sedang dijalani. */
    public function test_pengajuan_pembatalan_memakai_tahap_sebelumnya_sebagai_acuan(): void
    {
        $this->assertSame(
            ['production', 'quality_control'],
            QuotationStatus::selectableFrom(QuotationStatus::CANCELLATION_REQUESTED, QuotationStatus::PRODUCTION)
        );
    }

    public function test_dropdown_admin_mengunci_tahap_yang_belum_waktunya(): void
    {
        $quotation = $this->penawaran(QuotationStatus::REVIEWING);

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->getContent();

        // Tahap sekarang dan tahap berikutnya terbuka.
        $this->assertDoesNotMatchRegularExpression('/value="reviewing"\s+disabled/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="awaiting_payment"\s+disabled/', $html);

        // Tahap 3-8 terkunci, dan kuncinya terbaca di dropdown.
        foreach (['payment_review', 'payment_received', 'production', 'quality_control', 'ready_to_ship', 'completed'] as $status) {
            $this->assertMatchesRegularExpression('/value="'.$status.'"\s+disabled/', $html);
        }

        $this->assertStringContainsString('terkunci', $html);
    }

    /** Tahap yang sudah dilewati tetap tampil, tetapi tidak dapat dipilih ulang. */
    public function test_tahap_yang_sudah_dilewati_tampil_namun_terkunci(): void
    {
        $quotation = $this->penawaran(QuotationStatus::AWAITING_PAYMENT);

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('File Sedang Direview', $html);
        $this->assertMatchesRegularExpression('/value="reviewing"\s+disabled/', $html);
        $this->assertStringContainsString('sudah dilewati', $html);
    }

    /**
     * Batasannya dijaga di server, bukan hanya di dropdown: kiriman yang dibuat
     * sendiri ke endpoint pun tidak dapat melompati tahap.
     */
    public function test_admin_tidak_dapat_melompati_tahap_lewat_request_langsung(): void
    {
        $quotation = $this->penawaran(QuotationStatus::REVIEWING);
        $admin = User::factory()->admin()->create();

        foreach ([
            QuotationStatus::PAYMENT_REVIEW,
            QuotationStatus::PAYMENT_RECEIVED,
            QuotationStatus::PRODUCTION,
            QuotationStatus::COMPLETED,
        ] as $status) {
            $this->actingAs($admin)
                ->patch(route('admin.quotations.update', $quotation), ['status' => $status])
                ->assertSessionHasErrors('status');

            $this->assertSame(QuotationStatus::REVIEWING, $quotation->fresh()->status);
        }
    }

    public function test_admin_tidak_dapat_mundur_ke_tahap_sebelumnya(): void
    {
        $quotation = $this->penawaran(QuotationStatus::AWAITING_PAYMENT);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::REVIEWING])
            ->assertSessionHasErrors('status');

        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $quotation->fresh()->status);
    }

    /**
     * Tahap berikutnya baru terbuka sesudah tahap sekarang benar-benar
     * tersimpan — inti dari alur selangkah demi selangkah.
     */
    public function test_tahap_berikutnya_terbuka_setelah_perubahan_tersimpan(): void
    {
        $quotation = $this->penawaran(QuotationStatus::REVIEWING);
        $admin = User::factory()->admin()->create();

        // Sebelum disimpan, "Pengecekan Pembayaran" masih tertutup.
        $this->actingAs($admin)
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::PAYMENT_REVIEW])
            ->assertSessionHasErrors('status');

        // Maju satu tahap.
        $this->actingAs($admin)
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::AWAITING_PAYMENT])
            ->assertSessionHasNoErrors();

        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $quotation->fresh()->status);

        // Barulah tahap sesudahnya dapat dipasang.
        $this->actingAs($admin)
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::PAYMENT_REVIEW])
            ->assertSessionHasNoErrors();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }

    /** Seluruh delapan tahap dapat ditempuh berurutan sampai "Selesai". */
    public function test_seluruh_alur_dapat_ditempuh_satu_tahap_sekali_simpan(): void
    {
        $quotation = $this->penawaran(QuotationStatus::REVIEWING);
        $admin = User::factory()->admin()->create();

        foreach (QuotationStatus::flowKeys() as $status) {
            $this->actingAs($admin)
                ->patch(route('admin.quotations.update', $quotation), ['status' => $status])
                ->assertSessionHasNoErrors();

            $this->assertSame($status, $quotation->fresh()->status);
        }

        $this->assertSame(QuotationStatus::COMPLETED, $quotation->fresh()->status);

        // Di ujung alur tidak ada lagi tahap yang dapat dipasang.
        $this->assertNull(QuotationStatus::next(QuotationStatus::COMPLETED));
    }

    /**
     * Kunci dropdown mengikuti status yang tersimpan, jadi menyegarkan halaman
     * tidak pernah membuka tahap yang belum waktunya.
     */
    public function test_kunci_dropdown_mengikuti_status_tersimpan_setelah_refresh(): void
    {
        $quotation = $this->penawaran(QuotationStatus::REVIEWING);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::AWAITING_PAYMENT])
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($admin)
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->getContent();

        // Tahap 3 kini terbuka, tahap 4 dan sesudahnya masih terkunci.
        $this->assertDoesNotMatchRegularExpression('/value="payment_review"\s+disabled/', $html);
        $this->assertMatchesRegularExpression('/value="payment_received"\s+disabled/', $html);

        // Yang terpilih tetap tahap yang sedang berjalan, bukan tahap berikutnya.
        $this->assertMatchesRegularExpression('/value="awaiting_payment"[^>]*selected/', $html);
    }

    private function penawaran(string $status): QuotationRequest
    {
        $user = User::factory()->create();

        $quotation = QuotationRequest::create([
            'user_id' => $user->id,
            'tracking_number' => 'QT-FLOW01',
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '081211112222',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
            'estimated_price' => 300000,
            'status' => $status,
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 1,
            'scale_percent' => 100,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'infill_density' => 0.2,
            'model_volume_cm3' => 120,
            'estimated_weight_g' => 100,
            'support_weight_g' => 0,
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
        ]);

        $quotation->recordHistory($status, 'Permintaan penawaran berhasil dikirim.');

        return $quotation->fresh()->load('items');
    }
}
