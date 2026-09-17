<?php

namespace App\Models;

use App\Support\BasicFee;
use App\Support\SlaIndustries;
use Illuminate\Database\Eloquent\Model;

/**
 * Rumus Harga Otomatis — parameter Harga Jual.
 *
 * Hanya SATU baris yang berlaku, berkode GENERAL (`UMUM`), dan dipakai
 * Kalkulator Otomatis seluruh teknologi (lihat App\Services\SellingPriceEstimator).
 * Baris per teknologi yang lama masih ada di tabel tetapi tidak dibaca lagi.
 *
 * Accessor di bawah menjalankan simulasinya untuk halaman Rumus Harga Otomatis
 * pada Price List.
 *
 * Pola perhitungannya:
 *   Harga Operasional Mesin = Machine Time x Machine Cost
 *   HPP                     = Operasional Mesin + (Jumlah Material x Harga Material)
 *   Risk Cost               = HPP x Risiko Gagal Print (%)
 *   Subtotal HPP + Risk     = HPP + Risk Cost
 *   Subtotal                = Subtotal HPP + Risk + Packaging + Overtime
 *   Profit                  = Subtotal x Profit (%)
 *   Basic Fee               = tarif menurut sisi terpanjang 3D object
 *   Harga Jual              = Subtotal + Profit + Basic Fee
 *
 * Basic Fee tidak disimpan sebagai angka: yang disimpan `object_size_mm`, dan
 * tarifnya selalu diturunkan App\Support\BasicFee dari ukuran itu — sama
 * seperti pada penawaran sungguhan, hanya saja ukurannya diketik admin karena
 * tab Harga tidak punya model 3D untuk diukur.
 */
class PricingFormula extends Model
{
    /** Kode baris Rumus Harga Otomatis yang berlaku umum. */
    public const GENERAL = 'UMUM';

    protected $fillable = [
        'technology',
        'machine_time_hours',
        'machine_cost',
        'material_qty_g',
        'material_price_per_g',
        'risk_percent',
        'packaging_cost',
        'overtime_cost',
        'profit_percent',
        'object_size_mm',
    ];

    protected function casts(): array
    {
        return [
            'machine_time_hours' => 'decimal:2',
            'machine_cost' => 'decimal:2',
            'material_qty_g' => 'decimal:2',
            'material_price_per_g' => 'decimal:2',
            'risk_percent' => 'decimal:2',
            'packaging_cost' => 'decimal:2',
            'overtime_cost' => 'decimal:2',
            'profit_percent' => 'decimal:2',
            'object_size_mm' => 'decimal:2',
        ];
    }

    /**
     * Rumus Harga Otomatis yang berlaku umum.
     *
     * Dibuat bila belum ada — mis. pada basis data yang dibangun sebelum
     * migrasinya — dengan nilai baris FDM sebagai titik awal.
     */
    public static function general(): self
    {
        return static::firstOrCreate(
            ['technology' => self::GENERAL],
            static::where('technology', 'FDM')->first()?->only([
                'machine_time_hours', 'machine_cost', 'material_qty_g', 'material_price_per_g',
                'risk_percent', 'packaging_cost', 'overtime_cost', 'profit_percent', 'object_size_mm',
            ]) ?? [
                'machine_time_hours' => 2, 'machine_cost' => 0, 'material_qty_g' => 300, 'material_price_per_g' => 500,
                'risk_percent' => 25, 'packaging_cost' => 5000, 'overtime_cost' => 0, 'profit_percent' => 50, 'object_size_mm' => 100,
            ],
        );
    }

    /**
     * Daftar teknologi yang punya baris rumus.
     *
     * Dahulu tetap empat; sejak teknologi dikelola Superadmin, daftarnya
     * mengikuti App\Models\PrintTechnology. Baris rumusnya dibuat otomatis saat
     * teknologi baru ditambahkan — lihat PrintTechnology::booted().
     *
     * @return array<int, string>
     */
    public static function technologies(): array
    {
        // SLA Industries DIKECUALIKAN. Teknologi itu tidak dicetak sendiri —
        // partnya dipesan ke vendor — sehingga pola HPP/Risk/Packaging/Profit
        // di kelas ini tidak berlaku baginya sama sekali. Rumusnya tinggal di
        // App\Models\SlaIndustriesFormula, dan ia memang tidak punya baris di
        // tabel ini. Kalau tetap didaftarkan, tab Harga akan mencari baris yang
        // tidak akan pernah ada.
        return array_values(array_filter(
            PrintTechnology::codes(),
            fn (string $code) => ! SlaIndustries::is($code),
        ));
    }

