<?php

namespace App\Models;

use App\Support\InstallmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu termin pembayaran di dalam skema cicilan.
 *
 * Nominalnya tersimpan sebagai angka tetap, bukan dihitung ulang dari
 * persentase saat dibaca, supaya penjumlahan seluruh termin selalu persis sama
 * dengan total penawaran. Persentase hanya keterangan bagaimana angka itu
 * disusun.
 */
class PaymentInstallment extends Model
{
    protected $fillable = [
        'payment_term_id',
        'installment_number',
        'percentage',
        'amount',
        'milestone',
        'due_date',
        'status',
        'activated_at',
        'paid_at',
        'admin_note',
        'last_reminder_days',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'activated_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_reminder_days' => 'integer',
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_term_id');
    }

    /** Seluruh bukti yang pernah diunggah, terbaru di atas. */
    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class)->latest('uploaded_at');
    }

    /** Bukti terakhir yang diunggah — yang menentukan keadaan termin sekarang. */
    public function latestProof(): HasOne
    {
        return $this->hasOne(PaymentProof::class)->latestOfMany('uploaded_at');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('installment_number');
    }

    /** Termin yang sudah aktif dan belum lunas. */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [
            InstallmentStatus::PENDING,
            InstallmentStatus::REJECTED,
            InstallmentStatus::OVERDUE,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return InstallmentStatus::label($this->status);
    }

    public function getMarkerAttribute(): string
    {
        return InstallmentStatus::marker($this->status);
    }

    /** Nama termin yang dipakai di antarmuka dan notifikasi. */
    public function getTitleAttribute(): string
    {
        return 'Termin '.$this->installment_number;
    }

    /** Pelanggan boleh mengunggah bukti untuk termin ini. */
    public function acceptsProof(): bool
    {
        return InstallmentStatus::isPayable($this->status);
    }

    public function isPaid(): bool
    {
        return $this->status === InstallmentStatus::RECEIVED;
    }

    public function isAwaitingVerification(): bool
    {
        return $this->status === InstallmentStatus::VERIFICATION;
    }

    /** Termin aktif yang batas waktunya sudah terlewati. */
    public function isPastDue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->endOfDay()->isPast()
            && in_array($this->status, [InstallmentStatus::PENDING, InstallmentStatus::REJECTED], true);
    }

    /** Sisa hari menuju jatuh tempo; negatif bila sudah lewat. */
    public function daysUntilDue(): ?int
    {
        return $this->due_date === null
            ? null
            : (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
    }
}
