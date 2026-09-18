<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminAccountRequest;
use App\Http\Requests\UpdateAdminAccountRequest;
use App\Models\Permission;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\AdminPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return view('superadmin.admins.form', [
            'admin' => new User,
            'modules' => $this->permissionModules(),
            'granted' => AdminPermission::defaults(),
        ]);
    }

    public function store(StoreAdminAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $keys = AdminPermission::normalize($data['permissions'] ?? []);
        unset($data['permissions']);

        $admin = DB::transaction(function () use ($data, $keys) {
            // Role selalu ditetapkan di sini, tidak pernah dari kiriman formulir,
            // jadi akun yang dibuat lewat menu ini tidak dapat menjadi Superadmin.
            // Keduanya bukan kolom fillable, jadi dipasang lewat forceFill().
            $admin = (new User($data))->forceFill([
                'role' => User::ROLE_ADMIN,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);
            $admin->save();
            $this->syncPermissions($admin, $keys);

            return $admin;
        });

        $this->activity->log(
            action: ActivityAction::ADMIN_CREATE,
            description: 'Membuat akun admin '.$admin->name.' ('.$admin->email.').',
            subject: $admin,
            // Kata sandi tersaring pencatat sebelum apa pun tersimpan.
            new: [
                ...$admin->only(['name', 'email']),
                'status' => $this->statusLabel($admin->isActive()),
                ...AdminPermission::snapshot($keys),
            ],
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
        $admin = $this->resolveAdmin($admin);

        return view('superadmin.admins.form', [
            'admin' => $admin,
            'modules' => $this->permissionModules(),
            'granted' => $admin->adminPermissionKeys(),
        ]);
    }

    public function update(UpdateAdminAccountRequest $request, User $admin): RedirectResponse
    {
        $admin = $this->resolveAdmin($admin);

        $before = $admin->only(['name', 'email']);
        $wasActive = $admin->isActive();
        $grantedBefore = $admin->adminPermissionKeys();

        $data = $request->validated();
        $keys = AdminPermission::normalize($data['permissions'] ?? []);
        unset($data['permissions']);

        // Kata sandi hanya diganti bila benar-benar diisi: formulir edit
        // sengaja membiarkannya kosong supaya menyunting nama tidak
        // mengharuskan Superadmin menetapkan kata sandi baru.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        DB::transaction(function () use ($admin, $data, $keys) {
            // Status aktif bukan kolom fillable; dipasang eksplisit di sini.
            $admin->fill($data)->forceFill(['is_active' => (bool) ($data['is_active'] ?? false)])->save();

            // Berlaku pada permintaan Admin berikutnya: hak akses selalu
            // dibaca ulang dari basis data, tidak disalin ke session atau
            // cache, jadi tidak ada hak akses lama yang perlu diinvalidasi.
            $this->syncPermissions($admin, $keys);
        });

        $actor = $request->user();

        $this->activity->logChanges(
            action: ActivityAction::ADMIN_UPDATE,
            before: $before,
            after: $admin->only(['name', 'email']),
            description: 'Mengubah akun admin '.$admin->email.'.',
            subject: $admin,
            actor: $actor,
            module: ActivityModule::ADMIN,
            subjectLabel: $admin->email,
        );

        // Status dan hak akses menentukan apa yang boleh dilakukan akun ini,
        // jadi masing-masing dicatat sebagai aktivitas tersendiri yang mudah
        // dicari di Activity Log.
        if ($wasActive !== $admin->isActive()) {
            $this->activity->log(
                action: ActivityAction::ADMIN_STATUS_UPDATE,
                description: 'Mengubah status admin '.$admin->name.': '
                    .$this->statusLabel($wasActive).' → '.$this->statusLabel($admin->isActive()).'.',
                subject: $admin,
                old: ['status' => $this->statusLabel($wasActive)],
                new: ['status' => $this->statusLabel($admin->isActive())],
                actor: $actor,
                module: ActivityModule::ADMIN,
                subjectLabel: $admin->email,
            );
        }

        if ($this->permissionsChanged($grantedBefore, $keys)) {
            $this->activity->log(
                action: ActivityAction::ADMIN_PERMISSION_UPDATE,
                description: 'Mengubah hak akses admin '.$admin->name.' ('.$admin->email.').',
                subject: $admin,
                old: AdminPermission::snapshot($grantedBefore),
                new: AdminPermission::snapshot($keys),
                actor: $actor,
                module: ActivityModule::ADMIN,
                subjectLabel: $admin->email,
            );
        }

        // Penggantian kata sandi tidak terlihat pada perbandingan di atas —
        // kolomnya memang tidak pernah ikut dicatat — jadi dicatat terpisah.
        if (array_key_exists('password', $data)) {
            $this->activity->log(
                action: ActivityAction::ADMIN_PASSWORD_RESET,
                description: 'Menetapkan kata sandi baru untuk akun admin '.$admin->email.'.',
                subject: $admin,
                actor: $actor,
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
     * Simpan hak akses yang menyala untuk akun Admin ini.
     *
     * @param  array<int, string>  $keys  kunci App\Support\AdminPermission
     */
    private function syncPermissions(User $admin, array $keys): void
    {
        $admin->permissions()->sync(Permission::syncDefinitions()->toBase()->only($keys)->pluck('id')->all());
        $admin->unsetRelation('permissions');
    }

    /**
     * Hak akses per modul untuk formulir, dari katalog backend.
     *
     * Baris tabel `permissions` ikut disamakan lebih dulu, jadi hak akses yang
     * baru ditambahkan ke katalog langsung dapat diberikan.
     *
     * @return array<string, array{label: string, permissions: array<string, array{label: string, description: string, default: bool}>}>
     */
    private function permissionModules(): array
    {
        Permission::syncDefinitions();

        return AdminPermission::modules();
    }

    /**
     * @param  array<int, string>  $before
     * @param  array<int, string>  $after
     */
    private function permissionsChanged(array $before, array $after): bool
    {
        return array_diff($before, $after) !== [] || array_diff($after, $before) !== [];
    }

    private function statusLabel(bool $active): string
    {
        return $active ? 'Aktif' : 'Nonaktif';
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
