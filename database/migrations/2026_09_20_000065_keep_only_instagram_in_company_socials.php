<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sisakan Instagram saja pada tautan sosial perusahaan.
 *
 * LinkedIn, Facebook, dan YouTube tidak pernah benar-benar dipakai NUSAMA3D —
 * ketiganya terisi alamat contoh dari seeder pertama ("https://linkedin.com/")
 * dan hanya menghasilkan ikon yang membawa pengunjung ke halaman depan platform,
 * bukan ke akun perusahaan.
 *
 * Perbaikannya lewat migrasi, bukan hanya seeder: `company_profiles` adalah data
 * yang sudah berjalan di setiap lingkungan, dan menjalankan ulang seeder akan
 * menimpa isian lain yang mungkin sudah disunting. Footer maupun penanda
 * `sameAs` pada structured data sama-sama membaca kolom ini, jadi keduanya ikut
 * bersih sekaligus.
 */
return new class extends Migration
{
    /** Satu-satunya kanal yang dipakai, beserta alamat akunnya. */
    private const INSTAGRAM = 'https://www.instagram.com/nusama3d/';

    public function up(): void
    {
        foreach (DB::table('company_profiles')->get(['id', 'socials']) as $profile) {
            $socials = json_decode((string) $profile->socials, true);

            if (! is_array($socials)) {
                continue;
            }

            // Alamat Instagram yang sudah disunting sendiri dipertahankan;
            // hanya alamat contoh bawaan seeder yang diganti.
            $current = trim((string) ($socials['instagram'] ?? ''));
            $placeholder = $current === '' || rtrim($current, '/') === 'https://instagram.com';

            DB::table('company_profiles')
                ->where('id', $profile->id)
                ->update(['socials' => json_encode([
                    'instagram' => $placeholder ? self::INSTAGRAM : $current,
                ])]);
        }
    }

    /**
     * Tidak dapat dikembalikan.
     *
     * Yang dihapus adalah alamat contoh yang memang tidak menunjuk ke mana pun;
     * menuliskannya kembali hanya akan memunculkan lagi ikon yang salah.
     */
    public function down(): void
    {
        //
    }
};
