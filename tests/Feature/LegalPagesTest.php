<?php

namespace Tests\Feature;

use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanyProfileSeeder::class);
    }

    public function test_kedua_halaman_legal_dapat_diakses(): void
    {
        $this->get('/kebijakan-privasi')
            ->assertOk()
            ->assertSee('Kebijakan Privasi')
            ->assertSee('Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi')
            ->assertSee('id="cookie"', false);

        $this->get('/syarat-ketentuan')
            ->assertOk()
            ->assertSee('Syarat &amp; Ketentuan', false)
            // Angka diambil dari config/payment.php.
            ->assertSee(config('payment.window_hours').' jam');
    }

    public function test_tautan_legal_ada_di_footer_dan_banner_cookie(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Legal"', $html);
        $this->assertStringContainsString('data-cookie-banner', $html);
        $this->assertStringContainsString('href="'.route('legal.privacy').'#cookie"', $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="'.route('legal.terms').'"'));
    }

    public function test_tautan_legal_ada_di_bawah_setiap_form(): void
    {
        foreach (['login', 'register', 'password.request', 'tracking.index', 'models'] as $name) {
            $this->get(route($name))
                ->assertOk()
                ->assertSee('data-legal-note', false)
                ->assertSee('href="'.route('legal.terms').'"', false)
                ->assertSee('href="'.route('legal.privacy').'"', false);
        }

        $this->get(route('password.reset', ['token' => 'contoh', 'email' => 'user@contoh.test']))
            ->assertOk()
            ->assertSee('data-legal-note', false);
    }
}
