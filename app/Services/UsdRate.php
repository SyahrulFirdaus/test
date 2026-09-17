<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Kurs USD/IDR yang dipakai Form Perhitungan SLA Industries.
 *
 * "Dollar Hari Ini" tidak lagi diketik Admin. Kelas inilah satu-satunya tempat
 * kursnya berasal — halaman admin, endpoint penyegaran di browser, dan
 * penyimpanan perhitungan seluruhnya memanggil `current()` yang sama.
 *
 * Itu bukan kerapian belaka melainkan syarat kebenaran: kalau browser dan
 * server masing-masing menarik kurs sendiri, Final Price yang dilihat Admin
 * sebelum menekan Simpan bisa berbeda dari yang benar-benar tersimpan. Karena
 * keduanya membaca SATU simpanan yang sama, selisih itu tidak mungkin terjadi.
 *
 * Tiga lapis, dari yang termurah:
 *
 *   1. cache     jawaban penyedia selama `ttl_seconds`;
 *   2. penyedia  HTTP ke penyedia yang dikonfigurasi;
 *   3. cadangan  kurs terakhir yang diketahui dari tabel `exchange_rates`,
 *                dipakai HANYA bila penyedianya gagal dihubungi.
 *
 * Bila ketiganya tidak menghasilkan apa pun, yang dikembalikan `rate => null` —
 * BUKAN nol. Nol akan terhitung sebagai harga gratis; null memaksa pemanggilnya
 * menolak menghitung, dan itulah yang memang harus terjadi.
 */
class UsdRate
{
    public const PAIR = 'USD/IDR';

    private const CACHE_KEY = 'usd_rate.current';

