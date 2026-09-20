<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentInstallment;
use App\Models\PaymentProof;
use App\Models\PaymentTerm;
use App\Services\PaymentVerificationService;
use App\Support\InstallmentStatus;
use App\Support\PaymentProofFile;
use App\Support\PaymentTermStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu "Verifikasi Pembayaran": pusat verifikasi pembayaran BERTAHAP.
 *
 * Halaman ini dahulu mengantre dua hal sekaligus — pembayaran sekali bayar dan
 * bukti tiap termin. Yang pertama kini diputuskan langsung dari Detail
 * Penawaran (App\Http\Controllers\Admin\QuotationPaymentController), tempat
 * admin memang sudah berada ketika memeriksa penawarannya, sehingga tidak perlu
 * lagi berpindah halaman untuk menekan satu tombol.
 *
 * Yang tinggal di sini justru yang benar-benar membutuhkan antrean tersendiri:
 * penawaran pelanggan Business dengan Payment Term punya beberapa bukti
 * pembayaran untuk satu penawaran, masing-masing dengan jadwal, nominal, dan
 * keputusannya sendiri. Satu bukti hanya pernah muncul di satu tempat.
 */
class PaymentController extends Controller
{
    /**
     * Termin yang belum diputuskan: belum aktif, menunggu dibayar, buktinya
     * ditolak, atau sudah lewat jatuh tempo. Semuanya belum menuntut keputusan
     * admin sekarang, tetapi perlu terlihat sebagai jadwal yang berjalan.
     */
    private const SCHEDULED = [
        InstallmentStatus::INACTIVE,
        InstallmentStatus::PENDING,
        InstallmentStatus::REJECTED,
        InstallmentStatus::OVERDUE,
    ];

    public function __construct(private readonly PaymentVerificationService $verification) {}

    public function index(Request $request): View
    {
        $filter = in_array($request->query('filter'), ['review', 'scheduled', 'decided'], true)
            ? $request->query('filter')
            : 'review';

        /*
         * Antrean kerja, bukan arsip: bukti yang paling lama menunggu di atas.
         *
         * Seluruh tab dibatasi pada termin milik Payment Term yang berjalan,
         * jadi pembayaran sekali bayar tidak pernah muncul di sini — termasuk
         * milik pelanggan Business yang memang membayar sekali.
         */
        $installments = PaymentInstallment::query()
            ->whereHas('term', fn ($term) => $term->whereIn('status', [
                PaymentTermStatus::APPROVED,
                PaymentTermStatus::COMPLETED,
            ]))
            ->with(['latestProof', 'term.quotation.user'])
            ->when($filter === 'review', fn ($q) => $q
                ->where('status', InstallmentStatus::VERIFICATION)
                ->orderBy('updated_at'))
            ->when($filter === 'scheduled', fn ($q) => $q
                ->whereIn('status', self::SCHEDULED)
                ->orderBy('due_date'))
            ->when($filter === 'decided', fn ($q) => $q
                ->where('status', InstallmentStatus::RECEIVED)
                ->orderByDesc('paid_at'))
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', [
            'installments' => $installments,
            'filter' => $filter,
            'counts' => $this->counts(),
            // Penawaran Business yang sedang berjalan dengan termin, untuk
            // ringkasan di atas antrean.
            'activeTerms' => PaymentTerm::whereIn('status', [PaymentTermStatus::APPROVED])->count(),
        ]);
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        $scoped = fn () => PaymentInstallment::query()->whereHas('term', fn ($term) => $term->whereIn('status', [
            PaymentTermStatus::APPROVED,
            PaymentTermStatus::COMPLETED,
        ]));

        return [
            'review' => $scoped()->where('status', InstallmentStatus::VERIFICATION)->count(),
            'scheduled' => $scoped()->whereIn('status', self::SCHEDULED)->count(),
            'decided' => $scoped()->where('status', InstallmentStatus::RECEIVED)->count(),
        ];
    }

    /* --------------------------------------------- verifikasi per termin --- */

    /** Tampilkan bukti pembayaran salah satu termin. */
    public function installmentProof(PaymentInstallment $installment, PaymentProof $proof): StreamedResponse|RedirectResponse
    {
        abort_unless($proof->payment_installment_id === $installment->id, 404);

        if (! $proof->fileExists()) {
            return back()->with('error', 'Bukti pembayaran tidak ditemukan di penyimpanan.');
        }

        return PaymentProofFile::response($proof->file_path, $proof->file_name);
    }

    /** Terima pembayaran satu termin; termin berikutnya ikut diaktifkan. */
    public function approveInstallment(Request $request, PaymentInstallment $installment): RedirectResponse
    {
        return $this->decide(
            fn () => $this->verification->acceptInstallment($installment, $request->user()),
            'Pembayaran '.$installment->title.' diterima dan pelanggan sudah diberi tahu.',
        );
    }

    /** Tolak bukti pembayaran satu termin beserta alasannya. */
    public function rejectInstallment(Request $request, PaymentInstallment $installment): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ], [
            'reason.required' => 'Isi alasan penolakan agar pelanggan mengetahui penyebabnya.',
        ]);

        return $this->decide(
            fn () => $this->verification->rejectInstallment($installment, trim($validated['reason']), $request->user()),
            'Pembayaran '.$installment->title.' ditolak beserta alasannya.',
        );
    }

    /**
     * Jalankan keputusannya, lalu terjemahkan penolakan service menjadi jawaban.
     *
     * Sama persis dengan yang dilakukan Detail Penawaran: hak yang kurang
     * menjadi 403, keadaan yang tidak memungkinkan menjadi pesan di halaman.
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
