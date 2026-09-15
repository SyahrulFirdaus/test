<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CustomerType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Mode gelap pada ketiga dashboard: User, Admin, dan Superadmin.
 *
 * Ketiganya memakai kerangka yang sama (layouts/dashboard), jadi yang diuji di
 * sini bukan tampilannya melainkan bahwa pengalihnya benar-benar sampai ke
 * ketiga wilayah — dan bahwa halaman publik tidak ikut terbawa.
 */
class DarkModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function dashboards(): array
    {
        return [
            'user personal' => ['customer', 'dashboard'],
            'user business' => ['business', 'dashboard'],
            'admin' => ['admin', 'admin.dashboard'],
            'superadmin' => ['superadmin', 'superadmin.dashboard'],
        ];
    }

    private function actor(string $role): User
    {
        return match ($role) {
            'admin' => User::factory()->admin()->create(),
            'superadmin' => User::factory()->superAdmin()->create(),
            'business' => User::factory()->create(['customer_type' => CustomerType::BUSINESS]),
            default => User::factory()->create(['customer_type' => CustomerType::PERSONAL]),
        };
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dashboards')]
    public function test_pengalih_mode_gelap_tersedia(string $role, string $route): void
    {
        $this->actingAs($this->actor($role))
            ->get(route($route))
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('Aktifkan mode gelap');
    }

    /**
     * Temanya dipasang sebelum halaman digambar.
     *
     * Kalau skripnya ikut bundel yang dimuat belakangan, halaman sempat
     * tergambar terang dulu lalu berkedip menjadi gelap.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dashboards')]
    public function test_tema_dipasang_sebelum_halaman_digambar(string $role, string $route): void
    {
        $html = $this->actingAs($this->actor($role))
            ->get(route($route))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('localStorage.getItem("nusama-theme")', $html);

        // Skripnya berada di dalam <head>, bukan di bawah halaman.
        $head = substr($html, 0, strpos($html, '</head>'));
        $this->assertStringContainsString('nusama-theme', $head);
    }

    /** Halaman publik sengaja tidak ikut: temanya milik dashboard saja. */
    public function test_halaman_publik_tidak_memiliki_mode_gelap(): void
    {
        foreach ([route('home'), route('models'), route('models.guide')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('data-theme-toggle', false)
                ->assertDontSee('nusama-theme');
        }
    }
}
