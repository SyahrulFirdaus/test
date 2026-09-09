<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentInstallment;
use App\Models\PaymentTerm;
use App\Services\PaymentTermFlow;
use App\Services\PaymentTermPlanner;
use App\Support\InstallmentStatus;
use App\Support\PaymentTermStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Menu "Payment Terms" pada dashboard admin.
 *
 * Menampilkan seluruh penawaran Business yang memakai pembayaran bertahap
 * beserta kemajuan pembayarannya, dan menjadi tempat admin memutuskan
 * pengajuan skema, menyesuaikan pembagian termin, serta mengaktifkan termin
 * lebih awal bila milestone pekerjaannya sudah tercapai.
 */
class PaymentTermController extends Controller
{
    public function __construct(
        private readonly PaymentTermFlow $terms,
        private readonly PaymentTermPlanner $planner,
    ) {}

    public function index(Request $request): View
    {
        // Pengajuan yang menunggu keputusan selalu jadi tab pertama — itulah
        // pekerjaan yang benar-benar menahan pelanggan.
        $filter = in_array($request->query('filter'), ['pending', 'approved', 'completed', 'rejected'], true)
            ? $request->query('filter')
            : 'pending';

        $status = match ($filter) {
            'approved' => PaymentTermStatus::APPROVED,
            'completed' => PaymentTermStatus::COMPLETED,
            'rejected' => PaymentTermStatus::REJECTED,
            default => PaymentTermStatus::PENDING,
        };

        $terms = PaymentTerm::query()
            ->status($status)
            ->with(['quotation.user.businessProfile', 'installments'])
            ->orderBy('requested_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.payment-terms.index', [
            'terms' => $terms,
            'filter' => $filter,
            'counts' => [
                'pending' => PaymentTerm::where('status', PaymentTermStatus::PENDING)->count(),
                'approved' => PaymentTerm::where('status', PaymentTermStatus::APPROVED)->count(),
                'completed' => PaymentTerm::where('status', PaymentTermStatus::COMPLETED)->count(),
                'rejected' => PaymentTerm::where('status', PaymentTermStatus::REJECTED)->count(),
            ],
        ]);
    }

    public function show(PaymentTerm $term): View
    {
        return view('admin.payment-terms.show', [
            'term' => $term->load([
                'quotation.user.businessProfile',
                'installments.latestProof',
                'approver',
            ]),
            'milestones' => $this->planner->defaultMilestones($term->installment_count),
        ]);
    }

    /** Setujui pengajuan skema pembayaran; jadwal terminnya terbentuk di sini. */
    public function approve(Request $request, PaymentTerm $term): RedirectResponse
    {
        if (! $term->isPending()) {
            return back()->with('error', 'Skema pembayaran ini sudah diputuskan sebelumnya.');
        }

        $this->terms->approve($term, $request->user());

        return redirect()
            ->route('admin.payment-terms.show', $term)
            ->with('status', 'Skema '.$term->scheme_label.' disetujui. Jadwal termin sudah terbentuk dan pelanggan diberi tahu.');
    }

    /** Tolak pengajuan skema pembayaran beserta alasannya. */
    public function reject(Request $request, PaymentTerm $term): RedirectResponse
    {
        if (! $term->isPending()) {
            return back()->with('error', 'Skema pembayaran ini sudah diputuskan sebelumnya.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $reason = filled($validated['reason'] ?? null) ? trim($validated['reason']) : null;

        $this->terms->reject($term, $reason, $request->user());

        return back()->with('status', 'Skema '.$term->scheme_label.' ditolak dan pelanggan sudah diberi tahu.');
    }

    /**
     * Simpan pembagian termin yang disesuaikan admin.
     *
     * Total persentase wajib tepat 100% — pembagian yang tidak genap ditolak
     * sebelum apa pun tersimpan, sehingga jumlah termin tidak pernah meleset
     * dari total penawaran.
     */
    public function updateSchedule(Request $request, PaymentTerm $term): RedirectResponse
    {
        if (! $term->hasSchedule()) {
            return back()->with('error', 'Pembagian termin hanya dapat diatur setelah skema pembayaran disetujui.');
        }

        $validated = $request->validate([
            'installments' => ['required', 'array', 'size:'.$term->installments()->count()],
            'installments.*.percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'installments.*.milestone' => ['nullable', 'string', 'max:190'],
            'installments.*.due_date' => ['nullable', 'date'],
        ], [
            'installments.*.percentage.required' => 'Persentase tiap termin wajib diisi.',
            'installments.*.percentage.min' => 'Persentase termin tidak boleh nol.',
        ]);

        $rows = array_values($validated['installments']);

        if (! $this->planner->percentagesAddUp(array_column($rows, 'percentage'))) {
            return back()
                ->withInput()
                ->with('error', 'Total persentase seluruh termin harus tepat 100%. Perubahan tidak disimpan.');
        }

        $this->terms->updateSchedule($term, $rows, $request->user());

        return back()->with('status', 'Pembagian termin berhasil disimpan.');
    }

    /**
     * Aktifkan satu termin lebih awal.
     *
     * Dipakai ketika milestone pekerjaannya sudah tercapai sebelum termin
     * sebelumnya lunas, mis. produksi terlanjur dimulai.
     */
    public function activate(Request $request, PaymentTerm $term, PaymentInstallment $installment): RedirectResponse
    {
        abort_unless($installment->payment_term_id === $term->id, 404);

        if ($installment->status !== InstallmentStatus::INACTIVE) {
            return back()->with('error', $installment->title.' sudah aktif atau sudah dibayar.');
        }

        $this->terms->activate($installment, $request->user());

        return back()->with('status', $installment->title.' diaktifkan dan pelanggan sudah diberi tahu.');
    }
}
