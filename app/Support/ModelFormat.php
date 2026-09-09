<?php

namespace App\Support;

/**
 * Format berkas 3D yang diterima sistem.
 *
 * Satu daftar untuk semuanya: validasi unggahan, atribut `accept` pada input
 * berkas, teks "File Types" di area unggah, dan halaman panduan. Kembarannya di
 * sisi browser ada pada resources/js/modules/model-formats.js.
 *
 * STL, OBJ, dan 3MF adalah format mesh sehingga geometrinya dapat diukur
 * langsung — baik di browser maupun di server. STEP/STP adalah format CAD
 * (B-rep); geometrinya ditesselasi di browser memakai OpenCascade, jadi berkas
 * yang ditambahkan langsung dari dashboard tidak dapat diukur server dan
 * menunggu peninjauan engineer.
 */
class ModelFormat
{
    /** @return array<int, string> */
    public static function extensions(): array
    {
        return ['stl', 'stp', 'step', 'obj', '3mf'];
    }

    /** Format yang geometrinya dapat diukur App\Services\MeshInspector. */
    public static function measurable(): array
    {
        return ['stl', 'obj', '3mf'];
    }

    public static function isMeasurable(?string $extension): bool
    {
        return in_array(strtolower((string) $extension), self::measurable(), true);
    }

    /** Label yang dibaca pengguna, mis. "STL, STP, STEP, OBJ, 3MF". */
    public static function label(): string
    {
        return implode(', ', array_map('strtoupper', self::extensions()));
    }

    /** @return array<int, string> daftar huruf besar untuk ditampilkan satu per satu */
    public static function display(): array
    {
        return array_map('strtoupper', self::extensions());
    }

    /** Aturan validasi Laravel, mis. "extensions:stl,stp,step,obj,3mf". */
    public static function rule(): string
    {
        return 'extensions:'.implode(',', self::extensions());
    }

    /** Isi atribut `accept` pada input berkas. */
    public static function accept(): string
    {
        return implode(',', array_map(fn (string $extension) => '.'.$extension, self::extensions()));
    }
}
