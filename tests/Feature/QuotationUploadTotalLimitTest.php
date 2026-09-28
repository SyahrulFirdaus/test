<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\QuotationStatus;
use App\Support\UploadLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Batas total ukuran file per penawaran (300 MB), dan Tracking yang tidak lagi
 * menampilkan printer/mesin kepada pelanggan.
 *
 * Batasnya diperkecil lewat config pada pengujian supaya berkasnya tidak perlu
 * ratusan megabyte; yang diuji adalah cara menghitungnya: TOTAL seluruh file.
 */
class QuotationUploadTotalLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function penawaran(User $owner, int $existingBytes): QuotationRequest
    {
        $quotation = QuotationRequest::create([
            'user_id' => $owner->id,
            'tracking_number' => 'QT-'.strtoupper(fake()->bothify('?????')),
            'name' => $owner->name,
            'email' => $owner->email,
            'whatsapp' => '081211112222',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => $existingBytes,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'status' => QuotationStatus::REVIEWING,
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => $existingBytes,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'printer' => 'bambu_x1c',
            'printer_name' => 'Bambu Lab X1 Carbon',
            'quantity' => 2,
            'scale_percent' => 100,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'infill_density' => 0.2,
            'model_volume_cm3' => 120,
            'estimated_weight_g' => 100,
            'support_weight_g' => 0,
            'estimated_minutes' => 300,
            'estimated_cost' => 100000,
        ]);

        return $quotation->fresh();
    }

    /** STL biner yang sah, sekitar 50 byte per segitiga (6000 ≈ 293 KB). */
    private function binaryStl(int $triangles): string
    {
        $facet = pack('f3', 0, 0, 1).pack('f9', 0, 0, 0, 10, 0, 0, 0, 10, 0)."\0\0";

        return str_pad('uji', 80, ' ').pack('V', $triangles).str_repeat($facet, $triangles);
    }

    /* ================================================ batas 300 MB === */

    public function test_batas_aplikasi_300_mb_per_penawaran(): void
    {
        $this->assertSame(300, config('printing.limits.max_total_size_mb'));
        $this->assertSame(300 * 1024 * 1024, UploadLimit::preferredTotalBytes());
    }

    public function test_total_dihitung_dari_seluruh_file(): void
    {
        config(['printing.limits.max_total_size_mb' => 1]);

        $this->assertSame('1 MB', UploadLimit::maxTotalLabel());
        $this->assertTrue(UploadLimit::withinTotal(1024 * 1024));
        $this->assertFalse(UploadLimit::withinTotal(1024 * 1024 + 1));
        $this->assertSame(
            'Total ukuran file melebihi batas 1 MB per penawaran. Kurangi jumlah/ukuran model atau kirim dalam penawaran terpisah.',
            UploadLimit::totalExceededMessage(),
        );
    }

    public function test_kirim_penawaran_ditolak_backend_bila_total_melebihi_batas(): void
    {
        config(['printing.limits.max_total_size_mb' => 1]);

        // Tiap file di bawah batas, tetapi totalnya melebihinya.
        $this->actingAs(User::factory()->create())
            ->post(route('quotations.store'), ['items' => [
                ['model' => UploadedFile::fake()->create('a.stl', 600), 'quantity' => 1, 'technology' => 'FDM', 'material' => 'PLA Basic ESUN', 'model_volume_cm3' => 10, 'analysis_status' => 'ready'],
                ['model' => UploadedFile::fake()->create('b.stl', 600), 'quantity' => 1, 'technology' => 'FDM', 'material' => 'PLA Basic ESUN', 'model_volume_cm3' => 10, 'analysis_status' => 'ready'],
            ]])
            ->assertSessionHasErrors(['items' => UploadLimit::totalExceededMessage()]);
    }

    public function test_kirim_penawaran_di_bawah_batas_tidak_kena_pesan_total(): void
    {
        config(['printing.limits.max_total_size_mb' => 1]);

        $this->actingAs(User::factory()->create())
            ->post(route('quotations.store'), ['items' => [
                ['model' => UploadedFile::fake()->create('a.stl', 400), 'quantity' => 1, 'technology' => 'FDM', 'material' => 'PLA Basic ESUN', 'model_volume_cm3' => 10, 'analysis_status' => 'ready'],
                ['model' => UploadedFile::fake()->create('b.stl', 400), 'quantity' => 1, 'technology' => 'FDM', 'material' => 'PLA Basic ESUN', 'model_volume_cm3' => 10, 'analysis_status' => 'ready'],
            ]])
            ->assertSessionDoesntHaveErrors('items');
    }

    public function test_tambah_file_menghitung_file_yang_sudah_ada(): void
    {
        config(['printing.limits.max_total_size_mb' => 1]);

        $owner = User::factory()->create();
        $quotation = $this->penawaran($owner, 800 * 1024);

        $this->actingAs($owner)
            ->post(route('dashboard.quotations.items.store', $quotation), [
                'model' => UploadedFile::fake()->createWithContent('tambahan.stl', $this->binaryStl(6000)),
            ])
            ->assertSessionHasErrors(['model' => UploadLimit::totalExceededMessage()]);

        $this->assertSame(1, $quotation->items()->count());
    }

    public function test_halaman_ubah_penawaran_menyebut_batas_total(): void
    {
        $owner = User::factory()->create();
        $quotation = $this->penawaran($owner, 2048);

        $this->actingAs($owner)
            ->get(route('dashboard.quotations.edit', $quotation))
            ->assertOk()
            ->assertSee('Maksimal total ukuran file '.UploadLimit::maxTotalLabel().' per penawaran.');
    }

    /* ======================================================= tracking === */

    public function test_tracking_tidak_menampilkan_printer_atau_mesin(): void
    {
        $owner = User::factory()->create();
        $quotation = $this->penawaran($owner, 2048);

        $this->actingAs($owner)
            ->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('Detail Permintaan')
            ->assertSee('bracket.stl')
            ->assertSee('1 model')
            ->assertDontSee('Bambu Lab X1 Carbon')
            ->assertDontSee('mesin per model')
            ->assertDontSee('dicetak pada mesinnya sendiri');

        // Datanya tetap tersimpan untuk kebutuhan internal.
        $this->assertSame('Bambu Lab X1 Carbon', $quotation->items()->first()->printer_name);
    }
}
