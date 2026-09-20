<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use App\Services\PaymentVerificationService;
use App\Support\PaymentProofFile;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Verifikasi pembayaran sekali bayar, langsung dari Detail Penawaran.
 *
 * Inilah SATU-SATUNYA tempat pembayaran sekali bayar diputuskan. Sebelumnya
 * keputusannya berada di menu Pembayaran, yang menuntut admin berpindah halaman
 * hanya untuk menekan satu tombol atas penawaran yang sedang ia buka. Menu
 * Pembayaran kini khusus menangani pembayaran bertahap, yang memang punya
 * beberapa bukti per penawaran dan membutuhkan antreannya sendiri.
 *
 * Controllernya tipis: seluruh pemeriksaan hak, keadaan, transaksi, riwayat,
 * Activity Log, dan notifikasi ada di App\Services\PaymentVerificationService,
 * yang sama-sama dipakai keputusan per termin.
 */
class QuotationPaymentController extends Controller
{
    public function __construct(private readonly PaymentVerificationService $verification) {}

    /**
     * Tampilkan bukti pembayarannya.
     *
     * Berkasnya ada di disk privat, jadi satu-satunya jalan membukanya adalah
     * lewat route ini — yang sudah dijaga hak `quotation.view`.
     */
    public function proof(QuotationRequest $quotation): StreamedResponse|RedirectResponse
    {
        if (! $quotation->paymentProofExists()) {
            return back()->with('error', 'Bukti pembayaran tidak ditemukan di penyimpanan.');
        }

        return PaymentProofFile::response($quotation->payment_proof_path, $quotation->payment_proof_name);
    }

    /** Terima pembayaran; status berpindah ke "Pembayaran Diterima". */
    public function accept(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        return $this->decide(
            fn () => $this->verification->acceptQuotationPayment($quotation, $request->user()),
            'Pembayaran '.$quotation->tracking_number.' diterima dan pelanggan sudah diberi tahu.',
        );
    }

    /** Tolak pembayaran; alasannya wajib diisi dan ikut sampai ke pelanggan. */
    public function reject(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ], [
            'reason.required' => 'Isi alasan penolakan agar pelanggan mengetahui penyebabnya.',
        ]);

        return $this->decide(
            fn () => $this->verification->rejectQuotationPayment($quotation, trim($validated['reason']), $request->user()),
            'Pembayaran '.$quotation->tracking_number.' ditolak beserta alasannya.',
        );
    }

    /**
     * Jalankan keputusannya, lalu terjemahkan penolakan service menjadi jawaban.
     *
     * Hak akses yang kurang tetap menjadi 403 — bukan pesan di halaman —
     * supaya percobaan memanggil endpoint ini langsung terbaca apa adanya pada
     * log. Keadaan yang tidak memungkinkan (bukti sudah diputuskan orang lain,
     * berkasnya hilang) dikembalikan sebagai pesan, karena itu keadaan yang
     * wajar terjadi dan bukan pelanggaran.
     */
    private function decide(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', $success);
    }
}
