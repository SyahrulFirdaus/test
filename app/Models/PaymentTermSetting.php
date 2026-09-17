<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu pilihan payment term beserta batas nominalnya.
 *
 * Aturannya sengaja tinggal di basis data supaya admin dapat menggeser batas
 * dan mematikan pilihan tanpa menyentuh kode — halaman pembuatan penawaran
 * tidak pernah menghitung sendiri pilihan mana yang boleh tampil.
 */
class PaymentTermSetting extends Model
{
    protected $fillable = [
        'installment_count',
        'enabled',
        'minimum_amount',
    ];

    protected function casts(): array
    {
        return [
            'installment_count' => 'integer',
            'enabled' => 'boolean',
            'minimum_amount' => 'decimal:2',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('installment_count');
    }

    /** Label yang dilihat pelanggan, mis. "3x Pembayaran (3 Termin)". */
    public function getLabelAttribute(): string
    {
        return $this->installment_count === 1
            ? '1x Pembayaran (Lunas)'
            : $this->installment_count.'x Pembayaran ('.$this->installment_count.' Termin)';
    }

    /** Pilihan ini terbuka untuk penawaran senilai sekian. */
    public function acceptsAmount(float $amount): bool
    {
        return $this->enabled && $amount >= (float) $this->minimum_amount;
    }
}
