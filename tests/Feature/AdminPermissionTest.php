<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\ActivityAction;
use App\Support\AdminPermission as P;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Hak akses Admin `<modul>.<aksi>` yang diatur Superadmin per akun: tersimpan
 * di basis data, membentuk sidebar dan tombol, dan diperiksa backend (403)
 * pada setiap route — hak Lihat dan hak tindakan secara terpisah.
 */
class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['name' => 'Syahrul', 'email' => 'superadmin@nusama3d.com']);
    }

    /** @param  array<int, string>  $keys */
    private function adminWith(array $keys, array $attributes = []): User
    {
        return User::factory()->withPermissions($keys)->create(['name' => 'Budi', 'email' => 'budi@nusama3d.com', ...$attributes]);
    }

    private function quotation(string $status = QuotationStatus::REVIEWING): QuotationRequest
    {
        return QuotationRequest::create([
            'tracking_number' => 'QT-PERM01',
            'name' => 'Rani', 'email' => 'rani@contoh.test', 'whatsapp' => '081200000000',
            'quantity' => 1, 'file_name' => 'part.stl', 'file_path' => 'quotations/part.stl',
            'file_format' => 'STL', 'file_size' => 4096,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM', 'material' => 'PLA Plus', 'estimated_minutes' => 60,
            'status' => $status,
        ]);
    }

    /** Label menu sidebar yang tampil. */
    private function sidebar(string $html): array
    {
        $nav = substr($html, strpos($html, '<nav'), strpos($html, '</nav>') - strpos($html, '<nav'));
        preg_match_all('/<span class="sidebar-label flex-1">([^<]+)<\/span>/', $nav, $m);

        return array_map('trim', $m[1]);
    }

    /** @param  array<int, string>  $permissions */
    private function updateAdmin(User $superAdmin, User $admin, array $permissions, bool $active = true)
    {
        return $this->actingAs($superAdmin)->patch(route('superadmin.admins.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'is_active' => $active ? '1' : '0',
            'permissions' => $permissions,
        ]);
    }

    /* ------------------------------------------------------ penyimpanan --- */

    public function test_hak_akses_granular_tersimpan_di_basis_data_saat_admin_dibuat(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.admins.store'), [
                'name' => 'Budi',
                'email' => 'budi@nusama3d.com',
                'password' => 'Rahasia123!',
                'password_confirmation' => 'Rahasia123!',
                'is_active' => '1',
                'permissions' => [P::QUOTATION_VIEW, P::QUOTATION_EDIT, P::PAYMENT_VIEW, P::PROFILE_EDIT],
            ])
            ->assertRedirect(route('superadmin.admins.index'));

        $admin = User::where('email', 'budi@nusama3d.com')->sole();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertEqualsCanonicalizing(
            ['quotation.view', 'quotation.edit', 'payment.view', 'profile.edit'],
            $admin->adminPermissionKeys(),
        );
        $this->assertDatabaseCount('admin_permissions', 4);
        $this->assertDatabaseHas('permissions', ['key' => 'quotation.edit', 'module' => 'quotation', 'action' => 'edit']);
    }

    public function test_tindakan_otomatis_membawa_hak_lihat_modulnya(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.admins.store'), [
                'name' => 'Budi', 'email' => 'budi@nusama3d.com',
                'password' => 'Rahasia123!', 'password_confirmation' => 'Rahasia123!',
                'permissions' => [P::QUOTATION_DELETE, P::PROFILE_SECURITY],
            ]);

        $this->assertEqualsCanonicalizing(
            ['quotation.view', 'quotation.delete', 'profile.security'],
            User::where('email', 'budi@nusama3d.com')->sole()->adminPermissionKeys(),
        );
    }

    public function test_hak_akses_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.admins.store'), [
                'name' => 'Budi', 'email' => 'budi@nusama3d.com',
                'password' => 'Rahasia123!', 'password_confirmation' => 'Rahasia123!',
                'permissions' => ['penawaran', 'quotation.hapus_semua'],
            ])
            ->assertSessionHasErrors(['permissions.0', 'permissions.1']);
    }

    public function test_role_tidak_dapat_dinaikkan_lewat_formulir(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.admins.store'), [
                'name' => 'Budi', 'email' => 'budi@nusama3d.com',
                'password' => 'Rahasia123!', 'password_confirmation' => 'Rahasia123!',
                'role' => User::ROLE_SUPERADMIN,
            ]);

        $this->assertSame(User::ROLE_ADMIN, User::where('email', 'budi@nusama3d.com')->value('role'));
    }

    /* ---------------------------------------------------------- formulir --- */

    public function test_form_menampilkan_switch_per_modul_sesuai_yang_tersimpan(): void
    {
        $superAdmin = $this->superAdmin();

        // Tambah Admin: bawaan Profil › Edit & Ganti Password menyala.
        $html = $this->actingAs($superAdmin)->get(route('superadmin.admins.create'))->assertOk()
            ->assertSee('Hak Akses Admin')
            ->assertSee('Pilih Semua')
            ->assertSee('Nonaktifkan Semua')
            ->assertSee('data-permission-module="quotation"', false)
            ->assertSee('Update Status')
            ->getContent();

        $this->assertMatchesRegularExpression('/value="profile\.edit"\s+class="peer sr-only"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="quotation\.view"\s+class="peer sr-only"\s+checked/', $html);

        // Seluruh hak akses katalog tampil sebagai switch.
        foreach (P::keys() as $key) {
            $this->assertStringContainsString('value="'.$key.'"', $html);
        }

        // Edit Admin: mengikuti basis data.
        $admin = $this->adminWith([P::QUOTATION_VIEW, P::QUOTATION_UPDATE_STATUS]);
        $html = $this->actingAs($superAdmin)->get(route('superadmin.admins.edit', $admin))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="quotation\.view"\s+class="peer sr-only"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/value="quotation\.update_status"\s+class="peer sr-only"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="quotation\.delete"\s+class="peer sr-only"\s+checked/', $html);
    }

    /* ----------------------------------------------------------- sidebar --- */

    public function test_sidebar_hanya_memuat_menu_dengan_hak_lihat(): void
    {
        // Tindakan tanpa hak Lihat (disisipkan langsung ke basis data) tidak
        // memunculkan menu.
        $admin = $this->adminWith([
            P::QUOTATION_VIEW, P::PAYMENT_VIEW, P::PAYMENT_TERM_EDIT, P::USER_VIEW, P::PROFILE_EDIT,
        ]);

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertSame(['Dashboard', 'Penawaran', 'Verifikasi Pembayaran', 'User', 'Profil'], $this->sidebar($html));

        // Lonceng notifikasi ikut hilang tanpa notification.view.
        $this->assertStringNotContainsString('data-notification-bell', $html);
    }

    /* ------------------------------------------------------ otorisasi --- */

    public function test_tanpa_hak_lihat_seluruh_route_modul_ditolak_403(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->adminWith([P::PROFILE_EDIT]));

        $this->get(route('admin.quotations.index'))->assertForbidden();
        $this->get(route('admin.quotations.show', $quotation))->assertForbidden();
        $this->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::REVIEWING])->assertForbidden();
        $this->delete(route('admin.quotations.destroy', $quotation))->assertForbidden();
        $this->get(route('admin.exchange-rate.usd'))->assertForbidden();

        $this->get(route('admin.payments.index'))->assertForbidden();
        $this->post(route('admin.payments.approve', $quotation))->assertForbidden();

        $this->get(route('admin.payment-terms.index'))->assertForbidden();
        $this->get(route('admin.payment-terms.settings.edit'))->assertForbidden();

        $this->get(route('admin.notifications.index'))->assertForbidden();
        $this->getJson(route('admin.notifications.latest'))->assertForbidden();

        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.password.edit'))->assertForbidden();

        // Yang diizinkan tetap terbuka, begitu pula Dashboard.
        $this->get(route('admin.profile.edit'))->assertOk();
        $this->get(route('admin.dashboard'))->assertOk();

        $this->assertDatabaseHas('quotation_requests', ['id' => $quotation->id]);
    }

    public function test_hak_lihat_tanpa_edit_tetap_ditolak_saat_endpoint_dipanggil_manual(): void
    {
        $quotation = $this->quotation();
        $item = $quotation->items()->first();
        $admin = $this->adminWith([P::QUOTATION_VIEW, P::QUOTATION_UPDATE_STATUS]);

        $this->actingAs($admin);

        // ✓ Bisa membuka Penawaran dan melihat datanya.
        $this->get(route('admin.quotations.index'))->assertOk()->assertSee('QT-PERM01');
        $html = $this->get(route('admin.quotations.show', $quotation))->assertOk()->getContent();

        // Tombol mengikuti hak akses: Update Status tampil, Hapus tidak.
        $this->assertStringContainsString(route('admin.quotations.update', $quotation), $html);
        $this->assertStringNotContainsString('Hapus permintaan', $html);

        // ✕ Edit dan Hapus ditolak backend meski dipanggil langsung.
        $this->delete(route('admin.quotations.destroy', $quotation))->assertForbidden();
        $this->get(route('admin.exchange-rate.usd'))->assertForbidden();

        if ($item !== null) {
            $this->patch(route('admin.quotations.items.update', [$quotation, $item]), ['admin_note' => 'x'])->assertForbidden();
        }

        $this->assertDatabaseHas('quotation_requests', ['id' => $quotation->id]);
    }

    public function test_admin_customer_service_dan_admin_finance(): void
    {
        $quotation = $this->quotation(QuotationStatus::REVIEWING);

        // Admin Customer Service: menangani penawaran, tanpa hapus & pembayaran.
        $cs = $this->adminWith(
            [P::QUOTATION_VIEW, P::QUOTATION_CREATE, P::QUOTATION_EDIT, P::QUOTATION_UPDATE_STATUS],
            ['email' => 'cs@nusama3d.com'],
        );

        $this->actingAs($cs);
        $this->get(route('admin.quotations.show', $quotation))->assertOk();
        $this->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::REVIEWING])->assertRedirect();
        $this->delete(route('admin.quotations.destroy', $quotation))->assertForbidden();
        $this->get(route('admin.payments.index'))->assertForbidden();
        $this->post(route('admin.payments.approve', $quotation))->assertForbidden();

        // Admin Finance: hanya melihat penawaran, menangani pembayaran & payment term.
        $finance = $this->adminWith(
            [P::QUOTATION_VIEW, P::PAYMENT_VIEW, P::PAYMENT_VERIFY, P::PAYMENT_TERM_VIEW, P::PAYMENT_TERM_EDIT],
            ['email' => 'finance@nusama3d.com'],
        );

        $this->actingAs($finance);
        $html = $this->get(route('admin.quotations.show', $quotation))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('admin.quotations.update', $quotation), $html);
        $this->assertStringContainsString('Anda tidak memiliki hak akses Update Status', $html);

        $this->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::REVIEWING])->assertForbidden();
        $this->post(route('admin.quotations.cancellation.approve', $quotation))->assertForbidden();

        $this->get(route('admin.payments.index'))->assertOk();
        // Tidak 403: controller yang menolak karena tidak ada bukti menunggu.
        $this->post(route('admin.payments.approve', $quotation))->assertRedirect();
        $this->get(route('admin.payment-terms.index'))->assertOk();
        $this->get(route('admin.payment-terms.settings.edit'))->assertOk();
    }

    public function test_hak_lihat_pembayaran_tanpa_verifikasi_ditolak_saat_memutuskan(): void
    {
        $quotation = $this->quotation(QuotationStatus::PAYMENT_REVIEW);

        $this->actingAs($this->adminWith([P::PAYMENT_VIEW, P::PAYMENT_TERM_VIEW]));

        $this->get(route('admin.payments.index'))->assertOk()
            ->assertDontSee(route('admin.payments.approve', $quotation));
        $this->post(route('admin.payments.approve', $quotation))->assertForbidden();
        $this->post(route('admin.payments.reject', $quotation), ['reason' => 'x'])->assertForbidden();

        $this->get(route('admin.payment-terms.index'))->assertOk()
            ->assertDontSee(route('admin.payment-terms.settings.edit'));
        $this->get(route('admin.payment-terms.settings.edit'))->assertForbidden();
        $this->patch(route('admin.payment-terms.settings.update'))->assertForbidden();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }

    public function test_menu_user_dapat_dibuka_admin_dengan_hak_lihat(): void
    {
        $customer = User::factory()->create(['name' => 'Citra Pelanggan']);

        $this->actingAs($this->adminWith([P::USER_VIEW]));

        $this->get(route('admin.users.index'))->assertOk()
            ->assertSee('Citra Pelanggan')
            ->assertSee(route('admin.users.show', $customer));
        $this->get(route('admin.users.show', $customer))->assertOk();
    }

    public function test_alamat_lama_tetap_diperiksa_setelah_dialihkan(): void
    {
        $this->actingAs($this->adminWith([P::PROFILE_EDIT]))
            ->followingRedirects()
            ->get('/admin/permintaan')
            ->assertForbidden();
    }

    /* ------------------------------------------------ perubahan berlaku --- */

    public function test_perubahan_hak_akses_langsung_berlaku(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->adminWith([P::QUOTATION_VIEW, P::PROFILE_EDIT]);

        $this->actingAs($admin)->get(route('admin.quotations.index'))->assertOk();

        $this->updateAdmin($superAdmin, $admin, [P::PROFILE_EDIT])
            ->assertRedirect(route('superadmin.admins.index'));

        // Model dimuat ulang tiap permintaan; tidak ada cache/session hak akses.
        $fresh = User::find($admin->id);

        $this->actingAs($fresh)->get(route('admin.quotations.index'))->assertForbidden();
        $this->assertNotContains('Penawaran', $this->sidebar($this->actingAs($fresh)->get(route('admin.dashboard'))->getContent()));
    }

    public function test_admin_nonaktif_dikeluarkan_dari_sesi_yang_masih_berjalan(): void
    {
        $admin = $this->adminWith([P::QUOTATION_VIEW], ['is_active' => false]);

        $this->assertFalse($admin->can(P::QUOTATION_VIEW));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /* ------------------------------------------------------- superadmin --- */

    public function test_superadmin_tetap_akses_penuh(): void
    {
        $superAdmin = $this->superAdmin();

        $this->assertSame(0, $superAdmin->permissions()->count());

        foreach (P::keys() as $key) {
            $this->assertTrue(Gate::forUser($superAdmin)->allows($key), $key);
        }

        $this->actingAs($superAdmin);

        $this->get(route('superadmin.quotations.index'))->assertOk();
        $this->get(route('superadmin.payments.index'))->assertOk();
        $this->get(route('superadmin.payment-terms.index'))->assertOk();
        $this->get(route('superadmin.payment-terms.settings.edit'))->assertOk();
        $this->get(route('superadmin.notifications.index'))->assertOk();
        $this->get(route('superadmin.users.index'))->assertOk();
        $this->get(route('superadmin.profile.edit'))->assertOk();
        $this->get(route('superadmin.password.edit'))->assertOk();
    }

    public function test_admin_tidak_dapat_mengelola_akun_maupun_hak_akses_admin(): void
    {
        $admin = $this->adminWith(P::keys());
        $other = $this->adminWith([P::PROFILE_EDIT], ['email' => 'lain@nusama3d.com']);

        $this->actingAs($admin);

        $this->get(route('superadmin.admins.index'))->assertRedirect(route('admin.dashboard'));
        $this->post(route('superadmin.admins.store'), [
            'name' => 'Selundupan', 'email' => 'x@nusama3d.com',
            'password' => 'Rahasia123!', 'password_confirmation' => 'Rahasia123!',
        ])->assertRedirect(route('admin.dashboard'));
        $this->patch(route('superadmin.admins.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'permissions' => [],
        ])->assertRedirect(route('admin.dashboard'));
        $this->delete(route('superadmin.admins.destroy', $other))->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('users', ['email' => 'x@nusama3d.com']);
        $this->assertCount(count(P::keys()), $admin->fresh()->adminPermissionKeys());
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_role_tidak_berubah_dan_pelanggan_tidak_berhak(): void
    {
        $customer = User::factory()->create();

        $this->assertSame(User::ROLE_USER, $customer->role);

        foreach (P::keys() as $key) {
            $this->assertFalse($customer->can($key));
        }
    }

    /* ----------------------------------------------------- activity log --- */

    public function test_perubahan_hak_akses_dan_status_tercatat_di_activity_log(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->adminWith([P::QUOTATION_VIEW, P::QUOTATION_EDIT, P::PROFILE_SECURITY]);

        $this->updateAdmin($superAdmin, $admin, [P::QUOTATION_VIEW, P::PROFILE_SECURITY], active: false);

        $permissionLog = ActivityLog::where('action', ActivityAction::ADMIN_PERMISSION_UPDATE)->sole();

        $this->assertSame($superAdmin->id, $permissionLog->user_id);
        $this->assertSame('Syahrul', $permissionLog->user_name);
        $this->assertSame('budi@nusama3d.com', $permissionLog->subject_label);
        $this->assertSame('ON', $permissionLog->old_values['quotation.view']);
        $this->assertSame('ON', $permissionLog->new_values['quotation.view']);
        $this->assertSame('ON', $permissionLog->old_values['quotation.edit']);
        $this->assertSame('OFF', $permissionLog->new_values['quotation.edit']);
        $this->assertSame('OFF', $permissionLog->new_values['quotation.delete']);
        $this->assertSame('ON', $permissionLog->new_values['profile.security']);

        $statusLog = ActivityLog::where('action', ActivityAction::ADMIN_STATUS_UPDATE)->sole();

        $this->assertSame(['status' => 'Aktif'], $statusLog->old_values);
        $this->assertSame(['status' => 'Nonaktif'], $statusLog->new_values);

        // Halaman detail menampilkan kunci hak akses apa adanya.
        $this->actingAs($superAdmin)
            ->get(route('superadmin.activity-logs.show', $permissionLog))
            ->assertOk()
            ->assertSee('quotation.edit');

        // Tidak ada data rahasia yang ikut tercatat.
        foreach ([$permissionLog, $statusLog] as $log) {
            $this->assertStringNotContainsStringIgnoringCase('password', json_encode([$log->old_values, $log->new_values]));
        }
    }

    public function test_simpan_tanpa_perubahan_tidak_mencatat_hak_akses_maupun_status(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = $this->adminWith([P::QUOTATION_VIEW]);

        $this->updateAdmin($superAdmin, $admin, [P::QUOTATION_VIEW]);

        $this->assertDatabaseMissing('activity_logs', ['action' => ActivityAction::ADMIN_PERMISSION_UPDATE]);
        $this->assertDatabaseMissing('activity_logs', ['action' => ActivityAction::ADMIN_STATUS_UPDATE]);
    }

    /* ----------------------------------------------------------- katalog --- */

    public function test_daftar_hak_akses_bersumber_dari_definisi_backend(): void
    {
        $this->assertSame(P::keys(), Permission::syncDefinitions()->keys()->all());

        foreach (P::keys() as $key) {
            $this->assertMatchesRegularExpression('/^[a-z_]+\.[a-z_]+$/', $key);
            $this->assertTrue(Gate::has($key), $key);
        }
    }
}
