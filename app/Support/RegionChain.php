<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

/**
 * Aturan wilayah berjenjang: provinsi, kabupaten/kota, kecamatan, kelurahan.
 *
 * Dipakai bersama oleh buku alamat pelanggan dan data perusahaan pada
 * pendaftaran Business, sehingga keduanya tidak mungkin memeriksa hal yang sama
 * dengan cara berbeda.
 *
 * Keberadaan id saja tidak cukup: yang diperiksa adalah rantai induknya.
 * Kabupaten harus benar berada di provinsi yang dipilih, kecamatan di kabupaten
 * itu, dan kelurahan di kecamatan itu. Tanpa pemeriksaan berantai, kiriman yang
 * disusun sendiri bisa menghasilkan gabungan mustahil seperti
 * "Jawa Barat + Kota Medan + Cimahi Selatan".
 */
class RegionChain
{
    /**
     * Rantai wilayah: tabel, nama field, kolom induk, dan field induknya.
     *
     * @var array<int, array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    public const LEVELS = [
        ['provinces', 'province_id', null, null],
        ['regencies', 'regency_id', 'province_id', 'province_id'],
        ['districts', 'district_id', 'regency_id', 'regency_id'],
        ['villages', 'village_id', 'district_id', 'district_id'],
    ];

    /** @return array<string, array<int, string>> */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::LEVELS as [$table, $field]) {
            $rules[$field] = ['required', 'integer', 'exists:'.$table.',id'];
        }

        return $rules;
    }

    /** Periksa bahwa tiap tingkat benar berada di dalam induknya. */
    public static function validate(Validator $validator, Request $request): void
    {
        foreach (self::LEVELS as [$table, $field, $parentColumn, $parentField]) {
            if ($parentColumn === null) {
                continue;
            }

            // Tingkat yang isiannya sendiri sudah bermasalah dilewati: pesannya
            // sudah ada, dan memeriksa induknya hanya akan menambah pesan kedua
            // untuk kesalahan yang sama.
            if ($validator->errors()->hasAny([$field, $parentField])) {
                continue;
            }

            $belongs = DB::table($table)
                ->where('id', $request->input($field))
                ->where($parentColumn, $request->input($parentField))
                ->exists();

            if (! $belongs) {
                $validator->errors()->add($field, self::MISMATCH[$field]);
            }
        }
    }

    /** @var array<string, string> */
    private const MISMATCH = [
        'regency_id' => 'Kota/kabupaten tersebut tidak berada di provinsi yang dipilih.',
        'district_id' => 'Kecamatan tersebut tidak berada di kota/kabupaten yang dipilih.',
        'village_id' => 'Kelurahan/desa tersebut tidak berada di kecamatan yang dipilih.',
    ];

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'province_id.required' => 'Provinsi wajib dipilih.',
            'province_id.exists' => 'Provinsi tersebut tidak dikenal.',
            'regency_id.required' => 'Kota/kabupaten wajib dipilih.',
            'regency_id.exists' => 'Kota/kabupaten tersebut tidak dikenal.',
            'district_id.required' => 'Kecamatan wajib dipilih.',
            'district_id.exists' => 'Kecamatan tersebut tidak dikenal.',
            'village_id.required' => 'Kelurahan/desa wajib dipilih.',
            'village_id.exists' => 'Kelurahan/desa tersebut tidak dikenal.',
        ];
    }
}
