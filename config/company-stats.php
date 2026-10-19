<?php

/*
|--------------------------------------------------------------------------
| Angka Statistik Perusahaan (Home & About)
|--------------------------------------------------------------------------
| Dirender langsung di HTML (server-side); animasi hitung di browser hanya
| efek tambahan. Lihat App\Support\CompanyStats.
|
| - `since` : angka dihitung otomatis = tahun berjalan − since
|             (Tahun Pengalaman: sejak 2020 → 6+ di 2026).
| - `value` : angka tetap. null = belum ada data; yang tampil `placeholder`
|             tanpa animasi. JANGAN diisi angka karangan.
*/

return [
    ['label' => 'Tahun Pengalaman', 'since' => 2020, 'suffix' => '+'],
    ['label' => 'Customer', 'value' => 400, 'suffix' => '+'],
    // MENUNGGU ANGKA DARI OWNER — isi `value` (mis. 1500) bila sudah ada.
    ['label' => 'Part Diproduksi', 'value' => null, 'suffix' => '+', 'placeholder' => '—'],
    ['label' => 'Teknologi Cetak', 'value' => 4, 'suffix' => ''],
];
