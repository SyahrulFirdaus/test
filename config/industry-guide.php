<?php

/*
|--------------------------------------------------------------------------
| Panduan Umum Halaman Industri
|--------------------------------------------------------------------------
| - materials: sifat umum material yang muncul di config/industries.php.
|   Nilainya relatif antar-material (1 = rendah, 2 = sedang, 3 = tinggi),
|   bukan angka hasil uji, dan dipakai untuk tabel perbandingan material.
| - process  : alur kerja pemesanan. Lama tahap Produksi tidak ditulis di
|   sini; nilainya diambil dari spesifikasi "Waktu Produksi" tiap teknologi
|   (tabel technologies) sehingga selalu sama dengan halaman Technologies.
*/

return [
    'materials' => [
        'PLA' => ['technology' => 'FDM', 'strength' => 2, 'heat' => 1, 'resistance' => 1, 'surface' => 1],
        'PETG' => ['technology' => 'FDM', 'strength' => 2, 'heat' => 2, 'resistance' => 2, 'surface' => 1],
        'ABS' => ['technology' => 'FDM', 'strength' => 2, 'heat' => 2, 'resistance' => 1, 'surface' => 1],
        'ASA' => ['technology' => 'FDM', 'strength' => 2, 'heat' => 2, 'resistance' => 3, 'surface' => 1],
        'TPU' => ['technology' => 'FDM', 'strength' => 2, 'heat' => 1, 'resistance' => 2, 'surface' => 1, 'note' => 'Fleksibel'],
        'Carbon Fiber Nylon' => ['technology' => 'FDM', 'strength' => 3, 'heat' => 3, 'resistance' => 2, 'surface' => 1],
        'Nylon PA12' => ['technology' => 'MJF', 'strength' => 3, 'heat' => 2, 'resistance' => 3, 'surface' => 2],
        'Resin Standard' => ['technology' => 'SLA', 'strength' => 1, 'heat' => 1, 'resistance' => 1, 'surface' => 3],
        'Resin Tough' => ['technology' => 'SLA', 'strength' => 2, 'heat' => 1, 'resistance' => 1, 'surface' => 3],
        'Resin High Temp' => ['technology' => 'SLA', 'strength' => 2, 'heat' => 3, 'resistance' => 2, 'surface' => 3],
        'Resin Castable' => ['technology' => 'SLA', 'strength' => 1, 'heat' => 1, 'resistance' => 1, 'surface' => 3, 'note' => 'Untuk casting'],
        'Aluminium AlSi10Mg' => ['technology' => 'SLM', 'strength' => 3, 'heat' => 3, 'resistance' => 2, 'surface' => 2],
        'Stainless Steel 316L' => ['technology' => 'SLM', 'strength' => 3, 'heat' => 3, 'resistance' => 3, 'surface' => 2],
        'Titanium Ti6Al4V' => ['technology' => 'SLM', 'strength' => 3, 'heat' => 3, 'resistance' => 3, 'surface' => 2],
        'Inconel 718' => ['technology' => 'SLM', 'strength' => 3, 'heat' => 3, 'resistance' => 3, 'surface' => 2],
    ],

    'material_properties' => [
        'strength' => 'Kekuatan',
        'heat' => 'Tahan Panas',
        'resistance' => 'Tahan UV & Kimia',
        'surface' => 'Kehalusan Permukaan',
    ],

    'process' => [
        ['title' => 'Kirim file & konsultasi', 'description' => 'Unggah file 3D di halaman 3D Models atau kirim lewat WhatsApp, lalu ceritakan fungsi part yang Anda butuhkan.', 'duration' => null],
        ['title' => 'Review desain & penawaran', 'description' => 'Tim engineer memeriksa kelayakan cetak, merekomendasikan teknologi dan material, lalu mengirimkan penawaran.', 'duration' => 'Ditinjau di hari yang sama'],
        ['title' => 'Produksi', 'description' => 'Part dicetak dengan teknologi yang disepakati. Lama produksi bergantung pada teknologi, ukuran, dan jumlah part.', 'duration' => 'production'],
        ['title' => 'Finishing', 'description' => 'Opsional: pengamplasan, primer, pengecatan, hingga clear coat sesuai kebutuhan tampilan dan fungsi.', 'duration' => 'Sesuai tingkat finishing'],
        ['title' => 'Quality control & pengemasan', 'description' => 'Part diperiksa lalu dikemas dengan aman sebelum dikirim.', 'duration' => null],
        ['title' => 'Pengiriman', 'description' => 'Part dikirim ke alamat Anda melalui ekspedisi; status pesanan dapat dipantau di halaman Tracking.', 'duration' => 'Sesuai ekspedisi'],
    ],
];
