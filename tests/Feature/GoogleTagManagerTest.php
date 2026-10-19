<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Container Google Tag Manager: <head> + <noscript>, setelah Consent Mode v2. */
class GoogleTagManagerTest extends TestCase
{
    use RefreshDatabase;

    private const ID = 'GTM-ABC1234';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanyProfileSeeder::class);
        config(['services.google_tag_manager.id' => self::ID]);
    }

    /** @return array<string, string> halaman wajib → HTML */
    private function pages(): array
    {
        return [
            'home' => $this->get(route('home'))->assertOk()->getContent(),
            '3d-models' => $this->get(route('models'))->assertOk()->getContent(),
            'login' => $this->get(route('login'))->assertOk()->getContent(),
            'register' => $this->get(route('register'))->assertOk()->getContent(),
            'tracking' => $this->get(route('tracking.index'))->assertOk()->getContent(),
            'dashboard pelanggan' => $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->getContent(),
        ];
    }

    public function test_head_dan_noscript_terpasang_sekali_di_semua_halaman(): void
    {
        foreach ($this->pages() as $name => $html) {
            $this->assertSame(1, substr_count($html, 'https://www.googletagmanager.com/gtm.js?id='), $name.': snippet head');
            $this->assertSame(1, substr_count($html, '"'.self::ID.'"'), $name.': ID di head');
            $this->assertSame(1, substr_count($html, 'https://www.googletagmanager.com/ns.html?id='.self::ID), $name.': noscript');

            // Snippet head di dalam <head>; noscript tepat setelah <body>.
            $this->assertLessThan(strpos($html, '</head>'), strpos($html, 'gtm.js?id='), $name);
            $this->assertMatchesRegularExpression('#<body[^>]*>\s*<!-- Google Tag Manager \(noscript\) -->\s*<noscript><iframe src="https://www\.googletagmanager\.com/ns\.html\?id='.self::ID.'"#', $html, $name);
        }
    }

    public function test_consent_default_denied_sebelum_gtm(): void
    {
        foreach ($this->pages() as $name => $html) {
            $consent = strpos($html, "gtag('consent', 'default'");

            $this->assertNotFalse($consent, $name);
            $this->assertLessThan(strpos($html, 'gtm.js?id='), $consent, $name);
            // GTM memakai dataLayer bersama situs, bukan dataLayer baru.
            $this->assertStringContainsString("})(window,document,'script','dataLayer',", $html, $name);
        }
    }

    public function test_tidak_dimuat_di_dashboard_pengelola(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->superAdmin()->create()] as $staff) {
            $html = $this->actingAs($staff)->get($staff->isSuperAdmin() ? route('superadmin.dashboard') : route('admin.dashboard'))->assertOk()->getContent();

            $this->assertStringNotContainsString('googletagmanager.com', $html);
        }
    }

    public function test_id_kosong_atau_tidak_sah_tidak_memuat_gtm(): void
    {
        foreach ([null, '', 'GTM-', 'UA-12345', 'GTM-ABC"><script>'] as $id) {
            config(['services.google_tag_manager.id' => $id]);
            $html = $this->get(route('home'))->getContent();

            $this->assertStringNotContainsString('gtm.js', $html, (string) $id);
            $this->assertStringNotContainsString('ns.html', $html, (string) $id);
        }
    }
}
