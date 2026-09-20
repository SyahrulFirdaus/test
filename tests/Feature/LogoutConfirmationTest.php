<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keluar dari akun selalu ditanya lebih dulu, untuk seluruh role.
 *
 * Yang ditahan hanya pengirimannya: rute, middleware, dan mekanisme logout
 * Laravel tidak berubah sedikit pun — tombol "Batal" hanya menutup modal dan
 * tidak pernah menyentuh session.
 */
class LogoutConfirmationTest extends TestCase
{
    use RefreshDatabase;

    /** Tanda-tanda modal konfirmasi pada sebuah formulir logout. */
    private function assertAsksBeforeLoggingOut(string $html): void
    {
        $this->assertStringContainsString('data-confirm-title="Konfirmasi Logout"', $html);
        $this->assertStringContainsString('data-confirm="Apakah Anda yakin ingin keluar dari akun?"', $html);
        $this->assertStringContainsString('data-confirm-accept="Ya, Logout"', $html);
        $this->assertStringContainsString('data-confirm-cancel="Batal"', $html);

        // Markup modalnya sendiri ikut tergambar di halaman yang sama.
        $this->assertStringContainsString('data-confirm-dialog', $html);
    }

    public function test_logout_user_ditanya_lebih_dulu(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertAsksBeforeLoggingOut($html);
    }

    public function test_logout_admin_ditanya_lebih_dulu(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertAsksBeforeLoggingOut($html);
    }

    public function test_logout_superadmin_ditanya_lebih_dulu(): void
    {
        $html = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertAsksBeforeLoggingOut($html);
    }

    /** Navbar situs publik memakai modal yang sama. */
    public function test_logout_pada_navbar_publik_ditanya_lebih_dulu(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertAsksBeforeLoggingOut($html);
    }

    /** Rute logout itu sendiri tidak berubah: sekali dipanggil, session berakhir. */
    public function test_rute_logout_tetap_berjalan_seperti_semula(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect();

        $this->assertGuest();
    }
}
