<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSlaIndustriesFormulaRequest;
use App\Models\SlaIndustriesFormula;
use App\Services\ActivityLogger;
use App\Services\UsdRate;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use Illuminate\Http\RedirectResponse;

/**
 * Parameter bawaan Rumus Harga SLA (Kalkulator Manual) pada Price List.
 *
 * Hanya satu aksi: menyimpan. Barisnya tunggal dan sudah ada sejak migrasi,
 * jadi tidak ada tambah maupun hapus di sini — persis seperti tab "Harga" yang
 * juga hanya menyunting baris yang sudah tersedia.
 *
 * Yang disunting di sini TIDAK menggeser penawaran yang harganya sudah
 * ditetapkan: parameter tersalin ke App\Models\SlaIndustriesQuote milik tiap
 * model saat Admin menyimpan perhitungannya.
 */
class SlaIndustriesFormulaController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function update(StoreSlaIndustriesFormulaRequest $request, UsdRate $rates): RedirectResponse
    {
        $formula = SlaIndustriesFormula::current();
        $before = $this->snapshot($formula);

        // Kurs diambil server, bukan dari kiriman formulir. Pada baris bawaan
        // ini nilainya sekadar catatan kurs terakhir yang berlaku — yang
        // menentukan harga penawaran tetap kurs saat perhitungannya disimpan.
        $rate = $rates->current();

        $formula->update([
            ...$request->validated(),
            ...($rate['rate'] === null ? [] : ['usd_rate' => $rate['rate']]),
        ]);
        $formula->forgetComputed();

        $this->activity->logChanges(
            action: ActivityAction::PRICE_LIST_UPDATE,
            before: $before,
            after: $this->snapshot($formula->refresh()),
            description: 'Memperbarui parameter bawaan Rumus Harga SLA (Kalkulator Manual).',
            subject: $formula,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: 'SLA',
        );

        return redirect()
            ->route('superadmin.price-list.harga-manual')
            ->with('status', 'Rumus Harga Manual berhasil disimpan.');
    }

    /** @return array<string, mixed> */
    private function snapshot(SlaIndustriesFormula $formula): array
    {
        return [
            'usd_rate' => (float) $formula->usd_rate,
            'jlc_price_usd' => (float) $formula->jlc_price_usd,
            'jlc_shipping_usd' => (float) $formula->jlc_shipping_usd,
            'customs_idr' => (float) $formula->customs_idr,
            'margin_percent' => (float) $formula->margin_percent,
        ];
    }
}
