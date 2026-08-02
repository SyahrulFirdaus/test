<?php

namespace App\Models;

use App\Support\QuotationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris riwayat perubahan status penawaran.
 *
 * Baris riwayat hanya ditambahkan, tidak pernah diubah maupun dihapus saat status
 * berpindah — sehingga jejak perkembangan permintaan tetap utuh.
 */
class QuotationHistory extends Model
{
    protected $fillable = [
        'quotation_request_id',
        'status',
        'note',
        'created_by',
    ];

    public function quotationRequest(): BelongsTo
    {
        return $this->belongsTo(QuotationRequest::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return QuotationStatus::label($this->status);
    }
}
