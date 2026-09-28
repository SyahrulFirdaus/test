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
use Illuminate\Support\Facades\DB;

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
     * harus dibayar, dan berapa yang sudah selesai — ditambah total belanja
     * untuk kartu utama.
     *
     * @return array<string, int|float>
     */
    public function personalStats(User $user): array
    {
        return [
            'quotations' => $this->scope($user)->count(),
            'active_orders' => $this->scope($user)->whereIn('status', QuotationStatus::productionKeys())->count(),
            'awaiting_payment' => $this->scope($user)->whereIn('status', QuotationStatus::paymentKeys())->count(),
            'completed' => $this->scope($user)->where('status', QuotationStatus::COMPLETED)->count(),
            'spending' => $this->spending($user),
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

    /**
     * Bahan grafik dashboard pelanggan, dipakai dashboard Personal maupun Business.
     *
     *  - `monthly`       tujuh bulan terakhir: quotation yang dibuat, berapa di
     *                    antaranya yang berlanjut menjadi pesanan, dan uang yang
     *                    diterima pada bulan itu;
     *  - `trends`        perubahan bulan ini terhadap bulan lalu (persen), null
     *                    bila bulan lalu kosong sehingga tidak ada pembanding;
     *  - `distribution`  sebaran quotation menurut tahapnya;
     *  - `technologies`  teknologi cetak yang paling sering dipesan.
     *
     * Pengelompokan per bulan dikerjakan di PHP, bukan dengan fungsi tanggal
     * SQL, supaya tidak bergantung pada jenis basis datanya.
     *
     * @return array<string, mixed>
     */
    public function insights(User $user, int $months = 7): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $buckets = collect(range(0, $months - 1))
            ->mapWithKeys(fn (int $offset) => [$start->copy()->addMonths($offset)->format('Y-m') => [
                'label' => $start->copy()->addMonths($offset)->translatedFormat('M'),
                'month' => $start->copy()->addMonths($offset)->translatedFormat('F Y'),
                'quotations' => 0,
                'orders' => 0,
                'spending' => 0.0,
            ]])
            ->all();

        $ordered = array_merge(QuotationStatus::productionKeys(), [QuotationStatus::COMPLETED]);

        foreach ($this->scope($user)->where('created_at', '>=', $start)->get(['created_at', 'status']) as $quotation) {
            $key = $quotation->created_at->format('Y-m');
            $buckets[$key]['quotations']++;

            if (in_array($quotation->status, $ordered, true)) {
                $buckets[$key]['orders']++;
            }
        }

        foreach ($this->payments($user, $start) as [$paidAt, $amount]) {
            $buckets[$paidAt->format('Y-m')]['spending'] += $amount;
        }

        $monthly = array_values($buckets);
        [$previous, $current] = array_slice($monthly, -2);

        $trend = fn (float $now, float $before) => $before > 0
            ? round(($now - $before) / $before * 100, 1)
            : null;

        $counts = $this->scope($user)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $sum = fn (array $keys) => (int) collect($keys)->sum(fn (string $key) => $counts[$key] ?? 0);

        return [
            'monthly' => $monthly,
            'trends' => [
                'quotations' => $trend($current['quotations'], $previous['quotations']),
                'orders' => $trend($current['orders'], $previous['orders']),
                'spending' => $trend($current['spending'], $previous['spending']),
            ],
            'distribution' => [
                'total' => (int) $counts->sum(),
                'in_progress' => $counts->sum() - $sum(QuotationStatus::closedKeys()) - $sum(QuotationStatus::productionKeys()),
                'production' => $sum(QuotationStatus::productionKeys()),
                'completed' => $sum([QuotationStatus::COMPLETED]),
            ],
            'technologies' => $this->scope($user)
                ->whereNotNull('technology')
                ->whereNotIn('status', QuotationStatus::cancelledKeys())
                ->select('technology', DB::raw('count(*) as total'))
                ->groupBy('technology')
                ->orderByDesc('total')
                ->limit(4)
                ->pluck('total', 'technology')
                ->map(fn ($total) => (int) $total)
                ->all(),
        ];
    }

    /**
     * Pembayaran yang diterima sejak tanggal tertentu, sebagai pasangan
     * [tanggal diterima, jumlah]. Aturannya sama dengan spending(): termin yang
     * lunas ditambah pembayaran sekali bayar di luar skema termin.
     *
     * @return Collection<int, array{0: \Illuminate\Support\Carbon, 1: float}>
     */
    private function payments(User $user, \DateTimeInterface $since): Collection
    {
        $installments = PaymentInstallment::query()
            ->where('status', InstallmentStatus::RECEIVED)
            ->where('paid_at', '>=', $since)
            ->whereHas('term.quotation', fn (Builder $q) => $q->where('user_id', $user->id))
            ->get(['paid_at', 'amount'])
            ->map(fn (PaymentInstallment $installment) => [$installment->paid_at, (float) $installment->amount]);

        $single = $this->scope($user)
            ->where('payment_verified_at', '>=', $since)
            ->whereIn('status', array_merge(
                QuotationStatus::productionKeys(),
                [QuotationStatus::COMPLETED],
            ))
            ->whereDoesntHave('paymentTerm', fn (Builder $q) => $q
                ->where('status', PaymentTermStatus::APPROVED)
                ->orWhere('status', PaymentTermStatus::COMPLETED))
            ->get()
            ->map(fn (QuotationRequest $quotation) => [$quotation->payment_verified_at, (float) $quotation->payment_amount]);

        return $installments->concat($single)->values();
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
