<?php

namespace App\Services;

use App\Models\PaymentInstallment;
use App\Models\PaymentTerm;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\InstallmentStatus;
use App\Support\PaymentTermStatus;
use App\Support\QuotationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Angka dan ringkasan yang mengisi dashboard pelanggan.
 *
 * Dipisahkan dari controller karena dua dashboard — Personal dan Business —
 * membaca sebagian ukuran yang sama namun menyusunnya berbeda. Menaruh
 * definisinya di satu tempat menjaga agar "pesanan aktif" pada dashboard
 * Personal dan "Active Orders" pada dashboard Business benar-benar menghitung
 * hal yang sama.
 *
 * Seluruh angka dihitung dari data yang memang tersimpan; tidak ada yang
 * dikarang atau di-hardcode.
 */
class CustomerDashboard
{
    /** Penawaran milik satu akun. */
    private function scope(User $user): Builder
    {
        return QuotationRequest::query()->ownedBy($user);
    }

    /* ------------------------------------------------------- Personal --- */

    /**
     * Ringkasan dashboard Personal: empat angka yang paling dicari pelanggan
     * perorangan — berapa penawaran, mana yang sedang dikerjakan, adakah yang
     * harus dibayar, dan berapa yang sudah selesai.
     *
     * @return array<string, int>
     */
    public function personalStats(User $user): array
    {
        return [
            'quotations' => $this->scope($user)->count(),
            'active_orders' => $this->scope($user)->whereIn('status', QuotationStatus::productionKeys())->count(),
            'awaiting_payment' => $this->scope($user)->whereIn('status', QuotationStatus::paymentKeys())->count(),
            'completed' => $this->scope($user)->where('status', QuotationStatus::COMPLETED)->count(),
        ];
    }

    /* ------------------------------------------------------- Business --- */

    /**
     * Ringkasan dashboard Business.
     *
     * Dua angka uang di sini sengaja dihitung dari pembayaran yang benar-benar
     * tercatat, bukan dari nilai penawaran semata:
     *
     *  - `spending`    jumlah uang yang sudah diterima — termin yang lunas
     *                  ditambah pembayaran sekali bayar yang sudah diverifikasi;
     *  - `outstanding` sisa tagihan penawaran yang masih berjalan, yaitu sisa
     *                  termin yang belum lunas ditambah nilai penawaran yang
     *                  menunggu pembayaran tanpa skema termin.
     *
     * @return array<string, int|float>
     */
    public function businessStats(User $user): array
    {
        return [
            'active_quotations' => $this->scope($user)
                ->whereNotIn('status', QuotationStatus::closedKeys())
                ->whereNotIn('status', QuotationStatus::productionKeys())
                ->count(),
            'active_orders' => $this->scope($user)
                ->whereIn('status', QuotationStatus::productionKeys())
                ->count(),
            'completed_orders' => $this->scope($user)
                ->where('status', QuotationStatus::COMPLETED)
                ->count(),
            'outstanding' => $this->outstanding($user),
            'spending' => $this->spending($user),
        ];
    }

    /** Uang yang sudah benar-benar diterima dari akun ini. */
    public function spending(User $user): float
    {
        // Termin yang lunas.
        $fromInstallments = (float) PaymentInstallment::query()
            ->where('status', InstallmentStatus::RECEIVED)
            ->whereHas('term.quotation', fn (Builder $q) => $q->where('user_id', $user->id))
            ->sum('amount');

        // Pembayaran sekali bayar yang sudah diverifikasi. Penawaran yang
        // memakai termin dikecualikan agar nilainya tidak terhitung dua kali.
        $fromSingle = $this->scope($user)
            ->whereNotNull('payment_verified_at')
            ->whereIn('status', array_merge(
                QuotationStatus::productionKeys(),
                [QuotationStatus::COMPLETED],
            ))
            ->whereDoesntHave('paymentTerm', fn (Builder $q) => $q
                ->where('status', PaymentTermStatus::APPROVED)
                ->orWhere('status', PaymentTermStatus::COMPLETED))
            ->get()
            ->sum(fn (QuotationRequest $quotation) => (float) $quotation->payment_amount);

        return round($fromInstallments + $fromSingle, 2);
    }

