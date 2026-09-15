<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Material SLA pada Price List.
 *
 * Pandangan tersaring atas `print_materials`, sama seperti
 * App\Models\FdmMaterial — lihat catatan di sana.
 */
class SlaMaterial extends PrintMaterial
{
    public const TECHNOLOGY = 'SLA';

    protected static function booted(): void
    {
        static::addGlobalScope(
            'technology',
            fn (Builder $query) => $query->where('print_technology_id', PrintTechnology::idFor(static::TECHNOLOGY)),
        );

        static::creating(function (self $material) {
            $material->print_technology_id ??= PrintTechnology::idFor(static::TECHNOLOGY);
        });
    }
}
