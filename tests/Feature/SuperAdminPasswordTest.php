<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Ganti Password Superadmin: tiga kolom dengan ikon mata, validasi per kolom,
 * dan keluar otomatis ke /superadmin/login setelah berhasil.
 */
class SuperAdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const LAMA = 'Lama12345!';

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create([
            'email' => 'superadmin@nusama3d.com',
            'password' => Hash::make(self::LAMA),
        ]);
    }

    public function test_halaman_menampilkan_tiga_kolom_dengan_ikon_mata(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->get('/superadmin/akun/ganti-password')
            ->assertOk()
            ->assertSee('Password Lama')
            ->assertSee('Password Baru')
            ->assertSee('Konfirmasi Password Baru')
            ->assertSee('Ganti Password')
            ->assertSee('Batal')
            ->getContent();

        foreach (['current_password', 'password', 'password_confirmation'] as $field) {
            $this->assertStringContainsString('aria-controls="'.$field.'"', $html);
            $this->assertMatchesRegularExpression('/<input type="password"\s+id="'.$field.'"/', $html);
        }
    }

    public function test_password_lama_harus_sesuai(): void
    {
        $this->actingAs($this->superAdmin())
            ->from('/superadmin/akun/ganti-password')
            ->put(route('superadmin.password.update'), [
                'current_password' => 'Salah12345!',
                'password' => 'Baru12345!',
                'password_confirmation' => 'Baru12345!',
            ])
            ->assertRedirect('/superadmin/akun/ganti-password')
            ->assertSessionHasErrors('current_password');

        $this->assertAuthenticated();
    }

    public function test_password_baru_minimal_delapan_karakter(): void
    {
        $this->actingAs($this->superAdmin())
            ->put(route('superadmin.password.update'), [
                'current_password' => self::LAMA,
                'password' => 'Ab1!',
                'password_confirmation' => 'Ab1!',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_konfirmasi_salah_ditampilkan_di_kolom_konfirmasi(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->put(route('superadmin.password.update'), [
                'current_password' => self::LAMA,
                'password' => 'Baru12345!',
                'password_confirmation' => 'Beda12345!',
            ])
            ->assertSessionHasErrors('password_confirmation');

        $this->assertTrue(Hash::check(self::LAMA, $superAdmin->fresh()->password));
    }

    public function test_berhasil_lalu_keluar_dan_diarahkan_ke_login_superadmin(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->put(route('superadmin.password.update'), [
                'current_password' => self::LAMA,
                'password' => 'Baru12345!',
                'password_confirmation' => 'Baru12345!',
            ])
            ->assertRedirect('/superadmin/login')
            ->assertSessionHas('status', 'Password berhasil diubah. Silakan login kembali.');

        $this->assertGuest();
        $this->assertTrue(Hash::check('Baru12345!', $superAdmin->fresh()->password));

        // Halaman login Superadmin menampilkan pesan suksesnya.
        $this->get('/superadmin/login')
            ->assertOk()
            ->assertSee('Password berhasil diubah. Silakan login kembali.')
            ->assertSee('Dashboard Superadmin');

        // Halaman Superadmin tidak lagi dapat dibuka tanpa masuk ulang.
        $this->get('/superadmin/akun/ganti-password')->assertRedirect();
    }

    public function test_admin_biasa_tetap_seperti_sebelumnya(): void
    {
        $admin = User::factory()->admin()->create(['password' => Hash::make(self::LAMA)]);

        $this->actingAs($admin)
            ->put(route('admin.password.update'), [
                'current_password' => self::LAMA,
                'password' => 'Baru12345!',
                'password_confirmation' => 'Baru12345!',
            ])
            ->assertSessionHas('status', 'Kata sandi berhasil diperbarui.');

        $this->assertAuthenticatedAs($admin);
    }
}
