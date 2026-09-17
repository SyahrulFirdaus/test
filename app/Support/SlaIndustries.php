<?php

namespace App\Support;


/**
 * Teknologi SLA Industries dan rumus harganya.
 *
 * SLA Industries TIDAK dicetak sendiri: partnya dipesan ke vendor luar (JLC),
 * lalu dijual kembali. Karena itu harganya tidak dapat diturunkan dari berat,
 * waktu mesin, dan material seperti FDM/SLA/MJF/SLM — yang menentukan justru
 * kuotasi vendor, ongkos kirim, bea masuk, dan margin.
 *
 * Konsekuensinya dua hal yang membedakannya dari teknologi lain:
 *
 *  1. Pelanggan TIDAK melihat harga saat menyusun penawaran. Calculator tidak
 *     punya bahan untuk menghitungnya, jadi harganya ditahan sampai tim mengisi
 *     kuotasi JLC-nya.
 *  2. Harga akhirnya ditetapkan Admin/Superadmin per model pada Detail
 *     Penawaran lewat App\Models\SlaIndustriesQuote.
 *
 * Seluruh angka rumus berasal dari basis data — parameter bawaan pada
 * App\Models\SlaIndustriesFormula (Price List) dan kuotasi per model pada
 * `sla_industries_quotes`. Tidak ada satu pun nilai contoh yang ditulis tetap
 * di sini; `compute()` hanya menjalankan urutan perhitungannya.
 *
 * Rumusnya:
 *   Harga JLC (Rp)      = Harga JLC ($)   × Dollar Hari Ini
 *   Ongkir JLC (Rp)     = Ongkir JLC ($)  × Dollar Hari Ini
 *   Total Bayar ke JLC  = Harga JLC + Ongkir JLC
 *   HPP                 = Total Bayar ke JLC + DHL Beacukai
 *   Profit              = HPP × Margin Profit
 *   Final Price         = HPP + Profit
 */
class SlaIndustries
{
    /**
     * Kode teknologinya pada `print_technologies.code`.
     *
     * Ikut tersimpan pada `quotation_items.technology` — kolom varchar(10) —
     * sehingga tidak boleh diubah setelah ada penawaran yang memakainya.
     */
    public const CODE = 'SLAI';

    /**
     * Nama yang dilihat pelanggan maupun tim. Dahulu "SLA Industries"; sejak
     * teknologi SLA lama digabung ke sini, teknologinya cukup bernama "SLA".
     */
    public const NAME = 'SLA';

    /** Batas Margin Profit yang boleh dipakai, dalam persen. */
    public const MIN_MARGIN = 30;

    public const MAX_MARGIN = 50;

    /** Tautan rujukan untuk menghitung sendiri nilai DHL Beacukai. */
    public const CUSTOMS_CALCULATOR_URL = 'https://www.beacukai.go.id/faq/kalkulator-pabean.html';

    public static function is(?string $technologyCode): bool
    {
        return $technologyCode !== null && strtoupper(trim($technologyCode)) === self::CODE;
    }

    /**
     * Harga model ini ditetapkan tim lewat Kalkulator Manual (kuotasi JLC).
     *
     * Yang menentukan adalah `pricing_method` material Price List-nya.
     * Material yang tidak ditemukan — misalnya sudah dihapus — dianggap
     * manual: itulah mekanisme SLA Industries sebelum metode per material ada,
     * dan menahan harga lebih aman daripada menebaknya.
     */
    public static function usesManualPricing(?string $technologyCode, ?string $material): bool
    {
        return self::is($technologyCode) && PricingMethod::usesManualPricing($technologyCode, $material);
    }

    /** Aturan validasi Margin Profit, dipakai bersama seluruh form yang mengisinya. */
    public static function marginRule(): string
    {
        return 'numeric|min:'.self::MIN_MARGIN.'|max:'.self::MAX_MARGIN;
    }

    public static function marginMessage(): string
    {
        return 'Margin Profit hanya boleh '.self::MIN_MARGIN.'% sampai '.self::MAX_MARGIN.'%.';
    }

    /**
     * Jalankan rumusnya atas parameter yang diberikan.
     *
     * Nilai rupiah dibulatkan KE ATAS ke rupiah penuh pada setiap langkah, dan
     * langkah berikutnya memakai angka yang sudah dibulatkan itu — bukan
     * pecahannya. Dengan begitu angka yang tampil di layar benar-benar
     * berjumlah seperti yang terbaca: Total Bayar ke JLC persis sama dengan
     * Harga JLC + Ongkir JLC sebagaimana keduanya tertulis, dan Final Price
     * persis sama dengan HPP + Profit.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, float>
     */
    public static function compute(array $params): array
    {
        $usdRate = max(0.0, (float) ($params['usd_rate'] ?? 0));
        $jlcPriceUsd = max(0.0, (float) ($params['jlc_price_usd'] ?? 0));
        $jlcShippingUsd = max(0.0, (float) ($params['jlc_shipping_usd'] ?? 0));
        $customsIdr = max(0.0, (float) ($params['customs_idr'] ?? 0));
        $marginPercent = (float) ($params['margin_percent'] ?? 0);

        $rupiah = fn (float $value) => (float) ceil($value);

        $jlcPriceIdr = $rupiah($jlcPriceUsd * $usdRate);
        $jlcShippingIdr = $rupiah($jlcShippingUsd * $usdRate);

        $totalJlcUsd = round($jlcPriceUsd + $jlcShippingUsd, 2);
        $totalJlcIdr = $jlcPriceIdr + $jlcShippingIdr;

        $hpp = $totalJlcIdr + $rupiah($customsIdr);
        $profit = $rupiah($hpp * ($marginPercent / 100));

        return [
            'usd_rate' => $usdRate,
            'jlc_price_usd' => $jlcPriceUsd,
            'jlc_price_idr' => $jlcPriceIdr,
            'jlc_shipping_usd' => $jlcShippingUsd,
            'jlc_shipping_idr' => $jlcShippingIdr,
            'total_jlc_usd' => $totalJlcUsd,
            'total_jlc_idr' => $totalJlcIdr,
            'customs_idr' => $rupiah($customsIdr),
            'margin_percent' => $marginPercent,
            'hpp' => $hpp,
            'profit' => $profit,
            'final_price' => $hpp + $profit,
        ];
    }

    /**
     * Parameter yang dibaca `compute()`, disaring dari sekumpulan atribut.
     *
     * Dipakai model maupun controller supaya keduanya menyodorkan kunci yang
     * sama persis dan tidak ada parameter yang diam-diam terlewat.
     *
     * @param  array<string, mixed>|object  $source
     * @return array<string, mixed>
     */
    public static function paramsFrom(array|object $source): array
    {
        $get = fn (string $key) => is_array($source) ? ($source[$key] ?? null) : ($source->{$key} ?? null);

        return [
            'usd_rate' => $get('usd_rate'),
            'jlc_price_usd' => $get('jlc_price_usd'),
            'jlc_shipping_usd' => $get('jlc_shipping_usd'),
            'customs_idr' => $get('customs_idr'),
            'margin_percent' => $get('margin_percent'),
        ];
    }
}
