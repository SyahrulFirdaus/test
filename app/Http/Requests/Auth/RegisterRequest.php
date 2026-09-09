<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ValidatesProfileFields;
use App\Support\CustomerType;
use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Data akun pada langkah pertama pendaftaran.
 *
 * Pelanggan perorangan sekaligus mengisi data pengiriman di sini, karena itulah
 * satu-satunya alamat yang mereka punya. Pelanggan perusahaan hanya mengisi
 * kontak penanggung jawab akun — alamatnya ditanyakan pada langkah Data
 * Perusahaan berikutnya, lengkap dengan wilayah berjenjangnya.
 */
class RegisterRequest extends FormRequest
{
    use ValidatesProfileFields;

    /** Tipe akun yang sedang didaftarkan; menentukan field mana yang diminta. */
    private string $customerType = CustomerType::PERSONAL;

    public function forCustomerType(?string $type): self
    {
        $this->customerType = CustomerType::exists($type) ? $type : CustomerType::PERSONAL;

        return $this;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = array_merge($this->profileRules(), [
            'email' => ['required', 'email:rfc', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', ...PasswordPolicy::rules()],
        ]);

        if ($this->customerType === CustomerType::BUSINESS) {
            // Alamat perusahaan dikumpulkan pada langkah berikutnya, jadi tidak
            // ditanyakan dua kali di sini.
            unset($rules['city'], $rules['postal_code'], $rules['address']);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge($this->profileMessages(), PasswordPolicy::messages(), [
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
            'phone.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda +, -, dan tanda kurung.',
        ]);
    }
}
