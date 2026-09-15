<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePricingFormulaRequest;
use App\Models\PricingFormula;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Http\RedirectResponse;

/**
 * Parameter rumus Harga Jual per teknologi, tab "Harga" pada Price List.
 *
 * Hanya empat baris tetap (FDM/SLA/MJF/SLM, dibuat lewat migrasi) — tidak ada
 * tambah/hapus, admin hanya mengubah parameternya. Murni referensi/simulasi:
 * TIDAK menyentuh PrintEstimator, Calculator, atau Quotation manapun.
 */
class PricingFormulaController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function update(UpdatePricingFormulaRequest $request, string $technology): RedirectResponse
    {
        $formula = PricingFormula::where('technology', $technology)->firstOrFail();

        $before = $this->snapshot($formula);

        $formula->update($request->validated());

        $this->activity->logChanges(
            action: ActivityAction::PRICE_LIST_UPDATE,
            before: $before,
            after: $this->snapshot($formula->refresh()),
            description: 'Memperbarui rumus Harga Jual '.$technology.' pada Price List.',
            subject: $formula,
            subjectLabel: 'Rumus Harga '.$technology,
        );

        return redirect()
            ->route('superadmin.price-list.index', ['tab' => 'harga', 'formula' => $technology])
            ->with('status', 'Rumus Harga '.$technology.' berhasil diperbarui.');
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
