<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pelacakan (sheet "Spesifikasi Tracking") dan persetujuan cookie
 * (Consent Mode v2): phone_click, email_click, whatsapp_click, sign_up,
 * quote_submitted, file_upload, generate_lead + user_data.
 */
class TrackingConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CompanyProfileSeeder::class, \Database\Seeders\ServiceSeeder::class, \Database\Seeders\TechnologySeeder::class]);
    }

    /* --------------------------------------------- 1, 2, 4. Klik tel/mail/WA */

    public function test_tautan_telepon_format_internasional(): void
    {
        foreach (['/', '/about'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('href="tel:+6281292388805"', $html, $url);
            $this->assertStringNotContainsString('href="tel:0', $html, $url);
        }
    }

    public function test_pelacak_klik_terpasang_di_semua_layout_pengunjung(): void
    {
        $pages = [
            $this->get('/')->getContent(),
            $this->get(route('register'))->getContent(),
            $this->get(route('login'))->getContent(),
            $this->actingAs(User::factory()->create())->get(route('dashboard'))->getContent(),
        ];

        foreach ($pages as $html) {
            $this->assertStringContainsString("name = 'phone_click'", $html);
            $this->assertStringContainsString("name = 'email_click'", $html);
            $this->assertStringContainsString("name = 'whatsapp_click'", $html);
            $this->assertStringContainsString("var waPrefix = 'https://wa.me/' + \"6281292388805\"", $html);
        }
    }

    public function test_setiap_tautan_whatsapp_telepon_email_punya_data_cta(): void
    {
        foreach (['/', '/about', '/services', '/services/3d-printing', '/miniatur-teknik', '/industri/otomotif-motorsport', '/terima-kasih'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/<a\b[^>]*href="(https:\/\/wa\.me\/6281292388805[^"]*|tel:[^"]*|mailto:[^"]*)"[^>]*>/', $html, $links);

            $this->assertNotEmpty($links[0], $url);

            foreach ($links[0] as $tag) {
                $this->assertMatchesRegularExpression('/data-cta="[a-z-]+"/', $tag, $url.' → '.$tag);
            }
        }

        $html = $this->get('/')->getContent();
        foreach (['header', 'menu', 'footer', 'sticky', 'floating'] as $location) {
            $this->assertStringContainsString('data-cta="'.$location.'"', $html, $location);
        }
    }

    /* ------------------------------------------------------- 3. sign_up */

    public function test_sign_up_dikirim_sekali_setelah_registrasi(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Auth/RegisteredUserController.php'));
        $this->assertStringContainsString("->with('datalayer', [['event' => 'sign_up', 'method' => 'email']])", $controller);

        // Flash dari registrasi tampil di halaman login berikutnya, sekali saja:
        // tiru pengalihannya lewat route uji yang sama persis.
        \Illuminate\Support\Facades\Route::middleware('web')->get('/_uji-sign-up', fn () => redirect()
            ->route('login')
            ->with('datalayer', [['event' => 'sign_up', 'method' => 'email']]));

        $this->followingRedirects()->get('/_uji-sign-up')
            ->assertSee('window.nusamaTrack("sign_up", {"method":"email"})', false);

        $this->get(route('login'))->assertDontSee('window.nusamaTrack("sign_up"', false);
    }

    /* -------------------------------------------- 5 & 8. quote_submitted */

    public function test_respons_quote_membawa_data_untuk_quote_submitted(): void
    {
        Storage::fake('local');
        $customer = User::factory()->create(['email' => 'Rina@Contoh.test', 'phone' => '0812 1111 2222']);

        $response = $this->actingAs($customer)->postJson(route('quotations.store'), [
            'quantity' => 1,
            'model' => UploadedFile::fake()->createWithContent('bracket.stl', 'solid test'),
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'model_volume_cm3' => 50,
            'analysis_status' => 'ready',
            'analysis' => json_encode([]),
            'model_stats' => json_encode(['dimensions' => ['x' => 20, 'y' => 20, 'z' => 20]]),
        ])->assertCreated();

        $response->assertJson([
            'model_count' => 1,
            'guest' => false,
            'user_data' => ['email' => 'rina@contoh.test', 'phone_number' => '+6281211112222'],
        ]);
        $this->assertNotEmpty($response->json('tracking_number'));
        $this->assertStringNotContainsString('rina', strtolower((string) $response->json('thanks_url')));

        $js = file_get_contents(resource_path('js/modules/quotation-form.js'));
        $this->assertStringContainsString("window.nusamaTrack('quote_submitted', params)", $js);
    }

    public function test_quote_gagal_tidak_memicu_quote_submitted(): void
    {
        $js = file_get_contents(resource_path('js/modules/quotation-form.js'));

        // Dipanggil setelah semua cabang gagal (401/419/422/!ok) sudah return.
        $failPos = strrpos($js, "showAlert('Permintaan gagal dikirim.");
        $trackPos = strpos($js, 'trackQuoteSubmitted(data');
        $this->assertNotFalse($failPos);
        $this->assertGreaterThan($failPos, $trackPos);
    }

    /* ------------------------------------------------------ 6. file_upload */

    public function test_file_upload_hanya_jumlah_dan_tipe(): void
    {
        $js = file_get_contents(resource_path('js/modules/model-workspace.js'));

        $this->assertStringContainsString("window.nusamaTrack('file_upload', params)", $js);
        $this->assertStringContainsString('file_count: analyzed.length', $js);
        $this->assertStringContainsString("file_types: [...new Set(analyzed.filter(Boolean))].join(',')", $js);
        // Yang dikumpulkan hanya ekstensi, bukan nama berkas.
        $this->assertStringContainsString('analyzed.push(extensionOf(file.name));', $js);
    }

    /* --------------------------------------------- 8. generate_lead + user_data */

    public function test_generate_lead_membawa_lokasi_layanan_dan_user_data_tanpa_url(): void
    {
        Notification::fake();
        Storage::fake('local');

        $redirect = $this->post(route('leads.store'), [
            'form' => 'kontak',
            'form_started' => Crypt::encryptString((string) now()->subMinute()->timestamp),
            'name' => 'Budi',
            'email' => 'Budi@Contoh.test',
            'whatsapp' => '0812 3333 4444',
            'service' => 'Reverse Engineering',
            'notes' => 'Butuh RE.',
            'page_url' => 'http://localhost/industri/otomotif-motorsport',
        ])->assertRedirect()->headers->get('Location');

        $this->assertStringNotContainsString('budi', strtolower($redirect));
        $this->assertStringNotContainsString('6281233334444', $redirect);

        $this->get($redirect)
            ->assertSee('"form_location":"industri-otomotif-motorsport"', false)
            ->assertSee('"service":"Reverse Engineering"', false)
            ->assertSee('"user_data":{"email":"budi@contoh.test","phone_number":"+6281233334444"}', false);
    }

    public function test_form_landing_layanan_mengirim_generate_lead_setelah_tersimpan(): void
    {
        $this->get('/services/reverse-engineering')
            ->assertSee("window.nusamaTrack('generate_lead'", false)
            ->assertSee("if (response.status !== 201", false);
    }

    /* ------------------------------------------------ 7. Consent Mode v2 */

    public function test_consent_default_denied_sebelum_tag_google(): void
    {
        foreach ([
            $this->get('/')->getContent(),
            $this->get(route('register'))->getContent(),
            $this->actingAs(User::factory()->create())->get(route('dashboard'))->getContent(),
        ] as $html) {
            $consent = strpos($html, "gtag('consent', 'default'");
            $this->assertNotFalse($consent);
            $this->assertLessThan(strpos($html, "gtag('config'"), $consent);

            foreach (['ad_storage', 'analytics_storage', 'ad_user_data', 'ad_personalization'] as $type) {
                $this->assertMatchesRegularExpression("/{$type}: 'denied'/", $html, $type);
            }

            $this->assertStringContainsString('data-cookie-banner', $html);
        }
    }

    public function test_banner_dua_bahasa_dan_dapat_dibuka_lagi(): void
    {
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('Kami menghargai privasi Anda', $html);
        $this->assertStringContainsString('We value your privacy', $html);
        foreach (['Terima Semua', 'Tolak', 'Atur Pilihan', 'Accept All', 'Reject'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringContainsString("var COOKIE = 'nusama3d_consent'", $html);
        $this->assertStringContainsString('data-cookie-settings', $html);

        // Banner tidak tampil di dashboard pengelola (tanpa tag Google).
        $this->assertStringNotContainsString(
            'data-cookie-banner',
            $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->getContent()
        );
    }

    public function test_gtm_dimuat_setelah_consent_bila_diisi(): void
    {
        $this->assertStringNotContainsString('gtm.js', $this->get('/')->getContent());

        config(['services.google_tag_manager.id' => 'GTM-TEST123']);
        $html = $this->get('/')->getContent();

        $this->assertStringContainsString('gtm.js?id=', $html);
        $this->assertStringContainsString('"GTM-TEST123"', $html);
        $this->assertLessThan(strpos($html, 'gtm.js?id='), strpos($html, "gtag('consent', 'default'"));
    }
}
