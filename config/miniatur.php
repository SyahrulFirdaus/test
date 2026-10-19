<?php

/*
|--------------------------------------------------------------------------
| Landing Page Miniatur Teknik & Souvenir Korporat (/miniatur-teknik)
|--------------------------------------------------------------------------
| Teks harga ditetapkan owner dan WAJIB tampil persis — jangan diganti
| rentang harga, harga per gram, atau rincian biaya.
|
| Galeri: selama foto hasil proyek NUSAMA3D belum ada, setiap kategori
| memakai foto ilustrasi berlisensi bebas yang sudah ada di proyek
| (public/images/industries/, kreditnya di config/industry-photos.php) dan
| diberi label "Ilustrasi". Foto proyek asli cukup diletakkan di
| public/images/miniatur/{slug}.jpg (atau .png/.webp) — foto itu otomatis
| menggantikan ilustrasinya dan labelnya hilang.
*/

return [
    'title' => 'Miniatur Teknik & Souvenir Korporat Custom',

    'description' => 'Jasa pembuatan miniatur teknik, maket arsitektur, replika produk, dan souvenir korporat custom dengan 3D printing. Mulai Rp1 juta per unit, konsultasi desain sebelum produksi.',

    'price' => [
        'headline' => 'Mulai Rp1 juta per unit',
        'note' => 'Harga akhir tergantung pada dimensi, tingkat detail, kompleksitas desain, dan finishing.',
        'bulk' => 'Untuk pesanan lebih dari 25 pcs, harga khusus diberikan setelah review desain.',
    ],

    'wa_text' => 'Halo NUSAMA3D, saya ingin berkonsultasi mengenai pembuatan miniatur teknik atau souvenir korporat. Mohon informasi mengenai proses konsultasi dan kebutuhan desain.',

    'gallery' => [
        'miniatur-teknik' => [
            'title' => 'Miniatur Teknik',
            'description' => 'Miniatur mesin, alat berat, komponen industri, dan peralatan engineering.',
            'illustration' => 'manufaktur-otomasi-robotika',
        ],
        'maket-arsitektur' => [
            'title' => 'Maket Arsitektur',
            'description' => 'Maket bangunan, kawasan, fasilitas industri, dan proyek infrastruktur.',
            'illustration' => 'arsitektur-interior',
        ],
        'replika-produk' => [
            'title' => 'Replika Produk',
            'description' => 'Replika produk perusahaan, prototype, dan model presentasi.',
            'illustration' => 'mainan-display',
        ],
        'souvenir-korporat' => [
            'title' => 'Souvenir Korporat',
            'description' => 'Souvenir custom, miniatur logo perusahaan, dan merchandise eksklusif.',
            'illustration' => 'seni-fashion-perhiasan',
        ],
        'miniatur-custom' => [
            'title' => 'Miniatur Custom',
            'description' => 'Model berdasarkan desain atau kebutuhan khusus pelanggan.',
            'illustration' => 'pendidikan-riset',
        ],
    ],

    'advantages' => [
        ['icon' => 'spark', 'title' => 'Desain custom', 'description' => 'Bentuk, skala, dan detail disesuaikan dengan kebutuhan proyek Anda.'],
        ['icon' => 'cube', 'title' => 'Prototipe & miniatur teknis', 'description' => 'Mendukung pembuatan prototipe dan miniatur teknis dari file CAD, gambar kerja, atau referensi.'],
        ['icon' => 'brush', 'title' => 'Material & finishing pilihan', 'description' => 'Pilihan material dan finishing disesuaikan dengan fungsi dan tampilan yang diinginkan.'],
        ['icon' => 'star', 'title' => 'Untuk presentasi hingga souvenir', 'description' => 'Dapat digunakan untuk presentasi proyek, display perusahaan, pameran, dan souvenir korporat.'],
        ['icon' => 'chat', 'title' => 'Konsultasi desain', 'description' => 'Desain ditinjau dan dikonsultasikan bersama Anda sebelum produksi dimulai.'],
    ],

    // Pilihan "Jenis kebutuhan" pada formulir; juga nilai kolom Layanan di Lead Management.
    'needs' => ['Miniatur Teknik', 'Maket Arsitektur', 'Replika Produk', 'Souvenir Korporat', 'Lainnya'],

    // Lampiran referensi (opsional).
    'attachment' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        'max_kb' => 10240,
    ],
];
