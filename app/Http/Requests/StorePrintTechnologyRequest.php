<?php

namespace App\Http\Requests;

use App\Models\PrintTechnology;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Parameter satu teknologi cetak.
 *
 * Dipakai untuk menambah maupun menyunting. Bedanya hanya pada `code`: saat
 * menyunting teknologi yang sudah dipakai penawaran, kodenya tidak boleh
 * berubah — penawaran lama menunjuk ke kode itu — sehingga kolomnya dikunci
 * formulir dan nilainya diabaikan di sini.
 */
class StorePrintTechnologyRequest extends FormRequest
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

        return [
            'code' => [
                $technology instanceof PrintTechnology ? 'nullable' : 'required',
                'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('print_technologies', 'code')->ignore($technology),
            ],
            'name' => ['required', 'string', 'max:120'],
            'family' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:2000'],

            'build_volume_x' => ['required', 'integer', 'min:10', 'max:5000'],
            'build_volume_y' => ['required', 'integer', 'min:10', 'max:5000'],
            'build_volume_z' => ['required', 'integer', 'min:10', 'max:5000'],

            'shell_ratio' => ['required', 'numeric', 'min:0', 'max:1'],
            'default_infill' => ['required', 'numeric', 'min:0', 'max:1'],
            'infill_note' => ['nullable', 'string', 'max:500'],
            'min_wall_thickness_mm' => ['required', 'numeric', 'min:0.1', 'max:50'],
            'support_volume_factor' => ['required', 'numeric', 'min:0', 'max:5'],

            'layer_height_min' => ['required', 'numeric', 'min:0.001', 'max:5'],
            'layer_height_max' => ['required', 'numeric', 'min:0.001', 'max:5', 'gte:layer_height_min'],

            'throughput_cm3_per_hour' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'setup_hours' => ['required', 'numeric', 'min:0', 'max:1000'],
            'setup_fee' => ['required', 'numeric', 'min:0'],
            'machine_rate_per_hour' => ['required', 'numeric', 'min:0'],

            'allows_hollow' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            // Checkbox yang tidak dicentang tidak ikut terkirim.
            'allows_hollow' => $this->boolean('allows_hollow'),
            'code' => is_string($this->input('code')) ? strtoupper(trim($this->input('code'))) : $this->input('code'),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode teknologi wajib diisi.',
            'code.regex' => 'Kode teknologi hanya boleh huruf dan angka, tanpa spasi.',
            'code.unique' => 'Kode teknologi tersebut sudah dipakai.',
            'name.required' => 'Nama teknologi wajib diisi.',
            'layer_height_max.gte' => 'Tebal lapisan maksimum tidak boleh lebih kecil daripada minimumnya.',
            'required' => 'Kolom ini wajib diisi.',
            'numeric' => 'Kolom ini harus berupa angka.',
            'integer' => 'Kolom ini harus berupa bilangan bulat.',
        ];
    }
}
