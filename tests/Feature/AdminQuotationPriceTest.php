<?php

namespace Tests\Feature;

use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\SellingPriceEstimator;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Halaman Admin → Penawaran → Detail: seluruh harganya satu sumber.
 *
 * Kartu "Harga Jual · Harga Estimasi", tabel ringkas model, dan kartu tiap
 * model membaca hasil App\Services\SellingPriceEstimator yang sama dengan
 * tabel Detail Perhitungan Harga — bukan kolom `estimated_price` penawaran.
 *
 * Bedanya baru terlihat pada penawaran yang dibuat sebelum rumus Price List
 * berlaku: modelnya tidak menyimpan `cost_breakdown`, jadi rinciannya disusun
 * ulang dan hampir pasti tidak sama dengan harga lama yang tercatat. Di situlah
 * kartu merah dulu menampilkan angka yang berbeda dari rinciannya sendiri.
 */
class AdminQuotationPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function admin(): User
    {
        return User::factory()->admin()->create([
            'email' => 'admin@nusama3d.com',
            'password' => Hash::make('rahasia123'),
        ]);
    }

    private function rupiah(float $value): string
    {
        return 'Rp'.number_format($value, 0, ',', '.');
    }

    /**
     * Penawaran lama: harga tercatat Rp300.000 per model, tanpa perhitungan
     * tersimpan. Rinciannya disusun ulang dari Price List yang berlaku
     * sekarang, jadi angkanya berbeda dari yang tercatat.
     */
    private function penawaranLama(): QuotationRequest
    {
        $quotation = QuotationRequest::create([
            'tracking_number' => 'QT-LAMA01',
            'name' => 'David Kurniawan',
            'email' => 'david@contoh.test',
            'whatsapp' => '081234567890',
            'quantity' => 3,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['dimensions' => ['x' => 250, 'y' => 150, 'z' => 100]],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'estimated_minutes' => 180,
            'estimated_cost' => 600000,
            'estimated_price' => 600000,
            'status' => QuotationStatus::REVIEWING,
        ]);

        $model = fn (int $position, string $nama, array $dimensi, int $qty) => [
            'position' => $position,
            'file_name' => $nama,
            'file_path' => 'quotations/2026-09/'.$nama,
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['dimensions' => $dimensi],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => $qty,
            'scale_percent' => 100,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'infill_density' => 0.2,
            'model_volume_cm3' => 120,
            'estimated_weight_g' => 100,
            'support_weight_g' => 0,
            'estimated_minutes' => 120,

            // Harga lama, tanpa `cost_breakdown`: inilah yang tidak boleh lagi
            // muncul di kartu merah.
            'estimated_cost' => 300000,
        ];

        $quotation->items()->create($model(1, 'bracket.stl', ['x' => 250, 'y' => 150, 'z' => 100], 2));
        $quotation->items()->create($model(2, 'clip.stl', ['x' => 50, 'y' => 40, 'z' => 30], 1));

        return $quotation->fresh();
    }

    public function test_kartu_harga_memakai_harga_jual_bukan_harga_tercatat(): void
    {
        $quotation = $this->penawaranLama();
        $rincian = app(SellingPriceEstimator::class)->forQuotation($quotation);

        // Fixture-nya memang menyimpang — kalau tidak, pengujiannya tidak
        // membuktikan apa pun.
        $this->assertTrue($rincian['reconstructed']);
        $this->assertNotEqualsWithDelta(
            (float) $quotation->estimated_price,
            (float) $rincian['selling_price'],
            0.01,
        );

        $response = $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk();

        // Angka yang benar-benar tercetak di dalam kartu merah, bukan sekadar
        // ada di suatu tempat pada halaman.
        $this->assertSame(
            $this->rupiah((float) $rincian['selling_price']),
            $this->angkaKartuHarga($response->getContent()),
        );

        // Harga lama yang tercatat tidak lagi muncul di kartu itu; tempatnya
        // sekarang hanya di dalam rincian, sebagai "Tercatat pada Penawaran".
        $this->assertNotSame(
            $this->rupiah((float) $quotation->estimated_price),
            $this->angkaKartuHarga($response->getContent()),
        );
    }

    /** Angka besar di dalam kartu "Harga Jual · Harga Estimasi". */
    private function angkaKartuHarga(string $html): string
    {
        $pattern = '/Harga Jual &middot; Harga Estimasi.*?<p[^>]*>\s*(Rp[\d.]+)\s*<\/p>/s';

        $this->assertSame(1, preg_match($pattern, $html, $matches), 'Kartu harga tidak ditemukan.');

        return $matches[1];
    }

    public function test_harga_tiap_model_sama_dengan_accordion_rinciannya(): void
    {
        $quotation = $this->penawaranLama();
        $rincian = app(SellingPriceEstimator::class)->forQuotation($quotation);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk();

        foreach ($rincian['models'] as $entry) {
            $response->assertSee($this->rupiah((float) $entry['calculation']['selling_price']));
        }

        // Harga lama tiap model tidak muncul sama sekali.
        $response->assertDontSee($this->rupiah(300000));
    }

    public function test_total_kartu_sama_dengan_penjumlahan_harga_jual_model(): void
    {
        $quotation = $this->penawaranLama();
        $rincian = app(SellingPriceEstimator::class)->forQuotation($quotation);

        $this->assertSame(
            round($rincian['models']->sum(fn (array $entry) => (float) $entry['calculation']['selling_price']), 2),
            (float) $rincian['selling_price'],
        );
    }

    public function test_penawaran_baru_kartu_dan_rincian_tetap_sama(): void
    {
        // Penawaran yang dibuat dengan rumus yang berlaku sekarang: rinciannya
        // tersimpan, jadi kartu, rincian, dan harga tercatat semuanya bertemu
        // di satu angka.
        $quotation = $this->penawaranLama();

        $estimator = app(SellingPriceEstimator::class);

        $quotation->items->each(function (QuotationItem $item) use ($estimator) {
            $pricing = $estimator->calculate([
                'technology' => $item->technology,
                'material' => $item->material,
                'printer_name' => $item->printer_name,
                'quantity' => $item->quantity,
                'total_weight_g' => $item->total_weight_g,
                'minutes' => $item->estimated_minutes,
                'dimensions' => $item->dimensions,
            ]);

            $item->update([
                'cost_breakdown' => $pricing,
                'estimated_cost' => $pricing['selling_price'],
            ]);
        });

        $quotation->refreshSummary();
        $quotation->refresh()->load('items');

        $rincian = $estimator->forQuotation($quotation->fresh());

        $this->assertFalse($rincian['reconstructed']);
        $this->assertEqualsWithDelta(0.0, (float) $rincian['difference'], 0.01);
        $this->assertEqualsWithDelta(
            (float) $quotation->display_price,
            (float) $rincian['selling_price'],
            0.01,
        );

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee($this->rupiah((float) $rincian['selling_price']))
            ->assertSee('&check; Cocok dengan rincian di atas', false);
    }
}
