<?php

namespace App\Support;

use App\Models\PrintMaterial;

/**
 * Metode penentuan harga per material: Kalkulator Otomatis atau Manual.
 *
 * Satu mekanisme untuk SELURUH teknologi — FDM, SLA, MJF, SLM, dan teknologi
 * yang ditambahkan Superadmin kemudian: setiap material memilih sendiri
 * metodenya saat ditambahkan. Yang berbeda antar teknologi hanya datanya
 * (material, mesin, parameter Harga), bukan alurnya:
 *
 *  - `automatic` — rumus Harga Jual yang sudah ada (pola FDM) pada
 *    App\Services\SellingPriceEstimator; harga langsung tampil ke pelanggan.
 *  - `manual`    — harga ditahan sampai tim mengisi Form Perhitungan pada
 *    Detail Penawaran (App\Models\SlaIndustriesQuote, rumus
 *    App\Support\SlaIndustries::compute()).
 *
 * Dahulu hanya SLA, MJF, dan SLM yang memilikinya. Material teknologi lain
 * yang sudah ada dipindahkan ke Kalkulator Otomatis — metode yang memang
 * selama ini berlaku baginya — oleh migrasi
 * 2026_09_28_000069_apply_pricing_method_to_all_technologies, jadi harganya
 * tidak berubah.
 */
class PricingMethod
{
    /**
     * Teknologi yang memakai metode per material SEBELUM berlaku untuk
     * seluruh teknologi. Kini hanya dipakai migrasinya sebagai catatan.
     */
    public const TECHNOLOGIES = [SlaIndustries::CODE, 'MJF', 'SLM'];

    /**
     * Metode yang berlaku bila materialnya tidak ditemukan (mis. sudah dihapus).
     *
     * Mengikuti mekanisme masing-masing teknologi sebelum metode per material
     * ada: SLA (dahulu SLA Industries) selalu manual, sedangkan MJF dan SLM
     * selalu dihitung otomatis — sehingga penawaran lama tidak berubah.
     */
    public static function fallbackFor(?string $technologyCode): string
    {
        return SlaIndustries::is($technologyCode) ? PrintMaterial::PRICING_MANUAL : PrintMaterial::PRICING_AUTOMATIC;
    }

    /** Setiap teknologi memilih metode harga per materialnya. */
    public static function appliesTo(?string $technologyCode): bool
    {
        return $technologyCode !== null && trim($technologyCode) !== '';
    }

    /**
     * Harga model ini ditetapkan tim lewat Kalkulator Manual.
     *
     * Yang menentukan adalah `pricing_method` material Price List-nya;
     * material yang tidak ditemukan memakai fallbackFor().
     */
    public static function usesManualPricing(?string $technologyCode, ?string $material): bool
    {
        if (! self::appliesTo($technologyCode)) {
            return false;
        }

        $method = PrintMaterial::pricingMethodFor((string) $technologyCode, (string) $material)
            ?? self::fallbackFor($technologyCode);

        return $method !== PrintMaterial::PRICING_AUTOMATIC;
    }

    /**
     * Penanda Kalkulator Manual dari data material katalog (bentuk estimator).
     *
     * @param  array<string, mixed>  $material
     */
    public static function isManualMaterial(string $technologyCode, array $material): bool
    {
        return self::appliesTo($technologyCode)
            && ($material['pricing_method'] ?? self::fallbackFor($technologyCode)) !== PrintMaterial::PRICING_AUTOMATIC;
    }
}
