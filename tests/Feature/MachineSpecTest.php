<?php

namespace Tests\Feature;

use App\Models\MachineCost;
use App\Models\PrintTechnology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Detail mesin yang dapat dibuka-tutup pada Price List Superadmin.
 *
 * Yang dijaga di sini ada tiga:
 *   - detailnya benar-benar berasal dari basis data, bukan ditulis di Blade —
 *     mengubah datanya lewat CRUD langsung mengubah yang tampil;
 *   - mesin berkelompok di bawah teknologinya, termasuk yang belum ditentukan;
 *   - kolom Price List yang sudah ada (watt, depresiasi, Machine Cost,
 *     Pembulatan) tidak ikut berubah oleh penambahan ini.
 */
class MachineSpecTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
    }

    /* ------------------------------------------------------- format data --- */

    public function test_spesifikasi_tampil_dalam_format_indonesia(): void
    {
        $machine = $this->mesin([
            'width_mm' => 389,
            'depth_mm' => 389,
            'height_mm' => 458,
            'weight_kg' => 12.95,
            'build_volume_x' => 256,
            'build_volume_y' => 256,
            'build_volume_z' => 256,
        ]);

        $this->assertSame([
            ['label' => 'Lebar (W)', 'value' => '389 mm'],
            ['label' => 'Kedalaman (D)', 'value' => '389 mm'],
            ['label' => 'Tinggi (H)', 'value' => '458 mm'],
            ['label' => 'Berat', 'value' => '12,95 kg'],
            ['label' => 'Volume cetak', 'value' => '256 × 256 × 256 mm'],
        ], $machine->spec_rows);

        $this->assertTrue($machine->hasSpecs());
    }

    /** Pecahan milimeter tetap terbaca, bilangan bulat tidak diberi ",0". */
    public function test_pecahan_milimeter_dipertahankan(): void
    {
        $machine = $this->mesin(['width_mm' => 389.5, 'height_mm' => 458]);

        $this->assertSame('389,5 mm', $machine->spec_rows[0]['value']);
        $this->assertSame('458 mm', $machine->spec_rows[2]['value']);
    }

    public function test_spesifikasi_kosong_tampil_sebagai_strip(): void
    {
        $machine = $this->mesin();

        foreach ($machine->spec_rows as $row) {
            $this->assertSame(MachineCost::UNSET, $row['value'], $row['label'].' seharusnya kosong');
        }

        $this->assertFalse($machine->hasSpecs());
    }

    /** Volume cetak menuntut ketiga sisinya, bukan sebagian. */
    public function test_volume_cetak_hanya_tampil_bila_ketiga_sisinya_terisi(): void
    {
        $sebagian = $this->mesin(['build_volume_x' => 256, 'build_volume_y' => 256]);
        $this->assertSame(MachineCost::UNSET, $sebagian->build_volume_label);

        $lengkap = $this->mesin(['mesin' => 'Mesin Lengkap', 'build_volume_x' => 220, 'build_volume_y' => 220, 'build_volume_z' => 250]);
        $this->assertSame('220 × 220 × 250 mm', $lengkap->build_volume_label);
    }

    /* ------------------------------------------------------ tampil di UI --- */

    public function test_tiap_mesin_punya_tombol_pembuka_detail(): void
    {
        $machine = $this->mesin(['width_mm' => 389]);

        $html = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-machine-toggle="'.$machine->id.'"', $html);
        $this->assertStringContainsString('aria-controls="machine-detail-'.$machine->id.'"', $html);

        // Panelnya sudah tergambar namun tertutup — dibuka JS, bukan request baru.
        $this->assertMatchesRegularExpression(
            '/data-machine-detail="'.$machine->id.'" class="[^"]*hidden/',
            $html
        );
    }

    public function test_isi_detail_diambil_dari_basis_data(): void
    {
        $this->mesin([
            'width_mm' => 389,
            'depth_mm' => 389,
            'height_mm' => 458,
            'weight_kg' => 12.95,
            'build_volume_x' => 256,
            'build_volume_y' => 256,
            'build_volume_z' => 256,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.index'))
            ->assertOk();

        foreach (['Bagian', 'Ukuran', 'Lebar (W)', 'Kedalaman (D)', 'Tinggi (H)', 'Berat', 'Volume cetak'] as $label) {
            $response->assertSee($label);
        }

        $response->assertSee('389 mm')
            ->assertSee('458 mm')
            ->assertSee('12,95 kg')
            ->assertSee('256 × 256 × 256 mm');
    }

    /** Mesin yang detailnya belum diisi diarahkan ke formulir, bukan dibiarkan kosong. */
    public function test_mesin_tanpa_detail_menawarkan_tautan_pengisian(): void
    {
        $machine = $this->mesin();

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.index'))
            ->assertOk()
            ->assertSee('Detail mesin belum diisi.')
            ->assertSee(route('superadmin.price-list.machine-cost.edit', $machine), false);
    }

    /* --------------------------------------------------------- kelompok --- */

    public function test_mesin_berkelompok_di_bawah_teknologinya(): void
    {
        $this->mesin(['mesin' => 'Mesin FDM Satu', 'technology' => 'FDM']);
        $this->mesin(['mesin' => 'Mesin SLA Satu', 'technology' => 'SLA']);

        $html = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.index'))
            ->assertOk()
            ->getContent();

        // Judul kelompok memakai <th scope="colgroup">, satu per teknologi.
        $this->assertStringContainsString('scope="colgroup"', $html);

        // Urutannya mengikuti sort_order teknologi: FDM lebih dulu dari SLA,
        // dan tiap mesin berada sesudah judul kelompoknya.
        $this->assertLessThan(
            strpos($html, 'Mesin SLA Satu'),
            strpos($html, 'Mesin FDM Satu')
        );
    }

    /** Mesin tanpa teknologi tetap terdaftar, dikumpulkan paling bawah. */
    public function test_mesin_tanpa_teknologi_masuk_kelompok_sendiri(): void
    {
        $this->mesin(['mesin' => 'Mesin FDM Satu', 'technology' => 'FDM']);
        $this->mesin(['mesin' => 'Mesin Yatim']);

        $html = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Tanpa Teknologi', $html);
        $this->assertStringContainsString('Mesin Yatim', $html);

        $this->assertLessThan(
            strpos($html, 'Mesin Yatim'),
            strpos($html, 'Mesin FDM Satu')
        );
    }

    /* -------------------------------------------------------------- CRUD --- */

    public function test_superadmin_dapat_menyimpan_detail_mesin_baru(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.machine-cost.store'), [
                'mesin' => 'Bambu Lab X1 Carbon',
                'print_technology_id' => PrintTechnology::idFor('FDM'),
                'watt_kwh' => 0.35,
                'harga_listrik' => 1700,
                'depresiasi' => 5300,
                'width_mm' => 389,
                'depth_mm' => 389,
                'height_mm' => 458,
                'weight_kg' => 12.95,
                'build_volume_x' => 256,
                'build_volume_y' => 256,
                'build_volume_z' => 256,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('superadmin.price-list.machine-cost.index'));

        $machine = MachineCost::where('mesin', 'Bambu Lab X1 Carbon')->sole();

        $this->assertSame('FDM', $machine->technology->code);
        $this->assertSame('12,95 kg', $machine->weight_label);
        $this->assertSame('256 × 256 × 256 mm', $machine->build_volume_label);
    }

    /** Inti permintaan: sesudah disimpan, expand langsung memakai data terbaru. */
    public function test_expand_mengikuti_data_terbaru_setelah_diubah(): void
    {
        $machine = $this->mesin(['width_mm' => 200, 'weight_kg' => 5]);

        $sebelum = $this->detailPanel($machine);
        $this->assertStringContainsString('200 mm', $sebelum);
        $this->assertStringContainsString('5,00 kg', $sebelum);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.price-list.machine-cost.update', $machine), [
                'mesin' => $machine->mesin,
                'watt_kwh' => $machine->watt_kwh,
                'harga_listrik' => $machine->harga_listrik,
                'depresiasi' => $machine->depresiasi,
                'width_mm' => 389,
                'weight_kg' => 12.95,
            ])
            ->assertSessionHasNoErrors();

        $sesudah = $this->detailPanel($machine);
        $this->assertStringContainsString('389 mm', $sesudah);
        $this->assertStringContainsString('12,95 kg', $sesudah);
        $this->assertStringNotContainsString('200 mm', $sesudah);
        $this->assertStringNotContainsString('5,00 kg', $sesudah);
    }

    /**
     * Potongan HTML panel detail satu mesin.
     *
     * Diperlukan karena angka seperti "200 mm" juga muncul pada build volume
     * teknologi di tab lain — memeriksa seluruh halaman akan salah tuduh.
     */
    private function detailPanel(MachineCost $machine): string
    {
        $html = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.index'))
            ->assertOk()
            ->getContent();

        // Ditandai dari caption tabel spesifikasinya sampai penutup tabel itu,
        // bukan dari baris <tr> pembungkusnya — di dalamnya ada <tr> lain.
        $start = strpos($html, 'Detail mesin '.$machine->mesin.'</caption>');
        $this->assertNotFalse($start, 'Panel detail mesin '.$machine->mesin.' tidak ditemukan.');

        $end = strpos($html, '</table>', $start);

        return substr($html, $start, $end - $start);
    }

    /** Detail boleh dikosongkan — mesin tetap dapat didaftarkan untuk harganya. */
    public function test_detail_mesin_tidak_wajib_diisi(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.machine-cost.store'), [
                'mesin' => 'Mesin Tanpa Detail',
                'watt_kwh' => 0.25,
                'harga_listrik' => 1700,
                'depresiasi' => 2100,
            ])
            ->assertSessionHasNoErrors();

        $machine = MachineCost::where('mesin', 'Mesin Tanpa Detail')->sole();

        $this->assertNull($machine->width_mm);
        $this->assertNull($machine->print_technology_id);
        $this->assertFalse($machine->hasSpecs());
    }

    public function test_detail_mesin_menolak_isian_yang_tidak_masuk_akal(): void
    {
        $dasar = [
            'mesin' => 'Mesin Uji',
            'watt_kwh' => 0.25,
            'harga_listrik' => 1700,
            'depresiasi' => 2100,
        ];

        foreach ([
            'width_mm' => 'bukan angka',
            'weight_kg' => -1,
            'build_volume_x' => 0,
            'print_technology_id' => 9999,
        ] as $field => $value) {
            $this->actingAs($this->superAdmin)
                ->post(route('superadmin.price-list.machine-cost.store'), $dasar + [$field => $value])
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(0, MachineCost::where('mesin', 'Mesin Uji')->count());
    }

    /**
     * Angka di luar jangkauan kolomnya ditolak validasi, bukan oleh MySQL.
     *
     * "2e23" lolos aturan `numeric` tetapi tidak muat pada `decimal(12,2)`.
     */
    public function test_angka_di_luar_jangkauan_kolom_ditolak(): void
    {
        $dasar = [
            'mesin' => 'Mesin Uji',
            'watt_kwh' => 0.25,
            'harga_listrik' => 1700,
            'depresiasi' => 2100,
        ];

        foreach ([
            'watt_kwh' => '2e23',
            'harga_listrik' => '2e23',
            'depresiasi' => '2e23',
        ] as $field => $value) {
            $this->actingAs($this->superAdmin)
                ->post(route('superadmin.price-list.machine-cost.store'), [$field => $value] + $dasar)
                ->assertSessionHasErrors($field);
        }

        $this->assertSame(0, MachineCost::where('mesin', 'Mesin Uji')->count());
    }

    public function test_formulir_menyediakan_pilihan_teknologi_dan_kolom_detail(): void
    {
        $machine = $this->mesin(['technology' => 'FDM']);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.edit', $machine))
            ->assertOk();

        foreach (['print_technology_id', 'width_mm', 'depth_mm', 'height_mm', 'weight_kg', 'build_volume_x', 'build_volume_y', 'build_volume_z'] as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
    }

    /* ------------------------------------------------- tidak ada yang hilang --- */

    /** Kolom harga yang sudah ada tetap utuh — penambahan ini hanya menumpang. */
    public function test_kolom_price_list_lama_tetap_tampil(): void
    {
        $machine = $this->mesin(['watt_kwh' => 0.35, 'harga_listrik' => 1700, 'depresiasi' => 5300]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.index'))
            ->assertOk();

        foreach (['Watt (KWH)', 'Harga Listrik', 'Depresiasi', 'Listrik/Hour', 'Machine Cost', 'Pembulatan'] as $kolom) {
            $response->assertSee($kolom);
        }

        // Angka hasil perhitungannya pun tidak bergeser.
        $response->assertSee('Rp'.number_format($machine->rounded_machine_cost, 0, ',', '.'))
            ->assertSee(route('superadmin.price-list.machine-cost.edit', $machine), false);
    }

    /** Pencarian mesin tetap bekerja seperti sebelumnya. */
    public function test_pencarian_mesin_tetap_berjalan(): void
    {
        $this->mesin(['mesin' => 'Bambu Lab P1S', 'technology' => 'FDM']);
        $this->mesin(['mesin' => 'Elegoo Saturn 4', 'technology' => 'SLA']);

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.machine-cost.index', ['machine_q' => 'Bambu']))
            ->assertOk()
            ->assertSee('Bambu Lab P1S')
            ->assertDontSee('Elegoo Saturn 4');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function mesin(array $overrides = []): MachineCost
    {
        $code = $overrides['technology'] ?? null;
        unset($overrides['technology']);

        return MachineCost::create(array_merge([
            'mesin' => 'Mesin Uji '.MachineCost::count(),
            'print_technology_id' => $code === null ? null : PrintTechnology::idFor($code),
            'watt_kwh' => 0.25,
            'harga_listrik' => 1700,
            'depresiasi' => 2100,
        ], $overrides));
    }
}
