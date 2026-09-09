<?php

namespace App\Http\Requests\Concerns;

/**
 * Aturan data diri pelanggan.
 *
 * Dipakai bersama oleh formulir pendaftaran dan formulir profil di dashboard,
 * supaya data yang tersimpan lewat keduanya persis mengikuti aturan yang sama.
 */
trait ValidatesProfileFields
{
    /** @return array<string, array<int, string>> */
    protected function profileRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            // Nomor telepon Indonesia: 0812…, +62812…, boleh berspasi/strip.
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{8,32}$/'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:12', 'regex:/^[0-9]{5}$/'],
            'address' => ['required', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    protected function profileMessages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda +, -, dan tanda kurung.',
            'city.required' => 'Kota asal wajib diisi.',
            'postal_code.required' => 'Kode pos wajib diisi.',
            'postal_code.regex' => 'Kode pos terdiri dari 5 angka.',
            'address.required' => 'Alamat lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email tersebut sudah terdaftar. Silakan masuk atau gunakan email lain.',
            // Pesan kata sandi datang dari App\Support\PasswordPolicy agar
            // ketentuannya tidak tertulis di dua tempat.
        ];
    }
}
