<?php

namespace App\Http\Requests;

use App\Models\PackagingItem;
use Illuminate\Foundation\Http\FormRequest;

class StorePackagingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'item' => ['required', 'string', 'max:60'],
            'ukuran' => ['nullable', 'string', 'max:30'],
            'dimensi' => ['nullable', 'string', 'max:60'],
            // Kolomnya `decimal(12,2)`. Batas atasnya perlu ditulis: aturan
            // `numeric` meloloskan notasi ilmiah seperti "2e23" yang baru gagal
            // di MySQL sebagai galat 500, bukan pesan validasi.
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'price_unit' => ['required', 'in:'.PackagingItem::UNIT_FLAT.','.PackagingItem::UNIT_PER_CM],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'item.required' => 'Nama item wajib diisi.',
            'price.required' => 'Harga wajib diisi.',
            'price.max' => 'Harga terlalu besar, maksimal Rp9.999.999.999.',
        ];
    }
}
