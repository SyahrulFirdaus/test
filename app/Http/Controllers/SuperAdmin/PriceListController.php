<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MachineCost;
use App\Models\PackagingItem;
use App\Models\PricingFormula;
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
                ->orderBy('material')
                ->paginate(self::PER_PAGE, ['*'], $tab.'_page')
                ->withQueryString()];
        });

        $packaging = PackagingItem::search($request->query('packaging_q'))
            ->orderBy('item')
            ->orderBy('ukuran')
            ->paginate(self::PER_PAGE, ['*'], 'packaging_page')
            ->withQueryString();

        $machineCosts = MachineCost::search($request->query('machine_q'))
            ->orderBy('mesin')
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
}
