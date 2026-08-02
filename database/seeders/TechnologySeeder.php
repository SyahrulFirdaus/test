<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->technologies() as $index => $technology) {
            Technology::updateOrCreate(
                ['slug' => $technology['slug']],
                $technology + ['sort_order' => $index + 1, 'is_active' => true]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function technologies(): array
    {
        return [
            [
                'slug' => 'fdm',
                'code' => 'FDM',
                'name' => 'Fused Deposition Modeling',
                'tagline' => 'Ekonomis, kuat, dan fleksibel untuk part fungsional',
                'description' => 'FDM bekerja dengan melelehkan filamen termoplastik lalu mengekstrusikannya lapis demi lapis melalui nozzle panas mengikuti jalur yang telah dihitung slicer. Setiap lapisan menyatu dengan lapisan di bawahnya saat mendingin sehingga membentuk part yang solid. Karena menggunakan material termoplastik teknik yang sama dengan produk injection molding, FDM menjadi pilihan paling ekonomis untuk prototipe fungsional, jig, fixture, maupun part berukuran besar.',
                'image' => 'images/technologies/fdm.svg',
                'advantages' => [
                    'Biaya produksi paling ekonomis per volume part',
                    'Pilihan material teknik luas: PLA, ABS, PETG, ASA, Nylon, hingga PC',
                    'Mendukung part berukuran besar hingga 500 mm',
                    'Part tahan beban mekanis dan suhu tinggi (tergantung material)',
                ],
                'applications' => [
                    'Prototipe fungsional dan uji rakit',
                    'Jig, fixture, dan alat bantu produksi',
                    'Housing dan enclosure perangkat elektronik',
                    'Sparepart pengganti dan alat peraga edukasi',
                ],
                'materials' => ['PLA', 'PLA+', 'ABS', 'ASA', 'PETG', 'TPU', 'Nylon (PA)', 'PC', 'Carbon Fiber Composite'],
                'specs' => [
                    'Build Volume' => 'hingga 500 × 500 × 600 mm',
                    'Ketebalan Layer' => '0,10 – 0,30 mm',
                    'Toleransi Dimensi' => '± 0,3 mm atau ± 0,3%',
                    'Waktu Produksi' => '1 – 3 hari kerja',
                ],
                'accent_color' => '#95271D',
            ],
            [
                'slug' => 'sla',
                'code' => 'SLA',
                'name' => 'Stereolithography',
                'tagline' => 'Detail paling halus dengan permukaan mulus',
                'description' => 'SLA menggunakan sumber cahaya UV untuk mengeraskan resin fotopolimer cair di dalam tangki, lapis demi lapis, dengan resolusi jauh lebih tinggi dibanding proses ekstrusi. Hasilnya adalah part dengan detail sangat tajam, permukaan halus tanpa garis layer yang mencolok, serta akurasi dimensi tinggi. Teknologi ini menjadi standar untuk model presentasi, master cetakan, dan komponen kecil yang menuntut ketelitian.',
                'image' => 'images/technologies/sla.svg',
                'advantages' => [
                    'Detail sangat halus hingga fitur berukuran 0,2 mm',
                    'Permukaan mulus, minim proses amplas lanjutan',
                    'Akurasi dimensi tinggi untuk part presisi',
                    'Tersedia resin khusus: castable, dental, tahan suhu, dan transparan',
                ],
                'applications' => [
                    'Master model untuk silicone molding dan investment casting',
                    'Perhiasan, miniatur, dan figur koleksi',
                    'Model dental dan alat bantu medis',
                    'Prototipe visual berkualitas presentasi',
                ],
                'materials' => ['Standard Resin', 'Tough Resin', 'Clear Resin', 'Castable Resin', 'Dental Resin', 'High-Temp Resin'],
                'specs' => [
                    'Build Volume' => 'hingga 300 × 200 × 300 mm',
                    'Ketebalan Layer' => '0,025 – 0,10 mm',
                    'Toleransi Dimensi' => '± 0,1 mm',
                    'Waktu Produksi' => '1 – 3 hari kerja',
                ],
                'accent_color' => '#B8452F',
            ],
            [
                'slug' => 'mjf',
                'code' => 'MJF',
                'name' => 'Multi Jet Fusion',
                'tagline' => 'Produksi batch dengan kekuatan merata ke segala arah',
                'description' => 'MJF menyemprotkan fusing dan detailing agent ke atas hamparan serbuk nylon, lalu memanaskannya dengan lampu inframerah sehingga serbuk melebur menjadi part padat. Seluruh area dicetak sekaligus, bukan titik demi titik, sehingga prosesnya jauh lebih cepat. Part tersangga oleh serbuk di sekelilingnya sehingga tidak memerlukan struktur penyangga — geometri kompleks, engsel hidup, dan rakitan dalam sekali cetak menjadi mungkin.',
                'image' => 'images/technologies/mjf.svg',
                'advantages' => [
                    'Tanpa support, bebas merancang geometri kompleks',
                    'Sifat mekanis isotropik — kuat merata ke semua arah',
                    'Efisien untuk produksi batch puluhan hingga ratusan part',
                    'Permukaan seragam dan dapat langsung diwarnai (dyeing)',
                ],
                'applications' => [
                    'Produksi end-use part dalam jumlah menengah',
                    'Komponen otomotif dan industri yang menuntut kekuatan',
                    'Part dengan saluran internal atau struktur lattice',
                    'Rakitan bergerak yang dicetak sekaligus',
                ],
                'materials' => ['PA12', 'PA11', 'PA12 Glass Beads', 'TPU'],
                'specs' => [
                    'Build Volume' => 'hingga 380 × 284 × 380 mm',
                    'Ketebalan Layer' => '0,08 mm',
                    'Toleransi Dimensi' => '± 0,2 mm atau ± 0,2%',
                    'Waktu Produksi' => '3 – 5 hari kerja',
                ],
                'accent_color' => '#6E1B14',
            ],
            [
                'slug' => 'slm',
                'code' => 'SLM',
                'name' => 'Selective Laser Melting',
                'tagline' => 'Part logam padat langsung dari file digital',
                'description' => 'SLM melelehkan serbuk logam sepenuhnya menggunakan laser fiber berdaya tinggi di dalam ruang bebas oksigen, menghasilkan part logam dengan kepadatan mendekati 100% dan sifat mekanis setara logam tempa. Teknologi ini membuka kemungkinan yang tidak dapat dicapai proses permesinan konvensional, seperti saluran pendingin konformal, struktur lattice ringan, dan penggabungan beberapa komponen menjadi satu part utuh.',
                'image' => 'images/technologies/slm.svg',
                'advantages' => [
                    'Kepadatan part mendekati 100%, setara logam tempa',
                    'Memungkinkan saluran pendingin konformal dan struktur lattice',
                    'Ringan namun kuat melalui optimasi topologi',
                    'Mampu menyatukan banyak komponen menjadi satu part',
                ],
                'applications' => [
                    'Komponen aerospace dan otomotif berperforma tinggi',
                    'Insert cetakan dengan conformal cooling channel',
                    'Implan medis dan instrumen bedah',
                    'Sparepart logam langka dan tooling khusus',
                ],
                'materials' => ['Stainless Steel 316L', 'Maraging Steel', 'Titanium Ti6Al4V', 'AlSi10Mg', 'Inconel 718', 'CoCr'],
                'specs' => [
                    'Build Volume' => 'hingga 250 × 250 × 300 mm',
                    'Ketebalan Layer' => '0,02 – 0,06 mm',
                    'Toleransi Dimensi' => '± 0,1 mm atau ± 0,2%',
                    'Waktu Produksi' => '5 – 10 hari kerja',
                ],
                'accent_color' => '#4A120C',
            ],
        ];
    }
}
