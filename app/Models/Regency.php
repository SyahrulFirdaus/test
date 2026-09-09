<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kabupaten/kota, mis. "Kota Cimahi" (3277) di bawah Jawa Barat (32). */
class Regency extends RegionModel
{
    protected $table = 'regencies';

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class)->orderBy('name');
    }
}
