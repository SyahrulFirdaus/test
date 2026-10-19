<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use App\Models\WhatsAppNotification;
use App\Models\WhatsAppSetting;
use App\Support\AdminPermission;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Notifikasi WhatsApp setiap kali Admin/Superadmin mengubah status penawaran,
 * beserta menu Pengaturan WhatsApp.
 */
class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function customer(): User
    {
        return User::factory()->create(['name' => 'Rangga Prasetya', 'phone' => '0812-3456-7890']);
    }

    private function quotation(?User $owner = null, string $status = QuotationStatus::REVIEWING): QuotationRequest
    {
        return QuotationRequest::create([
            'user_id' => $owner?->id,
            'tracking_number' => 'QTN-20260930-WA0001',
            'name' => 'Rangga Prasetya', 'email' => 'rangga@contoh.test', 'whatsapp' => '081299998888',
            'quantity' => 1, 'file_name' => 'part.stl', 'file_path' => 'quotations/part.stl',
            'file_format' => 'STL', 'file_size' => 4096,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM', 'material' => 'PLA Plus', 'estimated_minutes' => 60,
            'status' => $status,
        ]);
    }

    private function sender(string $number = '6281111111111'): void
    {
        WhatsAppSetting::current()->update(['phone_number' => $number]);
    }

    private function useCloudApi(): void
    {
        config([
            'whatsapp.driver' => 'cloud_api',
            'whatsapp.cloud_api.access_token' => 'test-token',
            'whatsapp.cloud_api.business_account_id' => 'WABA1',
        ]);
    }

    /* ------------------------------------------------ perubahan status --- */

    public function test_perubahan_status_oleh_admin_mengirim_satu_notifikasi_ke_pelanggan(): void
    {
        $this->sender();
        $customer = $this->customer();
        $quotation = $this->quotation($customer);

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.quotations.update', $quotation), ['status' => QuotationStatus::AWAITING_PAYMENT])
            ->assertSessionHas('status')
            ->assertSessionMissing('whatsapp_warning');

        $log = WhatsAppNotification::sole();

        $this->assertSame(WhatsAppNotification::SENT, $log->status);
        $this->assertSame('6281234567890', $log->recipient, 'nomor diambil dari akun pelanggan');
        $this->assertSame('6281111111111', $log->sender);
        $this->assertSame($quotation->id, $log->quotation_request_id);
        $this->assertSame($customer->id, $log->user_id);
        $this->assertSame(QuotationStatus::REVIEWING, $log->previous_status);
        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $log->quotation_status);
        $this->assertStringContainsString('Halo Rangga Prasetya', $log->message);
        $this->assertStringContainsString('QTN-20260930-WA0001', $log->message);
        $this->assertStringContainsString(QuotationStatus::label(QuotationStatus::AWAITING_PAYMENT), $log->message);
        $this->assertStringContainsString('Tanggal Perubahan:', $log->message);
    }

    public function test_status_yang_sama_tidak_mengirim_notifikasi(): void
    {
        $this->sender();
        $quotation = $this->quotation($this->customer());

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.quotations.update', $quotation), [
                'status' => QuotationStatus::REVIEWING,
                'note' => 'Hanya catatan.',
            ])
            ->assertSessionHas('status');

        $this->assertSame(0, WhatsAppNotification::count());
    }

    public function test_perubahan_status_oleh_pelanggan_tidak_mengirim_notifikasi(): void
    {
        $this->sender();
        $customer = $this->customer();
        $quotation = $this->quotation($customer);

        $this->actingAs($customer);
        $quotation->update(['status' => QuotationStatus::CANCELLED_BY_USER]);

        $this->assertSame(0, WhatsAppNotification::count());
    }

    public function test_pembatalan_oleh_admin_ikut_mengirim_notifikasi(): void
    {
        $this->sender();
        $quotation = $this->quotation($this->customer());

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.quotations.cancel', $quotation))
            ->assertSessionHas('status');

        $this->assertSame(QuotationStatus::CANCELLATION_APPROVED, WhatsAppNotification::sole()->quotation_status);
    }

    public function test_whatsapp_gagal_tidak_menggagalkan_perubahan_status(): void
    {
        $this->useCloudApi();
        $this->sender();
        WhatsAppSetting::current()->update(['phone_number_id' => 'PNID1']);

        Http::fake(['*' => Http::response(['error' => ['message' => 'Recipient not on WhatsApp', 'code' => 131026]], 400)]);

        $quotation = $this->quotation($this->customer());

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.quotations.update', $quotation), ['status' => QuotationStatus::AWAITING_PAYMENT])
            ->assertSessionHas('status')
            ->assertSessionHas('whatsapp_warning');

        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $quotation->fresh()->status);

        $log = WhatsAppNotification::sole();
        $this->assertSame(WhatsAppNotification::FAILED, $log->status);
        $this->assertStringContainsString('Recipient not on WhatsApp', $log->error_message);
        $this->assertStringNotContainsString('test-token', $log->error_message);
    }

    public function test_tanpa_nomor_pengirim_status_tetap_tersimpan_dan_kegagalan_dicatat(): void
    {
        $quotation = $this->quotation($this->customer());

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.quotations.update', $quotation), ['status' => QuotationStatus::AWAITING_PAYMENT])
            ->assertSessionHas('whatsapp_warning');

        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $quotation->fresh()->status);
        $this->assertSame(WhatsAppNotification::FAILED, WhatsAppNotification::sole()->status);
    }

    public function test_cloud_api_mengirim_dari_nomor_pengaturan(): void
    {
        $this->useCloudApi();
        $this->sender('6285555555555');

        Http::fake([
            '*/WABA1/phone_numbers*' => Http::response(['data' => [
                ['id' => 'PN-LAMA', 'display_phone_number' => '+62 811-1111-1111'],
                ['id' => 'PN-BARU', 'display_phone_number' => '+62 855-5555-5555', 'verified_name' => 'NUSAMA3D'],
            ]]),
            '*/PN-BARU/messages' => Http::response(['messages' => [['id' => 'wamid.123']]]),
        ]);

        $quotation = $this->quotation($this->customer());

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.quotations.update', $quotation), ['status' => QuotationStatus::AWAITING_PAYMENT]);

        $log = WhatsAppNotification::sole();
        $this->assertSame(WhatsAppNotification::SENT, $log->status);
        $this->assertSame('wamid.123', $log->provider_message_id);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/PN-BARU/messages')
            && $request['to'] === '6281234567890'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    /* ------------------------------------------ Pengaturan WhatsApp --- */

    public function test_admin_dan_superadmin_dapat_membuka_pengaturan_pelanggan_tidak(): void
    {
        $this->actingAs($this->superAdmin())->get(route('superadmin.whatsapp.edit'))
            ->assertOk()->assertSee('Pengaturan WhatsApp')->assertSee('Uji Kirim WhatsApp');

        $admin = User::factory()->withPermissions([AdminPermission::WHATSAPP_VIEW, AdminPermission::WHATSAPP_EDIT])
            ->create(['email' => 'admin@nusama3d.com']);
        $this->actingAs($admin)->get(route('admin.whatsapp.edit'))->assertOk();

        $this->actingAs($this->customer())->get(route('admin.whatsapp.edit'))->assertRedirect(route('dashboard'));
    }

    public function test_admin_tanpa_hak_akses_ditolak(): void
    {
        $admin = User::factory()->withPermissions([AdminPermission::QUOTATION_VIEW])->create(['email' => 'admin@nusama3d.com']);

        $this->actingAs($admin)->get(route('admin.whatsapp.edit'))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.whatsapp.update'), ['phone_number' => '6281234567890'])->assertForbidden();
    }

    public function test_menyimpan_nomor_pengirim_menormalkan_formatnya(): void
    {
        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.whatsapp.update'), ['phone_number' => '0812 3456 7890'])
            ->assertSessionHas('status');

        $setting = WhatsAppSetting::current();
        $this->assertSame('6281234567890', $setting->phone_number);
        $this->assertSame(WhatsAppSetting::CONNECTED, $setting->connection_status);
    }

    public function test_periksa_koneksi_memakai_formulir_post_tersendiri(): void
    {
        $this->sender();
        $superAdmin = $this->superAdmin();

        $html = $this->actingAs($superAdmin)->get(route('superadmin.whatsapp.edit'))->assertOk()->getContent();

        // Tombolnya menunjuk formulir tanpa `_method=PATCH`, sehingga yang terkirim benar-benar POST.
        $this->assertMatchesRegularExpression(
            '/<form id="whatsapp-check-form" method="POST" action="'.preg_quote(route('superadmin.whatsapp.check'), '/').'"/',
            $html
        );
        $this->assertStringContainsString('form="whatsapp-check-form"', $html);

        $this->actingAs($superAdmin)->post(route('superadmin.whatsapp.check'))->assertSessionHas('status');
        $this->assertSame(WhatsAppSetting::CONNECTED, WhatsAppSetting::current()->connection_status);
    }

    public function test_halaman_tidak_menampilkan_credential(): void
    {
        $this->useCloudApi();
        config(['whatsapp.cloud_api.access_token' => 'rahasia-sekali-123']);

        $this->actingAs($this->superAdmin())->get(route('superadmin.whatsapp.edit'))
            ->assertOk()
            ->assertDontSee('rahasia-sekali-123')
            ->assertDontSee('WABA1');
    }

    public function test_uji_kirim_berhasil_dan_gagal(): void
    {
        $superAdmin = $this->superAdmin();
        $this->sender();

        $this->actingAs($superAdmin)
            ->post(route('superadmin.whatsapp.test'), ['test_number' => '6289999999999'])
            ->assertSessionHas('status', 'Pesan WhatsApp berhasil dikirim.');

        $this->useCloudApi();
        Http::fake(['*' => Http::response(['error' => ['message' => 'Invalid token']], 401)]);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.whatsapp.test'), ['test_number' => '6289999999999'])
            ->assertSessionHas('error', fn (string $message) => str_starts_with($message, 'Pesan WhatsApp gagal dikirim. Silakan periksa konfigurasi WhatsApp.'));

        $this->assertSame(
            [WhatsAppNotification::SENT, WhatsAppNotification::FAILED],
            WhatsAppNotification::orderBy('id')->pluck('status')->all()
        );
    }
}
