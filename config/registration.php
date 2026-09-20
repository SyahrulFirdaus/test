<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tipe akun yang dibuka untuk pendaftaran baru
    |--------------------------------------------------------------------------
    |
    | Akun Business sementara ditutup: pendaftar baru hanya dapat membuat akun
    | Personal. Yang ditutup HANYA pintu pendaftarannya — akun Business yang
    | sudah ada tetap berjalan seperti biasa, tetap terbaca sebagai "Business"
    | di dashboard maupun daftar pengguna, dan seluruh pertanyaan pendaftaran
    | miliknya tetap tersimpan di `registration_questions`.
    |
    | Membukanya kembali cukup dengan REGISTRATION_BUSINESS_ACCOUNTS=true pada
    | .env — tidak ada satu baris kode pun yang perlu diubah.
    |
    */

    'business_accounts' => (bool) env('REGISTRATION_BUSINESS_ACCOUNTS', false),

];
