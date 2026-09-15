<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrintTechnologyRequest;
use App\Models\PrintTechnology;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\ActivityModule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pengelolaan teknologi cetak oleh Superadmin.
 *
 * Menambah satu teknologi di sini langsung berakibat tiga hal, tanpa satu
 * baris kode pun berubah:
 *
 *   1. muncul tab barunya sendiri pada Price List, siap diisi material;
 *   2. muncul sebagai pilihan Technology pada Edit Specification;
 *   3. muncul baris parameternya pada tab Harga — dibuat otomatis oleh
 *      App\Models\PrintTechnology::booted().
 *
 * Penghapusan dijaga ketat: teknologi yang sudah dipakai penawaran tidak boleh
 * hilang, karena `quotation_items.technology` menunjuk ke kodenya dan riwayat
 * penawaran akan kehilangan artinya.
 */
class PrintTechnologyController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function create(): View
    {
        return view('superadmin.price-list.technology.form', [
            'technology' => new PrintTechnology([
                // Nilai awal yang masuk akal untuk teknologi berbasis filamen;
                // seluruhnya tetap dapat diubah sebelum disimpan.
                'build_volume_x' => 220,
                'build_volume_y' => 220,
                'build_volume_z' => 250,
                'shell_ratio' => 0.25,
                'default_infill' => 0.2,
                'min_wall_thickness_mm' => 0.8,
                'support_volume_factor' => 0.15,
                'layer_height_min' => 0.1,
                'layer_height_max' => 0.3,
                'throughput_cm3_per_hour' => 15,
                'setup_hours' => 0.5,
                'setup_fee' => 25000,
                'machine_rate_per_hour' => 15000,
                'sort_order' => (PrintTechnology::max('sort_order') ?? 0) + 10,
            ]),
        ]);
    }

    public function store(StorePrintTechnologyRequest $request): RedirectResponse
    {
        $technology = PrintTechnology::create($request->validated());

        $this->activity->log(
            action: ActivityAction::TECHNOLOGY_CREATE,
            description: 'Menambahkan teknologi '.$technology->code.' ('.$technology->name.') pada Price List.',
            subject: $technology,
            new: $this->snapshot($technology),
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $technology->code,
        );

        return redirect()
            ->route('superadmin.price-list.index', ['tab' => $technology->tabKey()])
            ->with('status', "Teknologi {$technology->code} berhasil ditambahkan. Tambahkan materialnya di tab ini.");
    }

    public function edit(PrintTechnology $technology): View
    {
        return view('superadmin.price-list.technology.form', ['technology' => $technology]);
    }

    public function update(StorePrintTechnologyRequest $request, PrintTechnology $technology): RedirectResponse
    {
        $before = $this->snapshot($technology);
        $data = $request->validated();

        // Kode yang sudah dipakai penawaran tidak boleh bergeser: seluruh
        // `quotation_items.technology` menunjuk ke sana.
        if ($technology->isInUse()) {
            unset($data['code']);
        }

        $technology->update($data);

        $this->activity->logChanges(
            action: ActivityAction::TECHNOLOGY_UPDATE,
            before: $before,
            after: $this->snapshot($technology->refresh()),
            description: 'Mengubah teknologi '.$technology->code.' pada Price List.',
            subject: $technology,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $technology->code,
        );

        return redirect()
            ->route('superadmin.price-list.index', ['tab' => $technology->tabKey()])
            ->with('status', "Teknologi {$technology->code} berhasil diperbarui.");
    }

    public function destroy(Request $request, PrintTechnology $technology): RedirectResponse
    {
        // Penawaran lama menunjuk teknologinya lewat kode. Menghapusnya akan
        // membuat riwayat penawaran kehilangan arti, jadi ditahan di sini —
        // bukan sekadar disembunyikan tombolnya.
        if ($technology->isInUse()) {
            return redirect()
                ->route('superadmin.price-list.index', ['tab' => 'teknologi'])
                ->with('error', "Teknologi {$technology->code} sudah dipakai penawaran, jadi tidak dapat dihapus.");
        }

        $removed = $this->snapshot($technology);
        $code = $technology->code;

        // Materialnya ikut terhapus lewat foreign key cascade; baris rumus pada
        // tab Harga dibersihkan di sini karena tidak terhubung foreign key.
        $technology->pricingFormula()?->delete();
        $technology->delete();

        $this->activity->log(
            action: ActivityAction::TECHNOLOGY_DELETE,
            description: 'Menghapus teknologi '.$code.' beserta seluruh materialnya dari Price List.',
            old: $removed,
            module: ActivityModule::SUPERADMIN,
            subjectLabel: $code,
        );

        return redirect()
            ->route('superadmin.price-list.index', ['tab' => 'teknologi'])
            ->with('status', "Teknologi {$code} berhasil dihapus.");
    }

    /** @return array<string, mixed> */
    private function snapshot(PrintTechnology $technology): array
    {
        return $technology->only([
            'code', 'name', 'family', 'build_volume_x', 'build_volume_y', 'build_volume_z',
            'shell_ratio', 'default_infill', 'min_wall_thickness_mm', 'support_volume_factor',
            'layer_height_min', 'layer_height_max', 'throughput_cm3_per_hour',
            'setup_hours', 'setup_fee', 'machine_rate_per_hour', 'allows_hollow', 'sort_order',
        ]);
    }
}
