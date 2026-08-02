<?php

namespace App\Support;

/**
 * Pilihan finishing part setelah selesai dicetak.
 *
 * Komponen biaya "Finishing" pada rincian penawaran sudah ada sejak awal dan
 * mewakili pembersihan dasar setiap part. Pilihan `none` memakai pengali 1,0
 * sehingga harga part yang tidak meminta finishing tambahan sama persis seperti
 * sebelum fitur ini ada; pilihan lain menaikkan biaya sekaligus menambah waktu
 * pengerjaan per unit.
 *
 * Seluruh angkanya diatur `finishing` di config/printing.php.
 */
class Finishing
{
    public const NONE = 'none';

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return config('printing.finishing.options', []);
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** @return array<string, string> daftar kunci => label, untuk dropdown */
    public static function options(): array
    {
        return array_map(fn (array $option) => $option['label'], self::all());
    }

    public static function default(): string
    {
        $default = (string) config('printing.finishing.default', self::NONE);

        return self::exists($default) ? $default : (self::keys()[0] ?? self::NONE);
    }

    public static function exists(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    /** @return array<string, mixed> */
    public static function resolve(?string $key): array
    {
        $options = self::all();

        return $options[$key] ?? $options[self::default()] ?? [
            'label' => 'Tanpa Finishing',
            'cost_multiplier' => 1.0,
            'hours_per_unit' => 0.0,
        ];
    }

    public static function label(?string $key): string
    {
        return self::resolve($key)['label'];
    }

    public static function description(?string $key): ?string
    {
        return self::resolve($key)['description'] ?? null;
    }

    /** Pengali biaya finishing terhadap pembersihan dasar. */
    public static function costMultiplier(?string $key): float
    {
        return max(0.0, (float) (self::resolve($key)['cost_multiplier'] ?? 1.0));
    }

    /** Tambahan waktu pengerjaan untuk setiap unit, dalam jam. */
    public static function hoursPerUnit(?string $key): float
    {
        return max(0.0, (float) (self::resolve($key)['hours_per_unit'] ?? 0.0));
    }

    /**
     * Bentuk ringkas untuk estimator di browser.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function browserPayload(): array
    {
        return collect(self::all())
            ->map(fn (array $option) => [
                'label' => $option['label'],
                'description' => $option['description'] ?? null,
                'costMultiplier' => (float) ($option['cost_multiplier'] ?? 1.0),
                'hoursPerUnit' => (float) ($option['hours_per_unit'] ?? 0.0),
            ])
            ->all();
    }
}
