<?php

/*
|--------------------------------------------------------------------------
| Pembayaran Penawaran
|--------------------------------------------------------------------------
| Rekening tujuan, batas waktu pembayaran, dan aturan berkas bukti transfer.
| Halaman "Menunggu Pembayaran", pemeriksaan kedaluwarsa, serta verifikasi
| admin membaca angka yang sama dari sini.
*/

return [

    /*
    | Rekening tujuan yang ditampilkan pada halaman pembayaran. Nomornya
    | ditulis apa adanya termasuk spasi pemisah agar mudah dibaca; versi tanpa
    | spasi disediakan tombol salin lewat `account_number_plain`.
    */
    'bank' => [
        'name' => 'Bank Mandiri',
        'account_holder' => 'PT. Nusantara Addictive Manufactur',
        'account_number' => '156 00 2510878 0',
        'currency' => 'IDR',
    ],

    /*
    | Batas waktu pembayaran sejak status berubah menjadi "Menunggu
    | Pembayaran". Melewati batas ini penawaran dibatalkan otomatis oleh
    | sistem menjadi "Penawaran Dibatalkan (Expired)".
    */
    'window_hours' => 24,

    /*
    | Berkas bukti pembayaran. Disimpan pada disk privat `local` — sama seperti
    | berkas model — dan hanya dapat dibuka pemiliknya atau admin.
    |
    | Aturan berkas ini dipakai bersama oleh pembayaran sekali bayar dan bukti
    | tiap termin pada pembayaran bertahap.
    */
    'proof' => [
        'extensions' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_kilobytes' => 5120,
        'directory' => 'payments',
    ],

    /*
    |----------------------------------------------------------------------
    | Pembayaran Bertahap (Business)
    |----------------------------------------------------------------------
    | Berlaku khusus pelanggan Business. Batas nominal dan jumlah termin yang
    | boleh dipilih TIDAK diatur di sini melainkan pada tabel
    | `payment_term_settings` agar dapat diubah admin lewat dashboard; yang
    | tinggal di sini hanya bawaan penyusunan jadwalnya.
    */
    'terms' => [

        // Jarak antar jatuh tempo termin saat jadwal pertama kali disusun.
        // Admin tetap dapat menggeser tanggalnya satu per satu.
        'interval_days' => 7,

        /*
        | Milestone bawaan per jumlah termin. Termin tidak hanya bergantung
        | tanggal — tiap tagihan dikaitkan dengan tahap pekerjaan supaya
        | pelanggan tahu apa yang sedang dibayar.
        */
        'milestones' => [
            3 => [
                'DP / Order Confirmed',
                'Produksi Dimulai',
                'Pelunasan Sebelum Pengiriman',
            ],
            4 => [
                'DP / Order Confirmed',
                'Produksi Dimulai',
                'Quality Control',
                'Pelunasan Sebelum Pengiriman',
            ],
            5 => [
                'DP / Order Confirmed',
                'Persiapan Material',
                'Produksi Dimulai',
                'Quality Control',
                'Pelunasan Sebelum Pengiriman',
            ],
        ],

        /*
        | Pengingat jatuh tempo, dalam hitungan hari sebelum tanggalnya.
        | Nilai 0 berarti pengingat pada hari-H. Tiap tahap hanya dikirim
        | sekali per termin.
        */
        'reminder_days' => [7, 3, 1, 0],
    ],

];
