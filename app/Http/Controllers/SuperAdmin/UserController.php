<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\CustomerType;
use App\Support\QuotationStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manajemen pengguna.
 *
 * Admin dapat melihat seluruh pelanggan beserta riwayat penawaran tiap akun.
 * Halaman ini sengaja hanya membaca — perubahan data akun tetap dilakukan
 * pemiliknya sendiri lewat menu Profil.
 *
 * Satu-satunya pengecualian adalah destroy(): menghapus akun pelanggan, khusus
 * Superadmin (route-nya hanya ada di grup `superadmin`).
 */
class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

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

    /**
     * Hapus satu akun pelanggan.
     *
     * Yang terhapus mengikuti aturan basis data yang sudah ada: alamat,
     * jawaban pendaftaran, dan profil bisnisnya ikut terhapus (cascade),
     * sedangkan penawarannya TETAP ada — `quotation_requests.user_id` menjadi
     * kosong (nullOnDelete) — sehingga riwayat penawaran dan pembayaran tidak
     * hilang. Baris Activity Log miliknya juga dibiarkan.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Middleware `superadmin` sudah menjaga route-nya; diperiksa lagi di
        // sini supaya tetap aman bila route ini suatu saat dipindahkan.
        abort_unless($request->user()?->isSuperAdmin(), 403);

        // Hanya akun pelanggan. Akun Admin dikelola lewat menu Akun Admin,
        // dan akun Superadmin tidak pernah dapat dihapus dari sini.
        abort_if($user->isAdmin(), 404);

        $removed = [
            'name' => $user->name,
            'email' => $user->email,
            'customer_type' => $user->customer_type,
            'phone' => $user->phone,
            'penawaran' => $user->quotationRequests()->count(),
        ];

        $user->delete();

        $this->activity->log(
            action: ActivityAction::USER_DELETE,
            description: 'Menghapus user '.$removed['name'].' ('.$removed['email'].').',
            old: $removed,
            actor: $request->user(),
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $removed['email'],
        );

        // Kembali ke daftar dengan pencarian, filter, dan halaman yang sama —
        // tetapi tidak ke halaman Detail user yang baru saja dihapus.
        $index = route('superadmin.users.index');
        $previous = url()->previous();
        $target = $previous === $index || str_starts_with($previous, $index.'?') ? $previous : $index;

        return redirect()->to($target)->with('status', 'User berhasil dihapus.');
    }
}
