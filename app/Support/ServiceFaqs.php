<?php

namespace App\Support;

/**
 * FAQ halaman tujuan iklan dari config/service-faqs.php.
 *
 * `{lead_time}` diganti pilihan kecepatan yang benar-benar dipakai sistem
 * pemesanan (config printing.lead_time), sehingga FAQ tidak pernah
 * menjanjikan waktu yang berbeda dari halaman 3D Models.
 */
class ServiceFaqs
{
    /** @return array<int, array{question: string, answer: string}> */
    public static function for(string $page): array
    {
        $leadTime = self::leadTimeText();

        return collect(config("service-faqs.{$page}", []))
            ->map(fn (array $faq) => [
                'question' => $faq['question'],
                'answer' => str_replace('{lead_time}', $leadTime, $faq['answer']),
            ])
            ->all();
    }

    /** "Standard (5–7 hari kerja) atau Express (1–2 hari kerja, …)". */
    public static function leadTimeText(): string
    {
        $config = config('printing.lead_time');
        $range = fn (array $speed) => $speed['min_days'].'–'.$speed['max_days'].' hari kerja';

        $standard = $config['standard']['name'].' ('.$range($config['standard']).')';
        $express = $config['express']['name'].' ('.$range($config['express']).', dengan biaya tambahan, khusus pesanan '
            .($config['express']['max_parts'] === 1 ? 'satu model' : 'hingga '.$config['express']['max_parts'].' model')
            .' dengan waktu cetak di bawah '.intdiv((int) $config['express']['below_minutes'], 60).' jam)';

        return $standard.' atau '.$express;
    }
}
