<?php

namespace App\Support;

/**
 * CAPTCHA buatan sendiri untuk pendaftaran akun.
 *
 * Tidak memakai layanan pihak ketiga: kodenya dibuat dan diperiksa di server
 * ini, jadi tidak ada data pengunjung yang dikirim ke luar dan tidak perlu
 * kunci API. Gambarnya SVG — tanpa ekstensi GD — dan setiap karakter
 * digambar sebagai GARIS (path), bukan teks, sehingga kodenya tidak dapat
 * dibaca begitu saja dari markup. Posisi, kemiringan, dan getaran tiap
 * karakter diacak, ditambah garis dan titik pengganggu.
 *
 * Aturannya:
 *   - satu kode per sesi per tujuan (mis. "register"), berlaku 10 menit;
 *   - sekali pakai: setelah diperiksa — benar ATAU salah — kodenya dibuang,
 *     jadi percobaan berikutnya harus memakai kode baru;
 *   - huruf besar/kecil dan spasi tidak dipedulikan.
 */
class Captcha
{
    /** Tanpa karakter yang mudah tertukar (0/O, 1/I/L). */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const LENGTH = 5;

    private const TTL_SECONDS = 600;

    private const WIDTH = 190;

    private const HEIGHT = 64;

    /**
     * Huruf garis sederhana pada grid 4 × 6 (x ke kanan, y ke bawah).
     * Tiap karakter berupa satu atau beberapa polyline.
     *
     * @var array<string, array<int, array<int, array{0: float, 1: float}>>>
     */
    private const GLYPHS = [
        'A' => [[[0, 6], [2, 0], [4, 6]], [[1, 3.5], [3, 3.5]]],
        'B' => [[[0, 0], [0, 6], [3, 6], [4, 5], [4, 4], [3, 3], [0, 3]], [[0, 0], [3, 0], [4, 1], [4, 2], [3, 3]]],
        'C' => [[[4, 1], [3, 0], [1, 0], [0, 1], [0, 5], [1, 6], [3, 6], [4, 5]]],
        'D' => [[[0, 0], [0, 6], [2.5, 6], [4, 4.5], [4, 1.5], [2.5, 0], [0, 0]]],
        'E' => [[[4, 0], [0, 0], [0, 6], [4, 6]], [[0, 3], [3, 3]]],
        'F' => [[[4, 0], [0, 0], [0, 6]], [[0, 3], [3, 3]]],
        'G' => [[[4, 1], [3, 0], [1, 0], [0, 1], [0, 5], [1, 6], [3, 6], [4, 5], [4, 3], [2, 3]]],
        'H' => [[[0, 0], [0, 6]], [[4, 0], [4, 6]], [[0, 3], [4, 3]]],
        'J' => [[[4, 0], [4, 5], [3, 6], [1, 6], [0, 5]]],
        'K' => [[[0, 0], [0, 6]], [[4, 0], [0, 3.5]], [[1.3, 2.7], [4, 6]]],
        'M' => [[[0, 6], [0, 0], [2, 3], [4, 0], [4, 6]]],
        'N' => [[[0, 6], [0, 0], [4, 6], [4, 0]]],
        'P' => [[[0, 6], [0, 0], [3, 0], [4, 1], [4, 2], [3, 3], [0, 3]]],
        'Q' => [[[1, 0], [3, 0], [4, 1], [4, 5], [3, 6], [1, 6], [0, 5], [0, 1], [1, 0]], [[2.5, 4.5], [4, 6]]],
        'R' => [[[0, 6], [0, 0], [3, 0], [4, 1], [4, 2], [3, 3], [0, 3]], [[2, 3], [4, 6]]],
        'S' => [[[4, 1], [3, 0], [1, 0], [0, 1], [0, 2], [1, 3], [3, 3], [4, 4], [4, 5], [3, 6], [1, 6], [0, 5]]],
        'T' => [[[0, 0], [4, 0]], [[2, 0], [2, 6]]],
        'U' => [[[0, 0], [0, 5], [1, 6], [3, 6], [4, 5], [4, 0]]],
        'V' => [[[0, 0], [2, 6], [4, 0]]],
        'W' => [[[0, 0], [1, 6], [2, 3], [3, 6], [4, 0]]],
        'X' => [[[0, 0], [4, 6]], [[4, 0], [0, 6]]],
        'Y' => [[[0, 0], [2, 3], [4, 0]], [[2, 3], [2, 6]]],
        'Z' => [[[0, 0], [4, 0], [0, 6], [4, 6]]],
        '2' => [[[0, 1], [1, 0], [3, 0], [4, 1], [4, 2], [0, 6], [4, 6]]],
        '3' => [[[0, 1], [1, 0], [3, 0], [4, 1], [4, 2], [3, 3], [1.5, 3]], [[3, 3], [4, 4], [4, 5], [3, 6], [1, 6], [0, 5]]],
        '4' => [[[3, 6], [3, 0], [0, 4], [4, 4]]],
        '5' => [[[4, 0], [0, 0], [0, 3], [3, 3], [4, 4], [4, 5], [3, 6], [1, 6], [0, 5]]],
        '6' => [[[4, 1], [3, 0], [1, 0], [0, 1], [0, 5], [1, 6], [3, 6], [4, 5], [4, 4], [3, 3], [0, 3]]],
        '7' => [[[0, 0], [4, 0], [1.5, 6]]],
        '8' => [[[1, 3], [0, 2], [0, 1], [1, 0], [3, 0], [4, 1], [4, 2], [3, 3], [1, 3], [0, 4], [0, 5], [1, 6], [3, 6], [4, 5], [4, 4], [3, 3]]],
        '9' => [[[4, 3], [1, 3], [0, 2], [0, 1], [1, 0], [3, 0], [4, 1], [4, 5], [3, 6], [1, 6], [0, 5]]],
    ];

