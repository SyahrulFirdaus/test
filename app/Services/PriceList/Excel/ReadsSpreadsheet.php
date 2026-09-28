<?php

namespace App\Services\PriceList\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Throwable;

/**
 * Bagian pembacaan berkas .xlsx yang sama bagi seluruh Import Price List.
 *
 * Material, Teknologi, dan Machine Cost membaca berkas dengan cara yang persis
 * sama — memuat lembar aktifnya, menafsirkan angka yang terlanjur berupa teks,
 * dan melewati baris kosong. Yang berbeda hanyalah kolom dan aturan tiap
 * barisnya, dan itu tetap milik pembacanya masing-masing.
 */
trait ReadsSpreadsheet
{
    /**
     * Isi lembar aktif sebagai tabel, atau pesan kesalahan siap tampil.
     *
     * @return array<int, array<int, mixed>>|string
     */
    protected function loadGrid(string $path): array|string
    {
        // Diperiksa lebih dulu supaya yang muncul bukan `Class "ZipArchive"
        // not found`, yang tidak memberi tahu pengelola apa pun.
        if (! ExcelRuntime::available()) {
            return ExcelRuntime::unavailableMessage();
        }

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);

            return $reader->load($path)->getActiveSheet()->toArray(null, true, false, false);
        } catch (ReaderException $exception) {
            return 'File tidak dapat dibaca sebagai Excel (.xlsx). Pastikan filenya tidak rusak dan bukan hasil ganti nama dari format lain.';
        } catch (Throwable $exception) {
            return 'File gagal dibaca: '.$exception->getMessage();
        }
    }

    /**
     * Angka dari sel yang mungkin sudah terlanjur berupa teks.
     *
     * Sel yang ditulis sebagai angka datang apa adanya. Yang berupa teks —
     * hasil menyalin dari tampilan, mis. "Rp185.000" atau "185,000" — tetap
     * diterima selama bentuknya memang satu angka; teks yang tidak menyisakan
     * angka sama sekali dikembalikan null agar dilaporkan sebagai kesalahan.
     */
    protected function number(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        $text = trim((string) $raw);

        if ($text === '') {
            return null;
        }

        // Buang lambang mata uang dan spasi, sisakan angka beserta pemisahnya.
        $text = preg_replace('/(?i)\brp\b|rp\.?|\s|\x{00A0}/u', '', $text);
        $text = str_replace(['(', ')'], '', $text);

        if (! preg_match('/^-?[\d.,]+$/', $text)) {
            return null;
        }

        /*
         * Pemisah ribuan dan desimal ditulis berbeda-beda. Yang menentukan
         * adalah tanda TERAKHIR: bila sisanya tepat dua digit, tanda itu
         * desimal; selain itu seluruh tanda dianggap pemisah ribuan.
         */
        $lastDot = strrpos($text, '.');
        $lastComma = strrpos($text, ',');
        $last = max($lastDot === false ? -1 : $lastDot, $lastComma === false ? -1 : $lastComma);

        if ($last >= 0 && strlen($text) - $last - 1 === 2 && substr_count($text, $text[$last]) === 1) {
            $text = str_replace(['.', ','], ['', ''], substr($text, 0, $last)).'.'.substr($text, $last + 1);
        } else {
            $text = str_replace(['.', ','], '', $text);
        }

        return is_numeric($text) ? (float) $text : null;
    }

    protected function text(mixed $raw): ?string
    {
        $text = trim((string) ($raw ?? ''));

        return $text === '' ? null : $text;
    }

    /** @param  array<int, mixed>  $cells */
    protected function isBlank(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) ($cell ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function rupiah(float|int $value): string
    {
        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }
}
