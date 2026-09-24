<?php

namespace Tests\Feature;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\QuotationRequest;
use App\Support\LeadTime;
use App\Support\QuotationStatus;
use App\Support\SlaIndustries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kecepatan pengerjaan DIPILIH pelanggan, bukan disimpulkan dari jam mesin.
 *
 *   Standard → 5–7 Hari Kerja, harga normal
 *   Express  → 1–2 Hari Kerja, harga printing +25%
 *
 * Express hanya tersedia bila KEDUA syaratnya terpenuhi sekaligus: pesanannya
 * berisi tepat satu part DAN total waktu mesinnya DI BAWAH 18 jam. Batasnya
 * tegas di bawah — 17 jam 59 menit masih boleh, 18 jam tepat tidak lagi.
 */
class LeadTimeTest extends TestCase
{
    use RefreshDatabase;

    private const JAM = 60;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /* ================================================ syarat Express === */

    /**
     * Tabel syarat Express, persis seperti yang diminta.
     *
     * Jumlah part dan waktu mesin diperiksa BERSAMAAN: dua part berjumlah 5 jam
     * tetap tidak mendapat Express walau waktunya jauh di bawah batas.
     */
    public function test_syarat_express_jumlah_part_dan_waktu_mesin(): void
    {
        $cases = [
            '1 part, 10 jam' => [1, 10 * self::JAM, true],
            '1 part, 17 jam 59 menit' => [1, 17 * self::JAM + 59, true],
            '1 part, 18 jam tepat' => [1, 18 * self::JAM, false],
            '1 part, 20 jam' => [1, 20 * self::JAM, false],
            '2 part, 5 jam' => [2, 5 * self::JAM, false],
            '2 part, 10 jam' => [2, 10 * self::JAM, false],
            '3 part, 5 jam' => [3, 5 * self::JAM, false],
        ];

        foreach ($cases as $nama => [$parts, $menit, $harapan]) {
            $this->assertSame($harapan, LeadTime::expressAvailable($parts, $menit), $nama);
        }
    }

    /** Batasnya tegas DI BAWAH 18 jam, bukan sampai dengan. */
    public function test_delapan_belas_jam_tepat_kehilangan_express(): void
    {
        $this->assertTrue(LeadTime::expressAvailable(1, 18 * self::JAM - 1));
        $this->assertFalse(LeadTime::expressAvailable(1, 18 * self::JAM));
    }

    /**
     * Beberapa part langsung menggugurkan Express.
     *
     * Contohnya: part A 5 jam dan part B 4 jam berjumlah 9 jam — jauh di bawah
     * batas, tetapi partnya dua.
     */
    public function test_lebih_dari_satu_part_tidak_mendapat_express(): void
    {
        $this->assertFalse(LeadTime::expressAvailable(2, (5 + 4) * self::JAM));
        $this->assertTrue(LeadTime::expressAvailable(1, (5 + 4) * self::JAM));
    }

    /** Pekerjaan berharga Rumus Harga Manual tidak pernah mendapat Express. */
    public function test_rumus_harga_manual_tidak_mendapat_express(): void
    {
        $this->assertFalse(LeadTime::expressAvailable(1, 2 * self::JAM, manualPricing: true));
    }

    /**
     * Express yang diminta tanpa memenuhi syarat diturunkan menjadi Standard.
     *
     * Inilah yang menjaga harga: kiriman browser yang menyebut Express tidak
     * dapat menaikkan lead time maupun harga tanpa syaratnya terpenuhi.
     */
    public function test_express_yang_tidak_memenuhi_syarat_diturunkan(): void
    {
        $this->assertSame(LeadTime::EXPRESS, LeadTime::resolve(LeadTime::EXPRESS, 1, 10 * self::JAM));
        $this->assertSame(LeadTime::STANDARD, LeadTime::resolve(LeadTime::EXPRESS, 2, 10 * self::JAM));
        $this->assertSame(LeadTime::STANDARD, LeadTime::resolve(LeadTime::EXPRESS, 1, 18 * self::JAM));
        $this->assertSame(LeadTime::STANDARD, LeadTime::resolve(null, 1, 10 * self::JAM));
    }

    /* ==================================================== tampilannya === */

    public function test_rentang_hari_kerja_tiap_kecepatan(): void
    {
        $this->assertSame('Standard (5–7 Hari Kerja)', LeadTime::label(LeadTime::STANDARD));
        $this->assertSame('Express (1–2 Hari Kerja)', LeadTime::label(LeadTime::EXPRESS));

        $this->assertSame(['min' => 5, 'max' => 7], LeadTime::days(LeadTime::STANDARD));
        $this->assertSame(['min' => 1, 'max' => 2], LeadTime::days(LeadTime::EXPRESS));
    }

    /** Kecepatan yang tidak dikenal dibaca sebagai Standard. */
    public function test_kecepatan_tak_dikenal_dibaca_standard(): void
    {
        $this->assertSame('Standard (5–7 Hari Kerja)', LeadTime::label(null));
        $this->assertSame('Standard (5–7 Hari Kerja)', LeadTime::label('kilat'));
    }

    /* ======================================================== harganya === */