    /** Sisa tagihan yang masih harus dibayar akun ini. */
    public function outstanding(User $user): float
    {
        // Sisa termin pada skema yang sudah disetujui dan belum lunas.
        $fromInstallments = (float) PaymentInstallment::query()
            ->whereNot('status', InstallmentStatus::RECEIVED)
            ->whereHas('term', fn (Builder $q) => $q->where('status', PaymentTermStatus::APPROVED))
            ->whereHas('term.quotation', fn (Builder $q) => $q
                ->where('user_id', $user->id)
                ->whereNotIn('status', QuotationStatus::cancelledKeys()))
            ->sum('amount');

        // Penawaran yang menunggu pembayaran tanpa skema termin.
        $fromSingle = $this->scope($user)
            ->whereIn('status', QuotationStatus::paymentKeys())
            ->whereDoesntHave('paymentTerm', fn (Builder $q) => $q
                ->where('status', PaymentTermStatus::APPROVED)
                ->orWhere('status', PaymentTermStatus::COMPLETED))
            ->get()
            ->sum(fn (QuotationRequest $quotation) => (float) $quotation->payment_amount);

        return round($fromInstallments + $fromSingle, 2);
    }

    /* ------------------------------------------------------ daftar isi --- */

    /**
     * Penawaran terbaru untuk tabel ringkasan.
     *
     * @return Collection<int, QuotationRequest>
     */
    public function recentQuotations(User $user, int $limit = 5): Collection
    {
        return $this->scope($user)
            ->with('paymentTerm')
            ->withCount('items')
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Penawaran yang sedang dikerjakan, untuk pelacakan produksi.
     *
     * @return Collection<int, QuotationRequest>
     */
    public function activeProduction(User $user, int $limit = 3): Collection
    {
        return $this->scope($user)
            ->whereIn('status', QuotationStatus::productionKeys())
            ->with('items')
            ->withCount('items')
            ->latestFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * Penawaran yang paling perlu ditindaklanjuti pelanggan sekarang.
     *
     * Dipakai kartu pengingat pembayaran sekali bayar; kosong berarti kartunya
     * tidak perlu ditampilkan sama sekali.
     */
    public function paymentDue(User $user): ?QuotationRequest
    {
        return $this->scope($user)
            ->whereIn('status', [QuotationStatus::AWAITING_PAYMENT, QuotationStatus::PAYMENT_REJECTED])
            // Penawaran bertahap punya pengingatnya sendiri per termin.
            ->whereDoesntHave('paymentTerm', fn (Builder $q) => $q
                ->where('status', PaymentTermStatus::APPROVED)
                ->where('installment_count', '>', 1))
            ->orderByRaw('payment_due_at IS NULL')
            ->orderBy('payment_due_at')
            ->first();
    }

    /**
     * Penawaran terakhir yang sedang berjalan, untuk penanda tahap sederhana
     * pada dashboard Personal.
     */
    public function trackedQuotation(User $user): ?QuotationRequest
    {
        return $this->scope($user)
            ->whereNotIn('status', QuotationStatus::closedKeys())
            ->latestFirst()
            ->first();
    }

    /* ------------------------------------------- pembayaran bertahap --- */

    /**
     * Skema pembayaran bertahap yang masih berjalan.
     *
     * @return Collection<int, PaymentTerm>
     */
    public function activePaymentTerms(User $user): Collection
    {
        return PaymentTerm::query()
            ->whereIn('status', [PaymentTermStatus::PENDING, PaymentTermStatus::APPROVED])
            ->whereHas('quotation', fn (Builder $q) => $q
                ->where('user_id', $user->id)
                ->whereNotIn('status', QuotationStatus::cancelledKeys()))
            ->with(['quotation', 'installments'])
            ->get();
    }

    /**
     * Termin yang perlu diingatkan: sudah aktif, dan jatuh temponya dekat atau
     * sudah lewat.
     *
     * @return Collection<int, PaymentInstallment>
     */
    public function installmentReminders(User $user): Collection
    {
        $window = (int) collect((array) config('payment.terms.reminder_days', [7]))
            ->map(fn ($days) => (int) $days)
            ->max();

        return PaymentInstallment::query()
            ->whereIn('status', [
                InstallmentStatus::PENDING,
                InstallmentStatus::REJECTED,
                InstallmentStatus::OVERDUE,
            ])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays($window)->toDateString())
            ->whereHas('term.quotation', fn (Builder $q) => $q
                ->where('user_id', $user->id)
                ->whereNotIn('status', QuotationStatus::cancelledKeys()))
            ->with('term.quotation')
            ->orderBy('due_date')
            ->get();
    }

    /* --------------------------------------------------------- dokumen --- */

    /**
     * Dokumen yang benar-benar tersedia untuk diunduh.
     *
     * Hanya dokumen yang memang ada yang didaftar — bukti penawaran selalu
     * dapat dibuat sistem, sedangkan bukti pembayaran bergantung pada berkas
     * yang sudah diunggah pelanggan. Daftar ini tidak pernah memuat tautan
     * yang berujung kosong.
     *
     * @return Collection<int, array{label: string, meta: string, url: string}>
     */
    public function documents(User $user, int $limit = 8): Collection
    {
        $documents = collect();

        $quotations = $this->scope($user)
            ->with('paymentTerm.installments.proofs')
            ->latestFirst()
            ->limit($limit)
            ->get();

        foreach ($quotations as $quotation) {
            $documents->push([
                'label' => 'Bukti Penawaran '.$quotation->tracking_number.'.pdf',
                'meta' => $quotation->created_at->translatedFormat('d F Y').' · '.$quotation->status_label,
                'url' => route('tracking.document', $quotation->tracking_number),
            ]);

            if ($quotation->paymentProofExists()) {
                $documents->push([
                    'label' => 'Bukti Pembayaran '.$quotation->tracking_number,
                    'meta' => optional($quotation->payment_proof_uploaded_at)->translatedFormat('d F Y').' · Sekali bayar',
                    'url' => route('dashboard.quotations.payment.proof', $quotation),
                ]);
            }

            foreach ($quotation->paymentTerm?->installments ?? [] as $installment) {
                foreach ($installment->proofs as $proof) {
                    if (! $proof->fileExists()) {
                        continue;
                    }

                    $documents->push([
                        'label' => 'Bukti '.$installment->title.' '.$quotation->tracking_number,
                        'meta' => $proof->uploaded_at->translatedFormat('d F Y').' · '.$proof->statusLabel(),
                        'url' => route('dashboard.quotations.installments.proof', [$quotation, $installment, $proof]),
                    ]);
                }
            }
        }

        return $documents->take($limit);
    }

    /* -------------------------------------------------------- re-order --- */

    /**
     * Pesanan lampau yang layak dipesan ulang.
     *
     * Hanya penawaran yang sudah selesai dan berkasnya masih ada di
     * penyimpanan — memesan ulang berkas yang sudah terhapus hanya akan gagal
     * di tengah jalan.
     *
     * @return Collection<int, QuotationRequest>
     */
    public function reorderable(User $user, int $limit = 4): Collection
    {
        return $this->scope($user)
            ->where('status', QuotationStatus::COMPLETED)
            ->with('items')
            ->withCount('items')
            ->latestFirst()
            ->limit($limit)
            ->get()
            ->filter(fn (QuotationRequest $quotation) => $quotation->items->isNotEmpty()
                && $quotation->items->every(fn (QuotationItem $item) => $item->fileExists()))
            ->values();
    }
}
