<?php

namespace Database\Seeders;

use App\Models\RegistrationQuestion;
use App\Support\CustomerType;
use Illuminate\Database\Seeder;

/**
 * Pertanyaan pendaftaran bawaan.
 *
 * Pelanggan perorangan menjawab lima pertanyaan, satu per halaman. Pelanggan
 * perusahaan menjawab lima belas pertanyaan pada satu langkah "Kebutuhan
 * Bisnis" yang terbagi menjadi enam bagian bertajuk.
 *
 * Seeder ini memakai `updateOrCreate` dengan `key` sebagai penanda, jadi aman
 * dijalankan berulang: pertanyaan yang sudah pernah dijawab pelanggan tidak
 * pernah dibuat ganda, dan jawabannya tidak ikut tersentuh.
 *
 * Dua pertanyaan sengaja tidak menyimpan pilihannya di sini — teknologi dan
 * material diambil dari data sistem lewat `options_source` supaya daftarnya
 * selalu mengikuti katalog yang benar-benar dilayani.
 */
class RegistrationQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [...$this->personalQuestions(), ...$this->businessQuestions()];

        foreach ($questions as $index => $question) {
            RegistrationQuestion::updateOrCreate(
                ['key' => $question['key']],
                [...$question, 'sort_order' => $question['sort_order'] ?? $index],
            );
        }

        // Pertanyaan Business susunan lama dinonaktifkan, bukan dihapus:
        // menghapusnya akan ikut membuang jawaban pelanggan yang sudah
        // terlanjur mendaftar. Yang dinonaktifkan tidak lagi muncul pada
        // formulir, tetapi jawaban lamanya tetap terbaca di dashboard admin.
        RegistrationQuestion::query()
            ->where('customer_type', CustomerType::BUSINESS)
            ->whereNotIn('key', array_column($questions, 'key'))
            ->update(['is_active' => false]);
    }

    /**
     * Lima pertanyaan pelanggan perorangan, masing-masing satu langkah sendiri.
     *
     * Satu pertanyaan per halaman membuat pengisiannya terasa ringan: pengguna
     * cukup menjawab satu hal lalu menekan Lanjut, tanpa dihadapkan pada
     * gulungan panjang berisi lima pertanyaan sekaligus.
     *
     * @return array<int, array<string, mixed>>
     */
    private function personalQuestions(): array
    {
        return [
            [
                'customer_type' => CustomerType::PERSONAL,
                'key' => 'personal_purpose',
                'question' => 'Apa tujuan Anda menggunakan layanan 3D Printing?',
                'type' => 'radio',
                'options' => ['Hobi', 'Prototype', 'Tugas / Pendidikan', 'Custom Product', 'Lainnya'],
                'step' => 1,
                'step_label' => 'Tujuan Penggunaan',
                'sort_order' => 1,
            ],
            [
                'customer_type' => CustomerType::PERSONAL,
                'key' => 'personal_frequency',
                'question' => 'Seberapa sering Anda menggunakan layanan 3D Printing?',
                'type' => 'radio',
                'options' => ['Pertama kali', 'Jarang', 'Beberapa kali dalam setahun', 'Rutin'],
                'step' => 2,
                'step_label' => 'Frekuensi Penggunaan',
                'sort_order' => 1,
            ],
            [
                'customer_type' => CustomerType::PERSONAL,
                'key' => 'personal_project_type',
                'question' => 'Jenis project apa yang biasanya Anda buat?',
                'type' => 'radio',
                'options' => ['Prototype', 'Spare Part', 'Miniature', 'Produk Custom', 'Lainnya'],
                'step' => 3,
                'step_label' => 'Jenis Project',
                'sort_order' => 1,
            ],
            [
                'customer_type' => CustomerType::PERSONAL,
                'key' => 'personal_quantity',
                'question' => 'Berapa jumlah kebutuhan Anda biasanya?',
                'type' => 'radio',
                'options' => ['1–5 pcs', '6–20 pcs', '21–100 pcs', 'Lebih dari 100 pcs'],
                'step' => 4,
                'step_label' => 'Jumlah Kebutuhan',
                'sort_order' => 1,
            ],
            [
                'customer_type' => CustomerType::PERSONAL,
                'key' => 'personal_priority',
                'question' => 'Apa yang paling penting bagi Anda?',
                'type' => 'radio',
                'options' => ['Harga', 'Kualitas', 'Kecepatan', 'Material', 'Ketepatan ukuran'],
                'step' => 5,
                'step_label' => 'Prioritas Anda',
                'sort_order' => 1,
            ],
        ];
    }

    /**
     * Lima belas pertanyaan pelanggan perusahaan.
     *
     * Seluruhnya berada di satu langkah — "Kebutuhan Bisnis" — tetapi terbagi
     * menjadi enam bagian bertajuk supaya halamannya terbaca seperti proses
     * pengenalan pelanggan, bukan formulir panjang.
     *
     * Hanya lima yang wajib: kebutuhan utama, jenis produksi, frekuensi,
     * tingkat kepentingan timeline, dan tenggat. Sisanya opsional karena
     * pelanggan yang baru mengenal 3D printing belum tentu dapat menjawabnya,
     * dan memaksanya justru membuat mereka mengarang jawaban. Industri
     * ditanyakan tersendiri pada langkah Data Perusahaan.
     *
     * Pertanyaan teknis selalu menyediakan pilihan "belum tahu". Jawaban itu
     * tetap disimpan dan justru berguna: menandakan pelanggan yang perlu
     * dibantu memilih teknologi maupun materialnya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function businessQuestions(): array
    {
        $step = [
            'customer_type' => CustomerType::BUSINESS,
            'step' => 1,
            'step_label' => 'Kebutuhan Bisnis',
        ];

        $unsure = 'Belum tahu / minta rekomendasi';

        return [
            // --- A. Kebutuhan 3D Printing --------------------------------------
            [
                ...$step,
                'key' => 'b_main_need',
                'question' => 'Apa kebutuhan utama Anda menggunakan layanan 3D Printing?',
                'type' => 'radio',
                'options' => ['Prototype', 'Functional Parts', 'Product Development', 'Small Batch Production', 'Mass Production', 'Replacement Parts', 'Lainnya'],
                'category' => 'Kebutuhan 3D Printing',
                'category_order' => 1,
                'sort_order' => 1,
                'is_required' => true,
            ],
            [
                ...$step,
                'key' => 'b_product_type',
                'question' => 'Jenis produk atau object apa yang biasanya Anda produksi?',
                'help' => 'Boleh memilih lebih dari satu.',
                'type' => 'checkbox',
                'options' => ['Prototype produk', 'Komponen mesin', 'Jig & fixture', 'Spare part', 'Housing / casing', 'Cetakan (mold)', 'Alat peraga / maket', 'Alat kesehatan', 'Produk konsumen', 'Lainnya'],
                'category' => 'Kebutuhan 3D Printing',
                'category_order' => 1,
                'sort_order' => 2,
                'is_required' => false,
            ],

            // --- B. Kebutuhan Produksi -----------------------------------------
            [
                ...$step,
                'key' => 'b_frequency',
                'question' => 'Seberapa sering perusahaan Anda membutuhkan 3D Printing?',
                'type' => 'radio',
                'options' => ['Sesekali', '1–2 kali per bulan', '3–5 kali per bulan', '5–10 kali per bulan', 'Lebih dari 10 kali per bulan'],
                'category' => 'Kebutuhan Produksi',
                'category_order' => 2,
                'sort_order' => 1,
                'is_required' => true,
            ],
            [
                ...$step,
                'key' => 'b_quantity',
                'question' => 'Berapa estimasi jumlah produk yang biasanya diproduksi?',
                'type' => 'radio',
                'options' => ['1–10 pcs', '11–50 pcs', '51–100 pcs', '101–500 pcs', 'Lebih dari 500 pcs'],
                'category' => 'Kebutuhan Produksi',
                'category_order' => 2,
                'sort_order' => 2,
                'is_required' => false,
            ],
            [
                ...$step,
                'key' => 'b_production_type',
                'question' => 'Apakah kebutuhan Anda lebih banyak untuk prototype atau produksi?',
                'type' => 'radio',
                'options' => ['Prototype', 'Small Batch', 'Production', 'Keduanya'],
                'category' => 'Kebutuhan Produksi',
                'category_order' => 2,
                'sort_order' => 3,
                'is_required' => true,
            ],

            // --- C. Teknologi & Material ---------------------------------------
            [
                ...$step,
                'key' => 'b_technologies',
                'question' => 'Teknologi 3D Printing apa yang biasa digunakan?',
                'help' => 'Boleh memilih lebih dari satu. Belum tahu pun tidak apa-apa — tim kami akan merekomendasikan.',
                'type' => 'checkbox',
                // Diambil dari tabel `technologies`, jadi selalu mengikuti
                // teknologi yang benar-benar dilayani.
                'options_source' => 'technologies',
                'category' => 'Teknologi & Material',
                'category_order' => 3,
                'sort_order' => 1,
                'is_required' => false,
            ],
            [
                ...$step,
                'key' => 'b_materials',
                'question' => 'Material apa yang biasanya digunakan?',
                'help' => 'Boleh memilih lebih dari satu.',
                'type' => 'checkbox',
                'options_source' => 'materials',
                'category' => 'Teknologi & Material',
                'category_order' => 3,
                'sort_order' => 2,
                'is_required' => false,
            ],

            // --- D. Kebutuhan Finishing ----------------------------------------
            [
                ...$step,
                'key' => 'b_finishing',
                'question' => 'Apakah membutuhkan finishing?',
                'type' => 'radio',
                'options' => ['Tidak membutuhkan finishing', 'Sanding', 'Painting', 'Surface Finishing', 'Lainnya', $unsure],
                'category' => 'Kebutuhan Finishing',
                'category_order' => 4,
                'sort_order' => 1,
                'is_required' => false,
            ],

            // --- E. Timeline & Budget ------------------------------------------
            [
                ...$step,
                'key' => 'b_timeline_priority',
                'question' => 'Seberapa penting timeline produksi bagi perusahaan Anda?',
                'type' => 'radio',
                'options' => ['Tidak terlalu mendesak', 'Normal', 'Cukup mendesak', 'Sangat mendesak'],
                'category' => 'Timeline & Budget',
                'category_order' => 5,
                'sort_order' => 1,
                'is_required' => true,
            ],
            [
                ...$step,
                'key' => 'b_deadline',
                'question' => 'Kapan biasanya project harus selesai?',
                'type' => 'radio',
                'options' => ['Tidak ada deadline khusus', 'Kurang dari 1 minggu', '1–2 minggu', '2–4 minggu', 'Lebih dari 1 bulan'],
                'category' => 'Timeline & Budget',
                'category_order' => 5,
                'sort_order' => 2,
                'is_required' => true,
            ],
            [
                ...$step,
                'key' => 'b_budget',
                'question' => 'Berapa estimasi budget untuk kebutuhan 3D Printing?',
                'type' => 'radio',
                'options' => [
                    'Kurang dari Rp500.000',
                    'Rp500.000 – Rp2.000.000',
                    'Rp2.000.000 – Rp5.000.000',
                    'Rp5.000.000 – Rp10.000.000',
                    'Lebih dari Rp10.000.000',
                    'Belum menentukan',
                ],
                'category' => 'Timeline & Budget',
                'category_order' => 5,
                'sort_order' => 3,
                'is_required' => false,
            ],

            // --- F. Kebutuhan Bisnis & Procurement -----------------------------
            [
                ...$step,
                'key' => 'b_need_quotation',
                'question' => 'Apakah perusahaan membutuhkan quotation resmi?',
                'type' => 'radio',
                'options' => ['Ya', 'Tidak'],
                'category' => 'Kebutuhan Bisnis & Procurement',
                'category_order' => 6,
                'sort_order' => 1,
                'is_required' => false,
            ],
            [
                ...$step,
                'key' => 'b_need_invoice',
                'question' => 'Apakah perusahaan membutuhkan invoice perusahaan?',
                'type' => 'radio',
                'options' => ['Ya', 'Tidak'],
                'category' => 'Kebutuhan Bisnis & Procurement',
                'category_order' => 6,
                'sort_order' => 2,
                'is_required' => false,
            ],
            [
                ...$step,
                'key' => 'b_procurement',
                'question' => 'Bagaimana biasanya proses procurement perusahaan Anda?',
                'type' => 'radio',
                'options' => ['Langsung melakukan pemesanan', 'Membutuhkan quotation terlebih dahulu', 'Membutuhkan PO', 'Membutuhkan approval internal', 'Lainnya'],
                'category' => 'Kebutuhan Bisnis & Procurement',
                'category_order' => 6,
                'sort_order' => 3,
                'is_required' => false,
            ],
            [
                ...$step,
                'key' => 'b_challenge',
                'question' => 'Apa kebutuhan atau tantangan utama yang ingin diselesaikan melalui NUSAMA3D?',
                'help' => 'Ceritakan sebebasnya — jawaban ini membantu tim kami menyiapkan solusi yang tepat.',
                'type' => 'textarea',
                'placeholder' => 'Misalnya: butuh prototype cepat untuk uji pasar, atau spare part mesin yang sudah tidak diproduksi lagi.',
                'category' => 'Kebutuhan Bisnis & Procurement',
                'category_order' => 6,
                'sort_order' => 4,
                'is_required' => false,
            ],
        ];
    }
}
