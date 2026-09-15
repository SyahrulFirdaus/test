<?php

namespace Tests\Feature;

use App\Models\MachineCost;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PrintEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Material Price List yang menunjuk mesin dari Machine Cost.
 *
 * Rantainya: teknologi → mesin → material → harga. Yang dijaga di sini:
 *   - daftar mesin pada formulir benar-benar berasal dari Machine Cost,
 *     bukan daftar kedua;
 *   - material berkelompok per mesin dan nomornya mulai dari 1 tiap kelompok;
 *   - nama material yang sama boleh berdiri di mesin berbeda — dan itu TIDAK
 *     menggeser harga yang dipakai Calculator;
 *   - fitur Price List yang sudah ada tetap berjalan.
 */
class MaterialMachineTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private PrintTechnology $fdm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->fdm = PrintTechnology::where('code', 'FDM')->sole();

        // Material bawaan migrasi dibersihkan agar susunan kelompok yang diuji
        // tidak tercampur data lain.
        PrintMaterial::query()->delete();
    }

    /* ------------------------------------------------ sumber daftar mesin --- */

    public function test_dropdown_mesin_diambil_dari_machine_cost(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $lain = $this->mesin('Elegoo Saturn 3 Ultra', 'SLA');

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.materials.create', $this->fdm))
            ->assertOk();

        $response->assertSee('name="machine_cost_id"', false)
            ->assertSee('value="'.$mesin->id.'"', false)
            ->assertSee('Bambu Lab X1 Carbon')
            // Seluruh mesin dapat dipilih, dikelompokkan per teknologinya.
            ->assertSee('value="'.$lain->id.'"', false)
            ->assertSee('<optgroup label="SLA">', false);
    }

    /** Mesin yang baru didaftarkan di Machine Cost langsung jadi pilihan. */
    public function test_mesin_baru_langsung_muncul_pada_dropdown_material(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.materials.create', $this->fdm))
            ->assertDontSee('Voron 2.4');

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.machine-cost.store'), [
                'mesin' => 'Voron 2.4',
                'print_technology_id' => $this->fdm->id,
                'watt_kwh' => 0.4,
                'harga_listrik' => 1700,
                'depresiasi' => 4000,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.materials.create', $this->fdm))
            ->assertSee('Voron 2.4');
    }

    /* ------------------------------------------------------------- CRUD --- */

    public function test_material_disimpan_beserta_mesinnya(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                'material' => 'PLA+',
                'brand' => 'eSUN',
                'machine_cost_id' => $mesin->id,
                'purchase_price' => 185000,
                'sale_price' => 231,
                'remark' => 'Standard Material',
            ])
            ->assertSessionHasNoErrors();

        $material = PrintMaterial::where('material', 'PLA+')->sole();

        $this->assertSame($mesin->id, $material->machine_cost_id);
        $this->assertSame('Bambu Lab X1 Carbon', $material->machine_label);
    }

    /** Membuka Edit Material harus memilihkan kembali mesin yang tersimpan. */
    public function test_mesin_tersimpan_otomatis_terpilih_saat_diedit(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $lain = $this->mesin('Creality K1 Max', 'FDM');
        $material = $this->material('PLA+', $mesin);

        $html = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.materials.edit', [$this->fdm, $material]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/value="'.$mesin->id.'"[^>]*selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="'.$lain->id.'"[^>]*selected/', $html);
    }

    /** Material boleh tanpa mesin — termasuk material lama yang belum ditentukan. */
    public function test_mesin_tidak_wajib_dipilih(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                'material' => 'PLA+',
                'brand' => 'eSUN',
                'machine_cost_id' => '',
                'purchase_price' => 185000,
                'sale_price' => 231,
            ])
            ->assertSessionHasNoErrors();

        $material = PrintMaterial::where('material', 'PLA+')->sole();

        $this->assertNull($material->machine_cost_id);
        $this->assertSame('Tanpa Mesin', $material->machine_label);
    }

    public function test_mesin_yang_tidak_terdaftar_ditolak(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                'material' => 'PLA+',
                'brand' => 'eSUN',
                'machine_cost_id' => 9999,
                'purchase_price' => 185000,
                'sale_price' => 231,
            ])
            ->assertSessionHasErrors('machine_cost_id');

        $this->assertSame(0, PrintMaterial::count());
    }

    /* -------------------------------------------- nama sama, mesin beda --- */

    /** Inti permintaan: "PLA+" boleh berdiri sendiri di tiap mesin. */
    public function test_nama_material_sama_boleh_pada_mesin_berbeda(): void
    {
        $bambu = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $creality = $this->mesin('Creality K1 Max', 'FDM');

        foreach ([$bambu, $creality] as $mesin) {
            $this->actingAs($this->superAdmin)
                ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                    'material' => 'PLA+',
                    'brand' => 'eSUN',
                    'machine_cost_id' => $mesin->id,
                    'purchase_price' => 185000,
                    'sale_price' => 231,
                ])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(2, PrintMaterial::where('material', 'PLA+')->count());
    }

    /** Di dalam SATU mesin, nama material tetap tidak boleh kembar. */
    public function test_nama_material_tidak_boleh_kembar_pada_mesin_yang_sama(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $this->material('PLA+', $mesin);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                'material' => 'PLA+',
                'brand' => 'eSUN',
                'machine_cost_id' => $mesin->id,
                'purchase_price' => 185000,
                'sale_price' => 231,
            ])
            ->assertSessionHasErrors('material');

        $this->assertSame(1, PrintMaterial::where('material', 'PLA+')->count());
    }

    /** Termasuk sesama material tanpa mesin, yang tidak dijaga indeks unik. */
    public function test_nama_material_tidak_boleh_kembar_sesama_tanpa_mesin(): void
    {
        $this->material('PLA+', null);

        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                'material' => 'PLA+',
                'brand' => 'eSUN',
                'machine_cost_id' => '',
                'purchase_price' => 185000,
                'sale_price' => 231,
            ])
            ->assertSessionHasErrors('material');

        $this->assertSame(1, PrintMaterial::where('material', 'PLA+')->count());
    }

    /* ------------------------------------------------------ pengelompokan --- */

    public function test_material_dikelompokkan_per_mesin_dengan_nomor_sendiri(): void
    {
        $bambu = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $creality = $this->mesin('Creality K1 Max', 'FDM');

        foreach (['PLA+', 'PETG', 'ABS'] as $nama) {
            $this->material($nama, $bambu);
        }

        foreach (['PLA+', 'TPU95A'] as $nama) {
            $this->material($nama, $creality);
        }

        $html = $this->tabHtml();

        // Satu judul per mesin, bukan nama mesin berulang di tiap baris.
        $this->assertSame(2, substr_count($html, 'scope="colgroup"'));
        $this->assertSame(1, substr_count($html, 'Bambu Lab X1 Carbon'));
        $this->assertSame(1, substr_count($html, 'Creality K1 Max'));

        // Bambu (3 material) tampil lebih dulu daripada Creality (2 material).
        $this->assertLessThan(
            strpos($html, 'Creality K1 Max'),
            strpos($html, 'Bambu Lab X1 Carbon')
        );

        // Nomor dimulai lagi dari 1 pada kelompok kedua.
        $this->assertSame([1, 2, 3, 1, 2], $this->nomorUrut($html));
    }

    /**
     * Nomor dihitung atas seluruh material, bukan halaman yang tampil, jadi
     * kelompok yang terpotong paginasi tidak mengulang dari 1.
     */
    public function test_nomor_tidak_mengulang_saat_kelompok_terpotong_paginasi(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');

        // 18 material dalam satu mesin: 15 di halaman pertama, 3 di halaman kedua.
        foreach (range(1, 18) as $urutan) {
            $this->material('Material '.str_pad((string) $urutan, 2, '0', STR_PAD_LEFT), $mesin);
        }

        $this->assertSame(range(1, 15), $this->nomorUrut($this->tabHtml()));
        $this->assertSame([16, 17, 18], $this->nomorUrut($this->tabHtml(['fdm_page' => 2])));
    }

    /** Mengubah mesin membuat material berpindah kelompok dengan sendirinya. */
    public function test_mengganti_mesin_memindahkan_material_ke_kelompok_lain(): void
    {
        $bambu = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $creality = $this->mesin('Creality K1 Max', 'FDM');

        $this->material('PLA+', $bambu);
        $this->material('PETG', $bambu);
        $abs = $this->material('ABS', $bambu);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.price-list.materials.update', [$this->fdm, $abs]), [
                'material' => 'ABS',
                'brand' => $abs->brand,
                'machine_cost_id' => $creality->id,
                'purchase_price' => $abs->purchase_price,
                'sale_price' => $abs->sale_price,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($creality->id, $abs->fresh()->machine_cost_id);

        $html = $this->tabHtml();

        // Bambu tinggal dua material, Creality mendapat ABS beserta nomor 1.
        $bambuBlok = $this->blok($html, 'Bambu Lab X1 Carbon', 'Creality K1 Max');

        $this->assertStringContainsString('PLA+', $bambuBlok);
        $this->assertStringContainsString('PETG', $bambuBlok);
        $this->assertStringNotContainsString('ABS', $bambuBlok);

        $this->assertSame([1, 2, 1], $this->nomorUrut($html));
    }

    /** Aturan yang sama berlaku pada SLA, bukan hanya FDM. */
    public function test_pengelompokan_berlaku_juga_untuk_sla(): void
    {
        $sla = PrintTechnology::where('code', 'SLA')->sole();
        $saturn = $this->mesin('Elegoo Saturn 3 Ultra', 'SLA');
        $photon = $this->mesin('Anycubic Photon Mono', 'SLA');

        $this->material('Standard Resin', $saturn, $sla);
        $this->material('Flexible Resin', $saturn, $sla);
        $this->material('Standard Resin', $photon, $sla);

        $html = $this->tabHtml([], 'sla');

        $this->assertStringContainsString('Elegoo Saturn 3 Ultra', $html);
        $this->assertStringContainsString('Anycubic Photon Mono', $html);

        // Anycubic lebih dulu secara abjad; nomornya masing-masing dari 1.
        $this->assertSame([1, 1, 2], $this->nomorUrut($html));
    }

    /** Material tanpa mesin tetap terdaftar, dikumpulkan paling bawah. */
    public function test_material_tanpa_mesin_masuk_kelompok_sendiri(): void
    {
        $bambu = $this->mesin('Bambu Lab X1 Carbon', 'FDM');

        $this->material('PLA+', $bambu);
        $this->material('Material Yatim', null);

        $html = $this->tabHtml();

        $this->assertStringContainsString('Tanpa Mesin', $html);
        $this->assertLessThan(
            strpos($html, 'Material Yatim'),
            strpos($html, 'Bambu Lab X1 Carbon')
        );
    }

    /* ------------------------------------------------- harga tidak bergeser --- */

    /**
     * Calculator mengenali material lewat namanya. Menambahkan nama yang sama
     * untuk mesin kedua karena itu tidak boleh menggeser harga yang berlaku.
     */
    public function test_material_kedua_bernama_sama_tidak_menggeser_harga_calculator(): void
    {
        $bambu = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $creality = $this->mesin('Creality K1 Max', 'FDM');

        $this->material('PLA+', $bambu, null, ['sale_price' => 231]);

        $sebelum = $this->fdm->fresh()->toEstimatorArray()['materials']['PLA+']['price_per_gram'];

        $this->material('PLA+', $creality, null, ['sale_price' => 999]);

        $sesudah = $this->fdm->fresh()->toEstimatorArray()['materials']['PLA+']['price_per_gram'];

        $this->assertSame($sebelum, $sesudah, 'Harga material yang sudah berjalan tidak boleh bergeser.');
        $this->assertSame(300, $sesudah);
    }

    /* --------------------------------------------- batas ukuran cetak --- */

    /**
     * "Maks." pada material mengikuti volume cetak mesinnya.
     *
     * Inilah yang dibaca Edit Specification sebagai `maxSize` dan ditampilkan
     * sebagai "Maks. 200 × 200 × 200 mm".
     */
    public function test_batas_ukuran_material_diambil_dari_volume_cetak_mesin(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $mesin->update(['build_volume_x' => 200, 'build_volume_y' => 200, 'build_volume_z' => 200]);

        $this->material('PLA+', $mesin);

        $this->assertSame(['x' => 200, 'y' => 200, 'z' => 200], $this->maxSizeOf('PLA+'));
    }

    /** Mengganti mesin material langsung menggeser batasnya. */
    public function test_mengganti_mesin_mengubah_batas_ukuran(): void
    {
        $mesinA = $this->mesin('Mesin A', 'FDM');
        $mesinA->update(['build_volume_x' => 200, 'build_volume_y' => 200, 'build_volume_z' => 200]);

        $mesinB = $this->mesin('Mesin B', 'FDM');
        $mesinB->update(['build_volume_x' => 300, 'build_volume_y' => 300, 'build_volume_z' => 400]);

        $material = $this->material('PLA+', $mesinA);

        $this->assertSame(['x' => 200, 'y' => 200, 'z' => 200], $this->maxSizeOf('PLA+'));

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.price-list.materials.update', [$this->fdm, $material]), [
                'material' => 'PLA+',
                'brand' => $material->brand,
                'machine_cost_id' => $mesinB->id,
                'purchase_price' => $material->purchase_price,
                'sale_price' => $material->sale_price,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['x' => 300, 'y' => 300, 'z' => 400], $this->maxSizeOf('PLA+'));
    }

    /** Mengubah volume cetak mesinnya pun langsung ikut terbawa. */
    public function test_mengubah_volume_cetak_mesin_menggeser_batas_materialnya(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $mesin->update(['build_volume_x' => 200, 'build_volume_y' => 200, 'build_volume_z' => 200]);

        $this->material('PLA+', $mesin);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.price-list.machine-cost.update', $mesin), [
                'mesin' => $mesin->mesin,
                'watt_kwh' => $mesin->watt_kwh,
                'harga_listrik' => $mesin->harga_listrik,
                'depresiasi' => $mesin->depresiasi,
                'build_volume_x' => 256,
                'build_volume_y' => 256,
                'build_volume_z' => 256,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['x' => 256, 'y' => 256, 'z' => 256], $this->maxSizeOf('PLA+'));
    }

    /** Volume cetak menuntut ketiga sisinya; dua sisi bukan volume. */
    public function test_volume_cetak_mesin_yang_belum_lengkap_tidak_dipakai(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $mesin->update(['build_volume_x' => 200, 'build_volume_y' => 200]);

        $this->material('PLA+', $mesin);

        $this->assertNull($this->maxSizeOf('PLA+'));
    }

    /** Tanpa mesin dan tanpa batas terkurasi, nilainya kosong — UI menulis "-". */
    public function test_material_tanpa_mesin_tidak_punya_batas_ukuran(): void
    {
        $this->material('PLA+', null);

        $this->assertNull($this->maxSizeOf('PLA+'));
    }

    /**
     * Batas terkurasi pada `technical_spec` tetap dipakai selama mesinnya belum
     * ditentukan — tanpa cadangan ini, material SLA/MJF/SLM yang batasnya sudah
     * terisi justru berubah menjadi "-".
     */
    public function test_batas_terkurasi_dipakai_selama_mesin_belum_ditentukan(): void
    {
        $this->material('PLA+', null, null, [
            'technical_spec' => ['maxSize' => ['x' => 300, 'y' => 200, 'z' => 300]],
        ]);

        $this->assertSame(['x' => 300, 'y' => 200, 'z' => 300], $this->maxSizeOf('PLA+'));
    }

    /** Begitu mesinnya ditentukan, volume mesin yang menang. */
    public function test_volume_mesin_mengalahkan_batas_terkurasi(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $mesin->update(['build_volume_x' => 256, 'build_volume_y' => 256, 'build_volume_z' => 256]);

        $this->material('PLA+', $mesin, null, [
            'technical_spec' => ['maxSize' => ['x' => 300, 'y' => 200, 'z' => 300]],
        ]);

        $this->assertSame(['x' => 256, 'y' => 256, 'z' => 256], $this->maxSizeOf('PLA+'));
    }

    /** Bentuknya harus {x, y, z} agar Edit Specification dapat menulisnya. */
    public function test_batas_ukuran_ikut_pada_payload_edit_specification(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');
        $mesin->update(['build_volume_x' => 200, 'build_volume_y' => 200, 'build_volume_z' => 200]);

        $this->material('PLA+', $mesin);

        $payload = collect(app(PrintEstimator::class)->browserPayload()['FDM']['materials'])
            ->firstWhere('name', 'PLA+');

        $this->assertNotNull($payload);
        $this->assertSame(['x' => 200, 'y' => 200, 'z' => 200], $payload['maxSize']);
    }

    /* -------------------------------------------------------- batas harga --- */

    /**
     * Harga di luar jangkauan kolom ditolak sebagai galat validasi.
     *
     * Aturan `numeric` sendiri meloloskan notasi ilmiah seperti "2e23", yang
     * dulu lolos sampai MySQL dan meledak di sana sebagai galat 500 alih-alih
     * pesan yang terbaca Superadmin.
     */
    public function test_harga_di_luar_jangkauan_kolom_ditolak(): void
    {
        $mesin = $this->mesin('Bambu Lab X1 Carbon', 'FDM');

        foreach ([
            'purchase_price' => '2e23',
            'sale_price' => '2e23',
        ] as $field => $value) {
            $this->actingAs($this->superAdmin)
                ->post(route('superadmin.price-list.materials.store', $this->fdm), [$field => $value] + [
                    'material' => 'PLA+',
                    'brand' => 'eSUN',
                    'machine_cost_id' => $mesin->id,
                    'purchase_price' => 185000,
                    'sale_price' => 231,
                ])
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(0, PrintMaterial::count());
    }

    /** Harga sebesar batas kolom tetap diterima. */
    public function test_harga_tepat_pada_batas_kolom_diterima(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                'material' => 'Material Mahal',
                'brand' => 'eSUN',
                'machine_cost_id' => '',
                'purchase_price' => 9999999999,
                'sale_price' => 9999999999,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, PrintMaterial::count());
    }

    /* ------------------------------------------- fitur lama tetap berjalan --- */

    public function test_kolom_dan_pencarian_material_tetap_berjalan(): void
    {
        $bambu = $this->mesin('Bambu Lab X1 Carbon', 'FDM');

        $this->material('PLA+', $bambu, null, ['purchase_price' => 185000, 'sale_price' => 231]);
        $this->material('PETG', $bambu);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.index', ['tab' => 'fdm']))
            ->assertOk();

        foreach (['Material', 'Brand', 'Harga Beli', 'Harga/gram', 'Harga Jual', 'Pembulatan', 'Harga/10 gram', 'Remark'] as $kolom) {
            $response->assertSee($kolom);
        }

        // Rumus harganya tidak berubah: 185.000/800 = 231, 231 → 300 → 3.000.
        $response->assertSee('Rp231')->assertSee('Rp300')->assertSee('Rp3.000');

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.index', ['tab' => 'fdm', 'fdm_q' => 'PETG']))
            ->assertOk()
            ->assertSee('PETG')
            ->assertDontSee('PLA+');
    }

    public function test_hapus_massal_tetap_berjalan(): void
    {
        $bambu = $this->mesin('Bambu Lab X1 Carbon', 'FDM');

        $satu = $this->material('PLA+', $bambu);
        $dua = $this->material('PETG', $bambu);

        $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.price-list.materials.destroy-many', $this->fdm), [
                'ids' => [$satu->id, $dua->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, PrintMaterial::count());
    }

    /* -------------------------------------------------------------- alat --- */

    /**
     * Batas ukuran cetak satu material, seperti yang dibaca Edit Specification.
     *
     * @return array{x: int, y: int, z: int}|null
     */
    private function maxSizeOf(string $nama): ?array
    {
        return $this->fdm->fresh()->toEstimatorArray()['materials'][$nama]['max_size'] ?? null;
    }

    private function mesin(string $nama, ?string $technologyCode = null): MachineCost
    {
        return MachineCost::create([
            'mesin' => $nama,
            'print_technology_id' => $technologyCode === null ? null : PrintTechnology::idFor($technologyCode),
            'watt_kwh' => 0.35,
            'harga_listrik' => 1700,
            'depresiasi' => 5300,
        ]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function material(string $nama, ?MachineCost $mesin, ?PrintTechnology $technology = null, array $overrides = []): PrintMaterial
    {
        return ($technology ?? $this->fdm)->materials()->create(array_merge([
            'material' => $nama,
            'brand' => 'eSUN',
            'machine_cost_id' => $mesin?->id,
            'purchase_price' => 185000,
            'sale_price' => 231,
        ], $overrides));
    }

    /**
     * Isi panel satu tab saja.
     *
     * Halaman Price List menggambar SELURUH tab sekaligus (yang tidak aktif
     * hanya disembunyikan), jadi memeriksa seluruh halaman akan ikut menghitung
     * baris tab Machine Cost yang kebetulan bermarkup serupa.
     *
     * @param  array<string, mixed>  $query
     */
    private function tabHtml(array $query = [], string $tab = 'fdm'): string
    {
        $html = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.index', array_merge(['tab' => $tab], $query)))
            ->assertOk()
            ->getContent();

        $start = strpos($html, 'data-price-list-panel="'.$tab.'"');
        $this->assertNotFalse($start, 'Panel tab '.$tab.' tidak ditemukan.');

        $end = strpos($html, 'data-price-list-panel=', $start + 30);

        return $end === false ? substr($html, $start) : substr($html, $start, $end - $start);
    }

    /**
     * Nomor pada kolom "No" tiap baris material, urut tampil.
     *
     * @return array<int, int>
     */
    private function nomorUrut(string $html): array
    {
        preg_match_all('/<td class="px-4 py-3 text-ink-500">(\d+)<\/td>/', $html, $matches);

        return array_map('intval', $matches[1]);
    }

    /** Potongan HTML antara dua judul kelompok. */
    private function blok(string $html, string $dari, string $sampai): string
    {
        $start = strpos($html, $dari);
        $end = strpos($html, $sampai, $start);

        return substr($html, $start, $end - $start);
    }
}
