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

    /**
     * Seluruh tipe yang dikenal sistem.
     *
     * Termasuk tipe yang pendaftarannya sedang ditutup: akun lama harus tetap
     * terbaca dan tetap dapat disaring di dashboard. Untuk pendaftaran baru,
     * pakai `availableKeys()`.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [self::PERSONAL, self::BUSINESS];
    }

    public static function exists(?string $type): bool
    {
        return $type !== null && in_array($type, self::keys(), true);
    }

    /**
     * Tipe yang saat ini dibuka untuk PENDAFTARAN BARU.
     *
     * Akun Business sementara ditutup lewat config/registration.php. Yang
     * berubah hanya pintu pendaftarannya — akun Business yang sudah ada tidak
     * tersentuh sama sekali.
     *
     * @return array<int, string>
     */
    public static function availableKeys(): array
    {
        return array_values(array_filter(
            self::keys(),
            fn (string $key) => $key !== self::BUSINESS || config('registration.business_accounts', false),
        ));
    }

    public static function isAvailable(?string $type): bool
    {
        return $type !== null && in_array($type, self::availableKeys(), true);
    }

    /**
     * Katalog tipe yang dibuka untuk pendaftaran baru, beserta keterangannya.
     *
     * @return array<string, array<string, string>>
     */
    public static function available(): array
    {
        return array_intersect_key(self::all(), array_flip(self::availableKeys()));
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
