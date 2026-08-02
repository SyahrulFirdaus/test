<?php

namespace Tests\Feature;

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
            'status' => 'received',
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

        $quotation->recordHistory('received', 'Permintaan penawaran berhasil dikirim.');

        return $quotation->load('items');
    }

    public function test_halaman_tracking_dapat_dibuka_tanpa_login(): void
    {
        $quotation = $this->quotation();

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('QTN-20260729-TEST01')
            ->assertSee('Rangga Prasetya')
            ->assertSee('bracket.stl')
            ->assertSee('FDM')
            ->assertSee('Menunggu Review')
            ->assertSee('Riwayat Tracking');
    }

    public function test_email_dan_whatsapp_disamarkan_di_halaman_publik(): void
    {
        $quotation = $this->quotation();

        $response = $this->get(route('tracking.show', $quotation->tracking_number));

        $response->assertDontSee('rangga@contoh.test');
        $response->assertDontSee('081234567890');
        $response->assertSee($quotation->masked_email, false);
    }

    public function test_seluruh_tahap_timeline_tampil_dengan_keadaannya(): void
    {
        $quotation = $this->quotation(['status' => 'production']);

        $response = $this->get(route('tracking.show', $quotation->tracking_number));

        foreach ([
            'Menunggu Review', 'File Sedang Direview', 'Menunggu Persetujuan Penawaran',
            'Menunggu Pembayaran', 'Pembayaran Diterima', 'Sedang Diproduksi',
            'Quality Control', 'Siap Dikirim', 'Selesai',
        ] as $label) {
            $response->assertSee($label);
        }

        $timeline = collect($quotation->timeline)->keyBy('key');

        $this->assertSame('done', $timeline['received']['state']);
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

        $this->post(route('tracking.lookup'), ['tracking_number' => 'qtn-20260729-test01'])
            ->assertRedirect(route('tracking.show', $quotation->tracking_number));

        $this->post(route('tracking.lookup'), ['tracking_number' => 'QTN-SALAH'])
            ->assertSessionHasErrors('tracking_number');
    }

    public function test_bukti_penawaran_pdf_dapat_diunduh(): void
    {
        $quotation = $this->quotation();

        $response = $this->get(route('tracking.document', $quotation->tracking_number));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $response->assertDownload('Bukti-Penawaran-QTN-20260729-TEST01.pdf');

        $content = $response->getContent();

        // Berkas PDF yang sah selalu diawali penanda %PDF-.
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(5000, strlen($content));
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

        foreach (['reviewing', 'awaiting_approval', 'awaiting_payment', 'production'] as $status) {
            $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
                'status' => $status,
                'note' => 'Berpindah ke '.$status,
            ]);
        }

        // Satu entri awal + empat perubahan.
        $this->assertSame(5, $quotation->histories()->count());

        $response = $this->get(route('tracking.show', $quotation->tracking_number));

        foreach (['reviewing', 'awaiting_approval', 'awaiting_payment', 'production'] as $status) {
            $response->assertSee('Berpindah ke '.$status);
        }
    }

    public function test_admin_dapat_mengubah_estimasi_harga_dan_tanggal_selesai(): void
    {
        $quotation = $this->quotation();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.quotations.update', $quotation), [
            'status' => 'awaiting_approval',
            'estimated_price' => 250000,
            'estimated_finish' => '2026-08-15',
        ])->assertRedirect();

        $fresh = $quotation->fresh();

        $this->assertEqualsWithDelta(250000, (float) $fresh->estimated_price, 0.01);
        $this->assertSame('2026-08-15', $fresh->estimated_finish->format('Y-m-d'));

        // Harga admin menggantikan estimasi sistem pada tampilan pelanggan.
        $this->assertEqualsWithDelta(250000, $fresh->display_price, 0.01);

        $this->get(route('tracking.show', $fresh->tracking_number))
            ->assertSee('Rp250.000', false)
            ->assertSee('15 August 2026');
    }

    public function test_admin_dapat_mengunggah_foto_proses_dan_hasil(): void
    {
        Storage::fake('public');

        $quotation = $this->quotation();
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
