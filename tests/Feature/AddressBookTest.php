<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use App\Services\AddressBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Buku alamat pengiriman di dashboard pelanggan, beserta wilayah bertingkatnya.
 */
class AddressBookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Province $province;

    private Regency $regency;

    private District $district;

    private Village $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Pengujian memakai potongan kecil daftar wilayah — Jawa Barat lengkap
        // beserta kecamatan/kelurahan Bandung dan Cimahi, ditambah Kota Medan
        // dan Kabupaten Sleman sebagai pembanding lintas provinsi. Isinya
        // dipotong langsung dari berkas aslinya, jadi tetap data resmi.
        //
        // Mengimpor seluruh 91.600 baris pada setiap pengujian akan menambah
        // sekitar tiga detik per pengujian tanpa menguji apa pun yang berbeda;
        // kelengkapan berkas aslinya diperiksa tersendiri di
        // test_berkas_data_wilayah_lengkap().
        Artisan::call('wilayah:import', ['file' => base_path('tests/fixtures/wilayah-test.csv')]);

        $this->user = User::factory()->create(['name' => 'Andi Saputra', 'phone' => '081234567890']);

        // Jawa Barat -> Kota Bandung -> Coblong -> Dago
        $this->province = Province::where('name', 'Jawa Barat')->sole();
        $this->regency = Regency::where('province_id', $this->province->id)->where('name', 'Kota Bandung')->sole();
        $this->district = District::where('regency_id', $this->regency->id)->where('name', 'Coblong')->sole();
        $this->village = Village::where('district_id', $this->district->id)->where('name', 'Dago')->sole();
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Rumah',
            'recipient_name' => 'Andi Saputra',
            'recipient_phone' => '081234567890',
            'province_id' => $this->province->id,
            'regency_id' => $this->regency->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'postal_code' => '40135',
            'detail' => 'Jl. Ir. H. Juanda No. 12, RT 01 RW 05',
        ], $overrides);
    }

    /* ---------------------------------------------------------- wilayah --- */

    /**
     * Kelengkapan berkas data wilayah yang dikirim bersama aplikasi.
     *
     * Diperiksa langsung dari berkasnya, bukan setelah diimpor, supaya
     * pengujian lain cukup memakai potongan kecil dan tetap cepat.
     */
    public function test_berkas_data_wilayah_lengkap(): void
    {
        $levels = [];

        $handle = fopen(database_path('data/wilayah.csv'), 'r');
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $level = substr_count((string) $row[0], '.') + 1;
            $levels[$level] = ($levels[$level] ?? 0) + 1;
        }

        fclose($handle);

        // Sesuai Kepmendagri No 300.2.2-2138 Tahun 2025.
        $this->assertSame(38, $levels[1], 'Jumlah provinsi tidak sesuai.');
        $this->assertSame(514, $levels[2], 'Jumlah kabupaten/kota tidak sesuai.');
        $this->assertSame(7285, $levels[3], 'Jumlah kecamatan tidak sesuai.');
        $this->assertSame(83762, $levels[4], 'Jumlah kelurahan/desa tidak sesuai.');
    }

    public function test_impor_menyusun_relasi_antar_tingkat(): void
    {
        $this->assertSame(3, Province::count());
        $this->assertSame(29, Regency::count());
        $this->assertSame($this->province->id, $this->regency->province_id);
        $this->assertSame($this->regency->id, $this->district->regency_id);
        $this->assertSame($this->district->id, $this->village->district_id);
    }

    public function test_rantai_wilayah_tersambung_benar(): void
    {
        // Contoh yang diminta: Jawa Barat -> Kota Cimahi -> Cimahi Selatan.
        $cimahi = Regency::where('name', 'Kota Cimahi')->sole();

        $this->assertSame('Jawa Barat', $cimahi->province->name);
        $this->assertEqualsCanonicalizing(
            ['Cimahi Selatan', 'Cimahi Tengah', 'Cimahi Utara'],
            $cimahi->districts->pluck('name')->all(),
        );

        $selatan = $cimahi->districts->firstWhere('name', 'Cimahi Selatan');

        $this->assertEqualsCanonicalizing(
            ['Cibeber', 'Cibeureum', 'Leuwigajah', 'Melong', 'Utama'],
            $selatan->villages->pluck('name')->all(),
        );

        // Kode wilayah resmi menjadi id, jadi induknya terbaca dari angkanya.
        $this->assertSame(32, $cimahi->province_id);
        $this->assertSame(3277, $cimahi->id);
        $this->assertSame(327701, $selatan->id);
    }

    /* ------------------------------------------- dropdown bertingkat --- */

    public function test_endpoint_wilayah_hanya_mengembalikan_anak_dari_induknya(): void
    {
        $cimahi = Regency::where('name', 'Kota Cimahi')->sole();

        $regencies = $this->actingAs($this->user)
            ->getJson(route('regions.regencies', ['province' => $this->province->id]))
            ->assertOk()
            ->json();

        $this->assertCount(27, $regencies);
        $this->assertContains('Kota Cimahi', array_column($regencies, 'name'));
        // Kota di provinsi lain tidak boleh ikut muncul.
        $this->assertNotContains('Kota Medan', array_column($regencies, 'name'));

        $districts = $this->actingAs($this->user)
            ->getJson(route('regions.districts', ['regency' => $cimahi->id]))
            ->assertOk()
            ->json();

        $this->assertSame(['Cimahi Selatan', 'Cimahi Tengah', 'Cimahi Utara'], array_column($districts, 'name'));

        $selatan = District::where('regency_id', $cimahi->id)->where('name', 'Cimahi Selatan')->sole();

        $villages = $this->actingAs($this->user)
            ->getJson(route('regions.villages', ['district' => $selatan->id]))
            ->assertOk()
            ->json();

        $this->assertSame(['Cibeber', 'Cibeureum', 'Leuwigajah', 'Melong', 'Utama'], array_column($villages, 'name'));
    }

    public function test_endpoint_wilayah_menuntut_login(): void
    {
        // Permintaan JSON dijawab 401, bukan diarahkan ke halaman masuk.
        $this->getJson(route('regions.regencies', ['province' => $this->province->id]))
            ->assertUnauthorized();

        $this->get(route('regions.regencies', ['province' => $this->province->id]))
            ->assertRedirect(route('login'));
    }

    public function test_formulir_hanya_memuat_provinsi_dan_mengunci_tingkat_berikutnya(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('dashboard.addresses.create'))
            ->assertOk();

        $response->assertSee('Jawa Barat')
            ->assertSee('Pilih provinsi terlebih dahulu')
            ->assertSee('Pilih kota/kabupaten terlebih dahulu')
            ->assertSee('Pilih kecamatan terlebih dahulu');

        // Daftar kelurahan se-Indonesia tidak ikut dikirim bersama halaman.
        $response->assertDontSee('Leuwigajah');
    }

    public function test_formulir_ubah_memuat_wilayah_yang_sudah_terpilih(): void
    {
        $address = app(AddressBook::class)->create($this->user, $this->payload());

        $this->actingAs($this->user)
            ->get(route('dashboard.addresses.edit', $address))
            ->assertOk()
            ->assertSee('Jawa Barat')
            ->assertSee('Kota Bandung')
            ->assertSee('Coblong')
            ->assertSee('Dago');
    }

    /* ------------------------------------------------------------- crud --- */

    public function test_pelanggan_dapat_menambah_alamat(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.addresses.store'), $this->payload())
            ->assertRedirect(route('dashboard.addresses.index'))
            ->assertSessionHas('status');

        $address = Address::sole();

        $this->assertSame($this->user->id, $address->user_id);
        $this->assertSame($this->district->id, $address->district_id);
        $this->assertSame($this->village->id, $address->village_id);
        // Alamat pertama otomatis menjadi alamat utama.
        $this->assertTrue($address->is_default);
        $this->assertTrue($address->isComplete());
    }

    public function test_seluruh_bagian_alamat_wajib_diisi(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.addresses.store'), [])
            ->assertSessionHasErrors([
                'label', 'recipient_name', 'recipient_phone',
                'province_id', 'regency_id', 'district_id', 'village_id',
                'postal_code', 'detail',
            ]);

        $this->assertSame(0, Address::count());
    }

    public function test_kode_pos_diperiksa(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.addresses.store'), $this->payload(['postal_code' => '401']))
            ->assertSessionHasErrors('postal_code');
    }

    /* -------------------------------------------- validasi relasi wilayah --- */

    public function test_kota_harus_berada_di_provinsi_yang_dipilih(): void
    {
        $medan = Regency::where('name', 'Kota Medan')->sole();

        $this->actingAs($this->user)
            ->post(route('dashboard.addresses.store'), $this->payload(['regency_id' => $medan->id]))
            ->assertSessionHasErrors('regency_id');

        $this->assertSame(0, Address::count());
    }

    public function test_kecamatan_harus_berada_di_kota_yang_dipilih(): void
    {
        $cimahiSelatan = District::whereRelation('regency', 'name', 'Kota Cimahi')
            ->where('name', 'Cimahi Selatan')
            ->sole();

        // Kota Bandung + Cimahi Selatan adalah gabungan yang mustahil.
        $this->actingAs($this->user)
            ->post(route('dashboard.addresses.store'), $this->payload(['district_id' => $cimahiSelatan->id]))
            ->assertSessionHasErrors('district_id');

        $this->assertSame(0, Address::count());
    }

    public function test_kelurahan_harus_berada_di_kecamatan_yang_dipilih(): void
    {
        $melong = Village::whereRelation('district', 'name', 'Cimahi Selatan')
            ->where('name', 'Melong')
            ->sole();

        $this->actingAs($this->user)
            ->post(route('dashboard.addresses.store'), $this->payload(['village_id' => $melong->id]))
            ->assertSessionHasErrors('village_id');

        $this->assertSame(0, Address::count());
    }

    public function test_alamat_dapat_diubah_dan_dihapus(): void
    {
        $this->actingAs($this->user)->post(route('dashboard.addresses.store'), $this->payload());
        $this->actingAs($this->user)->post(route('dashboard.addresses.store'), $this->payload(['label' => 'Kantor']));

        $kantor = Address::where('label', 'Kantor')->sole();

        $sukajadi = District::where('regency_id', $this->regency->id)->where('name', 'Sukajadi')->sole();
        $sukawarna = Village::where('district_id', $sukajadi->id)->first();

        $this->actingAs($this->user)
            ->patch(route('dashboard.addresses.update', $kantor), $this->payload([
                'label' => 'Kantor Pusat',
                'district_id' => $sukajadi->id,
                'village_id' => $sukawarna->id,
            ]))
            ->assertRedirect(route('dashboard.addresses.index'));

        $this->assertSame('Kantor Pusat', $kantor->fresh()->label);
        $this->assertSame($sukajadi->id, $kantor->fresh()->district_id);

        $this->actingAs($this->user)
            ->delete(route('dashboard.addresses.destroy', $kantor))
            ->assertRedirect(route('dashboard.addresses.index'));

        $this->assertSame(1, Address::count());
    }

    public function test_alamat_akun_lain_tidak_dapat_disentuh(): void
    {
        $orangLain = User::factory()->create();
        $address = app(AddressBook::class)->create($orangLain, $this->payload());

        $this->actingAs($this->user)->get(route('dashboard.addresses.edit', $address))->assertNotFound();
        $this->actingAs($this->user)->patch(route('dashboard.addresses.update', $address), $this->payload())->assertNotFound();
        $this->actingAs($this->user)->delete(route('dashboard.addresses.destroy', $address))->assertNotFound();
        $this->actingAs($this->user)->post(route('dashboard.addresses.default', $address))->assertNotFound();
    }

    /* ----------------------------------------------------- alamat utama --- */

    public function test_hanya_ada_satu_alamat_utama(): void
    {
        $book = app(AddressBook::class);

        $rumah = $book->create($this->user, $this->payload());
        $kantor = $book->create($this->user, $this->payload(['label' => 'Kantor']));

        $this->assertTrue($rumah->fresh()->is_default);
        $this->assertFalse($kantor->fresh()->is_default);

        $this->actingAs($this->user)
            ->post(route('dashboard.addresses.default', $kantor))
            ->assertRedirect(route('dashboard.addresses.index'));

        $this->assertFalse($rumah->fresh()->is_default);
        $this->assertTrue($kantor->fresh()->is_default);
    }

    public function test_menghapus_alamat_utama_menunjuk_penggantinya(): void
    {
        $book = app(AddressBook::class);

        $rumah = $book->create($this->user, $this->payload());
        $kantor = $book->create($this->user, $this->payload(['label' => 'Kantor']));

        $book->delete($rumah);

        // Akun yang masih punya alamat tidak boleh berakhir tanpa alamat utama.
        $this->assertTrue($kantor->fresh()->is_default);
    }

    public function test_alamat_utama_dicerminkan_ke_akun(): void
    {
        app(AddressBook::class)->create($this->user, $this->payload());

        $this->user->refresh();

        // Kolom alamat pada akun tetap terisi supaya pencarian pengguna dan
        // halaman admin yang sudah ada berjalan tanpa perubahan.
        $this->assertSame('Kota Bandung', $this->user->city);
        $this->assertSame('40135', $this->user->postal_code);
        $this->assertStringContainsString('Jl. Ir. H. Juanda', $this->user->address);
        $this->assertStringContainsString('Dago, Coblong, Kota Bandung, Jawa Barat', $this->user->address);
    }

    /* --------------------------------------------------------- tampilan --- */

    public function test_menu_alamat_muncul_di_dashboard_pelanggan(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('dashboard.addresses.index'), false);
    }

    public function test_halaman_alamat_menampilkan_daftar_dan_penanda_utama(): void
    {
        app(AddressBook::class)->create($this->user, $this->payload());

        $this->actingAs($this->user)
            ->get(route('dashboard.addresses.index'))
            ->assertOk()
            ->assertSee('Alamat Pengiriman')
            ->assertSee('Rumah')
            ->assertSee('Utama')
            ->assertSee('Dago, Coblong, Kota Bandung, Jawa Barat 40135');
    }

    public function test_profil_tidak_lagi_menyunting_alamat(): void
    {
        app(AddressBook::class)->create($this->user, $this->payload());

        $this->actingAs($this->user)
            ->get(route('dashboard.profile.edit'))
            ->assertOk()
            ->assertSee('Alamat Utama')
            ->assertSee('Kelola Alamat')
            ->assertDontSee('name="address"', false);

        // Kiriman yang tetap memuat alamat diabaikan, bukan menimpa buku alamat.
        $this->actingAs($this->user)
            ->patch(route('dashboard.profile.update'), [
                'name' => 'Andi Baru',
                'email' => $this->user->email,
                'phone' => '081298765432',
                'city' => 'Surabaya',
                'address' => 'Alamat selundupan',
            ])->assertSessionHas('status');

        $this->user->refresh();

        $this->assertSame('Andi Baru', $this->user->name);
        $this->assertSame('Kota Bandung', $this->user->city);
        $this->assertStringNotContainsString('selundupan', $this->user->address);
    }

    /* ------------------------------------------- modal minta penawaran --- */

    public function test_modal_penawaran_menampilkan_alamat_dari_buku_alamat(): void
    {
        $book = app(AddressBook::class);
        $book->create($this->user, $this->payload());
        $book->create($this->user, $this->payload([
            'label' => 'Kantor',
            'recipient_name' => 'Bagian Penerimaan',
            'detail' => 'Gedung Wisma Lantai 8',
        ]));

        $response = $this->actingAs($this->user)->get(route('models'))->assertOk();

        $response->assertSee('Alamat Pengiriman')
            ->assertSee('name="address_id"', false)
            ->assertSee('Rumah')
            ->assertSee('Kantor')
            ->assertSee('Bagian Penerimaan')
            ->assertSee('Gedung Wisma Lantai 8')
            ->assertSee('Dago, Coblong, Kota Bandung, Jawa Barat 40135');

        $default = Address::where('is_default', true)->sole();
        $response->assertSee('value="'.$default->id.'"', false);
    }

    public function test_modal_penawaran_mengingatkan_bila_belum_ada_alamat(): void
    {
        $this->actingAs($this->user)
            ->get(route('models'))
            ->assertOk()
            ->assertSee('Alamat Pengiriman')
            ->assertSee('Anda belum menyimpan alamat pengiriman.');
    }

    public function test_pengunjung_tanpa_akun_tidak_melihat_bagian_alamat(): void
    {
        $this->get(route('models'))
            ->assertOk()
            ->assertDontSee('name="address_id"', false);
    }

    public function test_ringkasan_modal_tidak_lagi_menyebut_mesin_status_dan_berat(): void
    {
        // Ringkasan disusun quotation-form.js; yang diperiksa di sini adalah
        // berkas sumbernya agar bagian-bagian itu tidak diam-diam kembali.
        $source = file_get_contents(resource_path('js/modules/quotation-form.js'));

        $this->assertStringNotContainsString('Satu printer untuk setiap model', $source);
        $this->assertStringNotContainsString('Perlu perbaikan', $source);
        $this->assertStringNotContainsString('printer_name', $source);
        $this->assertStringNotContainsString('Total Berat', $source);
        // "Printer Digunakan / N printer" sudah dihapus seluruhnya.
        $this->assertStringNotContainsString('Printer Digunakan', $source);
        $this->assertStringNotContainsString('} printer', $source);
        $this->assertStringNotContainsString('Printer ${index', $source);
    }

    public function test_modal_penawaran_menampilkan_identitas_terkunci_dari_profil(): void
    {
        $user = User::factory()->create([
            'name' => 'Syahrul Firdaus',
            'email' => 'syahrul@contoh.test',
            'phone' => '081234567890',
        ]);

        $response = $this->actingAs($user)->get(route('models'))->assertOk();

        // Nilainya datang dari profil akun, ditampilkan tetapi tidak dapat diubah.
        $response->assertSee('value="Syahrul Firdaus"', false)
            ->assertSee('value="syahrul@contoh.test"', false)
            ->assertSee('value="081234567890"', false)
            ->assertSee('readonly', false)
            ->assertSee('Ingin mengubah informasi ini? Silakan ubah melalui halaman Profil di Dashboard User.')
            ->assertSee('Edit Profil')
            ->assertSee(route('dashboard.profile.edit'), false);

        // Ketiganya tidak lagi dikirim sebagai isian formulir.
        $response->assertDontSee('name="name"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="whatsapp"', false);
    }

    /* ------------------------------------------------- pencocokan wilayah --- */

    public function test_kota_akun_lama_dicocokkan_walau_berkode(): void
    {
        $book = app(AddressBook::class);

        // Formulir lama menyimpan bentuk seperti "77 - Kota Cimahi".
        $this->assertSame('Kota Cimahi', $book->matchCity('77 - Kota Cimahi')?->name);
        $this->assertSame('Kota Bandung', $book->matchCity('Bandung')?->name);
        $this->assertSame('Kota Bandung', $book->matchCity('  kota   bandung ')?->name);
        $this->assertSame('Kabupaten Sleman', $book->matchCity('Sleman')?->name);
        $this->assertNull($book->matchCity('Entah Di Mana'));
    }

    public function test_alamat_pertama_dibuat_dari_data_pendaftaran(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi',
            'phone' => '08111111111',
            'city' => 'Cimahi',
            'postal_code' => '40532',
            'address' => 'Jl. Contoh No. 1',
        ]);

        $address = app(AddressBook::class)->createFromProfile($user);

        $this->assertNotNull($address);
        $this->assertTrue($address->is_default);
        $this->assertSame('Jl. Contoh No. 1', $address->detail);
        $this->assertSame('Kota Cimahi', $address->regency?->name);
        $this->assertSame('Jawa Barat', $address->province?->name);
        // Kecamatan dan kelurahan memang belum pernah ditanyakan, jadi alamatnya
        // ditandai belum lengkap untuk dilengkapi pemiliknya.
        $this->assertFalse($address->isComplete());
    }
}

