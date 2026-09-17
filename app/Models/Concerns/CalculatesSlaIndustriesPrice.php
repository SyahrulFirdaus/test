<?php

namespace App\Models\Concerns;

use App\Support\SlaIndustries;

/**
 * Nilai turunan Rumus Harga SLA Industries.
 *
 * Dipakai bersama App\Models\SlaIndustriesFormula (parameter bawaan di Price
 * List) dan App\Models\SlaIndustriesQuote (kuotasi satu model pada penawaran).
 * Keduanya menyimpan parameter yang sama persis, jadi rumusnya ditulis sekali
 * di sini — dan sebenarnya bahkan tidak di sini: seluruh aritmetikanya ada di
 * App\Support\SlaIndustries::compute(), supaya perhitungan di layar Superadmin,
 * di layar Admin, dan di server benar-benar satu sumber.
 *
 * Tidak satu pun nilai ini disimpan sebagai kolom. Mengubah Margin Profit
 * langsung mengubah Profit dan Final Price tanpa ada angka lama yang tertinggal.
 */
trait CalculatesSlaIndustriesPrice
{
    /** @var array<string, float>|null */
    private ?array $computed = null;

    /** @return array<string, float> */
    public function computed(): array
    {
        return $this->computed ??= SlaIndustries::compute(SlaIndustries::paramsFrom([
            'usd_rate' => (float) $this->usd_rate,
            'jlc_price_usd' => (float) $this->jlc_price_usd,
            'jlc_shipping_usd' => (float) $this->jlc_shipping_usd,
            'customs_idr' => (float) $this->customs_idr,
            'margin_percent' => (float) $this->margin_percent,
        ]));
    }

    /** Parameter berubah, jadi hasil turunannya harus dihitung ulang. */
    public function forgetComputed(): void
    {
        $this->computed = null;
    }

    public function getJlcPriceIdrAttribute(): float
    {
        return $this->computed()['jlc_price_idr'];
    }

    public function getJlcShippingIdrAttribute(): float
    {
        return $this->computed()['jlc_shipping_idr'];
    }

    public function getTotalJlcUsdAttribute(): float
    {
        return $this->computed()['total_jlc_usd'];
    }

    public function getTotalJlcIdrAttribute(): float
    {
        return $this->computed()['total_jlc_idr'];
    }

    public function getHppAttribute(): float
    {
        return $this->computed()['hpp'];
    }

    public function getProfitAttribute(): float
    {
        return $this->computed()['profit'];
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->computed()['final_price'];
    }

    /**
     * Rincian siap tampil, urut seperti tabel pada spesifikasinya.
     *
     * Kolom `dollar` bernilai null untuk komponen yang memang hanya rupiah —
     * DHL Beacukai, HPP, Profit, Final Price — sehingga tampilan cukup
     * menuliskan "-" tanpa perlu tahu komponen mana yang punya sisi dollar.
     *
     * @return array<int, array{label: string, dollar: float|null, rupiah: float|null, percent?: float, remark: string, auto?: bool, highlight?: bool}>
     */
    public function breakdown(): array
    {
        $values = $this->computed();

        return [
            [
                'label' => 'Dollar Hari Ini',
                'dollar' => 1.0,
                'rupiah' => $values['usd_rate'],
                'remark' => 'Kurs yang dipakai mengubah kuotasi JLC ke rupiah.',
            ],
            [
                'label' => 'Harga JLC',
                'dollar' => $values['jlc_price_usd'],
                'rupiah' => $values['jlc_price_idr'],
                'remark' => 'Isi harga dari JLC di kotak kuning.',
            ],
            [
                'label' => 'Ongkir JLC',
                'dollar' => $values['jlc_shipping_usd'],
                'rupiah' => $values['jlc_shipping_idr'],
                'remark' => 'Isi harga ongkir dari JLC di kotak kuning.',
            ],
            [
                'label' => 'Total Bayar ke JLC',
                'dollar' => $values['total_jlc_usd'],
                'rupiah' => $values['total_jlc_idr'],
                'remark' => 'Harga JLC + Ongkir JLC',
                'auto' => true,
            ],
            [
                'label' => 'DHL Beacukai (Pajak)',
                'dollar' => null,
                'rupiah' => $values['customs_idr'],
                'remark' => 'Hitung melalui website Bea Cukai.',
            ],
            [
                'label' => 'Margin Profit',
                'dollar' => null,
                'rupiah' => null,
                'percent' => $values['margin_percent'],
                'remark' => 'Isi margin profit '.SlaIndustries::MIN_MARGIN.'%–'.SlaIndustries::MAX_MARGIN.'%.',
            ],
            [
                'label' => 'HPP',
                'dollar' => null,
                'rupiah' => $values['hpp'],
                'remark' => 'Total Bayar ke JLC + DHL Beacukai',
                'auto' => true,
            ],
            [
                'label' => 'Profit',
                'dollar' => null,
                'rupiah' => $values['profit'],
                'remark' => 'HPP × Margin Profit',
                'auto' => true,
            ],
            [
                'label' => 'Final Price',
                'dollar' => null,
                'rupiah' => $values['final_price'],
                'remark' => 'HPP + Profit',
                'auto' => true,
                'highlight' => true,
            ],
        ];
    }
}
