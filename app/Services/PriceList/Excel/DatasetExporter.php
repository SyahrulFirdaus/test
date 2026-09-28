<?php

namespace App\Services\PriceList\Excel;

use Illuminate\Http\Response;

/**
 * Export, Template, dan Contoh untuk satu SpreadsheetDataset.
 *
 * Pasangan MaterialExporter bagi halaman selain material: penulis berkasnya
 * sama (MaterialSheetWriter), jadi tampilan, format angka, dan cara unduhnya
 * pun sama. Berkas apa pun dari sini dapat langsung diunggah kembali lewat
 * Import.
 *
 *   Export    — {Stem}_YYYY-MM-DD.xlsx, seluruh data dari basis data;
 *   Template  — Template_{Stem}.xlsx, judul kolom saja;
 *   Contoh    — Contoh_{Stem}.xlsx, template yang sudah terisi.
 */
class DatasetExporter
{
    public function __construct(private readonly MaterialSheetWriter $writer) {}

    public function export(SpreadsheetDataset $dataset): Response
    {
        return $this->download(
            $this->write($dataset, $dataset->exportRows()),
            $dataset->fileStem().'_'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /** Sengaja tanpa baris contoh: yang tertinggal akan ikut terimpor. */
    public function template(SpreadsheetDataset $dataset): Response
    {
        return $this->download($this->write($dataset, []), 'Template_'.$dataset->fileStem().'.xlsx');
    }

    public function example(SpreadsheetDataset $dataset): Response
    {
        return $this->download($this->write($dataset, $dataset->exampleRows()), 'Contoh_'.$dataset->fileStem().'.xlsx');
    }

    /** @param  array<int, array<int, mixed>>  $rows */
    private function write(SpreadsheetDataset $dataset, array $rows): string
    {
        return $this->writer->build($dataset->title(), $dataset->columns(), $rows, $dataset->headerTheme());
    }

    private function download(string $contents, string $filename): Response
    {
        return new Response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($contents),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
