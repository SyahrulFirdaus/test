<?php

namespace App\Http\Requests;

use App\Models\PrintTechnology;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kolom komersial satu baris material Price List.
 *
 * Harga per gram, Pembulatan Harga, dan Harga/10 gram tidak divalidasi di
 * sini — semuanya dihitung otomatis dari Harga Beli/Harga Jual, lihat
 * App\Models\Concerns\HasMaterialPricing.
 *
 * Nama material dijaga unik PER TEKNOLOGI, bukan unik global: dua teknologi
 * boleh sama-sama punya "PLA+", dan namanya itulah yang tersimpan pada
 * `quotation_items.material`.
 */
class StorePrintMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-nya sudah dijaga middleware `superadmin`.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $technology = $this->route('technology');
        $material = $this->route('material');

        return [
            'material' => [
                'required', 'string', 'max:120',
                Rule::unique('print_materials', 'material')
                    ->where('print_technology_id', $technology instanceof PrintTechnology ? $technology->getKey() : null)
                    ->ignore($material),
            ],
            'brand' => ['required', 'string', 'max:60'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'remark' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'material.required' => 'Nama material wajib diisi.',
            'material.unique' => 'Teknologi ini sudah punya material dengan nama tersebut.',
            'brand.required' => 'Brand wajib diisi.',
            'purchase_price.required' => 'Harga Beli wajib diisi.',
            'sale_price.required' => 'Harga Jual wajib diisi.',
            'numeric' => 'Kolom ini harus berupa angka.',
        ];
    }
}