    /** Express menaikkan harga printing 25%: Rp400.000 menjadi Rp500.000. */
    public function test_tambahan_harga_express_dua_puluh_lima_persen(): void
    {
        $this->assertSame(25.0, LeadTime::surchargePercent());
        $this->assertSame(1.25, LeadTime::surchargeFactor(LeadTime::EXPRESS));
        $this->assertSame(1.0, LeadTime::surchargeFactor(LeadTime::STANDARD));

        $this->assertSame(500000.0, 400000 * LeadTime::surchargeFactor(LeadTime::EXPRESS));
    }

    /* ================================================ payload browser === */

    public function test_payload_browser_membawa_aturan_express(): void
    {
        $payload = LeadTime::browserPayload();

        $this->assertSame('Standard', $payload['standard']['name']);
        $this->assertSame(5, $payload['standard']['minDays']);
        $this->assertSame(7, $payload['standard']['maxDays']);

        $this->assertSame('Express', $payload['express']['name']);
        $this->assertSame(1, $payload['express']['minDays']);
        $this->assertSame(2, $payload['express']['maxDays']);
        $this->assertSame(1, $payload['express']['maxParts']);
        $this->assertSame(1080.0, $payload['express']['belowMinutes']);
        $this->assertSame(25.0, $payload['express']['surchargePercent']);

        // Keterangan cadangan berbahasa Indonesia; yang biasanya dibaca
        // pelanggan adalah kalimat dinamis dari expressUnavailableReason().
        $this->assertStringContainsString('Standard', $payload['unavailableNote']);
        $this->assertStringContainsString('Express', $payload['unavailableNote']);
        $this->assertStringNotContainsString('unavailable for this order', $payload['unavailableNote']);
    }

    /* ================================================== pada penawaran === */

    public function test_lead_time_penawaran_mengikuti_kecepatan_tersimpan(): void
    {
        $this->assertSame('Standard (5–7 Hari Kerja)', $this->penawaran([10])->lead_time);
        $this->assertSame('Express (1–2 Hari Kerja)', $this->penawaran([10], LeadTime::EXPRESS)->lead_time);
    }

    /** Model membaca kecepatan dari penawaran induknya, bukan dari dirinya. */
    public function test_model_mengikuti_kecepatan_penawaran_induknya(): void
    {
        $quotation = $this->penawaran([10], LeadTime::EXPRESS);

        $this->assertSame('Express (1–2 Hari Kerja)', $quotation->items->first()->lead_time);
    }

    public function test_yang_ditampilkan_bukan_jam_atau_menit(): void
    {
        $quotation = $this->penawaran([8]);

        $this->actingAs($quotation->user ?? \App\Models\User::factory()->create());

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee('Standard (5–7 Hari Kerja)')
            // Jam mesin tetap tersimpan, tetapi tidak dipakai sebagai lead time.
            ->assertDontSee('8 jam');
    }

    /* ============================================ Rumus Harga Manual === */

    /**
     * Pekerjaan berharga Rumus Harga Manual memakai rentang tetapnya sendiri.
     *
     * Partnya menunggu kuotasi vendor lebih dahulu, jadi jam mesin maupun
     * kecepatan yang diminta tidak menentukan kapan pesanannya selesai.
     */
    public function test_rumus_harga_manual_lima_sampai_tujuh_hari(): void
    {
        $this->assertSame('Standard (5–7 Hari Kerja)', LeadTime::label(LeadTime::STANDARD, manualPricing: true));
        $this->assertSame('Standard (5–7 Hari Kerja)', LeadTime::label(LeadTime::EXPRESS, manualPricing: true));
        $this->assertSame(['min' => 5, 'max' => 7], LeadTime::days(LeadTime::EXPRESS, manualPricing: true));
    }

    public function test_penawaran_berteknologi_rumus_manual_memakai_lima_sampai_tujuh_hari(): void
    {
        $material = $this->materialManual();

        $quotation = $this->penawaran([8], LeadTime::EXPRESS);
        $quotation->items()->update([
            'technology' => SlaIndustries::CODE,
            'material' => $material->material,
        ]);

        $quotation = $quotation->fresh();

        $this->assertSame('Standard (5–7 Hari Kerja)', $quotation->items->first()->lead_time);
        $this->assertSame('Standard (5–7 Hari Kerja)', $quotation->lead_time);
    }

    public function test_payload_browser_membawa_tingkat_rumus_manual(): void
    {
        $payload = LeadTime::browserPayload();

        $this->assertSame(5, $payload['manual']['minDays']);
        $this->assertSame(7, $payload['manual']['maxDays']);
    }

    /* ----------------------------------------------------------- bantuan --- */

    /**
     * Penawaran berisi beberapa object dengan total waktu yang ditentukan.
     *
     * @param  array<int, int>  $jamTiapObject
     */
    private function penawaran(array $jamTiapObject, string $speed = LeadTime::STANDARD): QuotationRequest
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
            'production_speed' => $speed,
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

    /** Material SLA yang harganya ditetapkan tim lewat Kalkulator Manual. */
    private function materialManual(): PrintMaterial
    {
        $material = PrintTechnology::where('code', SlaIndustries::CODE)
            ->firstOrFail()
            ->materials()
            ->create([
                'material' => 'Resin Kuotasi Vendor',
                'brand' => 'Uji',
                'purchase_price' => 400000,
                'sale_price' => 1500,
                'pricing_method' => PrintMaterial::PRICING_MANUAL,
            ]);

        PrintTechnology::forgetCache();

        return $material;
    }
}
