<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Notifikasi status penawaran lewat WhatsApp.
 *
 *  - `whatsapp_settings`       satu baris: nomor pengirim NUSAMA3D beserta hasil
 *                              pemeriksaan koneksi terakhir. Credential API
 *                              TIDAK disimpan di sini — tempatnya di .env.
 *  - `whatsapp_notifications`  riwayat setiap pesan yang dicoba dikirim.
 *
 * Hak akses menu "Pengaturan WhatsApp" didaftarkan sebagai `whatsapp.view` dan
 * `whatsapp.edit`, dan langsung diberikan kepada seluruh akun Admin yang sudah
 * ada — menu ini memang milik Admin maupun Superadmin, jadi tidak ada Admin
 * yang harus menunggu diberi akses lebih dulu. Superadmin tetap dapat
 * mencabutnya per akun lewat Edit Admin.
 */
return new class extends Migration
{
    /** Salinan saat migrasi ini ditulis; daftar hidup ada di App\Support\AdminPermission. */
    private const PERMISSIONS = [
        'whatsapp.view' => ['whatsapp', 'view', 'Pengaturan WhatsApp › Lihat', 'Membuka menu Pengaturan WhatsApp dan riwayat pengiriman notifikasi.'],
        'whatsapp.edit' => ['whatsapp', 'edit', 'Pengaturan WhatsApp › Edit', 'Mengubah nomor WhatsApp pengirim, memeriksa koneksi, dan uji kirim.'],
    ];

    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();

            // Nomor pengirim dalam format internasional tanpa tanda, mis. 6281234567890.
            $table->string('phone_number', 20)->nullable();

            // Phone Number ID pada WhatsApp Cloud API, dicari otomatis dari nomor di atas.
            $table->string('phone_number_id', 64)->nullable();

            // Hasil pemeriksaan koneksi terakhir: connected / disconnected / unknown.
            $table->string('connection_status', 20)->default('unknown');
            $table->string('connection_message', 500)->nullable();
            $table->timestamp('connection_checked_at')->nullable();

            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('whatsapp_notifications', function (Blueprint $table) {
            $table->id();

            // status_update = perubahan status penawaran, test = Uji Kirim.
            $table->string('type', 20)->default('status_update')->index();

            $table->foreignId('quotation_request_id')->nullable()->constrained('quotation_requests')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Salinan teks supaya riwayat tetap terbaca meski penawarannya dihapus.
            $table->string('tracking_number', 40)->nullable();
            $table->string('customer_name')->nullable();

            $table->string('recipient', 20)->nullable();
            $table->string('sender', 20)->nullable();

            $table->string('previous_status', 40)->nullable();
            $table->string('quotation_status', 40)->nullable();

            $table->text('message');

            // pending / sent / failed
            $table->string('status', 20)->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        DB::table('whatsapp_settings')->insert([
            'connection_status' => 'unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->registerPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('key', array_keys(self::PERMISSIONS))->delete();
        }

        Schema::dropIfExists('whatsapp_notifications');
        Schema::dropIfExists('whatsapp_settings');
    }

    private function registerPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('admin_permissions')) {
            return;
        }

        $now = now();
        $order = (int) DB::table('permissions')->max('sort_order');

        foreach (self::PERMISSIONS as $key => [$module, $action, $label, $description]) {
            if (DB::table('permissions')->where('key', $key)->exists()) {
                continue;
            }

            DB::table('permissions')->insert([
                'key' => $key,
                'module' => $module,
                'action' => $action,
                'label' => $label,
                'description' => $description,
                'sort_order' => $order += 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $ids = DB::table('permissions')->whereIn('key', array_keys(self::PERMISSIONS))->pluck('id');

        foreach (DB::table('users')->where('role', 'admin')->pluck('id') as $userId) {
            foreach ($ids as $permissionId) {
                DB::table('admin_permissions')->insertOrIgnore([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
