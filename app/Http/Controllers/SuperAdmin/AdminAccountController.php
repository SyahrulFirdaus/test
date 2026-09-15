<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminAccountRequest;
use App\Http\Requests\UpdateAdminAccountRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Pengelolaan akun Admin oleh Superadmin.
 *
 * Akun yang dibuat di sini langsung dapat dipakai masuk ke Dashboard Admin —
 * tidak ada langkah aktivasi tersendiri; kata sandinya di-hash seperti akun
 * lain dan tidak pernah dapat dibaca kembali, termasuk oleh Superadmin.
 *
 * Dua batas yang dijaga dengan sengaja:
 *
 * 1. Hanya akun ber-role `admin` yang dapat disentuh. Route model binding
 *    diberi pagar tambahan di `resolveAdmin()` sehingga id milik pelanggan
 *    atau sesama Superadmin tidak dapat diselipkan lewat URL.
 *
 * 2. Menonaktifkan lebih disukai daripada menghapus. Akun nonaktif kehilangan
 *    aksesnya tetapi jejak aktivitasnya tetap utuh — itulah yang membuat
 *    Activity Log masih dapat dibaca setelahnya.
 */
class AdminAccountController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $admins = User::query()
            ->plainAdmins()
            ->search($request->query('q'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.admins.index', [
            'admins' => $admins,
            'filters' => ['q' => $request->query('q')],
            'summary' => [
                'total' => User::plainAdmins()->count(),
                'active' => User::plainAdmins()->where('is_active', true)->count(),
                'inactive' => User::plainAdmins()->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('superadmin.admins.form', ['admin' => new User]);
    }

    public function store(StoreAdminAccountRequest $request): RedirectResponse
    {
        $admin = User::create([
            ...$request->validated(),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->activity->log(
            action: ActivityAction::ADMIN_CREATE,
            description: 'Membuat akun admin '.$admin->name.' ('.$admin->email.').',
            subject: $admin,
            // Kata sandi tersaring pencatat sebelum apa pun tersimpan.
            new: $admin->only(['name', 'email', 'is_active']),
            actor: $request->user(),
            module: ActivityModule::ADMIN,
            subjectLabel: $admin->email,
        );

        return redirect()
            ->route('superadmin.admins.index')
            ->with('status', "Akun admin {$admin->name} berhasil dibuat.");
    }

    public function edit(User $admin): View
    {
        return view('superadmin.admins.form', ['admin' => $this->resolveAdmin($admin)]);
    }

    public function update(UpdateAdminAccountRequest $request, User $admin): RedirectResponse
    {
        $admin = $this->resolveAdmin($admin);
        $before = $admin->only(['name', 'email', 'is_active']);

        $data = $request->validated();

        // Kata sandi hanya diganti bila benar-benar diisi: formulir edit
        // sengaja membiarkannya kosong supaya menyunting nama tidak
        // mengharuskan Superadmin menetapkan kata sandi baru.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $admin->update($data);

        $this->activity->logChanges(
            action: ActivityAction::ADMIN_UPDATE,
            before: $before,
            after: $admin->only(['name', 'email', 'is_active']),
            description: 'Mengubah akun admin '.$admin->email.'.',
            subject: $admin,
            actor: $request->user(),
            module: ActivityModule::ADMIN,
            subjectLabel: $admin->email,
        );

        // Penggantian kata sandi tidak terlihat pada perbandingan di atas —
        // kolomnya memang tidak pernah ikut dicatat — jadi dicatat terpisah.
        if (array_key_exists('password', $data)) {
            $this->activity->log(
                action: ActivityAction::ADMIN_PASSWORD_RESET,
                description: 'Menetapkan kata sandi baru untuk akun admin '.$admin->email.'.',
                subject: $admin,
                actor: $request->user(),
                module: ActivityModule::ADMIN,
                subjectLabel: $admin->email,
            );
        }

        return redirect()
            ->route('superadmin.admins.index')
            ->with('status', "Akun admin {$admin->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $admin): RedirectResponse
    {
        $admin = $this->resolveAdmin($admin);

        $removed = $admin->only(['name', 'email', 'is_active']);
        $name = $admin->name;

        // Baris Activity Log miliknya sengaja dibiarkan: `user_name` sudah
        // dibekukan saat dicatat, jadi riwayatnya tetap terbaca setelah
        // akunnya hilang.
        $admin->delete();

        $this->activity->log(
            action: ActivityAction::ADMIN_DELETE,
            description: 'Menghapus akun admin '.$removed['name'].' ('.$removed['email'].').',
            old: $removed,
            actor: $request->user(),
            module: ActivityModule::ADMIN,
            subjectLabel: $removed['email'],
        );

        return redirect()
            ->route('superadmin.admins.index')
            ->with('status', "Akun admin {$name} berhasil dihapus.");
    }

    /**
     * Pastikan yang dikelola benar-benar akun Admin.
     *
     * Superadmin dan pelanggan berada di luar jangkauan menu ini: akun
     * Superadmin diurus lewat menu Profil dan Ganti Password miliknya sendiri,
     * pelanggan lewat menu User.
     */
    private function resolveAdmin(User $admin): User
    {
        abort_unless($admin->isPlainAdmin(), 404);

        return $admin;
    }
}
