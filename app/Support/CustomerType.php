<?php

namespace App\Support;

/**
 * Tipe pelanggan NUSAMA3D.
 *
 * Istilah yang dilihat pelanggan sengaja hanya "Personal" dan "Business";
 * penyebutan B2C maupun B2B tidak pernah muncul di antarmuka. Nilai yang
 * tersimpan di basis data adalah `personal` dan `business`.
 */
class CustomerType
{
    /** Kebutuhan pribadi: hobi, prototype, tugas, custom object. */
    public const PERSONAL = 'personal';

    /** Kebutuhan perusahaan: engineering, produksi, procurement. */
    public const BUSINESS = 'business';

    /** @return array<int, string> */
    public static function keys(): array
    {
        return [self::PERSONAL, self::BUSINESS];
    }

    public static function exists(?string $type): bool
    {
        return $type !== null && in_array($type, self::keys(), true);
    }

    public static function default(): string
    {
        return self::PERSONAL;
    }

    /** @return array<string, array<string, string>> */
    public static function all(): array
    {
        return [
            self::PERSONAL => [
                'label' => 'Personal',
                'tagline' => 'Untuk kebutuhan pribadi',
                'description' => 'Untuk kebutuhan pribadi, hobi, prototype, tugas, custom object, dan kebutuhan non-perusahaan.',
                'icon' => 'user',
            ],
            self::BUSINESS => [
                'label' => 'Business',
                'tagline' => 'Untuk kebutuhan perusahaan',
                'description' => 'Untuk kebutuhan perusahaan, engineering, produksi, prototype industri, procurement, dan kebutuhan bisnis.',
                'icon' => 'layers',
            ],
        ];
    }

    public static function label(?string $type): string
    {
        return self::all()[$type]['label'] ?? 'Personal';
    }

    public static function description(?string $type): string
    {
        return self::all()[$type]['description'] ?? '';
    }

    /** @return array<string, string> daftar kunci => label, untuk dropdown filter */
    public static function options(): array
    {
        return array_map(fn (array $type) => $type['label'], self::all());
    }
}
