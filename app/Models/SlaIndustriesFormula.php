<?php

namespace App\Models;

use App\Models\Concerns\CalculatesSlaIndustriesPrice;
use Illuminate\Database\Eloquent\Model;

/**
 * Parameter bawaan Rumus Harga SLA Industries — satu baris untuk seluruh sistem.
 *
 * Disunting Superadmin pada tab SLA Industries di Price List. Perannya hanya
 * sebagai TITIK AWAL: saat Admin membuka form perhitungan sebuah model yang
 * belum pernah dihitung, isian formnya diambil dari sini supaya kurs dan margin
 * yang berlaku tidak perlu diketik ulang tiap penawaran.
 *
 * Mengubah nilai di sini TIDAK menggeser harga penawaran yang sudah ditetapkan:
 * begitu Admin menyimpan, parameternya tersalin ke App\Models\SlaIndustriesQuote
 * milik model itu sendiri.
 */
class SlaIndustriesFormula extends Model
{
    use CalculatesSlaIndustriesPrice;

    protected $table = 'sla_industries_formulas';

    protected $fillable = [
        'usd_rate',
        'jlc_price_usd',
        'jlc_shipping_usd',
        'customs_idr',
        'margin_percent',
    ];

    protected function casts(): array
    {
        return [
            'usd_rate' => 'decimal:2',
            'jlc_price_usd' => 'decimal:2',
            'jlc_shipping_usd' => 'decimal:2',
            'customs_idr' => 'decimal:2',
            'margin_percent' => 'decimal:2',
        ];
    }

    /**
     * Baris yang berlaku, dibuat bila belum ada.
     *
     * Migrasinya sudah menyisipkan satu baris; `firstOrCreate` di sini menjaga
     * halaman Price List tetap terbuka pada pemasangan yang barisnya terlanjur
     * terhapus — bukan menampilkan galat karena satu baris master hilang.
     */
    public static function current(): self
    {
        return static::query()->oldest('id')->first() ?? static::create([]);
    }
}
