<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    /**
     * Ulasan asli dari Google Review, dikutip apa adanya termasuk ejaan
     * dan gaya bahasa penulisnya — tidak dirapikan maupun ditambahi.
     *
     * `reviewed_label` menyimpan keterangan waktu persis seperti yang tampil
     * pada sumbernya; tanggal pastinya tidak diketahui sehingga tidak dikarang.
     */
    public function run(): void
    {
        foreach ($this->testimonials() as $index => $testimonial) {
            Testimonial::updateOrCreate(
                ['name' => $testimonial['name'], 'quote' => $testimonial['quote']],
                $testimonial + [
                    'rating' => 5,
                    'source' => 'Google Review',
                    'reviewed_label' => '2 tahun lalu',
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function testimonials(): array
    {
        return [
            [
                'name' => 'Rachmansyah',
                'quote' => 'Hasil cetakan rapi dan halus, saya order bahan nylon ternyata bisa halus permukaannya, padahal setau saya untuk bahan nylon itu yang paling sulit untuk dicetak rapi dan halus. Maptap.',
            ],
            [
                'name' => 'Mochammad Dwiyan Robiansyah',
                'quote' => 'hasil presisi dan detail , harga juga murah , respon cepat',
            ],
            [
                'name' => 'Radja Valdy',
                'quote' => 'Respon cepat, pelayanan oke banget, hasil juga memuaskan dan pengerjaan tepat waktu 👍',
            ],
            [
                'name' => 'Jims',
                'quote' => 'Service print resin yang bagus, hasil print jg mantab banget.',
            ],
            [
                'name' => 'An Nurfitriyana',
                'quote' => "Pengerjaan cepat, ramah, hasil presisi\nMau order lagi.",
            ],
            [
                'name' => 'Daniel Hamonangan',
                'quote' => 'Very good service overall and great print quality.',
            ],
            [
                'name' => 'zidan tools',
                'quote' => 'Good service and print quality',
            ],
            [
                'name' => 'Julvanal Sinaga',
                'quote' => 'Layanan memuaskan.',
            ],
            [
                'name' => 'Furqaan Novanto',
                'quote' => 'Sip presisi',
            ],
            [
                'name' => 'Muhammad Farras Adzikra',
                'quote' => 'Good!',
            ],
        ];
    }
}
