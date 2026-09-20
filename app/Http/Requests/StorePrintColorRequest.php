<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulir menu Color: nama warna dan kode hexanya.
 *
 * Kode hexa WAJIB diawali tanda pagar dan berisi enam digit heksadesimal
 * ("#B8452F"). Bentuk tiga digit ("#FFF") sengaja tidak diterima: nilai ini
 * dipakai apa adanya sebagai warna material di viewer dan sebagai contoh warna
 * pada dokumen penawaran, sehingga satu bentuk saja membuat seluruh
 * penyimpanan dan perbandingannya tidak pernah ambigu.
 */
class StorePrintColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kode hexa dinormalkan sebelum diperiksa.
     *
     * Pengelola yang menyalin kode dari aplikasi desain kerap membawa spasi
     * atau huruf kecil; keduanya dirapikan supaya tidak ditolak karena hal yang
     * tidak ia maksudkan. Tanda pagarnya sendiri TIDAK ditambahkan otomatis —
     * itu bagian dari aturan yang harus dipenuhi kirimannya.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('hex')) {
            $this->merge(['hex' => strtoupper(trim((string) $this->input('hex')))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'label' => [
                'required',
                'string',
                'max:40',
                // Dua warna bernama sama membuat pilihan pada Edit
                // Specification tidak dapat dibedakan pelanggan.
                Rule::unique('print_colors', 'label')->ignore($this->route('color')),
            ],
            'hex' => ['required', 'string', 'regex:/^#[0-9A-F]{6}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'label.required' => 'Nama warna wajib diisi.',
            'label.unique' => 'Nama warna itu sudah ada pada daftar.',
            'hex.required' => 'Kode hexa wajib diisi.',
            'hex.regex' => 'Kode hexa harus diawali # dan berisi 6 digit, contoh #B8452F.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'label' => 'nama warna',
            'hex' => 'kode hexa',
        ];
    }
}
