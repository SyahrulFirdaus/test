<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use App\Services\PaymentTermFlow;
use App\Services\PaymentTermPlanner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pemilihan skema pembayaran oleh pelanggan Business.
 *
 * Pilihan yang ditawarkan mengikuti nominal penawaran dan aturan yang diatur
 * admin — halaman ini tidak pernah menentukan sendiri skema mana yang boleh
 * tampil, seluruhnya dibaca lewat App\Services\PaymentTermPlanner.
 *
 * Pelanggan Personal tidak pernah sampai ke sini: pemeriksaan tipe akun ada di
 * setiap aksi, dan alur pembayaran mereka tetap yang lama.
 */
class PaymentTermController extends Controller
{
    public function __construct(
        private readonly PaymentTermFlow $terms,
        private readonly PaymentTermPlanner $planner,
    ) {}

    /** Halaman pilihan skema pembayaran beserta simulasi pembagian terminnya. */
    public function create(Request $request, QuotationRequest $quotation): View|RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);

        if (($redirect = $this->guardBusiness($request, $quotation)) !== null) {
            return $redirect;
        }

        $quotation->load('paymentTerm.installments');

        // Skema yang sudah disetujui tidak dapat diganti sendiri oleh
        // pelanggan; perubahannya harus melewati persetujuan admin lagi.
        if ($quotation->paymentTerm?->isApproved()) {
            return redirect()
                ->route('dashboard.quotations.payment', $quotation)
                ->with('error', 'Skema pembayaran Anda sudah disetujui admin dan tidak dapat diubah sendiri.');
        }

        $total = $this->planner->quotationTotal($quotation);

        return view('dashboard.quotations.payment-term', [
            'quotation' => $quotation,
            'total' => $total,
            'options' => $this->planner->optionsFor($total),
            // Tiap pilihan disertai pratinjau pembagiannya agar pelanggan tahu
            // persis berapa yang harus dibayar tiap termin sebelum memilih.
            'previews' => $this->previews($total),
            'term' => $quotation->paymentTerm,
        ]);
    }

    /** Terima pilihan skema pembayaran lalu ajukan ke admin. */
    public function store(Request $request, QuotationRequest $quotation): RedirectResponse
    {
        $this->authorizeOwner($request, $quotation);

        if (($redirect = $this->guardBusiness($request, $quotation)) !== null) {
            return $redirect;
        }

        $quotation->load('paymentTerm');

        if ($quotation->paymentTerm?->isApproved()) {
            return redirect()
                ->route('dashboard.quotations.payment', $quotation)
                ->with('error', 'Skema pembayaran yang sudah disetujui admin tidak dapat diubah tanpa persetujuan ulang.');
        }

        $total = $this->planner->quotationTotal($quotation);
        $allowed = $this->planner->allowedCounts($total);

        $validated = $request->validate([
            'installment_count' => ['required', 'integer', 'in:'.implode(',', $allowed ?: [1])],
        ], [
            'installment_count.required' => 'Pilih salah satu skema pembayaran lebih dulu.',
            'installment_count.in' => 'Skema pembayaran tersebut tidak tersedia untuk nilai penawaran ini.',
        ]);

        $term = $this->terms->request($quotation, (int) $validated['installment_count'], $request->user());

        return redirect()
            ->route('dashboard.quotations.payment', $quotation)
            ->with('status', $term->isInstalment()
                ? 'Pengajuan '.$term->scheme_label.' berhasil dikirim. Mohon tunggu persetujuan payment term dari Admin.'
                : 'Skema pembayaran 1x (lunas) berhasil dipilih dan menunggu konfirmasi Admin.');
    }

    /**
     * Pratinjau pembagian nominal tiap pilihan skema.
     *
     * @return array<int, array<int, float>>
     */
    private function previews(float $total): array
    {
        $previews = [];

        foreach ($this->planner->allowedCounts($total) as $count) {
            $previews[$count] = $this->planner->splitEvenly($total, $count);
        }

        return $previews;
    }

    /**
     * Fitur ini khusus pelanggan Business dengan penawaran yang sudah bernilai.
     *
     * @return RedirectResponse|null pengalihan bila tidak memenuhi syarat
     */
    private function guardBusiness(Request $request, QuotationRequest $quotation): ?RedirectResponse
    {
        if (! $request->user()->isBusiness()) {
            return redirect()
                ->route('dashboard.quotations.show', $quotation)
                ->with('error', 'Pembayaran bertahap hanya tersedia untuk akun Business.');
        }

        if (! $this->planner->availableFor($quotation, $request->user())) {
            return redirect()
                ->route('dashboard.quotations.show', $quotation)
                ->with('error', 'Skema pembayaran baru dapat dipilih setelah nilai penawaran Anda ditetapkan.');
        }

        return null;
    }

    private function authorizeOwner(Request $request, QuotationRequest $quotation): void
    {
        abort_unless($quotation->user_id === $request->user()->id, 404);
    }
}
