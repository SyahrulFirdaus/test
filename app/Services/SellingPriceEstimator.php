<?php

namespace App\Services;

use App\Models\MachineCost;
use App\Models\PackagingItem;
use App\Models\PricingFormula;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Support\BasicFee;
use App\Support\Finishing;
use App\Support\LeadTime;
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
 *                             (berat dibulatkan ke atas kelipatan 10 gram)
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
        'basic_fee', 'printing_price', 'express_fee', 'finishing_price',
        'selling_price', 'total',

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
     *     printer?: string|null,
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
        if (! ($context['force_automatic'] ?? false) && PricingMethod::usesManualPricing($technology, $material)) {
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
        $machine = $this->machineFor($context['printer'] ?? null);
        $machineCost = $machine !== null
            ? (float) $machine->rounded_machine_cost
            : (float) ($formula->machine_cost ?? 0);

        // Berat model + support berlaku per unit, jadi dikalikan jumlah unit.
        //
        // Material ditagih per kelipatan 10 gram, jadi beratnya dibulatkan ke
        // ATAS ke kelipatan 10 sebelum dikalikan harga — 1 g maupun 10 g
        // sama-sama 10 g, 11 g menjadi 20 g. Pembulatannya dikenakan sekali
        // pada berat total pesanan, bukan per unit.
        //
        // Hasil baginya dibulatkan enam desimal lebih dulu supaya sisa galat
        // pecahan biner pada berat hasil geometri — 20 g yang tersimpan sebagai
        // 20,0000000001 — tidak menaikkannya satu kelipatan penuh.
        $totalWeightG = round(((float) ($context['total_weight_g'] ?? 0)) * $quantity, 2);
        $materialQty = ceil(round($totalWeightG / 10, 6)) * 10;
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

        /*
         * Harga printing: harga mencetak partnya saja.
         *
         * Inilah dasar dua komponen di bawahnya — biaya finishing dan tambahan
         * Express keduanya diturunkan dari angka ini, bukan dari HPP maupun dari
         * total akhir, sehingga keduanya tidak pernah saling melipatgandakan.
         */
        $printingPrice = round($subtotal + $profit + $basicFee, 2);

        $finishing = Finishing::exists($context['finishing'] ?? null)
            ? (string) $context['finishing']
            : Finishing::default();

        /*
         * Custom Finishing berhenti di sini.
         *
         * Multi-color, masking, airbrush, dan sejenisnya tidak dapat dihitung
         * dari berat maupun harga printing — harganya ditetapkan tim lewat
         * kuotasi project. Harga printingnya tetap dilaporkan supaya tim punya
         * titik mulai, tetapi totalnya null: yang membaca angka ini harus
         * menulis "Menunggu Perhitungan", bukan harga Rp0.
         */
        if (Finishing::isManual($finishing)) {
            return [
                'technology' => $technology,
                'material_source' => $material,
                'quantity' => $quantity,

                'printing_price' => $printingPrice,
                'finishing' => $finishing,
                'finishing_label' => Finishing::label($finishing),
                'finishing_price' => null,

                'manual_pricing' => true,
                'pricing_source' => 'custom_finishing',
                'selling_price' => null,
                'total' => null,
            ];
        }

        $finishingPrice = (float) Finishing::priceFor($finishing, $printingPrice);

        /*
         * Express menaikkan harga PRINTING saja, bukan biaya finishing.
         *
         * Kecepatannya sudah ditetapkan pemanggil — yang memeriksa jumlah part
         * dan total waktu mesin seluruh pesanan lewat App\Support\LeadTime —
         * jadi di sini tinggal dipakai pengalinya.
         */
        $speed = LeadTime::exists($context['production_speed'] ?? null)
            ? (string) $context['production_speed']
            : LeadTime::default();

        $expressFactor = LeadTime::surchargeFactor($speed);
        $expressFee = round($printingPrice * ($expressFactor - 1), 2);
        $sellingPrice = round($printingPrice + $expressFee + $finishingPrice, 2);

        return [
            'technology' => $technology,
            'formula_missing' => $formula === null,

            'machine_time_hours' => round($machineTimeHours, 2),
            'machine_cost' => round($machineCost, 2),
            'machine_source' => $machine?->mesin,

            'material_qty_g' => round($materialQty, 2),
            'material_qty_g_actual' => round($totalWeightG, 2),
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
            'printing_price' => $printingPrice,

            'production_speed' => $speed,
            'express_percent' => $speed === LeadTime::EXPRESS ? LeadTime::surchargePercent() : 0.0,
            'express_fee' => $expressFee,

            'finishing' => $finishing,
            'finishing_label' => Finishing::label($finishing),
            'finishing_percent' => Finishing::percent($finishing),
            'finishing_min_price' => Finishing::minPrice($finishing),
            'finishing_price' => round($finishingPrice, 2),

            'selling_price' => $sellingPrice,

            // Nama lama untuk total, supaya apa pun yang membaca
            // `cost_breakdown['total']` tetap mendapat angka yang benar.
            'total' => $sellingPrice,
        ];
    }

    /**
     * Terapkan kecepatan pengerjaan pada rincian yang SUDAH dihitung.
     *
     * Express menaikkan harga printing dan tidak menyentuh komponen lain — berat,
     * waktu mesin, packaging, maupun biaya finishing tidak berubah — jadi
     * rinciannya tidak perlu dihitung dari awal.
     *
     * Dipakai saat kecepatan pesanan baru diketahui SETELAH tiap model
     * diestimasi: syarat Express bergantung pada jumlah part dan total waktu
     * mesin SELURUH pesanan, sedangkan harga dihitung per model. Rincian yang
     * menunggu perhitungan manual dibiarkan apa adanya.
     *
     * @param  array<string, mixed>  $calculation
     * @return array<string, mixed>
     */
    public function withProductionSpeed(array $calculation, string $speed): array
    {
        if (($calculation['manual_pricing'] ?? false) || ! array_key_exists('printing_price', $calculation)) {
            return $calculation;
        }

        $printingPrice = (float) $calculation['printing_price'];
        $finishingPrice = (float) ($calculation['finishing_price'] ?? 0);
        $expressFee = round($printingPrice * (LeadTime::surchargeFactor($speed) - 1), 2);
        $sellingPrice = round($printingPrice + $expressFee + $finishingPrice, 2);

        return [
            ...$calculation,
            'production_speed' => $speed,
            'express_percent' => $speed === LeadTime::EXPRESS ? LeadTime::surchargePercent() : 0.0,
            'express_fee' => $expressFee,
            'selling_price' => $sellingPrice,
            'total' => $sellingPrice,
        ];
    }

    /**
     * Parameter Harga Jual untuk Calculator di browser — TANPA angka internal.
     *
     * Rumusnya dapat dibuka menjadi:
     *
     *   Harga Jual = (Material + Operasional Mesin) × (1 + Risk%) × (1 + Profit%)
     *              + (Packaging + Overtime) × (1 + Profit%)
     *              + Basic Fee
     *
     * Jadi yang dikirim hanyalah tarif JUAL yang pengali Risk dan Profit-nya
     * sudah dilebur ke dalamnya, dengan Risk % dan Profit % bernilai nol.
     * Browser tetap menghasilkan Harga Jual yang sama persis dengan server,
     * tetapi HPP, Machine Cost, harga material per gram, Risk %, dan Profit %
     * tidak pernah sampai ke pengunjung. Server tetap menghitung ulang seluruh
     * harga dari Price List saat penawaran disimpan; angka di browser hanya
     * pratinjau.
     *
     * @return array<string, mixed>
     */
    public function browserPayload(): array
    {
        $formula = $this->formula('');
        $costFactor = $this->costFactor();
        $profitFactor = 1 + ((float) ($formula->profit_percent ?? 0)) / 100;

        $machines = collect(Printer::keys())
            ->mapWithKeys(function (string $key) use ($costFactor) {
                $machine = $this->machineFor($key);

                return [$key => $machine === null ? null : [
                    'name' => $machine->mesin,
                    'cost' => round((float) $machine->rounded_machine_cost * $costFactor, 4),
                ]];
            })
            ->filter()
            ->all();

        $packaging = $this->flatBoxes()
            ->map(fn (PackagingItem $box) => [
                'label' => $box->label,
                'price' => round((float) $box->price * $profitFactor, 4),
                'sides' => $box->dimensions_cm,
            ])
            ->filter(fn (array $box) => $box['sides'] !== null)
            ->values()
            ->all();

        return [
            'formula' => [
                'machineCost' => round((float) ($formula->machine_cost ?? 0) * $costFactor, 4),
                'materialPricePerG' => round((float) ($formula->material_price_per_g ?? 0) * $costFactor, 4),
                'packagingCost' => round((float) ($formula->packaging_cost ?? 0) * $profitFactor, 4),
                'overtimeCost' => round((float) ($formula->overtime_cost ?? 0) * $profitFactor, 4),
                // Sudah dilebur ke tarif di atas.
                'riskPercent' => 0,
                'profitPercent' => 0,
            ],
            'machines' => $machines,
            'packaging' => $packaging,
        ];
    }

    /**
     * Daftar teknologi untuk browser dengan harga material versi JUAL.
     *
     * Harga material per gram adalah komponen HPP, jadi yang dikirim sudah
     * dikalikan pengali Risk dan Profit — lihat browserPayload().
     *
     * @param  array<string, array<string, mixed>>  $technologies  hasil PrintEstimator::browserPayload()
     * @return array<string, array<string, mixed>>
     */
    public function publicTechnologies(array $technologies): array
    {
        $costFactor = $this->costFactor();

        foreach ($technologies as $code => $technology) {
            foreach ((array) ($technology['materials'] ?? []) as $index => $material) {
                $technologies[$code]['materials'][$index]['pricePerGram'] =
                    round(((float) ($material['pricePerGram'] ?? 0)) * $costFactor, 4);
            }
        }

        return $technologies;
    }

    /** Pengali komponen HPP: (1 + Risk%) × (1 + Profit%). */
    private function costFactor(): float
    {
        $formula = $this->formula('');

        return (1 + ((float) ($formula->risk_percent ?? 0)) / 100)
            * (1 + ((float) ($formula->profit_percent ?? 0)) / 100);
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
            'printer' => $item->printer,
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

        $materialFormula = 'Berat (dibulatkan ke atas kelipatan 10 gr) × Harga Material';
        $machineFormula = 'Waktu Mesin × Machine Cost';
        $packagingFormula = 'Berdasarkan konfigurasi packaging';
        $overtimeFormula = 'Jika ada';
        $basicFeeFormula = 'Berdasarkan ukuran 3D Object';

        $expressFormula = 'Berdasarkan pilihan Production';
        $finishingFormula = 'Berdasarkan pilihan Finishing';

        if (! ($calculation['aggregated'] ?? false)) {
            $actualWeight = (float) ($calculation['material_qty_g_actual'] ?? $calculation['material_qty_g']);
            $billedWeight = (float) $calculation['material_qty_g'];
            $weightInfo = $actualWeight !== $billedWeight
                ? $number($actualWeight, 2).' gr → '.$number($billedWeight, 2).' gr'
                : $number($billedWeight, 2).' gr';
            $materialFormula .= ' · '.$weightInfo.' × '.$rupiah((float) $calculation['material_price_per_g']).'/gr';
            $machineFormula .= ' · '.$this->duration((float) $calculation['machine_time_hours']).' × '.$rupiah((float) $calculation['machine_cost']).'/jam';

            if (filled($calculation['packaging_source'] ?? null)) {
                $packagingFormula = $calculation['packaging_source'].' × '.$calculation['quantity'].' unit';
            }

            $basicFeeFormula .= ' · '.$number((float) $calculation['largest_dimension_mm'], 1).' mm · '.$calculation['basic_fee_label'];

            $expressPercent = (float) ($calculation['express_percent'] ?? 0);
            $expressFormula = $expressPercent > 0
                ? 'Harga Printing × '.$number($expressPercent, 0).'% (Express)'
                : 'Standard — tanpa tambahan';

            $finishingLabel = (string) ($calculation['finishing_label'] ?? Finishing::label(Finishing::NONE));
            $finishingPercent = (float) ($calculation['finishing_percent'] ?? 0);
            $finishingMinimum = (float) ($calculation['finishing_min_price'] ?? 0);
            $finishingFormula = $finishingPercent > 0 || $finishingMinimum > 0
                ? $finishingLabel.' · MAX(Harga Printing × '.$number($finishingPercent, 0).'%, '.$rupiah($finishingMinimum).')'
                : $finishingLabel;
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
            ['label' => 'Harga Printing', 'formula' => 'Subtotal + Profit + Basic Fee', 'value' => (float) ($calculation['printing_price'] ?? $calculation['selling_price'])],
            ['label' => 'Express', 'formula' => $expressFormula, 'value' => (float) ($calculation['express_fee'] ?? 0)],
            ['label' => 'Finishing', 'formula' => $finishingFormula, 'value' => (float) ($calculation['finishing_price'] ?? 0)],
            ['label' => 'Harga Jual', 'formula' => 'Harga Printing + Express + Finishing', 'value' => (float) $calculation['selling_price'], 'highlight' => true],
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

        $actualWeight = (float) ($calculation['material_qty_g_actual'] ?? $calculation['material_qty_g']);
        $billedWeight = (float) $calculation['material_qty_g'];
        $weightDisplay = $actualWeight !== $billedWeight
            ? $number($actualWeight, 2).' gr, ditagih '.$number($billedWeight, 2).' gr'
            : $number($billedWeight, 2).' gr';

        return [
            'Material' => (string) ($calculation['material_source'] ?? '-'),
            'Berat Material' => $weightDisplay
                .' ('.$calculation['quantity'].' unit)',
            'Harga Material' => $rupiah((float) $calculation['material_price_per_g']).' / gram'
                .' · ditagih per 10 gram',
            'Machine Time' => $this->duration((float) $calculation['machine_time_hours']),
            'Machine Cost' => $rupiah((float) $calculation['machine_cost']).' / jam'
                .' ('.($calculation['machine_source'] ?? 'Rumus Harga Otomatis').')',
            'Risk' => $number((float) $calculation['risk_percent'], 0).'%',
            'Packaging' => $calculation['packaging_source'] ?? 'Rumus Harga Otomatis',
            'Overtime' => $rupiah((float) $calculation['overtime']),
            'Profit' => $number((float) $calculation['profit_percent'], 0).'%',
            'Dimensi Terbesar' => $number((float) $calculation['largest_dimension_mm'], 1).' mm',
            'Kategori Basic Fee' => (string) ($calculation['basic_fee_label'] ?? '-'),
            'Production' => LeadTime::label($calculation['production_speed'] ?? null),
            'Finishing' => (string) ($calculation['finishing_label'] ?? Finishing::label(Finishing::NONE)),
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
     * Baris Machine Cost milik printer pada model ini.
     *
     * Pemetaannya eksplisit lewat kolom `machine_costs.printer_key` yang diatur
     * Superadmin pada Price List — bukan lagi tebakan dari kemiripan nama mesin.
     * Pencocokan nama dahulu membuat harga berubah diam-diam begitu nama mesin
     * disunting, dan printer yang namanya tidak mirip mesin mana pun jatuh ke
     * Machine Cost Rumus Harga Otomatis. Printer tanpa pemetaan tetap memakai
     * Machine Cost Rumus Harga Otomatis, dan hal itu tercatat pada rinciannya
     * (`machine_source` kosong).
     */
    private function machineFor(?string $printerKey): ?MachineCost
    {
        if (blank($printerKey)) {
            return null;
        }

        $this->machines ??= MachineCost::whereNotNull('printer_key')->get()->keyBy('printer_key');

        return $this->machines->get($printerKey);
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
            'material_qty_g_actual' => $sum('material_qty_g_actual'),
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
            'printing_price' => $sum('printing_price'),
            'express_fee' => $sum('express_fee'),
            'finishing_price' => $sum('finishing_price'),
            'selling_price' => $sum('selling_price'),
            'total' => $sum('selling_price'),

            // Cukup satu model yang rinciannya disusun ulang untuk membuat
            // angka gabungan tidak lagi mewakili harga yang ditagihkan.
            'reconstructed' => $calculations->contains(fn (array $row) => (bool) ($row['reconstructed'] ?? false)),
        ];
    }
}
