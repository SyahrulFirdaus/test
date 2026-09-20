<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Instagram adalah satu-satunya kanal sosial NUSAMA3D.
 *
 * LinkedIn, Facebook, dan YouTube pernah terisi alamat contoh bawaan seeder
 * ("https://linkedin.com/") yang hanya membawa pengunjung ke halaman depan
 * platform. Keduanya — ikon di footer dan penanda `sameAs` pada structured
 * data — membaca kolom `socials` yang sama, jadi cukup datanya yang bersih.
 */
class CompanySocialsTest extends TestCase
{
    use RefreshDatabase;

    private const INSTAGRAM = 'https://www.instagram.com/nusama3d/';

    /** Seeder untuk pemasangan baru hanya menuliskan Instagram. */
    public function test_seeder_hanya_menyimpan_instagram(): void
    {
        $this->seed(\Database\Seeders\CompanyProfileSeeder::class);

        $socials = CompanyProfile::find(1)->socials;

        $this->assertSame(['instagram' => self::INSTAGRAM], $socials);
    }

    public function test_footer_hanya_menampilkan_ikon_instagram(): void
    {
        $this->seed(\Database\Seeders\CompanyProfileSeeder::class);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Instagram NUSAMA3D"', $html);
        $this->assertStringContainsString(self::INSTAGRAM, $html);

        foreach (['LinkedIn', 'Facebook', 'YouTube'] as $platform) {
            $this->assertStringNotContainsString('aria-label="'.$platform, $html);
        }

        foreach (['linkedin.com', 'facebook.com', 'youtube.com'] as $domain) {
            $this->assertStringNotContainsString($domain, $html);
        }
    }

    /**
     * Migrasi merapikan pemasangan yang sudah berjalan.
     *
     * Lingkungan yang sudah di-seed sebelumnya tidak akan pernah menjalankan
     * seeder itu lagi, jadi pembersihannya harus datang dari migrasi.
     */
    public function test_migrasi_menyisakan_instagram_pada_data_yang_sudah_ada(): void
    {
        // Keadaan sebelum perbaikan: alamat contoh bawaan seeder pertama.
        $this->seedWithSocials([
            'instagram' => 'https://instagram.com/',
            'linkedin' => 'https://linkedin.com/',
            'youtube' => 'https://youtube.com/',
            'facebook' => 'https://facebook.com/',
        ]);

        $this->runMigration();

        $this->assertSame(['instagram' => self::INSTAGRAM], CompanyProfile::find(1)->socials);
    }

    /** Alamat Instagram yang sudah disunting sendiri tidak ditimpa. */
    public function test_migrasi_mempertahankan_alamat_instagram_yang_sudah_disunting(): void
    {
        $this->seedWithSocials([
            'instagram' => 'https://www.instagram.com/akun_lain/',
            'facebook' => 'https://facebook.com/',
        ]);

        $this->runMigration();

        $this->assertSame(
            ['instagram' => 'https://www.instagram.com/akun_lain/'],
            CompanyProfile::find(1)->socials,
        );
    }

    /**
     * Profil perusahaan lengkap dengan tautan sosial apa adanya.
     *
     * Barisnya dibuat seeder — kolom lain pada `company_profiles` wajib terisi —
     * lalu hanya `socials` yang dikembalikan ke keadaan sebelum perbaikan.
     *
     * @param  array<string, string>  $socials
     */
    private function seedWithSocials(array $socials): void
    {
        $this->seed(\Database\Seeders\CompanyProfileSeeder::class);

        CompanyProfile::whereKey(1)->update(['socials' => json_encode($socials)]);
    }

    private function runMigration(): void
    {
        (require database_path('migrations/2026_09_20_000065_keep_only_instagram_in_company_socials.php'))->up();
    }
}
