<?php

namespace App\Http\Requests;

use App\Support\RegionChain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Aturan pengisian alamat pengiriman.
 *
 * Seluruh bagian alamat wajib diisi — tanpa kecamatan, kelurahan, atau kode pos
 * yang jelas, paket tidak dapat dikirim dengan andal.
 *
 * Wilayahnya diperiksa berantai lewat App\Support\RegionChain, aturan yang sama
 * dengan yang dipakai data perusahaan pada pendaftaran Business.
 */
class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'recipient_phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{8,32}$/'],

            ...RegionChain::rules(),

            'postal_code' => ['required', 'string', 'max:12', 'regex:/^[0-9]{5}$/'],

            'detail' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:255'],

            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => RegionChain::validate($validator, $this),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(RegionChain::messages(), [
            'label.required' => 'Beri nama alamat ini, misalnya Rumah atau Kantor.',
            'recipient_name.required' => 'Nama penerima wajib diisi.',
            'recipient_phone.required' => 'Nomor telepon penerima wajib diisi.',
            'recipient_phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda +, -, dan tanda kurung.',
            'postal_code.required' => 'Kode pos wajib diisi.',
            'postal_code.regex' => 'Kode pos terdiri dari 5 angka.',
            'detail.required' => 'Alamat lengkap wajib diisi.',
        ]);
    }
}
