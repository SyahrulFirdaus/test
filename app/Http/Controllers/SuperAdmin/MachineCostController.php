<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMachineCostRequest;
use App\Models\MachineCost;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** CRUD Machine Cost pada Price List — belum dipakai menghitung biaya Calculator. */
class MachineCostController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function create(): View
    {
        return view('superadmin.price-list.machine-cost.form', ['machineCost' => new MachineCost]);
    }

    public function store(StoreMachineCostRequest $request): RedirectResponse
    {
        $machine = MachineCost::create($request->validated());

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menambahkan mesin "'.$machine->mesin.'" pada Price List.',
            subject: $machine,
            new: $this->snapshot($machine),
            subjectLabel: $machine->mesin,
        );

        return redirect()
            ->route('superadmin.price-list.index')
            ->with('status', 'Machine Cost berhasil ditambahkan.');
    }

    public function edit(MachineCost $machineCost): View
    {
        return view('superadmin.price-list.machine-cost.form', ['machineCost' => $machineCost]);
    }

    public function update(StoreMachineCostRequest $request, MachineCost $machineCost): RedirectResponse
    {
        $before = $this->snapshot($machineCost);

        $machineCost->update($request->validated());

        $this->activity->logChanges(
            action: ActivityAction::PRICE_LIST_UPDATE,
            before: $before,
            after: $this->snapshot($machineCost->refresh()),
            description: 'Memperbarui mesin "'.$machineCost->mesin.'" pada Price List.',
            subject: $machineCost,
            subjectLabel: $machineCost->mesin,
        );

        return redirect()
            ->route('superadmin.price-list.index')
            ->with('status', 'Machine Cost berhasil diperbarui.');
    }

    public function destroy(MachineCost $machineCost): RedirectResponse
    {
        $removed = $this->snapshot($machineCost);
        $label = $machineCost->mesin;

        $machineCost->delete();

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menghapus mesin "'.$label.'" dari Price List.',
            old: $removed,
            subjectLabel: $label,
        );

        return redirect()
            ->route('superadmin.price-list.index')
            ->with('status', 'Machine Cost berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function snapshot(MachineCost $machine): array
    {
        return [
            'mesin' => $machine->mesin,
            'watt_kwh' => (float) $machine->watt_kwh,
            'harga_listrik' => (float) $machine->harga_listrik,
            'depresiasi' => (float) $machine->depresiasi,
        ];
    }
}
