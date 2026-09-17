<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hak akses Admin dipecah dari satu switch per menu menjadi `<modul>.<aksi>`.
 *
 *   penawaran  →  quotation.view, quotation.create, quotation.edit, …
 *
 * Tabel yang sama tetap dipakai (`permissions` + `admin_permissions`); yang
 * ditambah hanya kolom `module` dan `action`. Admin yang sudah memegang suatu
 * menu diberi SELURUH aksi pada menu itu, jadi tidak ada Admin yang tiba-tiba
 * kehilangan akses begitu migrasi ini berjalan. Superadmin menyempitkannya
 * kemudian lewat Edit Admin. Menu User sebelumnya khusus Superadmin, jadi
 * hak aksesnya tidak diberikan otomatis.
 */
return new class extends Migration
{
    /** Salinan daftar saat migrasi ini ditulis; daftar hidup ada di App\Support\AdminPermission. */
    private const PERMISSIONS = [
        'quotation.view' => ['quotation', 'view', 'Penawaran › Lihat'],
        'quotation.create' => ['quotation', 'create', 'Penawaran › Buat'],
        'quotation.edit' => ['quotation', 'edit', 'Penawaran › Edit'],
        'quotation.delete' => ['quotation', 'delete', 'Penawaran › Hapus'],
        'quotation.update_status' => ['quotation', 'update_status', 'Penawaran › Update Status'],
        'payment.view' => ['payment', 'view', 'Verifikasi Pembayaran › Lihat'],
        'payment.verify' => ['payment', 'verify', 'Verifikasi Pembayaran › Verifikasi'],
        'payment_term.view' => ['payment_term', 'view', 'Payment Term › Lihat'],
        'payment_term.edit' => ['payment_term', 'edit', 'Payment Term › Edit'],
        'notification.view' => ['notification', 'view', 'Notifikasi › Lihat'],
        'user.view' => ['user', 'view', 'User › Lihat'],
        'user.edit' => ['user', 'edit', 'User › Edit'],
        'user.delete' => ['user', 'delete', 'User › Hapus'],
        'profile.edit' => ['profile', 'edit', 'Profil › Edit'],
        'profile.security' => ['profile', 'security', 'Profil › Ganti Password'],
    ];

    /** Menu lama => hak akses baru yang menggantikannya. */
    private const LEGACY = [
        'penawaran' => ['quotation.view', 'quotation.create', 'quotation.edit', 'quotation.delete', 'quotation.update_status'],
        'verifikasi_pembayaran' => ['payment.view', 'payment.verify'],
        'payment_term' => ['payment_term.view', 'payment_term.edit'],
        'notifikasi' => ['notification.view'],
        'profil' => ['profile.edit'],
        'ganti_password' => ['profile.security'],
    ];

    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('module', 60)->nullable()->after('key')->index();
            $table->string('action', 60)->nullable()->after('module');
        });

        $now = now();
        $order = 0;

        foreach (self::PERMISSIONS as $key => [$module, $action, $label]) {
            $values = ['module' => $module, 'action' => $action, 'label' => $label, 'sort_order' => $order += 10, 'updated_at' => $now];

            if (DB::table('permissions')->where('key', $key)->exists()) {
                DB::table('permissions')->where('key', $key)->update($values);
            } else {
                DB::table('permissions')->insert(['key' => $key, 'created_at' => $now, ...$values]);
            }
        }

        $ids = DB::table('permissions')->pluck('id', 'key');

        foreach (self::LEGACY as $legacyKey => $newKeys) {
            $legacyId = $ids[$legacyKey] ?? null;

            if ($legacyId === null) {
                continue;
            }

            foreach (DB::table('admin_permissions')->where('permission_id', $legacyId)->pluck('user_id') as $userId) {
                foreach ($newKeys as $newKey) {
                    DB::table('admin_permissions')->insertOrIgnore([
                        'user_id' => $userId,
                        'permission_id' => $ids[$newKey],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // Pemberian lama ikut terhapus lewat cascade.
        DB::table('permissions')->whereIn('key', array_keys(self::LEGACY))->delete();
    }

    public function down(): void
    {
        $now = now();
        $order = 0;

        foreach (array_keys(self::LEGACY) as $legacyKey) {
            DB::table('permissions')->insertOrIgnore([
                'key' => $legacyKey,
                'label' => ucwords(str_replace('_', ' ', $legacyKey)),
                'sort_order' => $order += 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $ids = DB::table('permissions')->pluck('id', 'key');

        // Menu lama dipegang bila Admin memegang hak MELIHAT menu itu.
        foreach (self::LEGACY as $legacyKey => $newKeys) {
            foreach (DB::table('admin_permissions')->where('permission_id', $ids[$newKeys[0]] ?? 0)->pluck('user_id') as $userId) {
                DB::table('admin_permissions')->insertOrIgnore([
                    'user_id' => $userId,
                    'permission_id' => $ids[$legacyKey],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('permissions')->whereIn('key', array_keys(self::PERMISSIONS))->delete();

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['module']);
            $table->dropColumn(['module', 'action']);
        });
    }
};
