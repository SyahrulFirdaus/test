<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'role',
        'rating',
        'quote',
        'source',
        'reviewed_label',
        'reviewed_at',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'reviewed_at' => 'date',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Waktu ulasan untuk ditampilkan: relatif dari tanggalnya ("2 minggu
     * yang lalu"), atau teks lama `reviewed_label` bila tanggalnya belum diisi.
     */
    public function getReviewedRelativeAttribute(): ?string
    {
        if ($this->reviewed_at !== null) {
            $date = $this->reviewed_at->copy()->startOfDay();
            $today = now()->startOfDay();

            if ($date->greaterThanOrEqualTo($today)) {
                return 'hari ini';
            }

            if ($date->diffInDays($today) < 1.5) {
                return 'kemarin';
            }

            // "2 minggu lalu", "3 bulan lalu", "1 tahun lalu".
            return str_replace(' yang lalu', ' lalu', $date->locale('id')->diffForHumans($today, ['syntax' => \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW, 'parts' => 1]));
        }

        return $this->reviewed_label ?: null;
    }

    /**
     * Inisial untuk avatar. Foto profil pengulas tidak disalin ke website,
     * jadi avatarnya dibentuk dari huruf awal namanya.
     */
    public function getInitialsAttribute(): string
    {
        return Str::of($this->name)
            ->squish()
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
