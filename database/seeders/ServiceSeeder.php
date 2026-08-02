<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->services() as $index => $service) {
            Service::updateOrCreate(
                ['slug' => $service['slug']],
                $service + ['sort_order' => $index + 1, 'is_active' => true]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function services(): array
    {
        return [
            [
                'slug' => '3d-printing-services',
                'title' => '3D Printing Services',
                'tagline' => 'Dari file digital menjadi part fisik',
                'excerpt' => 'Layanan cetak 3D on-demand untuk prototipe maupun produksi satuan hingga batch kecil, didukung teknologi FDM, SLA, MJF, dan SLM.',
                'description' => 'Kami mencetak part Anda langsung dari file 3D (STL, STEP, OBJ) dengan pemilihan teknologi dan material yang disesuaikan pada fungsi part. Tim engineering kami melakukan review file sebelum produksi untuk memastikan wall thickness, toleransi, dan orientasi cetak menghasilkan kualitas terbaik. Setiap order melewati pemeriksaan dimensi sebelum dikirim, sehingga part yang Anda terima siap dipakai untuk uji fungsi, presentasi, maupun pemakaian akhir.',
                'icon' => 'printer',
                'image' => 'images/services/3d-printing.svg',
                'highlights' => [
                    'Review file gratis sebelum produksi',
                    'Material teknik & food-safe tersedia',
                    'Lead time mulai 1 hari kerja',
                ],
            ],
            [
                'slug' => 'product-development-service',
                'title' => 'Product Development Service',
                'tagline' => 'Pendampingan dari ide sampai siap produksi',
                'excerpt' => 'Pendampingan pengembangan produk mulai dari konsep, desain, prototipe fungsional, hingga kesiapan produksi massal.',
                'description' => 'Kami bekerja bersama tim Anda menerjemahkan kebutuhan pasar menjadi produk yang benar-benar dapat diproduksi. Prosesnya mencakup studi kelayakan, sketsa konsep, pemodelan CAD, pembuatan prototipe fungsional, uji coba, hingga penyusunan dokumen teknis dan Design for Manufacturing (DFM). Pendekatan iteratif ini memangkas risiko kegagalan desain sebelum Anda berinvestasi pada tooling atau cetakan injeksi yang mahal.',
                'icon' => 'lightbulb',
                'image' => 'images/services/product-development.svg',
                'highlights' => [
                    'Konsep, CAD, prototipe, hingga DFM',
                    'Iterasi cepat tiap siklus desain',
                    'Dokumentasi teknis lengkap',
                ],
            ],
            [
                'slug' => 'reverse-engineering',
                'title' => 'Reverse Engineering',
                'tagline' => 'Menghidupkan kembali part yang sudah tidak diproduksi',
                'excerpt' => 'Mengubah part fisik menjadi model CAD parametrik yang akurat, siap dimodifikasi maupun diproduksi ulang.',
                'description' => 'Punya sparepart langka yang gambar teknisnya sudah hilang? Kami memindai part tersebut, merekonstruksi geometrinya menjadi model CAD parametrik, lalu memvalidasi hasilnya terhadap benda aslinya melalui analisis deviasi. Hasil akhirnya berupa file CAD yang dapat diedit beserta gambar kerja 2D, sehingga part dapat diproduksi ulang, diperbaiki desainnya, atau ditingkatkan performanya.',
                'icon' => 'reverse',
                'image' => 'images/services/reverse-engineering.svg',
                'highlights' => [
                    'Model CAD parametrik, bukan sekadar mesh',
                    'Laporan analisis deviasi',
                    'Gambar kerja 2D siap produksi',
                ],
            ],
            [
                'slug' => '3d-design',
                'title' => '3D Design',
                'tagline' => 'Desain yang indah sekaligus manufacturable',
                'excerpt' => 'Jasa pemodelan 3D dan desain produk yang mempertimbangkan estetika, ergonomi, sekaligus kemudahan produksi.',
                'description' => 'Layanan desain kami mencakup pemodelan CAD presisi, desain organik dan artistik, perakitan multi-part, hingga rendering fotorealistis untuk kebutuhan presentasi dan pemasaran. Setiap model dibangun dengan riwayat fitur yang rapi sehingga mudah direvisi di kemudian hari, dan selalu diperiksa terhadap batasan proses manufaktur yang akan dipakai.',
                'icon' => 'cube',
                'image' => 'images/services/3d-design.svg',
                'highlights' => [
                    'CAD parametrik & modeling organik',
                    'Rendering fotorealistis',
                    'File siap cetak dan siap CNC',
                ],
            ],
            [
                'slug' => '3d-scanning',
                'title' => '3D Scanning',
                'tagline' => 'Data digital presisi dari objek nyata',
                'excerpt' => 'Pemindaian 3D beresolusi tinggi untuk digitalisasi objek, inspeksi dimensi, dan pembuatan data acuan desain.',
                'description' => 'Menggunakan scanner cahaya terstruktur, kami menangkap geometri objek dengan akurasi hingga puluhan mikron. Layanan ini dipakai untuk digitalisasi benda cagar budaya, pembuatan custom fit product yang mengikuti kontur tubuh atau mesin, serta inspeksi kualitas dengan membandingkan benda hasil produksi terhadap model CAD aslinya. Data dikirim dalam format mesh (STL/OBJ) maupun point cloud sesuai kebutuhan.',
                'icon' => 'scan',
                'image' => 'images/services/3d-scanning.svg',
                'highlights' => [
                    'Akurasi hingga 0,05 mm',
                    'Layanan on-site tersedia',
                    'Output STL, OBJ, atau point cloud',
                ],
            ],
            [
                'slug' => 'paint-finishing',
                'title' => 'Paint & Finishing',
                'tagline' => 'Sentuhan akhir setara produk massal',
                'excerpt' => 'Post-processing menyeluruh: sanding, priming, pengecatan, hingga pelapisan khusus agar part tampil seperti produk jadi.',
                'description' => 'Part hasil cetak 3D kami olah lebih lanjut melalui pengamplasan bertingkat, pendempulan, priming, hingga pengecatan dengan spray gun di ruang khusus. Tersedia berbagai pilihan hasil akhir mulai dari matte, satin, glossy, metalik, hingga soft-touch, dengan pencocokan warna berdasarkan kode RAL atau Pantone. Tersedia pula opsi electroplating dan clear coat untuk part yang membutuhkan ketahanan ekstra terhadap gores dan sinar UV.',
                'icon' => 'brush',
                'image' => 'images/services/paint-finishing.svg',
                'highlights' => [
                    'Color matching RAL & Pantone',
                    'Finish matte, glossy, metalik, soft-touch',
                    'Opsi clear coat tahan UV',
                ],
            ],
        ];
    }
}
