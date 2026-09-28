<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\AdminPermission;
use App\Support\CustomerType;
use App\Support\DashboardMode;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Switch Personal/Business pada header dashboard pengelola.
 *
 * Yang diubahnya hanya TAMPILAN: segmen pelanggan yang diringkas dashboard
 * beserta menu pembayaran yang relevan baginya. Role dan hak akses tidak ikut
 * berubah — itulah yang dijaga paling ketat di sini, karena switch yang diam-diam
 * memperluas akses adalah lubang keamanan, bukan sekadar salah tampilan.
 */
class DashboardModeTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------ mode --- */

    public function test_mode_bawaan_personal(): void
    {
        $this->assertSame(DashboardMode::PERSONAL, DashboardMode::current());
        $this->assertFalse(DashboardMode::isBusiness());
        $this->assertSame(CustomerType::PERSONAL, DashboardMode::customerType());
    }

    public function test_switch_menyimpan_pilihannya(): void
    {
        $this->actingAs($this->superAdmin())
            ->put(route('superadmin.dashboard.mode'), ['mode' => DashboardMode::BUSINESS])
            ->assertRedirect();

        $this->assertSame(DashboardMode::BUSINESS, session('dashboard_mode'));
    }

    /** Pilihan bertahan saat halaman dimuat ulang. */
    public function test_mode_bertahan_setelah_halaman_dimuat_ulang(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('superadmin.dashboard.mode'), ['mode' => DashboardMode::BUSINESS]);

        $this->actingAs($admin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Business');
    }

    public function test_mode_tak_dikenal_ditolak(): void
    {
        $this->actingAs($this->superAdmin())
            ->put(route('superadmin.dashboard.mode'), ['mode' => 'enterprise'])
            ->assertSessionHasErrors('mode');
    }

    /* -------------------------------------------------------- sidebar --- */

    /**
     * Struktur menu yang diminta.
     *
     * Personal → "Pembayaran Personal" berisi Verifikasi Pembayaran.
     * Business → "Pembayaran" berisi Payment Term.
     */
    public function test_sidebar_personal_hanya_verifikasi_pembayaran(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Pembayaran Personal')
            ->assertSee('Verifikasi Pembayaran')
            ->assertDontSee('Payment Term');
    }

    public function test_sidebar_business_hanya_payment_term(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('superadmin.dashboard.mode'), ['mode' => DashboardMode::BUSINESS]);

        $this->actingAs($admin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Payment Term')
            ->assertDontSee('Pembayaran Personal')
            ->assertDontSee('Verifikasi Pembayaran');
    }

    /* ------------------------------------------------- angka dashboard --- */

    /**
     * Angka dashboard ikut tersaring segmen pelanggannya.
     *
     * Inilah yang membuat kedua mode benar-benar berbeda, bukan sekadar
     * berganti menu.
     */
    public function test_angka_dashboard_hanya_segmen_yang_dipilih(): void
    {
        $this->quotation(CustomerType::PERSONAL);
        $this->quotation(CustomerType::BUSINESS);
        $this->quotation(CustomerType::BUSINESS);

        $admin = $this->superAdmin();

        $personal = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(1, $personal->viewData('summary')['quotations']);
        $this->assertSame(1, $personal->viewData('summary')['users']);

        $this->actingAs($admin)->put(route('admin.dashboard.mode'), ['mode' => DashboardMode::BUSINESS]);

        $business = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertSame(2, $business->viewData('summary')['quotations']);
        $this->assertSame(2, $business->viewData('summary')['users']);
    }

    /* ------------------------------------------------------- keamanan --- */

    /**
     * Switch TIDAK mengubah hak akses.
     *
     * Admin yang tidak berhak atas Payment Term tetap ditolak di mode Business,
     * dan menunya tetap tidak muncul. Kalau tidak, switch tampilan berubah
     * menjadi jalan pintas menaikkan akses.
     */
    public function test_switch_tidak_menaikkan_hak_akses(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->permissions()->detach();

        $this->actingAs($admin)->put(route('admin.dashboard.mode'), ['mode' => DashboardMode::BUSINESS]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Payment Term');

        $this->actingAs($admin)
            ->get(route('admin.payment-terms.index'))
            ->assertForbidden();
    }

    /** Role akunnya sendiri tidak ikut berubah. */
    public function test_switch_tidak_mengubah_role(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('superadmin.dashboard.mode'), ['mode' => DashboardMode::BUSINESS]);

        $this->assertTrue($admin->fresh()->isSuperAdmin());
        $this->assertSame(User::ROLE_SUPERADMIN, $admin->fresh()->role);
    }

    public function test_tamu_tidak_dapat_mengubah_mode(): void
    {
        $this->put(route('admin.dashboard.mode'), ['mode' => DashboardMode::BUSINESS])
            ->assertRedirect();

        $this->assertNull(session('dashboard_mode'));
    }

    /* -------------------------------------------------------- bantuan --- */

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    /** Satu penawaran selesai milik pelanggan bertipe tertentu. */
    private function quotation(string $customerType): QuotationRequest
    {
        $user = User::factory()->create([
            'customer_type' => $customerType,
            'role' => User::ROLE_USER,
        ]);

        return QuotationRequest::create([
            'user_id' => $user->id,
            'tracking_number' => 'QT-'.strtoupper(fake()->bothify('?????')),
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => '081234567890',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-09/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Basic ESUN',
            'estimated_price' => 250000,
            'status' => QuotationStatus::COMPLETED,
        ]);
    }
}
