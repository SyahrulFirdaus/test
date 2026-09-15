<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CompanyProfileSeeder::class,
            ServiceSeeder::class,
            TechnologySeeder::class,
            ClientSeeder::class,
            TestimonialSeeder::class,
            AdminUserSeeder::class,
            SuperAdminUserSeeder::class,
            // Harga packaging dan mesin pada Price List (material FDM/SLA
            // sudah disisipkan langsung di migrasinya masing-masing).
            PriceListSeeder::class,
            // Wilayah Indonesia 4 tingkat untuk dropdown alamat.
            WilayahSeeder::class,
            // Dijalankan setelah TechnologySeeder: pertanyaan teknologi pada
            // pendaftaran perusahaan membaca daftar teknologi yang tersedia.
            RegistrationQuestionSeeder::class,
        ]);
    }
}
