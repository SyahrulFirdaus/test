<?php

namespace App\Services;

use App\Models\MachineCost;
use App\Models\PackagingItem;
use App\Models\PricingFormula;
use App\Models\PrintTechnology;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Support\BasicFee;
use App\Support\PricingMethod;
use App\Support\Printer;
use App\Support\SlaIndustries;
use Illuminate\Support\Collection;

/**
 * Penetapan Harga Jual sebuah penawaran.
 *
 * Kelas ini adalah penentu harga penawaran. Pola perhitungannya sama persis
 * dengan tab "Harga" pada Price List (lihat App\Models\PricingFormula), bedanya
 * angka yang masuk bukan parameter simulasi melainkan hasil nyata penawaran:
 * Machine Time dari estimasi waktu cetak model, Jumlah Material dari berat
 * hasil estimasi, dan harga satuannya dari Price List. Jadi tidak ada satu pun
 * angka yang ditulis tetap di sini.
 *
 *   Material                = Jumlah Material x Harga Material
 *   Harga Operasional Mesin = Machine Time x Machine Cost
 *   HPP                     = Material + Operasional Mesin
 *   Risk Cost               = HPP x Risiko Gagal Print (%)
 *   Subtotal                = HPP + Risk Cost + Packaging + Overtime
 *   Profit                  = Subtotal x Profit (%)
 *   Basic Fee               = tarif menurut sisi terpanjang model
 *   Harga Jual              = Subtotal + Profit + Basic Fee
 *
 * Seluruh teknologi membaca SATU Rumus Harga Otomatis yang berlaku umum
 * (App\Models\PricingFormula::GENERAL); yang berbeda antar model hanya
 * material Price List yang dipakai dan mesin yang dipilih.
 *
 * Material SLA/MJF/SLM dengan Kalkulator Manual tidak melewati jalur ini:
 * harganya tidak diturunkan dari berat dan waktu mesin melainkan dari kuotasi
 * vendor yang diisi tim pada Detail Penawaran — lihat App\Support\SlaIndustries
 * dan App\Models\SlaIndustriesQuote. Material SLA dengan Kalkulator Otomatis
 * menempuh jalur yang sama dengan FDM (lihat App\Support\PricingMethod).
 *
 * App\Services\PrintEstimator tetap menghitung berat, waktu, dan volume —
 * hanya penetapan harganya yang pindah ke sini.
 *
 * Pembagian tugasnya penting: `calculate()` dipanggil SEKALI saat permintaan
 * penawaran dibentuk dan hasilnya disimpan utuh pada `cost_breakdown`, sedangkan
 * halaman admin memanggil `forItem()`/`forQuotation()` yang hanya MEMBACA
 * perhitungan tersimpan itu. Dengan begitu membuka halaman detail tidak pernah
 * menghitung ulang, sehingga harga yang sudah ditawarkan ke pelanggan tidak
 * dapat bergeser hanya karena parameter Price List berubah kemudian.
 */
class SellingPriceEstimator
{
    /**
     * Kunci `cost_breakdown` yang berupa rupiah, jadi boleh dijumlahkan.
     *
     * Sisa isinya adalah parameter — persentase, nama mesin, ukuran object —
     * yang tidak punya arti bila dijumlahkan antar model. Deretan lima nama
     * terakhir adalah komponen penawaran lama, yang masih tersimpan pada
     * penawaran sebelum rumus Price List dipakai.
     */
    public const SUMMABLE = [
        'material_cost', 'machine_operational_cost', 'hpp', 'risk_cost',
        'subtotal_hpp_risk', 'packaging', 'overtime', 'subtotal', 'profit',
        'basic_fee', 'selling_price', 'total',

        'material', 'machine_time', 'support', 'finishing', 'quality_control',
    ];

    /** Parameter rumus per teknologi, dibaca sekali per permintaan. */
    private ?Collection $formulas = null;

    /** Seluruh baris Machine Cost, untuk dicocokkan dengan printer tiap model. */
    private ?Collection $machines = null;

    /** Kardus berharga flat, untuk dicarikan ukuran terkecil yang memuat model. */
    private ?Collection $boxes = null;

