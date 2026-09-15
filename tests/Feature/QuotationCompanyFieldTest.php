<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\District;
use App\Models\QuotationRequest;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use App\Support\CustomerType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Nama perusahaan pada modal "Minta Penawaran".
 *
 * Akun Business tidak lagi mengetiknya: nilainya dibaca dari profil perusahaan
 * yang diisi saat pendaftaran, ditampilkan terkunci, dan ditetapkan server saat
 * penawaran disimpan. Akun Personal tidak memiliki kolom ini sama sekali.
 */
class QuotationCompanyFieldTest extends TestCase
{
    use RefreshDatabase;

    private User $business;

    private User $personal;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Alamat perusahaan memakai wilayah bertingkat. Dipakai potongan kecil
        // daftar wilayah, sama seperti RegistrationTest, agar pengujian tidak
        // menunggu seluruh daftar diimpor.
        Artisan::call('wilayah:import', ['file' => base_path('tests/fixtures/wilayah-test.csv')]);

        $this->business = User::factory()->create([
            'name' => 'David Kurnia',
            'email' => 'david@contoh.test',
            'phone' => '0812 3456 7890',
            'customer_type' => CustomerType::BUSINESS,
        ]);

        BusinessProfile::create([
            'user_id' => $this->business->id,
            'company_name' => 'PT Contoh Sejahtera',
            'pic_name' => 'David Kurnia',
            'position' => 'Engineering Manager',
            'phone' => '022 1234567',
            'email' => 'procurement@contoh.test',
            'industry' => 'Manufacturing',
            'address' => 'Kawasan Industri Blok A1',
            'postal_code' => '40532',
        ]);

