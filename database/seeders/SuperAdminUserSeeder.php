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

        // `role` dan `is_active` bukan kolom fillable (lihat App\Models\User).
        $superAdmin->forceFill([
            'name' => env('SUPERADMIN_NAME', 'Superadmin'),
            'role' => User::ROLE_SUPERADMIN,
            'is_active' => true,
            'email_verified_at' => $superAdmin->email_verified_at ?? now(),
        ]);

        if (! $superAdmin->exists) {
            // Kata sandi bawaan hanya kredensial awal. Di production wajib
            // diberikan lewat SUPERADMIN_PASSWORD — kata sandi yang tertulis
            // di kode sumber tidak boleh menjadi kata sandi akun tertinggi.
            $password = env('SUPERADMIN_PASSWORD');

            if (blank($password) && app()->isProduction()) {
                throw new \RuntimeException('Setel SUPERADMIN_PASSWORD pada .env sebelum membuat akun Superadmin di production.');
            }

            $superAdmin->password = Hash::make($password ?: 'superadminnusama');
        }

        $superAdmin->save();
    }
}
