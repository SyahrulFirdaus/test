<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PricingFormula;
use App\Models\PrintTechnology;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Support\ActivityAction;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CRUD teknologi cetak pada Price List.
 *
 * Yang dijaga bukan sekadar "barisnya tersimpan", melainkan AKIBATNYA:
 * menambah satu teknologi harus langsung memunculkan tabnya sendiri di Price
 * List, pilihannya di Edit Specification, dan baris parameternya di tab Harga
 * — tanpa satu baris kode pun berubah.
 */
class PrintTechnologyCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'DLP',
            'name' => 'Digital Light Processing',
            'family' => 'Resin',
            'description' => 'Resin dikeraskan proyektor DLP.',
            'build_volume_x' => 190,
            'build_volume_y' => 120,
            'build_volume_z' => 245,
            'shell_ratio' => 1,
            'default_infill' => 1,
            'min_wall_thickness_mm' => 0.6,
            'support_volume_factor' => 0.14,
            'layer_height_min' => 0.02,
            'layer_height_max' => 0.1,
            'throughput_cm3_per_hour' => 12,
            'setup_hours' => 0.5,
            'setup_fee' => 40000,
            'machine_rate_per_hour' => 28000,
            'allows_hollow' => '1',
            'sort_order' => 50,
        ], $overrides);
    }

    /* ------------------------------------------------------------ tambah --- */

    public function test_teknologi_baru_langsung_punya_tab_sendiri(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.price-list.technologies.store'), $this->payload())
            ->assertRedirect(route('superadmin.price-list.technology', ['slug' => 'dlp']));

        $technology = PrintTechnology::where('code', 'DLP')->sole();
        $this->assertTrue($technology->allows_hollow);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.price-list.technology', ['slug' => 'dlp']))
            ->assertOk()
            // Halaman materialnya sendiri, dan menunya ikut muncul di sidebar.
            ->assertSee('data-price-list-link="dlp"', false)
            ->assertSee('Material DLP')
            ->assertDontSee('Material FDM');
    }

    public function test_teknologi_baru_langsung_muncul_di_edit_specification(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.technologies.store'), $this->payload());

        // Katalog estimator — sumber pilihan Technology pada Edit Specification.
        $payload = app(PrintEstimator::class)->browserPayload();

        $this->assertArrayHasKey('DLP', $payload);
        $this->assertSame('DLP (Resin)', $payload['DLP']['label']);
        $this->assertTrue($payload['DLP']['allowsHollow']);
        $this->assertSame(['x' => 190, 'y' => 120, 'z' => 245], $payload['DLP']['buildVolume']);

        // Dan menjadi pilihan yang sah pada validasi penawaran.
        $this->assertContains('DLP', PrintTechnology::codes());
    }

    /** Seluruh teknologi memakai satu Rumus Harga Otomatis; tidak ada baris rumus per teknologi. */
    public function test_teknologi_baru_memakai_rumus_harga_otomatis_umum(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.technologies.store'), $this->payload());

        $this->assertFalse(PricingFormula::where('technology', 'DLP')->exists());
        $this->assertSame(PricingFormula::GENERAL, \App\Services\SellingPriceEstimator::formulaCode('DLP'));
    }

    public function test_kode_wajib_unik_dan_tanpa_spasi(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.price-list.technologies.store'), $this->payload(['code' => 'FDM']))
            ->assertSessionHasErrors('code');

        $this->actingAs($superAdmin)
            ->post(route('superadmin.price-list.technologies.store'), $this->payload(['code' => 'DL P']))
            ->assertSessionHasErrors('code');
    }

    public function test_kode_disimpan_huruf_kapital(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.technologies.store'), $this->payload(['code' => 'dlp']));

        $this->assertDatabaseHas('print_technologies', ['code' => 'DLP']);
    }

    /* ------------------------------------------------------------- ubah --- */

    public function test_mengubah_parameter_langsung_dipakai_estimator(): void
    {
        $technology = PrintTechnology::where('code', 'FDM')->sole();

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.technologies.update', $technology), $this->payload([
                'code' => 'FDM',
                'name' => 'Fused Deposition Modeling',
                'build_volume_x' => 300,
                'build_volume_y' => 300,
                'build_volume_z' => 400,
                'allows_hollow' => '0',
            ]))
            ->assertRedirect(route('superadmin.price-list.technology', ['slug' => 'fdm']));

        $payload = app(PrintEstimator::class)->browserPayload();

        $this->assertSame(['x' => 300, 'y' => 300, 'z' => 400], $payload['FDM']['buildVolume']);
        $this->assertFalse($payload['FDM']['allowsHollow']);
    }

    /** Kode teknologi yang sudah dipakai penawaran tidak boleh bergeser. */
    public function test_kode_terkunci_setelah_dipakai_penawaran(): void
    {
        $technology = PrintTechnology::where('code', 'FDM')->sole();
        $this->penawaranFdm();

        $this->assertTrue($technology->fresh()->isInUse());

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.technologies.update', $technology), $this->payload([
                'code' => 'FDMX',
                'name' => 'Fused Deposition Modeling',
            ]));

        // Namanya boleh berubah, kodenya tidak.
        $this->assertSame('FDM', $technology->fresh()->code);
    }

    /* ------------------------------------------------------------ hapus --- */

    public function test_menghapus_teknologi_ikut_menghapus_materialnya(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('superadmin.price-list.technologies.store'), $this->payload());
        $technology = PrintTechnology::where('code', 'DLP')->sole();

        $this->actingAs($superAdmin)->post(route('superadmin.price-list.materials.store', $technology), [
            'material' => 'Standard Resin',
            'brand' => 'Sunlu',
            'purchase_price' => 300000,
            'sale_price' => 1200,
        ]);

        $materialId = $technology->materials()->sole()->id;

        $this->actingAs($superAdmin)
            ->delete(route('superadmin.price-list.technologies.destroy', $technology))
            ->assertRedirect(route('superadmin.price-list.technologies.index'));

        $this->assertDatabaseMissing('print_technologies', ['code' => 'DLP']);
        $this->assertDatabaseMissing('print_materials', ['id' => $materialId]);
        // Baris rumusnya ikut dibersihkan.
        $this->assertDatabaseMissing('pricing_formulas', ['technology' => 'DLP']);
    }

    public function test_teknologi_yang_dipakai_penawaran_tidak_dapat_dihapus(): void
    {
        $technology = PrintTechnology::where('code', 'FDM')->sole();
        $this->penawaranFdm();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.price-list.technologies.destroy', $technology))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('print_technologies', ['code' => 'FDM']);
    }

    /* ---------------------------------------------------- material per tab --- */

    public function test_material_baru_masuk_ke_teknologinya_sendiri(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('superadmin.price-list.technologies.store'), $this->payload());
        $dlp = PrintTechnology::where('code', 'DLP')->sole();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.price-list.materials.store', $dlp), [
                'material' => 'Tough Resin',
                'brand' => 'Sunlu',
                'purchase_price' => 400000,
                'sale_price' => 1600,
            ])
            ->assertRedirect(route('superadmin.price-list.technology', ['slug' => 'dlp']));

        $payload = app(PrintEstimator::class)->browserPayload();

        $this->assertSame(['Tough Resin'], array_column($payload['DLP']['materials'], 'name'));
        $this->assertNotContains('Tough Resin', array_column($payload['FDM']['materials'], 'name'));
    }

    /** Material milik tab lain tidak dapat disentuh lewat URL tab ini. */
    public function test_material_teknologi_lain_tidak_dapat_disunting_lewat_tab_lain(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('superadmin.price-list.technologies.store'), $this->payload());
        $dlp = PrintTechnology::where('code', 'DLP')->sole();

        $fdmMaterial = PrintTechnology::where('code', 'FDM')->sole()->materials()->first();

        $this->actingAs($superAdmin)
            ->get(route('superadmin.price-list.materials.edit', [$dlp, $fdmMaterial]))
            ->assertNotFound();
    }

    public function test_nama_material_boleh_sama_antar_teknologi(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('superadmin.price-list.technologies.store'), $this->payload());
        $dlp = PrintTechnology::where('code', 'DLP')->sole();

        $nama = PrintTechnology::where('code', 'FDM')->sole()->materials()->first()->material;

        // Nama yang sama pada teknologi berbeda diterima...
        $this->actingAs($superAdmin)
            ->post(route('superadmin.price-list.materials.store', $dlp), [
                'material' => $nama,
                'brand' => 'Sunlu',
                'purchase_price' => 300000,
                'sale_price' => 1200,
            ])
            ->assertSessionHasNoErrors();

        // ...tetapi tidak boleh kembar di dalam satu teknologi.
        $this->actingAs($superAdmin)
            ->post(route('superadmin.price-list.materials.store', $dlp), [
                'material' => $nama,
                'brand' => 'Sunlu',
                'purchase_price' => 300000,
                'sale_price' => 1200,
            ])
            ->assertSessionHasErrors('material');
    }

    /* -------------------------------------------------------- hak akses --- */

    public function test_admin_biasa_tidak_dapat_menambah_teknologi(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('superadmin.price-list.technologies.store'), $this->payload())
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('print_technologies', ['code' => 'DLP']);
    }

    /* ----------------------------------------------------- activity log --- */

    public function test_penambahan_teknologi_tercatat(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.technologies.store'), $this->payload());

        $log = ActivityLog::where('action', ActivityAction::TECHNOLOGY_CREATE)->sole();

        $this->assertSame('DLP', $log->subject_label);
        $this->assertStringContainsString('Digital Light Processing', $log->description);
    }

    private function penawaranFdm(): QuotationRequest
    {
        $user = User::factory()->create();

        $quotation = QuotationRequest::create([
            'user_id' => $user->id,
            'tracking_number' => 'QT-TECH01',
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '081211112222',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
            'estimated_price' => 300000,
            'status' => QuotationStatus::REVIEWING,
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
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
            'estimated_minutes' => 120,
            'estimated_cost' => 300000,
        ]);

        return $quotation;
    }
}