    public function __construct(private readonly PrintEstimator $estimator) {}

    /**
     * Rincian harga seluruh penawaran, dikelompokkan per teknologi.
     *
     * Bila satu penawaran memuat beberapa model, tiap model dihitung sendiri
     * lebih dulu lalu komponennya dijumlahkan — bukan sebaliknya — supaya
     * parameter tiap model (mesin, material, jumlah) tetap berlaku apa adanya.
     *
     * @return array<string, mixed>
     */
    public function forQuotation(QuotationRequest $quotation): array
    {
        $items = $quotation->relationLoaded('items') ? $quotation->items : $quotation->items()->get();

        // Tiap model dihitung tepat sekali, lalu dipakai dua kali: sebagai
        // accordion per model dan sebagai bahan subtotal per teknologi.
        $models = $items
            ->sortBy('position')
            ->map(function (QuotationItem $item) {
                $calculation = $this->forItem($item);

                return [
                    'item' => $item,
                    'calculation' => $calculation,
                    'rows' => $this->rows($calculation),
                    'parameters' => $this->parameters($calculation),
                ];
            })
            ->values();

        $groups = $models
            ->groupBy(fn (array $entry) => $entry['calculation']['technology'])
            ->map(function (Collection $group, string $technology) {
                $totals = $this->sumCalculations($group->pluck('calculation'));

                return [
                    'technology' => $technology,
                    'formula' => $this->formula($technology),
                    'entries' => $group->values(),
                    'totals' => $totals,
                    'rows' => $this->rows($totals),
                ];
            })
            ->sortKeys()
            ->values();

        $totals = $this->sumCalculations($models->pluck('calculation'));
        $quotationTotal = (float) ($quotation->display_price ?? 0);

        return [
            'models' => $models,
            'groups' => $groups,
            'totals' => $totals,
            'rows' => $this->rows($totals),
            'selling_price' => $totals['selling_price'],

            // Harga penawaran ditetapkan rumus ini sejak permintaan dikirim,
            // jadi keduanya seharusnya sama persis. Selisihnya tetap dihitung
            // sebagai pemeriksaan: bila tidak nol, penawarannya dibuat sebelum
            // rumus ini berlaku dan rinciannya hanya disusun ulang.
            'quotation_total' => $quotationTotal,
            'difference' => round($totals['selling_price'] - $quotationTotal, 2),
            'reconstructed' => (bool) ($totals['reconstructed'] ?? false),
        ];
    }

    /**
     * Rincian harga satu model pada penawaran yang sudah tersimpan.
     *
     * Perhitungan lengkapnya ikut disimpan pada `cost_breakdown` saat
     * permintaan dikirim, jadi yang dilakukan di sini hanya membacanya kembali:
     * membuka halaman admin tidak pernah menghitung ulang, apalagi menggeser
     * harga yang sudah ditawarkan ke pelanggan.
     *
     * Penawaran yang dibuat sebelum rumus ini berlaku tidak punya perhitungan
     * tersimpan; rinciannya disusun ulang dari parameter yang tersedia sekarang
     * dan ditandai `reconstructed` supaya tidak dikira harga yang ditagihkan.
     *
     * @return array<string, mixed>
     */
    public function forItem(QuotationItem $item): array
    {
        $stored = $item->cost_breakdown;

        if (is_array($stored) && array_key_exists('selling_price', $stored)) {
            return $stored;
        }

        return [...$this->calculate($this->contextFor($item)), 'reconstructed' => true];
    }

