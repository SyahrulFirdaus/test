<?php

/*
|--------------------------------------------------------------------------
| Detail Halaman per Industri (/industri/{slug})
|--------------------------------------------------------------------------
| Melengkapi config/industries.php; kuncinya adalah `slug` industri.
|
| - challenges    : pasangan tantangan khas industri → solusi 3D printing.
| - material_notes: kegunaan tiap material (dari `materials` di
|                   config/industries.php) untuk industri ini. Sifat umum
|                   materialnya ada di config/industry-guide.php.
| - finishing     : pilihan finishing yang paling relevan.
| - faqs          : pertanyaan umum; juga diterbitkan sebagai data
|                   terstruktur FAQPage untuk mesin pencari.
| - clients       : slug klien (tabel `clients`) dari sektor ini; diambil
|                   dari kolom `industry` milik data klien perusahaan.
| - case_studies  : studi kasus proyek nyata — ['title', 'summary',
|                   'image' (opsional)]. Kosong = bagiannya tidak tampil.
| - stats         : angka hasil nyata — ['value', 'label']. Kosong = tidak
|                   tampil. Jangan diisi angka perkiraan.
*/

return [
    'otomotif-motorsport' => [
        'challenges' => [
            ['problem' => 'Part lama atau aftermarket sudah tidak diproduksi lagi.', 'solution' => 'Part dipindai atau diukur ulang, dimodelkan, lalu dicetak dalam material yang setara atau lebih kuat dari aslinya.'],
            ['problem' => 'Iterasi prototipe dengan tooling konvensional mahal dan lama.', 'solution' => 'Prototipe uji pasang dicetak langsung dari file CAD tanpa cetakan, sehingga revisi desain bisa diuji berkali-kali dalam waktu singkat.'],
            ['problem' => 'Part di ruang mesin dan di perahu terpapar panas, getaran, air, dan sinar UV.', 'solution' => 'Material dipilih sesuai kondisi kerja: Nylon PA12 dan Carbon Fiber Nylon untuk beban mekanis, ASA untuk paparan UV dan cuaca.'],
        ],
        'material_notes' => [
            'Nylon PA12' => 'Bracket, klip, dan ducting fungsional yang menerima beban dan getaran.',
            'ASA' => 'Part eksterior dan perlengkapan perahu yang terkena sinar matahari dan air.',
            'Carbon Fiber Nylon' => 'Part ringan yang kaku untuk motorsport, seperti mounting dan bracket.',
            'Aluminium AlSi10Mg' => 'Komponen logam ringan untuk beban dan suhu yang lebih tinggi.',
        ],
        'finishing' => [
            ['name' => 'Amplas & cat', 'description' => 'Permukaan dihaluskan lalu dicat sesuai warna kendaraan untuk part yang terlihat.'],
            ['name' => 'Clear coat tahan UV', 'description' => 'Lapisan pelindung untuk part eksterior dan perlengkapan perahu yang terkena sinar matahari.'],
            ['name' => 'Finish matte, satin, atau glossy', 'description' => 'Tingkat kilap disesuaikan agar serasi dengan part bawaan kendaraan.'],
        ],
        'faqs' => [
            ['question' => 'Apakah part hasil cetak tahan dipasang di ruang mesin?', 'answer' => 'Bisa, selama materialnya sesuai. Untuk area bersuhu tinggi kami merekomendasikan material teknik seperti Carbon Fiber Nylon atau logam. Ceritakan lokasi pemasangan part dan kami bantu memilih materialnya.'],
            ['question' => 'Saya hanya punya part fisik yang rusak, tanpa file 3D. Bisa dibuatkan?', 'answer' => 'Bisa. Kami menyediakan layanan 3D scanning dan reverse engineering untuk membuat model 3D dari part fisik, lalu mencetaknya.'],
            ['question' => 'Apakah bisa mencetak dalam jumlah kecil untuk produksi?', 'answer' => 'Bisa. 3D printing cocok untuk produksi low-volume karena tidak membutuhkan biaya cetakan; harga per unit bergantung pada ukuran, material, dan jumlah.'],
            ['question' => 'Apakah part aman untuk dipakai di air laut?', 'answer' => 'Material polimer seperti ASA dan Nylon PA12 tidak berkarat dan umum dipakai untuk perlengkapan perahu. Untuk part logam di air laut, kami akan merekomendasikan material yang tahan korosi.'],
        ],
        'clients' => ['mikuni', 'dharma-group'],
        'case_studies' => [],
        'stats' => [],
    ],

    'aerospace-pertahanan-uav' => [
        'challenges' => [
            ['problem' => 'Setiap gram bobot memengaruhi waktu terbang dan kapasitas payload.', 'solution' => 'Geometri lattice dan topologi yang dioptimasi memangkas bobot tanpa mengorbankan kekakuan struktur.'],
            ['problem' => 'Kebutuhan part bersifat low-volume dan sering berubah desain.', 'solution' => 'Tanpa tooling, setiap revisi desain bisa langsung dicetak, cocok untuk pengembangan dan produksi terbatas.'],
            ['problem' => 'Spare part untuk perawatan sulit didapat dan lama datangnya.', 'solution' => 'Part pengganti dapat diproduksi sesuai kebutuhan (on-demand) dari file digital, tanpa menyimpan stok besar.'],
        ],
        'material_notes' => [
            'Nylon PA12' => 'Housing, arm, dan bracket drone yang kuat sekaligus ringan.',
            'Carbon Fiber Nylon' => 'Airframe dan struktur yang menuntut kekakuan tinggi.',
            'Titanium Ti6Al4V' => 'Komponen logam berkekuatan tinggi dengan bobot rendah.',
            'Aluminium AlSi10Mg' => 'Bracket, heatsink, dan komponen logam ringan.',
        ],
        'finishing' => [
            ['name' => 'Finish matte', 'description' => 'Permukaan doff yang rapi dan tidak memantulkan cahaya.'],
            ['name' => 'Cat warna RAL / Pantone', 'description' => 'Warna disesuaikan dengan kode warna produk atau unit Anda.'],
            ['name' => 'Clear coat', 'description' => 'Lapisan pelindung tambahan terhadap gores dan sinar UV.'],
        ],
        'faqs' => [
            ['question' => 'Apakah part cetak cukup kuat untuk airframe drone?', 'answer' => 'Untuk banyak kelas drone, Nylon PA12 dan Carbon Fiber Nylon sudah umum dipakai sebagai airframe. Kami akan meninjau desain dan beban kerjanya sebelum merekomendasikan material.'],
            ['question' => 'Bagaimana dengan kerahasiaan desain untuk proyek pertahanan?', 'answer' => 'File desain hanya dipakai untuk keperluan produksi pesanan Anda. Hubungi tim kami bila proyek Anda memerlukan perjanjian kerahasiaan (NDA).'],
            ['question' => 'Bisakah mencetak struktur lattice?', 'answer' => 'Bisa. Struktur lattice dan geometri kompleks justru menjadi keunggulan additive manufacturing dibanding proses konvensional.'],
            ['question' => 'Apakah tersedia part logam?', 'answer' => 'Ya, melalui teknologi SLM untuk material seperti titanium dan aluminium.'],
        ],
        'clients' => ['pindad'],
        'case_studies' => [],
        'stats' => [],
    ],

    'manufaktur-otomasi-robotika' => [
        'challenges' => [
            ['problem' => 'Downtime lini produksi karena menunggu spare part.', 'solution' => 'Spare part pengganti dicetak sesuai kebutuhan dari file atau hasil reverse engineering, sehingga mesin lebih cepat kembali beroperasi.'],
            ['problem' => 'Jig dan fixture dari machining mahal untuk dibuat dan diubah.', 'solution' => 'Jig dan fixture cetak lebih ringan, dapat dibentuk mengikuti kontur produk, dan mudah direvisi saat desain produk berubah.'],
            ['problem' => 'Gripper standar tidak pas untuk bentuk produk yang unik.', 'solution' => 'End-of-arm tooling dibuat custom sesuai bentuk produk Anda, termasuk saluran vakum internal bila diperlukan.'],
        ],
        'material_notes' => [
            'Nylon PA12' => 'Gripper, jig, dan fixture yang kuat dan tahan aus.',
            'PETG' => 'Cover, guard, dan alat bantu dengan biaya ekonomis.',
            'TPU' => 'Bantalan, ujung gripper, dan peredam yang fleksibel.',
            'Stainless Steel 316L' => 'Part logam yang tahan korosi dan beban berat.',
        ],
        'finishing' => [
            ['name' => 'Cat warna kode', 'description' => 'Jig dan fixture diberi warna sesuai kode stasiun kerja (RAL) agar tidak tertukar.'],
            ['name' => 'Amplas permukaan kontak', 'description' => 'Permukaan yang bersentuhan dengan produk dihaluskan agar tidak meninggalkan bekas.'],
            ['name' => 'Penandaan & label', 'description' => 'Kode part atau label posisi dicetak timbul langsung pada jig.'],
        ],
        'faqs' => [
            ['question' => 'Apakah part cetak tahan dipakai terus-menerus di lini produksi?', 'answer' => 'Untuk jig, fixture, dan gripper, Nylon PA12 tahan aus dan banyak dipakai di lingkungan produksi. Untuk beban sangat berat atau suhu tinggi, kami merekomendasikan material logam.'],
            ['question' => 'Mesin kami sudah tua dan part-nya sudah tidak dijual. Bisa dibantu?', 'answer' => 'Bisa. Kirimkan part aslinya atau fotonya untuk ditinjau; kami dapat melakukan 3D scanning dan reverse engineering lalu mencetak penggantinya.'],
            ['question' => 'Bisakah gripper dibuat dengan saluran vakum di dalamnya?', 'answer' => 'Bisa. Saluran internal termasuk geometri yang sulit dibuat dengan machining, namun dapat dicetak langsung dengan 3D printing.'],
            ['question' => 'Apakah bisa memesan dalam jumlah banyak?', 'answer' => 'Bisa, dengan harga per unit yang menyesuaikan jumlah pesanan. Kirimkan file untuk mendapatkan estimasi.'],
        ],
        'clients' => ['fanuc', 'esautomation', 'sgt', 'voith', 'pti', 'pindad'],
        'case_studies' => [],
        'stats' => [],
    ],

    'kimia-energi-migas' => [
        'challenges' => [
            ['problem' => 'Komponen bekerja di lingkungan korosif dan bersuhu tinggi.', 'solution' => 'Part logam seperti Stainless Steel 316L dan Inconel 718 dicetak dengan teknologi SLM untuk kondisi kerja yang berat.'],
            ['problem' => 'Komponen pengganti untuk peralatan lama memiliki lead time panjang.', 'solution' => 'Komponen dapat diproduksi on-demand dari file digital untuk kebutuhan pemeliharaan (MRO).'],
            ['problem' => 'Sulit menjelaskan desain plant yang kompleks kepada pemangku kepentingan.', 'solution' => 'Model skala plant dan perpipaan dicetak detail untuk presentasi, pelatihan, dan review desain.'],
        ],
        'material_notes' => [
            'Stainless Steel 316L' => 'Impeller, nozzle, dan fitting yang tahan korosi.',
            'Inconel 718' => 'Komponen untuk suhu dan tekanan tinggi.',
            'Nylon PA12' => 'Housing instrumen dan part polimer yang tahan bahan kimia.',
            'Resin High Temp' => 'Prototipe dan uji aliran yang menuntut ketahanan panas.',
        ],
        'finishing' => [
            ['name' => 'Pengamplasan bertingkat', 'description' => 'Permukaan dihaluskan bertahap untuk tampilan yang rapi.'],
            ['name' => 'Clear coat', 'description' => 'Lapisan pelindung untuk model dan housing yang sering dipegang.'],
            ['name' => 'Cat & label warna', 'description' => 'Model plant diberi warna sesuai kode jalur pipa agar mudah dibaca.'],
        ],
        'faqs' => [
            ['question' => 'Apakah part logam hasil SLM bisa dipakai untuk fungsi sebenarnya?', 'answer' => 'Part SLM merupakan logam padat yang dapat dipakai fungsional. Untuk aplikasi bertekanan atau kritis, kami akan mendiskusikan kebutuhan pengujian dan finishing-nya terlebih dahulu.'],
            ['question' => 'Material apa yang tahan bahan kimia?', 'answer' => 'Bergantung pada jenis bahan kimianya. Stainless Steel 316L dan Nylon PA12 tahan terhadap banyak bahan kimia umum; sebutkan media yang akan dilalui agar kami bisa merekomendasikan material yang tepat.'],
            ['question' => 'Bisakah dibuatkan model skala plant dari file desain kami?', 'answer' => 'Bisa. Kami dapat mencetak model dari file 3D desain plant Anda dan menyesuaikan skalanya dengan kebutuhan presentasi.'],
            ['question' => 'Apakah bisa mencetak dari part fisik tanpa gambar teknik?', 'answer' => 'Bisa, melalui layanan 3D scanning dan reverse engineering.'],
        ],
        'clients' => ['slb'],
        'case_studies' => [],
        'stats' => [],
    ],

    'elektrikal-elektronik-iot' => [
        'challenges' => [
            ['problem' => 'Enclosure dari cetakan injeksi mahal untuk produksi awal.', 'solution' => 'Enclosure dicetak tanpa cetakan, ekonomis untuk prototipe, pilot run, hingga produksi skala kecil.'],
            ['problem' => 'Posisi PCB, konektor, dan tombol harus pas.', 'solution' => 'Toleransi dicetak presisi dan desain dapat direvisi cepat sampai semua komponen terpasang sempurna.'],
            ['problem' => 'Produk perlu terlihat seperti produk jadi untuk investor atau uji pasar.', 'solution' => 'Finishing amplas, cat, dan tekstur membuat prototipe tampil layaknya produk siap jual.'],
        ],
        'material_notes' => [
            'Nylon PA12' => 'Enclosure fungsional yang kuat dengan snap-fit.',
            'Resin Tough' => 'Casing dengan permukaan halus dan detail tajam.',
            'PETG' => 'Holder, bracket, dan prototipe awal yang ekonomis.',
            'ABS' => 'Casing yang mudah diamplas dan dicat.',
        ],
        'finishing' => [
            ['name' => 'Amplas & cat', 'description' => 'Tampilan seperti produk jadi untuk demo dan uji pasar.'],
            ['name' => 'Finish soft-touch', 'description' => 'Permukaan lembut di tangan seperti perangkat komersial.'],
            ['name' => 'Tekstur & logo', 'description' => 'Logo dan tekstur permukaan dicetak langsung pada casing.'],
        ],
        'faqs' => [
            ['question' => 'Apakah casing bisa dibuat dengan snap-fit?', 'answer' => 'Bisa. Material seperti Nylon PA12 cukup lentur untuk snap-fit yang dipakai berulang.'],
            ['question' => 'Berapa toleransi yang bisa dicapai?', 'answer' => 'Toleransi bergantung pada teknologi dan ukuran part; spesifikasi tiap teknologi tersedia di halaman Technologies. Kami akan meninjau bagian yang membutuhkan presisi sebelum dicetak.'],
            ['question' => 'Apakah bisa produksi puluhan hingga ratusan unit?', 'answer' => 'Bisa. 3D printing cocok untuk pilot run dan produksi skala kecil sebelum Anda berinvestasi pada cetakan injeksi.'],
            ['question' => 'Saya belum punya file 3D enclosure. Bisa dibantu desain?', 'answer' => 'Bisa. Tim kami menyediakan layanan desain 3D berdasarkan ukuran PCB dan komponen Anda.'],
        ],
        'clients' => [],
        'case_studies' => [],
        'stats' => [],
    ],

    'arsitektur-interior' => [
        'challenges' => [
            ['problem' => 'Membuat maket manual membutuhkan waktu lama dan tenaga terampil.', 'solution' => 'Maket dicetak langsung dari model 3D desain Anda (mis. SketchUp, Revit, Rhino) dengan detail yang konsisten.'],
            ['problem' => 'Klien sulit membayangkan desain hanya dari gambar dan render.', 'solution' => 'Model fisik membantu klien memahami bentuk, skala, dan hubungan ruang secara langsung.'],
            ['problem' => 'Elemen dekoratif parametrik sulit diproduksi secara konvensional.', 'solution' => 'Panel parametrik, ornamen, dan kap lampu dengan bentuk kompleks dapat dicetak tanpa cetakan.'],
        ],
        'material_notes' => [
            'Resin Standard' => 'Detail fasad, jendela, dan ornamen yang halus.',
            'PLA' => 'Maket kawasan berukuran besar dengan biaya ekonomis.',
            'PETG' => 'Kap lampu dan elemen interior yang lebih tahan dipakai.',
        ],
        'finishing' => [
            ['name' => 'Cat dasar putih', 'description' => 'Tampilan maket arsitektur yang bersih dan seragam.'],
            ['name' => 'Cat warna & material', 'description' => 'Warna atap, dinding, dan lanskap untuk maket presentasi.'],
            ['name' => 'Perakitan bagian', 'description' => 'Maket besar dicetak per bagian lalu dirakit menjadi satu.'],
        ],
        'faqs' => [
            ['question' => 'File apa yang dibutuhkan untuk mencetak maket?', 'answer' => 'File 3D seperti STL atau OBJ. Model dari SketchUp, Revit, atau Rhino biasanya perlu disiapkan agar dinding memiliki ketebalan; tim kami dapat membantu memeriksanya.'],
            ['question' => 'Seberapa besar maket yang bisa dicetak?', 'answer' => 'Maket besar dapat dicetak per bagian lalu dirakit. Ukuran maksimal satu cetakan bergantung pada build volume mesin, yang tercantum di halaman Technologies.'],
            ['question' => 'Apakah maket bisa diberi warna?', 'answer' => 'Bisa, melalui finishing cat sesuai kebutuhan presentasi Anda.'],
            ['question' => 'Bisakah mencetak model topografi dari data kontur?', 'answer' => 'Bisa, selama data kontur sudah diolah menjadi model 3D. Hubungi tim kami untuk membahas format datanya.'],
        ],
        'clients' => [],
        'case_studies' => [],
        'stats' => [],
    ],

    'furnitur-desain-produk' => [
        'challenges' => [
            ['problem' => 'Desain harus diuji bentuk dan ergonominya sebelum produksi massal.', 'solution' => 'Prototipe looks-like dan works-like dicetak cepat agar desain bisa dipegang, dicoba, dan direvisi.'],
            ['problem' => 'Hardware dan konektor custom tidak tersedia di pasaran.', 'solution' => 'Konektor, joint, handle, dan kaki furnitur dibuat sesuai desain Anda dalam jumlah kecil maupun menengah.'],
            ['problem' => 'Pembuatan master model untuk cetakan silikon memakan waktu.', 'solution' => 'Master model dicetak presisi dengan permukaan halus, siap dipakai untuk membuat cetakan silikon.'],
        ],
        'material_notes' => [
            'PLA' => 'Prototipe bentuk awal yang ekonomis.',
            'PETG' => 'Konektor dan part fungsional yang lebih tahan benturan.',
            'Resin Standard' => 'Sampel pameran dan master model dengan permukaan halus.',
            'Nylon PA12' => 'Hardware fungsional yang kuat untuk pemakaian berulang.',
        ],
        'finishing' => [
            ['name' => 'Amplas & cat', 'description' => 'Sampel produk tampil seperti produk jadi untuk pameran dan foto katalog.'],
            ['name' => 'Primer & smoothing', 'description' => 'Permukaan master model dihaluskan sebelum dibuat cetakan silikon.'],
            ['name' => 'Tekstur permukaan', 'description' => 'Tekstur dicetak langsung untuk merasakan hasil akhir produk.'],
        ],
        'faqs' => [
            ['question' => 'Apa beda prototipe looks-like dan works-like?', 'answer' => 'Looks-like berfokus pada tampilan dan bentuk produk, sedangkan works-like berfokus pada fungsi mekanisnya. Keduanya bisa dicetak dengan material yang berbeda sesuai tujuannya.'],
            ['question' => 'Apakah konektor furnitur cetak cukup kuat?', 'answer' => 'Untuk banyak aplikasi, Nylon PA12 dan PETG cukup kuat. Kami akan meninjau beban yang akan diterima konektor sebelum mencetak.'],
            ['question' => 'Bisakah dibuatkan master model untuk produksi resin atau silikon?', 'answer' => 'Bisa. Master model dicetak dengan permukaan halus dan dapat difinishing lebih lanjut sebelum dibuat cetakannya.'],
            ['question' => 'Saya hanya punya sketsa desain. Bisa dibantu?', 'answer' => 'Bisa. Layanan desain 3D kami dapat mengubah sketsa dan ukuran menjadi model siap cetak.'],
        ],
        'clients' => [],
        'case_studies' => [],
        'stats' => [],
    ],

    'mainan-display' => [
        'challenges' => [
            ['problem' => 'Cetakan injeksi mainan mahal bila desainnya belum final.', 'solution' => 'Prototipe mainan dicetak untuk uji bentuk dan fungsi sebelum berinvestasi pada cetakan injeksi.'],
            ['problem' => 'Figure koleksi membutuhkan detail sangat halus.', 'solution' => 'Teknologi SLA menghasilkan detail tajam dan permukaan mulus yang siap dicat.'],
            ['problem' => 'Properti event dan display dibutuhkan cepat dan dalam jumlah terbatas.', 'solution' => 'Properti booth dan display retail dicetak sesuai desain brand tanpa minimum order besar.'],
        ],
        'material_notes' => [
            'Resin Standard' => 'Figure dan miniatur dengan detail halus.',
            'Resin Tough' => 'Prototipe mainan yang perlu tahan dipegang dan dijatuhkan.',
            'PLA' => 'Properti display dan booth berukuran besar.',
        ],
        'finishing' => [
            ['name' => 'Primer & cat detail', 'description' => 'Figure dicat dengan detail warna seperti produk koleksi.'],
            ['name' => 'Amplas halus', 'description' => 'Bekas support dihilangkan untuk permukaan yang mulus.'],
            ['name' => 'Perakitan & lem', 'description' => 'Figure besar dicetak per bagian lalu dirakit.'],
        ],
        'faqs' => [
            ['question' => 'Apakah figure bisa dicetak dalam warna?', 'answer' => 'Figure dicetak dalam satu warna material lalu dicat sesuai desain melalui layanan finishing kami.'],
            ['question' => 'Apakah aman untuk mainan anak-anak?', 'answer' => 'Prototipe cetak ditujukan untuk uji desain, bukan produk akhir untuk anak-anak. Produk mainan yang dijual umumnya harus memenuhi standar keamanan mainan yang berlaku.'],
            ['question' => 'Bisakah mencetak banyak figure yang sama?', 'answer' => 'Bisa, dengan harga per unit yang menyesuaikan jumlah. Untuk jumlah sangat besar kami juga dapat membuat master model untuk cetakan silikon.'],
            ['question' => 'Berapa ukuran display yang bisa dibuat?', 'answer' => 'Display besar dapat dicetak per bagian lalu dirakit menjadi satu.'],
        ],
        'clients' => [],
        'case_studies' => [],
        'stats' => [],
    ],

    'medis-kedokteran-gigi' => [
        'challenges' => [
            ['problem' => 'Perencanaan tindakan sulit hanya dari citra CT/MRI di layar.', 'solution' => 'Model anatomi fisik dari data CT/MRI membantu tim medis memahami kasus dan merencanakan tindakan.'],
            ['problem' => 'Menjelaskan kondisi medis kepada pasien tidak mudah.', 'solution' => 'Model fisik membuat penjelasan kepada pasien lebih jelas dan mudah dipahami.'],
            ['problem' => 'Alat bantu rehabilitasi standar tidak selalu pas di setiap pasien.', 'solution' => 'Prototipe ortotik dan alat bantu dapat dibuat custom mengikuti bentuk tubuh dari hasil pemindaian.'],
        ],
        'material_notes' => [
            'Resin Standard' => 'Model anatomi dan model gigi dengan detail tinggi.',
            'Resin Tough' => 'Model yang lebih tahan untuk sering dipegang saat edukasi.',
            'Nylon PA12' => 'Prototipe ortotik dan housing alat medis.',
        ],
        'finishing' => [
            ['name' => 'Amplas halus', 'description' => 'Permukaan model dihaluskan agar nyaman dipegang.'],
            ['name' => 'Pewarnaan bagian', 'description' => 'Bagian anatomi tertentu diberi warna berbeda untuk edukasi.'],
            ['name' => 'Penandaan', 'description' => 'Label atau kode kasus dicetak pada dasar model.'],
        ],
        'faqs' => [
            ['question' => 'Apakah model ini bisa dipakai di dalam tubuh atau mulut pasien?', 'answer' => 'Model dan prototipe kami ditujukan untuk perencanaan, edukasi, dan pengembangan produk. Penggunaan di dalam tubuh atau mulut pasien memerlukan material dan proses yang tersertifikasi untuk alat kesehatan; diskusikan kebutuhan ini dengan tim kami terlebih dahulu.'],
            ['question' => 'Data apa yang dibutuhkan untuk mencetak model anatomi?', 'answer' => 'Data CT/MRI perlu diolah (segmentasi) menjadi model 3D seperti STL. Bila Anda sudah memiliki file STL, kami dapat langsung meninjaunya.'],
            ['question' => 'Bagaimana dengan kerahasiaan data pasien?', 'answer' => 'Kirimkan hanya file model 3D tanpa identitas pasien. File hanya dipakai untuk keperluan produksi pesanan Anda.'],
            ['question' => 'Apakah bisa mencetak model gigi dari hasil intraoral scan?', 'answer' => 'Bisa, selama hasil pemindaian diekspor dalam format 3D seperti STL.'],
        ],
        'clients' => [],
        'case_studies' => [],
        'stats' => [],
    ],

    'pendidikan-riset' => [
        'challenges' => [
            ['problem' => 'Anggaran riset dan tugas akhir terbatas.', 'solution' => 'Material ekonomis seperti PLA dan PETG membuat prototipe tetap terjangkau tanpa mengorbankan fungsi.'],
            ['problem' => 'Tenggat kompetisi dan sidang sangat ketat.', 'solution' => 'Desain langsung dicetak dari file tanpa proses tooling, sehingga revisi bisa dikejar sebelum tenggat.'],
            ['problem' => 'Peralatan laboratorium yang spesifik tidak tersedia di pasaran.', 'solution' => 'Fixture, holder, dan perangkat eksperimen dibuat custom sesuai kebutuhan penelitian.'],
        ],
        'material_notes' => [
            'PLA' => 'Prototipe dan alat peraga dengan biaya paling ekonomis.',
            'PETG' => 'Part fungsional yang lebih tahan benturan.',
            'Resin Standard' => 'Model sains dan alat peraga dengan detail halus.',
            'Nylon PA12' => 'Part robot dan kendaraan kompetisi yang menerima beban.',
        ],
        'finishing' => [
            ['name' => 'Tanpa finishing', 'description' => 'Pilihan paling hemat untuk prototipe fungsional dan uji coba.'],
            ['name' => 'Amplas & cat', 'description' => 'Tampilan rapi untuk presentasi sidang dan pameran.'],
            ['name' => 'Clear coat', 'description' => 'Pelindung gores untuk alat peraga yang sering dipegang.'],
        ],
        'faqs' => [
            ['question' => 'Apakah ada harga khusus untuk mahasiswa?', 'answer' => 'Hubungi tim kami dan ceritakan proyek Anda; kami akan membantu memilih material dan teknologi yang paling sesuai dengan anggaran.'],
            ['question' => 'Saya belum terlalu paham menyiapkan file 3D. Bisa dibantu?', 'answer' => 'Bisa. Panduan menyiapkan file tersedia di halaman 3D Models, dan tim kami dapat membantu memeriksa atau memperbaiki file Anda.'],
            ['question' => 'Bisakah mencetak part untuk tim kompetisi robotik?', 'answer' => 'Bisa. Nylon PA12 dan PETG banyak dipakai untuk part robot karena kuat dan ringan.'],
            ['question' => 'Apakah bisa memesan satu buah saja?', 'answer' => 'Bisa. Tidak ada minimum order; satu part pun dapat dicetak.'],
        ],
        'clients' => [],
        'case_studies' => [],
        'stats' => [],
    ],

    'seni-fashion-perhiasan' => [
        'challenges' => [
            ['problem' => 'Bentuk karya terlalu kompleks untuk dipahat atau dibentuk manual.', 'solution' => 'Bentuk organik dan geometris yang rumit dicetak langsung dari model digital.'],
            ['problem' => 'Master model perhiasan membutuhkan detail sangat halus.', 'solution' => 'Resin castable dengan teknologi SLA menghasilkan detail halus yang siap untuk proses casting.'],
            ['problem' => 'Properti kostum dan merchandise dibutuhkan dalam jumlah terbatas.', 'solution' => 'Produksi tanpa cetakan membuat pesanan satuan hingga jumlah kecil tetap ekonomis.'],
        ],
        'material_notes' => [
            'Resin Standard' => 'Patung, aksesori, dan karya dengan detail halus.',
            'Resin Castable' => 'Master model perhiasan untuk proses casting logam.',
            'PLA' => 'Properti kostum dan karya berukuran besar.',
            'TPU' => 'Aksesori fleksibel seperti tali dan elemen wearable.',
        ],
        'finishing' => [
            ['name' => 'Primer & cat', 'description' => 'Karya dan properti kostum dicat sesuai konsep.'],
            ['name' => 'Metalik & electroplating', 'description' => 'Cat metalik atau electroplating untuk tampilan seperti perunggu, emas, atau perak.'],
            ['name' => 'Amplas halus', 'description' => 'Permukaan dihaluskan untuk karya yang dipajang dari dekat.'],
        ],
        'faqs' => [
            ['question' => 'Apakah master perhiasan bisa langsung dicor menjadi logam?', 'answer' => 'Master dari resin castable dirancang untuk proses investment casting di workshop perhiasan. Kami mencetak master-nya; proses pengecoran dilakukan oleh pengrajin atau workshop casting Anda.'],
            ['question' => 'Bisakah mencetak properti kostum berukuran besar seperti helm?', 'answer' => 'Bisa. Properti besar dapat dicetak per bagian lalu dirakit dan difinishing.'],
            ['question' => 'Apakah karya bisa dicetak dalam warna?', 'answer' => 'Karya dicetak dalam satu warna material lalu dicat sesuai desain melalui layanan finishing kami.'],
            ['question' => 'Bisakah membuat merchandise dengan logo brand?', 'answer' => 'Bisa. Logo dan teks dapat dicetak timbul langsung pada merchandise.'],
        ],
        'clients' => [],
        'case_studies' => [],
        'stats' => [],
    ],
];
