<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\MachineCost;
use App\Models\PackagingItem;
use App\Models\PricingFormula;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\SlaIndustriesFormula;
use App\Services\UsdRate;
use App\Support\PriceListPage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Halaman Price List — sumber data Calculator/Quotation.
 *
 * Tiap item menu di sidebar punya route dan halamannya sendiri: satu halaman
 * per teknologi (hanya material teknologi itu), Machine Cost, Harga, Packaging,
 * dan Teknologi. Tidak ada lagi satu halaman bertab yang memuat semuanya.
 *
 * Halaman teknologi dibangkitkan dari basis data, jadi teknologi yang
 * ditambahkan Superadmin langsung punya halamannya sendiri tanpa kode baru.
 *
 * Nama parameter pencarian dan halaman tetap seperti sebelumnya (`fdm_q`,
 * `fdm_page`, `machine_q`, …) supaya tautan lama tetap berlaku.
 */
class PriceListController extends Controller
{
    /** Banyaknya baris per halaman pada tiap tabel. */
    private const PER_PAGE = 15;

    /**
     * Alamat lama `/price-list?tab=…` diteruskan ke halaman barunya.
     *
     * Tanpa `tab`, halaman teknologi pertama yang dibuka. Query lain (pencarian,
     * nomor halaman) ikut dibawa, begitu pula pesan status yang sedang dikirim.
     */
    public function index(Request $request): RedirectResponse
    {
        $tab = (string) $request->query('tab', '');
        $key = PriceListPage::exists($tab)
            ? $tab
            : (PriceListPage::technologies()->first()?->tabKey() ?? PriceListPage::TEKNOLOGI);

        $request->session()->reflash();

        return redirect()->to(PriceListPage::url($key, $request->except('tab')));
    }

    /** Material satu teknologi saja, mis. /price-list/fdm. */
    public function technology(Request $request, string $slug): View
    {
        $technology = PriceListPage::technologyForSlug($slug);

        abort_if($technology === null, 404);

        $tab = $technology->tabKey();
        $search = $request->query($tab.'_q');

        $materials = $technology->materials()
            ->search($search)
            ->with('machine.technology')
            ->orderedByMachine()
            ->paginate(self::PER_PAGE, ['*'], $tab.'_page')
            ->withQueryString();

        // Nomor material dihitung ulang dari AWAL pada tiap kelompok mesin, dan
        // dihitung atas seluruh material teknologinya — bukan atas halaman yang
        // sedang tampil. Kalau dihitung per halaman, kelompok yang terpotong
        // paginasi akan mengulang dari 1 di halaman berikutnya.
        $numbers = $this->numberPerMachine(
            $technology->materials()
                ->search($search)
                ->orderedByMachine()
                ->get(['print_materials.id', 'print_materials.machine_cost_id'])
        );

        return view('superadmin.price-list.technology', [
            'technology' => $technology->loadCount('materials'),
            'materials' => $materials,
            'materialNumbers' => $numbers,
            'search' => (string) $search,
        ]);
    }

    public function machineCost(Request $request): View
    {
        // Mesin diurutkan mengikuti teknologinya supaya barisnya berkelompok
        // rapat di bawah judul masing-masing; teknologinya ikut dimuat karena
        // tiap baris menampilkan nama kelompok dan detail mesinnya.
        $machineCosts = MachineCost::search($request->query('machine_q'))
            ->with('technology')
            ->ordered()
            ->paginate(self::PER_PAGE, ['*'], 'machine_page')
            ->withQueryString();

        return view('superadmin.price-list.machine-cost', [
            'machineCosts' => $machineCosts,
            'search' => (string) $request->query('machine_q', ''),
        ]);
    }

    public function packaging(Request $request): View
    {
        $packaging = PackagingItem::search($request->query('packaging_q'))
            ->orderBy('item')
            ->orderBy('ukuran')
            ->paginate(self::PER_PAGE, ['*'], 'packaging_page')
            ->withQueryString();

        return view('superadmin.price-list.packaging', [
            'packaging' => $packaging,
            'search' => (string) $request->query('packaging_q', ''),
        ]);
    }

    /** Rumus Harga Otomatis: satu rumus yang berlaku untuk seluruh teknologi. */
    public function harga(): View
    {
        return view('superadmin.price-list.harga', [
            'formula' => PricingFormula::general(),
        ]);
    }

    /**
     * Rumus Harga Manual — parameter BAWAAN Form Perhitungan Kalkulator Manual
     * (dahulu "Rumus Harga SLA" di halaman SLA). Dipakai material SLA, MJF, dan
     * SLM yang memilih Kalkulator Manual.
     */
    public function hargaManual(UsdRate $rates): View
    {
        return view('superadmin.price-list.harga-manual', [
            'slaIndustriesFormula' => SlaIndustriesFormula::current(),

            // Kurs USD/IDR tidak diketik; nilainya dibaca sistem. Alamat
            // penyegarannya ikut dikirim supaya formulirnya dapat memperbaruinya
            // sendiri tanpa memuat ulang halaman.
            'slaIndustriesUsdRate' => $rates->current(),
            'usdRateEndpoint' => staff_route('exchange-rate.usd'),
        ]);
    }

    public function technologies(): View
    {
        return view('superadmin.price-list.technologies', [
            'technologies' => PrintTechnology::query()->managed()->withCount('materials')->ordered()->get(),
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
