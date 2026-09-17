<?php

namespace App\Models;

use App\Models\Concerns\CalculatesSlaIndustriesPrice;
use App\Support\SlaIndustries;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kuotasi JLC satu model SLA Industries, diisi Admin/Superadmin.
 *
 * Inilah yang menetapkan harga model SLA Industries. Selama barisnya belum ada,
 * modelnya berstatus "Menunggu Perhitungan" dan pelanggan tidak melihat angka
 * apa pun — lihat App\Models\QuotationItem::awaitsPricing().
 *
 * Yang tersimpan hanya parameter; Final Price dan seluruh nilai antaranya
 * diturunkan App\Support\SlaIndustries::compute() lewat trait di bawah.
 */
class SlaIndustriesQuote extends Model
{
    use CalculatesSlaIndustriesPrice;

    protected $table = 'sla_industries_quotes';

    protected $fillable = [
        'quotation_item_id',
        'product_name',
        'usd_rate',
        'usd_rate_source',
        'usd_rate_published_at',
        'jlc_price_usd',
        'jlc_shipping_usd',
        'customs_idr',
        'margin_percent',
        'calculated_by',
    ];

    protected function casts(): array
    {
        return [
            'usd_rate' => 'decimal:2',
            'usd_rate_published_at' => 'datetime',
            'jlc_price_usd' => 'decimal:2',
            'jlc_shipping_usd' => 'decimal:2',
            'customs_idr' => 'decimal:2',
            'margin_percent' => 'decimal:2',
        ];
    }

    /**
     * Kurs yang DIPAKAI kuotasi ini, bukan kurs hari ini.
     *
     * Membuka kembali penawaran yang harganya sudah ditetapkan tidak boleh
     * menarik kurs terbaru: Final Price yang sudah ditawarkan ke pelanggan
     * dihitung dengan kurs tertentu, dan itulah yang harus tetap terbaca.
     *
     * Bentuknya sengaja sama dengan App\Services\UsdRate::current() supaya
     * tampilan formulirnya tidak perlu tahu mana yang hidup dan mana yang beku.
     *
     * @return array<string, mixed>
     */
    public function usdRateInfo(): array
    {
        return [
            'rate' => (float) $this->usd_rate,
            'source' => $this->usd_rate_source,

            // "frozen" membedakannya dari daily/intraday: kurs ini tidak pernah
            // disegarkan lagi, karena harganya sudah ditetapkan.
            'cadence' => 'frozen',
            'published_at' => $this->usd_rate_published_at?->toIso8601String(),
            'fetched_at' => $this->updated_at?->toIso8601String(),
            'stale' => false,
            'error' => null,
            'refresh_seconds' => 0,
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(QuotationItem::class, 'quotation_item_id');
    }

    /** Petugas yang terakhir menetapkan harganya; kosong bila akunnya dihapus. */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /**
     * Rincian yang disimpan pada `quotation_items.cost_breakdown`.
     *
     * Bentuknya sengaja memakai kunci `selling_price` dan `total` seperti
     * rincian teknologi lain, supaya penjumlahan antar model pada
     * App\Models\QuotationRequest::summaryFrom() tetap berjalan apa adanya
     * untuk penawaran yang mencampur SLA Industries dengan teknologi lain.
     *
     * @return array<string, mixed>
     */
    public function toCostBreakdown(): array
    {
        $values = $this->computed();

        return [
            'pricing_source' => 'sla_industries',
            'product_name' => $this->product_name,

            // Tiga kunci pertama dibaca ringkasan accordion "Detail Perhitungan
            // Harga" pada halaman admin, sama seperti rincian teknologi lain.
            // `manual_pricing` yang membuat tabel komponennya diganti
            // keterangan — lihat App\Services\SellingPriceEstimator::rows().
            'technology' => $this->item?->technology ?? SlaIndustries::CODE,
            'material_source' => $this->item?->material,
            'quantity' => (int) ($this->item?->quantity ?? 1),
            'manual_pricing' => true,

            'usd_rate' => $values['usd_rate'],
            'usd_rate_source' => $this->usd_rate_source,
            'jlc_price_usd' => $values['jlc_price_usd'],
            'jlc_price_idr' => $values['jlc_price_idr'],
            'jlc_shipping_usd' => $values['jlc_shipping_usd'],
            'jlc_shipping_idr' => $values['jlc_shipping_idr'],
            'total_jlc_usd' => $values['total_jlc_usd'],
            'total_jlc_idr' => $values['total_jlc_idr'],
            'customs_idr' => $values['customs_idr'],
            'margin_percent' => $values['margin_percent'],

            'hpp' => $values['hpp'],
            'profit' => $values['profit'],

            // Dibaca sebagai harga model ini oleh ringkasan penawaran.
            'selling_price' => $values['final_price'],
            'total' => $values['final_price'],
        ];
    }
}
