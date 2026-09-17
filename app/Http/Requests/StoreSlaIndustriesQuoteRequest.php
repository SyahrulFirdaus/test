<?php

namespace App\Http\Requests;

/**
 * Kuotasi JLC satu model SLA Industries pada Detail Penawaran.
 *
 * Isinya sama persis dengan parameter bawaan di Price List — karena itu aturan
 * dan pesannya diwarisi utuh — ditambah Nama Produk/Model yang hanya ada pada
 * penawaran sungguhan.
 */
class StoreSlaIndustriesQuoteRequest extends StoreSlaIndustriesFormulaRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'product_name' => ['nullable', 'string', 'max:150'],
        ];
    }
}
