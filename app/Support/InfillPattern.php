<?php

namespace App\Support;

/**
 * Kepadatan dan pola infill.
 *
 * Infill hanya mengisi rongga di dalam part; dindingnya (`shell_ratio` pada
 * tiap teknologi) selalu padat. Pengali pola diberlakukan sebanding dengan
 * kepadatannya, sehingga pada infill 0% pola apa pun tidak berpengaruh dan
 * pada 100% pengaruhnya penuh.
 */
class InfillPattern
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('printing.infill.patterns', []);
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        $default = (string) config('printing.infill.default_pattern');

        return self::exists($default) ? $default : (self::keys()[0] ?? 'grid');
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    /** @return array<string, mixed> */
    public static function resolve(?string $key): array
    {
        return self::all()[$key]
            ?? self::all()[self::default()]
            ?? ['label' => 'Grid', 'description' => '', 'material_multiplier' => 1.0, 'time_multiplier' => 1.0];
    }

    public static function label(?string $key): string
    {
        return (string) self::resolve($key)['label'];
    }

    /** @return array<int, float> */
    public static function densities(): array
    {
        return array_map('floatval', config('printing.infill.densities', [0.2]));
    }

    /**
     * Bulatkan kepadatan ke pilihan terdekat yang tersedia.
     *
     * Nilai di luar daftar tetap diterima agar data lama atau permintaan yang
     * dikirim langsung ke API tidak ditolak, tetapi selalu dijepit ke 0–1.
     */
    public static function density(mixed $value, float $fallback): float
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return min(1.0, max(0.0, (float) $value));
    }

    /**
     * Bagian volume part yang benar-benar terisi material.
     *
     * shell_ratio + (1 - shell_ratio) x kepadatan x pengali_pola
     */
    public static function fillFactor(float $shellRatio, float $density, ?string $pattern): float
    {
        $shell = min(1.0, max(0.0, $shellRatio));
        $multiplier = (float) self::resolve($pattern)['material_multiplier'];

        return min(1.0, $shell + (1.0 - $shell) * $density * $multiplier);
    }

    /** Pengali waktu pola, diberlakukan sebanding kepadatan infill. */
    public static function timeMultiplier(float $density, ?string $pattern): float
    {
        $multiplier = (float) self::resolve($pattern)['time_multiplier'];

        return 1.0 + ($multiplier - 1.0) * min(1.0, max(0.0, $density));
    }

    /** Konfigurasi ringkas untuk kartu pilihan di browser. */
    public static function browserPayload(): array
    {
        $payload = [];

        foreach (self::all() as $key => $pattern) {
            $payload[$key] = [
                'label' => $pattern['label'],
                'description' => $pattern['description'],
                'materialMultiplier' => $pattern['material_multiplier'],
                'timeMultiplier' => $pattern['time_multiplier'],
            ];
        }

        return $payload;
    }
}
