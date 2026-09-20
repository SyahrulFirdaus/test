<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menu staf di sidebar dikelompokkan: Penawaran, Pembayaran, Price List, Akun.
 */
class SidebarGroupingTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create(['email' => 'admin@nusama3d.com']);
    }

    /** Label item menu di bawah satu heading kelompok, urut tampil. */
    private function groupItems(string $html, string $group): array
    {
        $nav = substr($html, strpos($html, '<nav'), strpos($html, '</nav>') - strpos($html, '<nav'));

        preg_match_all('/<p class="sidebar-heading[^"]*"\s+title="([^"]+)">|<a href="[^"]*"\s+class="sidebar-link[^>]*>.*?<span class="sidebar-label flex-1">([^<]+)<\/span>/s', $nav, $matches, PREG_SET_ORDER);

        $items = [];
        $current = null;

        foreach ($matches as $match) {
            if (($match[1] ?? '') !== '') {
                $current = $match[1];
            } elseif ($current === $group) {
                $items[] = trim($match[2]);
            }
        }

        return $items;
    }

    public function test_sidebar_superadmin_dikelompokkan(): void
    {
        $html = $this->actingAs($this->superAdmin())->get(route('superadmin.dashboard'))->assertOk()->getContent();

        $this->assertSame(['User', 'Activity Log', 'Profil', 'Ganti Password', 'Akun Admin'], $this->groupItems($html, 'Akun'));
        $this->assertSame(['Penawaran', 'Notifikasi'], $this->groupItems($html, 'Penawaran'));
        $this->assertSame(['Verifikasi Pembayaran', 'Payment Term'], $this->groupItems($html, 'Pembayaran'));

        // Urutan kelompok: Penawaran, Pembayaran, Price List, lalu Akun.
        $penawaran = strpos($html, 'title="Penawaran"');
        $pembayaran = strpos($html, 'title="Pembayaran"');
        $priceList = strpos($html, 'title="Price List"');
        $akun = strpos($html, 'title="Akun"');

        $this->assertTrue($penawaran < $pembayaran && $pembayaran < $priceList && $priceList < $akun);
    }

    public function test_sidebar_admin_hanya_memuat_menu_haknya(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->getContent();

        // Admin factory memegang seluruh hak akses, termasuk User › Lihat.
        $this->assertSame(['User', 'Profil', 'Ganti Password'], $this->groupItems($html, 'Akun'));
        $this->assertSame(['Penawaran', 'Notifikasi'], $this->groupItems($html, 'Penawaran'));
        $this->assertSame(['Verifikasi Pembayaran', 'Payment Term'], $this->groupItems($html, 'Pembayaran'));
        $this->assertStringNotContainsString('title="Price List"', $html);
    }

    public function test_item_aktif_disorot(): void
    {
        $html = $this->actingAs($this->superAdmin())->get(route('superadmin.payments.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/title="Pembayaran › Verifikasi Pembayaran"\s+aria-current="page"/', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_alamat_menu_mengikuti_kelompoknya(): void
    {
        $this->assertSame('/superadmin/akun/user', route('superadmin.users.index', [], false));
        $this->assertSame('/superadmin/akun/activity-log', route('superadmin.activity-logs.index', [], false));
        $this->assertSame('/superadmin/akun/profil', route('superadmin.profile.edit', [], false));
        $this->assertSame('/superadmin/akun/ganti-password', route('superadmin.password.edit', [], false));
        $this->assertSame('/superadmin/akun/admin', route('superadmin.admins.index', [], false));
        $this->assertSame('/superadmin/penawaran/penawaran', route('superadmin.quotations.index', [], false));
        $this->assertSame('/superadmin/penawaran/notifikasi', route('superadmin.notifications.index', [], false));
        $this->assertSame('/superadmin/pembayaran/verifikasi', route('superadmin.payments.index', [], false));
        $this->assertSame('/superadmin/pembayaran/payment-term', route('superadmin.payment-terms.index', [], false));

        $this->assertSame('/admin/penawaran/penawaran', route('admin.quotations.index', [], false));
    }

    /**
     * Keterangan di bawah nama brand menyebut area yang sedang dibuka.
     *
     * Superadmin lolos `isAdmin()` juga — seluruh tugas operasional Admin
     * memang miliknya — jadi role yang lebih khusus harus diperiksa lebih dulu,
     * kalau tidak Superadmin ikut terbaca sebagai "Dashboard Admin".
     */
    public function test_label_sidebar_mengikuti_role(): void
    {
        $superAdmin = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Dashboard Superadmin', $superAdmin);
        $this->assertStringNotContainsString('Dashboard Admin', $superAdmin);

        $admin = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Dashboard Admin', $admin);
        $this->assertStringNotContainsString('Dashboard Superadmin', $admin);
    }

    public function test_alamat_lama_diteruskan_ke_alamat_baru(): void
    {
        $superAdmin = $this->superAdmin();

        foreach ([
            '/superadmin/pengguna' => '/superadmin/akun/user',
            '/superadmin/activity-logs' => '/superadmin/akun/activity-log',
            '/superadmin/akun-admin/tambah' => '/superadmin/akun/admin/tambah',
            '/superadmin/permintaan/12?status=reviewing' => '/superadmin/penawaran/penawaran/12?status=reviewing',
            '/superadmin/verifikasi-pembayaran' => '/superadmin/pembayaran/verifikasi',
            '/superadmin/payment-terms/5' => '/superadmin/pembayaran/payment-term/5',
            '/superadmin/notifikasi' => '/superadmin/penawaran/notifikasi',
            '/superadmin/profil' => '/superadmin/akun/profil',
            '/superadmin/ganti-password' => '/superadmin/akun/ganti-password',
        ] as $old => $new) {
            $this->actingAs($superAdmin)->get($old)->assertRedirect($new)->assertStatus(301);
        }

        $this->actingAs($this->admin())->get('/admin/permintaan/7')
            ->assertRedirect('/admin/penawaran/penawaran/7');
    }
}
