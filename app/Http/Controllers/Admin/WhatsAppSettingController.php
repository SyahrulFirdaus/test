<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppNotification;
use App\Models\WhatsAppSetting;
use App\Services\ActivityLogger;
use App\Services\WhatsApp\WhatsAppNotifier;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\WhatsAppNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Menu "Pengaturan WhatsApp".
 *
 * Nomor WhatsApp pengirim NUSAMA3D diatur di sini, sehingga pergantian nomor
 * tidak menuntut perubahan kode. Credential API (access token, WABA ID) tidak
 * pernah melewati halaman ini — tempatnya di .env, dibaca config/whatsapp.php.
 *
 * Dipakai bersama Admin dan Superadmin lewat $staffRoutes; tautan dibangun
 * dengan staff_route() supaya masing-masing tetap di wilayahnya sendiri.
 */
class WhatsAppSettingController extends Controller
{
    public function __construct(
        private readonly WhatsAppNotifier $notifier,
        private readonly ActivityLogger $activity,
    ) {}

    public function edit(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.whatsapp.settings', [
            'setting' => WhatsAppSetting::current(),
            'driver' => config('whatsapp.driver'),
            'usesTemplate' => filled(config('whatsapp.templates.status')),
            'logs' => WhatsAppNotification::query()
                ->with('triggeredBy:id,name')
                ->status($status)
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'logStatuses' => WhatsAppNotification::statuses(),
            'logFilter' => array_key_exists((string) $status, WhatsAppNotification::statuses()) ? $status : null,
            'summary' => WhatsAppNotification::query()
                ->where('type', WhatsAppNotification::TYPE_STATUS_UPDATE)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{8,32}$/'],
        ], [
            'phone_number.required' => 'Nomor WhatsApp wajib diisi.',
            'phone_number.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, tanda +, -, dan tanda kurung.',
        ]);

        $number = WhatsAppNumber::normalize($validated['phone_number']);

        if ($number === null) {
            return back()->withInput()->withErrors([
                'phone_number' => 'Nomor WhatsApp tidak valid. Gunakan format internasional, mis. 6281234567890.',
            ]);
        }

        $setting = WhatsAppSetting::current();
        $before = $setting->phone_number;

        if ($before !== $number) {
            // Nomor baru berarti Phone Number ID dan status koneksi lama tidak lagi berlaku.
            $setting->forceFill([
                'phone_number' => $number,
                'phone_number_id' => null,
                'connection_status' => WhatsAppSetting::UNKNOWN,
                'connection_message' => null,
                'connection_checked_at' => null,
                'updated_by' => $request->user()?->id,
            ])->save();

            $this->activity->logChanges(
                action: ActivityAction::SETTING_UPDATE,
                before: ['nomor_whatsapp' => $before],
                after: ['nomor_whatsapp' => $number],
                description: 'Mengubah nomor WhatsApp pengirim notifikasi.',
                actor: $request->user(),
                module: ActivityModule::ADMIN,
                subjectLabel: 'Pengaturan WhatsApp',
            );
        }

        // Koneksi langsung diperiksa supaya admin tahu nomor barunya siap dipakai.
        $setting = $this->notifier->checkConnection($setting);

        return back()->with(
            $setting->isConnected() ? 'status' : 'error',
            'Nomor WhatsApp disimpan. Status koneksi: '.$setting->connection_label.'.'
                .($setting->isConnected() ? '' : ' '.$setting->connection_message)
        );
    }

    public function check(): RedirectResponse
    {
        $setting = $this->notifier->checkConnection(WhatsAppSetting::current());

        return back()->with(
            $setting->isConnected() ? 'status' : 'error',
            'Status koneksi: '.$setting->connection_label.'. '.$setting->connection_message
        );
    }

    public function test(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_number' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{8,32}$/'],
        ], [
            'test_number.required' => 'Nomor tujuan wajib diisi.',
            'test_number.regex' => 'Nomor tujuan hanya boleh berisi angka, spasi, tanda +, -, dan tanda kurung.',
        ]);

        $recipient = WhatsAppNumber::normalize($validated['test_number']);

        if ($recipient === null) {
            return back()->withInput()->withErrors([
                'test_number' => 'Nomor tujuan tidak valid. Gunakan format internasional, mis. 628xxxxxxxxxx.',
            ]);
        }

        $log = $this->notifier->sendTest($recipient, $request->user());

        return $log->failed()
            ? back()->withInput()->with('error', 'Pesan WhatsApp gagal dikirim. Silakan periksa konfigurasi WhatsApp. ('.$log->error_message.')')
            : back()->with('status', 'Pesan WhatsApp berhasil dikirim.');
    }
}
