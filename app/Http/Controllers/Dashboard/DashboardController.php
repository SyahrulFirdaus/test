<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Ringkasan dashboard pelanggan: berapa penawaran yang berjalan, berapa yang
 * sudah selesai, dan apa yang terakhir bergerak.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $quotations = QuotationRequest::query()->ownedBy($user);

        $closed = [
            QuotationStatus::COMPLETED,
            QuotationStatus::CANCELLED_BY_USER,
            QuotationStatus::CANCELLATION_APPROVED,
        ];

        return view('dashboard.index', [
            'summary' => [
                'total' => (clone $quotations)->count(),
                'active' => (clone $quotations)->whereNotIn('status', $closed)->count(),
                'completed' => (clone $quotations)->where('status', QuotationStatus::COMPLETED)->count(),
                'models' => QuotationItem::whereIn('quotation_request_id', (clone $quotations)->select('id'))->count(),
                'value' => (float) (clone $quotations)
                    ->whereNotIn('status', [QuotationStatus::CANCELLED_BY_USER, QuotationStatus::CANCELLATION_APPROVED])
                    ->sum('estimated_cost'),
            ],
            'recent' => (clone $quotations)->withCount('items')->latestFirst()->limit(5)->get(),
            'notifications' => $user->notifications()->limit(5)->get(),
        ]);
    }
}
