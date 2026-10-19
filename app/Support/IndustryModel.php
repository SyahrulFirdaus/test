<?php

namespace App\Support;

/**
 * File 3D asli (STL/OBJ) untuk halaman Industri.
 *
 * Letakkan file di public/models/industries/{slug industri}/{nama}.stl
 * (atau .obj). Bila ada, file itulah yang dirender di kartu, galeri, hero,
 * dan viewer; bila tidak ada, dipakai model buatan program
 * (resources/js/modules/industry-parts.js).
 *
 * - Kartu ringkasan & hero : {nama} = "utama"; bila tidak ada, dipakai file
 *                            contoh pertama di galeri.
 * - Contoh di galeri       : {nama} = nama file gambar contohnya tanpa
 *                            ekstensi, mis. "intake-ducting" untuk
 *                            images/industries/otomotif-motorsport/intake-ducting.webp.
 */
final class IndustryModel
{
    public const EXTENSIONS = ['stl', 'obj'];

    public const DIRECTORY = 'models/industries';

    public const PHOTO_DIRECTORY = 'images/industries';

    public const PHOTO_EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png'];

    /** URL publik file 3D asli, atau null bila belum tersedia. */
    public static function url(string $slug, string $name): ?string
    {
        foreach (self::EXTENSIONS as $extension) {
            $path = self::DIRECTORY."/{$slug}/{$name}.{$extension}";

            if (is_file(public_path($path))) {
                return asset($path);
            }
        }

        return null;
    }

    /** Nama file 3D untuk satu contoh galeri (mengikuti nama gambarnya). */
    public static function exampleName(array $example): ?string
    {
        $name = pathinfo($example['image'] ?? '', PATHINFO_FILENAME);

        return $name !== '' ? $name : null;
    }

    public static function forExample(string $slug, array $example): ?string
    {
        $name = self::exampleName($example);

        return $name ? self::url($slug, $name) : null;
    }

    /**
     * Foto asli untuk kartu ringkasan di /industri:
     * public/images/industries/{slug}/utama.{webp|jpg|jpeg|png}; bila tidak
     * ada, dipakai foto contoh pertama galeri bila sudah tersedia.
     */
    public static function photoForIndustry(array $industry): ?string
    {
        foreach (self::PHOTO_EXTENSIONS as $extension) {
            $path = self::PHOTO_DIRECTORY."/{$industry['slug']}/utama.{$extension}";

            if (is_file(public_path($path))) {
                return asset($path);
            }
        }

        $first = $industry['examples'][0]['image'] ?? null;

        return $first && is_file(public_path($first)) ? asset($first) : null;
    }

    /** File untuk kartu ringkasan dan hero industri. */
    public static function forIndustry(array $industry): ?string
    {
        return self::url($industry['slug'], 'utama')
            ?? (isset($industry['examples'][0]) ? self::forExample($industry['slug'], $industry['examples'][0]) : null);
    }
}
