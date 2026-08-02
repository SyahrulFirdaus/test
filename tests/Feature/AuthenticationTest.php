<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Pendaftaran, masuk, lupa kata sandi, dan ganti kata sandi.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Andi Saputra',
            'phone' => '0812 3456 7890',
            'city' => 'Bandung',
            'postal_code' => '40123',
            'address' => 'Jl. Merdeka No. 12, Sumur Bandung',
            'email' => 'andi@contoh.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    /* ------------------------------------------------------ pendaftaran --- */

    public function test_pengunjung_dapat_mendaftar_dan_langsung_masuk(): void
    {
        $this->post(route('register.store'), $this->registrationPayload())
            ->assertRedirect(route('dashboard'));

        $user = User::sole();

        $this->assertSame('Andi Saputra', $user->name);
        $this->assertSame('Bandung', $user->city);
        $this->assertSame('40123', $user->postal_code);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));

        $this->assertAuthenticatedAs($user);
    }

    public function test_seluruh_data_pendaftaran_divalidasi(): void
    {
        $this->post(route('register.store'), [])
            ->assertSessionHasErrors(['name', 'phone', 'city', 'postal_code', 'address', 'email', 'password']);

        $this->assertSame(0, User::count());
    }

    public function test_kode_pos_dan_konfirmasi_password_diperiksa(): void
    {
        $this->post(route('register.store'), $this->registrationPayload([
            'postal_code' => '40',
            'password_confirmation' => 'berbeda123',
        ]))->assertSessionHasErrors(['postal_code', 'password']);

        $this->assertSame(0, User::count());
    }

    public function test_email_yang_sudah_terdaftar_ditolak(): void
    {
        User::factory()->create(['email' => 'andi@contoh.test']);

        $this->post(route('register.store'), $this->registrationPayload())
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::count());
    }

    /* ------------------------------------------------------------ masuk --- */

    public function test_pelanggan_dapat_masuk_dan_diarahkan_ke_dashboardnya(): void
    {
        $user = User::factory()->create([
            'email' => 'andi@contoh.test',
            'password' => Hash::make('rahasia123'),
        ]);

        $this->post(route('login.store'), [
            'email' => 'andi@contoh.test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_me_menyimpan_token_pada_cookie(): void
    {
        User::factory()->create([
            'email' => 'andi@contoh.test',
            'password' => Hash::make('rahasia123'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'andi@contoh.test',
            'password' => 'rahasia123',
            'remember' => '1',
        ]);

        $response->assertCookie(auth()->guard()->getRecallerName());
    }

    public function test_kata_sandi_salah_ditolak(): void
    {
        User::factory()->create([
            'email' => 'andi@contoh.test',
            'password' => Hash::make('rahasia123'),
        ]);

        $this->post(route('login.store'), [
            'email' => 'andi@contoh.test',
            'password' => 'salah',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_yang_masuk_lewat_halaman_pelanggan_diarahkan_ke_dashboard_admin(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@nusama3d.com',
            'password' => Hash::make('rahasia123'),
        ]);

        $this->post(route('login.store'), [
            'email' => 'admin@nusama3d.com',
            'password' => 'rahasia123',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_akun_pelanggan_tidak_dapat_masuk_lewat_halaman_admin(): void
    {
        User::factory()->create([
            'email' => 'andi@contoh.test',
            'password' => Hash::make('rahasia123'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'andi@contoh.test',
            'password' => 'rahasia123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_pelanggan_tidak_dapat_membuka_dashboard_admin(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.quotations.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_tamu_diarahkan_ke_halaman_login_pelanggan(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_pengguna_dapat_keluar(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    /* --------------------------------------------------- lupa password --- */

    public function test_tautan_reset_password_dikirim_ke_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'andi@contoh.test']);

        $this->post(route('password.email'), ['email' => 'andi@contoh.test'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_email_tidak_terdaftar_tidak_membocorkan_keberadaan_akun(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'entah@contoh.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_password_baru_dapat_dibuat_lewat_tautan_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'andi@contoh.test',
            'password' => Hash::make('lama12345'),
        ]);

        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'andi@contoh.test',
            'password' => 'baru123456',
            'password_confirmation' => 'baru123456',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('baru123456', $user->fresh()->password));
    }

    public function test_token_reset_yang_tidak_sah_ditolak(): void
    {
        $user = User::factory()->create(['email' => 'andi@contoh.test']);

        $this->post(route('password.update'), [
            'token' => 'token-palsu',
            'email' => 'andi@contoh.test',
            'password' => 'baru123456',
            'password_confirmation' => 'baru123456',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(Hash::check('baru123456', $user->fresh()->password));
    }

    /* --------------------------------------------------- ganti password --- */

    public function test_pengguna_dapat_mengganti_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('lama12345')]);

        $this->actingAs($user)
            ->put(route('dashboard.password.update'), [
                'current_password' => 'lama12345',
                'password' => 'baru123456',
                'password_confirmation' => 'baru123456',
            ])->assertSessionHas('status');

        $this->assertTrue(Hash::check('baru123456', $user->fresh()->password));
    }

    public function test_password_lama_yang_salah_ditolak(): void
    {
        $user = User::factory()->create(['password' => Hash::make('lama12345')]);

        $this->actingAs($user)
            ->put(route('dashboard.password.update'), [
                'current_password' => 'bukan-ini',
                'password' => 'baru123456',
                'password_confirmation' => 'baru123456',
            ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('lama12345', $user->fresh()->password));
    }

    public function test_admin_juga_dapat_mengganti_passwordnya(): void
    {
        $admin = User::factory()->admin()->create(['password' => Hash::make('lama12345')]);

        $this->actingAs($admin)
            ->put(route('admin.password.update'), [
                'current_password' => 'lama12345',
                'password' => 'baru123456',
                'password_confirmation' => 'baru123456',
            ])->assertSessionHas('status');

        $this->assertTrue(Hash::check('baru123456', $admin->fresh()->password));
    }

    /* ---------------------------------------------------------- profil --- */

    public function test_pelanggan_dapat_memperbarui_profilnya(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('dashboard.profile.update'), [
                'name' => 'Andi Saputra',
                'email' => 'andi.baru@contoh.test',
                'phone' => '081298765432',
                'city' => 'Cimahi',
                'postal_code' => '40512',
                'address' => 'Jl. Baru No. 5',
            ])->assertSessionHas('status');

        $user->refresh();

        $this->assertSame('Andi Saputra', $user->name);
        $this->assertSame('andi.baru@contoh.test', $user->email);
        $this->assertSame('Cimahi', $user->city);
    }

    public function test_navbar_menampilkan_menu_sesuai_keadaan_masuk(): void
    {
        // Tamu melihat Order Now dan Sign In; pendaftaran tidak lagi dipajang
        // di navbar dan hanya ditawarkan saat hendak membuat penawaran.
        $this->get(route('home'))
            ->assertSee('Order Now')
            ->assertSee('Sign In')
            ->assertDontSee('>Register<', false);

        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertSee('Order Now')
            ->assertSee('Dashboard')
            ->assertSee('Logout')
            ->assertDontSee('Sign In');
    }
}
