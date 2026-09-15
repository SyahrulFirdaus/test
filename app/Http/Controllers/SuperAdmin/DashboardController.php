<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard statistik keseluruhan sistem.
 *
 * Berbeda dengan dashboard Admin yang memotret pekerjaan harian — sebaran
 * status dan penawaran terbaru — halaman ini memotret keseluruhan: berapa
 * banyak yang masuk, berapa yang selesai, berapa pendapatannya, dan
 * bagaimana perkembangannya sepanjang dua belas bulan terakhir.
 *
 * Angka pendapatan dihitung dari penawaran yang benar-benar selesai memakai
 * harga penawaran yang tersimpan; penawaran lama yang kolomnya masih kosong
 * memakai estimasi sistemnya — sama seperti yang dilihat pelanggan di halaman
 * tracking.
 *
 * Seluruhnya dibaca langsung dari basis data, tidak ada angka contoh.
 */
class DashboardController extends Controller
{
    /** Banyaknya bulan yang ditampilkan pada grafik. */
    private const CHART_MONTHS = 12;

    public function index(): View
    {
        $completed = QuotationRequest::query()->where('status', QuotationStatus::COMPLETED);

        return view('superadmin.dashboard', [
            'summary' => [
                'quotations' => QuotationRequest::count(),
                'completed' => (clone $completed)->count(),
                'printed_models' => (int) QuotationItem::whereIn(
                    'quotation_request_id',
                    (clone $completed)->select('id')
                )->sum('quantity'),
                'revenue' => $this->revenue(),
                'net_profit' => $this->netProfit(),
                'users' => User::customers()->count(),
                'admins' => User::plainAdmins()->count(),
            ],

            'chart' => $this->monthlyChart(),

            // Sebaran status tetap dihitung di sini juga: pada dashboard
            // Superadmin gunanya bukan antrean kerja melainkan gambaran
            // keseluruhan, jadi ditampilkan sebagai pendamping grafik.
            'status_breakdown' => $this->statusBreakdown(),
        ]);
    }

    /**
     * Total pendapatan kotor: seluruh Harga Jual penawaran yang sudah selesai.
     *
     * Disebut kotor karena di dalamnya masih ada modal — material, jam mesin,
     * packaging — yang belum dikeluarkan.
     */
    private function revenue(): float
    {
        return (float) QuotationRequest::query()
            ->where('status', QuotationStatus::COMPLETED)
            ->sum(DB::raw('COALESCE(estimated_price, estimated_cost, 0)'));
    }

    /**
     * Profit bersih: penjumlahan komponen Profit dari tiap penawaran selesai.
     *
     * Bukan dihitung ulang dengan rumus tersendiri, melainkan dibaca dari
     * perhitungan yang sudah tersimpan pada `cost_breakdown` masing-masing
     * penawaran — komponen yang sama dengan baris "Profit" pada Detail
     * Perhitungan Harga, yaitu Subtotal × Profit %. Dengan begitu angka di
     * sini tidak pernah bisa berbeda dari rincian yang dilihat admin.
     *
     * Penjumlahannya dikerjakan di PHP, bukan lewat fungsi JSON basis data,
     * supaya hasilnya sama pada MySQL maupun SQLite yang dipakai pengujian.
     *
     * Penawaran lama yang dibuat sebelum rumus Price List berlaku tidak
     * menyimpan komponen ini dan karena itu tidak menyumbang apa pun — bukan
     * ditaksir, karena menaksir profit yang tidak pernah dihitung akan
     * melaporkan angka yang tidak dapat ditelusuri ke penawarannya.
     */
    private function netProfit(): float
    {
        $profit = QuotationRequest::query()
            ->where('status', QuotationStatus::COMPLETED)
            ->pluck('cost_breakdown')
            ->sum(fn ($breakdown) => (float) (is_array($breakdown) ? ($breakdown['profit'] ?? 0) : 0));

        return round($profit, 2);
    }

    /**
     * Sebaran penawaran per status. Status tanpa penawaran tetap ditampilkan
     * agar posisinya stabil dari waktu ke waktu.
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
     * Statistik bulanan dua belas bulan terakhir: banyaknya penawaran masuk,
     * penawaran selesai, dan nilai pendapatannya.
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