    /**
     * Nilai awal yang masuk akal bagi teknologi yang baru ditambahkan.
     *
     * Machine Cost diambil dari tarif mesin teknologinya sendiri supaya
     * simulasi tab Harga tidak dimulai dari nol; sisanya angka lazim yang
     * bebas disunting Superadmin kapan saja.
     *
     * @return array<string, mixed>
     */
    public static function defaultsFor(PrintTechnology $technology): array
    {
        return [
            'machine_time_hours' => max(1, round($technology->setup_hours * 4, 2)),
            'machine_cost' => $technology->machine_rate_per_hour,
            'material_qty_g' => 300,
            'material_price_per_g' => 500,
            'risk_percent' => 25,
            'packaging_cost' => 5000,
            'overtime_cost' => 0,
            'profit_percent' => 50,
            'object_size_mm' => 100,
        ];
    }

    /* --------------------------------------------------- nilai turunan --- */

    /** Harga Operasional Mesin = Machine Time x Machine Cost. */
    public function getMachineOperationalCostAttribute(): float
    {
        return round((float) $this->machine_time_hours * (float) $this->machine_cost, 2);
    }

    /** Material = Jumlah Material x Harga Material. */
    public function getMaterialCostAttribute(): float
    {
        return round((float) $this->material_qty_g * (float) $this->material_price_per_g, 2);
    }

    public function getHppAttribute(): float
    {
        return round($this->machine_operational_cost + $this->material_cost, 2);
    }

    public function getRiskCostAttribute(): float
    {
        return round($this->hpp * ((float) $this->risk_percent / 100), 2);
    }

    public function getSubtotalHppRiskAttribute(): float
    {
        return round($this->hpp + $this->risk_cost, 2);
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->subtotal_hpp_risk + (float) $this->packaging_cost + (float) $this->overtime_cost, 2);
    }

    public function getProfitAttribute(): float
    {
        return round($this->subtotal * ((float) $this->profit_percent / 100), 2);
    }

    /**
     * Basic Fee diturunkan dari ukuran object, bukan disimpan.
     *
     * Dengan begitu tingkatannya selalu mengikuti daftar yang sama dengan
     * penawaran sungguhan — lihat App\Support\BasicFee.
     */
    public function getBasicFeeAttribute(): float
    {
        return BasicFee::amount((float) $this->object_size_mm);
    }

    public function getBasicFeeLabelAttribute(): string
    {
        return BasicFee::label((float) $this->object_size_mm);
    }

    public function getSellingPriceAttribute(): float
    {
        return round($this->subtotal + $this->profit + $this->basic_fee, 2);
    }

    /**
     * Rincian siap tampil untuk tabel "Rincian Harga Jual" pada tab Harga.
     *
     * Kolom rumus memuat parameter yang benar-benar dipakai supaya admin dapat
     * menelusuri asal angkanya tanpa membuka basis data.
     *
     * @return array<int, array{label: string, formula: string, value: float, highlight?: bool}>
     */
    public function breakdown(): array
    {
        $number = fn (float $value, int $decimals = 0) => number_format($value, $decimals, ',', '.');
        $rupiah = fn (float $value) => 'Rp'.$number($value);

        return [
            [
                'label' => 'Material',
                'formula' => $number((float) $this->material_qty_g, 2).' gr × '.$rupiah((float) $this->material_price_per_g).'/gr',
                'value' => $this->material_cost,
            ],
            [
                'label' => 'Operasional Mesin',
                'formula' => $number((float) $this->machine_time_hours, 2).' jam × '.$rupiah((float) $this->machine_cost).'/jam',
                'value' => $this->machine_operational_cost,
            ],
            [
                'label' => 'HPP',
                'formula' => 'Material + Operasional Mesin',
                'value' => $this->hpp,
            ],
            [
                'label' => 'Risk Cost',
                'formula' => 'HPP × Risk '.$number((float) $this->risk_percent, 0).'%',
                'value' => $this->risk_cost,
            ],
            [
                'label' => 'Packaging',
                'formula' => 'Biaya packaging',
                'value' => (float) $this->packaging_cost,
            ],
            [
                'label' => 'Overtime',
                'formula' => 'Jika ada',
                'value' => (float) $this->overtime_cost,
            ],
            [
                'label' => 'Subtotal',
                'formula' => 'HPP + Risk Cost + Packaging + Overtime',
                'value' => $this->subtotal,
            ],
            [
                'label' => 'Profit',
                'formula' => 'Subtotal × Profit '.$number((float) $this->profit_percent, 0).'%',
                'value' => $this->profit,
            ],
            [
                'label' => 'Basic Fee',
                'formula' => 'Ukuran object '.$number((float) $this->object_size_mm, 1).' mm · '.$this->basic_fee_label,
                'value' => $this->basic_fee,
            ],
            [
                'label' => 'Harga Jual',
                'formula' => 'Subtotal + Profit + Basic Fee',
                'value' => $this->selling_price,
                'highlight' => true,
            ],
        ];
    }
}
