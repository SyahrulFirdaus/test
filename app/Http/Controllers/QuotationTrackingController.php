<?php

namespace App\Http\Controllers;

use App\Models\QuotationRequest;
use App\Services\QrCodeGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class QuotationTrackingController extends Controller
{
    /** Nomor tracking yang sudah lolos verifikasi WhatsApp pada sesi ini. */
    public const SESSION_KEY = 'tracking.verified';

    /** Percobaan verifikasi yang salah per nomor tracking per IP... */
    private const MAX_ATTEMPTS = 5;

    /** ...dalam rentang 15 menit. */
    private const DECAY_SECONDS = 900;

    public function __construct(private readonly QrCodeGenerator $qrCode) {}

    /** Formulir pencarian bagi pelanggan yang datang tanpa tautan langsung. */
    public function index(): View
    {
        return view('pages.tracking-lookup');
    }

    /**
     * Cari penawaran, lalu verifikasi 4 digit terakhir nomor WhatsApp-nya.
     *
     * Tracking baru dapat dibuka setelah verifikasi ini berhasil. Seluruhnya
     * diperiksa di sini, di server: nomor WhatsApp lengkap tidak pernah
     * dikirim ke browser. Hasilnya dicatat di sesi (lihat canView()), jadi
     * membuka URL tracking langsung tanpa verifikasi tetap ditolak.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['required', 'string', 'max:40'],
        ], [
            'tracking_number.required' => 'Nomor tracking wajib diisi.',
        ]);

        $number = strtoupper(trim($validated['tracking_number']));
        $quotation = QuotationRequest::with('user')->where('tracking_number', $number)->first();

        if ($quotation === null) {
            return back()
                ->withInput()
                ->withErrors(['tracking_number' => 'Nomor tracking tidak ditemukan. Periksa kembali penulisannya.']);
        }

        $failed = fn (string $message) => redirect()
            ->route('tracking.index')
            ->withInput($request->only('tracking_number'))
            ->withErrors(['whatsapp_last4' => $message]);

        // Tepat 4 digit angka — tidak kurang, tidak lebih.
        $digits = (string) $request->input('whatsapp_last4', '');

        if (! preg_match('/^\d{4}$/', $digits)) {
            return $failed('Masukkan tepat 4 digit angka terakhir nomor WhatsApp.');
        }

        // Empat digit hanya 10.000 kemungkinan, jadi percobaan per nomor
        // tracking dibatasi supaya tidak dapat ditebak satu per satu.
        $limiterKey = 'tracking-verify:'.$number.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($limiterKey) / 60);

            return $failed("Terlalu banyak percobaan verifikasi. Coba lagi dalam {$minutes} menit.");
        }

        if (! $this->matchesWhatsapp($quotation, $digits)) {
            RateLimiter::hit($limiterKey, self::DECAY_SECONDS);

            return $failed('Verifikasi gagal. 4 digit terakhir nomor WhatsApp tidak sesuai.');
        }

        RateLimiter::clear($limiterKey);

        $verified = (array) $request->session()->get(self::SESSION_KEY, []);
        $request->session()->put(self::SESSION_KEY, array_values(array_unique([...$verified, $number])));

        return redirect()->route('tracking.show', $number);
    }

    /**
     * Halaman tracking.
     *
     * Hanya terbuka setelah verifikasi WhatsApp berhasil pada sesi ini —
     * kecuali bagi pemilik penawaran atau staf yang sedang masuk, yang
     * identitasnya sudah terbukti lewat login. Selain itu diarahkan ke
     * halaman Tracking dengan modal verifikasi terbuka.
     *
     * Email dan nomor WhatsApp tidak ditampilkan, dan nama depan disensor.
     */
    public function show(Request $request, string $trackingNumber): View|RedirectResponse
    {
        $quotation = $this->findOrFail($trackingNumber);

        if (! $this->canView($request, $quotation)) {
            return $this->requireVerification($quotation);
        }

        return view('pages.tracking', [
            'quotation' => $quotation,
            'timeline' => $quotation->timeline,
            'histories' => $quotation->timelineHistories,
        ]);
    }

    /**
     * Bukti permintaan penawaran dalam bentuk PDF.
     *
     * Dokumen ini memuat kontak lengkap, jadi dijaga verifikasi yang sama
     * dengan halaman tracking.
     */
    public function document(Request $request, string $trackingNumber): Response|RedirectResponse
    {
        $quotation = $this->findOrFail($trackingNumber);

        if (! $this->canView($request, $quotation)) {
            return $this->requireVerification($quotation);
        }

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

    /**
     * Boleh melihat tracking: sudah lolos verifikasi pada sesi ini, atau
     * pemilik penawaran/staf yang sedang masuk.
     */
    private function canView(Request $request, QuotationRequest $quotation): bool
    {
        if (in_array($quotation->tracking_number, (array) $request->session()->get(self::SESSION_KEY, []), true)) {
            return true;
        }

        $user = $request->user();

        return $user !== null
            && ($user->isAdmin() || ($quotation->user_id !== null && $quotation->user_id === $user->id));
    }

    /** Kembali ke halaman Tracking dengan nomornya terisi dan modal verifikasi terbuka. */
    private function requireVerification(QuotationRequest $quotation): RedirectResponse
    {
        return redirect()
            ->route('tracking.index')
            ->withInput(['tracking_number' => $quotation->tracking_number])
            ->with('tracking_verify', true);
    }

    /**
     * Cocokkan dengan 4 digit terakhir nomor WhatsApp yang tercatat pada
     * penawaran; bila kosong, nomor pada akun pemiliknya.
     */
    private function matchesWhatsapp(QuotationRequest $quotation, string $digits): bool
    {
        $registered = preg_replace('/\D/', '', (string) $quotation->whatsapp);

        if ($registered === '') {
            $registered = preg_replace('/\D/', '', (string) $quotation->user?->phone);
        }

        return strlen($registered) >= 4 && hash_equals(substr($registered, -4), $digits);
    }

    private function findOrFail(string $trackingNumber): QuotationRequest
    {
        return QuotationRequest::query()
            ->with('timelineHistories', 'items')
            ->where('tracking_number', strtoupper(trim($trackingNumber)))
            ->firstOrFail();
    }
}
