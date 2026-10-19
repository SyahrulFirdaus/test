<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Lead;
use App\Models\Testimonial;
use App\Models\User;
use App\Notifications\NewLeadSubmitted;
use App\Support\AdminPermission;
use App\Support\BusinessHours;
use App\Support\LeadTracking;
use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Form Kontak, halaman Terima Kasih, teks WhatsApp per halaman, CTA sticky
 * mobile, Ulasan Google dari admin, dan angka statistik server-side.
 */
class WebsiteConversionTest extends TestCase
{
    use RefreshDatabase;

    private const INDUSTRY = 'otomotif-motorsport';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CompanyProfileSeeder::class, \Database\Seeders\ServiceSeeder::class, \Database\Seeders\TechnologySeeder::class]);
        Storage::fake('local');
        Notification::fake();
    }

    /** @return array<string, mixed> */
    private function contact(array $overrides = []): array
    {
        return array_merge([
            'form' => 'kontak',
            'form_started' => Crypt::encryptString((string) now()->subMinute()->timestamp),
            'name' => 'Budi Santoso',
            'company' => 'PT Otomotif Jaya',
            'email' => 'budi@contoh.test',
            'whatsapp' => '0812 3333 4444',
            'service' => 'Reverse Engineering',
            'notes' => 'Butuh reverse engineering bracket mesin.',
            'page_url' => 'http://localhost/about',
        ], $overrides);
    }

    /* ------------------------------------------------------ 1. Form Kontak */

    public function test_form_kontak_tampil_di_about_services_dan_halaman_industri(): void
    {
        foreach (['/about', '/services', '/industri/'.self::INDUSTRY] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('data-contact-form', false)
                ->assertSee('name="form" value="kontak"', false)
                ->assertSee('Miniatur/Maket')
                ->assertSee('name="website"', false);
        }
    }

    public function test_form_kontak_tersimpan_diemailkan_lalu_ke_terima_kasih(): void
    {
        $this->withCredentials()
            ->withCookie(LeadTracking::COOKIE, json_encode(['gclid' => 'G-KONTAK', 'utm_source' => 'google', 'utm_campaign' => 're-q4']))
            ->post(route('leads.store'), $this->contact([
                'attachment' => UploadedFile::fake()->createWithContent('bracket.stl', 'solid bracket'),
            ]))
            ->assertRedirect(route('thanks', ['tipe' => 'kontak']));

        $lead = Lead::sole();
        $this->assertSame('form', $lead->source);
        $this->assertSame('Reverse Engineering', $lead->service);
        $this->assertSame('G-KONTAK', $lead->gclid);
        $this->assertSame('re-q4', $lead->utm_campaign);
        $this->assertSame('bracket.stl', $lead->attachments[0]['name']);

        Notification::assertSentTo(
            new AnonymousNotifiable,
            NewLeadSubmitted::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'cs@nusama3d.com'
                && $notification->lead->is($lead)
        );

        // Halaman Terima Kasih berikutnya mengirim generate_lead satu kali.
        $this->get(route('thanks', ['tipe' => 'kontak']))->assertOk()->assertSee("nusamaTrack('generate_lead'", false);
        $this->get(route('thanks', ['tipe' => 'kontak']))->assertOk()->assertDontSee("nusamaTrack('generate_lead'", false);
    }

    public function test_form_kontak_lewat_fetch_menjawab_url_terima_kasih(): void
    {
        $this->postJson(route('leads.store'), $this->contact())
            ->assertCreated()
            ->assertJson(['redirect' => route('thanks', ['tipe' => 'kontak'])]);
    }

    public function test_form_kontak_divalidasi(): void
    {
        $this->postJson(route('leads.store'), $this->contact([
            'name' => '', 'email' => 'salah', 'whatsapp' => '', 'service' => 'Bukan Layanan', 'notes' => '',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'whatsapp', 'service', 'notes']);

        $this->assertSame(0, Lead::count());
        Notification::assertNothingSent();
    }

    public function test_lampiran_form_kontak_dibatasi_format_isi_dan_ukuran(): void
    {
        foreach ([
            UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
            UploadedFile::fake()->createWithContent('palsu.stl', '<html>bukan model</html>'),
            UploadedFile::fake()->createWithContent('palsu.pdf', '<html>bukan pdf</html>'),
            UploadedFile::fake()->create('besar.pdf', 61441, 'application/pdf'),
        ] as $file) {
            $response = $this->postJson(route('leads.store'), $this->contact(['attachment' => $file]));
            $this->assertSame(422, $response->status(), $file->getClientOriginalName());
            $response
                ->assertJsonValidationErrors('attachment');
        }

        $this->postJson(route('leads.store'), $this->contact([
            'attachment' => UploadedFile::fake()->createWithContent('gambar.pdf', "%PDF-1.4\n%test"),
        ]))->assertCreated();

        $this->assertSame(1, Lead::count());
    }

    public function test_bot_honeypot_dan_kiriman_terlalu_cepat_tidak_disimpan(): void
    {
        $this->post(route('leads.store'), $this->contact(['website' => 'http://spam.test']))
            ->assertRedirect(route('thanks', ['tipe' => 'kontak']));

        $this->post(route('leads.store'), $this->contact(['form_started' => Crypt::encryptString((string) now()->timestamp)]))
            ->assertRedirect();

        $this->post(route('leads.store'), $this->contact(['form_started' => 'tidak-sah']))
            ->assertRedirect();

        $this->assertSame(0, Lead::count());
        Notification::assertNothingSent();

        // Bot tidak memicu event konversi.
        $this->get(route('thanks', ['tipe' => 'kontak']))->assertDontSee("nusamaTrack('generate_lead'", false);
    }

    public function test_recaptcha_diperiksa_bila_kunci_diisi(): void
    {
        config(['leads.recaptcha.site_key' => 'site', 'leads.recaptcha.secret_key' => 'secret']);

        \Illuminate\Support\Facades\Http::fake([
            'www.google.com/recaptcha/*' => \Illuminate\Support\Facades\Http::sequence()
                ->push(['success' => true, 'score' => 0.1, 'action' => 'contact'])
                ->push(['success' => true, 'score' => 0.9, 'action' => 'contact']),
        ]);

        // Skor rendah: ditolak.
        $this->postJson(route('leads.store'), $this->contact(['recaptcha_token' => 'token']))->assertUnprocessable();
        $this->assertSame(0, Lead::count());

        // Skor tinggi: diterima.
        $this->postJson(route('leads.store'), $this->contact(['recaptcha_token' => 'token']))->assertCreated();
        $this->assertSame(1, Lead::count());
    }

    /** Host situs seperti ditulis di teks WA, mis. "nusama3d.com". */
    private function host(): string
    {
        return rtrim(preg_replace('#^https?://(www\.)?#', '', url('/')), '/');
    }

    /* ---------------------------------------------- 2. Halaman Terima Kasih */

    public function test_halaman_terima_kasih_noindex_dan_berisi_wa_estimasi_tracking(): void
    {
        $response = $this->get('/terima-kasih?tipe=kontak')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('Terima kasih')
            ->assertSee('Estimasi waktu balasan')
            ->assertSee(route('tracking.index'), false)
            ->assertSee('https://wa.me/', false);

        $this->get('/sitemap.xml')->assertDontSee('terima-kasih');
    }

    public function test_quotation_diarahkan_ke_terima_kasih_dengan_nomor_tracking(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->postJson(route('quotations.store'), [
            'quantity' => 1,
            'model' => UploadedFile::fake()->createWithContent('bracket.stl', 'solid test'),
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'model_volume_cm3' => 50,
            'analysis_status' => 'ready',
            'analysis' => json_encode([]),
            'model_stats' => json_encode(['dimensions' => ['x' => 20, 'y' => 20, 'z' => 20]]),
        ])->assertCreated()->assertJson(['thanks_url' => route('thanks', ['tipe' => 'quote'])]);

        $number = $response->json('tracking_number');

        $this->actingAs($customer)->get(route('thanks', ['tipe' => 'quote']))
            ->assertOk()
            ->assertSee($number)
            ->assertSee(route('tracking.show', $number), false)
            // Quote dilaporkan sebagai quote_submitted di 3D Models, bukan generate_lead.
            ->assertDontSee("nusamaTrack('generate_lead'", false);
    }

    public function test_estimasi_balasan_mengikuti_jam_kerja(): void
    {
        $jakarta = fn (string $time) => Carbon::parse($time, 'Asia/Jakarta');

        $this->assertStringContainsString('hari ini, paling lambat pukul 17.00', BusinessHours::replyEstimate($jakarta('2026-10-19 10:00'))); // Senin
        $this->assertStringContainsString('hari ini mulai pukul 08.00', BusinessHours::replyEstimate($jakarta('2026-10-19 06:00')));
        $this->assertStringContainsString('besok, Selasa, 20 Oktober 2026', BusinessHours::replyEstimate($jakarta('2026-10-19 18:00')));
        $this->assertStringContainsString('Senin, 19 Oktober 2026', BusinessHours::replyEstimate($jakarta('2026-10-17 10:00'))); // Sabtu
    }

    /* ------------------------------------------- 3. Teks WhatsApp per halaman */

    public function test_teks_wa_menyebut_layanan_dan_halamannya(): void
    {
        $html = $this->get('/services/reverse-engineering')->assertOk()->getContent();

        $this->assertStringContainsString(
            rawurlencode('Halo NUSAMA3D, saya ingin konsultasi Reverse Engineering (dari halaman '.$this->host().'/services/reverse-engineering)'),
            $html
        );
        $this->assertStringNotContainsString(rawurlencode('[Google]'), $html);
    }

    public function test_teks_wa_berbeda_per_halaman_dan_menandai_google(): void
    {
        $this->get('/industri/'.self::INDUSTRY)
            ->assertSee(rawurlencode('(dari halaman '.$this->host().'/industri/'.self::INDUSTRY.')'), false);

        $this->get('/technologies')->assertSee(rawurlencode('konsultasi teknologi 3D printing (dari halaman '.$this->host().'/technologies)'), false);

        // GCLID di URL, lalu dari cookie first touch.
        $this->get('/services/3d-scanning?gclid=ABC')->assertSee(rawurlencode(' [Google]'), false);

        $this->withCookie(LeadTracking::COOKIE, json_encode(['gclid' => 'ABC']))
            ->get('/about')
            ->assertSee(rawurlencode('(dari halaman '.$this->host().'/about) [Google]'), false);
    }

    /* ---------------------------------------------------- 4. Sticky mobile */

    public function test_cta_sticky_mobile_di_semua_halaman_publik(): void
    {
        foreach (['/', '/about', '/services', '/services/3d-printing', '/miniatur-teknik', '/technologies'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertSame(2, substr_count($html, 'data-cta="sticky"'), $url);
            $this->assertStringContainsString('data-sticky-cta', $html);
            $this->assertStringContainsString('Minta Penawaran', $html);
            // Ruang bawah agar konten tidak tertutup bilah.
            $this->assertStringContainsString('pb-[calc(4.5rem+env(safe-area-inset-bottom))] md:pb-0', $html);
        }

        // "Minta Penawaran" menuju formulir di halaman itu.
        $this->get('/about')->assertSee('href="#form-kontak"', false);
        $this->get('/services/3d-printing')->assertSee('href="#minta-penawaran"', false);
    }

    /* ------------------------------------------------------ 5. Ulasan Google */

    public function test_ulasan_tampil_dengan_tanggal_relatif_dan_tautan_google(): void
    {
        Testimonial::create(['name' => 'Rina', 'rating' => 5, 'quote' => 'Hasil cetak rapi.', 'source' => 'Google Review', 'reviewed_at' => now()->subWeeks(2), 'is_active' => true]);

        $this->get('/')
            ->assertSee('2 minggu lalu')
            ->assertSee('Lihat semua ulasan di Google')
            ->assertSee('https://www.google.com/maps/search/?api=1&amp;query=', false);

        CompanyProfile::current()->update(['google_reviews_url' => 'https://g.page/r/nusama3d/review']);
        $this->app->forgetInstance(CompanyProfile::class);

        $this->get('/')->assertSee('https://g.page/r/nusama3d/review', false);
    }

    public function test_owner_mengelola_ulasan_dari_admin(): void
    {
        $owner = User::factory()->superAdmin()->create();

        $this->actingAs($owner)->get(route('superadmin.reviews.index'))->assertOk();

        $this->actingAs($owner)->post(route('superadmin.reviews.store'), [
            'name' => 'Andi Wijaya',
            'rating' => 5,
            'quote' => 'Pelayanan cepat.',
            'reviewed_at' => now()->subDays(3)->toDateString(),
            'is_active' => 1,
        ])->assertRedirect(route('superadmin.reviews.index'));

        $review = Testimonial::where('name', 'Andi Wijaya')->sole();
        $this->get('/')->assertSee('Pelayanan cepat.')->assertSee('3 hari lalu');

        $this->actingAs($owner)->patch(route('superadmin.reviews.update', $review), [
            'name' => 'Andi Wijaya', 'rating' => 4, 'quote' => 'Diganti.', 'reviewed_at' => now()->toDateString(), 'is_active' => 0,
        ])->assertRedirect();
        $this->get('/')->assertDontSee('Diganti.');

        $this->actingAs($owner)->patch(route('superadmin.reviews.link'), ['google_reviews_url' => 'https://evil.test/x'])
            ->assertSessionHasErrors('google_reviews_url');

        $this->actingAs($owner)->delete(route('superadmin.reviews.destroy', $review))->assertRedirect();
        $this->assertModelMissing($review);
    }

    public function test_admin_tanpa_hak_ulasan_ditolak(): void
    {
        $admin = User::factory()->withPermissions([AdminPermission::REVIEW_VIEW])->create();

        $this->actingAs($admin)->get(route('admin.reviews.index'))->assertOk()->assertDontSee('Tambah Ulasan');
        $this->actingAs($admin)->get(route('admin.reviews.create'))->assertForbidden();

        $other = User::factory()->withPermissions([AdminPermission::QUOTATION_VIEW])->create();
        $this->actingAs($other)->get(route('admin.reviews.index'))->assertForbidden();
    }

    /* ------------------------------------------------ 6. Statistik server-side */

    public function test_angka_statistik_dirender_di_html(): void
    {
        $this->travelTo(Carbon::parse('2026-10-18'));

        foreach (['/', '/about'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/data-counter="6"\s*>6</', $html, $url);
            $this->assertMatchesRegularExpression('/data-counter="400"\s*>400</', $html, $url);
            $this->assertMatchesRegularExpression('/data-counter="4"\s*>4</', $html, $url);
            $this->assertDoesNotMatchRegularExpression('/data-counter="\d+"\s*>0</', $html, $url);
            // Part Diproduksi: placeholder, bukan angka karangan.
            $this->assertStringNotContainsString('25.000', $html);
            $this->assertStringContainsString('Part Diproduksi', $html);
        }
    }
}
