<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris Price List packaging (kardus, foam, bubble wrap).
 *
 * Belum dipakai menghitung biaya Calculator — untuk saat ini murni dikelola
 * di halaman Price List, menunggu aturan pemakaiannya ditentukan.
 */
class PackagingItem extends Model
{
    public const UNIT_FLAT = 'flat';

    public const UNIT_PER_CM = 'per_cm';

    protected $fillable = [
        'item',
        'ukuran',
        'dimensi',
        'price',
        'price_unit',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $inner) use ($term) {
            $inner->where('item', 'like', "%{$term}%")
                ->orWhere('ukuran', 'like', "%{$term}%")
                ->orWhere('dimensi', 'like', "%{$term}%");
        }));
    }

    /** "Rp2.000" untuk harga flat, "Rp2.000/cm" untuk harga per sentimeter. */
    public function getFormattedPriceAttribute(): string
    {
        $formatted = 'Rp'.number_format((float) $this->price, 0, ',', '.');

        return $this->price_unit === self::UNIT_PER_CM ? $formatted.'/cm' : $formatted;
    }

    /** Nama singkat baris ini, mis. "Kardus M" atau "Bubble". */
    public function getLabelAttribute(): string
    {
        return trim($this->item.' '.($this->ukuran === '-' ? '' : (string) $this->ukuran));
    }

    /**
     * Ukuran kemasan dalam sentimeter, terurut dari sisi terpanjang.
     *
     * `dimensi` ditulis bebas oleh admin ("30 x 15 x 15 cm"), jadi yang diambil
     * cukup tiga angka pertama di dalamnya. Baris yang tidak menyebut tiga sisi
     * — foam dan bubble wrap yang hanya bertuliskan tebalnya — mengembalikan
     * null karena tidak dapat dipakai memeriksa apakah sebuah model muat.
     *
     * @return array<int, float>|null
     */
    public function getDimensionsCmAttribute(): ?array
    {
        preg_match_all('/\d+(?:[.,]\d+)?/', (string) $this->dimensi, $matches);

        if (count($matches[0]) < 3) {
            return null;
        }

        $sides = array_map(
            fn (string $number) => (float) str_replace(',', '.', $number),
            array_slice($matches[0], 0, 3),
        );

        rsort($sides);

        return $sides;
    }
}
