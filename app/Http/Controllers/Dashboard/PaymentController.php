<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use App\Services\PaymentFlow;
use App\Services\PaymentTermPlanner;
use App\Support\PaymentProofFile;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman "Menunggu Pembayaran" milik pelanggan.
 *
 * Menampilkan nomor penawaran, total tagihan, rekening tujuan, petunjuk
 * pembayaran, hitung mundur 24 jam, dan formulir unggah bukti transfer.
 * Batas waktunya diperiksa ulang di server setiap halaman dibuka maupun saat
 * bukti dikirim, jadi hitung mundur di browser hanya sebagai penunjuk — bukan
 * satu-satunya yang menentukan penawaran kedaluwarsa.
 *
 * Halaman ini sekaligus menjadi pintu masuk pembayaran bertahap milik
 * pelanggan Business: bila penawarannya memakai skema termin, yang tampil
 * adalah timeline terminnya, bukan satu tagihan tunggal.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentFlow $payments,
        private readonly PaymentTermPlanner $planner,
    ) {}

    public function show(Request $request, QuotationRequest $quotation): View|RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);

        $quotation->load('paymentTerm.installments.latestProof');

        $this->payments->expireIfDue($quotation);

        $term = $quotation->paymentTerm;

        /*
        | Pembayaran bertahap punya halamannya sendiri berupa timeline termin.
        | Halaman ini tetap dapat dibuka meski penawaran sudah lewat tahap
        | pembayaran — termin berikutnya masih berjalan saat produksi jalan.
        |
        | Pengajuan yang masih menunggu keputusan admin ikut ke sini supaya
        | pelanggan melihat keadaannya, bukan formulir bayar yang belum berlaku.
        */
        if ($term !== null && ($term->isPending() || $quotation->usesInstallments())) {
            return view('dashboard.quotations.installments', [
                'quotation' => $quotation,
                'term' => $term,
                'bank' => config('payment.bank'),
            ]);
        }

        if (! $quotation->isPaymentStage()) {
            return redirect()
                ->route('dashboard.quotations.show', $quotation)
                ->with('error', 'Penawaran ini berstatus "'.$quotation->status_label.'", tidak ada pembayaran yang perlu diselesaikan.');
        }

        /*
        | Pelanggan Business memilih skema pembayarannya lebih dulu — sekali
        | bayar atau bertahap. Skema 1x yang sudah disetujui melanjutkan ke
        | halaman pembayaran biasa di bawah, sama persis dengan alur lama.
        |
        | Pelanggan Personal tidak pernah melewati langkah ini.
        */
        if (($term === null || $term->isRejected())
            && $this->planner->availableFor($quotation, $request->user())
            && $this->planner->allowedCounts($this->planner->quotationTotal($quotation)) !== []) {
            return redirect()->route('dashboard.quotations.payment-term', $quotation);
        }

        return view('dashboard.quotations.payment', [
            'quotation' => $quotation->load('items'),
            'bank' => config('payment.bank'),
            'windowHours' => (int) config('payment.window_hours', 24),
            'extensions' => config('payment.proof.extensions'),
            'maxKilobytes' => (int) config('payment.proof.max_kilobytes'),
        ]);
    }

    /** Terima berkas bukti pembayaran lalu pindahkan ke "Pengecekan Pembayaran". */
    public function store(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);

        // Pembayaran bertahap diunggah per termin, bukan sekaligus lewat
        // formulir tunggal ini.
        if ($quotation->usesInstallments()) {
            return redirect()
                ->route('dashboard.quotations.payment', $quotation)
                ->with('error', 'Penawaran ini memakai pembayaran bertahap. Unggah bukti pada termin yang sedang aktif.');
        }

        $this->payments->expireIfDue($quotation);

        if (! $quotation->needsPaymentProof()) {
            return redirect()
                ->route('dashboard.quotations.show', $quotation)
                ->with('error', $quotation->status === QuotationStatus::PAYMENT_REVIEW
                    ? 'Bukti pembayaran Anda sudah diunggah dan sedang diverifikasi admin.'
                    : 'Penawaran ini berstatus "'.$quotation->status_label.'" sehingga bukti pembayaran tidak dapat diunggah.');
        }

        // Ekstensi DAN isi berkas diperiksa — lihat App\Support\PaymentProofFile.
        $request->validate(['proof' => PaymentProofFile::rules()], PaymentProofFile::messages());

        $this->payments->submitProof($quotation, $request->file('proof'), $request->user());

        return redirect()
            ->route('dashboard.quotations.payment', $quotation)
            ->with('status', 'Bukti pembayaran berhasil diunggah. Mohon tunggu proses verifikasi dari Admin.');
    }

    /** Buka kembali bukti yang sudah diunggah, hanya untuk pemiliknya. */
    public function proof(Request $request, QuotationRequest $quotation): StreamedResponse|RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);

        if (! $quotation->paymentProofExists()) {
            return back()->with('error', 'Bukti pembayaran tidak ditemukan di penyimpanan.');
        }

        return PaymentProofFile::response($quotation->payment_proof_path, $quotation->payment_proof_name);
    }

    /** Penawaran milik akun lain tidak boleh terbaca sama sekali. */
    private function authorizeOwner(Request $request, QuotationRequest $quotation): void
    {
        abort_unless($quotation->user_id === $request->user()->id, 404);
    }
}
