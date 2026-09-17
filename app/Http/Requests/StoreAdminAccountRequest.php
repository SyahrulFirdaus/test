<?php

namespace App\Http\Requests;

use App\Support\AdminPermission;
use App\Support\PasswordPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Akun Admin baru yang dibuat Superadmin.
 *
 * Kata sandinya tunduk pada ketentuan yang sama dengan akun lain
 * (App\Support\PasswordPolicy) dan di-hash oleh cast `password` pada model —
 * tidak pernah tersimpan sebagai teks biasa.
 */
class StoreAdminAccountRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', ...PasswordPolicy::rules()],
            'is_active' => ['nullable', 'boolean'],

            // Hak akses `<modul>.<aksi>` yang menyala. Yang mati tidak terkirim.
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(AdminPermission::keys())],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Checkbox yang tidak dicentang tidak ikut terkirim; tanpa ini akun
        // baru akan selalu tercatat aktif.
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