        $this->personal = User::factory()->create([
            'name' => 'Andi Saputra',
            'customer_type' => CustomerType::PERSONAL,
        ]);
    }

    /**
     * Rantai wilayah yang sah untuk formulir informasi perusahaan.
     *
     * @return array<string, int>
     */
    private function region(): array
    {
        $regency = Regency::where('name', 'Kota Cimahi')->sole();
        $district = District::where('regency_id', $regency->id)->where('name', 'Cimahi Selatan')->sole();
        $village = Village::where('district_id', $district->id)->where('name', 'Melong')->sole();

        return [
            'province_id' => $regency->province_id,
            'regency_id' => $regency->id,
            'district_id' => $district->id,
            'village_id' => $village->id,
        ];
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'quantity' => 1,
            'notes' => 'Mohon warna hitam doff.',
            'model' => UploadedFile::fake()->createWithContent('bracket.stl', 'solid test'),
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'model_volume_cm3' => 120.5,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
        ], $overrides);
    }

    /* ------------------------------------------------------- tampilan --- */

    public function test_modal_business_menampilkan_nama_perusahaan_terkunci(): void
    {
        $this->actingAs($this->business)
            ->get(route('models'))
            ->assertOk()
            ->assertSee('Nama Perusahaan')
            ->assertSee('PT Contoh Sejahtera')
            ->assertSee('Ingin mengubah informasi perusahaan?')
            ->assertSee('Edit Profil');
    }

    public function test_modal_business_tidak_lagi_punya_isian_nama_perusahaan(): void
    {
        $response = $this->actingAs($this->business)->get(route('models'))->assertOk();

        // Tidak ada satu pun input bernama `company` yang dapat diketik.
        $this->assertStringNotContainsString('name="company"', $response->getContent());
        $this->assertStringNotContainsString('id="q-company"', $response->getContent());
    }

    public function test_modal_personal_tidak_menampilkan_nama_perusahaan(): void
    {
        $response = $this->actingAs($this->personal)->get(route('models'))->assertOk();

        $response->assertDontSee('Nama Perusahaan');
        $this->assertStringNotContainsString('name="company"', $response->getContent());
    }

    public function test_tombol_edit_profil_business_menuju_halaman_perusahaan(): void
    {
        $this->actingAs($this->business)
            ->get(route('models'))
            ->assertOk()
            ->assertSee(route('dashboard.company-profile.edit'), false);
    }

    /* ------------------------------------------------ penyimpanan data --- */

    public function test_penawaran_business_memakai_nama_perusahaan_dari_profil(): void
    {
        $this->actingAs($this->business)
            ->postJson(route('quotations.store'), $this->payload())
            ->assertCreated();

        $this->assertSame('PT Contoh Sejahtera', QuotationRequest::sole()->company);
    }

    public function test_nama_perusahaan_kiriman_sendiri_diabaikan(): void
    {
        // Kiriman yang disusun di luar formulir tidak boleh membuat penawaran
        // mengaku mewakili perusahaan lain.
        $this->actingAs($this->business)
            ->postJson(route('quotations.store'), $this->payload([
                'company' => 'PT Perusahaan Palsu',
            ]))
            ->assertCreated();

        $this->assertSame('PT Contoh Sejahtera', QuotationRequest::sole()->company);
    }

    public function test_penawaran_personal_tidak_menyimpan_nama_perusahaan(): void
    {
        $this->actingAs($this->personal)
            ->postJson(route('quotations.store'), $this->payload([
                'company' => 'PT Bukan Milik Saya',
            ]))
            ->assertCreated();

        $this->assertNull(QuotationRequest::sole()->company);
    }

    public function test_nama_perusahaan_mengikuti_profil_terbaru(): void
    {
        $this->business->businessProfile->update(['company_name' => 'PT Nama Baru']);

        $this->actingAs($this->business)
            ->postJson(route('quotations.store'), $this->payload())
            ->assertCreated();

        $this->assertSame('PT Nama Baru', QuotationRequest::sole()->company);
    }

    /* ------------------------------------------ halaman ubah perusahaan --- */

    public function test_business_dapat_membuka_dan_mengubah_informasi_perusahaan(): void
    {
        $this->actingAs($this->business)
            ->get(route('dashboard.company-profile.edit'))
            ->assertOk()
            ->assertSee('Informasi Perusahaan')
            ->assertSee('PT Contoh Sejahtera');

        $this->actingAs($this->business)
            ->patch(route('dashboard.company-profile.update'), [
                'company_name' => 'PT Contoh Sejahtera Abadi',
                'pic_name' => 'David Kurnia',
                'position' => 'Procurement Lead',
                'phone' => '022 7654321',
                'email' => 'procurement@contoh.test',
                'industry' => 'Automotive',
                'address' => 'Kawasan Industri Blok B2',
                'postal_code' => '40533',
                ...$this->region(),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $profile = $this->business->businessProfile()->first();

        $this->assertSame('PT Contoh Sejahtera Abadi', $profile->company_name);
        $this->assertSame('Procurement Lead', $profile->position);

        // `actingAs` memakai ulang objek User yang sama antar-permintaan,
        // sehingga relasi profil yang sempat termuat perlu disegarkan dulu.
        // Pada permintaan HTTP sungguhan hal ini tidak terjadi: tiap
        // permintaan memuat akunnya kembali dari awal.
        $this->business->refresh();

        // Penawaran berikutnya langsung memakai nama yang baru.
        $this->actingAs($this->business)
            ->postJson(route('quotations.store'), $this->payload())
            ->assertCreated();

        $this->assertSame('PT Contoh Sejahtera Abadi', QuotationRequest::sole()->company);
    }

    public function test_nama_perusahaan_wajib_diisi_saat_disunting(): void
    {
        $this->actingAs($this->business)
            ->patch(route('dashboard.company-profile.update'), [
                'company_name' => '',
                'pic_name' => 'David Kurnia',
                'position' => 'Engineering Manager',
                'phone' => '022 1234567',
                'email' => 'procurement@contoh.test',
                'industry' => 'Manufacturing',
                'address' => 'Kawasan Industri Blok A1',
                'postal_code' => '40532',
                ...$this->region(),
            ])
            ->assertSessionHasErrors('company_name');

        $this->assertSame('PT Contoh Sejahtera', $this->business->businessProfile()->first()->company_name);
    }

    public function test_akun_personal_tidak_dapat_membuka_informasi_perusahaan(): void
    {
        $this->actingAs($this->personal)
            ->get(route('dashboard.company-profile.edit'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }
}
