<?php

namespace Tests\Feature;

use App\Services\SellingPriceEstimator;
use App\Support\Finishing;
use App\Support\LeadTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Harga finishing dan tambahan Express.
 *
 * Keduanya diturunkan dari HARGA PRINTING — harga mencetak partnya saja —
 * bukan dari HPP maupun dari total akhir, sehingga tidak pernah saling
 * melipatgandakan:
 *
 *   Raw / No Finishing = Rp0
 *   Sanding            = MAX(Harga Printing x 30%, Rp30.000)
 *   Sanding + Painting = MAX(Harga Printing x 70%, Rp75.000)
 *   Express            = Harga Printing x 25%   (biaya finishing tidak ikut)
 *
 * Custom Finishing tidak dihitung sama sekali: harganya ditetapkan tim lewat
 * kuotasi project.
 */
class FinishingPricingTest extends TestCase
{
    use RefreshDatabase;

    /* ============================================ formula finishing === */

    /** Contoh yang diminta, persis angkanya. */
    public function test_harga_finishing_mengikuti_persen_dengan_dasar_minimum(): void
    {
        $cases = [
            // [harga printing, raw, sanding, sanding + painting]
            [50000, 0.0, 30000.0, 75000.0],
            [200000, 0.0, 60000.0, 140000.0],
            // Tepat di titik minimum berlaku.
            [100000, 0.0, 30000.0, 75000.0],
            [0, 0.0, 30000.0, 75000.0],
        ];

        foreach ($cases as [$printing, $raw, $sanding, $painting]) {
            $this->assertSame($raw, Finishing::priceFor(Finishing::NONE, $printing), "Raw pada {$printing}");
            $this->assertSame($sanding, Finishing::priceFor(Finishing::SANDING, $printing), "Sanding pada {$printing}");
            $this->assertSame($painting, Finishing::priceFor(Finishing::PAINTING, $printing), "Painting pada {$printing}");
        }
    }

    /** Di atas titik minimum, persentasenya yang berlaku. */
    public function test_persentase_berlaku_di_atas_minimum(): void
    {
        // 30% x 300.000 = 90.000, jauh di atas minimum Rp30.000.
        $this->assertSame(90000.0, Finishing::priceFor(Finishing::SANDING, 300000));

        // 70% x 300.000 = 210.000, jauh di atas minimum Rp75.000.
        $this->assertSame(210000.0, Finishing::priceFor(Finishing::PAINTING, 300000));
    }

    public function test_custom_finishing_tidak_dihitung_otomatis(): void
    {
        $this->assertTrue(Finishing::isManual(Finishing::CUSTOM));
        $this->assertNull(Finishing::priceFor(Finishing::CUSTOM, 200000));

        $this->assertFalse(Finishing::isManual(Finishing::SANDING));
        $this->assertFalse(Finishing::isManual(Finishing::NONE));
    }

    public function test_sanding_painting_menuntut_pilihan_warna(): void
    {
        $this->assertTrue(Finishing::needsColor(Finishing::PAINTING));
        $this->assertFalse(Finishing::needsColor(Finishing::SANDING));
        $this->assertFalse(Finishing::needsColor(Finishing::NONE));
    }

    public function test_tiga_pilihan_utama_beserta_custom_tersedia(): void
    {
        $this->assertSame(
            [Finishing::NONE, Finishing::SANDING, Finishing::PAINTING, Finishing::CUSTOM],
            Finishing::keys(),
        );

        $this->assertSame('Raw / No Finishing', Finishing::label(Finishing::NONE));
        $this->assertSame('Sanding', Finishing::label(Finishing::SANDING));
        $this->assertSame('Sanding + Painting', Finishing::label(Finishing::PAINTING));
        $this->assertSame('Custom Finishing', Finishing::label(Finishing::CUSTOM));
    }

    /** Keterangan yang dibaca pelanggan. */
    public function test_keterangan_finishing_untuk_pelanggan(): void
    {
        $this->assertSame('3D print tanpa proses finishing.', Finishing::description(Finishing::NONE));
        $this->assertStringContainsString('mengurangi layer line', Finishing::description(Finishing::SANDING));
        $this->assertStringContainsString('Tidak termasuk painting', Finishing::description(Finishing::SANDING));
        $this->assertStringContainsString('dipersiapkan sebelum dilakukan painting', Finishing::description(Finishing::PAINTING));
        $this->assertStringContainsString('Based on Project Quotation', Finishing::note(Finishing::CUSTOM));
    }

