<?php

namespace Tests\Feature;

use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanyProfileSeeder::class);
        config(['app.canonical_url' => 'https://nusama3d.com']);
    }

    public static function variations(): array
    {
        return [
            'http tanpa www' => ['http://nusama3d.com/about', 'https://nusama3d.com/about'],
            'http dengan www' => ['http://www.nusama3d.com/about', 'https://nusama3d.com/about'],
            'https dengan www' => ['https://www.nusama3d.com/about', 'https://nusama3d.com/about'],
            'beranda via www' => ['https://www.nusama3d.com/', 'https://nusama3d.com/'],
            'query ikut dibawa' => ['http://www.nusama3d.com/tracking?kode=QTN-1', 'https://nusama3d.com/tracking?kode=QTN-1'],
        ];
    }

    #[DataProvider('variations')]
    public function test_variasi_url_dialihkan_permanen_ke_alamat_resmi(string $from, string $to): void
    {
        $this->get($from)->assertStatus(301)->assertRedirect($to);
    }

    public function test_alamat_resmi_tidak_dialihkan(): void
    {
        $this->get('https://nusama3d.com/about')->assertOk();
    }

    public function test_https_di_balik_proxy_tidak_berputar(): void
    {
        // TLS diakhiri di proxy/CDN: aplikasi menerima http + X-Forwarded-Proto.
        $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('http://nusama3d.com/about')
            ->assertOk();
    }

    public function test_tanpa_alamat_resmi_tidak_ada_pengalihan(): void
    {
        // Localhost & lingkungan selain production: canonical_url kosong.
        config(['app.canonical_url' => null]);

        $this->get('http://www.nusama3d.com/about')->assertOk();
    }
}
