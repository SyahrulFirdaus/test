<?php

namespace App\Support;

use App\Models\CompanyProfile;

/**
 * Data terstruktur schema.org `LocalBusiness` dari profil perusahaan.
 *
 * Semua nilai diambil dari data profil yang sudah ada (menu Profil
 * Perusahaan). Teks bebas seperti jam operasional dan kota diurai seperlunya;
 * bila formatnya tidak dikenali, bagian itu dilewati — tidak pernah ditebak.
 * Koordinat (geo) dan rentang harga tidak dicantumkan karena datanya belum ada.
 */
final class BusinessSchema
{
    private const DAYS = [
        'senin' => 'Monday',
        'selasa' => 'Tuesday',
        'rabu' => 'Wednesday',
        'kamis' => 'Thursday',
        'jumat' => 'Friday',
        "jum'at" => 'Friday',
        'sabtu' => 'Saturday',
        'minggu' => 'Sunday',
        'ahad' => 'Sunday',
    ];

    public static function for(CompanyProfile $company, string $description, ?string $image = null): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => url('/').'#business',
            'name' => $company->name,
            'legalName' => $company->legal_name,
            'description' => $description,
            'url' => url('/'),
            'image' => $image,
            'email' => $company->email,
            'telephone' => self::telephone($company->phone),
            'foundingDate' => $company->founded_year ? (string) $company->founded_year : null,
            'address' => self::address($company->address, $company->city),
            'openingHoursSpecification' => self::openingHours($company->operational_hours),
            'hasMap' => $company->maps_url,
            'sameAs' => array_values(array_filter($company->socials ?? [])),
        ];

        return self::clean($schema);
    }

    /** Nomor lokal (0812…) menjadi format internasional (+62 812…). */
    public static function telephone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return str_starts_with($digits, '62') ? '+'.$digits : $phone;
    }

    /**
     * Alamat pos. Kolom kota berbentuk "Kabupaten Bekasi 17533, Jawa Barat,
     * Indonesia" diurai menjadi wilayah, kode pos, dan provinsi; bila
     * formatnya lain, teksnya dipakai utuh sebagai wilayah.
     */
    public static function address(?string $street, ?string $city): ?array
    {
        if (blank($street) && blank($city)) {
            return null;
        }

        $address = [
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressCountry' => 'ID',
        ];

        $city = trim((string) $city);

        if (preg_match('/^(.+?)\s+(\d{5})\s*,\s*(.+?)(?:\s*,\s*Indonesia)?$/iu', $city, $match)) {
            $address['addressLocality'] = trim($match[1]);
            $address['postalCode'] = $match[2];
            $address['addressRegion'] = trim($match[3]);
        } elseif ($city !== '') {
            $address['addressLocality'] = $city;
        }

        return self::clean($address);
    }

    /**
     * Jam operasional berbentuk "Senin – Jumat, 08.00 – 17.00 WIB" (boleh
     * beberapa kelompok dipisah titik koma) menjadi OpeningHoursSpecification.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public static function openingHours(?string $text): ?array
    {
        if (blank($text)) {
            return null;
        }

        $specs = [];

        foreach (preg_split('/\s*[;\n]\s*/', $text) as $part) {
            $pattern = '/^\s*([a-z\']+)(?:\s*[–—-]\s*([a-z\']+))?\s*,?\s*(\d{1,2})[.:](\d{2})\s*[–—-]\s*(\d{1,2})[.:](\d{2})/iu';

            if (! preg_match($pattern, $part, $match)) {
                continue;
            }

            $days = self::dayRange(mb_strtolower($match[1]), mb_strtolower($match[2] ?: $match[1]));

            if ($days === null) {
                continue;
            }

            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $days,
                'opens' => sprintf('%02d:%02d', $match[3], $match[4]),
                'closes' => sprintf('%02d:%02d', $match[5], $match[6]),
            ];
        }

        return $specs ?: null;
    }

    /** @return array<int, string>|null */
    private static function dayRange(string $from, string $to): ?array
    {
        $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $start = array_search(self::DAYS[$from] ?? null, $order, true);
        $end = array_search(self::DAYS[$to] ?? null, $order, true);

        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        return array_slice($order, $start, $end - $start + 1);
    }

    private static function clean(array $data): array
    {
        return array_filter($data, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }
}
