<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Technology extends Model
{
    protected $fillable = [
        'slug',
        'code',
        'name',
        'tagline',
        'description',
        'image',
        'advantages',
        'applications',
        'materials',
        'specs',
        'accent_color',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'advantages' => 'array',
            'applications' => 'array',
            'materials' => 'array',
            'specs' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Nama lengkap beserta singkatannya, mis. "FDM — Fused Deposition Modeling". */
    public function getFullNameAttribute(): string
    {
        return "{$this->code} — {$this->name}";
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
