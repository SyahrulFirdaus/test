<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Support\LeadTime;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Estimasi Lead Time ditentukan TOTAL waktu proses seluruh 3D object dalam
 * satu penawaran:
 *
 *   sampai dengan 20 jam  → Express — 1 Hari Kerja
 *   lebih dari 20 jam     → Standard — 3–5 Hari Kerja
 *
 * Jam mesin tetap dihitung dan tersimpan sebagai data internal; yang sampai ke
 * pelanggan hanya nama tingkat beserta rentang hari kerjanya.
 */
class LeadTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private const JAM = 60;

    public function test_batas_dua_puluh_jam(): void
    {
        $cases = [
            'tanpa waktu' => [0, 'Express — 1 Hari Kerja'],
            'satu jam' => [1 * self::JAM, 'Express — 1 Hari Kerja'],
            'sembilan belas jam' => [19 * self::JAM, 'Express — 1 Hari Kerja'],
            'tepat dua puluh jam' => [20 * self::JAM, 'Express — 1 Hari Kerja'],
            'lewat satu menit' => [20 * self::JAM + 1, 'Standard — 3–5 Hari Kerja'],
            'dua puluh dua jam' => [22 * self::JAM, 'Standard — 3–5 Hari Kerja'],
            'seratus jam' => [100 * self::JAM, 'Standard — 3–5 Hari Kerja'],
        ];

        foreach ($cases as $nama => [$menit, $label]) {
            $this->assertSame($label, LeadTime::label($menit), $nama);
        }

        $this->assertSame('Express', LeadTime::name(19 * self::JAM));
        $this->assertSame('Standard', LeadTime::name(22 * self::JAM));
    }

    /**
     * Penawaran berisi beberapa object dengan total waktu yang ditentukan.
     *
     * @param  array<int, int>  $jamTiapObject
     */
    private function penawaran(array $jamTiapObject): QuotationRequest
    {
        $quotation = QuotationRequest::create([
            'tracking_number' => 'QT-'.strtoupper(fake()->bothify('?????')),
            'name' => 'Rani Prameswari',
            'email' => 'rani@contoh.test',
            'whatsapp' => '081211112222',
            'quantity' => count($jamTiapObject),
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'status' => QuotationStatus::REVIEWING,
        ]);

        foreach ($jamTiapObject as $index => $jam) {
            $quotation->items()->create([
                'position' => $index + 1,
                'file_name' => 'object-'.($index + 1).'.stl',
                'file_path' => 'quotations/2026-09/object-'.($index + 1).'.stl',
                'file_format' => 'STL',
                'file_size' => 2048,
                'analysis_status' => QuotationRequest::ANALYSIS_READY,
                'technology' => 'FDM',
                'material' => 'PLA Basic ESUN',
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
                'estimated_minutes' => $jam * self::JAM,
                'estimated_cost' => 100000,
            ]);
        }

        $quotation->refreshSummary();

        return $quotation->fresh();
    }

    public function test_contoh_spesifikasi_delapan_enam_lima_jam_masih_express(): void
    {
        // 8 + 6 + 5 = 19 jam.
        $quotation = $this->penawaran([8, 6, 5]);

        $this->assertSame(19 * self::JAM, (int) $quotation->estimated_minutes);
        $this->assertSame('Express — 1 Hari Kerja', $quotation->lead_time);
    }

    public function test_contoh_spesifikasi_sepuluh_tujuh_lima_jam_menjadi_standard(): void
    {
        // 10 + 7 + 5 = 22 jam.
        $quotation = $this->penawaran([10, 7, 5]);

        $this->assertSame(22 * self::JAM, (int) $quotation->estimated_minutes);
        $this->assertSame('Standard — 3–5 Hari Kerja', $quotation->lead_time);
    }

    /**
     * Inti perubahannya: tidak ada satu object pun yang melewati 20 jam, tetapi
     * totalnya melewati. Aturan lama — yang mengikuti object paling lama —
     * akan menjawab Express di sini.
     */
    public function test_yang_menentukan_total_bukan_object_terlama(): void
    {
        $quotation = $this->penawaran([7, 7, 7]);

        $this->assertSame('Express — 1 Hari Kerja', LeadTime::label(7 * self::JAM));
        $this->assertSame('Standard — 3–5 Hari Kerja', $quotation->lead_time);
    }

    public function test_yang_ditampilkan_bukan_jam_atau_menit(): void
    {
        $quotation = $this->penawaran([8, 6, 5]);

        $this->actingAs($quotation->user ?? \App\Models\User::factory()->create());

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('Express — 1 Hari Kerja')
            // Jam mesin tetap tersimpan, tetapi tidak dipakai sebagai lead time.
            ->assertDontSee('19 jam');
    }

    public function test_payload_browser_membawa_nama_tingkat(): void
    {
        $payload = LeadTime::browserPayload();

        $this->assertSame('Express', $payload['tiers'][0]['name']);
        $this->assertSame(1200, $payload['tiers'][0]['maxMinutes']);
        $this->assertSame('Standard', $payload['tiers'][1]['name']);
        $this->assertNull($payload['tiers'][1]['maxMinutes']);
    }
}
