<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\ActivityAction;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tombol "Hapus" pada daftar Penawaran MEMBATALKAN, bukan menghapus.
 *
 * Penawaran yang dibatalkan tetap ada beserta seluruh isinya — akun pemilik,
 * berkas model, snapshot harga, riwayat status, dan Activity Log — karena
 * history dan audit justru bergantung padanya. Penghapusan permanen tetap ada
 * sebagai aksi tersendiri di halaman detail.
 */
class QuotationCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create(['email' => 'admin@nusama3d.com']);
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
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
            'technology' => 'FDM',
            'material' => 'PLA',
            'model_volume_cm3' => 120.5,
            'material_volume_cm3' => 54.2,
            'estimated_weight_g' => 67.2,
            'estimated_minutes' => 380,
            'estimated_cost' => 185000,
            'status' => QuotationStatus::REVIEWING,
        ], $overrides));

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-07/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['triangles' => 12, 'vertices' => 36],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
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
            'printer' => 'bambu-p1s',
            'printer_name' => 'Bambu Lab P1S',
            'infill_density' => 0.2,
            'infill_pattern' => 'gyroid',
        ]);

        return $quotation->load('items');
    }

    /* ------------------------------------------------- pembatalan satuan --- */

    public function test_admin_membatalkan_satu_penawaran_tanpa_menghapusnya(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('quotations/2026-07/bracket.stl', 'solid test');

        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.cancel', $quotation))
            ->assertRedirect();

        $quotation->refresh();

        $this->assertSame(QuotationStatus::CANCELLATION_APPROVED, $quotation->status);

        // Barisnya, modelnya, berkasnya, dan harganya tetap ada.
        $this->assertSame(1, QuotationRequest::count());
        $this->assertSame(1, $quotation->items()->count());
        $this->assertSame('185000.00', (string) $quotation->estimated_cost);
        Storage::disk('local')->assertExists('quotations/2026-07/bracket.stl');
    }

    public function test_superadmin_juga_dapat_membatalkan(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.quotations.cancel', $quotation))
            ->assertRedirect();

        $this->assertSame(QuotationStatus::CANCELLATION_APPROVED, $quotation->fresh()->status);
    }

    /** Tahap yang sedang dijalani disimpan, jadi timeline tracking tetap terbaca. */
    public function test_tahap_sebelum_pembatalan_ikut_tersimpan(): void
    {
        $quotation = $this->quotation(['status' => QuotationStatus::PRODUCTION]);

        $this->actingAs($this->admin())->post(route('admin.quotations.cancel', $quotation));

        $this->assertSame(QuotationStatus::PRODUCTION, $quotation->fresh()->status_before_cancellation);
    }

    public function test_pembatalan_tercatat_di_riwayat_dan_activity_log(): void
    {
        $quotation = $this->quotation();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.quotations.cancel', $quotation), [
            'note' => 'Pelanggan membatalkan lewat WhatsApp.',
        ]);

        $this->assertDatabaseHas('quotation_histories', [
            'quotation_request_id' => $quotation->id,
            'status' => QuotationStatus::CANCELLATION_APPROVED,
            'note' => 'Pelanggan membatalkan lewat WhatsApp.',
            'created_by' => $admin->name,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => ActivityAction::QUOTATION_CANCEL,
            'user_id' => $admin->id,
        ]);
    }

    public function test_pemilik_penawaran_diberi_tahu(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $quotation = $this->quotation(['user_id' => $owner->id]);

        $this->actingAs($this->admin())->post(route('admin.quotations.cancel', $quotation));

        Notification::assertSentTo($owner, \App\Notifications\QuotationStatusUpdated::class);
    }

    /** Membatalkan yang sudah dibatalkan tidak menimpa jejak pembatalan pertamanya. */
    public function test_penawaran_yang_sudah_dibatalkan_dilewati(): void
    {
        $quotation = $this->quotation(['status' => QuotationStatus::CANCELLED_BY_USER]);

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.cancel', $quotation))
            ->assertSessionHas('error');

        $this->assertSame(QuotationStatus::CANCELLED_BY_USER, $quotation->fresh()->status);
    }

    /* ------------------------------------------------- pembatalan massal --- */

    public function test_pembatalan_massal_memakai_jalur_yang_sama(): void
    {
        $satu = $this->quotation(['tracking_number' => 'QTN-20260729-AAAAAA']);
        $dua = $this->quotation(['tracking_number' => 'QTN-20260729-BBBBBB', 'status' => QuotationStatus::PRODUCTION]);
        $tiga = $this->quotation(['tracking_number' => 'QTN-20260729-CCCCCC']);

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.cancel-many'), ['ids' => [$satu->id, $dua->id]])
            ->assertRedirect();

        $this->assertSame(QuotationStatus::CANCELLATION_APPROVED, $satu->fresh()->status);
        $this->assertSame(QuotationStatus::CANCELLATION_APPROVED, $dua->fresh()->status);

        // Yang tidak dipilih tidak tersentuh.
        $this->assertSame(QuotationStatus::REVIEWING, $tiga->fresh()->status);

        // Tidak ada satu baris pun yang hilang.
        $this->assertSame(3, QuotationRequest::count());
    }

    public function test_pembatalan_massal_menuntut_minimal_satu_pilihan(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.quotations.cancel-many'), ['ids' => []])
            ->assertSessionHasErrors(['ids' => 'Pilih minimal satu penawaran.']);
    }

    /** Satu id yang tidak dikenal membatalkan seluruh permintaan, tidak setengah jalan. */
    public function test_pembatalan_massal_menolak_id_yang_tidak_dikenal(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin())
            ->post(route('admin.quotations.cancel-many'), ['ids' => [$quotation->id, 99999]])
            ->assertSessionHasErrors('ids.1');

        $this->assertSame(QuotationStatus::REVIEWING, $quotation->fresh()->status);
    }

    /* -------------------------------------------------------- hak akses --- */

    public function test_admin_tanpa_hak_hapus_ditolak(): void
    {
        $quotation = $this->quotation();

        $admin = User::factory()
            ->withPermissions([\App\Support\AdminPermission::QUOTATION_VIEW])
            ->create(['email' => 'terbatas@nusama3d.com']);

        $this->actingAs($admin)
            ->post(route('admin.quotations.cancel', $quotation))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.quotations.cancel-many'), ['ids' => [$quotation->id]])
            ->assertForbidden();

        $this->assertSame(QuotationStatus::REVIEWING, $quotation->fresh()->status);
    }

    public function test_tamu_tidak_dapat_membatalkan(): void
    {
        $quotation = $this->quotation();

        $this->post(route('admin.quotations.cancel', $quotation))->assertRedirect();

        $this->assertSame(QuotationStatus::REVIEWING, $quotation->fresh()->status);
    }

    /* ---------------------------------------------------------- tampilan --- */

    public function test_daftar_penawaran_menyediakan_centang_dan_tombol_hapus(): void
    {
        $quotation = $this->quotation();

        $html = $this->actingAs($this->admin())
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-bulk-all="quotations"', $html);
        $this->assertStringContainsString('data-bulk-item="quotations"', $html);
        $this->assertStringContainsString('Hapus Terpilih', $html);
        $this->assertStringContainsString('Pilih minimal satu penawaran.', $html);
        $this->assertStringContainsString(
            route('admin.quotations.cancel', $quotation),
            $html
        );

        // Pertanyaannya memakai modal bersama, bukan confirm() bawaan browser.
        $this->assertStringContainsString('data-confirm-title="Batalkan Penawaran?"', $html);
        $this->assertStringNotContainsString('return confirm(', $html);
    }

    /** Penawaran yang sudah dibatalkan tidak lagi menawarkan tombolnya. */
    public function test_penawaran_yang_sudah_dibatalkan_tidak_menampilkan_tombol_hapus(): void
    {
        $quotation = $this->quotation(['status' => QuotationStatus::CANCELLATION_APPROVED]);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->assertDontSee(route('admin.quotations.cancel', $quotation));
    }

    /**
     * Centangnya pun dimatikan, bukan hanya tombolnya.
     *
     * Kalau tidak, penawaran yang sudah dibatalkan masih dapat ikut terpilih —
     * dan satu-satunya jawaban yang didapat pengelola adalah penolakan server
     * "seluruh pilihan sudah dalam keadaan pembatalan", yang terbaca seolah
     * aksinya gagal padahal justru sudah berhasil sebelumnya.
     */
    public function test_penawaran_yang_sudah_dibatalkan_tidak_dapat_dicentang(): void
    {
        $berjalan = $this->quotation(['tracking_number' => 'QTN-20260729-AAAAAA']);
        $dibatalkan = $this->quotation([
            'tracking_number' => 'QTN-20260729-BBBBBB',
            'status' => QuotationStatus::CANCELLATION_APPROVED,
        ]);

        $html = $this->actingAs($this->admin())
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->getContent();

        $baris = function (int $id) use ($html): string {
            preg_match('/<input[^>]*value="'.$id.'"[^>]*>/', $html, $m);

            return $m[0] ?? '';
        };

        // `disabled` sebagai ATRIBUT, bukan awalan kelas Tailwind
        // `disabled:opacity-40` yang selalu ada pada keduanya.
        $mati = fn (string $tag) => (bool) preg_match('/\sdisabled(?![:\-])/', $tag);

        // Yang masih berjalan: dapat dipilih, ikut centang-semua.
        $this->assertStringContainsString('data-bulk-item="quotations"', $baris($berjalan->id));
        $this->assertFalse($mati($baris($berjalan->id)));

        // Yang sudah dibatalkan: mati, dan tanpa `data-bulk-item` sehingga
        // centang-semua pun melewatinya.
        $this->assertTrue($mati($baris($dibatalkan->id)));
        $this->assertStringNotContainsString('data-bulk-item', $baris($dibatalkan->id));
    }

    /** Statusnya diberi warna tersendiri supaya perubahannya terbaca sekilas. */
    public function test_status_pembatalan_ditandai_berbeda_pada_daftar(): void
    {
        $this->quotation(['status' => QuotationStatus::CANCELLATION_APPROVED]);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.index'))
            ->assertOk()
            ->assertSee('border-brand-200 bg-brand-50 text-brand-700', false)
            ->assertSee(QuotationStatus::label(QuotationStatus::CANCELLATION_APPROVED));
    }
}
