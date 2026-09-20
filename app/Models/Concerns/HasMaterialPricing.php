<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Formula harga bersama `FdmMaterial` dan `SlaMaterial`.
 *
 * Admin hanya mengisi Harga Beli dan Harga Jual lewat halaman Price List —
 * Harga per gram, Pembulatan Harga, dan Harga/10 gram selalu dihitung dari
 * keduanya (bukan disimpan) supaya tidak pernah tidak sinkron. Formulanya
 * sudah dicocokkan persis dengan seluruh data harga NUSAMA3D:
 *
 *   harga per gram    = round(harga_beli / 800)   — asumsi berat bersih 800 g
 *   pembulatan harga  = ceil(harga_jual / 100) * 100
 *   harga per 10 gram = pembulatan_harga * 10
 *
 * `harga_per_10_gram` (dibagi 10) inilah yang dipakai Calculator sebagai
 * harga per gram material — bukan `harga_per_gram` mentah dari Harga Beli —
 * karena Calculator harus mengutip pelanggan dengan harga jual (bermarjin),
 * bukan harga modal.
 */
trait HasMaterialPricing
{
    public const SPOOL_WEIGHT_G = 800;

    /** Eloquent memanggil ini otomatis lewat initializeTraits() saat model dibuat. */
    protected function initializeHasMaterialPricing(): void
    {
        $this->fillable([
            'material',
            'brand',
            'purchase_price',
            'sale_price',
            'remark',
            'technical_spec',
        ]);
    }

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'technical_spec' => 'array',
        ];
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $inner) use ($term) {
            $inner->where('material', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%")
                ->orWhere('remark', 'like', "%{$term}%");
        }));
    }

    /**
     * Batas ukuran cetak material ini.
     *
     * Yang menentukan adalah MESIN tempat material dipakai: volume cetaknya
     * pada Price List → Machine Cost. Karena diturunkan, mengganti mesin
     * material atau mengubah volume cetak mesinnya langsung mengubah batas yang
     * tampil — tidak ada angka kedua yang perlu ikut disunting.
     *
     * Mesin adalah SATU-SATUNYA sumbernya. Material yang mesinnya belum
     * ditentukan tidak punya batas ukuran — `technical_spec.maxSize` sengaja
     * tidak lagi dipakai sebagai cadangan, karena angka terkurasi itu tidak
     * mewakili mesin mana pun yang benar-benar mengerjakan material tersebut.
     * Begitu pula volume cetak teknologinya: itu milik teknologi, bukan
     * material. Tidak ada mesin berarti null, dan tampilan tidak menampilkan
     * apa-apa.
     *
     * `build_volume` mesin sendiri sudah null bila salah satu sisinya kosong
     * atau nol, jadi nilai yang lolos ke sini selalu lengkap dan masuk akal.
     *
     * Dipakai bersama App\Models\PrintMaterial, satu-satunya pemakai trait
     * ini, jadi relasi `machine()` selalu tersedia.
     *
     * @return array{x: int, y: int, z: int}|null
     */
    protected function maxSize(): ?array
    {
        return $this->machine?->build_volume;
    }

    /** Harga modal per gram, dari Harga Beli dibagi asumsi berat spool. */
    public function getPricePerGramAttribute(): int
    {
        return (int) round(((float) $this->purchase_price) / self::SPOOL_WEIGHT_G);
    }

    /** Harga Jual dibulatkan ke atas kelipatan seratus rupiah. */
    public function getRoundedPriceAttribute(): int
    {
        return (int) (ceil(((float) $this->sale_price) / 100) * 100);
    }

    /** Harga jual per 10 gram — inilah yang dipakai Calculator. */
    public function getPricePer10GramAttribute(): int
    {
        return $this->rounded_price * 10;
    }

    /**
     * Bentuk yang selama ini dipakai `config('printing.technologies.X.materials.Y')`.
     *
     * @return array<string, mixed>
     */
    public function toEstimatorArray(): array
    {
        $spec = $this->technical_spec ?? [];

        return [
            'density' => $spec['density'] ?? 1.0,
            // Harga Calculator mengikuti harga jual (bermarjin), bukan harga modal.
            'price_per_gram' => $this->rounded_price,
            'colors' => $spec['colors'] ?? [],
            'description' => $spec['description'] ?? null,
            'characteristics' => $spec['characteristics'] ?? [],
            'pros' => $spec['pros'] ?? [],
            'cons' => $spec['cons'] ?? [],
            'max_size' => $this->maxSize(),
            'min_size' => $spec['minSize'] ?? null,
            'min_size_slender' => $spec['minSizeSlender'] ?? null,
            'pricing_method' => $this->pricing_method ?? 'manual',
        ];
    }
}
