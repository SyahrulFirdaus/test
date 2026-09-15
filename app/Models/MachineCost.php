<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris Price List biaya mesin.
 *
 * Belum dipakai menghitung biaya Calculator (tarif mesin yang berjalan masih
 * `machine_rate_per_hour` per printer di config/printing.php) — untuk saat
 * ini murni dikelola di halaman Price List, menunggu aturan pemakaiannya
 * ditentukan (mis. pemetaan printer ke baris Machine Cost).
 *
 * Formula (dicocokkan persis dengan data NUSAMA3D):
 *   listrik/jam   = watt_kwh x harga_listrik
 *   machine cost  = round((listrik/jam + depresiasi) x 1,5)
 *   pembulatan    = ceil(machine_cost / 1000) x 1000
 */
class MachineCost extends Model
{
    protected $fillable = [
        'mesin',
        'watt_kwh',
        'harga_listrik',
        'depresiasi',
    ];

    protected function casts(): array
    {
        return [
            'watt_kwh' => 'decimal:3',
            'harga_listrik' => 'decimal:2',
            'depresiasi' => 'decimal:2',
        ];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where('mesin', 'like', "%{$term}%"));
    }

    /** Biaya listrik per jam pemakaian. */
    public function getElectricityPerHourAttribute(): float
    {
        return ((float) $this->watt_kwh) * ((float) $this->harga_listrik);
    }

    /** Biaya mesin per jam: listrik + depresiasi, dikali margin 1,5x. */
    public function getMachineCostAttribute(): int
    {
        return (int) round(($this->electricity_per_hour + (float) $this->depresiasi) * 1.5);
    }

    /** Biaya mesin dibulatkan ke atas kelipatan seribu rupiah. */
    public function getRoundedMachineCostAttribute(): int
    {
        return (int) (ceil($this->machine_cost / 1000) * 1000);
    }

    /**
     * Seberapa cocok baris ini dengan nama printer yang dipilih pelanggan.
     *
     * Nama mesin di sini ditulis admin sendiri ("Ender 3 V2") sedangkan nama
     * printer pada penawaran berasal dari config/printing.php ("Creality Ender
     * 3"), jadi keduanya hampir tidak pernah sama persis. Yang dibandingkan
     * adalah jumlah kata yang sama; App\Services\SellingPriceEstimator memakai
     * baris dengan nilai tertinggi dan menuntut minimal dua kata cocok supaya
     * mesin yang belum terdaftar tidak tersangkut ke baris mana pun.
     */
    public function printerMatchScore(?string $printerName): int
    {
        $tokens = self::tokenize($printerName);

        if ($tokens === []) {
            return 0;
        }

        return count(array_intersect($tokens, self::tokenize($this->mesin)));
    }

    /** @return array<int, string> kata-kata pembentuk nama mesin, tanpa duplikat */
    private static function tokenize(?string $name): array
    {
        return array_values(array_unique(array_filter(
            preg_split('/[^a-z0-9]+/', strtolower((string) $name)) ?: []
        )));
    }
}
