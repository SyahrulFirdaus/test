<?php

/*
|--------------------------------------------------------------------------
| Formulir Publik → Lead Management
|--------------------------------------------------------------------------
| Seluruh formulir publik (Form Kontak, konsultasi Miniatur, form singkat
| landing page layanan) disimpan lewat App\Services\LeadRecorder dan masuk
| ke menu Lead di admin.
*/

return [

    // Penerima email pemberitahuan setiap kiriman formulir.
    'notify_email' => env('LEAD_NOTIFY_EMAIL', 'cs@nusama3d.com'),

    /*
    | Form Kontak (komponen <x-contact-form>): About, Services, dan setiap
    | halaman industri.
    */
    'contact' => [
        // Pilihan layanan: enam layanan + Miniatur/Maket + Lainnya.
        'services' => [
            '3D Printing',
            'Reverse Engineering',
            '3D Scanning',
            'Product Development',
            '3D Design / CAD',
            'Paint & Finishing',
            'Miniatur/Maket',
            'Lainnya',
        ],

        // Lampiran: model 3D, PDF, atau foto. Isi berkas model diperiksa
        // App\Rules\ModelFile; PDF/JPG diperiksa tipe aslinya.
        'attachment' => [
            'extensions' => ['stl', 'step', 'stp', 'obj', '3mf', 'pdf', 'jpg', 'jpeg'],
            'max_kb' => 61440, // 60 MB
        ],
    ],

    /*
    | reCAPTCHA v3 (opsional). Selama kuncinya kosong, perlindungan spam
    | memakai honeypot + batas waktu pengisian saja.
    */
    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
    ],

    /*
    | Jam kerja tim untuk estimasi waktu balasan di halaman Terima Kasih
    | (sesuai Jam Operasional di Profil Perusahaan). Hari: 1 = Senin … 7 = Minggu.
    */
    'business_hours' => [
        'timezone' => 'Asia/Jakarta',
        'days' => [1, 2, 3, 4, 5],
        'open' => '08:00',
        'close' => '17:00',
    ],

    /*
    | Export CSV Deal → impor konversi Google Ads (Data Manager, upload file).
    |
    | - conversion_name : HARUS sama persis (ejaan & kapital) dengan nama
    |                     tindakan konversi di Google Ads.
    | - time_format     : format PHP waktu konversi, zona Asia/Jakarta.
    |                     'Y-m-d H:i:sP' → 2026-10-19 14:30:00+07:00 (sesuai
    |                     spesifikasi owner). Bila Google Ads menolak, ganti
    |                     'Y-m-d H:i:sO' → 2026-10-19 14:30:00+0700 (format
    |                     yang tercantum di tabel resmi Google Ads).
    | - order_id        : kolom "Order ID" (opsional & didukung Google untuk
    |                     deduplikasi). Nilainya stabil per lead: prefix + id.
    */
    'google_ads_export' => [
        'conversion_name' => env('GOOGLE_ADS_DEAL_CONVERSION_NAME', 'Deal Closing'),
        'currency' => 'IDR',
        'timezone' => 'Asia/Jakarta',
        'time_format' => env('GOOGLE_ADS_DEAL_TIME_FORMAT', 'Y-m-d H:i:sP'),
        'order_id' => (bool) env('GOOGLE_ADS_DEAL_ORDER_ID', true),
        'order_id_prefix' => 'NSM-LEAD-',
    ],

    // Formulir yang dikirim lebih cepat dari ini (detik) dianggap bot.
    'min_fill_seconds' => 3,
];
