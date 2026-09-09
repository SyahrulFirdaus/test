<?php

namespace App\Models;

use App\Support\InstallmentStatus;
use App\Support\PaymentTermStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Skema pembayaran bertahap milik satu penawaran Business.
 *
 * Menyimpan pilihan pelanggan (1x/3x/4x/5x) beserta nasib pengajuannya, dan
 * menjadi induk seluruh terminnya. Selama belum disetujui admin, terminnya
 * belum terbentuk — jadwal baru disusun pada saat persetujuan.
 */
class PaymentTerm extends Model
{
    protected $fillable = [
        'quotation_request_id',
        'total_amount',
        'installment_count',
        'status',
        'rejection_reason',
        'requested_at',
        'approved_by',
        'approved_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'installment_count' => 'integer',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(QuotationRequest::class, 'quotation_request_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Jadwal terminnya, selalu urut dari termin pertama. */
    public function installments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class)->orderBy('installment_number');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $q) => $q->where('status', $status));
    }

    /** Pengajuan yang menunggu keputusan admin. */
    public function scopeAwaitingDecision(Builder $query): Builder
    {
        return $query->where('status', PaymentTermStatus::PENDING);
    }

    public function getStatusLabelAttribute(): string
    {
        return PaymentTermStatus::label($this->status);
    }

    /** Label skemanya, mis. "3x Pembayaran". */
    public function getSchemeLabelAttribute(): string
    {
        return $this->installment_count.'x Pembayaran';
    }

    public function isPending(): bool
    {
        return $this->status === PaymentTermStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [PaymentTermStatus::APPROVED, PaymentTermStatus::COMPLETED], true);
    }

    public function isRejected(): bool
    {
        return $this->status === PaymentTermStatus::REJECTED;
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentTermStatus::COMPLETED;
    }

    /**
     * Skema ini benar-benar memecah pembayaran.
     *
     * Pilihan 1x tetap dicatat sebagai payment term agar riwayat pilihan
     * pelanggan lengkap, tetapi pembayarannya tidak memerlukan jadwal termin —
     * alurnya sama dengan pembayaran sekali bayar yang sudah ada.
     */
    public function isInstalment(): bool
    {
        return $this->installment_count > 1;
    }

    /** Jadwal terminnya sudah terbentuk dan berlaku. */
    public function hasSchedule(): bool
    {
        return $this->isApproved() && $this->isInstalment();
    }

    /* --------------------------------------------------------- ringkasan --- */

    /** Total nominal termin yang sudah diterima admin. */
    public function paidAmount(): float
    {
        return (float) $this->installments
            ->where('status', InstallmentStatus::RECEIVED)
            ->sum(fn (PaymentInstallment $installment) => (float) $installment->amount);
    }

    /** Sisa tagihan yang belum diterima. */
    public function outstandingAmount(): float
    {
        return round((float) $this->total_amount - $this->paidAmount(), 2);
    }

    /** Termin yang sedang berjalan — yang pertama belum lunas. */
    public function currentInstallment(): ?PaymentInstallment
    {
        return $this->installments->first(fn (PaymentInstallment $installment) => ! $installment->isPaid());
    }

    /** Termin berikutnya yang belum aktif, untuk diaktifkan setelah satu lunas. */
    public function nextInactiveInstallment(): ?PaymentInstallment
    {
        return $this->installments->first(
            fn (PaymentInstallment $installment) => $installment->status === InstallmentStatus::INACTIVE
        );
    }

    /** Seluruh termin sudah diterima. */
    public function allInstallmentsPaid(): bool
    {
        return $this->installments->isNotEmpty()
            && $this->installments->every(fn (PaymentInstallment $installment) => $installment->isPaid());
    }

    /** Perbandingan lunas terhadap total, 0–100, untuk bilah kemajuan. */
    public function paidPercentage(): float
    {
        $total = (float) $this->total_amount;

        return $total <= 0 ? 0.0 : round($this->paidAmount() / $total * 100, 2);
    }
}
