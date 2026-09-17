<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Price List: tiap item menu sidebar adalah halaman tersendiri, bukan tab.
 */
class PriceListPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Halaman Rumus Harga Manual membaca kurs USD/IDR; penyedianya tidak
        // pernah benar-benar dihubungi dari pengujian.
        Http::fake(fn () => Http::response([
            'rates' => ['IDR' => 17690],
            'time_last_update_unix' => strtotime('2026-09-16T00:02:31+00:00'),
        ]));
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    /** @return array<string, array{string, string}> */
    public static function pages(): array
    {
        return [
            'fdm' => ['/superadmin/price-list/fdm', 'fdm'],
            'sla' => ['/superadmin/price-list/sla', 'slai'],
            'mjf' => ['/superadmin/price-list/mjf', 'mjf'],
            'slm' => ['/superadmin/price-list/slm', 'slm'],
            'machine cost' => ['/superadmin/price-list/machine-cost', 'machine-cost'],
            'rumus harga otomatis' => ['/superadmin/price-list/rumus-harga-otomatis', 'harga'],
            'rumus harga manual' => ['/superadmin/price-list/rumus-harga-manual', 'harga-manual'],
            'teknologi' => ['/superadmin/price-list/teknologi', 'teknologi'],
        ];
    }

    #[DataProvider('pages')]
    public function test_tiap_menu_punya_halaman_dan_disorot_di_sidebar(string $url, string $key): void
    {
        $html = $this->actingAs($this->superAdmin())->get($url)->assertOk()->getContent();

        // Hanya item menu halaman ini yang ditandai aktif.
        $this->assertMatchesRegularExpression('/data-price-list-link="'.preg_quote($key, '/').'"\s+aria-current="page"/', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));

        // Tidak ada tab di dalam konten.
        $this->assertStringNotContainsString('role="tablist"', $html);
    }

    public function test_halaman_fdm_tidak_menampilkan_teknologi_lain(): void
    {
        $main = $this->main($this->actingAs($this->superAdmin())->get('/superadmin/price-list/fdm')->assertOk()->getContent());

        $this->assertStringContainsString('Material FDM', $main);
        $this->assertStringContainsString('PLA Plus', $main);

        // Judul, formulir hapus massal, dan tombol tambah teknologi lain tidak ada.
        foreach (['sla' => 'slai', 'mjf' => 'mjf', 'slm' => 'slm'] as $label => $tab) {
            $this->assertStringNotContainsString('Material '.strtoupper($label), $main);
            $this->assertStringNotContainsString('id="'.$tab.'-bulk-delete"', $main);
        }

        foreach (['Titanium', 'Standard Resin Plus Sunlu', 'Machine Cost</h3>', 'Rumus Harga Jual'] as $lain) {
            $this->assertStringNotContainsString($lain, $main);
        }
    }

    public function test_menu_sidebar_mengikuti_struktur_baru(): void
    {
        $html = $this->actingAs($this->superAdmin())->get('/superadmin/price-list/fdm')->assertOk()->getContent();

        foreach (['Teknologi FDM', 'Teknologi SLA', 'Teknologi MJF', 'Teknologi SLM', 'Rumus Harga Manual', 'Rumus Harga Otomatis'] as $label) {
            $this->assertMatchesRegularExpression('/data-price-list-link="[^"]+"[^>]*>\s*'.preg_quote($label, '/').'\s*</', $html);
        }

        $this->assertStringNotContainsString('data-price-list-link="packaging"', $html);
    }

    public function test_rumus_harga_sla_pindah_ke_rumus_harga_manual(): void
    {
        $superAdmin = $this->superAdmin();

        $sla = $this->main($this->actingAs($superAdmin)->get('/superadmin/price-list/sla')->assertOk()->getContent());
        $this->assertStringNotContainsString('Total Bayar ke JLC', $sla);
        $this->assertStringNotContainsString('Rumus Harga', $sla);

        $manual = $this->main($this->actingAs($superAdmin)->get('/superadmin/price-list/rumus-harga-manual')->assertOk()->getContent());
        $this->assertStringContainsString('Total Bayar ke JLC', $manual);
        $this->assertStringContainsString('Final Price', $manual);
        $this->assertStringNotContainsString('Material SLA', $manual);

        // Menyimpan rumusnya kembali ke halaman Rumus Harga Manual.
        $this->actingAs($superAdmin)
            ->patch(route('superadmin.price-list.sla-industries.update'), [
                'jlc_price_usd' => 100, 'jlc_shipping_usd' => 2, 'customs_idr' => 500000, 'margin_percent' => 40,
            ])
            ->assertRedirect(route('superadmin.price-list.harga-manual'));
    }

    public function test_packaging_tetap_dapat_dibuka_dari_rumus_harga_otomatis(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->get('/superadmin/price-list/rumus-harga-otomatis')
            ->assertOk()
            ->assertSee(route('superadmin.price-list.packaging.index'));

        $html = $this->actingAs($superAdmin)->get('/superadmin/price-list/packaging')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-price-list-link="harga"\s+aria-current="page"/', $html);
    }

    public function test_rumus_harga_otomatis_hanya_berisi_satu_rumus(): void
    {
        $main = $this->main($this->actingAs($this->superAdmin())->get('/superadmin/price-list/rumus-harga-otomatis')->assertOk()->getContent());

        $this->assertSame(1, substr_count($main, 'Rincian Harga Jual'));
        $this->assertSame(1, substr_count($main, 'name="risk_percent"'));
        $this->assertStringContainsString(route('superadmin.price-list.harga.update'), $main);

        foreach (['FDM', 'MJF', 'SLM', 'SLA'] as $code) {
            $this->assertStringNotContainsString('Rincian Harga Jual: '.$code, $main);
            $this->assertStringNotContainsString('Parameter '.$code, $main);
        }
    }

    public function test_halaman_mjf_hanya_menampilkan_mjf(): void
    {
        $main = $this->main($this->actingAs($this->superAdmin())->get('/superadmin/price-list/mjf')->assertOk()->getContent());

        $this->assertStringContainsString('PA12', $main);
        $this->assertStringContainsString('id="mjf-bulk-delete"', $main);
        $this->assertStringNotContainsString('id="fdm-bulk-delete"', $main);
        $this->assertStringNotContainsString('PLA Plus', $main);
        $this->assertStringNotContainsString('Titanium', $main);
    }

    public function test_alamat_lama_diteruskan_ke_halaman_barunya(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->get('/superadmin/price-list')
            ->assertRedirect('/superadmin/price-list/fdm');

        $this->actingAs($superAdmin)->get('/superadmin/price-list?tab=harga')
            ->assertRedirect('/superadmin/price-list/rumus-harga-otomatis');

        $this->actingAs($superAdmin)->get('/superadmin/price-list/harga')
            ->assertRedirect('/superadmin/price-list/rumus-harga-otomatis');

        $this->actingAs($superAdmin)->get('/superadmin/price-list?tab=slai&slai_q=Resin')
            ->assertRedirect('/superadmin/price-list/sla?slai_q=Resin');
    }

    public function test_teknologi_tidak_dikenal_atau_nonaktif_tidak_ditemukan(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->get('/superadmin/price-list/tidakada')->assertNotFound();

        // SLA lama sudah digabung; alamat /sla milik SLA yang aktif (kode SLAI).
        $this->actingAs($superAdmin)->get('/superadmin/price-list/slai')->assertNotFound();
    }

    public function test_admin_biasa_tidak_dapat_membuka_price_list(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@nusama3d.com']);

        $response = $this->actingAs($admin)->get('/superadmin/price-list/fdm');

        $this->assertNotSame(200, $response->getStatusCode());
    }

    private function main(string $html): string
    {
        $start = strpos($html, '<main');

        return substr($html, $start, strpos($html, '</main>', $start) - $start);
    }
}
