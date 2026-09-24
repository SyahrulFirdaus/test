<?php

namespace Tests\Feature;

use App\Models\PrintColor;
use App\Models\PrintMaterial;
use App\Models\PrintMaterialColor;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PrintEstimator;
use App\Support\MaterialColor;
use App\Support\PriceListPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Satu material, banyak warna.
 *
 * Warna tidak lagi satu daftar bersama untuk seluruh material: tiap material
 * punya daftarnya sendiri, diisi Superadmin pada form Tambah/Ubah Material.
 * PLA Plus boleh menawarkan Putih, Hitam, Merah, dan Biru sementara PETG hanya
 * Putih, Hitam, dan Abu-abu.
 *
 * Yang dijaga di sini: formulirnya, penyimpanannya, dan — yang terpenting —
 * bahwa pelanggan yang memilih Teknologi lalu Material benar-benar melihat
 * daftar warna milik material itu saja.
 */
class MaterialColorListTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private PrintTechnology $fdm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->superAdmin()->create();
        $this->fdm = PrintTechnology::where('code', 'FDM')->sole();

        // Material bawaan migrasi dibersihkan agar daftar warna yang diuji
        // tidak tercampur warna hasil pemindahan.
        PrintMaterial::query()->delete();
        $this->refreshCatalog();
    }

    /* ---------------------------------------------------------- formulir --- */

    public function test_form_material_memuat_daftar_warna_dan_tombol_tambah(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.materials.create', $this->fdm))
            ->assertOk()
            ->assertSee('Tambah Color')
            ->assertSee('name="colors[0][name]"', false)
            ->assertSee('name="colors[0][hex]"', false)
            // Baris kosong untuk tombol Tambah Color.
            ->assertSee('data-color-template', false);
    }

    /** Berlaku untuk setiap teknologi yang punya material, bukan FDM saja. */
    public function test_daftar_warna_tersedia_di_setiap_teknologi(): void
    {
        foreach (PrintTechnology::all() as $technology) {
            $this->actingAs($this->superAdmin)
                ->get(route('superadmin.price-list.materials.create', $technology))
                ->assertOk()
                ->assertSee('name="colors[0][name]"', false)
                ->assertSee('Tambah Color');
        }
    }

    public function test_form_edit_menampilkan_warna_yang_sudah_tersimpan(): void
    {
        $material = $this->material('PLA Plus', [
            ['name' => 'Putih', 'hex' => '#FFFFFF'],
            ['name' => 'Hitam', 'hex' => '#000000'],
        ]);

        $this->actingAs($this->superAdmin)
            ->get(route('superadmin.price-list.materials.edit', [$this->fdm, $material]))
            ->assertOk()
            ->assertSee('value="Putih"', false)
            ->assertSee('value="#FFFFFF"', false)
            ->assertSee('value="Hitam"', false)
            ->assertSee('value="#000000"', false);
    }

    /* --------------------------------------------------------- menyimpan --- */

    public function test_menyimpan_material_dengan_beberapa_warna(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload('PLA Plus'),
                'colors' => [
                    ['name' => 'Putih', 'hex' => '#FFFFFF'],
                    ['name' => 'Hitam', 'hex' => '#000000'],
                    ['name' => 'Merah', 'hex' => '#FF0000'],
                    ['name' => 'Biru', 'hex' => '#0000FF'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $colors = PrintMaterial::where('material', 'PLA Plus')->sole()->colors;

        $this->assertSame(['Putih', 'Hitam', 'Merah', 'Biru'], $colors->pluck('name')->all());
        $this->assertSame(['#FFFFFF', '#000000', '#FF0000', '#0000FF'], $colors->pluck('hex')->all());
        // Urutan pengisian dipertahankan.
        $this->assertSame([1, 2, 3, 4], $colors->pluck('position')->all());
    }

    /** Baris kosong bawaan formulir yang tidak jadi diisi tidak ikut tersimpan. */
    public function test_baris_warna_kosong_diabaikan(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload('PLA Plus'),
                'colors' => [
                    ['name' => 'Putih', 'hex' => '#FFFFFF'],
                    ['name' => '', 'hex' => ''],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Putih'], PrintMaterial::where('material', 'PLA Plus')->sole()->colors->pluck('name')->all());
    }

    public function test_kode_hexa_dirapikan_menjadi_huruf_besar(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload('PLA Plus'),
                'colors' => [['name' => 'Merah Bata', 'hex' => '  #b8452f  ']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('#B8452F', PrintMaterial::where('material', 'PLA Plus')->sole()->colors->sole()->hex);
    }

    #[DataProvider('hexaTidakValid')]
    public function test_kode_hexa_tidak_valid_ditolak(string $hex): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload('PLA Plus'),
                'colors' => [['name' => 'Putih', 'hex' => $hex]],
            ])
            ->assertSessionHasErrors('colors.0.hex');

        $this->assertSame(0, PrintMaterial::count());
    }

    /** @return array<string, array<int, string>> */
    public static function hexaTidakValid(): array
    {
        return [
            'tanpa pagar' => ['FFFFFF'],
            'terlalu pendek' => ['#FFF'],
            'bukan heksadesimal' => ['#GGGGGG'],
            'nama warna' => ['putih'],
        ];
    }

    public function test_nama_wajib_diisi_bila_hexanya_diisi(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload('PLA Plus'),
                'colors' => [['name' => '', 'hex' => '#FFFFFF']],
            ])
            ->assertSessionHasErrors('colors.0.name');
    }

    public function test_dua_warna_bernama_sama_ditolak(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.price-list.materials.store', $this->fdm), [
                ...$this->payload('PLA Plus'),
                'colors' => [
                    ['name' => 'Putih', 'hex' => '#FFFFFF'],
                    ['name' => 'Putih', 'hex' => '#F5F5F5'],
                ],
            ])
            ->assertSessionHasErrors('colors');

        $this->assertSame(0, PrintMaterial::count());
    }

    /* ---------------------------------------------------------- mengubah --- */

    public function test_warna_dapat_ditambah_dan_dihapus_lewat_form_edit(): void
    {
        $material = $this->material('PLA Plus', [
            ['name' => 'Putih', 'hex' => '#FFFFFF'],
            ['name' => 'Hitam', 'hex' => '#000000'],
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.price-list.materials.update', [$this->fdm, $material]), [
                ...$this->payload('PLA Plus'),
                'colors' => [
                    // "Hitam" dihapus, "Merah" ditambahkan.
                    ['key' => 'putih', 'name' => 'Putih', 'hex' => '#FFFFFF'],
                    ['name' => 'Merah', 'hex' => '#FF0000'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Putih', 'Merah'], $material->refresh()->colors->pluck('name')->all());
    }

    /**
     * Mengganti NAMA sebuah warna tidak boleh mengganti kuncinya.
     *
     * Penawaran menyimpan kuncinya, bukan salinan namanya, jadi kunci yang
     * berubah membuat model yang sudah dipesan kehilangan warna yang disetujui
     * pelanggan.
     */
    public function test_mengganti_nama_warna_tidak_mengganti_kuncinya(): void
    {
        $material = $this->material('PLA Plus', [['name' => 'Merah', 'hex' => '#FF0000']]);

        $this->assertSame('merah', $material->colors->sole()->key);

        $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.price-list.materials.update', [$this->fdm, $material]), [
                ...$this->payload('PLA Plus'),
                'colors' => [['key' => 'merah', 'name' => 'Merah Bata', 'hex' => '#A33A28']],
            ])
            ->assertSessionHasNoErrors();

        $color = $material->refresh()->colors->sole();

        $this->assertSame('merah', $color->key);
        $this->assertSame('Merah Bata', $color->name);
        $this->assertSame('#A33A28', $color->hex);
    }

    /* -------------------------------------------------------- sisi user --- */

    /**
     * Inti permintaannya: tiap material punya daftar warnanya sendiri.
     *
     * Pelanggan yang memilih FDM lalu PLA Plus melihat empat warna, sedangkan
     * FDM lalu PETG melihat tiga warna yang berbeda.
     */
    public function test_tiap_material_menawarkan_daftar_warnanya_sendiri(): void
    {
        $this->material('PLA Plus', [
            ['name' => 'Putih', 'hex' => '#FFFFFF'],
            ['name' => 'Hitam', 'hex' => '#000000'],
            ['name' => 'Merah', 'hex' => '#FF0000'],
            ['name' => 'Biru', 'hex' => '#0000FF'],
        ]);

        $this->material('PETG', [
            ['name' => 'Putih', 'hex' => '#FFFFFF'],
            ['name' => 'Hitam', 'hex' => '#000000'],
            ['name' => 'Abu-abu', 'hex' => '#808080'],
        ]);

        $this->refreshCatalog();

        $this->assertSame(
            ['Putih', 'Hitam', 'Merah', 'Biru'],
            collect(MaterialColor::forMaterial('FDM', 'PLA Plus'))->pluck('label')->all(),
        );

        $this->assertSame(
            ['Putih', 'Hitam', 'Abu-abu'],
            collect(MaterialColor::forMaterial('FDM', 'PETG'))->pluck('label')->all(),
        );
    }

    /** Daftar warna tiap material ikut terkirim ke browser. */
    public function test_warna_material_terkirim_ke_browser_per_material(): void
    {
        $this->material('PLA Plus', [
            ['name' => 'Putih', 'hex' => '#FFFFFF'],
            ['name' => 'Merah', 'hex' => '#FF0000'],
        ]);

        $this->material('PETG', [['name' => 'Abu-abu', 'hex' => '#808080']]);

        $this->refreshCatalog();

        $materials = collect(app(PrintEstimator::class)->browserPayload()['FDM']['materials']);

        $this->assertSame(['putih', 'merah'], $materials->firstWhere('name', 'PLA Plus')['colors']);
        $this->assertSame(['abu-abu'], $materials->firstWhere('name', 'PETG')['colors']);
    }

    /**
     * Warna yang sama persis berbagi satu kunci, yang berbeda hex dipisahkan.
     *
     * "Putih #FFFFFF" pada dua material memang warna yang sama, sedangkan
     * "Putih #F5F5F5" adalah warna lain dan tidak boleh menumpanginya.
     */
    public function test_warna_serupa_berbagi_kunci_dan_yang_berbeda_dipisahkan(): void
    {
        $this->material('PLA Plus', [['name' => 'Putih', 'hex' => '#FFFFFF']]);
        $this->material('PETG', [['name' => 'Putih', 'hex' => '#FFFFFF']]);
        $this->material('ABS', [['name' => 'Putih', 'hex' => '#F5F5F5']]);

        $keys = PrintMaterialColor::orderBy('id')->pluck('key')->all();

        $this->assertSame('putih', $keys[0]);
        $this->assertSame('putih', $keys[1], 'Putih dengan hex sama harus berbagi satu kunci.');
        $this->assertNotSame('putih', $keys[2], 'Putih dengan hex berbeda harus punya kunci sendiri.');
    }

    /**
     * Warna material menentukan tampilannya; palet lama tinggal cadangan.
     *
     * Penawaran lama yang menyimpan kunci yang tidak dimiliki material mana pun
     * tetap menemukan nama dan hexanya dari palet.
     */
    public function test_palet_lama_hanya_dipakai_untuk_kunci_yang_tak_dimiliki_material(): void
    {
        PrintColor::updateOrCreate(['key' => 'merah'], ['label' => 'Merah Palet', 'hex' => '#B8452F', 'position' => 1]);
        PrintColor::updateOrCreate(['key' => 'ungu'], ['label' => 'Ungu Palet', 'hex' => '#6B46C1', 'position' => 2]);

        $this->material('PLA Plus', [['name' => 'Merah', 'hex' => '#FF0000']]);

        $this->refreshCatalog();

        $this->assertSame('Merah', MaterialColor::label('merah'), 'Warna material harus mengalahkan palet.');
        $this->assertSame('Ungu Palet', MaterialColor::label('ungu'), 'Kunci tanpa pemilik tetap terbaca dari palet.');
    }

    /* ------------------------------------------------------------ tampil --- */

    public function test_warna_material_tampil_pada_tabel_price_list(): void
    {
        $this->material('PLA Plus', [
            ['name' => 'Putih', 'hex' => '#FFFFFF'],
            ['name' => 'Biru', 'hex' => '#0000FF'],
        ]);

        $this->actingAs($this->superAdmin)
            ->get(PriceListPage::technologyUrl($this->fdm))
            ->assertOk()
            ->assertSee('2 warna')
            ->assertSee('Putih #FFFFFF', false)
            ->assertSee('Biru #0000FF', false);
    }

    /* ----------------------------------------------------------- bantuan --- */

    /**
     * Material FDM beserta daftar warnanya.
     *
     * @param  array<int, array{name: string, hex: string}>  $colors
     */
    private function material(string $name, array $colors): PrintMaterial
    {
        $material = $this->fdm->materials()->create($this->payload($name));

        foreach ($colors as $position => $color) {
            $material->colors()->create([
                'key' => PrintMaterialColor::makeKey($color['name'], $color['hex']),
                'name' => $color['name'],
                'hex' => $color['hex'],
                'position' => $position + 1,
            ]);
        }

        return $material->load('colors');
    }

    /** @return array<string, mixed> */
    private function payload(string $name): array
    {
        return [
            'material' => $name,
            'brand' => 'eSUN',
            'purchase_price' => 185000,
            'sale_price' => 231,
        ];
    }

    /** Katalog dibaca ulang dari basis data, bukan dari ingatan permintaan ini. */
    private function refreshCatalog(): void
    {
        MaterialColor::forget();
        PrintTechnology::forgetCache();
        app()->forgetInstance(PrintEstimator::class);
    }
}
