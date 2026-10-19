<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Support\LeadTracking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Landing page /miniatur-teknik beserta formulir konsultasinya. */
class MiniaturPageTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'form' => 'miniatur',
            'name' => 'Sari Wulandari',
            'company' => 'PT Properti Maju',
            'email' => 'sari@contoh.test',
            'whatsapp' => '0813 1111 2222',
            'service' => 'Maket Arsitektur',
            'quantity' => 2,
            'dimensions' => '60 × 40 × 30 cm',
            'notes' => 'Maket kawasan perumahan untuk pameran.',
            'page_url' => 'http://localhost/miniatur-teknik',
        ], $overrides);
    }

    public function test_halaman_tampil_dengan_seluruh_bagian(): void
    {
        $this->get('/miniatur-teknik')
            ->assertOk()
            ->assertSee('<title>Miniatur Teknik &amp; Souvenir Korporat Custom', false)
            ->assertSee('name="description"', false)
            ->assertSee('Konsultasikan Proyek')
            ->assertSee('Lihat Galeri')
            ->assertSee('id="galeri"', false)
            ->assertSee('id="konsultasi"', false)
            ->assertSeeInOrder(['Miniatur Teknik', 'Maket Arsitektur', 'Replika Produk', 'Souvenir Korporat', 'Miniatur Custom'])
            ->assertSee('Konsultasi desain');
    }

    public function test_informasi_harga_persis_dan_tanpa_rentang_atau_per_gram(): void
    {
        $html = $this->get('/miniatur-teknik')->assertOk()->getContent();

        $this->assertStringContainsString('Mulai Rp1 juta per unit', $html);
        $this->assertStringContainsString('Harga akhir tergantung pada dimensi, tingkat detail, kompleksitas desain, dan finishing.', $html);
        $this->assertStringContainsString('Untuk pesanan lebih dari 25 pcs, harga khusus diberikan setelah review desain.', $html);

        $text = strip_tags($html);
        $this->assertDoesNotMatchRegularExpression('/per\s*gram|\/\s*gram|Rp\s?[\d.,]+\s*(–|-|s\.?d\.?|sampai)\s*Rp/i', $text);
    }

    public function test_cta_whatsapp_memakai_nomor_resmi_dari_konfigurasi(): void
    {
        $this->seed(\Database\Seeders\CompanyProfileSeeder::class);

        $html = $this->get('/miniatur-teknik')->assertOk()->getContent();
        $company = \App\Models\CompanyProfile::current();
        $this->assertNotNull($company->whatsapp_link);

        if ($company->whatsapp_link) {
            $expected = e($company->whatsapp_link.'?text='.rawurlencode(config('miniatur.wa_text')));
            $this->assertGreaterThanOrEqual(2, substr_count($html, $expected));
        } else {
            $this->assertStringContainsString(route('about').'#kontak', $html);
        }
    }

    public function test_galeri_ilustrasi_diberi_label_dan_kredit(): void
    {
        $this->get('/miniatur-teknik')
            ->assertSee('Ilustrasi')
            ->assertSee('bukan hasil proyek NUSAMA3D')
            ->assertSee('commons.wikimedia.org', false);
    }

    public function test_formulir_tersimpan_ke_lead_beserta_tracking_dan_lampiran(): void
    {
        Storage::fake('local');

        $this->withCredentials()
            ->withCookie(LeadTracking::COOKIE, json_encode(['gclid' => 'G-MINI', 'utm_campaign' => 'miniatur-q4', 'utm_source' => 'google']))
            ->post(route('leads.store'), $this->payload([
                // GD tidak selalu tersedia: cukup header JPEG yang sah.
                'attachment' => UploadedFile::fake()->createWithContent('referensi.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9"),
            ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJson(['message' => 'Terima kasih, kebutuhan Anda sudah kami terima. Tim kami akan segera menghubungi Anda.']);

        $lead = Lead::sole();

        $this->assertSame('form', $lead->source);
        $this->assertSame('Maket Arsitektur', $lead->service);
        $this->assertSame(2, $lead->quantity);
        $this->assertSame('60 × 40 × 30 cm', $lead->dimensions);
        $this->assertSame('6281311112222', $lead->whatsapp);
        $this->assertSame('http://localhost/miniatur-teknik', $lead->source_url);
        $this->assertSame('G-MINI', $lead->gclid);
        $this->assertSame('miniatur-q4', $lead->utm_campaign);
        $this->assertCount(1, $lead->attachments);
        $this->assertSame('referensi.jpg', $lead->attachments[0]['name']);
        Storage::disk('local')->assertExists($lead->attachments[0]['path']);
    }

    public function test_setelah_terkirim_diarahkan_ke_halaman_terima_kasih(): void
    {
        $this->from('/miniatur-teknik')
            ->post(route('leads.store'), $this->payload())
            ->assertRedirect(route('thanks', ['tipe' => 'kontak']))
            ->assertSessionHas('lead_submitted');

        $this->assertSame(1, Lead::count());
    }

    public function test_validasi_backend(): void
    {
        $this->postJson(route('leads.store'), $this->payload([
            'name' => '',
            'email' => 'bukan-email',
            'whatsapp' => '',
            'service' => '3D Printing',
            'quantity' => 0,
            'notes' => '',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'whatsapp', 'service', 'quantity', 'notes']);

        $this->assertSame(0, Lead::count());
    }

    public function test_lampiran_berbahaya_ditolak(): void
    {
        Storage::fake('local');

        $this->postJson(route('leads.store'), $this->payload([
            'attachment' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('attachment');

        $this->postJson(route('leads.store'), $this->payload([
            'attachment' => UploadedFile::fake()->create('besar.pdf', 11000, 'application/pdf'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('attachment');

        $this->assertSame(0, Lead::count());
    }

    public function test_lampiran_dapat_diunduh_admin_berhak_saja(): void
    {
        Storage::fake('local');

        $this->post(route('leads.store'), $this->payload([
            'attachment' => UploadedFile::fake()->createWithContent('desain.pdf', '%PDF-1.4 test'),
        ]), ['Accept' => 'application/json'])->assertCreated();

        $lead = Lead::sole();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get(route('superadmin.leads.show', $lead))
            ->assertSee('desain.pdf')
            ->assertSee('60 × 40 × 30 cm')
            ->assertSee('2 pcs');

        $this->actingAs($superAdmin)->get(route('superadmin.leads.attachment', [$lead, 0]))
            ->assertOk()
            ->assertDownload('desain.pdf');

        $this->actingAs($superAdmin)->get(route('superadmin.leads.attachment', [$lead, 5]))->assertNotFound();

        $this->actingAs(User::factory()->withPermissions([\App\Support\AdminPermission::QUOTATION_VIEW])->create())
            ->get(route('admin.leads.attachment', [$lead, 0]))
            ->assertForbidden();

        auth()->logout();
        $this->get(route('admin.leads.attachment', [$lead, 0]))->assertRedirect();
    }

    public function test_form_singkat_landing_layanan_tetap_berjalan(): void
    {
        $this->postJson(route('leads.store'), [
            'name' => 'Andi',
            'service' => '3D Scanning',
            'notes' => 'Scan part mesin.',
        ])->assertCreated();

        $this->assertSame('3D Scanning', Lead::sole()->service);
    }

    public function test_tautan_footer_dan_sitemap(): void
    {
        $this->get('/miniatur-teknik')->assertSee(route('miniatur'), false);
        $this->get('/sitemap.xml')->assertSee(route('miniatur'), false);
    }
}
