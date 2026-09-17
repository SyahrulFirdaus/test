<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kurs terakhir yang berhasil diambil dari penyedia.
 *
 * Ditulis App\Services\UsdRate setiap kali pengambilan berhasil, dan dibaca
 * kembali HANYA ketika penyedianya sedang tidak dapat dihubungi. Lihat
 * migrasinya untuk alasan mengapa ini disimpan di basis data, bukan di cache.
 */
class ExchangeRate extends Model
{
    protected $table = 'exchange_rates';

    protected $fillable = [
        'pair',
        'rate',
        'provider',
        'source_label',
        'published_at',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'published_at' => 'datetime',
            'fetched_at' => 'datetime',
        ];
    }
}
