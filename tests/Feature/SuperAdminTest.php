<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\ActivityAction;
use App\Support\ActorType;
use App\Support\QuotationStatus;
use Database\Seeders\SuperAdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Role Superadmin: dashboard, pembagian hak akses, dan pengelolaan akun Admin.
 *
 * Yang dijaga di sini bukan sekadar "menunya muncul", melainkan bahwa URL-nya
 * benar-benar tertutup — menyembunyikan menu dari sidebar tidak menghalangi
 * siapa pun mengetik alamatnya sendiri.
 */
class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create([
            'name' => 'Superadmin',
            'email' => 'superadmin@nusama3d.com',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create([
            'name' => 'Admin 1',
            'email' => 'admin@nusama3d.com',
        ]);
    }

    /* ----------------------------------------------------- akun bawaan --- */

    public function test_seeder_membuat_akun_superadmin_dengan_password_terhash(): void
    {
        $this->seed(SuperAdminUserSeeder::class);

        $superAdmin = User::where('email', 'superadmin@nusama3d.com')->sole();

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isActive());

        // Kata sandinya tersimpan sebagai hash, bukan teks biasa, dan tetap
        // cocok dengan kata sandi awal yang disepakati.
        $this->assertNotSame('superadminnusama', $superAdmin->password);
        $this->assertTrue(Hash::check('superadminnusama', $superAdmin->password));
    }

    public function test_menjalankan_seeder_dua_kali_tidak_menimpa_password_yang_sudah_diganti(): void
    {
        $this->seed(SuperAdminUserSeeder::class);

        $superAdmin = User::where('email', 'superadmin@nusama3d.com')->sole();
        $superAdmin->update(['password' => 'KataSandiBaru9!']);

        $this->seed(SuperAdminUserSeeder::class);

        $this->assertTrue(Hash::check('KataSandiBaru9!', $superAdmin->fresh()->password));
    }

    /* ------------------------------------------------------------ login --- */

    public function test_superadmin_mendarat_di_dashboardnya_sendiri(): void
    {
        $this->seed(SuperAdminUserSeeder::class);

        $this->post(route('admin.login.store'), [
            'email' => 'superadmin@nusama3d.com',
            'password' => 'superadminnusama',
        ])->assertRedirect(route('superadmin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_tetap_mendarat_di_dashboard_admin(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@nusama3d.com',
            'password' => Hash::make('AdminRahasia9!'),
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@nusama3d.com',
            'password' => 'AdminRahasia9!',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_nonaktif_tidak_dapat_masuk(): void
    {
        User::factory()->admin()->create([
            'email' => 'cuti@nusama3d.com',
            'password' => Hash::make('AdminRahasia9!'),
            'is_active' => false,
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'cuti@nusama3d.com',
            'password' => 'AdminRahasia9!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /* ------------------------------------------------------ hak akses --- */

    /** @return array<int, string> */
    public static function superAdminRoutes(): array
    {
        return [
            'dashboard' => ['superadmin.dashboard'],
            'price list' => ['superadmin.price-list.index'],
            'user' => ['superadmin.users.index'],
            'activity log' => ['superadmin.activity-logs.index'],
            'akun admin' => ['superadmin.admins.index'],

            // Menu operasional pun punya alamat sendiri di wilayah Superadmin,
            // dan alamat itu tetap tertutup bagi Admin biasa.
            'penawaran' => ['superadmin.quotations.index'],
            'verifikasi pembayaran' => ['superadmin.payments.index'],
            'payment term' => ['superadmin.payment-terms.index'],
            'notifikasi' => ['superadmin.notifications.index'],
            'profil' => ['superadmin.profile.edit'],
            'ganti password' => ['superadmin.password.edit'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('superAdminRoutes')]
    public function test_admin_biasa_ditolak_di_seluruh_halaman_superadmin(string $route): void
    {
        $this->actingAs($this->admin())
            ->get(route($route))
            ->assertRedirect(route('admin.dashboard'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('superAdminRoutes')]
    public function test_pelanggan_ditolak_di_seluruh_halaman_superadmin(string $route): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route($route))
            ->assertRedirect(route('dashboard'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('superAdminRoutes')]
    public function test_tamu_diarahkan_ke_halaman_masuk_admin(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('admin.login'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('superAdminRoutes')]
    public function test_superadmin_berhak_atas_seluruh_halamannya(string $route): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route($route))
            ->assertOk();
    }

    public function test_superadmin_tetap_berhak_atas_halaman_operasional_admin(): void
    {
        $superAdmin = $this->superAdmin();

        foreach (['admin.dashboard', 'admin.quotations.index', 'admin.payments.index', 'admin.payment-terms.index'] as $route) {
            $this->actingAs($superAdmin)->get(route($route))->assertOk();
        }
    }

    /** Menulis langsung ke URL pun tertolak, bukan hanya menunya yang hilang. */
    public function test_admin_tidak_dapat_mengubah_price_list_lewat_url(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('superadmin.price-list.harga.update', 'FDM'), ['profit_percent' => 99])
            ->assertRedirect(route('admin.dashboard'));
    }

    /* ---------------------------------------------------------- sidebar --- */

    public function test_sidebar_admin_tidak_lagi_memuat_menu_yang_pindah(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        $response->assertDontSee(route('superadmin.price-list.index'))
            ->assertDontSee(route('superadmin.users.index'))
            ->assertDontSee(route('superadmin.activity-logs.index'))
            ->assertDontSee(route('superadmin.admins.index'));

        // Yang tetap menjadi haknya masih ada.
        $response->assertSee(route('admin.quotations.index'))
            ->assertSee(route('admin.payments.index'))
            ->assertSee(route('admin.payment-terms.index'))
            ->assertSee(route('admin.password.edit'));
    }

    public function test_sidebar_superadmin_memuat_seluruh_menunya(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('superadmin.dashboard'))->assertOk();

        // Seluruh menunya menunjuk wilayah /superadmin, termasuk yang dipakai
        // bersama Admin — Superadmin tidak pernah terlempar ke /admin.
        foreach ([
            'superadmin.dashboard', 'superadmin.quotations.index', 'superadmin.payments.index',
            'superadmin.payment-terms.index', 'superadmin.price-list.index', 'superadmin.users.index',
            'superadmin.activity-logs.index', 'superadmin.notifications.index', 'superadmin.profile.edit',
            'superadmin.password.edit', 'superadmin.admins.index',
        ] as $route) {
            $response->assertSee(route($route));
        }

        foreach (['admin.quotations.index', 'admin.payments.index', 'admin.payment-terms.index', 'admin.password.edit'] as $route) {
            $response->assertDontSee(route($route));
        }
    }

    /* -------------------------------------------------------- dashboard --- */

    public function test_dashboard_superadmin_memakai_angka_dari_basis_data(): void
    {
        $customer = User::factory()->create();

        QuotationRequest::create([
            'user_id' => $customer->id,
            'tracking_number' => 'QT-SA0001',
            'name' => $customer->name,
            'email' => $customer->email,
            'whatsapp' => '081234567890',
            'quantity' => 2,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'estimated_minutes' => 120,
            'estimated_cost' => 500000,
            'estimated_price' => 500000,
            'status' => QuotationStatus::COMPLETED,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Statistik 12 Bulan Terakhir')
            ->assertSee('Total Penawaran')
            ->assertSee('Total Pendapatan Kotor')
            ->assertSee('Total User')
            ->assertSee('Rp500.000', false);
    }

    /**
     * Profit Bersih dibaca dari komponen Profit yang tersimpan pada tiap
     * penawaran, bukan dihitung ulang dengan rumus tersendiri.
     */
    public function test_profit_bersih_menjumlahkan_komponen_profit_penawaran_selesai(): void
    {
        $selesai = $this->quotationSelesai('QT-PB0001', 500000, ['subtotal' => 400000, 'profit' => 100000, 'basic_fee' => 0]);
        $this->quotationSelesai('QT-PB0002', 300000, ['subtotal' => 240000, 'profit' => 60000, 'basic_fee' => 0]);

        // Yang belum selesai tidak ikut dihitung — profitnya belum tertagih.
        $belum = $this->quotationSelesai('QT-PB0003', 900000, ['subtotal' => 700000, 'profit' => 200000, 'basic_fee' => 0]);
        $belum->update(['status' => QuotationStatus::REVIEWING]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.dashboard'))
            ->assertOk();

        // 100.000 + 60.000, bukan 360.000.
        $response->assertSee('Profit Bersih')
            ->assertSee('Rp160.000', false)
            ->assertDontSee('Rp360.000', false);

        // Pendapatan kotornya tetap penjumlahan Harga Jual, bukan profitnya.
        $response->assertSee('Rp800.000', false);

        // Marginnya dihitung dari keduanya: 160.000 / 800.000 = 20%.
        $response->assertSee('margin 20%');

        $this->assertSame(100000.0, (float) $selesai->cost_breakdown['profit']);
    }

    public function test_penawaran_lama_tanpa_rincian_tidak_menyumbang_profit(): void
    {
        // Penawaran sebelum rumus Price List berlaku: tidak ada cost_breakdown,
        // jadi profitnya tidak diketahui dan tidak boleh ditaksir.
        $this->quotationSelesai('QT-PB0004', 600000, null);

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Rp600.000', false)
            ->assertSee('margin 0%');
    }

    /** @param array<string, mixed>|null $breakdown */
    private function quotationSelesai(string $tracking, float $harga, ?array $breakdown): QuotationRequest
    {
        $customer = User::factory()->create();

        return QuotationRequest::create([
            'user_id' => $customer->id,
            'tracking_number' => $tracking,
            'name' => $customer->name,
            'email' => $customer->email,
            'whatsapp' => '081234567890',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'estimated_minutes' => 120,
            'estimated_cost' => $harga,
            'estimated_price' => $harga,
            'cost_breakdown' => $breakdown,
            'status' => QuotationStatus::COMPLETED,
        ]);
    }

    public function test_dashboard_admin_tidak_lagi_memuat_statistik_dua_belas_bulan(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Statistik 12 Bulan Terakhir')
            // Yang memang dipakai bekerja tetap ada.
            ->assertSee('Sebaran Status')
            ->assertSee('Penawaran Terbaru');
    }

    /* ------------------------------------------------------ akun admin --- */

    public function test_superadmin_membuat_akun_admin_yang_langsung_dapat_masuk(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.admins.store'), [
                'name' => 'Admin Produksi',
                'email' => 'produksi@nusama3d.com',
                'password' => 'AdminBaru9!',
                'password_confirmation' => 'AdminBaru9!',
                'is_active' => '1',
            ])
            ->assertRedirect(route('superadmin.admins.index'));

        $admin = User::where('email', 'produksi@nusama3d.com')->sole();

        $this->assertTrue($admin->isPlainAdmin());
        $this->assertTrue($admin->isActive());
        $this->assertTrue(Hash::check('AdminBaru9!', $admin->password));

        // Akunnya benar-benar dapat dipakai masuk ke Dashboard Admin.
        $this->post(route('admin.logout'));
        $this->post(route('admin.login.store'), [
            'email' => 'produksi@nusama3d.com',
            'password' => 'AdminBaru9!',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_daftar_akun_admin_menampilkan_nama_email_dan_status(): void
    {
        $this->admin();
        User::factory()->admin()->create(['name' => 'Admin Cuti', 'email' => 'cuti@nusama3d.com', 'is_active' => false]);

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.admins.index'))
            ->assertOk()
            ->assertSee('Admin 1')
            ->assertSee('admin@nusama3d.com')
            ->assertSee('Aktif')
            ->assertSee('Admin Cuti')
            ->assertSee('Nonaktif');
    }

    public function test_edit_admin_tanpa_mengisi_password_tidak_menggantinya(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin Lama',
            'email' => 'lama@nusama3d.com',
            'password' => Hash::make('AdminRahasia9!'),
        ]);

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.admins.update', $admin), [
                'name' => 'Admin Baru',
                'email' => 'lama@nusama3d.com',
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('superadmin.admins.index'));

        $admin->refresh();

        $this->assertSame('Admin Baru', $admin->name);
        $this->assertTrue(Hash::check('AdminRahasia9!', $admin->password));
    }

    public function test_menonaktifkan_admin_mencabut_aksesnya(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'lama@nusama3d.com',
            'password' => Hash::make('AdminRahasia9!'),
        ]);

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.admins.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => '0',
            ])
            ->assertRedirect(route('superadmin.admins.index'));

        $this->assertFalse($admin->fresh()->isActive());
    }

    public function test_menghapus_akun_admin(): void
    {
        $admin = $this->admin();

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.admins.destroy', $admin))
            ->assertRedirect(route('superadmin.admins.index'));

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }

    /** Akun Superadmin dan pelanggan berada di luar jangkauan menu ini. */
    public function test_menu_akun_admin_hanya_menyentuh_akun_admin(): void
    {
        $superAdmin = $this->superAdmin();
        $customer = User::factory()->create();
        $lain = User::factory()->superAdmin()->create(['email' => 'lain@nusama3d.com']);

        $this->actingAs($superAdmin)->get(route('superadmin.admins.edit', $customer))->assertNotFound();
        $this->actingAs($superAdmin)->get(route('superadmin.admins.edit', $lain))->assertNotFound();
        $this->actingAs($superAdmin)->delete(route('superadmin.admins.destroy', $lain))->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $lain->id]);
    }

    public function test_admin_biasa_tidak_dapat_membuat_akun_admin(): void
    {
        $this->actingAs($this->admin())
            ->post(route('superadmin.admins.store'), [
                'name' => 'Admin Selundupan',
                'email' => 'selundupan@nusama3d.com',
                'password' => 'AdminBaru9!',
                'password_confirmation' => 'AdminBaru9!',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('users', ['email' => 'selundupan@nusama3d.com']);
    }

    /* ----------------------------------------------------- activity log --- */

    public function test_aktivitas_superadmin_tercatat(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post(route('superadmin.admins.store'), [
            'name' => 'Admin Produksi',
            'email' => 'produksi@nusama3d.com',
            'password' => 'AdminBaru9!',
            'password_confirmation' => 'AdminBaru9!',
            'is_active' => '1',
        ]);

        $log = ActivityLog::where('action', ActivityAction::ADMIN_CREATE)->sole();

        $this->assertSame($superAdmin->id, $log->user_id);
        $this->assertSame(ActorType::SUPERADMIN, $log->user_type);
        $this->assertSame('produksi@nusama3d.com', $log->subject_label);

        // Kata sandi tidak pernah ikut tersimpan pada jejak audit.
        $this->assertArrayNotHasKey('password', (array) $log->new_values);
        $this->assertSame(['name', 'email', 'is_active'], array_keys((array) $log->new_values));
    }

    public function test_perubahan_price_list_tercatat_sebagai_aktivitas_superadmin(): void
    {
        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.price-list.harga.update', 'FDM'), [
                'machine_time_hours' => 8,
                'machine_cost' => 61000,
                'material_qty_g' => 100,
                'material_price_per_g' => 500,
                'risk_percent' => 10,
                'packaging_cost' => 5000,
                'overtime_cost' => 0,
                'profit_percent' => 25,
                'object_size_mm' => 100,
            ])->assertRedirect();

        $log = ActivityLog::where('action', ActivityAction::PRICE_LIST_UPDATE)->first();

        $this->assertNotNull($log);
        $this->assertSame(ActorType::SUPERADMIN, $log->user_type);
    }

    public function test_ganti_password_superadmin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'password' => Hash::make('SandiLama9!'),
        ]);

        $this->actingAs($superAdmin)
            ->put(route('admin.password.update'), [
                'current_password' => 'SandiLama9!',
                'password' => 'SandiBaru9!',
                'password_confirmation' => 'SandiBaru9!',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('SandiBaru9!', $superAdmin->fresh()->password));
    }

    public function test_password_lama_yang_salah_ditolak(): void
    {
        $superAdmin = User::factory()->superAdmin()->create([
            'password' => Hash::make('SandiLama9!'),
        ]);

        $this->actingAs($superAdmin)
            ->put(route('admin.password.update'), [
                'current_password' => 'SalahTotal9!',
                'password' => 'SandiBaru9!',
                'password_confirmation' => 'SandiBaru9!',
            ])
            ->assertSessionHasErrors();

        $this->assertTrue(Hash::check('SandiLama9!', $superAdmin->fresh()->password));
    }
}
