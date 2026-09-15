<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Kolom satu baris Price List Machine Cost — komersial maupun fisik.
 *
 * Listrik/Hour, Machine Cost, dan Pembulatan tidak divalidasi di sini —
 * ketiganya dihitung otomatis, lihat App\Models\MachineCost.
 *
 * Spesifikasi fisik (dimensi, berat, volume cetak) seluruhnya opsional: mesin
 * dapat didaftarkan lebih dulu untuk keperluan harga, lalu detailnya dilengkapi
 * kemudian. Yang kosong tampil sebagai "—" pada expand Price List.
 */
class StoreMachineCostRequest extends FormRequest
{
    /**
     * Batas atas kolom rupiah `decimal(12,2)`.
     *
     * Sama seperti pada material: `numeric` meloloskan notasi ilmiah seperti
     * "2e23" yang baru gagal di MySQL sebagai galat 500.
     */
    private const MAX_RUPIAH = '9999999999.99';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $dimension = ['nullable', 'numeric', 'min:0', 'max:99999'];

        return [
            'mesin' => ['required', 'string', 'max:60'],
            'print_technology_id' => ['nullable', 'integer', 'exists:print_technologies,id'],
            // Kolomnya `decimal(6,3)`, jadi paling besar 999,999 KWH.
            'watt_kwh' => ['required', 'numeric', 'min:0', 'max:999.999'],
            'harga_listrik' => ['required', 'numeric', 'min:0', 'max:'.self::MAX_RUPIAH],
            'depresiasi' => ['required', 'numeric', 'min:0', 'max:'.self::MAX_RUPIAH],

            'width_mm' => $dimension,
            'depth_mm' => $dimension,
            'height_mm' => $dimension,
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:9999'],

            'build_volume_x' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'build_volume_y' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'build_volume_z' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'mesin.required' => 'Nama mesin wajib diisi.',
            'watt_kwh.required' => 'Watt (KWH) wajib diisi.',
            'harga_listrik.required' => 'Harga Listrik wajib diisi.',
            'depresiasi.required' => 'Depresiasi wajib diisi.',
            'watt_kwh.max' => 'Watt (KWH) terlalu besar, maksimal 999,999.',
            'harga_listrik.max' => 'Harga Listrik terlalu besar, maksimal Rp9.999.999.999.',
            'depresiasi.max' => 'Depresiasi terlalu besar, maksimal Rp9.999.999.999.',
            'print_technology_id.exists' => 'Teknologi yang dipilih tidak ditemukan.',
            'width_mm.numeric' => 'Lebar (W) harus berupa angka milimeter.',
            'depth_mm.numeric' => 'Kedalaman (D) harus berupa angka milimeter.',
            'height_mm.numeric' => 'Tinggi (H) harus berupa angka milimeter.',
            'weight_kg.numeric' => 'Berat harus berupa angka kilogram.',
        ];
    }
}
