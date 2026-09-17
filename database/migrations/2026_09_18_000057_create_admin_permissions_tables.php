<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hak akses menu Admin yang diatur Superadmin per akun.
 *
 *   permissions        daftar hak akses (penawaran, verifikasi_pembayaran, …)
 *   admin_permissions  pemberian hak akses: user_id + permission_id
 *
 * Role (`users.role`) tidak disentuh; ini lapisan tambahan khusus role Admin.
 *
 * Admin yang SUDAH ADA diberi seluruh hak akses, supaya tidak ada yang
 * tiba-tiba kehilangan menu begitu fitur ini terpasang. Superadmin
 * menyesuaikannya kemudian lewat Edit Admin.
 */
return new class extends Migration
{
    /** Salinan daftar saat migrasi ini ditulis; daftar hidup ada di App\Support\AdminPermission. */
    private const PERMISSIONS = [
        'penawaran' => 'Penawaran',
        'verifikasi_pembayaran' => 'Verifikasi Pembayaran',
        'payment_term' => 'Payment Term',
        'notifikasi' => 'Notifikasi',
        'profil' => 'Profil',
        'ganti_password' => 'Ganti Password',
    ];

    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('label', 120);
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });

        $now = now();
        $order = 0;

        foreach (self::PERMISSIONS as $key => $label) {
            DB::table('permissions')->insert([
                'key' => $key,
                'label' => $label,
                'sort_order' => $order += 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->pluck('id');

        foreach (DB::table('users')->where('role', 'admin')->pluck('id') as $userId) {
            DB::table('admin_permissions')->insert($permissionIds->map(fn ($permissionId) => [
                'user_id' => $userId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('permissions');
    }
};
