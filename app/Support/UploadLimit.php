<?php

namespace App\Support;

/**
 * Batas ukuran unggahan yang benar-benar dapat diterima server.
 *
 * Batas yang dikehendaki aplikasi diatur di config/printing.php
 * (`limits.max_file_size_mb` dan `limits.max_models_per_quotation`), tetapi
 * php.ini tetap menjadi batas keras: `upload_max_filesize`, `post_max_size`,
 * dan `max_file_uploads` tidak dapat dilampaui dari sisi aplikasi.
 *
 * Nilai efektifnya adalah yang terkecil di antara keduanya, supaya form di
 * browser menolak berkas kelewat besar dengan pesan yang jelas — bukan
 * berakhir sebagai error 419/413 yang membingungkan pengguna.
 *
 * Agar batas 300 MB per berkas dan 25 berkas per permintaan benar-benar
 * berlaku, php.ini perlu disetel minimal:
 *   upload_max_filesize = 300M
 *   post_max_size       = 1024M
 *   max_file_uploads    = 30
 */
class UploadLimit
{
    /** Sisakan ruang untuk field form lain di dalam body POST. */
    private const OVERHEAD_BYTES = 512 * 1024;

    /** Batas per berkas yang dikehendaki aplikasi, dalam byte. */
    public static function preferredBytes(): int
    {
        return (int) round(((float) config('printing.limits.max_file_size_mb', 300)) * 1024 * 1024);
    }

    public static function preferredMegabytes(): float
    {
        return round(self::preferredBytes() / 1024 / 1024, 1);
    }

    public static function maxBytes(): int
    {
        $upload = self::parseIniSize((string) ini_get('upload_max_filesize'));
        $post = self::parseIniSize((string) ini_get('post_max_size'));

        $limits = array_filter([$upload, $post], fn (int $value) => $value > 0);

        if ($limits === []) {
            return self::preferredBytes();
        }

        return max(1024, min(min($limits) - self::OVERHEAD_BYTES, self::preferredBytes()));
    }

    public static function maxKilobytes(): int
    {
        return (int) floor(self::maxBytes() / 1024);
    }

    public static function maxMegabytes(): float
    {
        return round(self::maxBytes() / 1024 / 1024, 1);
    }

    /** Batas per berkas server lebih kecil daripada yang dikehendaki aplikasi. */
    public static function throttledByServer(): bool
    {
        return self::maxBytes() < self::preferredBytes();
    }

    /**
     * Batas gabungan seluruh berkas dalam satu permintaan.
     *
     * Satu permintaan penawaran dapat memuat beberapa model sekaligus, dan
     * yang membatasi keseluruhannya adalah `post_max_size` — bukan
     * `upload_max_filesize` yang berlaku per berkas.
     */
    public static function maxTotalBytes(): int
    {
        $post = self::parseIniSize((string) ini_get('post_max_size'));

        if ($post <= 0) {
            return self::preferredBytes() * self::maxFiles(self::preferredFiles());
        }

        return max(1024, $post - self::OVERHEAD_BYTES);
    }

    public static function maxTotalMegabytes(): float
    {
        return round(self::maxTotalBytes() / 1024 / 1024, 1);
    }

    /** Banyaknya model per permintaan yang dikehendaki aplikasi. */
    public static function preferredFiles(): int
    {
        return max(1, (int) config('printing.limits.max_models_per_quotation', 25));
    }

    /**
     * Banyaknya berkas yang benar-benar dapat diterima dalam satu POST.
     *
     * `max_file_uploads` pada php.ini memotong berkas berlebih tanpa pesan
     * kesalahan, jadi batas halaman tidak boleh melampauinya.
     */
    public static function maxFiles(?int $preferred = null): int
    {
        $preferred ??= self::preferredFiles();
        $ini = (int) ini_get('max_file_uploads');

        return $ini > 0 ? max(1, min($preferred, $ini)) : max(1, $preferred);
    }

    private static function parseIniSize(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
