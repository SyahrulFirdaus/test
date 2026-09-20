<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Google tag (gtag.js) untuk Google Ads.
 *
 * Dipasang pada halaman yang dilihat PENGUNJUNG. Dashboard pengelola sengaja
 * tidak ikut: lalu lintas admin dan superadmin bukan calon pelanggan, dan
 * menghitungnya hanya mengaburkan data konversi.
 */
class GoogleTagTest extends TestCase
{
    use RefreshDatabase;

    private const ID = 'AW-17850382906';

    private function assertHasTag(string $html): void
    {
        $this->assertStringContainsString(
            'https://www.googletagmanager.com/gtag/js?id='.self::ID,
            $html,
        );
        $this->assertStringContainsString("gtag('config', '".self::ID."')", $html);
    }

    private function assertHasNoTag(string $html): void
    {
        $this->assertStringNotContainsString('googletagmanager.com', $html);
    }

    public function test_terpasang_pada_halaman_publik(): void
    {
        $this->assertHasTag($this->get(route('home'))->assertOk()->getContent());
    }

    public function test_terpasang_pada_halaman_masuk_dan_daftar(): void
    {
        $this->assertHasTag($this->get(route('login'))->assertOk()->getContent());
        $this->assertHasTag($this->get(route('register'))->assertOk()->getContent());
    }

    public function test_terpasang_pada_dashboard_pelanggan(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))->assertOk()->getContent();

        $this->assertHasTag($html);
    }

    public function test_tidak_terpasang_pada_dashboard_pengelola(): void
    {
        $this->assertHasNoTag(
            $this->actingAs(User::factory()->admin()->create())
                ->get(route('admin.dashboard'))->assertOk()->getContent()
        );

        $this->assertHasNoTag(
            $this->actingAs(User::factory()->superAdmin()->create())
                ->get(route('superadmin.dashboard'))->assertOk()->getContent()
        );
    }

    /** ID yang dikosongkan di .env mematikan pemasangannya sama sekali. */
    public function test_dapat_dimatikan_lewat_konfigurasi(): void
    {
        config(['services.google_ads.id' => null]);

        $this->assertHasNoTag($this->get(route('home'))->assertOk()->getContent());
    }
}
