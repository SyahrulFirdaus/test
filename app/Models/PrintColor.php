<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Satu warna material yang tersedia, dikelola dari menu Color.
 *
 * Yang dilihat dan diubah pengelola hanya nama dan kode hexanya; `key` dibuat
 * sekali saat warnanya ditambah dan tidak pernah berubah lagi — nilai itulah
 * yang tersimpan pada penawaran (`quotation_items.material_color`) dan yang
 * disebut material sebagai warna yang diizinkannya.
 *
 * Pembacanya adalah App\Support\MaterialColor; halaman lain tidak membaca tabel
 * ini langsung.
 */
class PrintColor extends Model
{
    protected $fillable = [
        'key',
        'label',
        'hex',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /** Urutan tampil: mengikuti `position`, lalu nama bila posisinya sama. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('label');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $inner) use ($term) {
            $inner->where('label', 'like', "%{$term}%")
                ->orWhere('hex', 'like', "%{$term}%")
                ->orWhere('key', 'like', "%{$term}%");
        }));
    }

    /**
     * Kunci tetap yang dibuat dari nama warna.
     *
     * Dipanggil hanya saat warnanya ditambah. Nama yang sama dengan warna yang
     * sudah ada diberi akhiran angka, sehingga kunci tetap unik tanpa menolak
     * kiriman pengelola.
     */
    public static function makeKey(string $label): string
    {
        $base = Str::slug($label) ?: 'warna';
        $key = $base;
        $suffix = 1;

        while (static::where('key', $key)->exists()) {
            $key = $base.'-'.(++$suffix);
        }

        return $key;
    }

    /** Warna terang membutuhkan teks gelap di atasnya, dan sebaliknya. */
    public function getIsLightAttribute(): bool
    {
        [$r, $g, $b] = [
            hexdec(substr($this->hex, 1, 2)),
            hexdec(substr($this->hex, 3, 2)),
            hexdec(substr($this->hex, 5, 2)),
        ];

        // Luminansi persepsi (ITU-R BT.601).
        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150;
    }
}
