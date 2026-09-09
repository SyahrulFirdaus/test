<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PaymentInstallment;
use App\Models\PaymentProof;
use App\Models\QuotationRequest;
use App\Services\PaymentTermFlow;
use App\Support\InstallmentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman pembayaran satu termin milik pelanggan.
 *
 * Isinya sama seperti halaman pembayaran sekali bayar — nomor penawaran,
 * nominal, batas waktu, rekening tujuan, dan formulir unggah bukti — hanya saja
 * yang ditagih adalah satu termin, bukan seluruh nilai penawaran. Rekening yang
 * ditampilkan tetap rekening yang sama dengan pembayaran biasa.
 */
class InstallmentController extends Controller
{
    public function __construct(private readonly PaymentTermFlow $terms) {}

    public function show(Request $request, QuotationRequest $quotation, PaymentInstallment $installment): View|RedirectResponse
    {
        $this->authorizeAccess($request, $quotation, $installment);

        // Termin yang belum aktif tidak boleh dibuka: urutan pembayarannya
        // ditentukan jadwal, bukan pilihan pelanggan.
        if ($installment->status === InstallmentStatus::INACTIVE) {
            return redirect()
                ->route('dashboard.quotations.payment', $quotation)
                ->with('error', $installment->title.' belum aktif. Selesaikan termin sebelumnya lebih dulu.');
        }

        return view('dashboard.quotations.installment', [
            'quotation' => $quotation,
            'term' => $installment->term,
            'installment' => $installment->load('proofs'),
            'bank' => config('payment.bank'),
            'extensions' => config('payment.proof.extensions'),
            'maxKilobytes' => (int) config('payment.proof.max_kilobytes'),
        ]);
    }

    /** Terima bukti pembayaran termin lalu pindahkan ke "Pengecekan Pembayaran". */
    public function store(Request $request, QuotationRequest $quotation, PaymentInstallment $installment): RedirectResponse
    {
        $this->authorizeAccess($request, $quotation, $installment);

        // Bukti yang masih diperiksa tidak boleh ditimpa unggahan baru, dan
        // termin yang sudah diterima tidak menerima bukti lagi.
        if (! $installment->acceptsProof()) {
            return redirect()
                ->route('dashboard.quotations.payment', $quotation)
                ->with('error', $installment->isAwaitingVerification()
                    ? 'Bukti pembayaran '.$installment->title.' sedang diverifikasi admin. Mohon tunggu hasilnya sebelum mengunggah ulang.'
                    : $installment->title.' berstatus "'.$installment->status_label.'" sehingga bukti pembayaran tidak dapat diunggah.');
        }

        $extensions = (array) config('payment.proof.extensions', ['jpg', 'jpeg', 'png', 'pdf']);
        $maxKilobytes = (int) config('payment.proof.max_kilobytes', 5120);

        $request->validate([
            'proof' => ['required', 'file', 'extensions:'.implode(',', $extensions), 'max:'.$maxKilobytes],
        ], [
            'proof.required' => 'Pilih berkas bukti pembayaran lebih dulu.',
            'proof.extensions' => 'Bukti pembayaran harus berformat '.strtoupper(implode(', ', $extensions)).'.',
            'proof.max' => 'Ukuran berkas melebihi batas '.round($maxKilobytes / 1024).' MB.',
        ]);

        $this->terms->submitProof($installment, $request->file('proof'), $request->user());

        return redirect()
            ->route('dashboard.quotations.installments.show', [$quotation, $installment])
            ->with('status', 'Bukti pembayaran berhasil diunggah. Mohon tunggu verifikasi dari Admin.');
    }

    /** Buka kembali salah satu bukti yang pernah diunggah, hanya untuk pemiliknya. */
    public function proof(
        Request $request,
        QuotationRequest $quotation,
        PaymentInstallment $installment,
        PaymentProof $proof,
    ): StreamedResponse|RedirectResponse {
        $this->authorizeAccess($request, $quotation, $installment);

        abort_unless($proof->payment_installment_id === $installment->id, 404);

        if (! $proof->fileExists()) {
            return back()->with('error', 'Bukti pembayaran tidak ditemukan di penyimpanan.');
        }

        return Storage::disk('local')->response($proof->file_path, $proof->file_name);
    }

    /**
     * Termin ini benar-benar milik penawaran tersebut, dan penawarannya milik
     * akun yang sedang masuk. Milik akun lain tidak boleh terbaca sama sekali.
     */
    private function authorizeAccess(Request $request, QuotationRequest $quotation, PaymentInstallment $installment): void
    {
        abort_unless($quotation->user_id === $request->user()->id, 404);
        abort_unless($installment->term->quotation_request_id === $quotation->id, 404);
    }
}
