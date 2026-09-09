<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTermSetting;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Menu "Pengaturan Payment Term".
 *
 * Batas nominal dan pilihan cicilan yang boleh dipakai pelanggan diatur di
 * sini, bukan di kode maupun frontend, sehingga admin dapat menyesuaikannya
 * kapan saja tanpa perubahan program.
 */
class PaymentTermSettingController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function edit(): View
    {
        return view('admin.payment-terms.settings', [
            'settings' => PaymentTermSetting::query()->ordered()->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.enabled' => ['nullable', 'boolean'],
            'settings.*.minimum_amount' => ['required', 'numeric', 'min:0', 'max:99999999999'],
        ], [
            'settings.*.minimum_amount.required' => 'Batas nominal minimum tiap pilihan wajib diisi.',
            'settings.*.minimum_amount.numeric' => 'Batas nominal minimum harus berupa angka.',
        ]);

        // Seluruh pilihan dicatat sebagai satu perubahan konfigurasi, bukan
        // satu baris log per pilihan: yang disimpan admin memang satu formulir.
        $before = [];
        $after = [];

        foreach (PaymentTermSetting::query()->ordered()->get() as $setting) {
            $row = $validated['settings'][$setting->id] ?? null;

            if ($row === null) {
                continue;
            }

            $key = $setting->installment_count.'x';

            $before[$key.' aktif'] = $setting->enabled ? 'Ya' : 'Tidak';
            $before[$key.' minimum'] = $setting->minimum_amount;

            $setting->update([
                // Kotak centang yang tidak dicentang tidak ikut terkirim.
                'enabled' => (bool) ($row['enabled'] ?? false),
                'minimum_amount' => (float) $row['minimum_amount'],
            ]);

            $after[$key.' aktif'] = $setting->enabled ? 'Ya' : 'Tidak';
            $after[$key.' minimum'] = $setting->minimum_amount;
        }

        $this->activity->logChanges(
            action: ActivityAction::SETTING_UPDATE,
            before: $before,
            after: $after,
            description: 'Mengubah pengaturan payment term.',
            actor: $request->user(),
            module: ActivityModule::ADMIN,
            subjectLabel: 'Pengaturan Payment Term',
        );

        return back()->with('status', 'Pengaturan payment term berhasil disimpan.');
    }
}