    /**
     * Perhitungan Harga Jual dari parameter mentah.
     *
     * Sengaja tidak menyentuh basis data selain membaca Price List, sehingga
     * dapat dipanggil saat permintaan penawaran baru dibentuk — ketika model
     * QuotationItem-nya belum ada — maupun saat menyusun ulang rincian
     * penawaran lama.
     *
     * @param  array{
     *     technology: string,
     *     material: string,
     *     printer_name?: string|null,
     *     quantity?: int,
     *     total_weight_g?: float,
     *     minutes?: int,
     *     dimensions?: array<string, mixed>|null
     * }  $context
     * @return array<string, mixed>
     */
    public function calculate(array $context): array
    {
        $technology = strtoupper((string) $context['technology']);
        $material = (string) $context['material'];
        $quantity = max(1, (int) ($context['quantity'] ?? 1));

        // Material SLA/MJF/SLM dengan Kalkulator Manual berhenti di sini.
        // Harganya tidak diturunkan dari berat dan waktu mesin — baru ada
        // setelah tim mengisi Form Perhitungan pada Detail Penawaran. Kalkulator
        // Otomatis melanjutkan ke rumus di bawah, sama seperti FDM.
        // Lihat App\Support\PricingMethod.
        if (PricingMethod::usesManualPricing($technology, $material)) {
            return [
                'technology' => $technology,
                'material_source' => $material,
                'quantity' => $quantity,

                // Ditunggu, bukan nol: yang membaca angka ini harus menampilkan
                // "Menunggu Perhitungan", bukan harga Rp0.
                'manual_pricing' => true,
                'pricing_source' => 'sla_industries',
                'selling_price' => null,
                'total' => null,
            ];
        }

        $formula = $this->formula($technology);

        // Machine Time sudah mencakup seluruh unit model ini — angka yang masuk
        // adalah total menit dari estimator, bukan waktu per unit.
        $machineTimeHours = ((int) ($context['minutes'] ?? 0)) / 60;
        $machine = $this->machineFor($context['printer_name'] ?? null);
        $machineCost = $machine !== null
            ? (float) $machine->rounded_machine_cost
            : (float) ($formula->machine_cost ?? 0);

        // Berat model + support berlaku per unit, jadi dikalikan jumlah unit.
        $materialQty = ((float) ($context['total_weight_g'] ?? 0)) * $quantity;
        $materialPrice = $this->materialPriceFor($technology, $material, $formula);

        // Satu unit dikemas dalam satu kardus, jadi biayanya ikut jumlah unit.
        $dimensions = is_array($context['dimensions'] ?? null) ? $context['dimensions'] : null;
        $box = $this->boxFor($dimensions);
        $packaging = $box !== null
            ? (float) $box->price * $quantity
            : (float) ($formula->packaging_cost ?? 0) * $quantity;

        // Basic Fee melekat pada objectnya, bukan pada jumlah cetak, jadi
        // dikenakan sekali per model — berapa pun unit yang dicetak. Dimensi
        // pada `model_stats` sudah terskalakan sejak diukur browser.
        $largestDimension = BasicFee::largestDimension($dimensions);
        $basicFee = BasicFee::amount($largestDimension);

        $overtime = (float) ($formula->overtime_cost ?? 0);
        $riskPercent = (float) ($formula->risk_percent ?? 0);
        $profitPercent = (float) ($formula->profit_percent ?? 0);

        $machineOperational = $machineTimeHours * $machineCost;
        $materialCost = $materialQty * $materialPrice;
        $hpp = $machineOperational + $materialCost;
        $riskCost = $hpp * ($riskPercent / 100);
        $subtotalHppRisk = $hpp + $riskCost;
        $subtotal = $subtotalHppRisk + $packaging + $overtime;
        $profit = $subtotal * ($profitPercent / 100);
        $sellingPrice = round($subtotal + $profit + $basicFee, 2);

        return [
            'technology' => $technology,
            'formula_missing' => $formula === null,

            'machine_time_hours' => round($machineTimeHours, 2),
            'machine_cost' => round($machineCost, 2),
            'machine_source' => $machine?->mesin,

            'material_qty_g' => round($materialQty, 2),
            'material_price_per_g' => round($materialPrice, 2),
            'material_source' => $material,

            'risk_percent' => $riskPercent,
            'profit_percent' => $profitPercent,
            'packaging_source' => $box?->label,
            'quantity' => $quantity,

            'largest_dimension_mm' => round($largestDimension, 2),
            'basic_fee_label' => BasicFee::label($largestDimension),

            'machine_operational_cost' => round($machineOperational, 2),
            'material_cost' => round($materialCost, 2),
            'hpp' => round($hpp, 2),
            'risk_cost' => round($riskCost, 2),
            'subtotal_hpp_risk' => round($subtotalHppRisk, 2),
            'packaging' => round($packaging, 2),
            'overtime' => round($overtime, 2),
            'subtotal' => round($subtotal, 2),
            'profit' => round($profit, 2),
            'basic_fee' => round($basicFee, 2),
            'selling_price' => $sellingPrice,

            // Nama lama untuk total, supaya apa pun yang membaca
            // `cost_breakdown['total']` tetap mendapat angka yang benar.
            'total' => $sellingPrice,
        ];
    }

