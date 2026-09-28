<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminPresence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Kolom Status Login pada menu Akun Admin: Aktif selama Admin sedang login,
 * Nonaktif setelah logout, dan terlihat hanya oleh Superadmin.
 */
class AdminLoginStatusTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function statusOf(User $superAdmin, User $admin): string
    {
        $html = $this->actingAs($superAdmin)
            ->get(route('superadmin.admins.index'))
            ->assertOk()
            ->assertSee('Status Login')
            ->getContent();

        // Baris milik admin itu saja.
        $row = substr($html, strpos($html, e($admin->email)));
        preg_match('/data-login-status="(aktif|nonaktif)"/', $row, $match);

        return $match[1];
    }

    public function test_admin_yang_belum_login_berstatus_nonaktif(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertSame('nonaktif', $this->statusOf($this->superAdmin(), $admin));
    }

    public function test_login_menjadikan_aktif_dan_logout_menjadikan_nonaktif(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'rahasia-uji-123']);

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'rahasia-uji-123']);
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(AdminPresence::isOnline($admin));

        $this->post(route('admin.logout'));
        $this->assertGuest();
        $this->assertFalse(AdminPresence::isOnline($admin));
    }

    public function test_status_tampil_aktif_bagi_superadmin(): void
    {
        $admin = User::factory()->admin()->create();
        $superAdmin = $this->superAdmin();

        // Admin memakai dashboard-nya; sesinya tercatat.
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        Auth::forgetGuards();

        $this->assertSame('aktif', $this->statusOf($superAdmin, $admin));
    }

    public function test_sesi_kedaluwarsa_terbaca_nonaktif(): void
    {
        $admin = User::factory()->admin()->create();
        AdminPresence::markOnline($admin);

        $this->travel(config('session.lifetime') + 1)->minutes();

        $this->assertFalse(AdminPresence::isOnline($admin));
    }

    public function test_akun_superadmin_dan_pelanggan_tidak_dicatat(): void
    {
        $superAdmin = $this->superAdmin();
        $this->actingAs($superAdmin)->get(route('superadmin.admins.index'));

        $this->assertFalse(Cache::has('admin-presence:'.$superAdmin->id));
        $this->assertFalse(AdminPresence::tracks(User::factory()->create()));
    }

    public function test_admin_biasa_tidak_dapat_membuka_menu_akun_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('superadmin.admins.index'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
