<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Kelurahan/desa, mis. "Melong" (3277011001) di bawah Cimahi Selatan (327701). */
class Village extends RegionModel
{
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