    /**
     * Kurs yang berlaku sekarang.
     *
     * @param  bool  $force  abaikan cache — dipakai tombol "Coba Lagi"
     * @return array{
     *     rate: float|null, source: string|null, cadence: string,
     *     published_at: string|null, fetched_at: string|null,
     *     stale: bool, error: string|null, refresh_seconds: int
     * }
     */
    public function current(bool $force = false): array
    {
        $provider = $this->provider();

        if ($force) {
            Cache::forget(self::CACHE_KEY);
        }

        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $fresh = $this->fetch($provider);

            Cache::put(self::CACHE_KEY, $fresh, (int) ($provider['ttl_seconds'] ?? 3600));
            $this->remember($fresh);

            return $fresh;
        } catch (Throwable $exception) {
            // Kegagalan mengambil kurs bukan kesalahan yang perlu dilemparkan ke
            // pengguna sebagai galat 500: sistem masih punya jalan keluar.
            // Tetapi tetap dicatat, karena kalau berlarut-larut seluruh harga
            // SLA Industries akan memakai kurs basi.
            Log::warning('Gagal mengambil kurs USD/IDR.', [
                'provider' => config('printing.usd_rate.provider'),
                'message' => $exception->getMessage(),
            ]);

            return $this->fallback($exception->getMessage());
        }
    }

    /** Kurs yang benar-benar dipakai menghitung, atau null bila tidak ada. */
    public function rate(): ?float
    {
        return $this->current()['rate'];
    }

    /* ------------------------------------------------------- pengambilan --- */

    /**
     * @param  array<string, mixed>  $provider
     * @return array<string, mixed>
     */
    private function fetch(array $provider): array
    {
        $url = (string) ($provider['url'] ?? '');

        if ($url === '') {
            throw new \RuntimeException('Alamat penyedia kurs belum diatur.');
        }

        $response = Http::timeout(8)
            ->retry(2, 250, throw: false)
            ->acceptJson()
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Penyedia kurs menjawab HTTP '.$response->status().'.');
        }

        $body = (array) $response->json();
        $rate = $this->guard(Arr::get($body, (string) $provider['rate_path']));

        return [
            'rate' => $rate,
            'source' => (string) $provider['label'],
            'cadence' => (string) ($provider['cadence'] ?? 'daily'),
            'published_at' => $this->timestamp(Arr::get($body, (string) ($provider['updated_at_path'] ?? '')))?->toIso8601String(),
            'fetched_at' => CarbonImmutable::now()->toIso8601String(),
            'stale' => false,
            'error' => null,
            'refresh_seconds' => $this->refreshSeconds($provider),
        ];
    }

    /**
     * Kurs yang masuk akal, atau lemparkan.
     *
     * Penyedia yang berubah bentuk jawabannya, kunci API kedaluwarsa, atau
     * halaman galat yang kebetulan berformat JSON semuanya berakhir sebagai
     * nilai yang tidak berguna. Lebih baik ditolak di sini — lalu jatuh ke kurs
     * terakhir yang diketahui — daripada diteruskan menjadi harga.
     */
    private function guard(mixed $value): float
    {
        if (! is_numeric($value)) {
            throw new \RuntimeException('Jawaban penyedia kurs tidak memuat angka USD/IDR.');
        }

        $rate = (float) $value;
        $min = (float) config('printing.usd_rate.min', 1000);
        $max = (float) config('printing.usd_rate.max', 1000000);

        if ($rate < $min || $rate > $max) {
            throw new \RuntimeException('Kurs dari penyedia di luar batas wajar: '.$rate.'.');
        }

        return $rate;
    }

    /** Stempel waktu penyedia: detik Unix, atau tanggal "2026-09-15". */
    private function timestamp(mixed $value): ?CarbonImmutable
    {
        if (blank($value)) {
            return null;
        }

        try {
            return is_numeric($value)
                ? CarbonImmutable::createFromTimestamp((int) $value)
                : CarbonImmutable::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    /* ---------------------------------------------------------- cadangan --- */

    /** @param  array<string, mixed>  $rate */
    private function remember(array $rate): void
    {
        ExchangeRate::updateOrCreate(
            ['pair' => self::PAIR],
            [
                'rate' => $rate['rate'],
                'provider' => (string) config('printing.usd_rate.provider'),
                'source_label' => $rate['source'],
                'published_at' => $rate['published_at'],
                'fetched_at' => $rate['fetched_at'],
            ],
        );
    }

    /**
     * Kurs terakhir yang diketahui, ditandai basi.
     *
     * Ditandai `stale` supaya tampilannya dapat berterus terang bahwa angka ini
     * bukan kurs terbaru — pengguna berhak tahu harga yang sedang disusun
     * memakai kurs kapan.
     *
     * @return array<string, mixed>
     */
    private function fallback(string $error): array
    {
        $last = ExchangeRate::where('pair', self::PAIR)->first();

        return [
            'rate' => $last ? (float) $last->rate : null,
            'source' => $last?->source_label,
            'cadence' => (string) ($this->provider()['cadence'] ?? 'daily'),
            'published_at' => $last?->published_at?->toIso8601String(),
            'fetched_at' => $last?->fetched_at?->toIso8601String(),
            'stale' => true,
            'error' => $error,
            'refresh_seconds' => $this->refreshSeconds($this->provider()),
        ];
    }

    /* ------------------------------------------------------ konfigurasi --- */

    /** @return array<string, mixed> */
    private function provider(): array
    {
        $key = (string) config('printing.usd_rate.provider');
        $provider = config('printing.usd_rate.providers.'.$key);

        if (! is_array($provider)) {
            throw new \RuntimeException('Penyedia kurs "'.$key.'" tidak ada di config/printing.php.');
        }

        return $provider;
    }

    /**
     * Jeda penyegaran di browser.
     *
     * Sumber HARIAN sengaja disegarkan jarang: angkanya memang tidak berubah
     * sepanjang hari, jadi menariknya tiap lima menit hanya menghabiskan kuota
     * penyedia tanpa pernah menghasilkan nilai baru. Penyegaran rapat baru
     * berarti pada kurs pasar intraday.
     *
     * @param  array<string, mixed>  $provider
     */
    private function refreshSeconds(array $provider): int
    {
        return ($provider['cadence'] ?? 'daily') === 'intraday'
            ? max(60, (int) ($provider['ttl_seconds'] ?? 300))
            : 900;
    }
}
