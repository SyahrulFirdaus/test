<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\FdmMaterial;
use App\Models\SlaMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Support\ActivityAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Penghapusan massal material FDM & SLA pada Price List.
 *
 * Yang dijaga bukan hanya "barisnya hilang", melainkan juga bahwa aksinya
 * tetap milik Superadmin, terekam sebagai SATU peristiwa, dan tidak dapat
 * menyentuh baris di luar yang dipilih.
 */
class PriceListBulkDeleteTest extends TestCase
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

    /* ------------------------------------------------------------- FDM --- */

    public function test_superadmin_menghapus_beberapa_material_fdm_sekaligus(): void
    {
        $dihapus = FdmMaterial::query()->orderBy('id')->take(3)->get();
        $sisa = FdmMaterial::query()->whereNotIn('id', $dihapus->modelKeys())->count();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('FDM')), ['ids' => $dihapus->modelKeys()])
            ->assertRedirect(route('superadmin.price-list.index', ['tab' => 'fdm']))
            ->assertSessionHas('status', '3 material FDM berhasil dihapus.');

        foreach ($dihapus as $material) {
            $this->assertDatabaseMissing('print_materials', ['id' => $material->id]);
        }

        // Yang tidak dipilih tidak tersentuh sama sekali.
        $this->assertSame($sisa, FdmMaterial::count());
    }

    public function test_penghapusan_massal_tercatat_sebagai_satu_peristiwa(): void
    {
        $dihapus = FdmMaterial::query()->orderBy('id')->take(3)->get();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('FDM')), ['ids' => $dihapus->modelKeys()]);

        // Satu baris log berisi ketiganya, bukan tiga baris terpisah.
        $log = ActivityLog::where('action', ActivityAction::PRICE_LIST_UPDATE)->sole();

        $this->assertStringContainsString('Menghapus 3 material FDM', $log->description);
        $this->assertCount(3, $log->old_values['materials']);
        // Urutannya mengikuti urutan baca controller (menurut nama), bukan
        // urutan pemilihan — yang penting ketiganya tercatat.
        $this->assertEqualsCanonicalizing(
            $dihapus->pluck('material')->all(),
            array_column($log->old_values['materials'], 'material'),
        );
    }

    public function test_id_yang_sudah_tidak_ada_diabaikan_bukan_menggagalkan(): void
    {
        $ada = FdmMaterial::query()->orderBy('id')->first();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('FDM')), ['ids' => [$ada->id, 999999]])
            ->assertRedirect()
            ->assertSessionHas('status', '1 material FDM berhasil dihapus.');

        $this->assertDatabaseMissing('print_materials', ['id' => $ada->id]);
    }

    public function test_tanpa_satu_pun_pilihan_ditolak(): void
    {
        $sebelum = FdmMaterial::count();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('FDM')), ['ids' => []])
            ->assertSessionHasErrors('ids');

        $this->assertSame($sebelum, FdmMaterial::count());
    }

    public function test_seluruh_id_tidak_dikenal_tidak_menghapus_apa_pun(): void
    {
        $sebelum = FdmMaterial::count();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('FDM')), ['ids' => [999998, 999999]])
            ->assertSessionHas('error');

        $this->assertSame($sebelum, FdmMaterial::count());
        $this->assertSame(0, ActivityLog::where('action', ActivityAction::PRICE_LIST_UPDATE)->count());
    }

    /* ------------------------------------------------------------- SLA --- */

    public function test_superadmin_menghapus_beberapa_material_sla_sekaligus(): void
    {
        $dihapus = SlaMaterial::query()->orderBy('id')->take(2)->get();
        $sisa = SlaMaterial::count() - 2;

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('SLA')), ['ids' => $dihapus->modelKeys()])
            ->assertRedirect(route('superadmin.price-list.index', ['tab' => 'sla']))
            ->assertSessionHas('status', '2 material SLA berhasil dihapus.');

        $this->assertSame($sisa, SlaMaterial::count());
    }

    /** Tab SLA tidak boleh dapat menghapus baris milik tab FDM. */
    public function test_tiap_tab_hanya_menyentuh_tabelnya_sendiri(): void
    {
        $fdm = FdmMaterial::query()->orderBy('id')->first();
        $fdmSebelum = FdmMaterial::count();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('SLA')), ['ids' => [$fdm->id]]);

        $this->assertDatabaseHas('print_materials', ['id' => $fdm->id]);
        $this->assertSame($fdmSebelum, FdmMaterial::count());
    }

    /* -------------------------------------------------------- hak akses --- */

    public function test_admin_biasa_tidak_dapat_menghapus_massal(): void
    {
        $sebelum = FdmMaterial::count();
        $ids = FdmMaterial::query()->take(3)->pluck('id')->all();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('FDM')), ['ids' => $ids])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($sebelum, FdmMaterial::count());
    }

    public function test_pelanggan_tidak_dapat_menghapus_massal(): void
    {
        $sebelum = SlaMaterial::count();
        $ids = SlaMaterial::query()->take(2)->pluck('id')->all();

        $this->actingAs(User::factory()->create())
            ->delete(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode('SLA')), ['ids' => $ids])
            ->assertRedirect(route('dashboard'));

        $this->assertSame($sebelum, SlaMaterial::count());
    }

    /* ---------------------------------------------------------- tampilan --- */

    public function test_kedua_tab_menampilkan_kotak_centang_dan_tombolnya(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.index'))
            ->assertOk();

        foreach (['fdm', 'sla'] as $tab) {
            $response->assertSee('id="'.$tab.'-bulk-delete"', false)
                ->assertSee('data-bulk-all="'.$tab.'"', false)
                ->assertSee('data-bulk-item="'.$tab.'"', false)
                ->assertSee(route('superadmin.price-list.materials.destroy-many', PrintTechnology::findByCode($tab)));
        }

        $response->assertSee('Hapus Terpilih');
    }

    /**
     * Kotak centangnya dikaitkan ke formulir di luar tabel lewat atribut
     * `form`, karena tiap baris sudah punya formulir hapus satuannya sendiri
     * dan formulir bersarang bukan HTML yang sah.
     */
    public function test_kotak_centang_dikaitkan_lewat_atribut_form(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('form="fdm-bulk-delete"', $html);
        $this->assertStringContainsString('form="sla-bulk-delete"', $html);

        // Formulir massalnya ditutup sebelum <table> dibuka.
        $formEnd = strpos($html, 'id="fdm-bulk-delete"');
        $tableStart = strpos($html, '<table', $formEnd);
        $this->assertLessThan($tableStart, strpos($html, '</form>', $formEnd));
    }
}
