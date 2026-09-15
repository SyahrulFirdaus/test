<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Parameter rumus Harga Jual satu teknologi (tab Harga pada Price List).
 * Nilai turunannya (HPP, Risk Cost, Subtotal, Profit, Harga Jual) tidak
 * divalidasi di sini — semuanya dihitung otomatis, lihat App\Models\PricingFormula.
 */
class UpdatePricingFormulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'machine_time_hours' => ['required', 'numeric', 'min:0'],
            'machine_cost' => ['required', 'numeric', 'min:0'],
            'material_qty_g' => ['required', 'numeric', 'min:0'],
            'material_price_per_g' => ['required', 'numeric', 'min:0'],
            'risk_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'packaging_cost' => ['required', 'numeric', 'min:0'],
            'overtime_cost' => ['required', 'numeric', 'min:0'],
            'profit_percent' => ['required', 'numeric', 'min:0', 'max:100'],

            // Basic Fee tidak diisi langsung: yang diisi ukuran objectnya, dan
            // tarifnya diturunkan App\Support\BasicFee dari ukuran itu.
            'object_size_mm' => ['required', 'numeric', 'min:0', 'max:10000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Kolom ini wajib diisi.',
            'numeric' => 'Kolom ini harus berupa angka.',
            'risk_percent.max' => 'Persentase risiko maksimum 100%.',
            'profit_percent.max' => 'Persentase profit maksimum 100%.',
            'object_size_mm.max' => 'Ukuran object maksimum 10.000 mm.',
        ];
    }
}
