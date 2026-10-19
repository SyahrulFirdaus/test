<?php

namespace App\Support;

/**
 * Nomor WhatsApp dalam format internasional tanpa tanda, mis. 6281234567890.
 *
 * Pelanggan umumnya mengisi format lokal (0812…) atau menyertakan spasi, tanda
 * +, dan tanda hubung; API WhatsApp hanya menerima digit berawalan kode negara.
 */
class WhatsAppNumber
{
    public static function normalize(?string $number): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $number);

        if ($digits === '' || $digits === null) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return self::isValid($digits) ? $digits : null;
    }

    /** Nomor internasional yang masuk akal: 10–15 digit, tidak diawali 0. */
    public static function isValid(?string $digits): bool
    {
        return is_string($digits) && preg_match('/^[1-9]\d{9,14}$/', $digits) === 1;
    }

    /** Nomor disamarkan untuk tampilan, hanya empat angka terakhir yang terlihat. */
    public static function mask(?string $number): ?string
    {
        if (blank($number)) {
            return null;
        }

        $length = strlen($number);

        return $length <= 4 ? $number : str_repeat('*', $length - 4).substr($number, -4);
    }
}
