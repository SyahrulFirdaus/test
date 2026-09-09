<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Database\Seeders\ClientSeeder;
use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\TechnologySeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CompanyProfileSeeder::class,
            ServiceSeeder::class,
            TechnologySeeder::class,
            ClientSeeder::class,
            TestimonialSeeder::class,
        ]);
    }

    public function test_home_menampilkan_logo_klien(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Dipercaya oleh', false);

        foreach (['eFishery', 'Pindad', 'Mikuni', 'FANUC', 'Dharma Group'] as $client) {
            $response->assertSee('Logo '.$client, false);
        }

        $response->assertSee('images/clients/efishery.png', false);
    }

    public function test_home_menampilkan_testimoni_apa_adanya(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('Rachmansyah')
            ->assertSee('Mochammad Dwiyan Robiansyah')
            ->assertSee('Google Review');

        // Kutipan harus tampil persis seperti aslinya, termasuk ejaan penulisnya.
        $response->assertSee('padahal setau saya untuk bahan nylon itu yang paling sulit', false);
        $response->assertSee('hasil presisi dan detail , harga juga murah , respon cepat', false);

        // Rata-rata dihitung dari ulasan yang ditampilkan, bukan klaim total.
        $response->assertSee('10 ulasan', false);
    }

    public function test_setiap_menu_utama_dapat_diakses(): void
    {
        foreach (['home', 'services', 'technologies', 'models', 'about', 'tracking.index'] as $name) {
            $this->get(route($name))->assertOk();
        }
    }

    public function test_halaman_services_menampilkan_seluruh_layanan(): void
    {
        $response = $this->get(route('services'));

        foreach (['3D Printing Services', 'Product Development Service', 'Reverse Engineering', '3D Design', '3D Scanning', 'Paint &amp; Finishing'] as $title) {
            $response->assertSee($title, false);
        }
    }

    public function test_halaman_technologies_menampilkan_empat_teknologi(): void
    {
        $response = $this->get(route('technologies'));

        foreach (['FDM', 'SLA', 'MJF', 'SLM'] as $code) {
            $response->assertSee($code);
        }
    }

    public function test_halaman_about_menampilkan_visi_misi_dan_kontak(): void
    {
        $this->get(route('about'))
            ->assertSee('Visi')
            ->assertSee('Misi')
            ->assertSee('Hubungi Kami')
            ->assertSee('id="kontak"', false);
    }

    public function test_halaman_3d_models_menyediakan_area_unggah_dan_daftar_model(): void
    {
        // Halaman ini kini hanya berisi area unggah dan daftar model. Viewer 3D
        // dibuka di tab tersendiri, tetapi markup cardnya tetap ada sebagai
        // template yang dipakai memproses model di luar layar.
        $this->get(route('models'))
            ->assertSee('data-dropzone', false)
            ->assertSee('data-file-input', false)
            ->assertSee('data-model-list', false)
            ->assertSee('data-printer-template', false)
            ->assertSee('data-headless-host', false)
            ->assertSee('data-card-canvas', false)
            // Kelima format yang diterima ikut dipasang pada dialog pemilih berkas.
            ->assertSee('accept=".stl,.stp,.step,.obj,.3mf"', false)
            ->assertSee('File Types:')
            ->assertSee('STL, STP, STEP, OBJ, 3MF');
    }

    public function test_menu_lama_cek_barang_diarahkan_ke_3d_models(): void
    {
        // Tautan lama yang terlanjur tersebar tetap sampai ke tujuannya.
        $this->get('/cek-barang')->assertRedirect('/3d-models');
        $this->get('/cek-barang/viewer')->assertRedirect('/3d-models/viewer');
    }

    public function test_navbar_memuat_top_bar_tombol_aksi_dan_menu_support_us(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('3D Printing Service &amp; Engineering Solutions', false)
            ->assertSee('Order Now')
            ->assertSee('Sign In')
            ->assertSee('Support Us')
            // Registrasi tidak lagi dipajang di navbar.
            ->assertDontSee('>Register<', false);

        // Order Now menuju halaman 3D Models.
        $response->assertSee('href="'.route('models').'" class="nav-cta-outline"', false);

        // Halaman itu tidak lagi menjadi butir menu tersendiri di navbar.
        $navbar = str($response->getContent())->between('<header', '</header>')->toString();
        $this->assertStringNotContainsString('3D Models', $navbar);
    }

    public function test_hero_home_memuat_carousel_empat_video(): void
    {
        $response = $this->get(route('home'));

        $response->assertSee('data-hero-carousel', false)
            ->assertSee('data-hero-prev', false)
            ->assertSee('data-hero-next', false);

        foreach (['FDM Printing', 'Resin SLA', 'Finishing', 'Workshop Kami'] as $title) {
            $response->assertSee($title);
        }

        foreach (['fdm-printing.mp4', 'resin-printing.mp4', 'post-processing.mp4', 'workshop.mp4'] as $video) {
            $response->assertSee('videos/'.$video, false);
        }
    }

    public function test_menu_support_us_menampilkan_kontak_perusahaan(): void
    {
        $company = CompanyProfile::current();

        $this->get(route('home'))
            ->assertSee('WhatsApp')
            ->assertSee('Email')
            ->assertSee($company->email);
    }

    public function test_label_kelayakan_cetak_tidak_ditampilkan_ke_pengunjung(): void
    {
        // Status analisis tetap dihitung dan dikirim ke dashboard admin, tetapi
        // labelnya tidak lagi dipajang di halaman pelanggan.
        foreach ([route('models'), route('models.viewer')] as $url) {
            $response = $this->get($url);

            $response->assertDontSee('Need Improvement')
                ->assertDontSee('Perlu Perbaikan')
                ->assertDontSee('Ready to Print')
                ->assertDontSee('data-analysis-badge', false)
                ->assertDontSee('Analisis Kelayakan Cetak');
        }
    }

    public function test_pengunjung_tanpa_akun_tidak_melihat_harga(): void
    {
        $response = $this->get(route('models'));

        // Yang tetap tersedia: unggah, pratinjau, dan ringkasan teknis.
        $response->assertSee('data-dropzone', false)
            ->assertSee('data-model-list', false)
            ->assertSee('Total Model')
            ->assertSee('Estimasi Lead Time');

        // Ringkasan penawaran tidak lagi memuat kolom mesin & material; berat
        // hanya tersisa pada estimasi per model, tempat materialnya dipilih.
        $response->assertDontSee('Mesin &amp; Material', false)
            ->assertDontSee('Total Estimasi Waktu');

        // Yang ditahan: seluruh angka dan tombol yang bersifat komersial.
        $response->assertDontSee('Total Estimasi Biaya')
            ->assertDontSee('Estimasi Biaya')
            ->assertDontSee('data-open-quotation', false);

        // Ajakan masuk beserta kedua tombolnya.
        $response->assertSee('Login untuk melihat estimasi harga dan membuat penawaran.')
            ->assertSee('Sign In')
            ->assertSee('Daftar Akun');
    }

    public function test_pengguna_yang_sudah_masuk_melihat_harga_dan_tombol_penawaran(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('models'));

        $response->assertSee('Total Estimasi Biaya')
            ->assertSee('Estimasi Biaya')
            ->assertSee('data-open-quotation', false)
            ->assertDontSee('Login untuk melihat estimasi harga dan membuat penawaran.');
    }

    public function test_halaman_viewer_3d_dapat_dibuka_tanpa_login(): void
    {
        // Model dipanggil JavaScript dari penyimpanan browser, jadi halamannya
        // sendiri tidak membutuhkan parameter apa pun di server.
        $this->get(route('models.viewer'))
            ->assertOk()
            ->assertSee('data-model-detail', false)
            ->assertSee('data-card-canvas', false)
            ->assertSee('Viewer 3D');
    }

    public function test_sitemap_memuat_seluruh_halaman(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml');

        foreach (['home', 'services', 'technologies', 'models', 'about', 'tracking.index'] as $name) {
            $response->assertSee(route($name), false);
        }
    }
}
