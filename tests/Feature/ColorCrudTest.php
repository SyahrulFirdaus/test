<?php

namespace Tests\Feature;

use App\Models\PrintColor;
use App\Models\User;
use App\Support\AdminPermission;
use App\Support\MaterialColor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu Color: warna material yang tersedia beserta kode hexanya.
 *
 * Daftarnya bukan lagi isi config melainkan tabel `print_colors`, sehingga apa
 * yang ditambah pengelola di sini benar-benar menjadi pilihan pelanggan pada
 * Edit Specification.
 */
class ColorCrudTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        MaterialColor::forget();
    }

    /* ============================================================ menu === */

    /**
     * Warna kini dikelola sebagai bagian dari Material (Nama Color dan Hexa
     * Color pada form Tambah/Ubah Material), jadi menu Color tidak lagi berdiri
     * sendiri di sidebar. Halamannya sendiri sengaja dipertahankan — palet
     * warna yang dipilih pelanggan masih dibaca dari sana.
     */
    public function test_sidebar_tidak_lagi_memuat_kelompok_color(): void
    {
        foreach (['superadmin' => 'superadmin.dashboard', 'admin' => 'admin.dashboard'] as $role => $route) {
            $user = $role === 'superadmin'
                ? $this->superAdmin()
                : User::factory()->admin()->create(['email' => 'admin@nusama3d.com']);

            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertDontSee('title="Color"', false)
                ->assertDontSee('title="Color › Color"', false);
        }
    }

    public function test_alamat_menu_mengikuti_wilayahnya(): void
    {
        $this->assertSame('/superadmin/color/warna', route('superadmin.colors.index', [], false));
        $this->assertSame('/admin/color/warna', route('admin.colors.index', [], false));
    }

    /* ============================================================ baca === */

    public function test_daftar_warna_diisi_dari_config_saat_migrasi(): void
    {
        // Migrasi memindahkan seluruh warna bawaan apa adanya.
        $this->assertSame(
            array_keys(config('printing.material_colors.options')),
            PrintColor::ordered()->pluck('key')->all(),
        );

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.colors.index'))
            ->assertOk()
            ->assertSee('Merah')
            ->assertSee('#B8452F');
    }

    /* ========================================================== tambah === */

    public function test_menambah_warna(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.colors.store'), ['label' => 'Hijau Lumut', 'hex' => '#4A7C2F'])
            ->assertRedirect(route('superadmin.colors.index'));

        $color = PrintColor::where('label', 'Hijau Lumut')->firstOrFail();

        $this->assertSame('hijau-lumut', $color->key);
        $this->assertSame('#4A7C2F', $color->hex);

        // Warna baru langsung menjadi pilihan pelanggan.
        MaterialColor::forget();
        $this->assertArrayHasKey('hijau-lumut', MaterialColor::all());
        $this->assertSame('Hijau Lumut', MaterialColor::label('hijau-lumut'));
        $this->assertSame('#4A7C2F', MaterialColor::hex('hijau-lumut'));
    }

    /**
     * Kode hexa WAJIB diawali tanda pagar dan berisi enam digit.
     *
     * Kiriman tanpa pagar ditolak dengan pesan, bukan diperbaiki diam-diam:
     * warna yang salah satu digit saja berbeda tetap terlihat masuk akal, jadi
     * yang aman adalah mengembalikannya kepada pengirimnya.
     */
    public function test_kode_hexa_harus_diawali_pagar(): void
    {
        $superAdmin = $this->superAdmin();

        foreach (['B8452F', '#B845', '#GGGGGG', 'merah', '#B8452FF', ''] as $hex) {
            $this->actingAs($superAdmin)
                ->post(route('superadmin.colors.store'), ['label' => 'Warna Uji', 'hex' => $hex])
                ->assertSessionHasErrors('hex');
        }

        $this->assertSame(0, PrintColor::where('label', 'Warna Uji')->count());
    }

    /** Huruf kecil dan spasi yang ikut tersalin dirapikan, pagarnya tidak. */
    public function test_kode_hexa_dirapikan_menjadi_huruf_besar(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.colors.store'), ['label' => 'Kuning Kunyit', 'hex' => '  #e0a82e  '])
            ->assertSessionHasNoErrors();

        $this->assertSame('#E0A82E', PrintColor::where('label', 'Kuning Kunyit')->value('hex'));
    }

    public function test_nama_warna_tidak_boleh_kembar(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.colors.store'), ['label' => 'Merah', 'hex' => '#FF0000'])
            ->assertSessionHasErrors('label');
    }

    /* =========================================================== ubah === */

    /**
     * Mengubah warna tidak menyentuh `key`.
     *
     * Penawaran menyimpan kuncinya, jadi mengganti nama sebuah warna hanya
     * mengubah cara warna itu ditampilkan — model yang sudah dipesan tetap
     * memakai warna yang sama.
     */
    public function test_mengubah_warna_tidak_mengganti_kuncinya(): void
    {
        $color = PrintColor::where('key', 'merah')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->patch(route('superadmin.colors.update', $color), ['label' => 'Merah Bata', 'hex' => '#A33A28'])
            ->assertRedirect(route('superadmin.colors.index'));

        $color->refresh();

        $this->assertSame('merah', $color->key);
        $this->assertSame('Merah Bata', $color->label);
        $this->assertSame('#A33A28', $color->hex);

        /*
         * Yang TIDAK lagi diuji di sini: nama yang tampil untuk kunci "merah".
         * Warna kini dimiliki tiap material (App\Models\PrintMaterialColor) dan
         * daftar itulah yang menentukan tampilannya; palet ini hanya lapisan
         * dasar bagi kunci yang tidak dimiliki material mana pun. Urutan
         * keduanya diuji di Tests\Feature\MaterialColorListTest.
         */
    }

    /* ========================================================== hapus === */

    public function test_menghapus_warna_yang_belum_dipakai(): void
    {
        $color = PrintColor::create(['key' => 'ungu', 'label' => 'Ungu', 'hex' => '#6B46C1', 'position' => 99]);

        $this->actingAs($this->superAdmin())
            ->delete(route('superadmin.colors.destroy', $color))
            ->assertRedirect(route('superadmin.colors.index'));

        $this->assertNull(PrintColor::find($color->id));
    }

    /* ======================================================= hak akses === */

    public function test_admin_tanpa_hak_color_ditolak(): void
    {
        $admin = User::factory()
            ->admin()
            ->withPermissions([AdminPermission::PROFILE_EDIT])
            ->create(['email' => 'tanpa-color@nusama3d.com']);

        $this->actingAs($admin)->get(route('admin.colors.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertDontSee('title="Color"', false);
    }

    /** Hak Lihat saja tidak cukup untuk menambah, mengubah, atau menghapus. */
    public function test_admin_yang_hanya_boleh_melihat_tidak_dapat_mengubah(): void
    {
        $admin = User::factory()
            ->admin()
            ->withPermissions([AdminPermission::COLOR_VIEW])
            ->create(['email' => 'lihat-color@nusama3d.com']);

        $color = PrintColor::where('key', 'merah')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.colors.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.colors.create'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.colors.store'), ['label' => 'X', 'hex' => '#000000'])->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.colors.update', $color), ['label' => 'X', 'hex' => '#000000'])->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.colors.destroy', $color))->assertForbidden();
    }
}
