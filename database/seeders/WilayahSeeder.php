<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi daftar wilayah Indonesia empat tingkat.
 *
 * Pekerjaan sesungguhnya dilakukan `php artisan wilayah:import`; seeder ini
 * hanya memanggilnya agar `db:seed` dan penyiapan lingkungan baru tetap satu
 * langkah. Impornya memperbarui baris yang sudah ada, jadi aman diulang.
 */
class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        // Datanya besar (±91.600 baris) dan jarang berubah, jadi tidak perlu
        // diimpor ulang pada setiap db:seed bila sudah lengkap.
        if (DB::table('villages')->count() > 0) {
            return;
        }

        Artisan::call('wilayah:import');
    }
}
