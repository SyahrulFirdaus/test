<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Satu warna milik satu material, diisi dari form Tambah/Ubah Material.
 *
 * Tiap material punya daftarnya sendiri: PLA Plus boleh menawarkan Putih,
 * Hitam, Merah, dan Biru sementara PETG hanya Putih, Hitam, dan Abu-abu.
 *
 * Yang tersimpan pada penawaran adalah `key`, bukan nama maupun hexanya (lihat
 * `quotation_items.material_color`), sehingga mengganti nama atau warna sebuah
 * baris TIDAK membuat penawaran lama kehilangan warnanya — yang berubah hanya
 * cara warna itu ditampilkan.
 */
class PrintMaterialColor extends Model
{
    protected $fillable = [
        'key',
        'name',
        'hex',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(PrintMaterial::class, 'print_material_id');
    }

    /** Urutan tampil: mengikuti `position`, lalu nama bila posisinya sama. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    /**
     * Kunci tetap sebuah warna, dibuat dari namanya.
     *
     * Warna yang sama persis — nama DAN hex yang sama — sengaja berbagi satu
     * kunci di seluruh material, supaya "Putih #FFFFFF" pada PLA dan pada PETG
     * benar-benar warna yang sama: satu kunci, satu tampilan, dan penawaran
     * yang materialnya diganti tidak kehilangan pilihannya.
     *
     * Nama yang sama dengan hex BERBEDA justru harus dipisahkan — "Putih"
     * #FFFFFF dan "Putih" #F5F5F5 adalah dua warna — jadi yang belakangan
     * diberi akhiran angka.
     *
     * @param  array<int, string>  $exclude  kunci yang sudah terpakai pada
     *                                       kiriman yang sedang disimpan dan
     *                                       karena itu belum ada di basis data
     */
    public static function makeKey(string $name, string $hex, array $exclude = []): string
    {
        $base = Str::slug($name) ?: 'warna';
        $hex = strtoupper($hex);

        for ($suffix = 1;; $suffix++) {
            $key = $suffix === 1 ? $base : $base.'-'.$suffix;

            if (in_array($key, $exclude, true)) {
                continue;
            }

            $taken = static::where('key', $key)
                ->get(['hex'])
                ->contains(fn (self $color) => strtoupper($color->hex) !== $hex);

            if (! $taken) {
                return $key;
            }
        }
    }
}
