<?php

namespace Tests\Feature;

use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Harga Penawaran yang dilihat pelanggan = Harga Jual.
 *
 *   Harga Jual = Subtotal + Profit + Basic Fee
 *
 * Berkas ini menjaga satu janji saja: angka yang sampai ke pelanggan — pada
 * penawarannya, pada tiap modelnya, pada halaman tracking, dan pada tagihan
 * pembayarannya — tidak pernah berasal dari HPP, Subtotal, atau perhitungan
 * lain, melainkan dari Harga Jual yang tersimpan pada `cost_breakdown`.
 */
class OfferPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /** Harga Jual sebuah model, dihitung ulang dari komponen tersimpannya. */
    private function hargaJual(QuotationItem $item): float
    {
        $breakdown = $item->cost_breakdown;

        return round(
            (float) $breakdown['subtotal']
            + (float) $breakdown['profit']
            + (float) $breakdown['basic_fee'],
            2,
        );
    }

    /**
     * Dua model dengan ukuran berbeda supaya tingkat Basic Fee-nya ikut
     * berbeda — bila harga penawaran diam-diam memakai Subtotal, selisihnya
     * langsung terlihat.
     */
    private function kirimPenawaran(): QuotationRequest
    {
        $user = User::factory()->create([
            'name' => 'Rani Prameswari',
            'email' => 'rani@contoh.test',
            'phone' => '0812 1111 2222',
        ]);

        $model = fn (string $nama, array $dimensi, int $volume, int $qty) => [
            'model' => UploadedFile::fake()->createWithContent($nama, 'solid test'),
            'quantity' => $qty,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'model_volume_cm3' => $volume,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'analysis' => json_encode([['id' => 'watertight', 'label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.']]),
            'model_stats' => json_encode([
                'vertices' => 36,
                'triangles' => 12,
                'dimensions' => $dimensi,
                'watertight' => true,
            ]),
        ];

        $this->actingAs($user)
            ->postJson(route('quotations.store'), [
                'name' => $user->name,
                'email' => $user->email,
                'whatsapp' => $user->phone,
                'items' => [
                    // Besar: Basic Fee tingkat tertinggi.
                    $model('bracket.stl', ['x' => 250, 'y' => 150, 'z' => 100], 100, 2),
                    // Kecil: tanpa Basic Fee sama sekali.
                    $model('clip.stl', ['x' => 50, 'y' => 40, 'z' => 30], 20, 1),
                ],
            ])
            ->assertCreated();

        return QuotationRequest::sole();
    }

    public function test_harga_tiap_model_adalah_harga_jual_model_itu(): void
    {
        $quotation = $this->kirimPenawaran();
        $items = $quotation->items()->orderBy('position')->get();

        $this->assertCount(2, $items);

        // Ketentuan 10: banyak model, tiap model memakai Harga Jual-nya sendiri.
        foreach ($items as $item) {
            $hargaJual = $this->hargaJual($item);

            $this->assertSame($hargaJual, (float) $item->cost_breakdown['selling_price']);
            $this->assertSame($hargaJual, (float) $item->estimated_cost);
            $this->assertSame($hargaJual, (float) $item->display_price);

            // Bukan HPP, bukan Subtotal.
            $this->assertNotSame((float) $item->cost_breakdown['hpp'], (float) $item->display_price);
            $this->assertNotSame((float) $item->cost_breakdown['subtotal'], (float) $item->display_price);
        }

        // Basic Fee memang berbeda antar keduanya, jadi keduanya benar-benar
        // memakai Harga Jual miliknya sendiri.
        $this->assertSame(50000.0, (float) $items[0]->cost_breakdown['basic_fee']);
        $this->assertSame(0.0, (float) $items[1]->cost_breakdown['basic_fee']);
    }

    public function test_total_penawaran_adalah_penjumlahan_seluruh_harga_jual(): void
    {
        $quotation = $this->kirimPenawaran();
        $total = round($quotation->items->sum(fn (QuotationItem $item) => $this->hargaJual($item)), 2);

        // Ketentuan 11: totalnya penjumlahan Harga Jual seluruh model.
        $this->assertSame($total, (float) $quotation->estimated_price);
        $this->assertSame($total, (float) $quotation->display_price);
        $this->assertSame($total, (float) $quotation->payment_amount);

        // Dan sama dengan penjumlahan komponen Harga Jual pada rincian
        // penawarannya: Subtotal + Profit + Basic Fee.
        $breakdown = $quotation->cost_breakdown;
        $this->assertSame(
            $total,
            round((float) $breakdown['subtotal'] + (float) $breakdown['profit'] + (float) $breakdown['basic_fee'], 2),
        );
    }

    public function test_halaman_pelanggan_menampilkan_harga_jual(): void
    {
        $quotation = $this->kirimPenawaran();
        $rupiah = fn (float $value) => 'Rp'.number_format($value, 0, ',', '.');

        $total = $rupiah((float) $quotation->display_price);
        $pertama = $rupiah($this->hargaJual($quotation->items->first()));

        $this->actingAs($quotation->user)
            ->get(route('dashboard.quotations.show', $quotation))
            ->assertOk()
            ->assertSee($total)
            ->assertSee($pertama);

        $this->get(route('tracking.show', $quotation->tracking_number))
            ->assertOk()
            ->assertSee($total);
    }

    public function test_harga_mengikuti_harga_jual_saat_spesifikasi_berubah(): void
    {
        $quotation = $this->kirimPenawaran();
        $item = $quotation->items()->orderBy('position')->first();
        $sebelum = (float) $quotation->display_price;

        // Jumlah cetak dinaikkan: Material, Packaging, dan karenanya Subtotal
        // dan Profit ikut naik — Basic Fee tetap karena melekat pada objectnya.
        $this->actingAs($quotation->user)
            ->patch(route('dashboard.quotations.items.update', [$quotation, $item]), [
                'quantity' => 5,
                'technology' => $item->technology,
                'material' => $item->material,
                'material_color' => $item->material_color,
                'printer' => $item->printer,
                'resolution' => $item->resolution,
                'infill_density' => $item->infill_density,
                'infill_pattern' => $item->infill_pattern,
                'finishing' => $item->finishing,
            ])
            ->assertRedirect();

        $quotation->refresh()->load('items');
        $item->refresh();

        // Ketentuan 7: harganya bergeser, dan tetap sama dengan Harga Jual.
        $this->assertNotSame($sebelum, (float) $quotation->display_price);
        $this->assertSame($this->hargaJual($item), (float) $item->display_price);
        $this->assertSame(
            round($quotation->items->sum(fn (QuotationItem $row) => $this->hargaJual($row)), 2),
            (float) $quotation->display_price,
        );
    }
}
