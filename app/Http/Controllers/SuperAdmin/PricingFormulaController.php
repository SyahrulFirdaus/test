<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePricingFormulaRequest;
use App\Models\PricingFormula;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Http\RedirectResponse;

/**
 * Rumus Harga Otomatis pada Price List — satu rumus untuk seluruh teknologi.
 *
 * Tidak ada tambah/hapus; admin hanya mengubah parameternya. Nilainya dipakai
 * Kalkulator Otomatis (App\Services\SellingPriceEstimator) untuk penawaran
 * berikutnya; penawaran lama tetap memakai rincian yang sudah tersimpan.
 */
class PricingFormulaController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /** Simpan Rumus Harga Otomatis yang berlaku umum untuk seluruh teknologi. */
    public function update(UpdatePricingFormulaRequest $request): RedirectResponse
    {
        $formula = PricingFormula::general();

        $before = $this->snapshot($formula);

        $formula->update($request->validated());

        $this->activity->logChanges(
            action: ActivityAction::PRICE_LIST_UPDATE,
            before: $before,
            after: $this->snapshot($formula->refresh()),
            description: 'Memperbarui Rumus Harga Otomatis pada Price List.',
            subject: $formula,
            subjectLabel: 'Rumus Harga Otomatis',
        );

        return redirect()
            ->route('superadmin.price-list.harga')
            ->with('status', 'Rumus Harga Otomatis berhasil diperbarui.');
    }

    /** @return array<string, mixed> */
    private function snapshot(PricingFormula $formula): array
    {
        return [
            'machine_time_hours' => (float) $formula->machine_time_hours,
            'machine_cost' => (float) $formula->machine_cost,
            'material_qty_g' => (float) $formula->material_qty_g,
            'material_price_per_g' => (float) $formula->material_price_per_g,
            'risk_percent' => (float) $formula->risk_percent,
            'packaging_cost' => (float) $formula->packaging_cost,
            'overtime_cost' => (float) $formula->overtime_cost,
            'profit_percent' => (float) $formula->profit_percent,
            'object_size_mm' => (float) $formula->object_size_mm,
        ];
    }
}
