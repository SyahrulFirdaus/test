<?php

namespace App\Models;

use App\Support\InstallmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Satu berkas bukti pembayaran termin.
 *
 * Setiap unggahan menambah baris baru, tidak menimpa yang lama, sehingga
 * percobaan pembayaran yang pernah ditolak tetap terbaca beserta alasannya.
 * Berkasnya sendiri disimpan pada disk privat `local` — sama seperti berkas
 * model dan bukti pembayaran sekali bayar.
 */
class PaymentProof extends Model
{
    protected $fillable = [
        'payment_installment_id',
        'file_path',
        'file_name',
        'status',
        'uploaded_at',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(PaymentInstallment::class, 'payment_installment_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Bukti yang masih menunggu keputusan admin. */
    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->where('status', InstallmentStatus::VERIFICATION);
    }

    public function fileExists(): bool
    {
        return filled($this->file_path) && Storage::disk('local')->exists($this->file_path);
    }

    public function isAwaitingReview(): bool
    {
        return $this->status === InstallmentStatus::VERIFICATION;
    }

    public function statusLabel(): string
    {
        return InstallmentStatus::label($this->status);
    }

    /** Berkas ikut terhapus begitu barisnya dihapus. */
    protected static function booted(): void
    {
        static::deleting(function (self $proof) {
            if ($proof->fileExists()) {
                Storage::disk('local')->delete($proof->file_path);
            }
        });
    }
}
