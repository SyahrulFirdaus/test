<?php

namespace App\Http\Controllers;

use App\Models\QuotationRequest;
use App\Services\QrCodeGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class QuotationTrackingController extends Controller
{
    public function __construct(private readonly QrCodeGenerator $qrCode) {}

    /** Formulir pencarian bagi pelanggan yang datang tanpa tautan langsung. */
    public function index(): View
    {
        return view('pages.tracking-lookup');
    }

    /** Arahkan ke halaman tracking berdasarkan nomor yang dimasukkan. */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['required', 'string', 'max:40'],
        ], [
            'tracking_number.required' => 'Nomor tracking wajib diisi.',
        ]);

        $number = strtoupper(trim($validated['tracking_number']));

        if (! QuotationRequest::where('tracking_number', $number)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['tracking_number' => 'Nomor tracking tidak ditemukan. Periksa kembali penulisannya.']);
        }

        return redirect()->route('tracking.show', $number);
    }

    /**
     * Halaman tracking publik.
     *
     * Nomor tracking berisi enam karakter acak sehingga tidak praktis ditebak,
     * tetapi email dan nomor WhatsApp tetap disamarkan di halaman ini agar
     * tautan yang terlanjur tersebar tidak memampangkan kontak lengkap.
     */
    public function show(string $trackingNumber): View
    {
        $quotation = $this->findOrFail($trackingNumber);

        return view('pages.tracking', [
            'quotation' => $quotation,
            'timeline' => $quotation->timeline,
            'histories' => $quotation->timelineHistories,
        ]);
    }

    /** Bukti permintaan penawaran dalam bentuk PDF. */
    public function document(string $trackingNumber): Response
    {
        $quotation = $this->findOrFail($trackingNumber);

        $trackingUrl = route('tracking.show', $quotation->tracking_number);

        $pdf = Pdf::loadView('pdf.quotation-receipt', [
            'quotation' => $quotation,
            'trackingUrl' => $trackingUrl,
            'qrCode' => $this->qrCode->dataUri($trackingUrl),
            // Logo disematkan sebagai data URI SVG: dompdf tidak dapat mengunduh
            // aset lewat HTTP saat dirender, dan PNG memerlukan ekstensi GD.
            'logo' => $this->inlineSvg(public_path('images/logo-mark-white.svg')),
        ])->setPaper('a4');

        return $pdf->download('Bukti-Penawaran-'.$quotation->tracking_number.'.pdf');
    }

    private function inlineSvg(string $path): string
    {
        return is_file($path)
            ? 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($path))
            : '';
    }

    private function findOrFail(string $trackingNumber): QuotationRequest
    {
        return QuotationRequest::query()
            ->with('timelineHistories', 'items')
            ->where('tracking_number', strtoupper(trim($trackingNumber)))
            ->firstOrFail();
    }
}
