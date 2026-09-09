<?php

namespace App\Support;

/**
 * Ketentuan kata sandi akun.
 *
 * Kata sandi harus sepanjang minimal `MIN_LENGTH` karakter serta memuat huruf
 * kapital, angka, dan simbol.
 *
 * Ketentuannya dikumpulkan di sini karena berlaku di tiga tempat sekaligus —
 * pendaftaran, reset lewat email, dan ganti kata sandi dari dashboard. Bila
 * ketiganya menuliskan aturannya sendiri-sendiri, memperketat salah satu akan
 * menyisakan celah di dua tempat lain: pemilik akun tinggal mereset kata
 * sandinya untuk memasang kata sandi lemah.
 */
class PasswordPolicy
{
    public const MIN_LENGTH = 8;

    /**
     * Aturan validasi kata sandi baru.
     *
     * Ketiga pemeriksaan huruf kapital, angka, dan simbol sengaja dipisah
     * menjadi tiga regex agar masing-masing dapat dibaca sendiri, dan seluruh
     * kegagalannya memakai satu pesan yang menyebutkan ketiga syarat itu
     * sekaligus sehingga pengguna langsung tahu apa yang kurang.
     *
     * @return array<int, string>
     */
    public static function rules(): array
    {
        return [
            'string',
            'min:'.self::MIN_LENGTH,
            'regex:/[A-Z]/',            // minimal satu huruf kapital
            'regex:/\d/',               // minimal satu angka
            'regex:/[^A-Za-z0-9]/',     // minimal satu simbol
        ];
    }

    /**
     * Pesan kesalahan dalam bahasa Indonesia.
     *
     * @param  string  $field  nama field, mis. `password`
     * @return array<string, string>
     */
    public static function messages(string $field = 'password'): array
    {
        return [
            $field.'.required' => 'Kata sandi wajib diisi.',
            $field.'.confirmed' => 'Konfirmasi kata sandi belum sama.',
            $field.'.min' => 'Kata sandi minimal '.self::MIN_LENGTH.' karakter.',
            $field.'.regex' => 'Kata sandi harus memuat minimal satu huruf kapital, satu angka, dan satu simbol.',
        ];
    }

    /** Keterangan singkat yang ditampilkan di bawah kolom isian. */
    public static function hint(): string
    {
        return 'Minimal '.self::MIN_LENGTH.' karakter, memuat huruf kapital, angka, dan simbol.';
    }
}