    /**
     * Parameter Price List untuk estimator di browser.
     *
     * Pencocokan nama mesin diselesaikan di sini — bukan diulang di JavaScript —
     * sehingga browser tinggal membaca Machine Cost yang berlaku untuk printer
     * yang dipilih. Dengan begitu harga yang dilihat pelanggan di Calculator
     * sama persis dengan yang dihitung ulang server saat permintaan dikirim.
     *
     * @return array<string, mixed>
     */
    public function browserPayload(): array
    {
        // Satu Rumus Harga Otomatis untuk seluruh teknologi. Tetap dikirim
        // juga per kode teknologi supaya pembaca lama di browser pun menemukan
        // parameter yang sama.
        $general = $this->formula('');
        $parameters = $general === null ? [] : [
            'machineCost' => (float) $general->machine_cost,
            'materialPricePerG' => (float) $general->material_price_per_g,
            'riskPercent' => (float) $general->risk_percent,
            'packagingCost' => (float) $general->packaging_cost,
            'overtimeCost' => (float) $general->overtime_cost,
            'profitPercent' => (float) $general->profit_percent,
        ];

        $formulas = collect(PrintTechnology::cached()->keys())
            ->mapWithKeys(fn (string $code) => [$code => $parameters])
            ->all();

        $machines = collect(array_keys((array) config('printing.printers.options', [])))
            ->mapWithKeys(function (string $key) {
                $machine = $this->machineFor(Printer::name($key));

                return [$key => $machine === null ? null : [
                    'name' => $machine->mesin,
                    'cost' => (float) $machine->rounded_machine_cost,
                ]];
            })
            ->filter()
            ->all();

        $packaging = $this->flatBoxes()
            ->map(fn (PackagingItem $box) => [
                'label' => $box->label,
                'price' => (float) $box->price,
                'sides' => $box->dimensions_cm,
            ])
            ->filter(fn (array $box) => $box['sides'] !== null)
            ->values()
            ->all();

        return [
            'formula' => $parameters,
            'formulas' => $formulas,
            'machines' => $machines,
            'packaging' => $packaging,
        ];
    }

    /**
     * Parameter perhitungan yang dibaca dari sebuah model tersimpan.
     *
     * @return array<string, mixed>
     */
    private function contextFor(QuotationItem $item): array
    {
        return [
            'technology' => (string) $item->technology,
            'material' => (string) $item->material,
            'printer_name' => $item->printer_name ?: $item->printer,
            'quantity' => (int) $item->quantity,
            'total_weight_g' => $item->total_weight_g,
            'minutes' => (int) $item->estimated_minutes,
            'dimensions' => $item->dimensions,
        ];
    }

