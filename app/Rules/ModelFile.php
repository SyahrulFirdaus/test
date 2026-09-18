<?php

namespace App\Rules;

use App\Support\ModelFormat;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Isi berkas benar-benar model 3D sesuai ekstensinya — bukan sekadar nama.
 *
 * Aturan `extensions` hanya membaca nama berkas kiriman browser, jadi skrip
 * atau HTML yang diberi akhiran `.stl` akan lolos. Di sini beberapa byte
 * pertama berkas diperiksa terhadap tanda khas tiap format:
 *
 *   STL  biner  — 80 byte header + jumlah segitiga × 50 byte + 84
 *        ASCII  — diawali kata `solid`
 *   OBJ         — teks (tanpa byte NUL) yang memuat baris v/vt/vn/f/o/g/#
 *   3MF         — arsip ZIP (`PK\x03\x04`)
 *   STEP/STP    — header `ISO-10303-21`
 *
 * Pemeriksaan ini sengaja ringan (hanya awal berkas) supaya berkas ratusan MB
 * tetap cepat divalidasi. Berkas selalu disimpan di disk privat dengan nama
 * acak, jadi tidak pernah dapat dieksekusi web server.
 */
class ModelFile implements ValidationRule
{
    private const SNIFF_BYTES = 65536;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (! in_array($extension, ModelFormat::extensions(), true)) {
            // Ditangani aturan `extensions`.
            return;
        }

        if (! self::looksLike($value->getRealPath(), $extension)) {
            $fail('Isi file tidak sesuai format '.strtoupper($extension).'. Unggah file model 3D yang sah ('.ModelFormat::label().').');
        }
    }

    public static function looksLike(string|false $path, string $extension): bool
    {
        if ($path === false || ! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $size = (int) filesize($path);

        if ($size <= 0) {
            return false;
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, self::SNIFF_BYTES);
        fclose($handle);

        return match ($extension) {
            'stl' => self::isBinaryStl($head, $size) || self::startsWithWord($head, 'solid'),
            'obj' => self::isText($head) && preg_match('/^[ \t]*(?:#|(?:v|vt|vn|vp|f|l|o|g|s|mtllib|usemtl)[ \t])/m', $head) === 1,
            '3mf' => str_starts_with($head, "PK\x03\x04"),
            'stp', 'step' => self::startsWithWord($head, 'ISO-10303-21'),
            default => false,
        };
    }

    private static function isBinaryStl(string $head, int $size): bool
    {
        if (strlen($head) < 84) {
            return false;
        }

        $count = unpack('V', substr($head, 80, 4))[1] ?? -1;

        return $count >= 0 && $size === 84 + $count * 50;
    }

    private static function startsWithWord(string $head, string $word): bool
    {
        // Abaikan BOM UTF-8 dan spasi di depan.
        $head = ltrim(preg_replace('/^\xEF\xBB\xBF/', '', $head) ?? $head);

        return strncasecmp($head, $word, strlen($word)) === 0;
    }

    private static function isText(string $head): bool
    {
        return ! str_contains($head, "\0");
    }
}
