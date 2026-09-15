<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Support\CustomerType;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Manajemen pengguna.
 *
 * Admin dapat melihat seluruh pelanggan beserta riwayat penawaran tiap akun.
 * Halaman ini sengaja hanya membaca — perubahan data akun tetap dilakukan
 * pemiliknya sendiri lewat menu Profil.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->query('type');

        $users = User::query()
            ->customers()
            ->search($request->query('q'))
            ->ofCustomerType($type)
            ->withCount('quotationRequests')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.users.index', [
            'users' => $users,
            'filters' => [
                'q' => $request->query('q'),
                'type' => CustomerType::exists($type) ? $type : null,
            ],
            'customerTypes' => CustomerType::options(),
            'summary' => [
                'total' => User::customers()->count(),
                'with_quotations' => User::customers()->has('quotationRequests')->count(),
                'new_this_month' => User::customers()->where('created_at', '>=', now()->startOfMonth())->count(),
                'personal' => User::customers()->where('customer_type', CustomerType::PERSONAL)->count(),
                'business' => User::customers()->where('customer_type', CustomerType::BUSINESS)->count(),
            ],
        ]);
    }

    public function show(User $user): View
    {
        abort_if($user->isAdmin(), 404);

        $quotations = $user->quotationRequests()
            ->withCount('items')
            ->paginate(10);

        return view('superadmin.users.show', [
            'user' => $user,
            'quotations' => $quotations,
            // Jawaban yang diisi pemiliknya saat mendaftar, sudah dirangkum
            // satu baris per pertanyaan dan dikelompokkan per langkah.
            'registration' => $user->registrationSummary()->groupBy('step_label'),
            'summary' => [
                'total' => $user->quotationRequests()->count(),
                'completed' => $user->quotationRequests()->where('status', QuotationStatus::COMPLETED)->count(),
                'value' => (float) QuotationRequest::query()->ownedBy($user)->sum('estimated_cost'),
            ],
        ]);
    }
}
