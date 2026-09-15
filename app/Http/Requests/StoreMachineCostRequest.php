<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kolom komersial satu baris Price List Machine Cost.
 *
 * Listrik/Hour, Machine Cost, dan Pembulatan tidak divalidasi di sini —
 * keduanya dihitung otomatis, lihat App\Models\MachineCost.
 */
class StoreMachineCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mesin' => ['required', 'string', 'max:60'],
            'watt_kwh' => ['required', 'numeric', 'min:0'],
            'harga_listrik' => ['required', 'numeric', 'min:0'],
            'depresiasi' => ['required', 'numeric', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mesin.required' => 'Nama mesin wajib diisi.',
            'watt_kwh.required' => 'Watt (KWH) wajib diisi.',
            'harga_listrik.required' => 'Harga Listrik wajib diisi.',
            'depresiasi.required' => 'Depresiasi wajib diisi.',
        ];
    }
}
