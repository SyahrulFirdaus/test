<?php

namespace App\Models;

use App\Support\SlaIndustries;
use Illuminate\Database\Eloquent\Builder;

/**
 * Material SLA pada Price List.
 *
 * Pandangan tersaring atas `print_materials`, sama seperti
 * App\Models\FdmMaterial — lihat catatan di sana.
 */
class SlaMaterial extends PrintMaterial
{
    /**
     * Sejak SLA lama digabung ke SLA (dahulu SLA Industries), material SLA
     * tinggal di bawah kode `SLAI`.
     */
    public const TECHNOLOGY = SlaIndustries::CODE;

    protected static function booted(): void
    {
        static::addGlobalScope(
            'technology',
            fn (Builder $query) => $query->where('print_technology_id', PrintTechnology::idFor(static::TECHNOLOGY)),
        );

        static::creating(function (self $material) {
            $material->print_technology_id ??= PrintTechnology::idFor(static::TECHNOLOGY);

            // Material SLA berharga per gram, jadi bawaannya Kalkulator Otomatis.
            $material->pricing_method ??= static::PRICING_AUTOMATIC;
        });
    }
}