    /**
     * Baris siap tampil untuk tabel Detail Perhitungan Harga.
     *
     * Kolom rumus memuat parameter yang benar-benar dipakai — berat, harga per
     * gram, jam mesin, tarif mesin, ukuran object — supaya admin dapat
     * menelusuri angkanya tanpa membuka basis data. Pada tabel gabungan
     * beberapa model parameternya dihilangkan: angkanya berasal dari beberapa
     * perhitungan sekaligus sehingga menuliskannya justru menyesatkan.
     *
     * @param  array<string, mixed>  $calculation
     * @return array<int, array{label: string, formula: string, value: float, highlight?: bool}>
     */
    public function rows(array $calculation): array
    {
        $number = fn (float $value, int $decimals = 0) => number_format($value, $decimals, ',', '.');
        $rupiah = fn (float $value) => 'Rp'.$number($value);

        // SLA Industries tidak punya material, jam mesin, risk, maupun Basic
        // Fee — harganya satu angka dari kuotasi vendor. Rinciannya ada pada
        // Form Perhitungan SLA Industries di kartu modelnya, bukan di sini.
        if ($calculation['manual_pricing'] ?? false) {
            return [];
        }

        $materialFormula = 'Berat × Harga Material';
        $machineFormula = 'Waktu Mesin × Machine Cost';
        $packagingFormula = 'Berdasarkan konfigurasi packaging';
        $overtimeFormula = 'Jika ada';
        $basicFeeFormula = 'Berdasarkan ukuran 3D Object';

        if (! ($calculation['aggregated'] ?? false)) {
            $materialFormula .= ' · '.$number((float) $calculation['material_qty_g'], 2).' gr × '.$rupiah((float) $calculation['material_price_per_g']).'/gr';
            $machineFormula .= ' · '.$this->duration((float) $calculation['machine_time_hours']).' × '.$rupiah((float) $calculation['machine_cost']).'/jam';

            if (filled($calculation['packaging_source'] ?? null)) {
                $packagingFormula = $calculation['packaging_source'].' × '.$calculation['quantity'].' unit';
            }

            $basicFeeFormula .= ' · '.$number((float) $calculation['largest_dimension_mm'], 1).' mm · '.$calculation['basic_fee_label'];
        }

        return [
            ['label' => 'Material', 'formula' => $materialFormula, 'value' => (float) $calculation['material_cost']],
            ['label' => 'Operasional Mesin', 'formula' => $machineFormula, 'value' => (float) $calculation['machine_operational_cost']],
            ['label' => 'HPP', 'formula' => 'Material + Operasional Mesin', 'value' => (float) $calculation['hpp']],
            ['label' => 'Risk Cost', 'formula' => 'HPP × Risk '.$number((float) $calculation['risk_percent'], 0).'%', 'value' => (float) $calculation['risk_cost']],
            ['label' => 'Packaging', 'formula' => $packagingFormula, 'value' => (float) $calculation['packaging']],
            ['label' => 'Overtime', 'formula' => $overtimeFormula, 'value' => (float) $calculation['overtime']],
            ['label' => 'Subtotal', 'formula' => 'HPP + Risk Cost + Packaging + Overtime', 'value' => (float) $calculation['subtotal']],
            ['label' => 'Profit', 'formula' => 'Subtotal × Profit '.$number((float) $calculation['profit_percent'], 0).'%', 'value' => (float) $calculation['profit']],
            ['label' => 'Basic Fee', 'formula' => $basicFeeFormula, 'value' => (float) $calculation['basic_fee']],
            ['label' => 'Harga Jual', 'formula' => 'Subtotal + Profit + Basic Fee', 'value' => (float) $calculation['selling_price'], 'highlight' => true],
        ];
    }

    /**
     * Parameter perhitungan dalam bentuk daftar siap tampil.
     *
     * Isinya sengaja mengulang angka yang sudah muncul di kolom rumus: admin
     * yang ingin memeriksa satu nilai tertentu — misalnya tarif mesin mana yang
     * terpakai — dapat menemukannya tanpa membaca seluruh tabel.
     *
     * @param  array<string, mixed>  $calculation
     * @return array<string, string>
     */
    public function parameters(array $calculation): array
    {
        $number = fn (float $value, int $decimals = 0) => number_format($value, $decimals, ',', '.');
        $rupiah = fn (float $value) => 'Rp'.$number($value);

        if ($calculation['manual_pricing'] ?? false) {
            return [];
        }

        return [
            'Material' => (string) ($calculation['material_source'] ?? '-'),
            'Berat Material' => $number((float) $calculation['material_qty_g'], 2).' gr'
                .' ('.$calculation['quantity'].' unit)',
            'Harga Material' => $rupiah((float) $calculation['material_price_per_g']).' / gram',
            'Machine Time' => $this->duration((float) $calculation['machine_time_hours']),
            'Machine Cost' => $rupiah((float) $calculation['machine_cost']).' / jam'
                .' ('.($calculation['machine_source'] ?? 'Rumus Harga Otomatis').')',
            'Risk' => $number((float) $calculation['risk_percent'], 0).'%',
            'Packaging' => $calculation['packaging_source'] ?? 'Rumus Harga Otomatis',
            'Overtime' => $rupiah((float) $calculation['overtime']),
            'Profit' => $number((float) $calculation['profit_percent'], 0).'%',
            'Dimensi Terbesar' => $number((float) $calculation['largest_dimension_mm'], 1).' mm',
            'Kategori Basic Fee' => (string) ($calculation['basic_fee_label'] ?? '-'),
        ];
    }

