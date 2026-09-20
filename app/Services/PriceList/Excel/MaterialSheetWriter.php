<?php

namespace App\Services\PriceList\Excel;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Penulis berkas .xlsx material Price List.
 *
 * Satu penulis untuk ketiga berkas — Export, Template, dan Contoh — sehingga
 * bentuknya tidak mungkin berbeda: yang membedakan hanyalah baris yang
 * diserahkan kepadanya.
 *
 * Angka ditulis sebagai angka; tampilan rupiahnya dipasang lewat format sel,
 * bukan dengan menuliskan "Rp185.000" sebagai teks. Dengan begitu kolomnya
 * tetap dapat dijumlahkan di Excel dan tetap terbaca sebagai angka saat berkas
 * yang sama diunggah kembali lewat Import.
 */
class MaterialSheetWriter
{
    private const BORDER = 'FFBFBFBF';

    /** Format rupiah; pemisah ribuan mengikuti setelan Excel pembacanya. */
    private const MONEY = '"Rp"#,##0';

    /**
     * Susun berkas dan kembalikan isinya sebagai string biner.
     *
     * Kolom dan warna judulnya ditentukan pemanggil (lihat MaterialSheet),
     * sehingga penulis ini tetap satu untuk seluruh teknologi.
     *
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array{0: string, 1: string}  $theme  latar dan warna tulisan judul
     */
    public function build(string $sheetTitle, array $columns, array $rows, array $theme): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Judul sheet Excel dibatasi 31 karakter dan menolak beberapa tanda.
        $sheet->setTitle(mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '-', $sheetTitle), 0, 31));

        $lastColumn = chr(ord('A') + count($columns) - 1);

        $this->writeHeader($sheet, $columns, $lastColumn, $theme);
        $this->writeRows($sheet, $rows, $columns);
        $this->style($sheet, $columns, $lastColumn, count($rows));

        return $this->render($spreadsheet);
    }

    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array{0: string, 1: string}  $theme
     */
    private function writeHeader($sheet, array $columns, string $lastColumn, array $theme): void
    {
        foreach ($columns as $index => $column) {
            $sheet->setCellValue(chr(ord('A') + $index).'1', $column['heading']);
        }

        $header = $sheet->getStyle('A1:'.$lastColumn.'1');
        $header->getFont()->setBold(true)->getColor()->setARGB($theme[1]);
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($theme[0]);
        $header->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->getRowDimension(1)->setRowHeight(28);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $columns
     */
    private function writeRows($sheet, array $rows, array $columns): void
    {
        foreach ($rows as $offset => $row) {
            $line = $offset + 2;

            foreach ($columns as $index => $column) {
                $cell = chr(ord('A') + $index).$line;
                $value = $row[$index] ?? null;

                /*
                 * Nama material dan remark ditulis sebagai teks secara
                 * eksplisit. Tanpa itu, nilai seperti "PA 12" atau remark yang
                 * diawali tanda sama dengan dapat ditafsirkan Excel sebagai
                 * angka atau rumus.
                 */
                if (is_string($value)) {
                    $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);

                    continue;
                }

                $sheet->setCellValue($cell, $value);
            }
        }
    }

    /** @param  array<int, array<string, mixed>>  $columns */
    private function style($sheet, array $columns, string $lastColumn, int $rowCount): void
    {
        // Satu baris kosong tetap diberi bingkai supaya Template terlihat
        // sebagai tabel yang menunggu diisi, bukan lembar kosong.
        $lastRow = max(2, $rowCount + 1);
        $range = 'A1:'.$lastColumn.$lastRow;

        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB(self::BORDER);

        foreach ($columns as $index => $column) {
            $letter = chr(ord('A') + $index);
            $body = $letter.'2:'.$letter.$lastRow;

            $sheet->getColumnDimension($letter)->setAutoSize(true);

            if ($column['money']) {
                $sheet->getStyle($body)->getNumberFormat()->setFormatCode(self::MONEY);
                $sheet->getStyle($body)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                continue;
            }

            if ($column['key'] === 'no') {
                $sheet->getStyle($body)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Nama material resin panjang-panjang; tanpa ini kolomnya melebar
            // sampai tabelnya tidak lagi muat satu layar.
            if ($column['wrap'] ?? false) {
                $sheet->getStyle($body)->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);
            }
        }

        // Lebar minimum kolom, supaya judul yang panjang tidak membuat
        // kolomnya sempit saat isinya pendek.
        foreach ($columns as $index => $column) {
            $dimension = $sheet->getColumnDimension(chr(ord('A') + $index));

            if ($dimension->getWidth() > 0 && $dimension->getWidth() < $column['width']) {
                $dimension->setAutoSize(false)->setWidth($column['width']);
            }
        }

        // Judul tetap terlihat saat digulir, dan dapat disaring.
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);
        $sheet->setSelectedCell('A2');
    }

    private function render(Spreadsheet $spreadsheet): string
    {
        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $contents = (string) ob_get_clean();

        // Lembar kerja memegang banyak objek yang saling menunjuk; tanpa ini
        // memorinya baru dilepas di akhir permintaan.
        $spreadsheet->disconnectWorksheets();

        return $contents;
    }
}
