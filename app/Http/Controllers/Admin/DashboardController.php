<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard statistik admin.
 *
 * Angka pendapatan dihitung dari penawaran yang benar-benar selesai memakai
 * harga penawaran yang ditetapkan admin (`estimated_price`); bila belum
 * ditetapkan, estimasi sistem yang dipakai — sama seperti yang dilihat
 * pelanggan di halaman tracking.
 */
class DashboardController extends Controller
{
    /** Banyaknya bulan yang ditampilkan pada grafik. */
    private const CHART_MONTHS = 12;

    /** Banyaknya penawaran terbaru yang ditampilkan di bawah dashboard. */
    private const RECENT_LIMIT = 8;

    public function index(Request $request): View
    {
        $completed = QuotationRequest::query()->where('status', QuotationStatus::COMPLETED);

        // Filter status hanya menyaring daftar penawaran di bawah. Kartu
        // statistik, grafik, dan sebaran status tetap memotret keseluruhan —
        // angka seperti Total Pendapatan kehilangan artinya bila ikut disaring
        // (pendapatan hanya berasal dari penawaran yang selesai).
        $status = $request->query('status');
        $status = is_string($status) && QuotationStatus::exists($status) ? $status : null;

        return view('admin.dashboard', [
            'summary' => [
                'quotations' => QuotationRequest::count(),
                'completed' => (clone $completed)->count(),
                'printed_models' => QuotationItem::whereIn(
                    'quotation_request_id',
                    (clone $completed)->select('id')
                )->sum('quantity'),
                'revenue' => $this->revenue(),
                'users' => User::customers()->count(),
            ],

            'pending_cancellations' => QuotationRequest::query()
                ->awaitingCancellation()
                ->latestFirst()
                ->limit(5)
                ->get(),

            'status_breakdown' => $this->statusBreakdown(),
            'chart' => $this->monthlyChart(),

            'statuses' => QuotationStatus::options(),
            'filters' => ['status' => $status],

            'recent' => QuotationRequest::query()
                ->withCount('items')
                ->status($status)
                ->latestFirst()
                ->limit(self::RECENT_LIMIT)
                ->get(),

            // Banyaknya penawaran yang cocok dengan filter, supaya admin tahu
            // daftar di bawah masih terpotong atau memang sudah seluruhnya.
            'recent_total' => QuotationRequest::query()->status($status)->count(),
        ]);
    }

    /** Total pendapatan dari penawaran berstatus selesai. */
    private function revenue(): float
    {
        return (float) QuotationRequest::query()
            ->where('status', QuotationStatus::COMPLETED)
            ->sum(DB::raw('COALESCE(estimated_price, estimated_cost, 0)'));
    }

    /**
     * Sebaran penawaran per status, dipakai sebagai daftar ringkas di samping
     * grafik. Status tanpa penawaran tetap ditampilkan agar posisinya stabil.
     *
     * @return array<int, array{key: string, label: string, total: int}>
     */
    private function statusBreakdown(): array
    {
        $counts = QuotationRequest::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(QuotationStatus::all())
            ->map(fn (array $stage, string $key) => [
                'key' => $key,
                'label' => $stage['label'],
                'total' => (int) ($counts[$key] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * Statistik bulanan 12 bulan terakhir: banyaknya penawaran masuk, penawaran
     * selesai, dan nilai pendapatannya.
     *
     * Pengelompokan dikerjakan di PHP, bukan lewat fungsi tanggal basis data,
     * supaya hasilnya sama pada MySQL maupun SQLite yang dipakai pengujian.
     *
     * @return array<string, mixed>
     */
    private function monthlyChart(): array
    {
        $start = now()->startOfMonth()->subMonths(self::CHART_MONTHS - 1);

        $months = collect(range(0, self::CHART_MONTHS - 1))
            ->mapWithKeys(fn (int $offset) => [
                $start->copy()->addMonths($offset)->format('Y-m') => [
                    'label' => $start->copy()->addMonths($offset)->translatedFormat('M Y'),
                    'short' => $start->copy()->addMonths($offset)->translatedFormat('M'),
                    'incoming' => 0,
                    'completed' => 0,
                    'revenue' => 0.0,
                ],
            ]);

        QuotationRequest::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'status', 'estimated_price', 'estimated_cost'])
            ->each(function (QuotationRequest $quotation) use (&$months) {
                $key = Carbon::parse($quotation->created_at)->format('Y-m');

                if (! $months->has($key)) {
                    return;
                }

                $bucket = $months[$key];
                $bucket['incoming']++;

                if ($quotation->status === QuotationStatus::COMPLETED) {
                    $bucket['completed']++;
                    $bucket['revenue'] += (float) ($quotation->estimated_price ?? $quotation->estimated_cost ?? 0);
                }

                $months[$key] = $bucket;
            });

        $rows = $months->values()->all();

        return [
            'months' => $rows,
            'max_incoming' => max(1, (int) collect($rows)->max('incoming')),
            'max_revenue' => max(1.0, (float) collect($rows)->max('revenue')),
        ];
    }
}
