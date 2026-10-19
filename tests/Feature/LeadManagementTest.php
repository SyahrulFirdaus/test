<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\ActivityAction;
use App\Support\AdminPermission;
use App\Support\LeadStatus;
use App\Support\LeadTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lead Management (LD-01): lead dari Form landing page dan Quotation dalam
 * satu tempat, tracking first touch, status & nilai deal, serta hak akses.
 */
class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /** @return array<string, mixed> */
    private function quotationPayload(): array
    {
        return [
            'quantity' => 1,
            'model' => UploadedFile::fake()->createWithContent('bracket.stl', 'solid test'),
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'model_volume_cm3' => 50,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'analysis' => json_encode([]),
            'model_stats' => json_encode(['dimensions' => ['x' => 20, 'y' => 20, 'z' => 20]]),
        ];
    }

    /** @return array<string, string> */
    private function formPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dewi Lestari',
            'company' => 'PT Uji Coba',
            'email' => 'Dewi@Contoh.test',
            'whatsapp' => '0811-2233-4455',
            'service' => 'Reverse Engineering',
            'notes' => 'Butuh reverse engineering 3 gear.',
            'page_url' => 'http://localhost/services/reverse-engineering',
        ], $overrides);
    }

    private function trackingCookie(array $data): string
    {
        return json_encode($data);
    }

    /* ------------------------------------------------------------ tracking */

    public function test_kunjungan_pertama_menyimpan_gclid_dan_utm(): void
    {
        $response = $this->get('/kebijakan-privasi?gclid=ABC123&utm_source=google&utm_medium=cpc&utm_campaign=re-q4');

        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === LeadTracking::COOKIE);

        $this->assertNotNull($cookie);
    }

    public function test_first_touch_tidak_ditimpa_kunjungan_berikutnya(): void
    {
        $response = $this->withCookie(LeadTracking::COOKIE, $this->trackingCookie(['gclid' => 'PERTAMA', 'landing_page' => 'http://localhost/']))
            ->get('/services/3d-scanning?gclid=KEDUA');

        $this->assertNull(collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === LeadTracking::COOKIE));
    }

    public function test_halaman_pengelola_tidak_mencatat_tracking(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->get(route('superadmin.leads.index', ['utm_source' => 'x']));

        $response->assertOk();
        $this->assertNull(collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === LeadTracking::COOKIE));
    }

    /* ---------------------------------------------------------------- form */

    public function test_form_landing_page_tersimpan_sebagai_lead_beserta_tracking(): void
    {
        $this->withCredentials()
            ->withCookie(LeadTracking::COOKIE, $this->trackingCookie([
                'gclid' => 'GCLID-1',
                'utm_source' => 'google',
                'utm_medium' => 'cpc',
                'utm_campaign' => 're-q4',
                'utm_term' => 'jasa reverse engineering',
                'utm_content' => 'iklan-a',
                'landing_page' => 'http://localhost/services/reverse-engineering?gclid=GCLID-1',
            ]))
            ->postJson(route('leads.store'), $this->formPayload())
            ->assertCreated();

        $lead = Lead::sole();

        $this->assertSame('form', $lead->source);
        $this->assertSame(LeadStatus::LEAD, $lead->status);
        $this->assertSame('dewi@contoh.test', $lead->email);
        $this->assertSame('6281122334455', $lead->whatsapp);
        $this->assertSame('Reverse Engineering', $lead->service);
        $this->assertSame('GCLID-1', $lead->gclid);
        $this->assertSame('re-q4', $lead->utm_campaign);
        $this->assertSame('jasa reverse engineering', $lead->utm_term);
        $this->assertSame('http://localhost/services/reverse-engineering', $lead->source_url);
        $this->assertNull($lead->deal_value);
    }

    public function test_form_tanpa_tracking_tidak_mengarang_data(): void
    {
        $this->postJson(route('leads.store'), $this->formPayload())->assertCreated();

        $lead = Lead::sole();

        foreach (LeadTracking::PARAMETERS as $key) {
            $this->assertNull($lead->{$key});
        }
    }

    public function test_form_kontak_sama_tidak_membuat_lead_ganda(): void
    {
        $this->postJson(route('leads.store'), $this->formPayload())->assertCreated();
        $this->postJson(route('leads.store'), $this->formPayload())->assertCreated();
        $this->postJson(route('leads.store'), $this->formPayload(['notes' => 'Tambahan: juga 2 bracket.', 'email' => 'dewi@contoh.test']))->assertCreated();

        $lead = Lead::sole();
        $this->assertStringContainsString('Tambahan: juga 2 bracket.', $lead->notes);
        $this->assertStringContainsString('Butuh reverse engineering 3 gear.', $lead->notes);
    }

    public function test_form_divalidasi_dan_honeypot_diabaikan(): void
    {
        $this->postJson(route('leads.store'), $this->formPayload(['name' => '', 'service' => 'Bukan Layanan']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'service']);

        $this->postJson(route('leads.store'), $this->formPayload(['website' => 'http://spam.test']))->assertCreated();

        $this->assertSame(0, Lead::count());
    }

    /* ----------------------------------------------------------- quotation */

    public function test_quotation_membuat_lead_dengan_tracking_dan_tautan(): void
    {
        $customer = User::factory()->create(['email' => 'budi@contoh.test', 'phone' => '0812 0000 1111']);

        $this->actingAs($customer)
            ->withCredentials()
            ->withCookie(LeadTracking::COOKIE, $this->trackingCookie(['utm_source' => 'google', 'utm_campaign' => 'printing', 'gclid' => 'G-QUOTE']))
            ->postJson(route('quotations.store'), $this->quotationPayload())
            ->assertCreated();

        $lead = Lead::sole();
        $quotation = QuotationRequest::sole();

        $this->assertSame('quotation', $lead->source);
        $this->assertSame(LeadStatus::QUOTATION, $lead->status);
        $this->assertSame($customer->id, $lead->user_id);
        $this->assertSame('G-QUOTE', $lead->gclid);
        $this->assertSame('3D Printing', $lead->service);
        $this->assertSame($lead->id, $quotation->lead_id);
        // Nilai deal tidak diambil dari estimasi.
        $this->assertNull($lead->deal_value);
    }

    public function test_lead_form_naik_menjadi_quotation_tanpa_lead_baru(): void
    {
        $this->postJson(route('leads.store'), $this->formPayload(['email' => 'budi@contoh.test']))->assertCreated();

        $customer = User::factory()->create(['email' => 'budi@contoh.test', 'phone' => '0899 1111 2222']);

        $this->actingAs($customer)->postJson(route('quotations.store'), $this->quotationPayload())->assertCreated();
        $this->actingAs($customer)->postJson(route('quotations.store'), $this->quotationPayload())->assertCreated();

        $lead = Lead::sole();

        $this->assertSame('form', $lead->source);
        $this->assertSame(LeadStatus::QUOTATION, $lead->status);
        $this->assertSame(2, $lead->quotations()->count());
        $this->assertSame($customer->id, $lead->user_id);
    }

    public function test_quotation_setelah_deal_membuka_lead_baru(): void
    {
        $customer = User::factory()->create(['email' => 'budi@contoh.test']);

        $this->actingAs($customer)->postJson(route('quotations.store'), $this->quotationPayload())->assertCreated();
        Lead::query()->update(['status' => LeadStatus::DEAL, 'deal_value' => 1000000]);

        $this->actingAs($customer)->postJson(route('quotations.store'), $this->quotationPayload())->assertCreated();

        $this->assertSame(2, Lead::count());
        $this->assertSame(1000000, Lead::where('status', LeadStatus::DEAL)->sole()->deal_value);
    }

    /* --------------------------------------------------------- hak akses */

    public function test_admin_tanpa_hak_lead_ditolak(): void
    {
        $admin = User::factory()->withPermissions([AdminPermission::QUOTATION_VIEW])->create();
        $lead = Lead::create(['source' => 'form', 'name' => 'A']);

        $this->actingAs($admin)->get(route('admin.leads.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.leads.show', $lead))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.leads.update', $lead), ['status' => 'deal'])->assertForbidden();
    }

    public function test_admin_hanya_lihat_tidak_dapat_mengubah(): void
    {
        $admin = User::factory()->withPermissions([AdminPermission::LEAD_VIEW])->create();
        $lead = Lead::create(['source' => 'form', 'name' => 'Lead Lihat']);

        $this->actingAs($admin)->get(route('admin.leads.index'))->assertOk()->assertSee('Lead Lihat');
        $this->actingAs($admin)->get(route('admin.leads.show', $lead))->assertOk()->assertDontSee('name="deal_value"', false);
        $this->actingAs($admin)->patch(route('admin.leads.update', $lead), ['status' => 'deal'])->assertForbidden();

        $this->assertSame(LeadStatus::LEAD, $lead->fresh()->status);
    }

    public function test_menu_lead_tampil_sesuai_hak_akses(): void
    {
        $withLead = User::factory()->withPermissions([AdminPermission::LEAD_VIEW])->create();
        $without = User::factory()->withPermissions([AdminPermission::QUOTATION_VIEW])->create();

        $this->actingAs($withLead)->get(route('admin.leads.index'))->assertSee(route('admin.leads.index'), false);
        $this->actingAs($without)->get(route('admin.quotations.index'))->assertDontSee(route('admin.leads.index'), false);
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('superadmin.leads.index'))->assertOk();
    }

    /* ----------------------------------------------------- status & nilai */

    public function test_status_dan_nilai_deal_tersimpan_dan_tercatat(): void
    {
        $admin = User::factory()->withPermissions([AdminPermission::LEAD_VIEW, AdminPermission::LEAD_EDIT])->create();
        $lead = Lead::create(['source' => 'form', 'name' => 'Lead Deal']);

        $this->actingAs($admin)
            ->patch(route('admin.leads.update', $lead), ['status' => 'deal', 'deal_value' => 'Rp5.000.000'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $lead->refresh();
        $this->assertSame(LeadStatus::DEAL, $lead->status);
        $this->assertSame(5000000, $lead->deal_value);
        $this->assertSame('Rp5.000.000', $lead->deal_value_label);

        $log = ActivityLog::where('action', ActivityAction::LEAD_UPDATE)->sole();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame(['status' => 'Lead', 'deal_value' => '—'], $log->old_values);
        $this->assertSame(['status' => 'Deal', 'deal_value' => 'Rp5.000.000'], $log->new_values);

        $this->actingAs($admin)->get(route('admin.leads.show', $lead))
            ->assertSee('Rp5.000.000')
            ->assertSee($admin->name);
    }

    public function test_status_batal_tidak_menghapus_nilai_deal(): void
    {
        $admin = User::factory()->withPermissions([AdminPermission::LEAD_VIEW, AdminPermission::LEAD_EDIT])->create();
        $lead = Lead::create(['source' => 'form', 'name' => 'X', 'status' => 'deal', 'deal_value' => 7500000]);

        $this->actingAs($admin)->patch(route('admin.leads.update', $lead), ['status' => 'batal', 'deal_value' => '7.500.000'])
            ->assertSessionHasNoErrors();

        $this->assertSame(LeadStatus::CANCELLED, $lead->fresh()->status);
        $this->assertSame(7500000, $lead->fresh()->deal_value);
    }

    public function test_nilai_deal_negatif_atau_bukan_angka_ditolak(): void
    {
        $admin = User::factory()->withPermissions([AdminPermission::LEAD_VIEW, AdminPermission::LEAD_EDIT])->create();
        $lead = Lead::create(['source' => 'form', 'name' => 'X']);

        foreach (['-1000', 'lima juta', '5000,50', '5.5'] as $value) {
            $this->actingAs($admin)->patch(route('admin.leads.update', $lead), ['status' => 'deal', 'deal_value' => $value])
                ->assertSessionHasErrors('deal_value');
        }

        $this->actingAs($admin)->patch(route('admin.leads.update', $lead), ['status' => 'menang'])
            ->assertSessionHasErrors('status');

        $this->assertNull($lead->fresh()->deal_value);
        $this->assertSame(LeadStatus::LEAD, $lead->fresh()->status);
    }

    public function test_mengubah_lead_tidak_mengubah_quotation(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer)->postJson(route('quotations.store'), $this->quotationPayload())->assertCreated();

        $quotation = QuotationRequest::sole();
        $before = $quotation->only(['status', 'estimated_cost', 'payment_verified_at', 'updated_at']);

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->patch(route('superadmin.leads.update', Lead::sole()), ['status' => 'batal', 'deal_value' => ''])
            ->assertSessionHasNoErrors();

        $this->assertEquals($before, $quotation->fresh()->only(['status', 'estimated_cost', 'payment_verified_at', 'updated_at']));
    }

    /* ------------------------------------------------- daftar & filter */

    public function test_daftar_menampilkan_kolom_dan_tanda_kosong(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Lead::create(['source' => 'form', 'name' => 'Tanpa Tracking']);

        $this->actingAs($admin)->get(route('superadmin.leads.index'))
            ->assertOk()
            ->assertSeeInOrder(['Tanggal', 'Nama', 'Perusahaan', 'Email', 'WhatsApp', 'Layanan', 'Halaman Asal', 'GCLID', 'UTM Source', 'UTM Medium', 'UTM Campaign', 'UTM Term', 'UTM Content', 'Status Lead', 'Nilai Deal', 'Aksi'])
            ->assertSee('—');
    }

    public function test_filter_pencarian_sumber_status_layanan_campaign_tanggal(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Lead::create(['source' => 'form', 'name' => 'Alfa', 'company' => 'PT Alfa', 'service' => '3D Scanning', 'utm_campaign' => 'scan-q4', 'status' => 'lead']);
        Lead::create(['source' => 'quotation', 'name' => 'Beta', 'whatsapp' => '6281299998888', 'service' => '3D Printing', 'status' => 'deal']);
        $old = Lead::create(['source' => 'form', 'name' => 'Gamma Lama']);
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();

        $index = fn (array $query) => $this->actingAs($admin)->get(route('superadmin.leads.index', $query));

        $index(['q' => 'PT Alfa'])->assertSee('Alfa')->assertDontSee('Beta');
        $index(['q' => '0812 9999'])->assertSee('Beta')->assertDontSee('PT Alfa');
        $index(['source' => 'quotation'])->assertSee('Beta')->assertDontSee('PT Alfa');
        $index(['status' => 'deal'])->assertSee('Beta')->assertDontSee('PT Alfa');
        $index(['service' => '3D Scanning'])->assertSee('PT Alfa')->assertDontSee('Beta');
        $index(['campaign' => 'scan-q4'])->assertSee('PT Alfa')->assertDontSee('Beta');
        $index(['from' => now()->subDays(1)->format('Y-m-d'), 'to' => now()->format('Y-m-d')])->assertDontSee('Gamma Lama');
    }

    public function test_detail_menampilkan_quotation_terkait(): void
    {
        $customer = User::factory()->create();
        $this->actingAs($customer)->postJson(route('quotations.store'), $this->quotationPayload())->assertCreated();

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('superadmin.leads.show', Lead::sole()))
            ->assertOk()
            ->assertSee(QuotationRequest::sole()->tracking_number);
    }
}
