<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UsdRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Kurs USD/IDR untuk Form Perhitungan SLA Industries di browser.
 *
 * Satu-satunya pemakainya adalah formulir itu sendiri: saat dibuka, setiap
 * penyegaran berkala, dan ketika tombol "Coba Lagi" ditekan.
 *
 * Yang dikembalikan adalah simpanan yang SAMA dengan yang dibaca server saat
 * perhitungan disimpan — lihat App\Services\UsdRate. Dengan begitu angka yang
 * dilihat Admin sebelum menekan Simpan tidak mungkin berbeda dari angka yang
 * benar-benar tersimpan.
 */
class ExchangeRateController extends Controller
{
    public function usd(Request $request, UsdRate $rates): JsonResponse
    {
        // `force` hanya dipakai tombol "Coba Lagi": ia melewati cache supaya
        // penyedia benar-benar dihubungi ulang. Penyegaran berkala TIDAK
        // memakainya — kalau tidak, cache jadi sia-sia dan kuota penyedia habis.
        $rate = $rates->current(force: $request->boolean('force'));

        // 200 sekalipun kursnya gagal diambil: keadaan itu bukan galat HTTP,
        // melainkan keadaan yang memang harus digambarkan formulirnya (kurs
        // basi, atau belum ada kurs sama sekali).
        return response()->json($rate);
    }
}
