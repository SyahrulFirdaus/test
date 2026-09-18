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
        $admin = User::firstOrNew(['email' => env('ADMIN_EMAIL', 'admin@nusama3d.com')]);

        // `role` bukan kolom fillable (lihat App\Models\User).
        $admin->forceFill([
            'name' => env('ADMIN_NAME', 'Administrator'),
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ]);

        // Kata sandi hanya ditetapkan saat akun pertama kali dibuat, supaya
        // menjalankan seeder lagi tidak mengembalikan kata sandi yang sudah
        // diganti. Di production kata sandi bawaan "password" tidak dipakai.
        if (! $admin->exists) {
            $password = env('ADMIN_PASSWORD');

            if (blank($password) && app()->isProduction()) {
                return;
            }

            $admin->password = Hash::make($password ?: 'password');
        }

        $admin->save();
    }
}
