<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Dashboard pelanggan, dua rupa menurut tipe akunnya.
 *
 * Personal mendapat halaman yang ringkas — penawaran, pesanan, pembayaran —
 * sedangkan Business mendapat halaman yang lebih padat: skema pembayaran
 * bertahap, pelacakan produksi, dokumen, data perusahaan, dan pemesanan ulang.
 *
 * Keduanya berbagi satu alamat `/dashboard`. Tipe akun yang menentukan mana
 * yang tampil, jadi tidak ada alamat terpisah yang dapat dicoba pelanggan
 * untuk membuka dashboard tipe lain.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly CustomerDashboard $dashboard) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return $user->isBusiness()
            ? $this->business($user)
            : $this->personal($user);
    }

    /** Dashboard Personal: alur penawaran → pesanan → pembayaran → selesai. */
    private function personal(User $user): View
    {
        return view('dashboard.personal', [
            'stats' => $this->dashboard->personalStats($user),
            'recent' => $this->dashboard->recentQuotations($user),
            'tracked' => $this->dashboard->trackedQuotation($user),
            'paymentDue' => $this->dashboard->paymentDue($user),
            'orders' => $this->dashboard->activeProduction($user),
            'notifications' => $user->notifications()->limit(5)->get(),
        ]);
    }

    /** Dashboard Business: quotation → payment term → produksi → dokumen. */
    private function business(User $user): View
    {
        return view('dashboard.business', [
            'stats' => $this->dashboard->businessStats($user),
            'recent' => $this->dashboard->recentQuotations($user),
            'terms' => $this->dashboard->activePaymentTerms($user),
            'reminders' => $this->dashboard->installmentReminders($user),
            'paymentDue' => $this->dashboard->paymentDue($user),
            'production' => $this->dashboard->activeProduction($user),
            'documents' => $this->dashboard->documents($user),
            'reorderable' => $this->dashboard->reorderable($user),
            'profile' => $user->businessProfile,
            // Jawaban pendaftaran dipakai kartu "Business Profile"; isinya
            // dibaca apa adanya dari yang pernah diisi saat mendaftar.
            'answers' => $user->registrationSummary(),
            'notifications' => $user->notifications()->limit(5)->get(),
        ]);
    }
}
