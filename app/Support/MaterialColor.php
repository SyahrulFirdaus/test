<?php

namespace App\Support;

use App\Services\PrintEstimator;

/**
 * Simulasi warna material.
 *
 * Pilihan ini hanya mengubah tampilan model di viewer dan dicatat sebagai
 * preferensi pelanggan pada permintaan penawaran — berkas model yang diunggah
 * sama sekali tidak diubah.
 */
class MaterialColor
{
    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return config('printing.material_colors.options', []);
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        $default = (string) config('printing.material_colors.default');

        return self::exists($default) ? $default : (self::keys()[0] ?? 'merah');
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    /** @return array<string, string> */
    public static function resolve(?string $key): array
    {
        return self::all()[$key] ?? self::all()[self::default()] ?? ['label' => 'Merah', 'hex' => '#B8452F'];
    }

    public static function label(?string $key): string
    {
        return self::resolve($key)['label'];
    }

    public static function hex(?string $key): string
    {
        return self::resolve($key)['hex'];
    }

    /**
     * Warna yang benar-benar tersedia untuk satu material.
     *
     * Material menyebutkan pilihannya lewat kunci `colors` di
     * config/printing.php — resin bening hanya tersedia bening, part logam
     * hanya warna aslinya. Material tanpa kunci itu menerima seluruh warna.
     *
     * @return array<string, array<string, string>>
     */
    public static function forMaterial(?string $technology, ?string $material): array
    {
        $allowed = app(PrintEstimator::class)->material((string) $technology, (string) $material)['colors'] ?? null;

        if (! is_array($allowed) || $allowed === []) {
            return self::all();
        }

        return array_intersect_key(self::all(), array_flip($allowed));
    }

    /** @return array<int, string> */
    public static function keysForMaterial(?string $technology, ?string $material): array
    {
        return array_keys(self::forMaterial($technology, $material));
    }

    /**
     * Warna yang dipakai bila pilihan pengguna tidak tersedia pada materialnya.
     *
     * Warna bawaan global dipakai bila memang termasuk pilihan material itu;
     * bila tidak, warna pertama yang tersedia yang dipakai.
     */
    public static function defaultForMaterial(?string $technology, ?string $material): string
    {
        $available = self::keysForMaterial($technology, $material);

        return in_array(self::default(), $available, true)
            ? self::default()
            : ($available[0] ?? self::default());
    }

    /** Sesuaikan pilihan warna dengan material — dipakai saat materialnya berganti. */
    public static function resolveForMaterial(?string $key, ?string $technology, ?string $material): string
    {
        return in_array($key, self::keysForMaterial($technology, $material), true)
            ? (string) $key
            : self::defaultForMaterial($technology, $material);
    }
}
