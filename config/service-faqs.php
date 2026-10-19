<?php

/*
|--------------------------------------------------------------------------
| FAQ per Halaman Tujuan Iklan
|--------------------------------------------------------------------------
| Kunci = slug landing page layanan (/services/{slug}) dan `miniatur`
| (/miniatur-teknik). Ditampilkan komponen <x-faq-section> sebagai accordion,
| dan FAQPage JSON-LD-nya dibentuk dari daftar yang sama persis.
|
| Setiap halaman WAJIB memuat lima topik — format file, minimal order, lama
| pengerjaan, pengiriman luar Jabodetabek, dan NDA — dengan jawaban yang
| disesuaikan layanannya, lalu boleh ditambah pertanyaan khusus.
|
| Jawaban hanya memuat hal yang sudah tercantum/berlaku di website:
|   - format 3D Models  : App\Support\ModelFormat (STL, STP, STEP, OBJ, 3MF);
|   - lama pengerjaan    : {lead_time} diganti otomatis dari config
|                          printing.lead_time (pilihan Standard/Express yang
|                          dipakai sistem pemesanan);
|   - rincian layanan    : uraian & highlight tiap layanan di tabel services;
|   - NDA                : pernyataan perusahaan di Profil Perusahaan;
|   - harga miniatur     : ketetapan owner (config/miniatur.php).
| Tidak ada angka minimum order, jangkauan pengiriman, atau waktu yang
| belum dikonfirmasi. Bila owner menetapkan ketentuan baru, ubah di sini.
*/

$nda = 'Ya. Seluruh file dan dokumen proyek klien diperlakukan sebagai rahasia. Bila proyek Anda memerlukan Non-Disclosure Agreement (NDA), sampaikan saat konsultasi — kami siap menandatanganinya sebelum file dikirim.';

$shippingDigital = 'Bisa didiskusikan. Pengiriman ke luar Jabodetabek disesuaikan dengan lokasi, ukuran, dan karakteristik barang, dan detailnya dikonfirmasi tim kami sebelum barang dikirim.';

