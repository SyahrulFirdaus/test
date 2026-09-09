<?php

namespace App\Http\Requests;

use App\Support\RegionChain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Data perusahaan pada langkah kedua pendaftaran Business.
 *
 * Alamatnya memakai wilayah berjenjang yang sama dengan buku alamat pelanggan,
 * dan rantai induknya diperiksa di server — kabupaten harus benar berada di
 * provinsi yang dipilih, kecamatan di kabupaten itu, dan seterusnya.
 *
 * Website dibiarkan opsional: tidak semua perusahaan memilikinya, dan menahan
 * pendaftaran hanya karena itu tidak sepadan.
 */
class StoreBusinessProfileRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:160'],
            'pic_name' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{8,32}$/'],
            'email' => ['required', 'string', 'email:rfc', 'max:160'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'industry' => ['required', 'string', 'max:120'],

            'address' => ['required', 'string', 'max:500'],
            ...RegionChain::rules(),
            'postal_code' => ['required', 'string', 'max:12', 'regex:/^[0-9]{5}$/'],
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
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'pic_name.required' => 'Nama PIC wajib diisi.',
            'position.required' => 'Jabatan / posisi wajib diisi.',
            'phone.required' => 'Nomor telepon perusahaan wajib diisi.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, tanda +, -, dan tanda kurung.',
            'email.required' => 'Email perusahaan wajib diisi.',
            'email.email' => 'Format email perusahaan belum benar.',
            'website.url' => 'Alamat website belum benar. Awali dengan https://',
            'industry.required' => 'Industri / bidang perusahaan wajib dipilih.',
            'address.required' => 'Alamat lengkap perusahaan wajib diisi.',
            'postal_code.required' => 'Kode pos wajib diisi.',
            'postal_code.regex' => 'Kode pos terdiri dari 5 angka.',
        ]);
    }
}
