<?php

namespace Tests\Feature;

use App\Models\PricingFormula;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
use App\Support\BasicFee;
use App\Support\Printer;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Basic Fee: tingkatannya, pengaruhnya pada penawaran yang dibuat pelanggan,
 * pada rincian Harga Jual di halaman admin, dan pada simulasi Price List.
 */
class BasicFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function admin(): User
    {
        // Price List berada di area Superadmin; Superadmin juga lolos penjaga
        // admin sehingga halaman penawaran tetap dapat dibuka pada tes ini.
        return User::factory()->superAdmin()->create([
            'email' => 'superadmin@nusama3d.com',
            'password' => Hash::make('rahasia123'),
        ]);
    }

    /* ---------------------------------------------------------- tingkat --- */

    /**
     * Batasnya dibaca apa adanya dari daftar tingkat: di bawah 80 mm gratis,
     * 80 mm sampai dengan 200 mm Rp25.000, di atas 200 mm Rp50.000. Kedua
     * batasnya ikut diuji karena di situlah tingkatnya berpindah.
     */
    public function test_tingkat_basic_fee_mengikuti_ukuran(): void
    {
        $cases = [
            'contoh kecil' => [59.1, 0.0, 'Kecil'],
            'nyaris sedang' => [79.99, 0.0, 'Kecil'],
            'tepat 80 mm' => [80.0, 25000.0, 'Sedang'],
            'contoh sedang' => [100.0, 25000.0, 'Sedang'],
            'tepat 200 mm' => [200.0, 25000.0, 'Sedang'],
            'lewat 200 mm' => [200.01, 50000.0, 'Besar'],
            'contoh besar' => [250.0, 50000.0, 'Besar'],
            'tanpa ukuran' => [0.0, 0.0, 'Kecil'],
        ];

        foreach ($cases as $name => [$mm, $fee, $label]) {
            $this->assertSame($fee, BasicFee::amount($mm), $name);
            $this->assertSame($label, BasicFee::label($mm), $name);
        }
    }

    public function test_yang_dipakai_sisi_terpanjang_bukan_volume(): void
    {
        // Contoh dari spesifikasi: 49,3 x 59,1 x 30,2 mm → terbesar 59,1 mm.
        $this->assertSame(59.1, BasicFee::largestDimension(['x' => 49.3, 'y' => 59.1, 'z' => 30.2]));
        $this->assertSame(0.0, BasicFee::forDimensions(['x' => 49.3, 'y' => 59.1, 'z' => 30.2]));

        // Object pipih tapi panjang tetap masuk Besar walau volumenya kecil.
        $this->assertSame(50000.0, BasicFee::forDimensions(['x' => 250, 'y' => 5, 'z' => 5]));

        // Model tanpa catatan dimensi tidak dikenakan biaya tanpa dasar ukuran.
        $this->assertSame(0.0, BasicFee::forDimensions(null));
        $this->assertSame(0.0, BasicFee::forDimensions([]));
    }

    /* ------------------------------------------ estimasi harga penawaran --- */

    /**
     * Harga Jual satu model dari sebuah estimasi.
     *
     * PrintEstimator tidak lagi menghitung harga; berat dan waktunya masuk ke
     * satu-satunya rumus harga yang ada.
     *
     * @param  array<string, mixed>  $estimate
     * @param  array<string, mixed>|null  $dimensions
     * @return array<string, mixed>
     */
    private function priceOf(array $estimate, ?array $dimensions, int $quantity = 1): array
    {
        return app(SellingPriceEstimator::class)->calculate([
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'printer_name' => Printer::name(null),
            'quantity' => $quantity,
            'total_weight_g' => $estimate['total_weight_g'],
            'minutes' => $estimate['total_minutes'],
            'dimensions' => $dimensions,
        ]);
    }

    public function test_ukuran_object_menaikkan_harga_lewat_basic_fee(): void
    {
        $estimator = app(PrintEstimator::class);

        // Support dimatikan: dimensi juga dipakai App\Services\SupportEstimator
        // untuk menaksir volume support, jadi mematikannya membuat Basic Fee
        // menjadi satu-satunya komponen yang boleh berubah karena ukuran.
        $options = ['surface_area_cm2' => 180];

        $kecilDim = ['x' => 50, 'y' => 40, 'z' => 30];
        $sedangDim = ['x' => 100, 'y' => 80, 'z' => 50];
        $besarDim = ['x' => 250, 'y' => 150, 'z' => 100];

        $estimate = $estimator->estimate('FDM', 'PLA Plus Standart ESUN', 120, 2, $options);

        $kecil = $this->priceOf($estimate, $kecilDim, 2);
        $sedang = $this->priceOf($estimate, $sedangDim, 2);
        $besar = $this->priceOf($estimate, $besarDim, 2);

        $this->assertSame(0.0, $kecil['basic_fee']);
        $this->assertSame(25000.0, $sedang['basic_fee']);
        $this->assertSame(50000.0, $besar['basic_fee']);

        // Subtotal dan Profit tidak tersentuh ukuran object; Basic Fee berdiri
        // sendiri sesudahnya. Packaging dikecualikan karena kardusnya memang
        // dipilih menurut ukuran model.
        $this->assertSame($kecil['hpp'], $besar['hpp']);
        $this->assertSame($kecil['risk_cost'], $besar['risk_cost']);

        // Yang berubah pada Harga Jual persis sebesar selisih Basic Fee dan
        // Packaging-nya saja.
        $this->assertSame(
            round($besar['subtotal'] + $besar['profit'] + $besar['basic_fee'], 2),
            $besar['selling_price'],
        );
        $this->assertGreaterThan($kecil['selling_price'], $sedang['selling_price']);
        $this->assertGreaterThan($sedang['selling_price'], $besar['selling_price']);

        $this->assertSame(250.0, $besar['largest_dimension_mm']);
        $this->assertSame('Besar', $besar['basic_fee_label']);
    }

    public function test_basic_fee_dikenakan_sekali_per_model_bukan_per_unit(): void
    {
        $estimator = app(PrintEstimator::class);
        $dimensions = ['x' => 100, 'y' => 80, 'z' => 50];

        $satu = $this->priceOf($estimator->estimate('FDM', 'PLA Plus Standart ESUN', 120, 1, ['surface_area_cm2' => 180]), $dimensions, 1);
        $sepuluh = $this->priceOf($estimator->estimate('FDM', 'PLA Plus Standart ESUN', 120, 10, ['surface_area_cm2' => 180]), $dimensions, 10);

        $this->assertSame(25000.0, $satu['basic_fee']);
        $this->assertSame(25000.0, $sepuluh['basic_fee']);
    }

    public function test_penawaran_yang_dibuat_pelanggan_sudah_memakai_basic_fee(): void
    {
        $this->actingAs(User::factory()->create([
            'name' => 'David Kurniawan',
            'email' => 'david@contoh.test',
            'phone' => '0812 3456 7890',
        ]));

        $this->postJson(route('quotations.store'), [
            'name' => 'David Kurniawan',
            'email' => 'david@contoh.test',
            'whatsapp' => '0812 3456 7890',
            'items' => [[
                'model' => UploadedFile::fake()->createWithContent('bracket.stl', 'solid test'),
                'quantity' => 1,
                'technology' => 'FDM',
                'material' => 'PLA Plus Standart ESUN',
                'model_volume_cm3' => 100,
                'analysis_status' => QuotationRequest::ANALYSIS_READY,
                'analysis' => json_encode([['id' => 'watertight', 'label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.']]),
                'model_stats' => json_encode([
                    'vertices' => 36,
                    'triangles' => 12,
                    'dimensions' => ['x' => 250, 'y' => 150, 'z' => 100],
                    'watertight' => true,
                ]),
            ]],
        ])->assertCreated();

        $quotation = QuotationRequest::sole();
        $item = $quotation->items()->sole();

        $this->assertSame(50000.0, (float) $item->cost_breakdown['basic_fee']);
        $this->assertSame(50000.0, (float) $quotation->cost_breakdown['basic_fee']);

        // Total penawaran yang ditawarkan ke pelanggan sudah termasuk di dalamnya.
        $this->assertSame(
            (float) $item->cost_breakdown['total'],
            (float) $quotation->estimated_price,
        );

        // Penawaran baru langsung masuk tahap review.
        $this->assertSame(QuotationStatus::REVIEWING, $quotation->status);
    }

    /* ----------------------------------- rincian Harga Jual milik admin --- */

    private function quotationWithItem(array $dimensions): QuotationItem
    {
        $quotation = QuotationRequest::create([
            'tracking_number' => 'QT-'.strtoupper(fake()->bothify('####')),
            'name' => 'David Kurniawan',
            'email' => 'david@contoh.test',
            'whatsapp' => '081234567890',
            'quantity' => 2,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['dimensions' => $dimensions],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
            'estimated_price' => 300000,
            'status' => QuotationStatus::REVIEWING,
        ]);

        return $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['dimensions' => $dimensions],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 2,
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
    }

    public function test_harga_jual_penawaran_menambahkan_basic_fee(): void
    {
        $estimator = app(SellingPriceEstimator::class);

        $kecil = $estimator->forItem($this->quotationWithItem(['x' => 50, 'y' => 40, 'z' => 30]));
        $besar = $estimator->forItem($this->quotationWithItem(['x' => 250, 'y' => 150, 'z' => 100]));

        $this->assertSame(0.0, $kecil['basic_fee']);
        $this->assertSame(50000.0, $besar['basic_fee']);

        // Subtotal dan Profit tidak tersentuh — Basic Fee ditambahkan sesudahnya.
        $this->assertSame($kecil['subtotal'], $besar['subtotal']);
        $this->assertSame($kecil['profit'], $besar['profit']);

        $this->assertSame(
            round($besar['subtotal'] + $besar['profit'] + $besar['basic_fee'], 2),
            $besar['selling_price'],
        );
        $this->assertSame(round($kecil['selling_price'] + 50000, 2), $besar['selling_price']);
    }

    public function test_detail_admin_menampilkan_baris_basic_fee(): void
    {
        $item = $this->quotationWithItem(['x' => 250, 'y' => 150, 'z' => 100]);

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $item->quotationRequest))
            ->assertOk()
            ->assertSee('Basic Fee')
            ->assertSee('Subtotal + Profit + Basic Fee')
            ->assertSee('Rp50.000');
    }

    /* -------------------------------------- simulasi Price List → Harga --- */

    public function test_tab_harga_menurunkan_basic_fee_dari_ukuran_object(): void
    {
        $formula = PricingFormula::where('technology', 'FDM')->sole();

        $formula->update(['object_size_mm' => 59.1]);
        $this->assertSame(0.0, $formula->fresh()->basic_fee);

        $formula->update(['object_size_mm' => 100]);
        $formula = $formula->fresh();

        $this->assertSame(25000.0, $formula->basic_fee);
        $this->assertSame('Sedang', $formula->basic_fee_label);
        $this->assertSame(round($formula->subtotal + $formula->profit + 25000, 2), round($formula->selling_price, 2));

        $rows = collect($formula->breakdown());
        $basicFeeRow = $rows->firstWhere('label', 'Basic Fee');
        $sellingRow = $rows->firstWhere('label', 'Harga Jual');

        $this->assertNotNull($basicFeeRow);
        $this->assertSame(25000.0, $basicFeeRow['value']);
        $this->assertSame('Subtotal + Profit + Basic Fee', $sellingRow['formula']);
    }

    public function test_keempat_teknologi_punya_basic_fee(): void
    {
        foreach (PricingFormula::technologies() as $technology) {
            $formula = PricingFormula::where('technology', $technology)->sole();
            $formula->update(['object_size_mm' => 250]);
            $formula = $formula->fresh();

            $this->assertSame(50000.0, $formula->basic_fee, $technology.' tidak memakai Basic Fee');
            $this->assertSame(
                round($formula->subtotal + $formula->profit + 50000, 2),
                round($formula->selling_price, 2),
                $technology.' Harga Jual belum menambahkan Basic Fee',
            );
        }
    }

    public function test_tab_harga_menampilkan_baris_dan_isian_basic_fee(): void
    {
        PricingFormula::general()->update(['object_size_mm' => 250]);

        $this->actingAs($this->admin())
            ->get(route('superadmin.price-list.harga'))
            ->assertOk()
            ->assertSee('Rincian Harga Jual')
            ->assertDontSee('Rincian Harga Jual: FDM', false)
            ->assertSee('Basic Fee')
            ->assertSee('Subtotal + Profit + Basic Fee')
            ->assertSee('Ukuran 3D Object, sisi terpanjang (mm)', false)
            ->assertSee('name="object_size_mm"', false)
            ->assertSee('Rp50.000');
    }

    public function test_admin_dapat_mengubah_ukuran_object_dari_price_list(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('superadmin.price-list.harga.update'), [
                'machine_time_hours' => 2,
                'machine_cost' => 61000,
                'material_qty_g' => 800,
                'material_price_per_g' => 280,
                'risk_percent' => 30,
                'packaging_cost' => 5000,
                'overtime_cost' => 0,
                'profit_percent' => 50,
                'object_size_mm' => 250,
            ])
            ->assertRedirect(route('superadmin.price-list.harga'));

        $this->assertSame(250.0, (float) PricingFormula::general()->object_size_mm);
        $this->assertSame(50000.0, PricingFormula::general()->basic_fee);
    }

    public function test_ukuran_object_wajib_diisi(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('superadmin.price-list.harga.update'), [
                'machine_time_hours' => 2,
                'machine_cost' => 61000,
                'material_qty_g' => 800,
                'material_price_per_g' => 280,
                'risk_percent' => 30,
                'packaging_cost' => 5000,
                'overtime_cost' => 0,
                'profit_percent' => 50,
            ])
            ->assertSessionHasErrors('object_size_mm');
    }
}