    /** Lama pemakaian mesin dalam bentuk "8 jam 30 menit". */
    private function duration(float $hours): string
    {
        $minutes = (int) round($hours * 60);
        $jam = intdiv($minutes, 60);
        $sisa = $minutes % 60;

        return match (true) {
            $jam > 0 && $sisa > 0 => $jam.' jam '.$sisa.' menit',
            $jam > 0 => $jam.' jam',
            default => $sisa.' menit',
        };
    }

    /* ------------------------------------------------------- sumber data --- */

    private function formula(string $technology): ?PricingFormula
    {
        $this->formulas ??= collect([PricingFormula::GENERAL => PricingFormula::general()]);

        return $this->formulas->get(self::formulaCode($technology));
    }

    /**
     * Baris `pricing_formulas` yang dipakai sebuah teknologi.
     *
     * Selalu Rumus Harga Otomatis yang berlaku umum — tidak ada lagi rumus per
     * teknologi. Parameter `$technology` dipertahankan untuk pemanggil lama.
     */
    public static function formulaCode(string $technology = ''): string
    {
        return PricingFormula::GENERAL;
    }

    /**
     * Baris Machine Cost yang paling cocok dengan mesin pilihan pelanggan.
     *
     * Nama mesin di Price List ditulis admin sendiri ("Ender 3 V2") dan hampir
     * tidak pernah sama persis dengan nama printer di config/printing.php
     * ("Creality Ender 3"), jadi pencocokannya memakai irisan kata — lihat
     * App\Models\MachineCost::printerMatchScore(). Bila tidak ada yang cocok,
     * Machine Cost pada parameter teknologinya yang dipakai.
     */
    private function machineFor(?string $printerName): ?MachineCost
    {
        $this->machines ??= MachineCost::orderBy('mesin')->get();

        $name = $printerName;

        $match = $this->machines
            ->map(fn (MachineCost $row) => ['row' => $row, 'score' => $row->printerMatchScore($name)])
            ->filter(fn (array $candidate) => $candidate['score'] >= 2)
            ->sortByDesc('score')
            ->first();

        return $match['row'] ?? null;
    }

    /**
     * Harga material per gram menurut Price List.
     *
     * Dibaca lewat PrintEstimator supaya keempat teknologi tertangani satu
     * jalan: material FDM/SLA berasal dari tabel Price List-nya, MJF/SLM masih
     * dari config. Harga yang dipakai sama persis dengan yang dipakai estimator
     * saat menghitung penawarannya.
     */
    private function materialPriceFor(string $technology, string $material, ?PricingFormula $formula): float
    {
        $entry = $this->estimator->material($technology, $material);

        return $entry !== null
            ? (float) $entry['price_per_gram']
            : (float) ($formula->material_price_per_g ?? 0);
    }

    /**
     * Kardus termurah yang masih memuat model ini.
     *
     * Dimensi pada `model_stats` diukur browser dari model yang SUDAH
     * diskalakan dan diputar, jadi angkanya dipakai apa adanya — hanya diubah
     * dari milimeter ke sentimeter mengikuti satuan ukuran kardus. Model tanpa
     * catatan dimensi tidak dapat dicarikan kardusnya — pemanggil memakai
     * Packaging pada parameter teknologinya.
     */
    private function boxFor(?array $dimensions): ?PackagingItem
    {
        if (! is_array($dimensions)) {
            return null;
        }

        $sides = [
            ((float) ($dimensions['x'] ?? 0)) / 10,
            ((float) ($dimensions['y'] ?? 0)) / 10,
            ((float) ($dimensions['z'] ?? 0)) / 10,
        ];

        rsort($sides);

        if ($sides[0] <= 0) {
            return null;
        }

        return $this->flatBoxes()->first(function (PackagingItem $box) use ($sides) {
            $inner = $box->dimensions_cm;

            return $inner !== null
                && $inner[0] >= $sides[0]
                && $inner[1] >= $sides[1]
                && $inner[2] >= $sides[2];
        });
    }

