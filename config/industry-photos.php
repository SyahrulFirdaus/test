<?php

/*
|--------------------------------------------------------------------------
| Kredit Foto Kartu Industri
|--------------------------------------------------------------------------
| Foto di public/images/industries/{slug}/utama.* berasal dari Wikimedia
| Commons dengan lisensi bebas (Public domain, CC0, CC BY, CC BY-SA).
| Lisensi CC BY / CC BY-SA mewajibkan pencantuman nama pembuat, lisensi,
| dan tautan sumber — data di bawah ini ditampilkan di halaman /industri.
|
| `fit` => 'contain' menampilkan foto utuh (untuk foto tegak); tanpa itu
| foto memenuhi kartu. Bila foto diganti dengan foto hasil cetak milik
| sendiri, hapus entri industri tersebut dari daftar ini.
*/

return [
    'otomotif-motorsport' => [
        'title' => 'Shelby Cobra hasil cetak 3D di ORNL',
        'author' => 'ENERGY.GOV',
        'license' => 'Public domain',
        'license_url' => null,
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Shelby_Cobra_that_was_first_3D_printed_at_ORNL.jpg',
    ],
    'aerospace-pertahanan-uav' => [
        'title' => 'DUSTER, drone hasil cetak 3D selebar 2 meter',
        'author' => 'BigRep',
        'license' => 'CC BY 2.5',
        'license_url' => 'https://creativecommons.org/licenses/by/2.5',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:DUSTER,_a_3D_printed_2m_wide_drone.jpg',
    ],
    'manufaktur-otomasi-robotika' => [
        'title' => 'Gripper robot hasil cetak 3D',
        'author' => 'Simiprof',
        'license' => 'CC0',
        'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:3D_printed_robotic_gripper.jpg',
    ],
    'kimia-energi-migas' => [
        'title' => 'Komponen turbin hasil cetak 3D',
        'author' => 'MurphyTC',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:3D_Printed_Turbine_Parts.png',
    ],
    'elektrikal-elektronik-iot' => [
        'title' => 'Casing perangkat Astro Pi hasil cetak 3D',
        'author' => 'Jgilz',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:3D-Printed_Astro_Pi_flight_case.jpg',
    ],
    'arsitektur-interior' => [
        'title' => 'Maket masterplan kawasan hasil cetak 3D',
        'author' => 'iKix 3D Prints',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Architecture_Site_Masterplan_Miniature_Scale_Model.jpg',
    ],
    'furnitur-desain-produk' => [
        'title' => 'Vas hasil cetak 3D',
        'author' => 'D Eaketts',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:3D_Printed_Vase.jpg',
    ],
    'mainan-display' => [
        'title' => 'Mobil mainan hasil cetak 3D',
        'author' => 'Creative Tools from Halmstad, Sweden',
        'license' => 'CC BY 2.0',
        'license_url' => 'https://creativecommons.org/licenses/by/2.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:CreativeTools.se_-_PackshotCreator_-_3D_printed_LEGO_toy_car_(4325785556).jpg',
    ],
    'medis-kedokteran-gigi' => [
        'title' => 'Model anatomi hati hasil cetak 3D',
        'author' => 'Jan Witowski',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:3D_printed_liver_model.jpg',
    ],
    'pendidikan-riset' => [
        'title' => 'Triple Gear, model matematika hasil cetak 3D',
        'author' => 'Saul Schleimer and Henry Segerman',
        'license' => 'CC BY-SA 3.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:Triple_Gear.jpg',
    ],
    'seni-fashion-perhiasan' => [
        'title' => 'Figur Mesir kuno hasil cetak 3D',
        'author' => 'Bradina',
        'license' => 'CC0',
        'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
        'source_url' => 'https://commons.wikimedia.org/wiki/File:3D_Printed_Ancient_Egyptian_Figurine_1.png',
        // Foto tegak: tampilkan utuh di atas latar putih.
        'fit' => 'contain',
    ],
];
