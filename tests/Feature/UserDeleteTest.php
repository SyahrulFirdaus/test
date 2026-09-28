<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\ActivityAction;
use App\Support\AdminPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hapus User pada menu User: khusus Superadmin, dengan konfirmasi, dan
 * dijaga di backend — bukan sekadar tombol yang disembunyikan.
 */
class UserDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    public function test_superadmin_melihat_tombol_hapus_dengan_konfirmasi(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.users.index'))
            ->assertOk()
            ->assertSee(route('superadmin.users.destroy', $customer))
            ->assertSee('data-confirm-title="Hapus User?"', false)
            ->assertSee('data-confirm-accept="Ya, Hapus"', false)
            ->assertSee('data-confirm-cancel="Batal"', false);
    }

    public function test_admin_tidak_melihat_tombol_hapus(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->withPermissions([AdminPermission::USER_VIEW])->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($customer->email)
            ->assertDontSee('Hapus User?')
            ->assertDontSee(route('superadmin.users.destroy', $customer));
    }

    public function test_superadmin_menghapus_user(): void
    {
        $customer = User::factory()->create();
        $index = route('superadmin.users.index', ['q' => $customer->email]);

        $this->actingAs($this->superAdmin())
            ->from($index)
            ->delete(route('superadmin.users.destroy', $customer))
            ->assertRedirect($index)
            ->assertSessionHas('status', 'User berhasil dihapus.');

        $this->assertModelMissing($customer);
        $this->assertDatabaseHas('activity_logs', ['action' => ActivityAction::USER_DELETE]);

        // Daftar tidak lagi memuatnya.
        $this->get(route('superadmin.users.index'))->assertDontSee($customer->email);
    }

    public function test_penawaran_user_tetap_ada_setelah_dihapus(): void
    {
        $customer = User::factory()->create();
        $quotation = QuotationRequest::create([
            'user_id' => $customer->id,
            'tracking_number' => 'QTN-20260928-HAPUS1',
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
            'material' => 'PLA',
            'model_volume_cm3' => 100,
            'estimated_weight_g' => 55.8,
            'estimated_minutes' => 200,
            'estimated_cost' => 150000,
            'status' => \App\Support\QuotationStatus::REVIEWING,
        ]);

        $this->actingAs($this->superAdmin())->delete(route('superadmin.users.destroy', $customer));

        $this->assertModelMissing($customer);
        $this->assertNull($quotation->fresh()->user_id);
    }

    public function test_admin_ditolak_saat_memanggil_endpoint_langsung(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->withPermissions(AdminPermission::keys())->create();

        $this->actingAs($admin)
            ->delete(route('superadmin.users.destroy', $customer))
            ->assertRedirect(route('admin.dashboard'));

        // Alamat versi Admin tidak memiliki aksi hapus sama sekali.
        $this->actingAs($admin)
            ->delete('/admin/akun/user/'.$customer->id)
            ->assertStatus(405);

        $this->assertModelExists($customer);
    }

    public function test_pelanggan_dan_tamu_ditolak(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->delete(route('superadmin.users.destroy', $customer));
        $this->assertModelExists($customer);

        auth()->logout();
        $this->delete(route('superadmin.users.destroy', $customer))->assertRedirect(route('admin.login'));
        $this->assertModelExists($customer);
    }

    public function test_akun_admin_dan_superadmin_tidak_dapat_dihapus_lewat_menu_user(): void
    {
        $superAdmin = $this->superAdmin();
        $admin = User::factory()->admin()->create();

        $this->actingAs($superAdmin)->delete(route('superadmin.users.destroy', $admin))->assertNotFound();
        $this->actingAs($superAdmin)->delete(route('superadmin.users.destroy', $superAdmin))->assertNotFound();

        $this->assertModelExists($admin);
        $this->assertModelExists($superAdmin);
    }
}