    /**
     * Kardus berharga flat, termurah lebih dulu.
     *
     * @return Collection<int, PackagingItem>
     */
    private function flatBoxes(): Collection
    {
        return $this->boxes ??= PackagingItem::where('price_unit', PackagingItem::UNIT_FLAT)
            ->orderBy('price')
            ->orderBy('item')
            ->get();
    }

    /* ---------------------------------------------------------- gabungan --- */

    /**
     * Jumlahkan beberapa rincian menjadi satu.
     *
     * Persentase risk dan profit ikut dihitung ulang dari hasilnya — bukan
     * disalin dari model pertama — supaya kolom rumus pada tabel gabungan tetap
     * menjelaskan angka di sebelahnya meski modelnya berbeda teknologi.
     *
     * @param  Collection<int, array<string, mixed>>  $calculations
     * @return array<string, mixed>
     */
    private function sumCalculations(Collection $calculations): array
    {
        // Rincian SLA Industries hanya memuat harga akhirnya, tanpa komponen
        // generik seperti risk atau packaging, jadi kunci yang tidak ada
        // dihitung nol — bukan memicu galat "undefined array key".
        $sum = fn (string $key) => round($calculations->sum(fn (array $row) => (float) ($row[$key] ?? 0)), 2);

        $hpp = $sum('hpp');
        $subtotal = $sum('subtotal');
        $riskCost = $sum('risk_cost');
        $profit = $sum('profit');

        return [
            'aggregated' => true,
            'technology' => $calculations->pluck('technology')->unique()->implode(', '),
            'formula_missing' => $calculations->contains(fn (array $row) => (bool) ($row['formula_missing'] ?? false)),

            'machine_time_hours' => $sum('machine_time_hours'),
            'machine_cost' => 0.0,
            'machine_source' => null,
            'material_qty_g' => $sum('material_qty_g'),
            'material_price_per_g' => 0.0,
            'material_source' => null,
            'packaging_source' => null,
            'quantity' => (int) $calculations->sum(fn (array $row) => (int) ($row['quantity'] ?? 0)),

            // Sisi terpanjang tidak dapat dijumlahkan; yang dilaporkan model
            // terbesar dalam kelompok ini, sekadar sebagai keterangan.
            'largest_dimension_mm' => round((float) $calculations->max(fn (array $row) => (float) ($row['largest_dimension_mm'] ?? 0)), 2),
            'basic_fee_label' => null,

            'risk_percent' => $hpp > 0 ? round($riskCost / $hpp * 100, 2) : 0.0,
            'profit_percent' => $subtotal > 0 ? round($profit / $subtotal * 100, 2) : 0.0,

            'machine_operational_cost' => $sum('machine_operational_cost'),
            'material_cost' => $sum('material_cost'),
            'hpp' => $hpp,
            'risk_cost' => $riskCost,
            'subtotal_hpp_risk' => $sum('subtotal_hpp_risk'),
            'packaging' => $sum('packaging'),
            'overtime' => $sum('overtime'),
            'subtotal' => $subtotal,
            'profit' => $profit,
            'basic_fee' => $sum('basic_fee'),
            'selling_price' => $sum('selling_price'),
            'total' => $sum('selling_price'),

            // Cukup satu model yang rinciannya disusun ulang untuk membuat
            // angka gabungan tidak lagi mewakili harga yang ditagihkan.
            'reconstructed' => $calculations->contains(fn (array $row) => (bool) ($row['reconstructed'] ?? false)),
        ];
    }
}
