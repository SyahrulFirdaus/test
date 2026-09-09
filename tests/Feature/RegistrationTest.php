<?php

namespace Tests\Feature;

use App\Models\CustomerAnswer;
use App\Models\District;
use App\Models\Regency;
use App\Models\RegistrationQuestion;
use App\Models\User;
use App\Models\Village;
use App\Support\CustomerType;
use Database\Seeders\RegistrationQuestionSeeder;
use Database\Seeders\TechnologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pendaftaran bertahap untuk pelanggan Personal dan Business.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Pertanyaan teknologi membaca daftar teknologi yang tersedia, jadi
        // keduanya perlu ada sebelum formulir dapat diisi.
        $this->seed(TechnologySeeder::class);
        $this->seed(RegistrationQuestionSeeder::class);

        // Data perusahaan memakai dropdown wilayah bertingkat. Dipakai potongan
        // kecil daftar wilayah agar setiap pengujian tidak menunggu 91.600
        // baris diimpor; kelengkapan berkas aslinya diuji di AddressBookTest.
        Artisan::call('wilayah:import', ['file' => base_path('tests/fixtures/wilayah-test.csv')]);
    }

    /** @return array<string, string> */
    private function accountPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Andi Saputra',
            'phone' => '0812 3456 7890',
            'city' => 'Bandung',
            'postal_code' => '40123',
            'address' => 'Jl. Merdeka No. 12, Sumur Bandung',
            'email' => 'andi@contoh.test',
            // Memenuhi App\Support\PasswordPolicy: huruf kapital, angka, simbol.
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
        ], $overrides);
    }

    /**
     * Menjawab kelima pertanyaan perorangan, satu langkah per pertanyaan.
     *
     * @param  array<string, string>  $byKey
     */
    private function answerPersonalSteps(array $byKey): void
    {
        $step = 0;

        foreach ($byKey as $key => $answer) {
            $step++;

            $this->post(route('register.step.store', (string) $step), $this->answers([$key => $answer]))
                ->assertSessionHasNoErrors();
        }
    }

    /**
     * Menyusun payload jawaban dari pasangan key pertanyaan => jawaban.
     *
     * @param  array<string, string|array<int, string>>  $byKey
     * @return array<string, array<int, string|array<int, string>>>
     */
    private function answers(array $byKey): array
    {
        $questions = [];

        foreach ($byKey as $key => $value) {
            $id = RegistrationQuestion::where('key', $key)->value('id');

            $this->assertNotNull($id, "Pertanyaan {$key} tidak ditemukan.");

            $questions[$id] = $value;
        }

        return ['questions' => $questions];
    }

    private function startAs(string $type): void
    {
        $this->post(route('register.type'), ['customer_type' => $type])
            ->assertRedirect(route('register.step', 'account'));

        $this->post(route('register.step.store', 'account'), $this->accountPayload())
            ->assertSessionHasNoErrors();
    }

    /* --------------------------------------------------------- personal --- */

    public function test_pelanggan_personal_menyelesaikan_pendaftaran_dan_jawabannya_tersimpan(): void
    {
        $this->startAs(CustomerType::PERSONAL);

        $this->answerPersonalSteps([
            'personal_purpose' => 'Prototype',
            'personal_frequency' => 'Rutin',
            'personal_project_type' => 'Spare Part',
            'personal_quantity' => '6–20 pcs',
            'personal_priority' => 'Kualitas',
        ]);

        $this->get(route('register.step', 'review'))
            ->assertOk()
            ->assertSee('Ringkasan informasi')
            ->assertSee('Daftar Sekarang');

        $this->post(route('register.store'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Registrasi berhasil. Silakan login menggunakan email dan password Anda.');

        $user = User::sole();

        $this->assertSame(CustomerType::PERSONAL, $user->customer_type);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertTrue(Hash::check('Rahasia123!', $user->password));
        $this->assertSame(5, $user->customerAnswers()->count());

        $this->assertSame(
            'Spare Part',
            $user->customerAnswers()->forQuestionKey('personal_project_type')->value('answer'),
        );

        // Dashboard baru terbuka setelah benar-benar masuk.
        $this->assertGuest();
    }

    public function test_jawaban_personal_wajib_diisi(): void
    {
        $this->startAs(CustomerType::PERSONAL);

        $purpose = RegistrationQuestion::where('key', 'personal_purpose')->value('id');

        $this->post(route('register.step.store', '1'), [])
            ->assertSessionHasErrors('questions.'.$purpose);

        $this->assertSame(0, User::count());
    }

    public function test_pilihan_jawaban_di_luar_daftar_ditolak_di_server(): void
    {
        $this->startAs(CustomerType::PERSONAL);

        $purpose = RegistrationQuestion::where('key', 'personal_purpose')->value('id');

        $this->post(route('register.step.store', '1'), $this->answers([
            'personal_purpose' => 'Jawaban karangan sendiri',
        ]))->assertSessionHasErrors('questions.'.$purpose);
    }

    public function test_pertanyaan_personal_ditampilkan_satu_per_halaman(): void
    {
        $this->startAs(CustomerType::PERSONAL);

        $expected = [
            '1' => ['Tujuan Penggunaan', 'Apa tujuan Anda menggunakan layanan 3D Printing?'],
            '2' => ['Frekuensi Penggunaan', 'Seberapa sering Anda menggunakan layanan 3D Printing?'],
            '3' => ['Jenis Project', 'Jenis project apa yang biasanya Anda buat?'],
            '4' => ['Jumlah Kebutuhan', 'Berapa jumlah kebutuhan Anda biasanya?'],
            '5' => ['Prioritas Anda', 'Apa yang paling penting bagi Anda?'],
        ];

        $answers = [
            '1' => ['personal_purpose' => 'Prototype'],
            '2' => ['personal_frequency' => 'Rutin'],
            '3' => ['personal_project_type' => 'Spare Part'],
            '4' => ['personal_quantity' => '6–20 pcs'],
            '5' => ['personal_priority' => 'Kualitas'],
        ];

        foreach ($expected as $step => [$label, $question]) {
            $response = $this->get(route('register.step', $step))
                ->assertOk()
                ->assertSee($label)
                ->assertSee('Step '.$step.' dari 5')
                ->assertSee($question);

            // Hanya satu pertanyaan per halaman: pertanyaan langkah lain tidak
            // boleh ikut muncul.
            foreach ($expected as $otherStep => [, $otherQuestion]) {
                if ($otherStep !== $step) {
                    $response->assertDontSee($otherQuestion);
                }
            }

            $this->post(route('register.step.store', $step), $this->answers($answers[$step]))
                ->assertSessionHasNoErrors();
        }
    }

    /* --------------------------------------------------------- business --- */

    /** Data perusahaan yang sah, memakai Jawa Barat → Kota Cimahi → Cimahi Selatan. */
    private function companyPayload(array $overrides = []): array
    {
        $regency = Regency::where('name', 'Kota Cimahi')->sole();
        $district = District::where('regency_id', $regency->id)->where('name', 'Cimahi Selatan')->sole();
        $village = Village::where('district_id', $district->id)->where('name', 'Melong')->sole();

        return array_merge([
            'company_name' => 'PT ABC Manufacturing',
            'pic_name' => 'John Doe',
            'position' => 'Engineering Manager',
            'phone' => '022 1234567',
            'email' => 'procurement@abc.co.id',
            'website' => 'https://abc.co.id',
            'industry' => 'Manufacturing',
            'address' => 'Kawasan Industri Blok C No. 12',
            'province_id' => $regency->province_id,
            'regency_id' => $regency->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
            'postal_code' => '40534',
        ], $overrides);
    }

    /** Jawaban kebutuhan bisnis yang memenuhi seluruh pertanyaan wajib. */
    private function businessAnswers(array $overrides = []): array
    {
        return $this->answers(array_merge([
            'b_main_need' => 'Prototype',
            'b_frequency' => '5–10 kali per bulan',
            'b_production_type' => 'Keduanya',
            'b_timeline_priority' => 'Cukup mendesak',
            'b_deadline' => '1–2 minggu',
        ], $overrides));
    }

    /** Menyelesaikan langkah Data Kontak dan Data Perusahaan. */
    private function startAsBusiness(array $company = []): void
    {
        $this->post(route('register.type'), ['customer_type' => CustomerType::BUSINESS])
            ->assertRedirect(route('register.step', 'account'));

        // Pelanggan perusahaan tidak diminta alamat pada langkah ini.
        $this->post(route('register.step.store', 'account'), $this->accountPayload([
            'city' => null, 'postal_code' => null, 'address' => null,
        ]))->assertSessionHasNoErrors();

        $this->post(route('register.step.store', 'company'), $this->companyPayload($company))
            ->assertSessionHasNoErrors();
    }

    public function test_alur_business_empat_langkah_dengan_data_perusahaan(): void
    {
        $this->post(route('register.type'), ['customer_type' => CustomerType::BUSINESS]);

        $this->get(route('register.step', 'account'))
            ->assertOk()
            ->assertSee('Data Kontak')
            ->assertSee('Step 1 dari 4')
            ->assertSee('Nomor WhatsApp')
            // Alamat ditanyakan pada langkah Data Perusahaan, bukan di sini.
            ->assertDontSee('Kota Asal');

        $this->post(route('register.step.store', 'account'), $this->accountPayload([
            'city' => null, 'postal_code' => null, 'address' => null,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('register.step', 'company'));

        $this->get(route('register.step', 'company'))
            ->assertOk()
            ->assertSee('Data Perusahaan')
            ->assertSee('Step 2 dari 4')
            ->assertSee('Nama PIC')
            // Daftar provinsi ikut terkirim bersama halaman.
            ->assertSee('Jawa Barat')
            ->assertSee('Pilih provinsi terlebih dahulu')
            ->assertDontSee('Daftar wilayah belum tersedia.');
    }

    public function test_langkah_kebutuhan_bisnis_terbagi_menjadi_enam_bagian(): void
    {
        $this->startAsBusiness();

        $response = $this->get(route('register.step', '1'))->assertOk();

        $response->assertSee('Kebutuhan Bisnis')
            ->assertSee('Step 3 dari 4');

        foreach ([
            'Kebutuhan 3D Printing',
            'Kebutuhan Produksi',
            'Teknologi &amp; Material',
            'Kebutuhan Finishing',
            'Timeline & Budget',
            'Kebutuhan Bisnis &amp; Procurement',
        ] as $section) {
            $response->assertSee($section, false);
        }

        // Pertanyaan opsional ditandai jelas, dan yang teknis menyediakan
        // pilihan bagi yang belum memahaminya.
        $response->assertSee('Opsional')
            ->assertSee('Belum tahu / minta rekomendasi');
    }

    public function test_hanya_lima_pertanyaan_business_yang_wajib(): void
    {
        $this->startAsBusiness();

        $wajib = ['b_main_need', 'b_frequency', 'b_production_type', 'b_timeline_priority', 'b_deadline'];

        $fields = collect($wajib)
            ->map(fn (string $key) => 'questions.'.RegistrationQuestion::where('key', $key)->value('id'))
            ->all();

        $this->post(route('register.step.store', '1'), [])
            ->assertSessionHasErrors($fields);

        // Sisanya boleh dilewati sepenuhnya.
        $this->post(route('register.step.store', '1'), $this->businessAnswers())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('register.step', 'review'));
    }

    public function test_pendaftaran_business_menyimpan_profil_jawaban_dan_alamat(): void
    {
        $this->startAsBusiness();

        $this->post(route('register.step.store', '1'), $this->businessAnswers([
            'b_technologies' => ['FDM', 'MJF'],
            'b_materials' => ['PA12'],
            'b_budget' => 'Lebih dari Rp10.000.000',
            'b_need_quotation' => 'Ya',
            'b_challenge' => 'Butuh spare part mesin yang sudah tidak diproduksi.',
        ]))->assertSessionHasNoErrors();

        $this->get(route('register.step', 'review'))
            ->assertOk()
            ->assertSee('Data Perusahaan')
            ->assertSee('PT ABC Manufacturing')
            ->assertSee('Engineering Manager')
            ->assertSee('Melong, Cimahi Selatan, Kota Cimahi, Jawa Barat')
            ->assertSee('Daftar Sekarang');

        $this->post(route('register.store'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Registrasi Business berhasil. Silakan login menggunakan email dan password Anda.');

        $user = User::sole();
        $profile = $user->businessProfile;

        $this->assertSame(CustomerType::BUSINESS, $user->customer_type);
        $this->assertSame('PT ABC Manufacturing', $profile->company_name);
        $this->assertSame('Manufacturing', $profile->industry);
        $this->assertSame('Kota Cimahi', $profile->regency->name);

        // Jawaban ganda tersimpan sebagai beberapa baris.
        $this->assertEqualsCanonicalizing(
            ['FDM', 'MJF'],
            $user->customerAnswers()->forQuestionKey('b_technologies')->pluck('answer')->all(),
        );

        // Alamat perusahaan sekaligus menjadi alamat pengiriman utama akun.
        $address = $user->defaultAddress;

        $this->assertNotNull($address);
        $this->assertSame('John Doe', $address->recipient_name);
        $this->assertSame('Melong', $address->village->name);

        $this->assertGuest();
    }

    public function test_relasi_wilayah_perusahaan_diperiksa_di_server(): void
    {
        $this->post(route('register.type'), ['customer_type' => CustomerType::BUSINESS]);
        $this->post(route('register.step.store', 'account'), $this->accountPayload([
            'city' => null, 'postal_code' => null, 'address' => null,
        ]));

        $medan = Regency::where('name', 'Kota Medan')->sole();

        $this->post(route('register.step.store', 'company'), $this->companyPayload(['regency_id' => $medan->id]))
            ->assertSessionHasErrors('regency_id');
    }

    public function test_segmen_pelanggan_ditentukan_dari_jawabannya(): void
    {
        $this->startAsBusiness();

        $this->post(route('register.step.store', '1'), $this->businessAnswers([
            'b_main_need' => 'Mass Production',
            'b_production_type' => 'Production',
        ]));

        $this->post(route('register.store'));

        // Industri manufaktur + kebutuhan produksi = pelanggan industri.
        $this->assertSame('Industrial Customer', User::sole()->businessProfile->segment);
    }

    public function test_segmen_prototype_untuk_kebutuhan_purwarupa(): void
    {
        $this->startAsBusiness(['industry' => 'Education']);

        $this->post(route('register.step.store', '1'), $this->businessAnswers([
            'b_main_need' => 'Prototype',
            'b_production_type' => 'Prototype',
        ]));

        $this->post(route('register.store'));

        $this->assertSame('Prototype Customer', User::sole()->businessProfile->segment);
    }

    public function test_data_kembali_terisi_saat_menekan_kembali(): void
    {
        $this->startAsBusiness();

        // Kembali ke langkah sebelumnya tidak menghilangkan isian.
        $this->get(route('register.step', 'company'))
            ->assertOk()
            ->assertSee('value="PT ABC Manufacturing"', false)
            ->assertSee('value="John Doe"', false)
            // Wilayah yang sudah dipilih ikut dimuat, tidak terkunci kosong.
            ->assertSee('Kota Cimahi')
            ->assertSee('Cimahi Selatan')
            ->assertSee('Melong');

        $this->get(route('register.step', 'account'))
            ->assertOk()
            ->assertSee('value="Andi Saputra"', false);
    }

    public function test_langkah_tidak_dapat_dilompati(): void
    {
        $this->post(route('register.type'), ['customer_type' => CustomerType::PERSONAL]);

        // Data akun belum diisi, jadi langkah pertanyaan belum boleh dibuka.
        $this->get(route('register.step', '1'))
            ->assertRedirect(route('register.step', 'account'));

        $this->get(route('register.step', 'review'))
            ->assertRedirect(route('register.step', 'account'));
    }

    public function test_ringkasan_yang_belum_lengkap_tidak_membuat_akun(): void
    {
        $this->startAs(CustomerType::PERSONAL);

        $this->post(route('register.store'))
            ->assertRedirect(route('register.step', '1'));

        $this->assertSame(0, User::count());
        $this->assertSame(0, CustomerAnswer::count());
    }

    public function test_mengganti_tipe_akun_membuang_jawaban_sebelumnya(): void
    {
        $this->startAs(CustomerType::PERSONAL);

        $this->answerPersonalSteps([
            'personal_purpose' => 'Hobi',
            'personal_frequency' => 'Jarang',
            'personal_project_type' => 'Miniature',
            'personal_quantity' => '1–5 pcs',
            'personal_priority' => 'Harga',
        ]);

        // Pertanyaan kedua tipe berbeda sama sekali, jadi isian lama dibuang
        // dan alurnya kembali ke langkah data akun.
        $this->post(route('register.type'), ['customer_type' => CustomerType::BUSINESS]);

        $this->get(route('register.step', '1'))
            ->assertRedirect(route('register.step', 'account'));
    }

    /* --------------------------------------------------------- tampilan --- */

    public function test_halaman_pilih_tipe_menampilkan_kedua_kartu_tanpa_istilah_b2c_b2b(): void
    {
        $response = $this->get(route('register'))->assertOk();

        $response->assertSee('Pilih tipe akun Anda')
            ->assertSee('Personal')
            ->assertSee('Business')
            ->assertSee('Untuk kebutuhan pribadi, hobi, prototype, tugas, custom object, dan kebutuhan non-perusahaan.')
            ->assertSee('Untuk kebutuhan perusahaan, engineering, produksi, prototype industri, procurement, dan kebutuhan bisnis.');

        // Istilah teknis tidak pernah dipajang kepada pelanggan.
        $response->assertDontSee('B2C')->assertDontSee('B2B');
    }

    public function test_admin_melihat_tipe_customer_dan_jawaban_pendaftaran(): void
    {
        $this->startAs(CustomerType::PERSONAL);

        $this->answerPersonalSteps([
            'personal_purpose' => 'Custom Product',
            'personal_frequency' => 'Pertama kali',
            'personal_project_type' => 'Produk Custom',
            'personal_quantity' => '1–5 pcs',
            'personal_priority' => 'Ketepatan ukuran',
        ]);

        $this->post(route('register.store'));

        $customer = User::customers()->sole();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Tipe Customer')
            ->assertSee('Personal');

        $this->actingAs($admin)
            ->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertSee('Informasi Pendaftaran')
            ->assertSee('Custom Product')
            ->assertSee('Ketepatan ukuran');
    }

    public function test_daftar_user_dapat_disaring_per_tipe_customer(): void
    {
        $personal = User::factory()->create(['customer_type' => CustomerType::PERSONAL, 'name' => 'Andi Personal']);
        $business = User::factory()->create(['customer_type' => CustomerType::BUSINESS, 'name' => 'Budi Business']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.index', ['type' => CustomerType::BUSINESS]))
            ->assertOk()
            ->assertSee($business->name)
            ->assertDontSee($personal->name);
    }

    public function test_akun_lama_tanpa_tipe_dianggap_personal(): void
    {
        // Kolom `customer_type` bawaannya `personal`, sehingga akun yang dibuat
        // sebelum fitur ini ada tetap terbaca sebagai pelanggan perorangan.
        $user = User::factory()->create();

        $this->assertSame(CustomerType::PERSONAL, $user->fresh()->customer_type);
        $this->assertFalse($user->isBusiness());
    }
}
