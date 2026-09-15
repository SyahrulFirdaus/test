<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Material FDM pada Price List.
 *
 * Sejak teknologi dapat ditambah Superadmin, seluruh material tinggal di satu
 * tabel (`print_materials`). Kelas ini menjadi pandangan tersaring atas tabel
 * itu — hanya baris milik teknologi FDM — sehingga halaman Price List FDM,
 * CRUD-nya, penghapusan massalnya, dan seluruh pengujian yang sudah ada tetap
 * berjalan tanpa perubahan.
 *
 * Teknologi yang ditambahkan kemudian tidak memerlukan kelas seperti ini:
 * materialnya diurus lewat App\Models\PrintMaterial.
 */
class FdmMaterial extends PrintMaterial
{
    public const TECHNOLOGY = 'FDM';

    protected static function booted(): void
    {
        static::addGlobalScope(
            'technology',
            fn (Builder $query) => $query->where('print_technology_id', PrintTechnology::idFor(static::TECHNOLOGY)),
        );

        // Baris baru otomatis melekat pada teknologinya, jadi pemanggil lama
        // yang hanya mengirim kolom komersial tetap bekerja.
        static::creating(function (self $material) {
            $material->print_technology_id ??= PrintTechnology::idFor(static::TECHNOLOGY);
        });
    }
}
