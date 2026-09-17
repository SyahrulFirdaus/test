<?php

namespace App\Http\Requests;

use App\Support\AdminPermission;
use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Penyuntingan akun Admin oleh Superadmin.
 *
 * Bedanya dengan penambahan: kata sandi boleh dikosongkan. Mengubah nama atau
 * status tidak seharusnya memaksa Superadmin menetapkan kata sandi baru —
 * yang kosong berarti kata sandi lama tetap berlaku, dan controller-nya yang
 * membuang kolomnya sebelum menyimpan.
 */
class UpdateAdminAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-nya sudah dijaga middleware `superadmin`; diperiksa lagi di sini
        // supaya pengelolaan akun dan hak akses Admin tidak pernah terbuka bagi
        // Admin biasa meski route-nya kelak dipindah.
        return (bool) $this->user()?->isSuperAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email', 'max:190',
                Rule::unique('users', 'email')->ignore($this->route('admin')),
            ],
            'password' => ['nullable', 'confirmed', ...PasswordPolicy::rules()],
            'is_active' => ['nullable', 'boolean'],

            // Hak akses `<modul>.<aksi>` yang menyala. Yang mati tidak terkirim.
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(AdminPermission::keys())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama admin wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email tersebut sudah dipakai akun lain.',
            ...PasswordPolicy::messages('password'),
            'permissions.*.in' => 'Hak akses yang dipilih tidak dikenal.',
        ];
    }
}
