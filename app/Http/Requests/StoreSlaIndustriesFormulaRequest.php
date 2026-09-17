<?php

namespace App\Http\Requests;

use App\Support\SlaIndustries;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Parameter Rumus Harga SLA Industries.
 *
 * Dipakai BERSAMA oleh parameter bawaan di Price List dan kuotasi per model
 * pada Detail Penawaran, karena keduanya menerima isian yang sama persis.
 * App\Http\Requests\StoreSlaIndustriesQuoteRequest menurunkannya dan hanya
 * menambahkan Nama Produk.
 *
 * Nilai turunan — Total Bayar ke JLC, HPP, Profit, Final Price — tidak pernah
 * divalidasi maupun diterima dari kiriman: seluruhnya dihitung ulang server
 * lewat App\Support\SlaIndustries::compute(). Angka yang dikirim browser hanya
 * pratinjau; yang tersimpan adalah hasil hitung server sendiri.
 *
 * Begitu pula Dollar Hari Ini. Kurs USD/IDR diambil server dari
 * App\Services\UsdRate, bukan dari kiriman formulir — lihat rules().
 */
class StoreSlaIndustriesFormulaRequest extends FormRequest
{
    /**
     * Batas atas kolom `decimal(14,2)`.
     *
     * Aturan `numeric` saja meloloskan notasi ilmiah seperti "2e23", yang baru
     * gagal di MySQL sebagai galat 500 alih-alih pesan validasi yang terbaca.
     */
    protected const MAX_AMOUNT = '999999999999.99';

    public function authorize(): bool
    {
        // Route-nya sudah dijaga middleware peran masing-masing.
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // `usd_rate` SENGAJA tidak ada di sini. Kurs tidak lagi diketik
            // Admin: nilainya diambil server sendiri dari App\Services\UsdRate
            // saat menyimpan, jadi kiriman formulir tidak dapat menentukannya —
            // termasuk kiriman yang dibuat sendiri ke endpoint ini.
            'jlc_price_usd' => ['required', 'numeric', 'min:0', 'max:'.static::MAX_AMOUNT],
            'jlc_shipping_usd' => ['required', 'numeric', 'min:0', 'max:'.static::MAX_AMOUNT],
            'customs_idr' => ['required', 'numeric', 'min:0', 'max:'.static::MAX_AMOUNT],

            // Batas margin ditegakkan DI SERVER juga, bukan hanya di formulir:
            // pemeriksaan di browser dapat dilewati begitu saja.
            'margin_percent' => [
                'required',
                'numeric',
                'min:'.SlaIndustries::MIN_MARGIN,
                'max:'.SlaIndustries::MAX_MARGIN,
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'jlc_price_usd.required' => 'Harga JLC wajib diisi.',
            'jlc_shipping_usd.required' => 'Ongkir JLC wajib diisi.',
            'customs_idr.required' => 'DHL Beacukai wajib diisi.',
            'margin_percent.required' => 'Margin Profit wajib diisi.',
            'margin_percent.min' => SlaIndustries::marginMessage(),
            'margin_percent.max' => SlaIndustries::marginMessage(),
            'numeric' => 'Kolom ini harus berupa angka.',
            'min' => 'Nilainya tidak boleh negatif.',
            'max' => 'Nilainya terlalu besar.',
        ];
    }
}
