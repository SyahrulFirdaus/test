<?php

/*
|--------------------------------------------------------------------------
| Landing Page Layanan (/services/{slug})
|--------------------------------------------------------------------------
| Mengikuti sheet "Landing Page Layanan" (Checklist Perbaikan Website
| NUSAMA3D, item LP-01). Kunci array = slug URL halaman; `service` = slug
| baris di tabel `services` yang menjadi sumber uraian, tagline, highlight,
| dan ilustrasi — tidak ada data layanan yang diduplikasi di sini.
|
| - h1           : H1 sesuai matriks (kolom "H1 yang disarankan"); juga
|                  dipakai sebagai <title> halaman.
| - description  : meta description.
| - wa_service   : nama layanan di teks WhatsApp otomatis (UX-04).
|
| FAQ tiap halaman ada di config/service-faqs.php.
|
| Foto hasil kerja nyata (3–6 per layanan) diletakkan di
| public/images/services/{slug}/ (jpg/png/webp). Selama kosong, bagian
| galeri tidak ditampilkan.
*/

return [
    'pages' => [
        '3d-printing' => [
            'service' => '3d-printing-services',
            'h1' => 'Jasa 3D Printing Industri – FDM, SLA, MJF & SLM',
            'description' => 'Jasa 3D printing industri dengan teknologi FDM, SLA, MJF, dan SLM. Review file gratis, lead time mulai 1 hari kerja, NDA, dan pemeriksaan kualitas setiap order.',
            'wa_service' => '3D Printing',
        ],
        'reverse-engineering' => [
            'service' => 'reverse-engineering',
            'h1' => 'Jasa Reverse Engineering Sparepart',
            'description' => 'Jasa reverse engineering sparepart: part fisik dipindai dan direkonstruksi menjadi model CAD parametrik, lengkap dengan laporan analisis deviasi dan gambar kerja 2D.',
            'wa_service' => 'Reverse Engineering',
        ],
        '3d-scanning' => [
            'service' => '3d-scanning',
            'h1' => 'Jasa 3D Scanning Presisi hingga 0,05 mm',
            'description' => 'Jasa 3D scanning presisi hingga 0,05 mm untuk digitalisasi objek, inspeksi dimensi, dan data acuan desain. Output STL, OBJ, atau point cloud; layanan on-site tersedia.',
            'wa_service' => '3D Scanning',
        ],
        'product-development' => [
            'service' => 'product-development-service',
            'h1' => 'Jasa Pembuatan Prototype & Product Development',
            'description' => 'Jasa pembuatan prototype dan product development: konsep, CAD, prototipe fungsional, hingga Design for Manufacturing (DFM) dengan dokumentasi teknis lengkap.',
            'wa_service' => 'Product Development',
        ],
        '3d-design' => [
            'service' => '3d-design',
            'h1' => 'Jasa Desain 3D & CAD Siap Produksi',
            'description' => 'Jasa desain 3D dan CAD siap produksi: CAD parametrik, modeling organik, rendering fotorealistis, serta file siap cetak dan siap CNC.',
            'wa_service' => '3D Design / CAD',
        ],
        'paint-finishing' => [
            'service' => 'paint-finishing',
            'h1' => 'Jasa Finishing & Pengecatan Part 3D Print',
            'description' => 'Jasa finishing dan pengecatan part 3D print: pengamplasan, priming, pengecatan, color matching RAL & Pantone, finish matte hingga metalik, serta clear coat tahan UV.',
            'wa_service' => 'Paint & Finishing',
        ],
    ],

];
