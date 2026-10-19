<?php

/*
|--------------------------------------------------------------------------
| Notifikasi WhatsApp
|--------------------------------------------------------------------------
| Seluruh CREDENTIAL integrasi WhatsApp tinggal di .env dan dibaca dari sini —
| tidak pernah disimpan di basis data dan tidak pernah dikirim ke halaman.
|
| Nomor PENGIRIM sengaja tidak ada di file ini: nomornya diatur Admin/
| Superadmin lewat menu "Pengaturan WhatsApp" (tabel `whatsapp_settings`),
| sehingga mengganti nomor NUSAMA3D tidak menuntut perubahan kode maupun .env.
|
| Driver:
|   - `cloud_api` WhatsApp Business Platform (Cloud API) resmi dari Meta.
|   - `log`       tidak mengirim apa pun, hanya menuliskan pesannya ke log
|                 aplikasi. Bawaan untuk pengembangan dan pengujian, supaya
|                 tidak ada pesan nyata yang terkirim ke pelanggan.
*/

return [

    'driver' => env('WHATSAPP_DRIVER', 'log'),

    // Batas waktu satu permintaan ke API, dalam detik. Pengiriman berjalan di
    // tengah request admin, jadi dibuat pendek supaya halaman tidak menggantung.
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 10),

    'cloud_api' => [
        'base_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com'),
        'version' => env('WHATSAPP_API_VERSION', 'v21.0'),

        // Access token permanen milik System User pada Meta Business.
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),

        // WhatsApp Business Account (WABA) yang menaungi nomor-nomor NUSAMA3D.
        // Dipakai untuk mencari Phone Number ID dari nomor yang diisi pada
        // halaman Pengaturan WhatsApp, jadi admin cukup mengetik nomornya.
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    ],

    /*
    | Template pesan yang sudah disetujui Meta.
    |
    | WhatsApp hanya mengizinkan pesan teks bebas kepada pelanggan yang
    | menghubungi nomor bisnis dalam 24 jam terakhir. Di luar jendela itu, pesan
    | yang dikirim bisnis WAJIB memakai template. Isi template yang disarankan
    | untuk `status_template` (4 variabel, urut):
    |
    |   NUSAMA3D – Informasi Penawaran
    |   Halo {{1}},
    |   Kami ingin menginformasikan bahwa status penawaran Anda telah diperbarui.
    |   Nomor Penawaran: {{2}}
    |   Status: {{3}}
    |   Tanggal Perubahan: {{4}}
    |   Silakan login ke akun NUSAMA3D untuk melihat detail penawaran Anda.
    |   Terima kasih telah menggunakan layanan NUSAMA3D.
    |
    | Bila dikosongkan, pesan dikirim sebagai teks biasa (lihat
    | App\Services\WhatsApp\WhatsAppMessage).
    */
    'templates' => [
        'status' => env('WHATSAPP_STATUS_TEMPLATE'),
        'test' => env('WHATSAPP_TEST_TEMPLATE'),
        'language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'id'),
    ],

];
