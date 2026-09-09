<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kecamatan, mis. "Cimahi Selatan" (327701) di bawah Kota Cimahi (3277). */
class District extends RegionModel
{
    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class)->orderBy('name');
    }
}
