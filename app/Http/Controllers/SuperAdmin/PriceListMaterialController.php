<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrintMaterialRequest;
use App\Models\MachineCost;
use App\Models\PricingFormula;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use App\Support\PriceListPage;
use App\Support\PricingMethod;
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
        return $this->form($technology, new PrintMaterial);
    }

    public function store(StorePrintMaterialRequest $request, PrintTechnology $technology): RedirectResponse
    {
        $material = $technology->materials()->create($request->validated());

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menambahkan material '.$this->label($technology).' "'.$material->material.'" pada Price List.',
            subject: $material,
            new: $this->snapshot($material),
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $material->material,
        );

        return $this->back($technology, "Material {$this->label($technology)} berhasil ditambahkan.");
    }

    public function edit(PrintTechnology $technology, PrintMaterial $material): View
    {
        return $this->form($technology, $this->guard($technology, $material));
    }

    /**
     * Formulir tambah/ubah material.
     *
     * Pilihan mesinnya dibaca langsung dari Machine Cost — tidak ada daftar
     * mesin kedua di mana pun, jadi mesin yang baru didaftarkan di sana
     * langsung muncul di sini tanpa perubahan kode.
     */
    private function form(PrintTechnology $technology, PrintMaterial $material): View
    {
        return view('superadmin.price-list.material.form', [
            'technology' => $technology,
            'material' => $material,
            'machines' => MachineCost::with('technology')->orderBy('mesin')->get(),

            // Rumus Harga Otomatis yang dipakai Kalkulator Otomatis, untuk
            // preview rumusnya.
            'automaticFormula' => PricingMethod::appliesTo($technology->code)
                ? PricingFormula::general()
                : null,
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
            description: 'Memperbarui material '.$this->label($technology).' "'.$material->material.'" pada Price List.',
            subject: $material,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $material->material,
        );

        return $this->back($technology, "Material {$this->label($technology)} berhasil diperbarui.");
    }

    /**
     * Nyalakan atau matikan material dari switch Status pada tabelnya.
     *
     * Aktif berarti tampil sebagai pilihan Material pada Edit Specification;
     * nonaktif berarti tidak tampil dan ditolak pada penawaran baru. Penawaran
     * yang sudah ada tetap memakai material dan harga yang tersimpan.
     */
    public function updateStatus(Request $request, PrintTechnology $technology, PrintMaterial $material): RedirectResponse
    {
        $material = $this->guard($technology, $material);
        $active = $request->boolean('is_active');

        if ($material->is_active !== $active) {
            $material->update(['is_active' => $active]);
            PrintTechnology::forgetCache();

            $this->activity->logChanges(
                action: ActivityAction::PRICE_LIST_UPDATE,
                before: ['status' => $active ? 'Nonaktif' : 'Aktif'],
                after: ['status' => $active ? 'Aktif' : 'Nonaktif'],
                description: ($active ? 'Mengaktifkan' : 'Menonaktifkan').' material '.$this->label($technology).' "'.$material->material.'".',
                subject: $material,
                module: ActivityModule::SUPERADMIN,
                subjectLabel: $material->material,
            );
        }

        // Kembali ke halaman yang sama (pencarian & nomor halaman tetap).
        return redirect()
            ->back(fallback: PriceListPage::technologyUrl($technology))
            ->with('status', 'Material "'.$material->material.'" '.($active
                ? 'diaktifkan dan kini tampil di Edit Specification.'
                : 'dinonaktifkan dan tidak lagi tampil di Edit Specification.'));
    }

    public function destroy(PrintTechnology $technology, PrintMaterial $material): RedirectResponse
    {
        $material = $this->guard($technology, $material);

        $removed = $this->snapshot($material);
        $label = $material->material;

        $material->delete();

        $this->activity->log(
            action: ActivityAction::PRICE_LIST_UPDATE,
            description: 'Menghapus material '.$this->label($technology).' "'.$label.'" dari Price List.',
            old: $removed,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $label,
        );

        return $this->back($technology, "Material {$this->label($technology)} berhasil dihapus.");
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
            description: 'Menghapus '.$materials->count().' material '.$this->label($technology).' dari Price List: '.$labels->implode(', ').'.',
            old: ['materials' => $removed],
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $labels->first(),
        );

        return $this->back($technology, $materials->count().' material '.$this->label($technology).' berhasil dihapus.');
    }

    /** Nama teknologi pada pesan: kodenya, kecuali SLA ("SLAI" hanya kode teknis). */
    private function label(PrintTechnology $technology): string
    {
        return $technology->isSlaIndustries() ? $technology->name : $technology->code;
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
            ->to(PriceListPage::technologyUrl($technology))
            ->with($error !== null ? 'error' : 'status', $error ?? $status);
    }

    /** @return array<string, mixed> */
    private function snapshot(PrintMaterial $material): array
    {
        return [
            'material' => $material->material,
            'mesin' => $material->machine?->mesin,
            'brand' => $material->brand,
            'purchase_price' => (float) $material->purchase_price,
            'sale_price' => (float) $material->sale_price,
            'remark' => $material->remark,
            'status' => $material->isOffered() ? 'Aktif' : 'Nonaktif',
            ...(PricingMethod::appliesTo($material->technology?->code) ? ['pricing_method' => $material->pricing_method] : []),
        ];
    }
}
