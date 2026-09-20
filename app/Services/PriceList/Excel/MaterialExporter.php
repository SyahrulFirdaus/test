<?php

namespace App\Services\PriceList\Excel;

use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use Illuminate\Http\Response;

/**
 * Tiga berkas unduhan halaman material Price List.
 *
 *   Export    — seluruh material teknologi itu, diambil dari basis data;
 *   Template  — judul kolom saja, untuk diisi sendiri;
 *   Contoh    — template yang sudah terisi, sebagai acuan bentuk.
 *
 * Ketiganya memakai penulis dan definisi kolom yang sama, jadi berkas apa pun
 * yang diunduh dari sini dapat langsung diunggah kembali lewat Import.
 */
class MaterialExporter
{
    public function __construct(private readonly MaterialSheetWriter $writer) {}

    /**
     * Seluruh material satu teknologi, urut seperti pada tabel halaman.
     *
     * Angkanya dibaca dari basis data — Harga Beli dan Harga Jual apa adanya,
     * tiga kolom turunannya lewat rumus Pricing Engine yang sama dengan yang
     * dipakai tabel dan Calculator. Tidak ada angka yang berasal dari tampilan.
     */
    public function export(PrintTechnology $technology): Response
    {
        $materials = $technology->materials()->orderedByMachine()->get();

        $rows = $materials
            ->values()
            ->map(fn (PrintMaterial $material, int $index) => MaterialSheet::row($material, $index + 1, $technology))
            ->all();

        return $this->download(
            $this->write($technology, $rows),
            $this->code($technology).'_Material_'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * Template kosong.
     *
     * Sengaja TIDAK berisi material nyata: berkas ini dipakai untuk mengisi
     * data baru, dan baris contoh yang tertinggal akan ikut terimpor sebagai
     * material sungguhan. Yang ingin melihat isian lengkap mengunduh Contoh.
     */
    public function template(PrintTechnology $technology): Response
    {
        return $this->download(
            $this->write($technology, []),
            'Template_'.$this->code($technology).'_Material.xlsx',
        );
    }

    /** Contoh berisi data acuan, untuk dilihat — bukan untuk diimpor apa adanya. */
    public function example(PrintTechnology $technology): Response
    {
        return $this->download(
            $this->write($technology, MaterialSheet::exampleRows($technology)),
            'Contoh_'.$this->code($technology).'_Material.xlsx',
        );
    }

    /**
     * Susun berkasnya: kolom dan warna judul mengikuti teknologinya.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function write(PrintTechnology $technology, array $rows): string
    {
        return $this->writer->build(
            $this->code($technology),
            MaterialSheet::columns($technology),
            $rows,
            MaterialSheet::headerTheme($technology),
        );
    }

    /**
     * Nama teknologi yang aman dipakai sebagai nama berkas.
     *
     * Yang dipakai adalah nama yang dikenal tim, bukan kode mentahnya:
     * berkas SLA bernama "SLA_Material_…", bukan "SLAI_Material_…" — "SLAI"
     * hanya singkatan teknis yang tidak dipakai siapa pun dalam percakapan.
     */
    private function code(PrintTechnology $technology): string
    {
        return preg_replace('/[^A-Za-z0-9]+/', '', $technology->tabLabel()) ?: 'Material';
    }

    private function download(string $contents, string $filename): Response
    {
        return new Response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($contents),
            // Berkas dibangun ulang setiap kali diminta; versi lama tidak boleh
            // tersisa di cache peramban atau proxy.
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
