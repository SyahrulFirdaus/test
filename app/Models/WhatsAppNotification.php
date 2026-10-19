<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu pesan WhatsApp yang dicoba dikirim sistem, beserta hasilnya.
 *
 * Baris dibuat berstatus `pending` SEBELUM pesan dikirim, lalu diperbarui
 * menjadi `sent` atau `failed`, sehingga pengiriman yang terputus di tengah
 * jalan pun tetap meninggalkan jejak.
 */
class WhatsAppNotification extends Model
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const TYPE_STATUS_UPDATE = 'status_update';

    public const TYPE_TEST = 'test';

    protected $table = 'whatsapp_notifications';

    protected $fillable = [
        'type',
        'quotation_request_id',
        'user_id',
        'tracking_number',
        'customer_name',
        'recipient',
        'sender',
        'previous_status',
        'quotation_status',
        'message',
        'status',
        'error_message',
        'provider_message_id',
        'sent_at',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::SENT => 'Berhasil',
            self::FAILED => 'Gagal',
            self::PENDING => 'Menunggu',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(QuotationRequest::class, 'quotation_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            array_key_exists((string) $status, self::statuses()),
            fn (Builder $q) => $q->where('status', $status),
        );
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? (string) $this->status;
    }

    public function failed(): bool
    {
        return $this->status === self::FAILED;
    }
}
