<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Provinsi — tingkat teratas wilayah administratif.
 *
 * Id-nya adalah kode wilayah resmi tanpa titik (mis. 32 untuk Jawa Barat),
 * bukan angka berurut, sehingga tetap sama walaupun daftarnya diperbarui.
 */
class Province extends RegionModel
{
    public function regencies(): HasMany
    {
        return $this->hasMany(Regency::class)->orderBy('name');
    }
}
