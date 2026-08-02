<?php

namespace App\Support;

/**
 * Pilihan mesin printer 3D.
 *
 * Printer menentukan ukuran build plate yang digambar di viewer sekaligus
 * batas yang dipakai untuk memeriksa apakah model masih muat. Karena setiap
 * mesin punya kecepatan dan tarif berbeda, pilihannya juga ikut mengubah
 * estimasi waktu dan biaya.
 *
 * Ukuran build volume memakai konvensi yang sama dengan build_volume
 * teknologi: `x` lebar meja, `y` kedalaman meja, `z` tinggi maksimum.
 */
class Printer
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('printing.printers.options', []);
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        $default = (string) config('printing.printers.default');

        return self::exists($default) ? $default : (self::keys()[0] ?? 'ender3');
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    /** @return array<string, mixed>|null */
    public static function find(?string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public static function resolve(?string $key): array
    {
        return self::find($key) ?? self::find(self::default()) ?? [
            'name' => 'Custom',
            'build_volume' => ['x' => 220, 'y' => 220, 'z' => 250],
            'speed_factor' => 1.0,
            'rate_factor' => 1.0,
        ];
    }

    public static function name(?string $key): string
    {
        return (string) self::resolve($key)['name'];
    }

    /** Apakah pilihan ini membiarkan pengguna mengisi ukurannya sendiri. */
    public static function isCustom(?string $key): bool
    {
        return (bool) (self::find($key)['custom'] ?? false);
    }

    /**
     * Ukuran area cetak yang benar-benar dipakai.
     *
     * Pada pilihan Custom ukurannya datang dari pengguna, jadi nilainya
     * dibatasi ke rentang yang wajar sebelum dipakai — build plate raksasa
     * hasil salah ketik tidak boleh membuat validasi ukuran jadi tak berarti.
     *
     * @param  array<string, mixed>|null  $custom
     * @return array{x: float, y: float, z: float}
     */
    public static function buildVolume(?string $key, ?array $custom = null): array
    {
        $printer = self::resolve($key);
        $volume = $printer['build_volume'];

        if (self::isCustom($key) && is_array($custom)) {
            $min = (float) config('printing.printers.custom_limits.min', 50);
            $max = (float) config('printing.printers.custom_limits.max', 1000);

            foreach (['x', 'y', 'z'] as $axis) {
                if (is_numeric($custom[$axis] ?? null)) {
                    $volume[$axis] = min($max, max($min, (float) $custom[$axis]));
                }
            }
        }

        return [
            'x' => (float) $volume['x'],
            'y' => (float) $volume['y'],
            'z' => (float) $volume['z'],
        ];
    }

    /** Label lengkap, mis. "Creality Ender 3 (220 × 220 × 250 mm)". */
    public static function label(?string $key, ?array $custom = null): string
    {
        $volume = self::buildVolume($key, $custom);
        $format = fn (float $value) => rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',');

        return self::name($key).' ('.$format($volume['x']).' × '.$format($volume['y']).' × '.$format($volume['z']).' mm)';
    }

    /** Konfigurasi ringkas untuk dropdown dan build plate di browser. */
    public static function browserPayload(): array
    {
        $payload = [];

        foreach (self::all() as $key => $printer) {
            $payload[$key] = [
                'name' => $printer['name'],
                'buildVolume' => $printer['build_volume'],
                'speedFactor' => $printer['speed_factor'],
                'rateFactor' => $printer['rate_factor'],
                'custom' => (bool) ($printer['custom'] ?? false),
                'note' => $printer['note'] ?? null,
            ];
        }

        return $payload;
    }
}
