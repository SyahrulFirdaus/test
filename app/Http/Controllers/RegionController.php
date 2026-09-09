<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\JsonResponse;

/**
 * Isi dropdown wilayah bertingkat.
 *
 * Dipakai dua tempat: buku alamat di dashboard pelanggan, dan data perusahaan
 * pada pendaftaran Business. Yang kedua diisi sebelum akun terbentuk, jadi
 * endpoint ini harus terbuka untuk pengunjung yang belum masuk.
 *
 * Daftar kelurahan seluruh Indonesia berjumlah puluhan ribu baris, jadi tidak
 * mungkin dikirim sekaligus bersama halaman; tiap tingkat diambil sesuai
 * induknya ketika pilihan sebelumnya dibuat.
 *
 * Yang dikembalikan hanya id dan nama. Isinya memang informasi publik — daftar
 * wilayah administratif — sehingga tidak ada yang bocor karenanya; pembatasan
 * lajunya diatur di route agar tidak dipakai menggerus basis data.
 */
class RegionController extends Controller
{
    /** Kabupaten/kota di dalam sebuah provinsi. */
    public function regencies(int $province): JsonResponse
    {
        return $this->options(Regency::where('province_id', $province));
    }

    /** Kecamatan di dalam sebuah kabupaten/kota. */
    public function districts(int $regency): JsonResponse
    {
        return $this->options(District::where('regency_id', $regency));
    }

    /** Kelurahan/desa di dalam sebuah kecamatan. */
    public function villages(int $district): JsonResponse
    {
        return $this->options(Village::where('district_id', $district));
    }

    private function options($query): JsonResponse
    {
        return response()->json(
            $query->ordered()->get(['id', 'name'])
        );
    }
}