    /* =========================================== di dalam pricing engine === */

    /**
     * Finishing dan Express masuk sebagai komponen harga, bukan pengali total.
     *
     * Harga printing tetap sama pada ketiganya — yang berbeda hanya komponen
     * yang ditambahkan di atasnya.
     */
    public function test_pricing_engine_menambahkan_finishing_di_atas_harga_printing(): void
    {
        $raw = $this->hitung(Finishing::NONE);
        $sanding = $this->hitung(Finishing::SANDING);
        $painting = $this->hitung(Finishing::PAINTING);

        $printing = (float) $raw['printing_price'];

        $this->assertSame($printing, (float) $sanding['printing_price']);
        $this->assertSame($printing, (float) $painting['printing_price']);

        $this->assertSame(0.0, (float) $raw['finishing_price']);
        $this->assertSame(Finishing::priceFor(Finishing::SANDING, $printing), (float) $sanding['finishing_price']);
        $this->assertSame(Finishing::priceFor(Finishing::PAINTING, $printing), (float) $painting['finishing_price']);

        // Harga Jual = Harga Printing + Express + Finishing.
        $this->assertEqualsWithDelta($printing, (float) $raw['selling_price'], 0.01);
        $this->assertEqualsWithDelta(
            $printing + (float) $sanding['finishing_price'],
            (float) $sanding['selling_price'],
            0.01,
        );
    }

    /** Express menaikkan harga printing 25% dan TIDAK menyentuh biaya finishing. */
    public function test_express_hanya_mengali_harga_printing(): void
    {
        $standard = $this->hitung(Finishing::SANDING, LeadTime::STANDARD);
        $express = $this->hitung(Finishing::SANDING, LeadTime::EXPRESS);

        $printing = (float) $standard['printing_price'];

        $this->assertSame(0.0, (float) $standard['express_fee']);
        $this->assertEqualsWithDelta($printing * 0.25, (float) $express['express_fee'], 0.01);

        // Biaya finishingnya sama persis — tidak ikut dikalikan 1,25.
        $this->assertSame((float) $standard['finishing_price'], (float) $express['finishing_price']);

        $this->assertEqualsWithDelta(
            $printing * 1.25 + (float) $express['finishing_price'],
            (float) $express['selling_price'],
            0.01,
        );
    }

    /** Custom Finishing membuat harga penawaran menunggu perhitungan, bukan Rp0. */
    public function test_custom_finishing_menunggu_perhitungan(): void
    {
        $hasil = $this->hitung(Finishing::CUSTOM);

        $this->assertTrue($hasil['manual_pricing']);
        $this->assertNull($hasil['selling_price']);
        $this->assertNull($hasil['total']);
        $this->assertNull($hasil['finishing_price']);

        // Harga printingnya tetap dilaporkan sebagai titik mulai bagi tim.
        $this->assertGreaterThan(0, (float) $hasil['printing_price']);
    }

    /**
     * Kecepatan yang ditetapkan belakangan menghasilkan angka yang sama.
     *
     * Syarat Express baru diketahui setelah seluruh model diestimasi, jadi
     * rinciannya ditempeli pengalinya — hasilnya harus sama persis dengan
     * menghitungnya Express sejak awal.
     */
    public function test_menempelkan_express_sama_dengan_menghitungnya_sejak_awal(): void
    {
        $sejakAwal = $this->hitung(Finishing::SANDING, LeadTime::EXPRESS);
        $ditempel = app(SellingPriceEstimator::class)
            ->withProductionSpeed($this->hitung(Finishing::SANDING, LeadTime::STANDARD), LeadTime::EXPRESS);

        $this->assertSame((float) $sejakAwal['express_fee'], (float) $ditempel['express_fee']);
        $this->assertSame((float) $sejakAwal['selling_price'], (float) $ditempel['selling_price']);
        $this->assertSame(LeadTime::EXPRESS, $ditempel['production_speed']);
    }

    /** @return array<string, mixed> */
    private function hitung(string $finishing, string $speed = LeadTime::STANDARD): array
    {
        return app(SellingPriceEstimator::class)->calculate([
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'quantity' => 1,
            'total_weight_g' => 120,
            'minutes' => 180,
            'dimensions' => ['x' => 50, 'y' => 40, 'z' => 30],
            'finishing' => $finishing,
            'production_speed' => $speed,
        ]);
    }
}
