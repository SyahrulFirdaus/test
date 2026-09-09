<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Dasar bersama keempat tabel wilayah.
 *
 * Kunci utamanya adalah kode wilayah resmi tanpa titik, jadi bukan angka
 * berurut yang dibuat basis data — `incrementing` dimatikan agar Eloquent tidak
 * mencoba menghasilkan sendiri nilainya.
 */
abstract class RegionModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['id', 'code', 'name', 'province_id', 'regency_id', 'district_id'];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /** Bentuk ringkas untuk dropdown di browser. */
    public function toOption(): array
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
