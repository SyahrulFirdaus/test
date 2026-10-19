<?php

namespace App\Support;

/**
 * FAQPage JSON-LD dari daftar FAQ yang SAMA dengan yang ditampilkan
 * komponen <x-faq-section> — pertanyaan & jawabannya tidak pernah disusun
 * terpisah, jadi structured data selalu sesuai dengan isi halaman.
 */
class FaqSchema
{
    /**
     * FAQ yang sah saja: pertanyaan dan jawaban berupa teks tidak kosong,
     * dirapikan dari tag HTML dan spasi berlebih.
     *
     * @param  iterable<int, array{question?: mixed, answer?: mixed}>  $faqs
     * @return array<int, array{question: string, answer: string}>
     */
    public static function normalize(iterable $faqs): array
    {
        $clean = fn (mixed $text) => trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)));

        return collect($faqs)
            ->map(fn ($faq) => is_array($faq) ? ['question' => $clean($faq['question'] ?? ''), 'answer' => $clean($faq['answer'] ?? '')] : null)
            ->filter(fn ($faq) => $faq !== null && $faq['question'] !== '' && $faq['answer'] !== '')
            ->values()
            ->all();
    }

    /**
     * JSON siap dipasang di <script type="application/ld+json">. Karakter
     * < > & ' " di-escape (JSON_HEX_*) sehingga isi teks tidak dapat menutup
     * tag <script> atau menyisipkan markup.
     *
     * @param  array<int, array{question: string, answer: string}>  $faqs
     */
    public static function toJsonLd(array $faqs, ?string $url = null): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
        ];

        if ($url !== null) {
            $data['@id'] = $url.'#faq';
            $data['url'] = $url;
        }

        $data['inLanguage'] = 'id';
        $data['mainEntity'] = array_map(fn (array $faq) => [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
        ], $faqs);

        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
    }
}
