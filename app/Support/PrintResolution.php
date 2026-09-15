<?php

namespace App\Support;

/**
 * Pembantu pilihan resolusi (layer height).
 *
 * Seluruh pilihan beserta pengali waktu dan materialnya berada di
 * `resolutions` pada config/printing.php, sehingga kartu pilihan di browser,
 * perhitungan ulang di server, dan tampilan admin memakai satu sumber yang sama.
 */
class PrintResolution
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('printing.resolutions.options', []);
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function default(): string
    {
        $default = (string) config('printing.resolutions.default');

        return self::exists($default) ? $default : (self::keys()[0] ?? '0.25');
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

    public static function resolve(?string $key): array
    {
        return self::find($key) ?? self::find(self::default()) ?? [
            'layer_height' => 0.25,
            'name' => 'Normal',
            'quality' => 'Normal',
            'speed' => 'Seimbang',
            'time_multiplier' => 1.0,
            'material_multiplier' => 1.0,
        ];
    }

    public static function layerHeight(?string $key): float
    {
        return (float) self::resolve($key)['layer_height'];
    }

    /** Label lengkap, mis. "0,25 mm (Normal)". */
    public static function label(?string $key): string
    {
        $resolution = self::resolve($key);

        return number_format((float) $resolution['layer_height'], 2, ',', '.').' mm ('.$resolution['name'].')';
    }

    public static function quality(?string $key): string
    {
        return (string) self::resolve($key)['quality'];
    }

    /**
     * Apakah resolusi ini berada di dalam rentang lapisan yang benar-benar
     * tersedia pada teknologi tertentu.
     *
     * Empat pilihan tetap ditawarkan untuk semua teknologi, tetapi pengguna
     * diberi tahu bila pilihannya di luar rentang mesin — MJF misalnya bekerja
     * pada tebal lapisan tetap 0,08 mm.
     */
    public static function isWithinRange(?string $key, ?string $technology): bool
    {
        $range = self::rangeFor($technology);

        if (! is_array($range)) {
            return true;
        }

        $height = self::layerHeight($key);

        return $height >= (float) $range['min'] && $height <= (float) $range['max'];
    }

    /**
     * Rentang tebal lapisan milik satu teknologi.
     *
     * Dibaca dari teknologi yang dikelola Superadmin; teknologi yang tidak
     * dikenal mengembalikan null sehingga seluruh resolusi dianggap sah.
     *
     * @return array{min: float, max: float}|null
     */
    private static function rangeFor(?string $technology): ?array
    {
        $row = \App\Models\PrintTechnology::findByCode($technology);

        return $row === null
            ? null
            : ['min' => $row->layer_height_min, 'max' => $row->layer_height_max];
    }

    /** Keterangan singkat bila resolusi di luar rentang teknologi terpilih. */
    public static function rangeNotice(?string $key, ?string $technology): ?string
    {
        if (self::isWithinRange($key, $technology)) {
            return null;
        }

        $range = self::rangeFor($technology);
        $code = strtoupper((string) $technology);

        $rangeText = $range['min'] === $range['max']
            ? 'tetap '.self::formatMm((float) $range['min']).' mm'
            : self::formatMm((float) $range['min']).' – '.self::formatMm((float) $range['max']).' mm';

        return "Tebal lapisan {$code} {$rangeText}. Pilihan ini di luar rentang tersebut, tim kami akan menyesuaikannya saat produksi.";
    }

    /** Dua desimal, kecuali angkanya memang butuh tiga (mis. 0,025 mm). */
    private static function formatMm(float $value): string
    {
        $decimals = fmod($value * 100, 1.0) === 0.0 ? 2 : 3;

        return number_format($value, $decimals, ',', '.');
    }

    /** Konfigurasi ringkas untuk kartu pilihan di browser. */
    public static function browserPayload(): array
    {
        $payload = [];

        foreach (self::all() as $key => $resolution) {
            $payload[$key] = [
                'layerHeight' => $resolution['layer_height'],
                'name' => $resolution['name'],
                'quality' => $resolution['quality'],
                'speed' => $resolution['speed'],
                'timeMultiplier' => $resolution['time_multiplier'],
                'materialMultiplier' => $resolution['material_multiplier'],
                'recommendation' => $resolution['recommendation'] ?? null,
                'highlights' => $resolution['highlights'] ?? [],
            ];
        }

        return $payload;
    }
}
