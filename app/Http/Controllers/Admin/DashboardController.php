<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\AdminPermission;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard operasional admin.
 *
 * Isinya sengaja terbatas pada yang dipakai bekerja: kartu ringkas, sebaran
 * status, dan penawaran terbaru. Statistik dua belas bulan terakhir pindah ke
 * Dashboard Superadmin — gambaran keseluruhan sistem bukan alat kerja harian.
 *
 * Angka pendapatan dihitung dari penawaran yang benar-benar selesai memakai
 * harga penawaran yang tersimpan (`estimated_price`, diisi dari estimasi
 * sistem sejak permintaan dikirim); penawaran lama yang kolomnya masih kosong
 * memakai estimasi sistemnya — sama seperti yang dilihat pelanggan di halaman
 * tracking.
 */
class DashboardController extends Controller
{
    /** Banyaknya penawaran terbaru yang ditampilkan di bawah dashboard. */
    private const RECENT_LIMIT = 8;

    public function index(Request $request): View
    {
        $completed = QuotationRequest::query()->where('status', QuotationStatus::COMPLETED);

        // Filter status hanya menyaring daftar penawaran di bawah. Kartu
        // statistik dan sebaran status tetap memotret keseluruhan —
        // angka seperti Total Pendapatan kehilangan artinya bila ikut disaring
        // (pendapatan hanya berasal dari penawaran yang selesai).
        $status = $request->query('status');
        $status = is_string($status) && QuotationStatus::exists($status) ? $status : null;

        // Daftar penawaran (nama pelanggan, nilai, status) hanya dimuat bagi
        // yang berhak atas menu Penawaran — bukan sekadar disembunyikan Blade.
        $canViewQuotations = $request->user()->can(AdminPermission::QUOTATION_VIEW);

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

            'pending_cancellations' => $canViewQuotations
                ? QuotationRequest::query()
                    ->awaitingCancellation()
                    ->latestFirst()
                    ->limit(5)
                    ->get()
                : collect(),

            'status_breakdown' => $canViewQuotations ? $this->statusBreakdown() : [],

            'statuses' => QuotationStatus::options(),
            'filters' => ['status' => $status],

            'recent' => $canViewQuotations
                ? QuotationRequest::query()
                    ->withCount('items')
                    ->status($status)
                    ->latestFirst()
                    ->limit(self::RECENT_LIMIT)
                    ->get()
                : collect(),

            // Banyaknya penawaran yang cocok dengan filter, supaya admin tahu
            // daftar di bawah masih terpotong atau memang sudah seluruhnya.
            'recent_total' => $canViewQuotations ? QuotationRequest::query()->status($status)->count() : 0,
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
     * Sebaran penawaran per status: antrean kerja admin hari itu. Status
     * tanpa penawaran tetap ditampilkan agar posisinya stabil.
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
}
