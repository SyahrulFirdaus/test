<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        CompanyProfile::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'NUSAMA3D',
                'legal_name' => 'PT. Nusantara Additic Manufaktur',
                'tagline' => '3D Printing Service & Engineering Solutions',
                'founded_year' => 2020,
                'short_description' => 'NUSAMA3D adalah penyedia layanan additive manufacturing yang membantu industri, startup, dan institusi pendidikan mewujudkan ide menjadi produk nyata, mulai dari desain, pemindaian, pencetakan, hingga finishing.',
                'about' => 'NUSAMA3D berdiri sejak tahun 2020 dari satu unit mesin FDM di sebuah workshop kecil, berawal dari keyakinan sederhana: proses membuat sesuatu seharusnya tidak menjadi penghalang bagi ide yang baik. Sejak saat itu kami tumbuh menjadi service bureau dengan lini teknologi lengkap (FDM, SLA, MJF, hingga SLM logam) yang melayani lebih dari 400 customer di berbagai sektor. Kami mendampingi tim R&D perusahaan manufaktur memangkas siklus pengembangan produk, membantu praktisi medis menyiapkan model bedah yang presisi, serta menghidupkan kembali sparepart yang sudah tidak lagi diproduksi. Yang membedakan kami bukan sekadar mesin, melainkan tim engineer yang meninjau setiap file sebelum masuk produksi, memberi masukan desain yang jujur, dan bertanggung jawab atas kualitas hingga part sampai di tangan Anda.',
                'vision' => 'Menjadi mitra additive manufacturing paling tepercaya di Indonesia, yang membuat teknologi manufaktur canggih dapat dijangkau oleh setiap pencipta, dari industri besar hingga inovator perorangan.',
                'missions' => [
                    'Menghadirkan layanan cetak 3D berkualitas industri dengan harga yang wajar dan transparan.',
                    'Mendampingi klien sejak tahap ide hingga produk siap diproduksi melalui konsultasi teknis yang jujur.',
                    'Berinvestasi berkelanjutan pada teknologi, material, dan kompetensi tim.',
                    'Menjaga konsistensi mutu melalui proses kontrol kualitas yang terukur pada setiap pesanan.',
                    'Menumbuhkan ekosistem manufaktur digital Indonesia lewat edukasi dan kolaborasi.',
                ],
                'advantages' => [
                    [
                        'icon' => 'layers',
                        'title' => 'Teknologi Lengkap dalam Satu Atap',
                        'description' => 'FDM, SLA, MJF, dan SLM tersedia di satu tempat, sehingga kami dapat memilih proses yang paling tepat untuk part Anda, bukan memaksakan proses yang kebetulan kami miliki.',
                    ],
                    [
                        'icon' => 'users',
                        'title' => 'Didampingi Tim Engineer',
                        'description' => 'Setiap file ditinjau engineer berpengalaman sebelum produksi. Bila ada risiko desain, Anda akan mengetahuinya lebih dulu, bukan setelah part gagal.',
                    ],
                    [
                        'icon' => 'clock',
                        'title' => 'Lead Time Cepat & Pasti',
                        'description' => 'Prototipe dapat selesai mulai satu hari kerja. Estimasi waktu kami sampaikan di awal dan kami jaga komitmennya.',
                    ],
                    [
                        'icon' => 'shield',
                        'title' => 'Kontrol Kualitas Terukur',
                        'description' => 'Pemeriksaan dimensi dan visual dilakukan pada setiap batch, lengkap dengan laporan inspeksi bila dibutuhkan.',
                    ],
                    [
                        'icon' => 'lock',
                        'title' => 'Kerahasiaan Desain Terjaga',
                        'description' => 'Seluruh file klien diperlakukan sebagai rahasia. Kami siap menandatangani NDA sebelum file Anda dikirim.',
                    ],
                    [
                        'icon' => 'spark',
                        'title' => 'Finishing Setara Produk Massal',
                        'description' => 'Layanan post-processing internal memastikan part tidak berhenti pada hasil cetak mentah, melainkan tampil siap presentasi.',
                    ],
                ],
                'stats' => [
                    ['value' => 6, 'suffix' => '+', 'label' => 'Tahun Pengalaman'],
                    ['value' => 400, 'suffix' => '+', 'label' => 'Customer'],
                    ['value' => 25000, 'suffix' => '+', 'label' => 'Part Diproduksi'],
                    ['value' => 4, 'suffix' => '', 'label' => 'Teknologi Cetak'],
                ],
                'address' => 'Jl. Cibadak 3 No. 42, Jatibaru, Cikarang Timur',
                'city' => 'Kabupaten Bekasi 17533, Jawa Barat, Indonesia',
                'phone' => '0812 9238 8805',
                'whatsapp' => '0812 9238 8805',
                'email' => 'cs@nusama3d.com',
                'operational_hours' => 'Senin – Jumat, 08.00 – 17.00 WIB',
                'maps_url' => 'https://maps.google.com/?q=Jl.+Cibadak+3+No.+42+Jatibaru+Cikarang+Timur+Kabupaten+Bekasi+17533',
                // Hanya kanal yang benar-benar dipakai NUSAMA3D. Footer dan
                // penanda `sameAs` pada structured data sama-sama membaca daftar
                // ini, jadi menambah kanal baru nanti cukup satu baris di sini.
                'socials' => [
                    'instagram' => 'https://www.instagram.com/nusama3d/',
                ],
            ]
        );
    }
}
