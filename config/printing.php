<?php

/*
|--------------------------------------------------------------------------
| Parameter Estimasi Cetak 3D
|--------------------------------------------------------------------------
| Seluruh angka estimasi (harga material, tarif mesin, kecepatan produksi,
| dan batas area cetak) dikumpulkan di sini agar mudah diperbarui tanpa
| menyentuh kode. Nilai yang sama dipakai oleh estimator di browser maupun
| perhitungan ulang di server, sehingga hasilnya selalu konsisten.
|
| Rumus yang dipakai:
|   volume_model    = volume_geometri x skala^3
|   fill_factor     = shell_ratio + (1 - shell_ratio) x infill x pengali_pola
|   volume_material = volume_model x fill_factor        (atau volume cangkang bila hollow)
|   berat           = volume_material (cm3) x densitas (g/cm3)
|   waktu           = setup_hours + volume_material (cm3) / throughput (cm3/jam)
|   biaya           = material + waktu mesin + support + finishing + quality control + basic fee
*/

return [

    'currency' => 'IDR',

    /*
    |----------------------------------------------------------------------
    | Printer 3D
    |----------------------------------------------------------------------
    | Menentukan ukuran build plate yang digambar di viewer sekaligus batas
    | yang dipakai untuk memeriksa apakah model masih muat. Build volume
    | memakai konvensi yang sama dengan `build_volume` teknologi:
    | x = lebar meja, y = kedalaman meja, z = tinggi maksimum.
    |
    | `speed_factor` dan `rate_factor` membuat pilihan mesin ikut memengaruhi
    | estimasi: mesin yang lebih cepat menyelesaikan volume yang sama dalam
    | waktu lebih singkat, sedangkan tarif per jamnya bisa berbeda.
    */
    'printers' => [
        'default' => 'ender3',

        'custom_key' => 'custom',
        'custom_limits' => ['min' => 50, 'max' => 1000],

        'options' => [
            'ender3' => [
                'name' => 'Creality Ender 3',
                'build_volume' => ['x' => 220, 'y' => 220, 'z' => 250],
                'speed_factor' => 0.85,
                'rate_factor' => 0.90,
                'note' => 'Mesin FDM populer untuk prototipe harian dengan biaya paling ekonomis.',
            ],
            'bambu_x1c' => [
                'name' => 'Bambu Lab X1 Carbon',
                'build_volume' => ['x' => 256, 'y' => 256, 'z' => 256],
                'speed_factor' => 1.75,
                'rate_factor' => 1.15,
                'note' => 'Mesin CoreXY berkecepatan tinggi, cocok untuk pengerjaan yang dikejar tenggat.',
            ],
            'prusa_mk4' => [
                'name' => 'Prusa MK4',
                'build_volume' => ['x' => 250, 'y' => 210, 'z' => 220],
                'speed_factor' => 1.30,
                'rate_factor' => 1.05,
                'note' => 'Konsistensi dimensi paling terjaga, pilihan aman untuk part fungsional.',
            ],
            'anycubic_kobra' => [
                'name' => 'Anycubic Kobra',
                'build_volume' => ['x' => 220, 'y' => 220, 'z' => 250],
                'speed_factor' => 0.95,
                'rate_factor' => 0.90,
                'note' => 'Leveling otomatis, seimbang antara kecepatan dan biaya operasional.',
            ],
            'custom' => [
                'name' => 'Custom',
                'build_volume' => ['x' => 300, 'y' => 300, 'z' => 300],
                'speed_factor' => 1.00,
                'rate_factor' => 1.00,
                'custom' => true,
                'note' => 'Masukkan sendiri ukuran area cetak mesin yang akan dipakai.',
            ],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Infill
    |----------------------------------------------------------------------
    | Kepadatan infill hanya berlaku pada rongga di dalam part; dindingnya
    | (`shell_ratio` tiap teknologi) selalu padat. Karena itu SLA yang
    | shell_ratio-nya 1,0 tidak terpengaruh infill sama sekali — pengurangan
    | resin di SLA dilakukan lewat Hollow Model.
    |
    | Pengali pola diberlakukan sebanding kepadatannya: pada infill 0% pola
    | apa pun tidak berpengaruh, pada 100% pengaruhnya penuh.
    */
    'infill' => [
        'densities' => [0.10, 0.20, 0.40, 0.60, 0.80, 1.00],

        'default_pattern' => 'grid',
        'patterns' => [
            'grid' => [
                'label' => 'Grid',
                'description' => 'Garis lurus bersilangan. Paling cepat dicetak dan paling hemat material, kekuatannya searah sumbu.',
                'material_multiplier' => 1.00,
                'time_multiplier' => 1.00,
            ],
            'gyroid' => [
                'label' => 'Gyroid',
                'description' => 'Pola gelombang tiga dimensi. Kekuatan merata ke segala arah dan fleksibel, tetapi jalur cetaknya paling panjang.',
                'material_multiplier' => 1.05,
                'time_multiplier' => 1.18,
            ],
            'cubic' => [
                'label' => 'Cubic',
                'description' => 'Tumpukan kubus miring. Kekuatan baik ke segala arah dengan waktu cetak yang masih wajar.',
                'material_multiplier' => 1.03,
                'time_multiplier' => 1.08,
            ],
            'triangle' => [
                'label' => 'Triangle',
                'description' => 'Segitiga rapat. Paling kaku menahan gaya samping, konsumsi materialnya paling tinggi.',
                'material_multiplier' => 1.08,
                'time_multiplier' => 1.12,
            ],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Hollow Model (khusus SLA)
    |----------------------------------------------------------------------
    | Part SLA yang dikosongkan hanya menyisakan cangkang setebal
    | `wall_thickness_mm`, sehingga volumenya diperkirakan dari luas permukaan
    | model dikali tebal dinding. Lubang pembuangan wajib ada agar resin yang
    | terperangkap dapat keluar dan tidak menimbulkan efek cup.
    */
    'hollow' => [
        'technologies' => ['SLA'],

        'wall_thickness_mm' => ['default' => 2.0, 'min' => 0.8, 'max' => 5.0, 'step' => 0.1],

        'drain_hole' => [
            'diameter_mm' => ['default' => 3.5, 'min' => 1.0, 'max' => 10.0, 'step' => 0.5],
            'default_position' => 'bottom',
            'count' => 2,
            'positions' => [
                'bottom' => [
                    'label' => 'Dasar Model',
                    'description' => 'Paling tersembunyi setelah dicetak dan paling mudah dibersihkan.',
                ],
                'side' => [
                    'label' => 'Sisi Samping',
                    'description' => 'Dipakai bila dasar model menjadi permukaan tampak.',
                ],
                'top' => [
                    'label' => 'Bagian Atas',
                    'description' => 'Untuk model yang dicetak terbalik agar resin mengalir keluar sendiri.',
                ],
            ],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Simulasi Warna Material
    |----------------------------------------------------------------------
    | Hanya memengaruhi tampilan model di viewer, bukan berkas aslinya.
    | Nilai default sengaja sama dengan warna model sebelum fitur ini ada.
    */
    'material_colors' => [
        'default' => 'merah',

        'options' => [
            'putih' => ['label' => 'Putih', 'hex' => '#EDE7E3'],
            'hitam' => ['label' => 'Hitam', 'hex' => '#2C2523'],
            'merah' => ['label' => 'Merah', 'hex' => '#B8452F'],
            'biru' => ['label' => 'Biru', 'hex' => '#2F5FB8'],
            'abu' => ['label' => 'Abu-abu', 'hex' => '#8A817C'],
            'bening' => ['label' => 'Bening', 'hex' => '#DCE6EA'],
            'logam' => ['label' => 'Natural Logam', 'hex' => '#A8ADB3'],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Finishing
    |----------------------------------------------------------------------
    | Pekerjaan tambahan setelah part selesai dicetak. Komponen biaya
    | "Finishing" pada rincian penawaran sudah ada sejak awal dan mewakili
    | pembersihan dasar setiap part — itulah pilihan `none`, dengan pengali
    | 1,0 sehingga harga part yang tidak meminta finishing tambahan sama
    | persis seperti sebelum fitur ini ada.
    |
    | Pilihan lain menaikkan biaya finishing sebesar `cost_multiplier` dan
    | menambah waktu pengerjaan sebanyak `hours_per_unit` untuk setiap unit.
    | Angkanya simulasi dan mudah dikalibrasi.
    */
    'finishing' => [
        'default' => 'none',

        'options' => [
            'none' => [
                'label' => 'Tanpa Finishing',
                'description' => 'Part diserahkan apa adanya setelah dibersihkan dari sisa support dan serbuk.',
                'cost_multiplier' => 1.0,
                'hours_per_unit' => 0.0,
            ],
            'sanding' => [
                'label' => 'Sanding',
                'description' => 'Permukaan diamplas bertingkat sehingga garis lapisan jauh berkurang.',
                'cost_multiplier' => 1.8,
                'hours_per_unit' => 0.25,
            ],
            'painting' => [
                'label' => 'Painting',
                'description' => 'Diamplas, diprimer, lalu dicat sesuai warna yang dipilih. Hanya tersedia untuk warna Putih.',
                'cost_multiplier' => 3.4,
                'hours_per_unit' => 0.75,
            ],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Rincian Biaya
    |----------------------------------------------------------------------
    | Total penawaran dipecah menjadi lima komponen agar pelanggan dapat
    | melihat asal angkanya. Biaya setup mesin dimasukkan ke komponen waktu
    | printing karena memang bagian dari okupansi mesin.
    */
    'cost' => [
        // Pelepasan dan perapihan bekas support, per unit.
        'support_removal_fee' => 15000,

        'finishing' => [
            'rate_per_cm2' => 260,
            'minimum' => 12000,
        ],

        'quality_control' => [
            'percent' => 0.04,
            'minimum' => 8000,
        ],

        /*
         * Basic Fee: biaya dasar penanganan yang ditentukan sisi TERPANJANG
         * model — bukan volumenya — dalam milimeter. Ukuran yang dibaca sudah
         * termasuk skala, karena dimensi pada `model_stats` diukur browser dari
         * model yang sudah diskalakan dan diputar.
         *
         *   Kecil    < 80 mm            Rp0
         *   Sedang   80 mm s.d. 200 mm  Rp25.000
         *   Besar    > 200 mm           Rp50.000
         *
         * Batasnya ditulis apa adanya: `below_mm` berlaku selama ukuran masih
         * di bawah angka itu, `up_to_mm` sampai dengan angka itu, dan tingkat
         * terakhir tanpa batas menampung sisanya. Model tanpa catatan dimensi
         * dianggap Kecil sehingga tidak pernah dikenakan biaya yang tidak dapat
         * dipertanggungjawabkan ukurannya.
         */
        'basic_fee' => [
            'tiers' => [
                ['key' => 'kecil', 'label' => 'Kecil', 'below_mm' => 80, 'fee' => 0],
                ['key' => 'sedang', 'label' => 'Sedang', 'up_to_mm' => 200, 'fee' => 25000],
                ['key' => 'besar', 'label' => 'Besar', 'fee' => 50000],
            ],
        ],

        // Biaya akhir dibulatkan ke atas pada kelipatan ini agar enak dibaca.
        'rounding' => 500,
    ],

    /*
    |----------------------------------------------------------------------
    | Analisis Visual di Viewer
    |----------------------------------------------------------------------
    | Ambang pewarnaan model pada mode Overhang dan Wall Thickness. Keduanya
    | murni visual dan tidak memengaruhi estimasi.
    */
    'analysis' => [
        'overhang' => [
            // Sudut diukur dari bidang tegak, sama seperti Cura: dinding tegak
            // 0 derajat, langit-langit mendatar 90 derajat.
            'safe_deg' => 45,
            'warn_deg' => 60,
            'colors' => ['safe' => '#3FA45B', 'warn' => '#E0A82E', 'critical' => '#C0392B'],
        ],

        'wall_thickness' => [
            'colors' => ['safe' => '#3FA45B', 'thin' => '#C0392B'],
            // Pengukuran menembakkan sinar ke dalam model dari tiap titik
            // sampel; kedua batas ini menjaga browser tetap responsif.
            'max_triangles' => 250000,
            'max_samples' => 20000,
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Support Structure
    |----------------------------------------------------------------------
    | Untuk saat ini support dihitung sebagai simulasi, bukan hasil analisis
    | overhang yang sebenarnya. Seluruh angka dikumpulkan di sini agar mudah
    | dikalibrasi, dan strukturnya sudah menyediakan tempat untuk pengembangan
    | berikutnya (deteksi otomatis, analisis sudut overhang, jenis support).
    |
    | Rumus:
    |   aspek        = tinggi / sisi tapak terpanjang
    |   pengali      = 1 + min(aspek, max_aspect) x height_influence
    |   volume kasar = volume_model x support_volume_factor x pengali x pengali_jenis
    |   volume bahan = volume kasar x infill
    |   berat        = volume bahan x densitas material
    |
    | support_volume_factor diatur per teknologi di bawah; MJF bernilai 0
    | karena part tertopang serbuk di sekelilingnya sehingga tidak butuh support.
    */
    'support' => [
        'default_enabled' => false,

        // Support dicetak renggang, hanya sebagian rongganya berisi material.
        'infill' => 0.30,

        // Part yang tinggi dan langsing menuntut support lebih banyak.
        'height_influence' => 0.25,
        'max_aspect' => 3.0,

        'default_type' => 'normal',
        'types' => [
            'normal' => ['label' => 'Normal Support', 'multiplier' => 1.0],
            // Disiapkan untuk pengembangan berikutnya.
            'tree' => ['label' => 'Tree Support', 'multiplier' => 0.6],
        ],

        /*
        | Parameter pembentukan visualisasi support di viewer.
        |
        | Pendekatannya menyerupai konsep Cura: muka yang menggantung lebih dari
        | `overhang_angle_deg` dari bidang tegak dideteksi, titik terendahnya
        | dikelompokkan ke dalam kisi di bidang meja, lalu setiap sel kisi
        | ditumbuhkan menjadi pilar dari meja (atau dari permukaan model di
        | bawahnya) sampai menyentuh overhang tersebut.
        |
        | Karena volume support kini terukur dari geometri yang benar-benar
        | dibentuk, angka itulah yang dipakai untuk estimasi berat — rumus
        | simulasi di atas hanya menjadi cadangan bila visualisasi dilewati.
        */
        'visual' => [
            'overhang_angle_deg' => 45,     // ambang overhang, seperti default Cura
            'grid_size_mm' => 4.0,          // jarak antar pilar
            'max_cells_per_axis' => 44,     // batas agar model besar tetap ringan
            'pillar_shrink' => 0.62,        // lebar pilar relatif ukuran sel
            'min_pillar_height_mm' => 0.8,  // pilar lebih pendek dari ini diabaikan
            'plate_tolerance_mm' => 0.4,    // overhang yang sudah menempel meja dilewati
            'base_height_mm' => 0.8,        // pelat dasar tipis di bawah pilar
            'base_expand' => 1.3,           // pelebaran pelat dasar
            'color' => '#5AD4DE',           // biru muda transparan, seperti Cura
            'opacity' => 0.52,
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Resolusi (Layer Height)
    |----------------------------------------------------------------------
    | Pengali waktu mengikuti hubungan terbalik antara tebal lapisan dan jumlah
    | lapisan yang harus dicetak: 0,25 mm menjadi acuan (1,0), lapisan setengah
    | lebih tipis butuh waktu dua kali lipat, dan seterusnya.
    |
    | Pengali material sengaja dibuat kecil — tebal lapisan hampir tidak mengubah
    | volume bahan, hanya sedikit karena lapisan tipis menghasilkan dinding yang
    | lebih rapat sedangkan lapisan kasar cenderung kurang terisi.
    |
    | Seluruh angka di sini adalah simulasi dan mudah dikalibrasi.
    */
    'resolutions' => [
        'default' => '0.25',

        'options' => [
            '0.05' => [
                'layer_height' => 0.05,
                'name' => 'Ultra Fine',
                'quality' => 'Sangat Tinggi',
                'speed' => 'Paling Lambat',
                'time_multiplier' => 2.0,
                'material_multiplier' => 1.04,
                'recommendation' => 'Miniatur atau model dengan detail tinggi.',
                'highlights' => [
                    'Detail paling tinggi.',
                    'Permukaan sangat halus.',
                    'Waktu printing paling lama.',
                ],
            ],
            '0.10' => [
                'layer_height' => 0.10,
                'name' => 'Fine',
                'quality' => 'Tinggi',
                'speed' => 'Lambat',
                'time_multiplier' => 1.5,
                'material_multiplier' => 1.02,
                'recommendation' => 'Prototype berkualitas tinggi.',
                'highlights' => [
                    'Detail tinggi.',
                    'Permukaan halus.',
                    'Waktu printing lebih lama dibanding mode Normal.',
                ],
            ],
            '0.25' => [
                'layer_height' => 0.25,
                'name' => 'Normal',
                'quality' => 'Normal',
                'speed' => 'Seimbang',
                'time_multiplier' => 1.0,
                'material_multiplier' => 1.0,
                'recommendation' => 'Sebagian besar kebutuhan printing.',
                'highlights' => [
                    'Seimbang antara kualitas dan kecepatan.',
                    'Cocok untuk sebagian besar kebutuhan printing.',
                    'Menjadi pilihan default.',
                ],
            ],
            '0.50' => [
                'layer_height' => 0.50,
                'name' => 'Draft',
                'quality' => 'Draft',
                'speed' => 'Paling Cepat',
                'time_multiplier' => 0.5,
                'material_multiplier' => 0.97,
                'recommendation' => 'Prototype awal atau pengujian bentuk.',
                'highlights' => [
                    'Proses printing paling cepat.',
                    'Detail permukaan lebih kasar.',
                    'Cocok untuk prototype awal atau pengujian bentuk.',
                ],
            ],
        ],
    ],

    // Batas kewajaran ukuran model, dipakai pada analisis kelayakan cetak.
    'limits' => [
        'min_dimension_mm' => 2.0,   // di bawah ini part terlalu kecil untuk dicetak
        'warn_dimension_mm' => 5.0,  // di bawah ini perlu perhatian khusus
        // Di atas jumlah ini hanya volume & bounding box yang dihitung,
        // analisis topologi dilewati agar browser tidak membeku.
        'max_triangles_full_analysis' => 400000,
        // Banyaknya model 3D yang boleh masuk ke dalam satu permintaan
        // penawaran. Setiap model tetap tersimpan di memori browser selama
        // ditinjau, jadi model yang sangat banyak akan terasa berat.
        'max_models_per_quotation' => 25,

        // Ukuran maksimal satu berkas model. Batas ini hanya berlaku bila
        // php.ini mengizinkannya — `upload_max_filesize`, `post_max_size`, dan
        // `max_file_uploads` tetap menjadi batas keras yang tidak dapat
        // dilampaui aplikasi. Lihat App\Support\UploadLimit.
        'max_file_size_mb' => 300,

        // Ukuran gabungan seluruh berkas dalam satu permintaan penawaran.
        // Seluruh model dikirim dalam satu POST, jadi `post_max_size` pada
        // php.ini harus setidaknya sebesar ini agar batasnya benar-benar
        // berlaku. Lihat App\Support\UploadLimit.
        'max_total_size_mb' => 500,
    ],

    /*
    |----------------------------------------------------------------------
    | Teknologi & Material
    |----------------------------------------------------------------------
    | Satu-satunya sumber data katalog: halaman 3D Printing Guide, modal Edit
    | Specification, estimator di browser, dan perhitungan ulang di server
    | seluruhnya membaca dari sini. Jangan menyalin daftar ini ke view atau ke
    | JavaScript — tambahkan datanya di sini saja.
    |
    | Selain angka estimasi, tiap material membawa keterangan yang ditampilkan
    | pada panduan dan panel kiri Edit Specification:
    |
    |   description      penjelasan satu kalimat
    |   characteristics  sifat teknis singkat, label => nilai
    |   pros / cons      kelebihan dan kekurangan
    |   max_size         ukuran cetak terbesar yang diterima (mm)
    |   min_size         ukuran terkecil yang masih dapat dibentuk (mm)
    |   min_size_slender batas alternatif untuk part memanjang; model yang lolos
    |                    salah satu dari keduanya dianggap memenuhi syarat
    |
    | `max_size` sengaja tidak pernah melebihi `build_volume` teknologinya agar
    | validasi material tidak pernah bertentangan dengan pemeriksaan area cetak
    | yang sudah berjalan di viewer.
    */
    'technologies' => [

        'FDM' => [
            'name' => 'Fused Deposition Modeling',
            // Dipakai sebagai label pilihan teknologi, mis. "FDM (Plastic)".
            'family' => 'Plastic',
            'description' => 'Filamen termoplastik dilelehkan lalu diekstrusi lapis demi lapis. Paling ekonomis untuk prototipe fungsional, jig, dan part berukuran besar.',
            'build_volume' => ['x' => 500, 'y' => 500, 'z' => 600],
            // Dinding selalu padat; sisanya terisi sebanyak kepadatan infill.
            // Pada infill default 20% angkanya setara fill factor 0,45.
            'shell_ratio' => 0.3125,
            'default_infill' => 0.20,
            'min_wall_thickness_mm' => 1.2,
            'support_volume_factor' => 0.18, // Overhang di atas ~45 derajat perlu ditopang.
            'layer_height_range' => ['min' => 0.10, 'max' => 0.30], // rentang lapisan yang benar-benar tersedia
            'throughput_cm3_per_hour' => 16,
            'setup_hours' => 0.3,
            'setup_fee' => 25000,
            'machine_rate_per_hour' => 12000,
            // Materialnya (nama, harga, densitas, warna, batas ukuran) dikelola
            // admin lewat halaman Price List dan disuntikkan ke sini oleh
            // `PrintEstimator::technologies()` — lihat App\Models\FdmMaterial.
            // Jangan tulis material di sini lagi, akan selalu tertimpa.
            'materials' => [],
        ],

        'SLA' => [
            'name' => 'Stereolithography',
            'family' => 'Resin',
            'description' => 'Resin fotopolimer dikeraskan lapis demi lapis oleh sinar UV. Menghasilkan detail paling halus dan permukaan mulus untuk model presentasi dan part presisi.',
            'build_volume' => ['x' => 300, 'y' => 200, 'z' => 300],
            // Resin mengeras penuh, tidak ada rongga infill — pengurangan
            // material dilakukan lewat Hollow Model.
            'shell_ratio' => 1.0,
            'default_infill' => 1.00,
            'infill_note' => 'Part SLA mengeras padat sehingga kepadatan infill tidak mengubah pemakaian resin. Aktifkan Hollow Model untuk mengosongkan bagian dalamnya.',
            'min_wall_thickness_mm' => 0.8,
            'support_volume_factor' => 0.12, // Part digantung pada build plate, support relatif tipis.
            'layer_height_range' => ['min' => 0.025, 'max' => 0.10], // rentang lapisan yang benar-benar tersedia
            'throughput_cm3_per_hour' => 8,
            'setup_hours' => 0.5,
            'setup_fee' => 50000,
            'machine_rate_per_hour' => 30000,
            // Materialnya dikelola admin lewat halaman Price List — lihat
            // catatan yang sama di atas pada teknologi FDM.
            'materials' => [],
        ],

        'MJF' => [
            'name' => 'Multi Jet Fusion',
            'family' => 'Nylon',
            'description' => 'Serbuk nylon dilebur menyeluruh oleh fusing agent dan lampu inframerah. Tanpa support, kuat merata ke segala arah, dan efisien untuk produksi batch.',
            'build_volume' => ['x' => 380, 'y' => 284, 'z' => 380],
            // Part MJF umumnya dicetak padat, tetapi rongganya boleh dikurangi
            // karena serbuk di dalamnya dapat dikeluarkan setelah dicetak.
            'shell_ratio' => 0.55,
            'default_infill' => 1.00,
            'min_wall_thickness_mm' => 0.8,
            'support_volume_factor' => 0.0, // Tidak perlu support: part tertopang serbuk di sekelilingnya.
            'layer_height_range' => ['min' => 0.08, 'max' => 0.08], // rentang lapisan yang benar-benar tersedia
            'throughput_cm3_per_hour' => 22,
            'setup_hours' => 3.5,           // termasuk waktu pendinginan powder cake
            'setup_fee' => 120000,
            'machine_rate_per_hour' => 45000,
            'materials' => [
                'PA12' => [
                    'density' => 1.01,
                    'price_per_gram' => 3500,
                    'colors' => ['abu', 'hitam', 'putih'],
                    'description' => 'Nylon serbuk standar industri untuk part fungsional dan produksi batch kecil.',
                    'characteristics' => [
                        'Kekuatan' => 'Tinggi, merata ke segala arah',
                        'Tahan panas' => 'Baik, sampai ±160 °C',
                        'Kelenturan' => 'Liat',
                        'Permukaan' => 'Bertekstur matte',
                    ],
                    'pros' => ['Kuat merata ke segala arah', 'Tanpa support, geometri rumit bebas dibuat'],
                    'cons' => ['Permukaan bertekstur seperti pasir', 'Sedikit menyerap kelembapan'],
                    'max_size' => ['x' => 370, 'y' => 276, 'z' => 360],
                    'min_size' => ['x' => 5, 'y' => 5, 'z' => 5],
                    'min_size_slender' => ['x' => 10, 'y' => 2, 'z' => 2],
                ],
                'PA11' => [
                    'density' => 1.03,
                    'price_per_gram' => 4200,
                    'colors' => ['abu', 'hitam'],
                    'description' => 'Nylon berbahan dasar nabati, lebih liat dan tahan benturan daripada PA12.',
                    'characteristics' => [
                        'Kekuatan' => 'Tinggi',
                        'Tahan panas' => 'Baik, sampai ±180 °C',
                        'Kelenturan' => 'Sangat liat',
                        'Permukaan' => 'Bertekstur matte',
                    ],
                    'pros' => ['Lebih liat dan tahan lelah', 'Cocok untuk engsel hidup dan klip'],
                    'cons' => ['Lebih mahal daripada PA12', 'Kekakuannya sedikit lebih rendah'],
                    'max_size' => ['x' => 370, 'y' => 276, 'z' => 360],
                    'min_size' => ['x' => 5, 'y' => 5, 'z' => 5],
                    'min_size_slender' => ['x' => 10, 'y' => 2, 'z' => 2],
                ],
            ],
        ],

        'SLM' => [
            'name' => 'Selective Laser Melting',
            'family' => 'Metal',
            'description' => 'Serbuk logam dilelehkan sepenuhnya oleh laser berdaya tinggi di ruang bebas oksigen. Menghasilkan part logam padat dengan sifat mekanis setara logam tempa.',
            'build_volume' => ['x' => 250, 'y' => 250, 'z' => 300],
            // Rongga logam dapat dikurangi untuk menghemat serbuk dan waktu
            // laser, selama dindingnya tetap tebal.
            'shell_ratio' => 0.60,
            'default_infill' => 1.00,
            'min_wall_thickness_mm' => 0.5,
            'support_volume_factor' => 0.22, // Support wajib untuk menahan tegangan termal dan menopang overhang.
            'layer_height_range' => ['min' => 0.02, 'max' => 0.06], // rentang lapisan yang benar-benar tersedia
            'throughput_cm3_per_hour' => 3,
            'setup_hours' => 2.0,
            'setup_fee' => 500000,
            'machine_rate_per_hour' => 250000,
            // Part logam diserahkan dalam warna aslinya; pewarnaan dilakukan
            // lewat finishing, bukan lewat pilihan warna material.
            'materials' => [
                'Stainless Steel' => [
                    'density' => 7.99,
                    'price_per_gram' => 9000,
                    'colors' => ['logam'],
                    'description' => 'Logam serbaguna yang kuat dan tahan korosi untuk part akhir maupun tooling.',
                    'characteristics' => [
                        'Kekuatan' => 'Sangat tinggi',
                        'Tahan panas' => 'Sangat baik',
                        'Kelenturan' => 'Kaku',
                        'Permukaan' => 'Kasar, dapat dimesin dan dipoles',
                    ],
                    'pros' => ['Kuat dan tahan korosi', 'Dapat dimesin serta dipoles setelah cetak'],
                    'cons' => ['Paling berat di antara pilihan logam', 'Waktu cetak panjang'],
                    'max_size' => ['x' => 250, 'y' => 250, 'z' => 300],
                    'min_size' => ['x' => 5, 'y' => 5, 'z' => 5],
                    'min_size_slender' => ['x' => 10, 'y' => 2, 'z' => 2],
                ],
                'Aluminum' => [
                    'density' => 2.67,
                    'price_per_gram' => 12000,
                    'colors' => ['logam'],
                    'description' => 'Logam ringan dengan konduktivitas panas tinggi. Umum untuk bracket dan heat sink.',
                    'characteristics' => [
                        'Kekuatan' => 'Tinggi terhadap bobotnya',
                        'Tahan panas' => 'Baik',
                        'Kelenturan' => 'Kaku',
                        'Permukaan' => 'Kasar, dapat dimesin dan dipoles',
                    ],
                    'pros' => ['Rasio kekuatan terhadap berat baik', 'Melepas panas dengan cepat'],
                    'cons' => ['Lebih lunak daripada baja', 'Perlu perlakuan panas untuk hasil terbaik'],
                    'max_size' => ['x' => 250, 'y' => 250, 'z' => 300],
                    'min_size' => ['x' => 5, 'y' => 5, 'z' => 5],
                    'min_size_slender' => ['x' => 10, 'y' => 2, 'z' => 2],
                ],
                'Titanium' => [
                    'density' => 4.43,
                    'price_per_gram' => 28000,
                    'colors' => ['logam'],
                    'description' => 'Logam paling kuat sekaligus ringan, biokompatibel, untuk part kritis.',
                    'characteristics' => [
                        'Kekuatan' => 'Tertinggi dengan bobot ringan',
                        'Tahan panas' => 'Sangat baik',
                        'Kelenturan' => 'Kaku',
                        'Permukaan' => 'Kasar, dapat dimesin dan dipoles',
                    ],
                    'pros' => ['Kekuatan tertinggi dengan bobot ringan', 'Tahan korosi dan biokompatibel'],
                    'cons' => ['Material paling mahal', 'Support wajib dan sulit dilepas'],
                    'max_size' => ['x' => 250, 'y' => 250, 'z' => 300],
                    'min_size' => ['x' => 5, 'y' => 5, 'z' => 5],
                    'min_size_slender' => ['x' => 10, 'y' => 2, 'z' => 2],
                ],
            ],
        ],

    ],

    /*
    |----------------------------------------------------------------------
    | Nama Material yang Dilihat Pelanggan
    |----------------------------------------------------------------------
    | HANYA untuk teknologi yang daftar materialnya masih tinggal di config
    | ini, yaitu MJF dan SLM — keduanya belum punya tab Price List sendiri.
    |
    | FDM dan SLA TIDAK ada di sini dengan sengaja. Daftar materialnya
    | dikelola Superadmin lewat Price List, dan Price List itulah satu-satunya
    | penentu: nama yang tampil adalah kolom `material` pada barisnya, dan
    | yang ditawarkan adalah seluruh baris yang ada. Mengisi keduanya di sini
    | akan mengembalikan daftar tetap, yang justru membuat material baru pada
    | Price List tidak pernah muncul di Edit Specification.
    |
    | Untuk MJF/SLM: KUNCI adalah nama katalog yang tersimpan pada penawaran
    | dan menentukan harga, NILAI adalah nama yang ditampilkan. Urutan baris
    | menentukan urutan pilihan, dan daftar kuncinya sekaligus menentukan
    | material mana yang ditawarkan — yang tidak disebut tetap ada untuk
    | kebutuhan internal, hanya tidak muncul sebagai pilihan pelanggan.
    |
    | Teknologi yang petanya kosong ditawarkan apa adanya, dengan nama
    | katalognya sendiri.
    */
    'material_display' => [
        // Dikelola Price List — lihat catatan di atas.
        'FDM' => [],
        'SLA' => [],

        'MJF' => [
            'PA12' => 'PA 12 Nylon',
        ],

        'SLM' => [
            'Stainless Steel' => 'Stainless BJ 316L',
            'Titanium' => 'Titanium TC4 Metal',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Lead Time Pengerjaan
    |----------------------------------------------------------------------
    | Yang dibutuhkan pelanggan bukan lama mesin berputar, melainkan kapan
    | pesanannya selesai. Jam mesin hasil estimasi karena itu tidak lagi
    | ditampilkan apa adanya, melainkan diterjemahkan menjadi rentang hari
    | kerja yang sudah memperhitungkan antrean, post-processing, dan QC.
    |
    | Tiap tingkat berlaku selama total menit mesin masih di bawah atau sama
    | dengan `max_minutes`; tingkat terakhir (`max_minutes` null) menjadi
    | penampung untuk pekerjaan yang lebih besar dari itu.
    |
    | Yang dibandingkan adalah TOTAL waktu proses seluruh 3D object dalam satu
    | penawaran, bukan waktu satu object. Penawaran berisi tiga object 8 + 6 + 5
    | jam berjumlah 19 jam sehingga masih Express, sedangkan 10 + 7 + 5 jam
    | berjumlah 22 jam dan menjadi Standard.
    */
    'lead_time' => [
        'unit' => 'Hari Kerja',

        'tiers' => [
            // 20 jam = 1.200 menit.
            ['name' => 'Express', 'max_minutes' => 1200, 'min_days' => 1, 'max_days' => 1],
            ['name' => 'Standard', 'max_minutes' => null, 'min_days' => 3, 'max_days' => 5],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Alur Status Penawaran
    |----------------------------------------------------------------------
    | Urutan kunci bergrup `flow` menentukan urutan langkah pada timeline
    | halaman tracking maupun dashboard: langkah sebelum status sekarang
    | dianggap selesai (hijau), status sekarang menjadi langkah aktif (warna
    | brand), sisanya belum diproses (abu-abu).
    |
    | Status bergrup `cancellation` berada di luar alur — status ini tidak
    | pernah muncul pada timeline dan tidak dapat dipilih langsung dari
    | dropdown admin, melainkan dipasang oleh aksi pembatalan (pengajuan oleh
    | user, lalu persetujuan atau penolakan oleh admin).
    |
    | Menambah atau menyusun ulang tahap cukup dilakukan di sini — timeline,
    | filter admin, riwayat, dan notifikasi mengikutinya otomatis.
    */
    'quotation_statuses' => [
        // Tahap pertama sekaligus satu-satunya tahap yang isinya masih boleh
        // diubah pemiliknya. Penawaran baru langsung masuk ke sini — tidak
        // ada lagi antrean "Menunggu Review" sebelumnya.
        'reviewing' => [
            'label' => 'File Sedang Direview',
            'description' => 'Permintaan Anda sudah masuk dan engineer kami sedang memeriksa file modelnya.',
            'group' => 'flow',
            'editable' => true,
        ],
        'awaiting_payment' => [
            'label' => 'Menunggu Pembayaran',
            'description' => 'Menunggu penyelesaian pembayaran.',
            'group' => 'flow',
        ],
        'payment_review' => [
            'label' => 'Pengecekan Pembayaran',
            'description' => 'Bukti pembayaran sudah diterima dan sedang diverifikasi admin.',
            'group' => 'flow',
        ],
        'payment_received' => [
            'label' => 'Pembayaran Diterima',
            'description' => 'Pembayaran Anda sudah kami terima.',
            'group' => 'flow',
        ],
        'production' => [
            'label' => 'Sedang Diproduksi',
            'description' => 'Part Anda sedang dicetak.',
            'group' => 'flow',
        ],
        'quality_control' => [
            'label' => 'Quality Control',
            'description' => 'Pemeriksaan dimensi dan tampilan sebelum dikirim.',
            'group' => 'flow',
        ],
        'ready_to_ship' => [
            'label' => 'Siap Dikirim',
            'description' => 'Part sudah dikemas dan siap dikirim.',
            'group' => 'flow',
        ],
        'completed' => [
            'label' => 'Selesai',
            'description' => 'Pengerjaan selesai dan part sudah diserahkan.',
            'group' => 'flow',
        ],

        // Bukti pembayaran ditolak admin. Bukan pembatalan — penawaran tetap
        // berjalan dan pemiliknya dapat mengunggah bukti yang benar selama
        // batas waktunya belum lewat.
        'payment_rejected' => [
            'label' => 'Pembayaran Ditolak',
            'description' => 'Bukti pembayaran ditolak admin. Silakan periksa alasannya lalu unggah ulang bukti yang sesuai.',
            'group' => 'payment',
        ],

        'cancelled_by_user' => [
            'label' => 'Dibatalkan oleh User',
            'description' => 'Penawaran dibatalkan sebelum masuk proses review.',
            'group' => 'cancellation',
        ],
        'cancellation_requested' => [
            'label' => 'Permintaan Pembatalan',
            'description' => 'Pengajuan pembatalan menunggu persetujuan admin.',
            'group' => 'cancellation',
        ],
        'cancellation_approved' => [
            'label' => 'Pembatalan Disetujui',
            'description' => 'Admin menyetujui pembatalan, penawaran dihentikan.',
            'group' => 'cancellation',
        ],
        'cancellation_rejected' => [
            'label' => 'Pembatalan Ditolak',
            'description' => 'Admin menolak pembatalan, penawaran diteruskan.',
            'group' => 'cancellation',
        ],

        // Dipasang sistem, bukan admin: batas waktu pembayaran 24 jam terlewati.
        'payment_expired' => [
            'label' => 'Penawaran Dibatalkan (Expired)',
            'description' => 'Batas waktu pembayaran terlewati sehingga penawaran dibatalkan otomatis oleh sistem.',
            'group' => 'cancellation',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Kurs USD/IDR untuk SLA Industries
    |----------------------------------------------------------------------
    | "Dollar Hari Ini" pada Form Perhitungan SLA Industries tidak lagi
    | diketik Admin — nilainya diambil sistem dari penyedia kurs.
    |
    | PENTING, bedanya dua jenis sumber:
    |
    |   daily     kurs REFERENSI yang diterbitkan sekali per hari kerja.
    |             Contohnya JISDOR Bank Indonesia dan kurs acuan ECB. Angkanya
    |             tidak berubah sepanjang hari, jadi menariknya tiap lima menit
    |             hanya menghabiskan kuota tanpa pernah menghasilkan nilai baru.
    |
    |   intraday  kurs PASAR yang bergerak sepanjang hari. Baru di sinilah
    |             penyegaran berkala benar-benar berarti.
    |
    | Penyedia tanpa kunci API yang tersedia umum seluruhnya bersifat `daily`;
    | kurs intraday menuntut akun berbayar. Karena itu bawaannya `daily`, dan
    | penyedia intraday tinggal diaktifkan dengan mengisi kuncinya di .env —
    | tanpa satu baris kode pun berubah.
    |
    | `ttl_seconds` adalah umur simpan di sisi server. Halaman admin membaca
    | simpanan yang sama, jadi angka di layar dan angka yang dipakai server saat
    | menyimpan perhitungan tidak mungkin berbeda.
    */
    'usd_rate' => [
        'provider' => env('USD_RATE_PROVIDER', 'open-er-api'),

        // Jaring pengaman terhadap salah konfigurasi maupun jawaban penyedia
        // yang kacau: kurs di luar rentang ini ditolak, bukan dipakai.
        'min' => 1000,
        'max' => 1000000,

        'providers' => [
            // Tanpa kunci API. Membawa stempel waktu terbitnya sendiri, jadi
            // "Terakhir diperbarui" menyebut kapan KURSNYA terbit — bukan
            // kapan sistem kebetulan menariknya.
            'open-er-api' => [
                'label' => 'ExchangeRate-API',
                'note' => 'Kurs referensi harian',
                'cadence' => 'daily',
                'url' => 'https://open.er-api.com/v6/latest/USD',
                'rate_path' => 'rates.IDR',
                'updated_at_path' => 'time_last_update_unix',
                'ttl_seconds' => 3600,
            ],

            // Kurs acuan Bank Sentral Eropa, juga tanpa kunci API.
            'frankfurter' => [
                'label' => 'Frankfurter (kurs acuan ECB)',
                'note' => 'Kurs referensi harian',
                'cadence' => 'daily',
                'url' => 'https://api.frankfurter.dev/v1/latest?base=USD&symbols=IDR',
                'rate_path' => 'rates.IDR',
                'updated_at_path' => 'date',
                'ttl_seconds' => 3600,
            ],

            /*
             | Kurs pasar intraday — perlu kunci API. Isi USD_RATE_PROVIDER=
             | exchangerate-host dan EXCHANGERATE_HOST_KEY= di .env untuk
             | memakainya; barulah penyegaran berkala benar-benar berarti.
             */
            'exchangerate-host' => [
                'label' => 'exchangerate.host',
                'note' => 'Kurs pasar intraday',
                'cadence' => 'intraday',
                'url' => 'https://api.exchangerate.host/live?source=USD&currencies=IDR&access_key='
                    .env('EXCHANGERATE_HOST_KEY', ''),
                'rate_path' => 'quotes.USDIDR',
                'updated_at_path' => 'timestamp',
                'ttl_seconds' => 300,
            ],
        ],
    ],

];
