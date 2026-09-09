<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProfileFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Penyuntingan profil dari dashboard.
 *
 * Aturannya sama dengan formulir pendaftaran, hanya email yang diperiksa unik
 * terhadap akun lain — bukan terhadap akunnya sendiri.
 */
class UpdateProfileRequest extends FormRequest
{
    use ValidatesProfileFields;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = array_merge($this->profileRules(), [
            'email' => ['required', 'email:rfc', 'max:160', Rule::unique('users', 'email')->ignore($this->user()->id)],
        ]);

        // Akun admin tidak mengirim penawaran, jadi data pengiriman tidak wajib
        // diisi — kolomnya tetap tersedia bila ingin dilengkapi.
        if ($this->user()->isAdmin()) {
            foreach (['phone', 'city', 'postal_code', 'address'] as $field) {
                $rules[$field] = array_map(
                    fn (string $rule) => $rule === 'required' ? 'nullable' : $rule,
                    $rules[$field]
                );
            }

            return $rules;
        }

        // Alamat pelanggan diurus di menu Alamat, bukan di sini: kolom `city`,
        // `postal_code`, dan `address` pada akun kini hanya cerminan alamat
        // utamanya (lihat App\Services\AddressBook). Membiarkannya dapat
        // dikirim lewat formulir profil akan membuat keduanya saling menimpa.
        foreach (['city', 'postal_code', 'address'] as $field) {
            unset($rules[$field]);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->profileMessages();
    }
}
