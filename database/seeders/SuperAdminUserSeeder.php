<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminUserSeeder extends Seeder
{
    /**
     * Akun Superadmin awal — akses tertinggi atas seluruh sistem.
     *
     * Kata sandinya di-hash seperti akun lain dan dimaksudkan untuk diganti
     * sendiri lewat menu Ganti Password setelah masuk pertama kali; nilainya
     * dapat ditetapkan lebih dulu lewat SUPERADMIN_PASSWORD pada .env.
     *
     * `updateOrCreate` sengaja tidak ikut menulis ulang kata sandi bila akunnya
     * sudah ada, supaya menjalankan seeder lagi tidak mengembalikan kata sandi
     * yang sudah diganti.
     */
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL', 'superadmin@nusama3d.com');

        $superAdmin = User::firstOrNew(['email' => $email]);

        $superAdmin->fill([
            'name' => env('SUPERADMIN_NAME', 'Superadmin'),
            'role' => User::ROLE_SUPERADMIN,
            'is_active' => true,
            'email_verified_at' => $superAdmin->email_verified_at ?? now(),
        ]);

        if (! $superAdmin->exists) {
            $superAdmin->password = Hash::make(env('SUPERADMIN_PASSWORD', 'superadminnusama'));
        }

        $superAdmin->save();
    }
}
