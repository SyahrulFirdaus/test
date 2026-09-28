<?php

namespace Tests\Feature;

use App\Http\Controllers\QuotationTrackingController;
use App\Models\QuotationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuotationTrackingTest extends TestCase
{
    use RefreshDatabase;

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
            'status' => 'reviewing',
        ], $overrides));

        // Berkas model tersimpan sebagai item tersendiri: satu penawaran dapat
        // berisi banyak model, dan halaman tracking membacanya dari sana.
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
        ]);

        $quotation->recordHistory('reviewing', 'Permintaan penawaran berhasil dikirim.');

        return $quotation->load('items');
    }

    /** Sesi yang sudah lolos verifikasi WhatsApp untuk penawaran ini. */
    private function verified(QuotationRequest $quotation): static
    {
        return $this->withSession([QuotationTrackingController::SESSION_KEY => [$quotation->tracking_number]]);
    }

    public function test_halaman_tracking_terbuka_tanpa_login_setelah_verifikasi(): void
    {
        $quotation = $this->quotation();

        $this->post(route('tracking.lookup'), ['tracking_number' => $quotation->tracking_number, 'whatsapp_last4' => '7890'])
            ->assertRedirect(route('tracking.show', $quotation->tracking_number));

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('QTN-20260729-TEST01')
            ->assertSee('R***** Prasetya')
            ->assertSee('bracket.stl')
            ->assertSee('FDM')
            ->assertSee('File Sedang Direview')
            ->assertSee('Riwayat Tracking');
    }

    public function test_detail_permintaan_tanpa_email_whatsapp_dan_nama_depan_disensor(): void
    {
        $quotation = $this->quotation();

        $response = $this->verified($quotation)->get(route('tracking.show', $quotation->tracking_number));

        $response->assertOk();
        $response->assertDontSee('rangga@contoh.test');
        $response->assertDontSee($quotation->masked_email, false);
        $response->assertDontSee('081234567890');
        $response->assertDontSee($quotation->masked_whatsapp);
        $response->assertDontSee('Rangga');
        $response->assertSee('R***** Prasetya');

        // Data aslinya tidak berubah.
        $fresh = $quotation->fresh();
        $this->assertSame('Rangga Prasetya', $fresh->name);
        $this->assertSame('rangga@contoh.test', $fresh->email);
        $this->assertSame('081234567890', $fresh->whatsapp);
    }

    public function test_sensor_nama_depan(): void
    {
        $this->assertSame('S****** Firdaus', (new QuotationRequest(['name' => 'Syahrul Firdaus']))->masked_name);
        $this->assertSame('B*** Santoso', (new QuotationRequest(['name' => 'Budi Santoso']))->masked_name);
        $this->assertSame('A*** Budi Santoso', (new QuotationRequest(['name' => 'Agus Budi Santoso']))->masked_name);
        $this->assertSame('R*****', (new QuotationRequest(['name' => 'Rangga']))->masked_name);
    }

    /* ------------------------------------------------ verifikasi WhatsApp --- */

    public function test_url_tracking_langsung_meminta_verifikasi(): void
    {
        $quotation = $this->quotation();

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertRedirect(route('tracking.index'))
            ->assertSessionHas('tracking_verify', true);

        $this->get(route('tracking.document', $quotation->tracking_number))
            ->assertRedirect(route('tracking.index'));

        // Halaman Tracking membuka modalnya dengan nomor yang sudah terisi.
        $this->get(route('tracking.show', $quotation->tracking_number));
        $this->get(route('tracking.index'))
            ->assertOk()
            ->assertSee('Verifikasi Penawaran')
            ->assertSee('4 Digit Terakhir WhatsApp')
            ->assertSee('value="QTN-20260729-TEST01"', false)
            ->assertDontSee('7890');
    }

    public function test_digit_salah_ditolak_dan_tracking_tetap_tertutup(): void
    {
        $quotation = $this->quotation();

        $this->from(route('tracking.index'))
            ->post(route('tracking.lookup'), ['tracking_number' => $quotation->tracking_number, 'whatsapp_last4' => '1234'])
            ->assertRedirect(route('tracking.index'))
            ->assertSessionHasErrors(['whatsapp_last4' => 'Verifikasi gagal. 4 digit terakhir nomor WhatsApp tidak sesuai.']);

        $this->get(route('tracking.show', $quotation->tracking_number))->assertRedirect(route('tracking.index'));
    }

    public function test_notifikasi_gagal_tampil_di_halaman_tracking(): void
    {
        $quotation = $this->quotation();

        $this->followingRedirects()
            ->post(route('tracking.lookup'), ['tracking_number' => $quotation->tracking_number, 'whatsapp_last4' => '1234'])
            ->assertOk()
            ->assertSee('Verifikasi gagal. 4 digit terakhir nomor WhatsApp tidak sesuai.')
            ->assertSee('Verifikasi Penawaran')
            ->assertDontSee('Riwayat Tracking');
    }

    public function test_hanya_tepat_empat_digit_angka_yang_diterima(): void
    {
        $quotation = $this->quotation();

        foreach (['', '789', '67890', 'abcd', '78 9'] as $digits) {
            $this->post(route('tracking.lookup'), ['tracking_number' => $quotation->tracking_number, 'whatsapp_last4' => $digits])
                ->assertSessionHasErrors('whatsapp_last4');
        }

        $this->get(route('tracking.show', $quotation->tracking_number))->assertRedirect(route('tracking.index'));
    }

    public function test_percobaan_berulang_dibatasi(): void
    {
        $quotation = $this->quotation();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('tracking.lookup'), ['tracking_number' => $quotation->tracking_number, 'whatsapp_last4' => '0000']);
        }

        // Digit yang benar pun ditahan sampai jedanya lewat.
        $this->post(route('tracking.lookup'), ['tracking_number' => $quotation->tracking_number, 'whatsapp_last4' => '7890'])
            ->assertSessionHasErrors('whatsapp_last4');

        $this->get(route('tracking.show', $quotation->tracking_number))->assertRedirect(route('tracking.index'));
    }

    public function test_pemilik_yang_sedang_login_tidak_perlu_verifikasi(): void
    {
        $owner = User::factory()->create();
        $quotation = $this->quotation(['user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('tracking.show', $quotation->tracking_number))->assertOk();

        // Pengguna lain tetap harus verifikasi.
        $this->actingAs(User::factory()->create())
            ->get(route('tracking.show', $quotation->tracking_number))
            ->assertRedirect(route('tracking.index'));
    }

    public function test_seluruh_tahap_timeline_tampil_dengan_keadaannya(): void
    {
        $quotation = $this->quotation(['status' => 'production']);

        $response = $this->verified($quotation)->get(route('tracking.show', $quotation->tracking_number));

        foreach ([
            'File Sedang Direview', 'Menunggu Pembayaran',
            'Menunggu Pembayaran', 'Pembayaran Diterima', 'Sedang Diproduksi',
            'Quality Control', 'Siap Dikirim', 'Selesai',
        ] as $label) {
            $response->assertSee($label);
        }

        $timeline = collect($quotation->timeline)->keyBy('key');

        $this->assertSame('done', $timeline['reviewing']['state']);
        $this->assertSame('done', $timeline['awaiting_payment']['state']);
        $this->assertSame('current', $timeline['production']['state']);
        $this->assertSame('upcoming', $timeline['quality_control']['state']);
        $this->assertSame('upcoming', $timeline['ready_to_ship']['state']);
    }

    public function test_nomor_tracking_tidak_dikenal_menghasilkan_404(): void
    {
        $this->get(route('tracking.show', 'QTN-20260101-TIDAKADA'))->assertNotFound();
    }

    public function test_pencarian_nomor_tracking(): void
    {
        $quotation = $this->quotation();

        $this->post(route('tracking.lookup'), ['tracking_number' => 'qtn-20260729-test01', 'whatsapp_last4' => '7890'])
            ->assertRedirect(route('tracking.show', $quotation->tracking_number));

        $this->post(route('tracking.lookup'), ['tracking_number' => 'QTN-SALAH'])
            ->assertSessionHasErrors('tracking_number');
    }

    public function test_bukti_penawaran_pdf_dapat_diunduh(): void
    {
        $quotation = $this->quotation();

        $response = $this->verified($quotation)->get(route('tracking.document', $quotation->tracking_number));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $response->assertDownload('Bukti-Penawaran-QTN-20260729-TEST01.pdf');

        $content = $response->getContent();

        // Berkas PDF yang sah selalu diawali penanda %PDF-.
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(5000, strlen($content));
    }

    /**
     * Bukti Permintaan Penawaran tidak menyebut infill, nama mesin, maupun
     * resolusi.
     *
     * Ketiganya keputusan produksi yang masih dapat bergeser sampai pengerjaan
     * dimulai, dan pelanggan tidak memesan mesin tertentu. Yang diperiksa di
     * sini adalah HTML sumber dokumennya — isi PDF-nya sendiri terkompresi
     * sehingga tidak dapat dibaca sebagai teks biasa.
     */
    public function test_bukti_penawaran_tidak_memuat_infill_mesin_dan_resolusi(): void
    {
        $quotation = $this->quotation();

        $quotation->items()->first()->update([
            'printer' => 'bambu-p1s',
            'printer_name' => 'Bambu Lab P1S',
            'infill_density' => 0.2,
            'infill_pattern' => 'gyroid',
        ]);

        $html = view('pdf.quotation-receipt', [
            'quotation' => $quotation->fresh()->load('items'),
            'trackingUrl' => route('tracking.show', $quotation->tracking_number),
            'qrCode' => '',
            'logo' => '',
        ])->render();

        $this->assertStringNotContainsString('Bambu Lab P1S', $html);
        $this->assertStringNotContainsStringIgnoringCase('infill', $html);
        $this->assertStringNotContainsString('Mesin', $html);

        // Resolusi juga parameter mesin, bukan bagian dari apa yang dipesan.
        $this->assertStringNotContainsStringIgnoringCase('resolusi', $html);

        // Yang tersisa tetap lengkap: teknologi, material, dan harganya.
        $this->assertStringContainsString('FDM', $html);
        $this->assertStringContainsString($quotation->tracking_number, $html);
    }

    /** Datanya sendiri tetap tersimpan; yang berubah hanya dokumen keluarannya. */
    public function test_infill_mesin_dan_resolusi_tetap_tersimpan_di_basis_data(): void
    {
        $quotation = $this->quotation();

        $quotation->items()->first()->update([
            'printer' => 'bambu-p1s',
            'printer_name' => 'Bambu Lab P1S',
            'infill_density' => 0.2,
            'infill_pattern' => 'gyroid',
        ]);

        $item = $quotation->fresh()->items()->first();

        $this->assertSame('Bambu Lab P1S', $item->printer_name);
        $this->assertSame(0.2, (float) $item->infill_density);
        $this->assertSame('gyroid', $item->infill_pattern);
        $this->assertSame('0.25', (string) $item->resolution);
    }

    public function test_perubahan_status_admin_tercatat_di_riwayat_dan_tampil_ke_pelanggan(): void
    {
        $quotation = $this->quotation();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
            'status' => 'reviewing',
            'note' => 'Model memiliki ketebalan dinding yang terlalu tipis.',
        ])->assertRedirect();

        $this->assertSame('reviewing', $quotation->fresh()->status);
        $this->assertSame(2, $quotation->histories()->count());

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertSee('File Sedang Direview')
            ->assertSee('Model memiliki ketebalan dinding yang terlalu tipis.');
    }

    public function test_riwayat_lama_tidak_terhapus_saat_status_berubah_berkali_kali(): void
    {
        $quotation = $this->quotation();
        $admin = User::factory()->admin()->create();

        // Urutannya mengikuti alur berurutan: satu tahap sekali simpan, dan
        // menyimpan ulang tahap yang sama tetap menambah baris riwayat.
        $urutan = ['reviewing', 'awaiting_payment', 'awaiting_payment', 'payment_review'];

        foreach ($urutan as $status) {
            $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
                'status' => $status,
                'note' => 'Berpindah ke '.$status,
            ])->assertSessionHasNoErrors();
        }

        // Satu entri awal + empat perubahan.
        $this->assertSame(5, $quotation->histories()->count());

        $response = $this->get(route('tracking.show', $quotation->tracking_number));

        foreach ($urutan as $status) {
            $response->assertSee('Berpindah ke '.$status);
        }
    }

    public function test_admin_dapat_mengubah_tanggal_selesai_tanpa_menyentuh_harga(): void
    {
        $quotation = $this->quotation();
        $hargaSebelum = (float) $quotation->display_price;
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
            'status' => 'awaiting_payment',
            // Harga sengaja ikut dikirim: admin tidak lagi boleh mengubahnya.
            'estimated_price' => 250000,
            'estimated_finish' => '2026-08-15',
        ])->assertRedirect();

        $fresh = $quotation->fresh();

        $this->assertSame('2026-08-15', $fresh->estimated_finish->format('Y-m-d'));

        // Harga penawaran tetap mengikuti estimasi sistem, bukan kiriman admin.
        $this->assertEqualsWithDelta($hargaSebelum, $fresh->display_price, 0.01);
        $this->assertNotEqualsWithDelta(250000, $fresh->display_price, 0.01);

        $this->get(route('tracking.show', $fresh->tracking_number))
            ->assertSee('15 August 2026');
    }

    public function test_admin_dapat_mengunggah_foto_proses_dan_hasil(): void
    {
        Storage::fake('public');

        // Foto proses diunggah saat penawaran memang sudah berada di tahap
        // produksi — statusnya tidak dapat dilompati dari tahap review.
        $quotation = $this->quotation(['status' => 'production']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
            'status' => 'production',
            // Memakai create() alih-alih image(): pembuatan gambar palsu oleh
            // Laravel memerlukan ekstensi GD yang tidak tersedia di lingkungan ini.
            'production_photo' => UploadedFile::fake()->create('proses.jpg', 120, 'image/jpeg'),
            'result_photo' => UploadedFile::fake()->create('hasil.jpg', 120, 'image/jpeg'),
        ])->assertRedirect();

        $fresh = $quotation->fresh();

        $this->assertNotNull($fresh->production_photo);
        $this->assertNotNull($fresh->result_photo);
        Storage::disk('public')->assertExists($fresh->production_photo);
        Storage::disk('public')->assertExists($fresh->result_photo);

        $this->get(route('tracking.show', $fresh->tracking_number))->assertSee('Foto Proses');
    }
}