    /** Warna goresan: nuansa merah maroon dan abu gelap situs. */
    private const INK = ['#7a1f17', '#95271d', '#5b1a14', '#3b3438', '#a8352a'];

    /** Buat kode baru untuk satu tujuan, lalu kembalikan gambarnya (SVG). */
    public function image(string $purpose): string
    {
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        session()->put($this->key($purpose), [
            'code' => $code,
            'expires_at' => now()->getTimestamp() + self::TTL_SECONDS,
        ]);

        return $this->render($code);
    }

    /**
     * Periksa jawaban pengguna. Kode selalu dibuang setelah diperiksa, jadi
     * satu kode hanya dapat dicoba sekali.
     */
    public function verify(string $purpose, ?string $answer): bool
    {
        $stored = session()->pull($this->key($purpose));

        if (! is_array($stored) || ! isset($stored['code'], $stored['expires_at'])) {
            return false;
        }

        if (now()->getTimestamp() > (int) $stored['expires_at']) {
            return false;
        }

        $given = strtoupper(preg_replace('/\s+/', '', (string) $answer));

        return $given !== '' && hash_equals((string) $stored['code'], $given);
    }

    private function key(string $purpose): string
    {
        return 'captcha.'.$purpose;
    }

    /* ============================================================ gambar === */

    private function render(string $code): string
    {
        $parts = [];

        // Latar dengan bintik halus.
        $parts[] = '<rect width="100%" height="100%" rx="10" fill="#fbf6f5"/>';

        for ($i = 0; $i < 40; $i++) {
            $parts[] = sprintf(
                '<circle cx="%.1f" cy="%.1f" r="%.1f" fill="%s" opacity="%.2f"/>',
                $this->rand(0, self::WIDTH), $this->rand(0, self::HEIGHT), $this->rand(0.6, 1.8),
                self::INK[array_rand(self::INK)], $this->rand(0.15, 0.45),
            );
        }

        // Garis pengganggu di belakang karakter.
        for ($i = 0; $i < 3; $i++) {
            $parts[] = $this->noiseCurve(0.35);
        }

        $slot = (self::WIDTH - 20) / self::LENGTH;
        $unit = 5.4;

        foreach (str_split($code) as $index => $char) {
            $originX = 10 + $slot * $index + ($slot - 4 * $unit) / 2 + $this->rand(-3, 3);
            $originY = (self::HEIGHT - 6 * $unit) / 2 + $this->rand(-5, 5);
            $angle = $this->rand(-16, 16);
            $cx = $originX + 2 * $unit;
            $cy = $originY + 3 * $unit;

            $paths = [];

            foreach (self::GLYPHS[$char] as $stroke) {
                $d = '';

                foreach ($stroke as $n => [$x, $y]) {
                    $d .= sprintf(
                        '%s%.1f %.1f ',
                        $n === 0 ? 'M' : 'L',
                        $originX + ($x + $this->rand(-0.25, 0.25)) * $unit,
                        $originY + ($y + $this->rand(-0.2, 0.2)) * $unit,
                    );
                }

                $paths[] = '<path d="'.trim($d).'"/>';
            }

            $parts[] = sprintf(
                '<g transform="rotate(%.1f %.1f %.1f)" stroke="%s" stroke-width="%.1f" fill="none" stroke-linecap="round" stroke-linejoin="round">%s</g>',
                $angle, $cx, $cy, self::INK[array_rand(self::INK)], $this->rand(2.6, 3.4), implode('', $paths),
            );
        }

        // Garis pengganggu di depan karakter, tipis supaya kodenya tetap terbaca.
        for ($i = 0; $i < 2; $i++) {
            $parts[] = $this->noiseCurve(0.55, 1.2);
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">%s</svg>',
            self::WIDTH, self::HEIGHT, self::WIDTH, self::HEIGHT, implode('', $parts),
        );
    }

    private function noiseCurve(float $opacity, float $width = 1.6): string
    {
        return sprintf(
            '<path d="M%.1f %.1f C%.1f %.1f %.1f %.1f %.1f %.1f" stroke="%s" stroke-width="%.1f" fill="none" opacity="%.2f"/>',
            $this->rand(-10, 20), $this->rand(5, self::HEIGHT - 5),
            $this->rand(40, 80), $this->rand(-20, self::HEIGHT + 20),
            $this->rand(110, 150), $this->rand(-20, self::HEIGHT + 20),
            $this->rand(self::WIDTH - 20, self::WIDTH + 10), $this->rand(5, self::HEIGHT - 5),
            self::INK[array_rand(self::INK)], $width, $opacity,
        );
    }

    private function rand(float $min, float $max): float
    {
        return $min + (random_int(0, 10000) / 10000) * ($max - $min);
    }
}
