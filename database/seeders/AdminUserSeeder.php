<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Akun admin awal untuk mengakses dashboard permintaan penawaran.
     * Kata sandi dapat diganti lewat variabel ADMIN_PASSWORD pada .env.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@nusama3d.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );
    }
}
