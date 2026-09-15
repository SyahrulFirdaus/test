<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrintMaterialRequest;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD material Price List untuk teknologi APA PUN.
 *
 * Menggantikan sepasang controller khusus FDM/SLA: begitu teknologi dapat
 * ditambah Superadmin, satu controller per teknologi jelas tidak lagi mungkin.
 * Teknologinya datang sebagai parameter route, jadi teknologi ke-lima dan
 * seterusnya langsung terlayani tanpa kode baru.
 *
 * Material yang dihapus tidak menyentuh penawaran lama: nama materialnya sudah
 * dibekukan pada `quotation_items.material` dan harganya tersimpan di
 * `cost_breakdown`, jadi penawaran yang sudah jalan tetap terbaca dan tidak
 * pernah terhitung ulang.
 */
class PriceListMaterialController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function create(PrintTechnology $technology): View
    {
        return view('superadmin.price-list.material.form', [
            'technology' => $technology,
            'material' => new PrintMaterial,
        ]);
    }

    public function store(StorePrintMaterialRequest $request, PrintTechnology $technology): RedirectResponse
    {
        $material = $technology->materials()->create($request->validated());

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menambahkan material '.$technology->code.' "'.$material->material.'" pada Price List.',
            subject: $material,
            new: $this->snapshot($material),
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $material->material,
        );

        return $this->back($technology, "Material {$technology->code} berhasil ditambahkan.");
    }

    public function edit(PrintTechnology $technology, PrintMaterial $material): View
    {
        return view('superadmin.price-list.material.form', [
            'technology' => $technology,
            'material' => $this->guard($technology, $material),
        ]);
    }

    public function update(StorePrintMaterialRequest $request, PrintTechnology $technology, PrintMaterial $material): RedirectResponse
    {
        $material = $this->guard($technology, $material);
        $before = $this->snapshot($material);

        $material->update($request->validated());

        $this->activity->logChanges(
            action: ActivityAction::PRICE_LIST_UPDATE,
            before: $before,
            after: $this->snapshot($material->refresh()),
            description: 'Memperbarui material '.$technology->code.' "'.$material->material.'" pada Price List.',
            subject: $material,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $material->material,
        );

        return $this->back($technology, "Material {$technology->code} berhasil diperbarui.");
    }

    public function destroy(PrintTechnology $technology, PrintMaterial $material): RedirectResponse
    {
        $material = $this->guard($technology, $material);

        $removed = $this->snapshot($material);
        $label = $material->material;

        $material->delete();

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menghapus material '.$technology->code.' "'.$label.'" dari Price List.',
            old: $removed,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $label,
        );

        return $this->back($technology, "Material {$technology->code} berhasil dihapus.");
    }

    /**
     * Hapus beberapa material sekaligus.
     *
     * Tiga hal yang dijaga:
     *
     * 1. Yang dihapus hanya baris MILIK teknologi pada route-nya. Id dari tab
     *    lain yang diselipkan ke formulir karena itu tidak berpengaruh.
     * 2. Seluruhnya berjalan dalam satu transaksi, jadi tidak mungkin separuh
     *    terhapus lalu separuh lagi gagal.
     * 3. Jejaknya dicatat SATU baris berisi daftar yang terhapus, bukan satu
     *    baris per material.
     */
    public function destroyMany(Request $request, PrintTechnology $technology): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Pilih dulu material yang ingin dihapus.',
        ]);

        $materials = $technology->materials()->whereKey($validated['ids'])->get();

        if ($materials->isEmpty()) {
            return $this->back($technology, null, 'Material yang dipilih sudah tidak ada.');
        }

        $removed = $materials->map(fn (PrintMaterial $material) => $this->snapshot($material))->all();
        $labels = $materials->pluck('material');

        DB::transaction(fn () => PrintMaterial::whereKey($materials->modelKeys())->delete());

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menghapus '.$materials->count().' material '.$technology->code.' dari Price List: '.$labels->implode(', ').'.',
            old: ['materials' => $removed],
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $labels->first(),
        );

        return $this->back($technology, $materials->count().' material '.$technology->code.' berhasil dihapus.');
    }

    /** Material milik teknologi lain tidak dapat disentuh lewat URL tab ini. */
    private function guard(PrintTechnology $technology, PrintMaterial $material): PrintMaterial
    {
        abort_unless($material->print_technology_id === $technology->getKey(), 404);

        return $material;
    }

    private function back(PrintTechnology $technology, ?string $status, ?string $error = null): RedirectResponse
    {
        return redirect()
            ->route('superadmin.price-list.index', ['tab' => $technology->tabKey()])
            ->with($error !== null ? 'error' : 'status', $error ?? $status);
    }

    /** @return array<string, mixed> */
    private function snapshot(PrintMaterial $material): array
    {
        return [
            'material' => $material->material,
            'brand' => $material->brand,
            'purchase_price' => (float) $material->purchase_price,
            'sale_price' => (float) $material->sale_price,
            'remark' => $material->remark,
        ];
    }
}
