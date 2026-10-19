<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Data sumber kedatangan pengunjung (first touch) untuk Lead Management.
 *
 * Ditangkap pada kunjungan pertama oleh App\Http\Middleware\CaptureLeadTracking
 * dan disimpan di cookie selama 90 hari — sama dengan masa berlaku GCLID —
 * lalu disalin ke tabel `leads` saat pengunjung mengirim form atau quotation.
 *
 * First touch tidak ditimpa kunjungan berikutnya. Satu-satunya pengecualian:
 * kunjungan pertama yang datang tanpa parameter iklan/UTM sama sekali (mis.
 * langsung mengetik alamat) digantikan oleh kunjungan pertama yang membawa
 * parameter tersebut, supaya klik iklan tidak hilang hanya karena pengunjung
 * pernah mampir lebih dulu.
 */
class LeadTracking
{
    public const COOKIE = 'nusama3d_first_touch';

    /** 90 hari, dalam menit. */
    public const LIFETIME = 60 * 24 * 90;

    /** Parameter URL yang dicatat. */
    public const PARAMETERS = [
        'gclid',
        'gbraid',
        'wbraid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

    /**
     * Data first touch dari cookie, atau array kosong.
     *
     * @return array<string, string|null>
     */
    public static function fromRequest(Request $request): array
    {
        $raw = $request->cookie(self::COOKIE);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? self::clean($data) : [];
    }

    /**
     * Data first touch untuk kunjungan ini, atau null bila tidak perlu disimpan.
     *
     * @return array<string, string|null>|null
     */
    public static function capture(Request $request): ?array
    {
        $existing = self::fromRequest($request);

        $parameters = [];

        foreach (self::PARAMETERS as $key) {
            $value = $request->query($key);

            if (is_string($value) && trim($value) !== '') {
                $parameters[$key] = $value;
            }
        }

        if ($existing !== [] && (self::hasAttribution($existing) || $parameters === [])) {
            return null;
        }

        $referrer = (string) $request->headers->get('referer', '');

        // Perpindahan antarhalaman situs sendiri bukan referrer.
        if ($referrer !== '' && parse_url($referrer, PHP_URL_HOST) === $request->getHost()) {
            $referrer = '';
        }

        return self::clean([
            ...$parameters,
            'landing_page' => $request->fullUrl(),
            'referrer' => $referrer,
            'first_touch_at' => now()->toIso8601String(),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public static function hasAttribution(array $data): bool
    {
        foreach (self::PARAMETERS as $key) {
            if (filled($data[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /** @param  array<string, mixed>  $data */
    public static function fromGoogleAds(array $data): bool
    {
        return filled($data['gclid'] ?? null) || filled($data['gbraid'] ?? null) || filled($data['wbraid'] ?? null);
    }

    /**
     * Hanya kunci yang dikenal, berupa teks, dan dipotong sesuai kolomnya.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public static function clean(array $data): array
    {
        $limits = [
            ...array_fill_keys(self::PARAMETERS, 255),
            'landing_page' => 500,
            'referrer' => 500,
            'first_touch_at' => 40,
        ];

        $clean = [];

        foreach ($limits as $key => $limit) {
            $value = $data[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $clean[$key] = Str::limit(trim(strip_tags($value)), $limit, '');
            }
        }

        return $clean;
    }
}
