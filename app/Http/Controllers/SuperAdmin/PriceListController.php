<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MachineCost;
use App\Models\PackagingItem;
use App\Models\PricingFormula;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Halaman Price List — sumber data Calculator/Quotation.
 *
 * Tabnya TIDAK lagi tetap. Satu tab material dibangkitkan untuk tiap teknologi
 * yang ada di basis data, jadi menambah teknologi lewat tab "Teknologi"
 * langsung memunculkan tabnya sendiri tanpa perubahan kode. Packaging, Machine
 * Cost, Harga, dan Teknologi menutup deretan tab itu.
 *
 * Tiap tab material punya pencarian dan halamannya sendiri; nama parameternya
 * diturunkan dari kode teknologi (`fdm_q`, `fdm_page`) sehingga tautan lama ke
 * tab FDM/SLA tetap berlaku.
 */
class PriceListController extends Controller
{
    /** Banyaknya baris per halaman pada tiap tabel. */
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $technologies = PrintTechnology::query()
            ->withCount('materials')
            ->ordered()
            ->get();

        // Satu paginator per teknologi, masing-masing dengan kunci halaman dan
        // kunci pencariannya sendiri agar tab lain tidak ikut berpindah halaman.
        $materials = $technologies->mapWithKeys(function (PrintTechnology $technology) use ($request) {
            $tab = $technology->tabKey();

            return [$technology->code => $technology->materials()
                ->search($request->query($tab.'_q'))
                ->with('machine.technology')
                ->orderedByMachine()
                ->paginate(self::PER_PAGE, ['*'], $tab.'_page')
                ->withQueryString()];
        });

        // Nomor material dihitung ulang dari AWAL pada tiap kelompok mesin, dan
        // dihitung atas seluruh material teknologinya — bukan atas halaman yang
        // sedang tampil. Kalau dihitung per halaman, kelompok yang terpotong
        // paginasi akan mengulang dari 1 di halaman berikutnya.
        $materialNumbers = $technologies->mapWithKeys(fn (PrintTechnology $technology) => [
            $technology->code => $this->numberPerMachine(
                $technology->materials()
                    ->search($request->query($technology->tabKey().'_q'))
                    ->orderedByMachine()
                    ->get(['print_materials.id', 'print_materials.machine_cost_id'])
            ),
        ]);

        $packaging = PackagingItem::search($request->query('packaging_q'))
            ->orderBy('item')
            ->orderBy('ukuran')
            ->paginate(self::PER_PAGE, ['*'], 'packaging_page')
            ->withQueryString();

        // Mesin diurutkan mengikuti teknologinya supaya barisnya berkelompok
        // rapat di bawah judul masing-masing; teknologinya ikut dimuat karena
        // tiap baris menampilkan nama kelompok dan detail mesinnya.
        $machineCosts = MachineCost::search($request->query('machine_q'))
            ->with('technology')
            ->ordered()
            ->paginate(self::PER_PAGE, ['*'], 'machine_page')
            ->withQueryString();

        $formulas = PricingFormula::whereIn('technology', PricingFormula::technologies())
            ->get()
            ->keyBy('technology');

        // Tab yang terbuka saat halaman dimuat: mengikuti ?tab= bila dikenal,
        // kalau tidak teknologi pertama.
        $tabs = $technologies->map(fn (PrintTechnology $technology) => $technology->tabKey())
            ->merge(['packaging', 'machine-cost', 'harga', 'teknologi'])
            ->all();

        $active = in_array($request->query('tab'), $tabs, true)
            ? (string) $request->query('tab')
            : ($tabs[0] ?? 'teknologi');

        return view('superadmin.price-list.index', [
            'technologies' => $technologies,
            'materials' => $materials,
            'materialNumbers' => $materialNumbers,
            'packaging' => $packaging,
            'machineCosts' => $machineCosts,
            'formulas' => $formulas,
            'activeTab' => $active,

            'filters' => $technologies
                ->mapWithKeys(fn (PrintTechnology $technology) => [
                    $technology->tabKey().'_q' => (string) $request->query($technology->tabKey().'_q', ''),
                ])
                ->merge([
                    'packaging_q' => (string) $request->query('packaging_q', ''),
                    'machine_q' => (string) $request->query('machine_q', ''),
                ])
                ->all(),
        ]);
    }

    /**
     * Nomor tiap material, dihitung ulang dari 1 pada setiap kelompok mesin.
     *
     * Barisnya sudah urut per mesin, jadi cukup satu penghitung per kelompok.
     * Sengaja TIDAK memakai `flatMap`: penggabungannya membuang kunci bilangan
     * bulat, sehingga id material tidak lagi dapat dipakai mencari nomornya.
     *
     * @param  \Illuminate\Support\Collection<int, PrintMaterial>  $materials
     * @return array<int, int> [id material => nomor di dalam kelompoknya]
     */
    private function numberPerMachine($materials): array
    {
        $numbers = [];
        $counters = [];

        foreach ($materials as $material) {
            $group = (int) $material->machine_cost_id;
            $counters[$group] = ($counters[$group] ?? 0) + 1;

            $numbers[$material->id] = $counters[$group];
        }

        return $numbers;
    }
}
