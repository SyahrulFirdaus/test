<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrintColorRequest;
use App\Models\PrintColor;
use App\Models\PrintMaterial;
use App\Models\QuotationItem;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use App\Support\MaterialColor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Menu Color: warna material yang tersedia beserta kode hexanya.
 *
 * Inilah satu-satunya tempat daftar warna diubah. Yang tersimpan pada penawaran
 * bukan nama warnanya melainkan `key` (lihat App\Models\PrintColor), sehingga
 * mengganti nama maupun kode hexa sebuah warna TIDAK membuat penawaran lama
 * kehilangan warnanya — yang berubah hanya cara warna itu ditampilkan.
 *
 * Dipakai bersama Admin dan Superadmin: route-nya didaftarkan dua kali lewat
 * $staffRoutes, dan tautan di halaman dibangun dengan staff_route() sehingga
 * masing-masing tetap berada di wilayahnya sendiri.
 */
class ColorController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $colors = PrintColor::query()
            ->search($search)
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.colors.index', [
            'colors' => $colors,
            'search' => $search,
            // Warna yang sedang dipakai penawaran tidak boleh hilang dari
            // daftar; tombol Hapus pada barisnya dimatikan sejak di halaman.
            'usage' => $this->usageCounts(),
        ]);
    }

    public function create(): View
    {
        return view('admin.colors.form', ['color' => new PrintColor(['hex' => '#B8452F'])]);
    }

    public function store(StorePrintColorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $color = PrintColor::create([
            'key' => PrintColor::makeKey($data['label']),
            'label' => $data['label'],
            'hex' => $data['hex'],
            // Warna baru ditempatkan di urutan terakhir.
            'position' => (int) PrintColor::max('position') + 1,
        ]);

        MaterialColor::forget();

        $this->activity->log(
            action: ActivityAction::COLOR_CREATE,
            description: 'Menambahkan warna "'.$color->label.'" ('.$color->hex.').',
            subject: $color,
            new: $this->snapshot($color),
            subjectLabel: $color->label,
        );

        return redirect()
            ->route(staff_route_name('colors.index'))
            ->with('status', 'Warna berhasil ditambahkan.');
    }

    public function edit(PrintColor $color): View
    {
        return view('admin.colors.form', ['color' => $color]);
    }

    public function update(StorePrintColorRequest $request, PrintColor $color): RedirectResponse
    {
        $before = $this->snapshot($color);

        // `key` sengaja tidak ikut berubah — lihat catatan kelas.
        $color->update($request->validated());

        MaterialColor::forget();

        $this->activity->logChanges(
            action: ActivityAction::COLOR_UPDATE,
            before: $before,
            after: $this->snapshot($color->refresh()),
            description: 'Memperbarui warna "'.$color->label.'".',
            subject: $color,
            subjectLabel: $color->label,
        );

        return redirect()
            ->route(staff_route_name('colors.index'))
            ->with('status', 'Warna berhasil diperbarui.');
    }

    public function destroy(PrintColor $color): RedirectResponse
    {
        /*
         * Warna yang sudah dipakai tidak dapat dihapus.
         *
         * Penawaran menyimpan kuncinya, bukan salinan nama dan hexanya, jadi
         * menghapus barisnya membuat model yang sudah dipesan kehilangan warna
         * yang disetujui pelanggan. Menonaktifkan bukan pilihan di sini:
         * daftarnya memang daftar warna yang tersedia.
         */
        $used = $this->usageCounts()[$color->key] ?? 0;

        if ($used > 0) {
            return back()->withErrors([
                'color' => 'Warna "'.$color->label.'" sedang dipakai '.$used.' model penawaran, jadi tidak dapat dihapus.',
            ]);
        }

        if ($this->usedByMaterial($color->key)) {
            return back()->withErrors([
                'color' => 'Warna "'.$color->label.'" masih terdaftar sebagai pilihan pada material Price List, jadi tidak dapat dihapus.',
            ]);
        }

        $removed = $this->snapshot($color);
        $label = $color->label;

        $color->delete();

        MaterialColor::forget();

        $this->activity->log(
            action: ActivityAction::COLOR_DELETE,
            description: 'Menghapus warna "'.$label.'" dari daftar.',
            old: $removed,
            subjectLabel: $label,
        );

        return redirect()
            ->route(staff_route_name('colors.index'))
            ->with('status', 'Warna berhasil dihapus.');
    }

    /**
     * Berapa model penawaran yang memakai tiap warna, dikunci `key`.
     *
     * @return array<string, int>
     */
    private function usageCounts(): array
    {
        return QuotationItem::query()
            ->selectRaw('material_color, COUNT(*) as total')
            ->whereNotNull('material_color')
            ->groupBy('material_color')
            ->pluck('total', 'material_color')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /** Warna ini disebut sebagai pilihan pada material Price List mana pun. */
    private function usedByMaterial(string $key): bool
    {
        return PrintMaterial::query()
            ->get(['technical_spec'])
            ->contains(fn (PrintMaterial $material) => in_array($key, (array) ($material->technical_spec['colors'] ?? []), true));
    }

    /** @return array<string, mixed> */
    private function snapshot(PrintColor $color): array
    {
        return [
            'key' => $color->key,
            'label' => $color->label,
            'hex' => $color->hex,
        ];
    }
}
