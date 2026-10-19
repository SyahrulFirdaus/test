<?php

namespace App\Models;

use App\Support\LeadSource;
use App\Support\LeadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Calon pelanggan dari Form landing page layanan atau dari Quotation.
 *
 * Satu lead dapat memiliki beberapa quotation (`quotation_requests.lead_id`).
 * Status dan nilai deal-nya terpisah dari status quotation/pembayaran/produksi.
 */
class Lead extends Model
{
    protected $fillable = [
        'source',
        'user_id',
        'name',
        'company',
        'email',
        'whatsapp',
        'service',
        'quantity',
        'dimensions',
        'notes',
        'attachments',
        'source_url',
        'landing_page',
        'referrer',
        'gclid',
        'gbraid',
        'wbraid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'first_touch_at',
        'status',
        'deal_value',
        'deal_at',
        'ads_exported_at',
        'ads_export_count',
    ];

    protected $attributes = [
        'status' => LeadStatus::LEAD,
    ];

    protected function casts(): array
    {
        return [
            'deal_value' => 'integer',
            'quantity' => 'integer',
            'attachments' => 'array',
            'first_touch_at' => 'datetime',
            'deal_at' => 'datetime',
            'ads_exported_at' => 'datetime',
            'ads_export_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(QuotationRequest::class)->latest();
    }

    /** Riwayat perubahan lead di Activity Log. */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
            ->where('subject_type', $this->getMorphClass())
            ->latest('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', LeadStatus::open());
    }

    public function getStatusLabelAttribute(): string
    {
        return LeadStatus::label($this->status);
    }

    public function getSourceLabelAttribute(): string
    {
        return LeadSource::label($this->source);
    }

    /** "Rp5.000.000", atau "—" bila belum diisi. */
    public function getDealValueLabelAttribute(): string
    {
        return self::rupiah($this->deal_value);
    }

    public static function rupiah(mixed $value): string
    {
        return $value === null || $value === '' ? '—' : 'Rp'.number_format((int) $value, 0, ',', '.');
    }

    /** Nomor WhatsApp dalam bentuk angka saja dengan awalan 62, untuk pencocokan. */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }
}
