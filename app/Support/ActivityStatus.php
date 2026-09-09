<?php

namespace App\Support;

/**
 * Hasil satu aktivitas: berhasil atau gagal.
 *
 * Aktivitas gagal tetap dicatat — percobaan masuk yang ditolak justru salah
 * satu hal yang paling perlu terbaca pada jejak audit.
 */
class ActivityStatus
{
    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
        ];
    }

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function exists(?string $status): bool
    {
        return $status !== null && array_key_exists($status, self::all());
    }

    public static function label(?string $status): string
    {
        return self::all()[$status] ?? (string) $status;
    }

    /** @return array<string, string> daftar kunci => label, untuk dropdown filter */
    public static function options(): array
    {
        return self::all();
    }
}