return [
    '3d-printing' => [
        [
            'question' => 'Format file apa saja yang dapat digunakan?',
            'answer' => 'Untuk estimasi instan di halaman 3D Models, sistem menerima file STL, STP, STEP, OBJ, dan 3MF. Gambar kerja atau format CAD lain dapat dikirim lewat konsultasi agar ditinjau tim engineer lebih dulu.',
        ],
        [
            'question' => 'Apakah ada minimal jumlah pemesanan?',
            'answer' => 'Tidak ada jumlah minimum yang ditetapkan secara umum — melalui halaman 3D Models, permintaan dapat diajukan mulai 1 unit per model. Untuk pesanan dalam jumlah besar, ketentuannya disesuaikan dengan jenis part dan kebutuhan produksi.',
        ],
        [
            'question' => 'Berapa lama proses pengerjaannya?',
            'answer' => 'Lama pengerjaan bergantung pada dimensi, kompleksitas desain, jumlah pesanan, material, dan finishing. Saat mengirim permintaan di halaman 3D Models tersedia pilihan {lead_time}. Tanggal penyelesaiannya dikonfirmasi tim kami saat permintaan ditinjau.',
        ],
        [
            'question' => 'Apakah NUSAMA3D melayani pengiriman ke luar Jabodetabek?',
            'answer' => 'Bisa didiskusikan. Alamat pengiriman dipilih dari buku alamat akun Anda saat mengirim permintaan, dan pengiriman ke luar Jabodetabek disesuaikan dengan lokasi, ukuran, serta karakteristik part. Detailnya dikonfirmasi tim kami sebelum barang dikirim.',
        ],
        [
            'question' => 'Apakah desain dan dokumen proyek dapat dilindungi dengan NDA?',
            'answer' => $nda,
        ],
        [
            'question' => 'Teknologi 3D printing apa saja yang tersedia?',
            'answer' => 'Kami mengoperasikan FDM, SLA, MJF, dan SLM, sehingga teknologi dan material dapat dipilih sesuai fungsi part, mulai dari prototipe hingga part logam.',
        ],
        [
            'question' => 'Apakah file saya diperiksa sebelum dicetak?',
            'answer' => 'Ya. Tim engineering kami meninjau file sebelum produksi — termasuk wall thickness, toleransi, dan orientasi cetak — tanpa biaya tambahan.',
        ],
    ],

    'reverse-engineering' => [
        [
            'question' => 'Format file apa saja yang dapat digunakan?',
            'answer' => 'Yang dibutuhkan adalah part fisiknya, karena geometrinya kami pindai lalu direkonstruksi. Foto, gambar kerja lama, atau file CAD yang masih ada (bila ada) membantu sebagai referensi. Format file hasil akhirnya disepakati saat konsultasi sesuai software yang Anda gunakan.',
        ],
        [
            'question' => 'Apakah ada minimal jumlah pemesanan?',
            'answer' => 'Tidak ada jumlah minimum yang ditetapkan secara umum. Ruang lingkup pekerjaan ditentukan bersama berdasarkan jumlah part, kompleksitasnya, dan hasil yang Anda butuhkan.',
        ],
        [
            'question' => 'Berapa lama proses pengerjaannya?',
            'answer' => 'Bergantung pada dimensi dan kompleksitas geometri part, tingkat akurasi yang dibutuhkan, jumlah part, serta kelengkapan hasil yang diminta (model CAD, gambar kerja, atau part cetak ulang). Estimasinya kami sampaikan setelah part dan kebutuhan Anda ditinjau.',
        ],
        [
            'question' => 'Apakah NUSAMA3D melayani pengiriman ke luar Jabodetabek?',
            'answer' => 'Bisa didiskusikan. Pengiriman part ke workshop kami maupun pengembaliannya — beserta part hasil produksi ulang, bila ada — disesuaikan dengan lokasi, ukuran, dan karakteristik barang. File CAD hasil pekerjaan dikirim secara digital.',
        ],
        [
            'question' => 'Apakah desain dan dokumen proyek dapat dilindungi dengan NDA?',
            'answer' => $nda,
        ],
        [
            'question' => 'Apa hasil akhir layanan reverse engineering?',
            'answer' => 'Model CAD parametrik yang dapat diedit (bukan sekadar mesh), laporan analisis deviasi terhadap benda aslinya, serta gambar kerja 2D siap produksi.',
        ],
    ],

    '3d-scanning' => [
        [
            'question' => 'Format file apa saja yang dapat digunakan?',
            'answer' => 'Layanan ini dimulai dari objek fisiknya, jadi Anda tidak perlu menyiapkan file. Data hasil pemindaian kami kirim dalam format STL, OBJ, atau point cloud sesuai kebutuhan Anda.',
        ],
        [
            'question' => 'Apakah ada minimal jumlah pemesanan?',
            'answer' => 'Tidak ada jumlah minimum yang ditetapkan secara umum. Ketentuannya bergantung pada jumlah dan ukuran objek, serta apakah pemindaian dilakukan di workshop atau on-site.',
        ],
        [
            'question' => 'Berapa lama proses pengerjaannya?',
            'answer' => 'Bergantung pada dimensi objek, kerumitan permukaannya, jumlah objek, tingkat detail yang dibutuhkan, dan lokasi pemindaian. Estimasinya kami sampaikan setelah objek dan kebutuhan Anda ditinjau.',
        ],
        [
            'question' => 'Apakah NUSAMA3D melayani pengiriman atau pemindaian di luar Jabodetabek?',
            'answer' => 'Objek dapat dikirim ke workshop kami, dan untuk objek yang besar atau sulit dipindahkan tersedia layanan pemindaian on-site. Ketersediaan on-site maupun pengiriman di luar Jabodetabek dikonfirmasi saat konsultasi sesuai lokasi, ukuran, dan karakteristik objek.',
        ],
        [
            'question' => 'Apakah desain dan dokumen proyek dapat dilindungi dengan NDA?',
            'answer' => $nda,
        ],
        [
            'question' => 'Seberapa presisi hasil pemindaiannya?',
            'answer' => 'Pemindaian memakai scanner cahaya terstruktur dengan akurasi hingga 0,05 mm, sehingga datanya dapat dipakai untuk desain ulang maupun inspeksi dimensi.',
        ],
    ],

    'product-development' => [
        [
            'question' => 'Format file apa saja yang dapat digunakan?',
            'answer' => 'Proses dapat dimulai dari ide, sketsa, foto produk acuan, atau file CAD yang sudah Anda miliki. Hasilnya berupa model CAD, prototipe, dan dokumen teknis sesuai tahapan yang disepakati.',
        ],
        [
            'question' => 'Apakah ada minimal jumlah pemesanan?',
            'answer' => 'Layanan ini berbasis proyek, bukan jumlah unit, sehingga tidak ada jumlah minimum yang ditetapkan secara umum. Ruang lingkup tahapan dan jumlah prototipe disepakati di awal sesuai kebutuhan produk Anda.',
        ],
        [
            'question' => 'Berapa lama proses pengerjaannya?',
            'answer' => 'Bergantung pada tahapan yang dibutuhkan, kompleksitas desain, jumlah iterasi, serta material dan finishing prototipe. Estimasi waktu per tahap kami sampaikan setelah kebutuhan Anda ditinjau.',
        ],
        [
            'question' => 'Apakah NUSAMA3D melayani pengiriman ke luar Jabodetabek?',
            'answer' => $shippingDigital.' Model CAD dan dokumen teknis dikirim secara digital.',
        ],
        [
            'question' => 'Apakah desain dan dokumen proyek dapat dilindungi dengan NDA?',
            'answer' => $nda,
        ],
        [
            'question' => 'Tahapan apa saja yang termasuk dalam product development?',
            'answer' => 'Mulai dari studi kelayakan, sketsa konsep, pemodelan CAD, pembuatan prototipe fungsional, uji coba, hingga dokumen teknis dan Design for Manufacturing (DFM).',
        ],
    ],

    '3d-design' => [
        [
            'question' => 'Format file apa saja yang dapat digunakan?',
            'answer' => 'Anda dapat mengirim sketsa, foto, atau gambar 2D beserta ukuran pentingnya, maupun file CAD yang ingin direvisi. Model hasil desain disiapkan untuk dicetak 3D maupun dikerjakan dengan CNC, dan format file keluarannya disepakati saat konsultasi.',
        ],
        [
            'question' => 'Apakah ada minimal jumlah pemesanan?',
            'answer' => 'Tidak ada jumlah minimum yang ditetapkan secara umum. Ruang lingkup pekerjaan disesuaikan dengan jumlah model atau part, tingkat detail, dan kebutuhan proyek Anda.',
        ],
        [
            'question' => 'Berapa lama proses pengerjaannya?',
            'answer' => 'Bergantung pada kompleksitas desain, jumlah part atau rakitan, tingkat detail, kebutuhan rendering, dan jumlah revisi. Estimasinya kami sampaikan setelah referensi dan kebutuhan Anda ditinjau.',
        ],
        [
            'question' => 'Apakah NUSAMA3D melayani pengiriman ke luar Jabodetabek?',
            'answer' => 'Hasil desain berupa file digital sehingga dikirim secara elektronik ke mana pun. Bila desain dilanjutkan ke pencetakan, pengiriman part ke luar Jabodetabek dapat didiskusikan sesuai lokasi, ukuran, dan karakteristik barang.',
        ],
        [
            'question' => 'Apakah desain dan dokumen proyek dapat dilindungi dengan NDA?',
            'answer' => $nda,
        ],
        [
            'question' => 'Saya hanya punya sketsa atau foto. Bisa dibuatkan model 3D?',
            'answer' => 'Bisa. Kirimkan sketsa, foto, atau gambar 2D beserta ukuran pentingnya; tim kami membuatkan model 3D yang siap cetak maupun siap CNC.',
        ],
    ],

    'paint-finishing' => [
        [
            'question' => 'Format file apa saja yang dapat digunakan?',
            'answer' => 'Layanan ini mengerjakan part fisik hasil cetak 3D, jadi tidak memerlukan file. Untuk warna, sertakan kode RAL atau Pantone, atau kirim foto dan contoh warna sebagai acuan. Untuk part dari sumber lain, tanyakan kepada tim kami lebih dulu.',
        ],
        [
            'question' => 'Apakah ada minimal jumlah pemesanan?',
            'answer' => 'Tidak ada jumlah minimum yang ditetapkan secara umum. Ketentuannya bergantung pada jumlah dan ukuran part serta jenis finishing yang dipilih.',
        ],
        [
            'question' => 'Berapa lama proses pengerjaannya?',
            'answer' => 'Bergantung pada dimensi dan jumlah part, tingkat kehalusan permukaan, jenis finishing (cat, metalik, electroplating, atau clear coat), serta waktu pengeringannya. Estimasinya kami sampaikan setelah part dan kebutuhan Anda ditinjau.',
        ],
        [
            'question' => 'Apakah NUSAMA3D melayani pengiriman ke luar Jabodetabek?',
            'answer' => 'Bisa didiskusikan. Pengiriman disesuaikan dengan lokasi, ukuran, dan karakteristik barang — termasuk permukaan yang sudah dicat — dan detailnya dikonfirmasi tim kami sebelum barang dikirim.',
        ],
        [
            'question' => 'Apakah desain dan dokumen proyek dapat dilindungi dengan NDA?',
            'answer' => $nda,
        ],
        [
            'question' => 'Pilihan hasil akhir apa saja yang tersedia?',
            'answer' => 'Finish matte, satin, glossy, metalik, hingga soft-touch, dengan pencocokan warna berdasarkan kode RAL atau Pantone. Tersedia juga opsi electroplating dan clear coat tahan UV.',
        ],
    ],

    'miniatur' => [
        [
            'question' => 'Format file apa saja yang dapat digunakan?',
            'answer' => 'Lewat formulir di halaman ini Anda dapat melampirkan gambar referensi atau desain berformat JPG, PNG, WebP, atau PDF. Bila sudah ada file 3D (STL, STP, STEP, OBJ, atau 3MF), sampaikan saat konsultasi agar dapat ditinjau tim kami.',
        ],
        [
            'question' => 'Apakah ada minimal jumlah pemesanan?',
            'answer' => 'Ketentuan jumlah disesuaikan dengan jenis proyek. Harga mulai Rp1 juta per unit, dan untuk pesanan lebih dari 25 pcs, harga khusus diberikan setelah review desain.',
        ],
        [
            'question' => 'Berapa lama proses pengerjaannya?',
            'answer' => 'Bergantung pada dimensi, tingkat detail, kompleksitas desain, jumlah pesanan, material, dan finishing. Desain dikonsultasikan lebih dulu sebelum produksi, dan estimasi waktunya kami sampaikan setelah review desain.',
        ],
        [
            'question' => 'Apakah NUSAMA3D melayani pengiriman ke luar Jabodetabek?',
            'answer' => 'Bisa didiskusikan. Pengiriman disesuaikan dengan lokasi, ukuran, dan karakteristik miniatur atau souvenir, dan detailnya dikonfirmasi tim kami sebelum barang dikirim.',
        ],
        [
            'question' => 'Apakah desain dan dokumen proyek dapat dilindungi dengan NDA?',
            'answer' => $nda,
        ],
        [
            'question' => 'Apakah ada konsultasi desain sebelum produksi?',
            'answer' => 'Ya. Skala, tingkat detail, material, dan finishing dikonsultasikan bersama Anda sebelum produksi dimulai.',
        ],
    ],
];
