<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentInstallment;
use App\Models\PaymentProof;
use App\Models\QuotationRequest;
use App\Services\PaymentFlow;
use App\Services\PaymentTermFlow;
use App\Support\InstallmentStatus;
use App\Support\PaymentTermStatus;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu "Verifikasi Pembayaran" pada dashboard admin.
 *
 * Menampilkan bukti transfer yang masuk beserta nama pelanggan, nomor
 * penawaran, nominal, dan waktu unggahnya, lalu menyediakan dua keputusan:
 * terima atau tolak beserta alasannya.
 *
 * Dua jenis pembayaran mengantre di halaman yang sama namun pada tab
 * berbeda: pembayaran sekali bayar dan bukti tiap termin milik pelanggan
 * Business.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentFlow $payments,
        private readonly PaymentTermFlow $terms,
    ) {}

    public function index(Request $request): View
    {
        // Bukti yang belum diperiksa selalu di atas, lalu yang paling lama
        // menunggu — daftar ini adalah antrean kerja, bukan arsip.
        $filter = in_array($request->query('filter'), ['review', 'installments', 'awaiting', 'decided'], true)
            ? $request->query('filter')
            : 'review';

        $statuses = match ($filter) {
            'awaiting' => [QuotationStatus::AWAITING_PAYMENT],
            'decided' => [QuotationStatus::PAYMENT_RECEIVED, QuotationStatus::PAYMENT_REJECTED],
            default => [QuotationStatus::PAYMENT_REVIEW],
        };

        // Tab "Bukti Termin" mengantre bukti pembayaran bertahap; tab lainnya
        // tetap mengantre pembayaran sekali bayar seperti sebelumnya.
        $quotations = $filter === 'installments'
            ? new LengthAwarePaginator([], 0, 15)
            : QuotationRequest::query()
                ->whereIn('status', $statuses)
                // Penawaran yang memakai pembayaran bertahap punya antreannya
                // sendiri, jadi tidak ikut muncul di tab sekali bayar.
                ->whereDoesntHave('paymentTerm', fn ($term) => $term->where('status', PaymentTermStatus::APPROVED)
                    ->where('installment_count', '>', 1))
                ->with('user')
                ->orderByRaw('payment_proof_uploaded_at IS NULL')
                ->orderBy('payment_proof_uploaded_at')
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();

        $installments = $filter === 'installments'
            ? PaymentInstallment::query()
                ->where('status', InstallmentStatus::VERIFICATION)
                ->with(['latestProof', 'term.quotation.user'])
                ->orderBy('updated_at')
                ->paginate(15)
                ->withQueryString()
            : new LengthAwarePaginator([], 0, 15);

        return view('admin.payments.index', [
            'quotations' => $quotations,
            'installments' => $installments,
            'filter' => $filter,
            'counts' => [
                'review' => QuotationRequest::where('status', QuotationStatus::PAYMENT_REVIEW)->count(),
                'installments' => PaymentInstallment::where('status', InstallmentStatus::VERIFICATION)->count(),
                'awaiting' => QuotationRequest::where('status', QuotationStatus::AWAITING_PAYMENT)->count(),
                'decided' => QuotationRequest::whereIn('status', [
                    QuotationStatus::PAYMENT_RECEIVED,
                    QuotationStatus::PAYMENT_REJECTED,
                ])->count(),
            ],
        ]);
    }

    /* --------------------------------------------- verifikasi per termin --- */

    /** Tampilkan bukti pembayaran salah satu termin. */
    public function installmentProof(PaymentInstallment $installment, PaymentProof $proof): StreamedResponse|RedirectResponse
    {
        abort_unless($proof->payment_installment_id === $installment->id, 404);

        if (! $proof->fileExists()) {
            return back()->with('error', 'Bukti pembayaran tidak ditemukan di penyimpanan.');
        }

        return Storage::disk('local')->response($proof->file_path, $proof->file_name);
    }

    /** Terima pembayaran satu termin; termin berikutnya ikut diaktifkan. */
    public function approveInstallment(Request $request, PaymentInstallment $installment): RedirectResponse
    {
        if (! $installment->isAwaitingVerification()) {
            return back()->with('error', 'Tidak ada bukti pembayaran yang menunggu verifikasi pada termin ini.');
        }

        $this->terms->approveProof($installment, $request->user());

        return back()->with('status', 'Pembayaran '.$installment->title.' diterima dan pelanggan sudah diberi tahu.');
    }

    /** Tolak bukti pembayaran satu termin beserta alasannya. */
    public function rejectInstallment(Request $request, PaymentInstallment $installment): RedirectResponse
    {
        if (! $installment->isAwaitingVerification()) {
            return back()->with('error', 'Tidak ada bukti pembayaran yang menunggu verifikasi pada termin ini.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ], [
            'reason.required' => 'Isi alasan penolakan agar pelanggan mengetahui penyebabnya.',
        ]);

        $this->terms->rejectProof($installment, trim($validated['reason']), $request->user());

        return back()->with('status', 'Pembayaran '.$installment->title.' ditolak beserta alasannya.');
    }

    /** Tampilkan berkas bukti pembayaran; berkasnya disimpan pada disk privat. */
    public function proof(QuotationRequest $quotation): StreamedResponse|RedirectResponse
    {
        if (! $quotation->paymentProofExists()) {
            return back()->with('error', 'Bukti pembayaran tidak ditemukan di penyimpanan.');
        }

        return Storage::disk('local')->response(
            $quotation->payment_proof_path,
            $quotation->payment_proof_name,
        );
    }

    public function approve(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        if (! $this->awaitsDecision($quotation)) {
            return back()->with('error', 'Tidak ada bukti pembayaran yang menunggu verifikasi pada penawaran ini.');
        }

        $this->payments->approve($quotation, $request->user());

        return back()->with('status', 'Pembayaran '.$quotation->tracking_number.' diterima dan pelanggan sudah diberi tahu.');
    }

    public function reject(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        if (! $this->awaitsDecision($quotation)) {
            return back()->with('error', 'Tidak ada bukti pembayaran yang menunggu verifikasi pada penawaran ini.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ], [
            'reason.required' => 'Isi alasan penolakan agar pelanggan mengetahui penyebabnya.',
        ]);

        $this->payments->reject($quotation, trim($validated['reason']), $request->user());

        return back()->with('status', 'Pembayaran '.$quotation->tracking_number.' ditolak beserta alasannya.');
    }

    private function awaitsDecision(QuotationRequest $quotation): bool
    {
        return $quotation->status === QuotationStatus::PAYMENT_REVIEW;
    }
}
