<?php

namespace App\Support;

use RuntimeException;

/**
 * Pembaca arsip ZIP seperlunya, tanpa ekstensi zip.
 *
 * Berkas 3MF adalah arsip ZIP (OPC) berisi model XML. Ekstensi `zip` PHP tidak
 * selalu aktif di server, sedangkan `zlib` hampir selalu ada — jadi isian
 * arsipnya dibaca langsung dari struktur ZIP-nya: central directory dicari dari
 * ekor berkas, lalu entri yang dibutuhkan dibuka dari local header-nya.
 *
 * Hanya dua metode kompresi yang dilayani, dan memang hanya dua itu yang
 * diizinkan spesifikasi OPC: 0 (disimpan apa adanya) dan 8 (deflate).
 */
class ZipReader
{
    private const EOCD_SIGNATURE = "PK\x05\x06";

    private const CENTRAL_SIGNATURE = "PK\x01\x02";

    private const LOCAL_SIGNATURE = "PK\x03\x04";

    /** Ekor berkas yang dipindai untuk menemukan End of Central Directory. */
    private const TAIL_BYTES = 66000;

    /**
     * Isi entri pertama yang namanya berakhiran `$suffix`.
     *
     * @return string|null null bila arsipnya tidak memuat entri seperti itu
     */
    public static function firstEntryEndingWith(string $path, string $suffix): ?string
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Arsip tidak dapat dibuka.');
        }

        try {
            foreach (self::centralDirectory($handle, $path) as $entry) {
                if (str_ends_with(strtolower($entry['name']), strtolower($suffix))) {
                    return self::read($handle, $entry);
                }
            }
        } finally {
            fclose($handle);
        }

        return null;
    }

    /**
     * Daftar entri di dalam arsip beserta posisi dan ukurannya.
     *
     * @param  resource  $handle
     * @return array<int, array{name: string, method: int, compressed: int, size: int, offset: int}>
     */
    private static function centralDirectory($handle, string $path): array
    {
        $fileSize = filesize($path);
        $tail = min(self::TAIL_BYTES, $fileSize);

        fseek($handle, $fileSize - $tail);
        $buffer = (string) fread($handle, $tail);

        $eocd = strrpos($buffer, self::EOCD_SIGNATURE);

        if ($eocd === false) {
            throw new RuntimeException('Arsip tidak lengkap atau bukan berkas ZIP.');
        }

        $header = unpack('vdisk/vstart/ventries/vtotal/Vsize/Voffset', substr($buffer, $eocd + 4, 18));

        if ($header === false) {
            throw new RuntimeException('Struktur arsip tidak terbaca.');
        }

        fseek($handle, $header['offset']);
        $directory = (string) fread($handle, $header['size']);

        $entries = [];
        $cursor = 0;

        while (substr($directory, $cursor, 4) === self::CENTRAL_SIGNATURE) {
            $record = unpack(
                'vversion/vneeded/vflag/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vnameLength/vextraLength/vcommentLength/vdiskStart/vinternal/Vexternal/Voffset',
                substr($directory, $cursor + 4, 42)
            );

            if ($record === false) {
                break;
            }

            $entries[] = [
                'name' => substr($directory, $cursor + 46, $record['nameLength']),
                'method' => $record['method'],
                'compressed' => $record['compressed'],
                'size' => $record['size'],
                'offset' => $record['offset'],
            ];

            $cursor += 46 + $record['nameLength'] + $record['extraLength'] + $record['commentLength'];
        }

        return $entries;
    }

    /**
     * Baca satu entri dari local header-nya.
     *
     * @param  resource  $handle
     * @param  array{name: string, method: int, compressed: int, size: int, offset: int}  $entry
     */
    private static function read($handle, array $entry): string
    {
        fseek($handle, $entry['offset']);

        if ((string) fread($handle, 4) !== self::LOCAL_SIGNATURE) {
            throw new RuntimeException('Entri arsip tidak ditemukan pada posisinya.');
        }

        // Panjang nama dan extra field pada local header dapat berbeda dari yang
        // tercatat di central directory, jadi keduanya dibaca ulang di sini.
        $local = unpack('vversion/vflag/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vnameLength/vextraLength', (string) fread($handle, 26));

        if ($local === false) {
            throw new RuntimeException('Header entri arsip tidak terbaca.');
        }

        fseek($handle, $local['nameLength'] + $local['extraLength'], SEEK_CUR);

        $raw = $entry['compressed'] > 0 ? (string) fread($handle, $entry['compressed']) : '';

        if ($entry['method'] === 0) {
            return $raw;
        }

        if ($entry['method'] !== 8) {
            throw new RuntimeException('Kompresi arsip tidak didukung.');
        }

        $inflated = @gzinflate($raw);

        if ($inflated === false) {
            throw new RuntimeException('Isi arsip gagal didekompresi.');
        }

        return $inflated;
    }
}
