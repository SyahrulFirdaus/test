<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\ActivityStatus;
use App\Support\ActorType;
use App\Support\CustomerType;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Activity Log / Audit Trail pada dashboard admin.
 *
 * Yang diuji di sini bukan hanya "lognya tersimpan", melainkan tiga hal yang
 * membuat jejak audit dapat dipercaya: perubahan data terekam beserta nilai
 * sebelum dan sesudahnya, kolom rahasia tidak pernah ikut tersimpan, dan
 * halamannya tertutup bagi siapa pun selain admin.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Administrator']);
        $this->customer = User::factory()->create([
            'name' => 'David',
            'customer_type' => CustomerType::BUSINESS,
        ]);
    }

    private function quotation(array $overrides = []): QuotationRequest
    {
        return QuotationRequest::create(array_merge([
            'user_id' => $this->customer->id,
            'tracking_number' => 'QTN-20260820-'.strtoupper(fake()->bothify('??####')),
            'name' => $this->customer->name,
            'email' => $this->customer->email,
            'whatsapp' => '081234567890',
            'quantity' => 1,
            'file_name' => 'kitchenbox.stl',
            'file_path' => 'quotations/2026-08/kitchenbox.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA',
            'model_volume_cm3' => 100,
            'estimated_weight_g' => 55.8,
            'estimated_minutes' => 200,
            'estimated_cost' => 150000,
            'status' => QuotationStatus::RECEIVED,
        ], $overrides));
    }

    /* --------------------------------------------------------- pencatatan --- */

    public function test_login_berhasil_dan_gagal_sama_sama_tercatat(): void
    {
        $this->post(route('login.store'), [
            'email' => $this->customer->email,
            'password' => 'password',
        ]);

        $success = ActivityLog::where('action', ActivityAction::LOGIN)->first();

        $this->assertNotNull($success);
        $this->assertSame(ActivityStatus::SUCCESS, $success->status);
        $this->assertSame(ActorType::BUSINESS, $success->user_type);
        $this->assertSame($this->customer->id, $success->user_id);
        $this->assertSame(ActivityModule::ACCOUNT, $success->module);

        $this->post('/logout');

        $this->post(route('login.store'), [
            'email' => $this->customer->email,
            'password' => 'kata-sandi-salah',
        ]);

        $failed = ActivityLog::where('action', ActivityAction::LOGIN_FAILED)->first();

        $this->assertNotNull($failed);
        $this->assertSame(ActivityStatus::FAILED, $failed->status);
        $this->assertNull($failed->user_id);
    }

    public function test_logout_tercatat_beserta_pelakunya(): void
    {
        $this->actingAs($this->customer)->post('/logout');

        $log = ActivityLog::where('action', ActivityAction::LOGOUT)->first();

        $this->assertNotNull($log);
        $this->assertSame($this->customer->id, $log->user_id);
    }

    public function test_perubahan_profil_menyimpan_nilai_sebelum_dan_sesudah(): void
    {
        $this->actingAs($this->customer)->patch(route('dashboard.profile.update'), [
            'name' => 'David Wijaya',
            'email' => $this->customer->email,
            'phone' => '081200001111',
            'city' => 'Bandung',
            'postal_code' => '40123',
            'address' => 'Jl. Merdeka 10',
        ]);

        $log = ActivityLog::where('action', ActivityAction::PROFILE_UPDATE)->first();

        $this->assertNotNull($log);
        $this->assertSame('David', $log->old_values['name']);
        $this->assertSame('David Wijaya', $log->new_values['name']);

        // Kolom yang tidak berubah tidak ikut memenuhi perbandingannya.
        $this->assertArrayNotHasKey('email', $log->new_values);
    }

    public function test_penyimpanan_tanpa_perubahan_tidak_membuat_baris_log(): void
    {
        app(ActivityLogger::class)->logChanges(
            action: ActivityAction::PROFILE_UPDATE,
            before: ['name' => 'David', 'city' => 'Bandung'],
            after: ['name' => 'David', 'city' => 'Bandung'],
            actor: $this->customer,
        );

        $this->assertSame(0, ActivityLog::count());
    }

    public function test_perubahan_status_penawaran_oleh_admin_tercatat(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->admin)->patch(route('admin.quotations.update', $quotation), [
            'status' => QuotationStatus::REVIEWING,
        ]);

        $log = ActivityLog::where('action', ActivityAction::QUOTATION_STATUS_UPDATE)->first();

        $this->assertNotNull($log);
        $this->assertSame(ActorType::ADMIN, $log->user_type);
        $this->assertSame(QuotationStatus::label(QuotationStatus::RECEIVED), $log->old_values['status']);
        $this->assertSame(QuotationStatus::label(QuotationStatus::REVIEWING), $log->new_values['status']);
        $this->assertSame($quotation->tracking_number, $log->subject_label);
    }

    public function test_membuka_detail_penawaran_hanya_dicatat_sekali_dalam_rentang_waktu(): void
    {
        $quotation = $this->quotation();

        $this->actingAs($this->customer)->get(route('dashboard.quotations.show', $quotation));
        $this->actingAs($this->customer)->get(route('dashboard.quotations.show', $quotation));

        // Menyegarkan halaman tidak boleh menambah baris baru; tanpa jeda ini
        // tabel log akan penuh oleh aktivitas membaca.
        $this->assertSame(1, ActivityLog::where('action', ActivityAction::QUOTATION_VIEW)->count());
    }

    /* ------------------------------------------------------------ keamanan --- */

    public function test_kolom_rahasia_tidak_pernah_ikut_tersimpan(): void
    {
        app(ActivityLogger::class)->log(
            action: ActivityAction::PROFILE_UPDATE,
            new: [
                'name' => 'David',
                'password' => 'rahasia-sekali',
                'password_confirmation' => 'rahasia-sekali',
                'api_token' => 'abcd1234',
                'secret_key' => 'xyz',
                'nested' => ['remember_token' => 'zzz', 'city' => 'Bandung'],
            ],
            actor: $this->customer,
        );

        $log = ActivityLog::firstOrFail();

        $this->assertSame(['name' => 'David', 'nested' => ['city' => 'Bandung']], $log->new_values);

        // Terbaca juga dari kolom mentahnya, bukan hanya setelah cast.
        $this->assertStringNotContainsString('rahasia-sekali', $log->getRawOriginal('new_values'));
        $this->assertStringNotContainsString('abcd1234', $log->getRawOriginal('new_values'));
    }

    public function test_ganti_password_tercatat_tanpa_menyimpan_kata_sandinya(): void
    {
        $this->actingAs($this->customer)->put(route('dashboard.password.update'), [
            'current_password' => 'password',
            'password' => 'KataSandiBaru#2026',
            'password_confirmation' => 'KataSandiBaru#2026',
        ]);

        $log = ActivityLog::where('action', ActivityAction::PASSWORD_UPDATE)->first();

        $this->assertNotNull($log);
        $this->assertNull($log->old_values);
        $this->assertNull($log->new_values);
    }

    public function test_kegagalan_pencatatan_tidak_menggagalkan_aktivitas_pengguna(): void
    {
        // Tabel lognya sengaja dihilangkan: aktivitas pengguna tetap harus
        // berhasil, karena Activity Log adalah pengamat — bukan bagian alur.
        \Illuminate\Support\Facades\Schema::drop('activity_logs');

        $this->actingAs($this->customer)
            ->patch(route('dashboard.profile.update'), [
                'name' => 'David Wijaya',
                'email' => $this->customer->email,
                'phone' => '081200001111',
                'city' => 'Bandung',
                'postal_code' => '40123',
                'address' => 'Jl. Merdeka 10',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('David Wijaya', $this->customer->fresh()->name);
    }

    /* ------------------------------------------------------- halaman admin --- */

    public function test_halaman_activity_logs_tertutup_bagi_selain_admin(): void
    {
        // Pengunjung tanpa sesi diarahkan ke halaman masuk admin. Diperiksa
        // lebih dulu karena actingAs() di bawah berlaku sampai akhir pengujian.
        $this->get(route('admin.activity-logs.index'))->assertRedirect(route('admin.login'));

        // Pelanggan yang sudah masuk dikembalikan ke dashboardnya sendiri,
        // mengikuti perlakuan seluruh halaman admin lainnya.
        $this->actingAs($this->customer)
            ->get(route('admin.activity-logs.index'))
            ->assertRedirect(route('dashboard'));

        app(ActivityLogger::class)->log(action: ActivityAction::LOGIN, actor: $this->customer);

        $this->actingAs($this->customer)
            ->get(route('admin.activity-logs.show', ActivityLog::firstOrFail()))
            ->assertRedirect(route('dashboard'));
    }

    public function test_halaman_activity_logs_menampilkan_kolom_yang_diminta(): void
    {
        app(ActivityLogger::class)->log(
            action: ActivityAction::MODEL_UPLOAD,
            description: 'Menambahkan model kitchenbox.stl.',
            actor: $this->customer,
        );

        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('Tanggal &amp; Waktu', false)
            ->assertSee('Tipe Akun')
            ->assertSee('IP Address')
            ->assertSee('Upload Model')
            ->assertSee('3D Models')
            ->assertSee('David');
    }

    public function test_penyaring_menyaring_menurut_tipe_akun_module_dan_status(): void
    {
        $logger = app(ActivityLogger::class);

        /*
         * Yang diperiksa adalah isi barisnya — deskripsi dan nama pelaku —
         * bukan label aktivitasnya: daftar label lengkap juga tercetak pada
         * <datalist> penyaring, sehingga selalu ada di halaman.
         */
        $logger->log(
            action: ActivityAction::MODEL_UPLOAD,
            description: 'Menambahkan berkas kitchenbox.',
            actor: $this->customer,
        );
        $logger->log(
            action: ActivityAction::LOGIN,
            description: 'Admin masuk dashboard.',
            actor: $this->admin,
        );
        $logger->logFailure(
            action: ActivityAction::LOGIN_FAILED,
            description: 'Percobaan masuk ditolak.',
            userName: 'penyusup@example.com',
        );

        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.index', ['type' => ActorType::ADMIN]))
            ->assertOk()
            ->assertSee('Admin masuk dashboard.')
            ->assertDontSee('Menambahkan berkas kitchenbox.');

        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.index', ['module' => ActivityModule::MODELS]))
            ->assertOk()
            ->assertSee('Menambahkan berkas kitchenbox.')
            ->assertDontSee('penyusup@example.com');

        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.index', ['status' => ActivityStatus::FAILED]))
            ->assertOk()
            ->assertSee('penyusup@example.com')
            ->assertDontSee('Menambahkan berkas kitchenbox.');
    }

    public function test_penyaring_aktivitas_mengenali_label_yang_diketik_admin(): void
    {
        app(ActivityLogger::class)->log(
            action: ActivityAction::SPEC_UPDATE,
            description: 'Mengubah material dan jumlah.',
            actor: $this->customer,
        );
        app(ActivityLogger::class)->log(
            action: ActivityAction::LOGIN,
            description: 'Masuk ke akun.',
            actor: $this->customer,
        );

        // Yang tersimpan adalah kunci `spec_update`, sedangkan admin mengetik
        // labelnya seperti yang terbaca pada tabel.
        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.index', ['activity' => 'Edit Specification']))
            ->assertOk()
            ->assertSee('Mengubah material dan jumlah.')
            ->assertDontSee('Masuk ke akun.');
    }

    public function test_penyaring_tanggal_membatasi_rentangnya(): void
    {
        $lama = app(ActivityLogger::class)->log(
            action: ActivityAction::LOGIN,
            description: 'Aktivitas bulan lalu.',
            actor: $this->customer,
        );
        $lama->forceFill(['created_at' => now()->subMonth()])->save();

        app(ActivityLogger::class)->log(
            action: ActivityAction::MODEL_UPLOAD,
            description: 'Aktivitas hari ini.',
            actor: $this->customer,
        );

        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.index', ['from' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Aktivitas hari ini.')
            ->assertDontSee('Aktivitas bulan lalu.');

        // Batas atas juga berlaku, dan keduanya boleh dipakai bersamaan.
        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.index', ['to' => now()->subWeek()->toDateString()]))
            ->assertOk()
            ->assertSee('Aktivitas bulan lalu.')
            ->assertDontSee('Aktivitas hari ini.');
    }

    public function test_halaman_detail_menampilkan_perbandingan_before_dan_after(): void
    {
        app(ActivityLogger::class)->log(
            action: ActivityAction::SPEC_UPDATE,
            description: 'Mengubah spesifikasi model kitchenbox.stl.',
            old: ['material' => 'PLA', 'quantity' => 5],
            new: ['material' => 'PETG High Speed ESUN', 'quantity' => 10],
            actor: $this->customer,
            subjectLabel: 'kitchenbox.stl',
        );

        $log = ActivityLog::firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.show', $log))
            ->assertOk()
            ->assertSee('Edit Specification')
            ->assertSee('Business (B2B)')
            ->assertSee('kitchenbox.stl')
            ->assertSee('PLA')
            ->assertSee('PETG High Speed ESUN')
            ->assertSee('Before')
            ->assertSee('After');
    }
}
