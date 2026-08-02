<?php

namespace Tests\Feature;

use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\PrintEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuotationRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Permintaan penawaran kini hanya dapat dikirim dari akun pelanggan
        // yang sudah masuk; halaman 3D Models beserta simulasinya tetap
        // terbuka untuk siapa saja.
        $this->customer = User::factory()->create();
        $this->actingAs($this->customer);
    }

    public function test_tamu_tidak_dapat_mengirim_permintaan_penawaran(): void
    {
        auth()->logout();

        $this->postJson(route('quotations.store'), $this->payload())->assertUnauthorized();

        $this->assertSame(0, QuotationRequest::count());
    }

    public function test_penawaran_melekat_pada_akun_pembuatnya(): void
    {
        $this->postJson(route('quotations.store'), $this->payload())->assertCreated();

        $this->assertSame($this->customer->id, QuotationRequest::sole()->user_id);
    }

    public function test_admin_menerima_notifikasi_penawaran_baru(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson(route('quotations.store'), $this->payload())->assertCreated();

        $this->assertSame(1, $admin->unreadNotifications()->count());
        $this->assertStringContainsString(
            'Rangga Prasetya',
            $admin->notifications()->first()->data['title']
        );
    }

    /**
     * Payload bentuk lama: satu model dengan field di tingkat atas.
     *
     * Bentuk ini tetap diterima endpoint dan dipetakan menjadi `items[0]`.
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rangga Prasetya',
            'email' => 'rangga@contoh.test',
            'whatsapp' => '0812 3456 7890',
            'company' => 'PT Contoh Sejahtera',
            'quantity' => 3,
            'notes' => 'Mohon warna hitam doff.',
            'model' => UploadedFile::fake()->createWithContent('bracket.stl', 'solid test'),
            'technology' => 'FDM',
            'material' => 'PLA',
            'model_volume_cm3' => 120.5,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'analysis' => json_encode([
                ['id' => 'watertight', 'label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.'],
            ]),
            'model_stats' => json_encode([
                'vertices' => 36,
                'triangles' => 12,
                'dimensions' => ['x' => 50, 'y' => 40, 'z' => 30],
                'watertight' => true,
            ]),
        ], $overrides);
    }

    /**
     * Satu baris `items[]` — satu model beserta pengaturannya sendiri.
     *
     * @return array<string, mixed>
     */
    private function item(string $fileName, array $overrides = []): array
    {
        return array_merge([
            'model' => UploadedFile::fake()->createWithContent($fileName, 'solid test'),
            'quantity' => 1,
            'technology' => 'FDM',
            'material' => 'PLA',
            'model_volume_cm3' => 100,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'analysis' => json_encode([
                ['id' => 'watertight', 'label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.'],
            ]),
            'model_stats' => json_encode([
                'vertices' => 36,
                'triangles' => 12,
                'dimensions' => ['x' => 50, 'y' => 40, 'z' => 30],
                'watertight' => true,
            ]),
        ], $overrides);
    }

    /**
     * Payload permintaan berisi beberapa model sekaligus.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function multiPayload(array $items, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rangga Prasetya',
            'email' => 'rangga@contoh.test',
            'whatsapp' => '0812 3456 7890',
            'company' => 'PT Contoh Sejahtera',
            'notes' => 'Mohon warna hitam doff.',
            'items' => $items,
        ], $overrides);
    }

    public function test_permintaan_penawaran_tersimpan_beserta_berkasnya(): void
    {
        $response = $this->postJson(route('quotations.store'), $this->payload());

        $response->assertCreated()->assertJsonStructure(['message', 'tracking_number', 'tracking_url', 'document_url']);

        $quotation = QuotationRequest::sole();

        $this->assertSame('Rangga Prasetya', $quotation->name);
        $this->assertSame('FDM', $quotation->technology);
        $this->assertSame('PLA', $quotation->material);
        $this->assertSame(3, $quotation->quantity);
        $this->assertSame('STL', $quotation->file_format);
        $this->assertSame('bracket.stl', $quotation->file_name);
        $this->assertSame(12, $quotation->model_stats['triangles']);
        $this->assertSame('received', $quotation->status);
        $this->assertMatchesRegularExpression('/^QTN-\d{8}-[A-Z0-9]{6}$/', $quotation->tracking_number);

        Storage::disk('local')->assertExists($quotation->file_path);

        // Riwayat langsung terisi satu entri sejak permintaan masuk.
        $this->assertSame(1, $quotation->histories()->count());
        $this->assertSame('received', $quotation->histories()->first()->status);
    }

    public function test_estimasi_dihitung_ulang_di_server_bukan_diambil_dari_klien(): void
    {
        $this->postJson(route('quotations.store'), $this->payload())->assertCreated();

        $expected = app(PrintEstimator::class)->estimate('FDM', 'PLA', 120.5, 3);
        $quotation = QuotationRequest::sole();

        $this->assertEqualsWithDelta($expected['weight_g'], (float) $quotation->estimated_weight_g, 0.01);
        $this->assertSame($expected['total_minutes'], $quotation->estimated_minutes);
        $this->assertEqualsWithDelta($expected['total_cost'], (float) $quotation->estimated_cost, 0.01);
    }

    public function test_tanpa_support_berat_support_nol(): void
    {
        $this->postJson(route('quotations.store'), $this->payload(['support_enabled' => '0']))->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertFalse($quotation->support_enabled);
        $this->assertSame(0.0, (float) $quotation->support_weight_g);
        $this->assertSame((float) $quotation->estimated_weight_g, $quotation->total_weight_g);
    }

    public function test_support_menambah_berat_waktu_dan_biaya(): void
    {
        $this->postJson(route('quotations.store'), $this->payload(['support_enabled' => '0']))->assertCreated();
        $tanpaSupport = QuotationRequest::sole();

        $baseWeight = (float) $tanpaSupport->estimated_weight_g;
        $baseMinutes = $tanpaSupport->estimated_minutes;
        $baseCost = (float) $tanpaSupport->estimated_cost;

        $tanpaSupport->forceDelete();

        $this->postJson(route('quotations.store'), $this->payload(['support_enabled' => '1']))->assertCreated();
        $denganSupport = QuotationRequest::sole();

        $this->assertTrue($denganSupport->support_enabled);
        $this->assertGreaterThan(0, (float) $denganSupport->support_weight_g);

        // Berat model tidak berubah — support dicatat terpisah lalu dijumlahkan.
        $this->assertEqualsWithDelta($baseWeight, (float) $denganSupport->estimated_weight_g, 0.01);
        $this->assertGreaterThan($baseWeight, $denganSupport->total_weight_g);

        // Support ikut tercetak, jadi waktu dan biaya harus naik.
        $this->assertGreaterThan($baseMinutes, $denganSupport->estimated_minutes);
        $this->assertGreaterThan($baseCost, (float) $denganSupport->estimated_cost);
    }

    public function test_mjf_tidak_memakai_support_walau_diminta(): void
    {
        // MJF tidak memerlukan support karena part tertopang serbuk.
        $this->postJson(route('quotations.store'), $this->payload([
            'technology' => 'MJF',
            'material' => 'PA12',
            'support_enabled' => '1',
        ]))->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertFalse($quotation->support_enabled);
        $this->assertSame(0.0, (float) $quotation->support_weight_g);
    }

    public function test_support_pada_part_langsing_lebih_berat_daripada_part_pendek(): void
    {
        $estimator = app(PrintEstimator::class);

        $pendek = $estimator->estimate('FDM', 'PLA', 100, 1, [
            'support' => true,
            'dimensions' => ['x' => 100, 'y' => 20, 'z' => 100],
        ]);

        $langsing = $estimator->estimate('FDM', 'PLA', 100, 1, [
            'support' => true,
            'dimensions' => ['x' => 20, 'y' => 200, 'z' => 20],
        ]);

        $this->assertGreaterThan($pendek['support_weight_g'], $langsing['support_weight_g']);
    }

    public function test_volume_support_terukur_dipakai_menggantikan_rumus_simulasi(): void
    {
        $estimator = app(PrintEstimator::class);

        $simulasi = $estimator->estimate('FDM', 'PLA', 100, 1, ['support' => true]);
        $terukur = $estimator->estimate('FDM', 'PLA', 100, 1, [
            'support' => true,
            'support_volume_cm3' => 12.5,
        ]);

        $this->assertFalse($simulasi['support_measured']);
        $this->assertTrue($terukur['support_measured']);
        $this->assertEqualsWithDelta(12.5, $terukur['support_volume_cm3'], 0.001);
        // PLA 1,24 g/cm3
        $this->assertEqualsWithDelta(15.5, $terukur['support_weight_g'], 0.01);
    }

    public function test_volume_support_terukur_nol_dihormati_bukan_jatuh_ke_rumus(): void
    {
        // Pada orientasi tertentu tidak ada overhang yang perlu ditopang.
        // Nol yang terukur harus dipakai apa adanya, bukan diganti perkiraan.
        $estimate = app(PrintEstimator::class)->estimate('FDM', 'PLA', 100, 1, [
            'support' => true,
            'support_volume_cm3' => 0,
        ]);

        $this->assertTrue($estimate['support_measured']);
        $this->assertSame(0.0, $estimate['support_weight_g']);
        $this->assertSame($estimate['weight_g'], $estimate['total_weight_g']);
    }

    public function test_permintaan_menyimpan_volume_support_terukur(): void
    {
        $this->postJson(route('quotations.store'), $this->payload([
            'support_enabled' => '1',
            'support_volume_cm3' => '7.25',
        ]))->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertTrue($quotation->support_enabled);
        $this->assertEqualsWithDelta(7.25, (float) $quotation->support_volume_cm3, 0.001);
        $this->assertEqualsWithDelta(7.25 * 1.24, (float) $quotation->support_weight_g, 0.01);
    }

    public function test_resolusi_lebih_halus_menambah_waktu_dan_biaya(): void
    {
        $estimator = app(PrintEstimator::class);

        $draft = $estimator->estimate('FDM', 'PLA', 100, 1, ['resolution' => '0.50']);
        $normal = $estimator->estimate('FDM', 'PLA', 100, 1, ['resolution' => '0.25']);
        $fine = $estimator->estimate('FDM', 'PLA', 100, 1, ['resolution' => '0.10']);
        $ultra = $estimator->estimate('FDM', 'PLA', 100, 1, ['resolution' => '0.05']);

        // Semakin tipis lapisannya, semakin lama dan semakin mahal.
        $this->assertGreaterThan($draft['total_minutes'], $normal['total_minutes']);
        $this->assertGreaterThan($normal['total_minutes'], $fine['total_minutes']);
        $this->assertGreaterThan($fine['total_minutes'], $ultra['total_minutes']);

        $this->assertGreaterThan($draft['total_cost'], $normal['total_cost']);
        $this->assertGreaterThan($normal['total_cost'], $fine['total_cost']);
        $this->assertGreaterThan($fine['total_cost'], $ultra['total_cost']);

        // Label kualitas ikut menyesuaikan.
        $this->assertSame('Draft', $draft['quality']);
        $this->assertSame('Normal', $normal['quality']);
        $this->assertSame('Tinggi', $fine['quality']);
        $this->assertSame('Sangat Tinggi', $ultra['quality']);
    }

    public function test_pengali_waktu_resolusi_sesuai_config(): void
    {
        $estimator = app(PrintEstimator::class);

        $normal = $estimator->estimate('FDM', 'PLA', 200, 1, ['resolution' => '0.25']);
        $ultra = $estimator->estimate('FDM', 'PLA', 200, 1, ['resolution' => '0.05']);

        $setupMinutes = config('printing.technologies.FDM.setup_hours') * 60;

        // Waktu cetak murni (di luar setup) dipengaruhi dua pengali sekaligus:
        // pengali waktu (2,0) dan pengali material (1,04) pada 0,05 mm.
        $expected = ($normal['total_minutes'] - $setupMinutes)
            * config('printing.resolutions.options.0\.05.time_multiplier', 2.0)
            * config('printing.resolutions.options.0\.05.material_multiplier', 1.04);

        $this->assertEqualsWithDelta($expected, $ultra['total_minutes'] - $setupMinutes, 2);
    }

    public function test_resolusi_default_dipakai_bila_tidak_dikirim(): void
    {
        $this->postJson(route('quotations.store'), $this->payload())->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertSame('0.25', $quotation->resolution);
        $this->assertEqualsWithDelta(0.25, (float) $quotation->layer_height_mm, 0.001);
        $this->assertSame('Normal', $quotation->quality_label);
    }

    public function test_resolusi_pilihan_tersimpan_pada_permintaan(): void
    {
        $this->postJson(route('quotations.store'), $this->payload(['resolution' => '0.05']))->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertSame('0.05', $quotation->resolution);
        $this->assertEqualsWithDelta(0.05, (float) $quotation->layer_height_mm, 0.001);
        $this->assertSame('Sangat Tinggi', $quotation->quality_label);
        $this->assertSame('0,05 mm (Ultra Fine)', $quotation->resolution_label);
    }

    public function test_resolusi_tidak_dikenal_ditolak(): void
    {
        $this->postJson(route('quotations.store'), $this->payload(['resolution' => '1.00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.resolution');
    }

    public function test_resolusi_di_luar_rentang_teknologi_ditandai(): void
    {
        $estimator = app(PrintEstimator::class);

        // MJF bekerja pada tebal lapisan tetap 0,08 mm.
        $mjf = $estimator->estimate('MJF', 'PA12', 100, 1, ['resolution' => '0.50']);

        $this->assertFalse($mjf['resolution_within_range']);
        $this->assertStringContainsString('0,08 mm', $mjf['resolution_notice']);

        // FDM 0,10 – 0,30 mm, jadi 0,25 mm masih di dalam rentang.
        $fdm = $estimator->estimate('FDM', 'PLA', 100, 1, ['resolution' => '0.25']);

        $this->assertTrue($fdm['resolution_within_range']);
        $this->assertNull($fdm['resolution_notice']);
    }

    public function test_material_yang_tidak_tersedia_pada_teknologi_ditolak(): void
    {
        $this->postJson(route('quotations.store'), $this->payload([
            'technology' => 'FDM',
            'material' => 'Titanium',
        ]))->assertStatus(422)->assertJsonValidationErrors('items.0.material');

        $this->assertSame(0, QuotationRequest::count());
    }

    public function test_format_file_selain_stl_dan_obj_ditolak(): void
    {
        $this->postJson(route('quotations.store'), $this->payload([
            'model' => UploadedFile::fake()->create('gambar.png', 10),
        ]))->assertStatus(422)->assertJsonValidationErrors('items.0.model');
    }

    public function test_field_wajib_divalidasi(): void
    {
        $this->postJson(route('quotations.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'whatsapp', 'items']);
    }

    /* ------------------------------------------------ beberapa model ------ */

    public function test_beberapa_model_tersimpan_dalam_satu_nomor_tracking(): void
    {
        $response = $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['quantity' => 2, 'model_volume_cm3' => 120]),
            $this->item('cover.obj', ['technology' => 'SLA', 'material' => 'Standard Resin', 'model_volume_cm3' => 80]),
            $this->item('bracket.stl', ['model_volume_cm3' => 60, 'resolution' => '0.10']),
        ]));

        $response->assertCreated()->assertJsonPath('model_count', 3);

        $quotation = QuotationRequest::sole();
        $items = $quotation->items;

        $this->assertCount(3, $items);
        $this->assertSame([1, 2, 3], $items->pluck('position')->all());
        $this->assertSame(['gear.stl', 'cover.obj', 'bracket.stl'], $items->pluck('file_name')->all());

        // Satu nomor tracking untuk seluruh model.
        $this->assertMatchesRegularExpression('/^QTN-\d{8}-[A-Z0-9]{6}$/', $quotation->tracking_number);
        $this->assertSame(1, QuotationRequest::count());

        // Setiap model menyimpan berkasnya sendiri, tidak saling menimpa.
        $this->assertCount(3, $items->pluck('file_path')->unique());

        $items->each(fn ($item) => Storage::disk('local')->assertExists($item->file_path));
    }

    public function test_pengaturan_tiap_model_berdiri_sendiri(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['technology' => 'FDM', 'material' => 'PLA', 'resolution' => '0.50', 'quantity' => 2]),
            $this->item('cover.obj', ['technology' => 'SLA', 'material' => 'Standard Resin', 'resolution' => '0.05', 'quantity' => 5]),
        ]))->assertCreated();

        [$gear, $cover] = QuotationRequest::sole()->items->all();

        $this->assertSame('FDM', $gear->technology);
        $this->assertSame('PLA', $gear->material);
        $this->assertSame('0.50', $gear->resolution);
        $this->assertSame(2, $gear->quantity);

        $this->assertSame('SLA', $cover->technology);
        $this->assertSame('Standard Resin', $cover->material);
        $this->assertSame('0.05', $cover->resolution);
        $this->assertSame(5, $cover->quantity);
    }

    public function test_estimasi_tiap_model_dihitung_ulang_dan_ditotal_di_penawaran(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['model_volume_cm3' => 120, 'quantity' => 2]),
            $this->item('cover.obj', ['model_volume_cm3' => 80, 'quantity' => 1]),
        ]))->assertCreated();

        $estimator = app(PrintEstimator::class);
        $gear = $estimator->estimate('FDM', 'PLA', 120, 2);
        $cover = $estimator->estimate('FDM', 'PLA', 80, 1);

        $quotation = QuotationRequest::sole();
        [$gearItem, $coverItem] = $quotation->items->all();

        $this->assertEqualsWithDelta($gear['total_cost'], (float) $gearItem->estimated_cost, 0.01);
        $this->assertEqualsWithDelta($cover['total_cost'], (float) $coverItem->estimated_cost, 0.01);

        // Baris penawaran menyimpan totalnya, sehingga daftar admin tidak perlu
        // memuat seluruh model hanya untuk menampilkan nilai permintaan.
        $this->assertEqualsWithDelta(
            $gear['total_cost'] + $cover['total_cost'],
            (float) $quotation->estimated_cost,
            0.01
        );
        $this->assertSame($gear['total_minutes'] + $cover['total_minutes'], $quotation->estimated_minutes);
        $this->assertSame(3, $quotation->quantity);
    }

    public function test_status_analisis_penawaran_mengikuti_model_terburuk(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['analysis_status' => QuotationRequest::ANALYSIS_READY]),
            $this->item('cover.obj', ['analysis_status' => QuotationRequest::ANALYSIS_NOT_PRINTABLE]),
            $this->item('bracket.stl', ['analysis_status' => QuotationRequest::ANALYSIS_WARNING]),
        ]))->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertSame(QuotationRequest::ANALYSIS_NOT_PRINTABLE, $quotation->analysis_status);

        // Status tiap model tetap tersimpan apa adanya.
        $this->assertSame(
            [QuotationRequest::ANALYSIS_READY, QuotationRequest::ANALYSIS_NOT_PRINTABLE, QuotationRequest::ANALYSIS_WARNING],
            $quotation->items->pluck('analysis_status')->all()
        );
    }

    public function test_satu_model_bermasalah_membatalkan_seluruh_permintaan(): void
    {
        // Berbeda dengan pratinjau di browser, permintaan yang sudah terkirim
        // harus utuh: tidak boleh ada model yang diam-diam hilang.
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl'),
            $this->item('gambar.png'),
        ]))->assertStatus(422)->assertJsonValidationErrors('items.1.model');

        $this->assertSame(0, QuotationRequest::count());
    }

    public function test_jumlah_model_dibatasi(): void
    {
        $items = collect(range(1, 40))
            ->map(fn (int $index) => $this->item("part-{$index}.stl"))
            ->all();

        $this->postJson(route('quotations.store'), $this->multiPayload($items))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items');

        $this->assertSame(0, QuotationRequest::count());
    }

    public function test_menghapus_penawaran_ikut_menghapus_berkas_seluruh_model(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl'),
            $this->item('cover.obj'),
        ]))->assertCreated();

        $quotation = QuotationRequest::sole();
        $paths = $quotation->items->pluck('file_path');

        $quotation->delete();

        $paths->each(fn (string $path) => Storage::disk('local')->assertMissing($path));
        $this->assertSame(0, QuotationItem::count());
    }

    /* --------------------------------------- simulasi ala Cura ------------ */

    public function test_printer_tersimpan_beserta_area_cetaknya(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload(
            [$this->item('gear.stl')],
            ['printer' => 'prusa_mk4'],
        ))->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertSame('prusa_mk4', $quotation->printer);
        $this->assertSame('Prusa MK4', $quotation->printer_name);
        // JSON mengembalikan angka bulat sebagai integer, jadi dibandingkan longgar.
        $this->assertEquals(['x' => 250, 'y' => 210, 'z' => 220], $quotation->build_volume);
        $this->assertStringContainsString('250 × 210 × 220 mm', $quotation->printer_label);
    }

    public function test_setiap_model_menyimpan_mesinnya_sendiri(): void
    {
        // 1 printer = 1 build plate = 1 objek.
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['printer' => 'ender3']),
            $this->item('cover.obj', ['printer' => 'bambu_x1c']),
            $this->item('bracket.stl', ['printer' => 'prusa_mk4']),
        ]))->assertCreated();

        $items = QuotationRequest::sole()->items;

        $this->assertSame(['ender3', 'bambu_x1c', 'prusa_mk4'], $items->pluck('printer')->all());
        $this->assertSame(
            ['Creality Ender 3', 'Bambu Lab X1 Carbon', 'Prusa MK4'],
            $items->pluck('printer_name')->all()
        );

        // Area cetak tiap model mengikuti mesinnya masing-masing.
        $this->assertEquals(['x' => 220, 'y' => 220, 'z' => 250], $items[0]->build_volume);
        $this->assertEquals(['x' => 256, 'y' => 256, 'z' => 256], $items[1]->build_volume);
        $this->assertEquals(['x' => 250, 'y' => 210, 'z' => 220], $items[2]->build_volume);

        $this->assertStringContainsString('256 × 256 × 256 mm', $items[1]->printer_label);
    }

    public function test_mesin_berbeda_menghasilkan_estimasi_berbeda_per_model(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('lambat.stl', ['printer' => 'ender3']),
            $this->item('cepat.stl', ['printer' => 'bambu_x1c']),
        ]))->assertCreated();

        [$lambat, $cepat] = QuotationRequest::sole()->items->all();

        // Model identik, hanya mesinnya berbeda: Bambu X1C jauh lebih cepat.
        $this->assertGreaterThan($cepat->estimated_minutes, $lambat->estimated_minutes);
    }

    public function test_ringkasan_penawaran_menyebut_jumlah_mesin_berbeda(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['printer' => 'ender3']),
            $this->item('cover.obj', ['printer' => 'prusa_mk4']),
        ]))->assertCreated();

        $quotation = QuotationRequest::sole()->load('items');

        $this->assertSame('2 printer berbeda', $quotation->printer_summary);
        // Baris penawaran tetap menyimpan mesin model pertama sebagai wakilnya.
        $this->assertSame('ender3', $quotation->printer);
    }

    public function test_ringkasan_menyebut_satu_mesin_bila_seluruh_model_sama(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['printer' => 'prusa_mk4']),
            $this->item('cover.obj', ['printer' => 'prusa_mk4']),
        ]))->assertCreated();

        $this->assertStringContainsString(
            'Prusa MK4',
            QuotationRequest::sole()->load('items')->printer_summary
        );
    }

    public function test_ukuran_custom_berlaku_per_model(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('besar.stl', [
                'printer' => 'custom',
                'build_volume' => ['x' => 600, 'y' => 500, 'z' => 700],
            ]),
            $this->item('kecil.stl', ['printer' => 'ender3']),
        ]))->assertCreated();

        [$besar, $kecil] = QuotationRequest::sole()->items->all();

        $this->assertEquals(['x' => 600, 'y' => 500, 'z' => 700], $besar->build_volume);
        $this->assertEquals(['x' => 220, 'y' => 220, 'z' => 250], $kecil->build_volume);
    }

    public function test_mesin_tidak_dikenal_pada_salah_satu_model_ditolak(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl'),
            $this->item('cover.obj', ['printer' => 'makerbot']),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.1.printer');

        $this->assertSame(0, QuotationRequest::count());
    }

    public function test_printer_custom_memakai_ukuran_kiriman_pengguna(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload(
            [$this->item('gear.stl')],
            ['printer' => 'custom', 'build_volume' => ['x' => 420, 'y' => 380, 'z' => 500]],
        ))->assertCreated();

        $this->assertEquals(
            ['x' => 420, 'y' => 380, 'z' => 500],
            QuotationRequest::sole()->build_volume
        );
    }

    public function test_printer_lebih_cepat_memangkas_waktu_cetak(): void
    {
        $estimator = app(PrintEstimator::class);

        $ender = $estimator->estimate('FDM', 'PLA', 200, 1, ['printer' => 'ender3']);
        $bambu = $estimator->estimate('FDM', 'PLA', 200, 1, ['printer' => 'bambu_x1c']);

        // Ender 3 (0,85x) lebih lambat daripada Bambu X1C (1,75x).
        $this->assertGreaterThan($bambu['total_minutes'], $ender['total_minutes']);
    }

    public function test_skala_menaikkan_volume_pangkat_tiga(): void
    {
        $estimator = app(PrintEstimator::class);

        $asli = $estimator->estimate('FDM', 'PLA', 100, 1);
        $duaKali = $estimator->estimate('FDM', 'PLA', 100, 1, ['scale' => 2.0]);

        $this->assertEqualsWithDelta(100, $asli['model_volume_cm3'], 0.001);
        $this->assertEqualsWithDelta(800, $duaKali['model_volume_cm3'], 0.001);
        $this->assertEqualsWithDelta($asli['weight_g'] * 8, $duaKali['weight_g'], 0.01);
        $this->assertGreaterThan($asli['total_cost'], $duaKali['total_cost']);
    }

    public function test_skala_tersimpan_dan_estimasi_mengikutinya(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['scale_percent' => 150]),
        ]))->assertCreated();

        $item = QuotationRequest::sole()->items->first();

        $this->assertEqualsWithDelta(150, (float) $item->scale_percent, 0.01);
        // Volume yang tercatat adalah volume setelah diskalakan: 100 x 1,5^3.
        $this->assertEqualsWithDelta(337.5, (float) $item->model_volume_cm3, 0.01);
    }

    public function test_infill_lebih_rendah_menghemat_material_pada_fdm(): void
    {
        $estimator = app(PrintEstimator::class);

        $penuh = $estimator->estimate('FDM', 'PLA', 100, 1, ['infill_density' => 1.0]);
        $ringan = $estimator->estimate('FDM', 'PLA', 100, 1, ['infill_density' => 0.10]);

        $this->assertGreaterThan($ringan['material_volume_cm3'], $penuh['material_volume_cm3']);
        $this->assertGreaterThan($ringan['weight_g'], $penuh['weight_g']);
        $this->assertGreaterThan($ringan['total_minutes'], $penuh['total_minutes']);
        $this->assertGreaterThan($ringan['total_cost'], $penuh['total_cost']);
    }

    public function test_infill_bawaan_teknologi_setara_perhitungan_sebelumnya(): void
    {
        // FDM: shell 0,3125 + (1 - 0,3125) x 0,20 = 0,45 — sama seperti
        // fill factor yang dipakai sebelum infill dapat diatur.
        $estimate = app(PrintEstimator::class)->estimate('FDM', 'PLA', 100, 1);

        $this->assertEqualsWithDelta(0.45, $estimate['fill_factor'], 0.0001);
        $this->assertEqualsWithDelta(45.0, $estimate['material_volume_cm3'], 0.01);
    }

    public function test_pola_infill_menambah_material_dan_waktu(): void
    {
        $estimator = app(PrintEstimator::class);

        $grid = $estimator->estimate('FDM', 'PLA', 100, 1, ['infill_density' => 0.6, 'infill_pattern' => 'grid']);
        $gyroid = $estimator->estimate('FDM', 'PLA', 100, 1, ['infill_density' => 0.6, 'infill_pattern' => 'gyroid']);

        $this->assertGreaterThan($grid['material_volume_cm3'], $gyroid['material_volume_cm3']);
        $this->assertGreaterThan($grid['total_minutes'], $gyroid['total_minutes']);
    }

    public function test_pola_infill_tidak_berpengaruh_saat_teknologi_dicetak_padat(): void
    {
        $estimator = app(PrintEstimator::class);

        // SLA shell_ratio 1,0 — resin mengeras padat, infill tidak berperan.
        $penuh = $estimator->estimate('SLA', 'Standard Resin', 100, 1, ['infill_density' => 1.0]);
        $ringan = $estimator->estimate('SLA', 'Standard Resin', 100, 1, ['infill_density' => 0.10]);

        $this->assertEqualsWithDelta($penuh['material_volume_cm3'], $ringan['material_volume_cm3'], 0.001);
    }

    public function test_hollow_model_memangkas_resin_pada_sla(): void
    {
        $estimator = app(PrintEstimator::class);

        $padat = $estimator->estimate('SLA', 'Standard Resin', 500, 1, ['surface_area_cm2' => 300]);
        $kosong = $estimator->estimate('SLA', 'Standard Resin', 500, 1, [
            'surface_area_cm2' => 300,
            'hollow' => ['enabled' => true, 'wall_thickness_mm' => 2.0],
        ]);

        $this->assertFalse($padat['hollow_enabled']);
        $this->assertTrue($kosong['hollow_enabled']);

        // Cangkang 300 cm2 x 2 mm = 60 cm3, dikurangi dua lubang pembuangan.
        $this->assertLessThan(60, $kosong['material_volume_cm3']);
        $this->assertGreaterThan(55, $kosong['material_volume_cm3']);

        $this->assertLessThan($padat['weight_g'], $kosong['weight_g']);
        $this->assertLessThan($padat['total_minutes'], $kosong['total_minutes']);
        $this->assertLessThan($padat['total_cost'], $kosong['total_cost']);
    }

    public function test_hollow_model_diabaikan_pada_teknologi_selain_sla(): void
    {
        $estimate = app(PrintEstimator::class)->estimate('FDM', 'PLA', 500, 1, [
            'surface_area_cm2' => 300,
            'hollow' => ['enabled' => true, 'wall_thickness_mm' => 2.0],
        ]);

        $this->assertFalse($estimate['hollow_enabled']);
        $this->assertNull($estimate['hollow_wall_thickness_mm']);
    }

    public function test_hollow_tidak_pernah_melebihi_volume_padat(): void
    {
        // Pada part tipis, mengosongkan bagian dalam tidak menyisakan apa pun.
        $estimate = app(PrintEstimator::class)->estimate('SLA', 'Standard Resin', 5, 1, [
            'surface_area_cm2' => 400,
            'hollow' => ['enabled' => true, 'wall_thickness_mm' => 5.0],
        ]);

        $this->assertLessThanOrEqual(5.0, $estimate['material_volume_cm3']);
    }

    public function test_pengaturan_hollow_tersimpan_pada_item(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('cover.obj', [
                'technology' => 'SLA',
                'material' => 'Standard Resin',
                'hollow_enabled' => '1',
                'hollow_wall_thickness_mm' => '1.6',
                'hollow_drain_diameter_mm' => '4.0',
                'hollow_drain_position' => 'side',
                'model_stats' => json_encode([
                    'dimensions' => ['x' => 50, 'y' => 40, 'z' => 30],
                    'surface_area_cm2' => 120,
                ]),
            ]),
        ]))->assertCreated();

        $item = QuotationRequest::sole()->items->first();

        $this->assertTrue($item->hollow_enabled);
        $this->assertEqualsWithDelta(1.6, (float) $item->hollow_wall_thickness_mm, 0.001);
        $this->assertEqualsWithDelta(4.0, (float) $item->hollow_drain_diameter_mm, 0.001);
        $this->assertSame('side', $item->hollow_drain_position);
        $this->assertStringContainsString('Sisi Samping', $item->hollow_label);
    }

    public function test_rincian_biaya_berjumlah_sama_dengan_totalnya(): void
    {
        $estimate = app(PrintEstimator::class)->estimate('FDM', 'PLA', 120, 2, [
            'support' => true,
            'surface_area_cm2' => 180,
        ]);

        $breakdown = $estimate['breakdown'];

        $this->assertSame(
            ['material', 'machine_time', 'support', 'finishing', 'quality_control', 'total'],
            array_keys($breakdown)
        );

        $components = array_sum([
            $breakdown['material'],
            $breakdown['machine_time'],
            $breakdown['support'],
            $breakdown['finishing'],
            $breakdown['quality_control'],
        ]);

        $this->assertEqualsWithDelta($components, $breakdown['total'], 0.01);
        $this->assertEqualsWithDelta($breakdown['total'], $estimate['total_cost'], 0.01);
    }

    public function test_tanpa_support_komponen_biaya_support_nol(): void
    {
        $estimate = app(PrintEstimator::class)->estimate('FDM', 'PLA', 120, 1, ['surface_area_cm2' => 180]);

        $this->assertSame(0.0, $estimate['breakdown']['support']);
        $this->assertGreaterThan(0, $estimate['breakdown']['material']);
        $this->assertGreaterThan(0, $estimate['breakdown']['finishing']);
        $this->assertGreaterThan(0, $estimate['breakdown']['quality_control']);
    }

    public function test_rincian_biaya_penawaran_menjumlahkan_seluruh_model(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl'),
            $this->item('cover.obj'),
        ]))->assertCreated();

        $quotation = QuotationRequest::sole();
        $breakdown = $quotation->cost_breakdown;

        $this->assertNotEmpty($breakdown);

        foreach (['material', 'machine_time', 'support', 'finishing', 'quality_control', 'total'] as $component) {
            $expected = $quotation->items->sum(fn ($item) => (float) $item->cost_breakdown[$component]);

            $this->assertEqualsWithDelta($expected, (float) $breakdown[$component], 0.01, $component);
        }

        $this->assertEqualsWithDelta((float) $quotation->estimated_cost, (float) $breakdown['total'], 0.01);
    }

    public function test_model_di_luar_area_cetak_ditandai(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('besar.stl', ['fits_build_volume' => '0']),
            $this->item('kecil.stl', ['fits_build_volume' => '1']),
        ]))->assertCreated();

        [$besar, $kecil] = QuotationRequest::sole()->items->all();

        $this->assertFalse($besar->fits_build_volume);
        $this->assertTrue($kecil->fits_build_volume);
    }

    public function test_warna_material_tersimpan_per_model(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['material_color' => 'biru']),
            $this->item('cover.obj'),
        ]))->assertCreated();

        [$gear, $cover] = QuotationRequest::sole()->items->all();

        $this->assertSame('biru', $gear->material_color);
        $this->assertSame('Biru', $gear->material_color_label);
        // Model kedua memakai warna default, tidak ikut berubah.
        $this->assertSame('merah', $cover->material_color);
    }

    /* ------------------------------------------- finishing & warna ------- */

    public function test_tanpa_finishing_estimasinya_sama_seperti_sebelum_fitur_finishing(): void
    {
        $estimator = app(PrintEstimator::class);

        // `none` memakai pengali 1,0 — pembersihan dasar yang memang sudah
        // selalu dihitung, jadi penawaran lama tidak berubah nilainya.
        $tanpa = $estimator->estimate('FDM', 'PLA', 120, 2, ['surface_area_cm2' => 180]);
        $eksplisit = $estimator->estimate('FDM', 'PLA', 120, 2, ['surface_area_cm2' => 180, 'finishing' => 'none']);

        $this->assertSame('none', $tanpa['finishing']);
        $this->assertEqualsWithDelta($tanpa['total_cost'], $eksplisit['total_cost'], 0.01);
        $this->assertSame(0, $tanpa['finishing_minutes']);
    }

    public function test_finishing_menaikkan_biaya_dan_waktu(): void
    {
        $estimator = app(PrintEstimator::class);
        $options = ['surface_area_cm2' => 180];

        $none = $estimator->estimate('FDM', 'PLA', 120, 2, $options);
        $sanding = $estimator->estimate('FDM', 'PLA', 120, 2, $options + ['finishing' => 'sanding']);

        $this->assertGreaterThan($none['breakdown']['finishing'], $sanding['breakdown']['finishing']);
        $this->assertGreaterThan($none['total_cost'], $sanding['total_cost']);
        $this->assertGreaterThan($none['total_minutes'], $sanding['total_minutes']);

        // Waktu finishing dihitung per unit: 0,25 jam x 2 unit = 30 menit.
        $this->assertSame(30, $sanding['finishing_minutes']);
    }

    public function test_finishing_lebih_berat_lebih_mahal(): void
    {
        $estimator = app(PrintEstimator::class);
        $options = ['surface_area_cm2' => 180];

        $sanding = $estimator->estimate('FDM', 'PLA', 120, 1, $options + ['finishing' => 'sanding']);
        $painting = $estimator->estimate('FDM', 'PLA', 120, 1, $options + ['finishing' => 'painting']);

        $this->assertGreaterThan($sanding['breakdown']['finishing'], $painting['breakdown']['finishing']);
        $this->assertGreaterThan($sanding['total_minutes'], $painting['total_minutes']);
    }

    public function test_finishing_tersimpan_pada_item(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['finishing' => 'polishing']),
            $this->item('cover.obj'),
        ]))->assertCreated();

        [$gear, $cover] = QuotationRequest::sole()->items->all();

        $this->assertSame('polishing', $gear->finishing);
        $this->assertSame('Polishing', $gear->finishing_label);

        // Model lain memakai pilihan bawaan, tidak ikut berubah.
        $this->assertSame('none', $cover->finishing);
        $this->assertStringContainsString('Tanpa Finishing', $cover->specification_summary);
    }

    public function test_finishing_tidak_dikenal_ditolak(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('gear.stl', ['finishing' => 'chrome-plating']),
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.finishing');
    }

    public function test_warna_disesuaikan_dengan_material_yang_dipilih(): void
    {
        // Clear Resin hanya tersedia bening, jadi pilihan merah diperbaiki.
        $this->postJson(route('quotations.store'), $this->multiPayload([
            $this->item('lens.stl', [
                'technology' => 'SLA',
                'material' => 'Clear Resin',
                'material_color' => 'merah',
            ]),
        ]))->assertCreated();

        $item = QuotationRequest::sole()->items->first();

        $this->assertSame('bening', $item->material_color);
        $this->assertSame('Bening', $item->material_color_label);
    }

    public function test_pilihan_simulasi_tidak_dikenal_ditolak(): void
    {
        $this->postJson(route('quotations.store'), $this->multiPayload(
            [$this->item('gear.stl', ['infill_pattern' => 'honeycomb', 'material_color' => 'ungu'])],
            ['printer' => 'makerbot'],
        ))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['printer', 'items.0.infill_pattern', 'items.0.material_color']);
    }

    public function test_permintaan_satu_model_tetap_membuat_satu_item(): void
    {
        // Payload bentuk lama harus tetap diterima dan ikut memakai struktur baru.
        $this->postJson(route('quotations.store'), $this->payload())->assertCreated();

        $quotation = QuotationRequest::sole();

        $this->assertSame(1, $quotation->items->count());
        $this->assertSame('bracket.stl', $quotation->items->first()->file_name);
        $this->assertSame(3, $quotation->items->first()->quantity);
        $this->assertSame($quotation->file_path, $quotation->items->first()->file_path);
    }
}
