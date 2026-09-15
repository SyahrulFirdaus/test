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
            'price' => ['required', 'numeric', 'min:0'],
            'price_unit' => ['required', 'in:'.PackagingItem::UNIT_FLAT.','.PackagingItem::UNIT_PER_CM],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'item.required' => 'Nama item wajib diisi.',
            'price.required' => 'Harga wajib diisi.',
        ];
    }
}
