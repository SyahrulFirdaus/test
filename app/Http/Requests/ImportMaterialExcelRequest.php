<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Unggahan berkas Import material Price List.
 *
 * Yang diperiksa di sini hanyalah berkasnya: ekstensi, jenis, dan ukuran. Isi
 * tabelnya — judul kolom, tiap baris, tiap angka — diperiksa
 * App\Services\PriceList\Excel\MaterialImportReader setelah berkasnya lolos.
 *
 * Hanya .xlsx yang diterima. Sengaja tidak termasuk .xls maupun .csv: keduanya
 * tidak membawa format angka, sehingga kolom harga yang sudah dirapikan di
 * berkas ekspor akan kembali menjadi teks bebas dan menuntut penebakan yang
 * tidak perlu ada.
 */
class ImportMaterialExcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:5120',
                // `mimes` memeriksa ekstensinya, `mimetypes` isi berkasnya —
                // berkas berformat lain yang sekadar diganti namanya menjadi
                // .xlsx tertahan di aturan kedua.
                'mimes:xlsx',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/octet-stream',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.required' => 'Pilih dulu file Excel yang ingin diimpor.',
            'file.file' => 'Unggahan tidak terbaca sebagai file.',
            'file.mimes' => 'File harus berformat Excel .xlsx.',
            'file.mimetypes' => 'Isi file bukan Excel .xlsx yang sah.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ];
    }
}
