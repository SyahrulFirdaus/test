<?php

namespace App\Services\Pricing;

use App\Models\PrintMaterial;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\SlaIndustriesQuote;
use App\Services\PrintEstimator;
use App\Services\SellingPriceEstimator;
use App\Support\PricingMethod;
use App\Support\SlaIndustries;

/**
 * Pricing Engine — satu-satunya pintu perhitungan harga NUSAMA3D.
 *
 *   Technology → Material → Pricing Method → Pricing Engine → Final Price
 *
 * Calculator pelanggan (lewat endpoint JSON), pengiriman penawaran, tambah dan
 * ubah model di dashboard, serta Kalkulator Manual admin seluruhnya memanggil
 * kelas ini. Tidak ada rumus harga di controller, Blade, maupun JavaScript.
 *
 * Pembagian kerjanya:
 *
 *  - App\Services\PrintEstimator    — besaran fisik: berat, volume, waktu.
 *  - App\Services\SellingPriceEstimator — rumus Kalkulator Otomatis
 *    (Material, Operasional Mesin, HPP, Risk, Subtotal, Profit, Basic Fee).
 *  - App\Support\SlaIndustries      — rumus Kalkulator Manual (kuotasi JLC).
 *
 * Keduanya hanya dipanggil dari sini, dengan masukan yang sudah dibakukan
 * App\Services\Pricing\PricingInput.
 *
 * Price Snapshot: hasil `quote()` disimpan utuh pada `cost_breakdown` model
 * penawaran, lengkap dengan parameter Price List yang dipakai, sidik masukan,
 * dan versi engine. Setelah itu harga penawaran hanya DIBACA
 * (`calculateQuotation()`), tidak pernah dihitung ulang karena Price List
 * berubah atau halamannya dibuka kembali.
 */
class PricingEngine
{
    /**
     * Versi rumus engine. Naikkan bila cara perhitungan berubah, supaya
     * penawaran lama tetap dapat ditelusuri dihitung dengan aturan yang mana.
     */
    public const VERSION = '2026.09.17';

    public const AUTOMATIC = PrintMaterial::PRICING_AUTOMATIC;

    public const MANUAL = PrintMaterial::PRICING_MANUAL;

    public function __construct(
        private readonly PrintEstimator $estimator,
        private readonly SellingPriceEstimator $automatic,
    ) {}

    /* ---------------------------------------------------------- material --- */

    /**
     * Metode harga material, dibaca dari basis data.
     *
     * Material SLA/MJF/SLM memilih sendiri Kalkulator Otomatis atau Manual
     * lewat kolom `pricing_method`. FDM tidak mengenal Kalkulator Manual, jadi
     * selalu otomatis — lihat App\Support\PricingMethod.
     *
     * @return array{technology: string, material: string, pricing_method: string, price_per_gram: float|null, offered: bool}
     */
    public function calculateMaterial(string $technology, string $material): array
    {
        $entry = $this->estimator->material($technology, $material);

        return [
            'technology' => strtoupper($technology),
            'material' => $material,
            'pricing_method' => PricingMethod::usesManualPricing($technology, $material) ? self::MANUAL : self::AUTOMATIC,
            'price_per_gram' => $entry === null ? null : (float) $entry['price_per_gram'],
            'offered' => $entry !== null,
        ];
    }

    /* ------------------------------------------------------------- model --- */

    /**
     * Estimasi fisik + harga satu model.
     *
     * @return array{input: PricingInput, estimate: array<string, mixed>, pricing: array<string, mixed>}
     */
    public function quote(PricingInput $input): array
    {
        $estimate = $this->estimate($input);
        $method = $this->calculateMaterial($input->technology, $input->material)['pricing_method'];

        $pricing = $method === self::MANUAL
            ? $this->pendingManual($input)
            : $this->calculateAutomatic($input, $estimate);

        return [
            'input' => $input,
            'estimate' => $estimate,
            'pricing' => $this->snapshot($pricing, $input, $estimate, $method),
        ];
    }

    /**
     * Besaran fisik: berat, volume, support, dan waktu.
     *
     * @return array<string, mixed>
     */
    public function estimate(PricingInput $input): array
    {
        return $this->estimator->estimate(
            $input->technology,
            $input->material,
            $input->volumeCm3,
            $input->quantity,
            [
                'support' => $input->supportEnabled,
                'support_volume_cm3' => $input->supportEnabled ? $input->measuredSupportVolumeCm3 : null,
                // Support dan Basic Fee menilai ukuran part yang benar-benar
                // dicetak, jadi dimensinya yang sudah terskalakan.
                'dimensions' => $input->scaledDimensionsMm(),
                'resolution' => $input->resolution,
                'scale' => $input->scale,
                'surface_area_cm2' => $input->surfaceAreaCm2,
                'infill_density' => $input->infillDensity,
                'infill_pattern' => $input->infillPattern,
                'finishing' => $input->finishing,
                'hollow' => $input->hollow,
                'printer' => $input->printer,
                'build_volume' => $input->buildVolume,
            ],
        );
    }

    /**
     * Kalkulator Otomatis:
     *
     *   Machine Operation = Machine Time × Machine Cost
     *   HPP               = Material Cost + Machine Operation
     *   Risk Cost         = HPP × Risk %
     *   Subtotal          = HPP + Risk Cost + Packaging + Overtime
     *   Profit            = Subtotal × Profit %
     *   Harga Jual        = Subtotal + Profit + Basic Fee
     *
     * Seluruh parameternya dibaca dari Price List (App\Services\SellingPriceEstimator).
     *
     * @param  array<string, mixed>  $estimate  hasil estimate()
     * @return array<string, mixed>
     */
    public function calculateAutomatic(PricingInput $input, array $estimate): array
    {
        return $this->automatic->calculate([
            'technology' => $input->technology,
            'material' => $input->material,
            'printer' => $input->printer,
            'quantity' => $input->quantity,
            'total_weight_g' => $estimate['total_weight_g'],
            'minutes' => $estimate['total_minutes'],
            'dimensions' => $input->scaledDimensionsMm(),
            'force_automatic' => true,
        ]);
    }

    /**
     * Kalkulator Manual: Final Price dari kuotasi vendor yang diisi tim.
     *
     *   Total Bayar ke JLC = Harga JLC + Ongkir JLC   (dalam rupiah, kurs hari itu)
     *   HPP                = Total Bayar ke JLC + DHL Beacukai
     *   Profit             = HPP × Margin Profit
     *   Final Price        = HPP + Profit
     *
     * @param  array<string, mixed>  $params  usd_rate, jlc_price_usd, jlc_shipping_usd, customs_idr, margin_percent
     * @return array<string, mixed>
     */
    public function calculateManual(array $params): array
    {
        return SlaIndustries::compute(SlaIndustries::paramsFrom($params));
    }

    /**
     * Price Snapshot Kalkulator Manual yang baru ditetapkan tim untuk satu model.
     *
     * @return array<string, mixed>
     */
    public function manualSnapshot(SlaIndustriesQuote $quote, QuotationItem $item): array
    {
        $input = PricingInput::fromItem($item);

        return $this->snapshot($quote->toCostBreakdown(), $input, null, self::MANUAL);
    }

    /* --------------------------------------------------------- penawaran --- */

    /**
     * Rincian harga penawaran yang tersimpan — hanya dibaca, tidak dihitung ulang.
     *
     * @return array<string, mixed>
     */
    public function calculateQuotation(QuotationRequest $quotation): array
    {
        return $this->automatic->forQuotation($quotation);
    }

    /**
     * Kolom `quotation_items` hasil satu perhitungan.
     *
     * Dipakai bersama oleh pengiriman penawaran dan dashboard, supaya kedua
     * jalan tidak menyalin daftar kolom yang sama (dan perlahan berbeda).
     *
     * @param  array{input: PricingInput, estimate: array<string, mixed>, pricing: array<string, mixed>}  $quote
     * @return array<string, mixed>
     */
    public function itemAttributes(array $quote): array
    {
        $input = $quote['input'];
        $estimate = $quote['estimate'];
        $pricing = $quote['pricing'];

        return [
            'technology' => $input->technology,
            'material' => $input->material,

            'printer' => $estimate['printer'],
            'printer_name' => \App\Support\Printer::name($estimate['printer']),
            'build_volume' => $estimate['build_volume'],

            'quantity' => $input->quantity,
            'scale_percent' => round($estimate['scale'] * 100, 2),
            'resolution' => $estimate['resolution'],
            'layer_height_mm' => $estimate['layer_height_mm'],
            'infill_density' => $estimate['infill_density'],
            'infill_pattern' => $estimate['infill_pattern'],
            'finishing' => $estimate['finishing'],
            'support_enabled' => $estimate['support_enabled'],
            'support_type' => $estimate['support_enabled'] ? config('printing.support.default_type') : null,

            'hollow_enabled' => $estimate['hollow_enabled'],
            'hollow_wall_thickness_mm' => $estimate['hollow_wall_thickness_mm'],
            'hollow_drain_diameter_mm' => $estimate['hollow_drain_diameter_mm'],
            'hollow_drain_position' => $estimate['hollow_drain_position'],

            'model_volume_cm3' => $estimate['model_volume_cm3'],
            'material_volume_cm3' => $estimate['material_volume_cm3'],
            // Hanya volume support yang benar-benar ikut dicetak; saat support
            // mati kolomnya 0 dan tidak akan dibaca ulang sebagai hasil ukur.
            'support_volume_cm3' => $estimate['support_volume_cm3'],
            'estimated_weight_g' => $estimate['weight_g'],
            'support_weight_g' => $estimate['support_weight_g'],
            'estimated_minutes' => $estimate['total_minutes'],

            'estimated_cost' => $pricing['selling_price'],
            'cost_breakdown' => $pricing,
            'pricing_method' => $pricing['pricing_method'],
            'pricing_version' => $pricing['pricing_version'],
            'priced_at' => now(),
        ];
    }

    /**
     * Hasil yang boleh dibaca pelanggan di browser.
     *
     * Parameter internal — Machine Cost, Risk %, Profit %, HPP, harga material
     * per gram — sengaja tidak ikut. Pelanggan cukup menerima harga akhirnya
     * beserta besaran fisik yang memang ditampilkan Calculator.
     *
     * @param  array{input: PricingInput, estimate: array<string, mixed>, pricing: array<string, mixed>}  $quote
     * @return array<string, mixed>
     */
    public function publicResult(array $quote): array
    {
        $estimate = $quote['estimate'];
        $pricing = $quote['pricing'];

        return [
            'scale' => $estimate['scale'],
            'modelVolumeCm3' => $estimate['model_volume_cm3'],
            'surfaceAreaCm2' => $estimate['surface_area_cm2'],
            'infillDensity' => $estimate['infill_density'],
            'fillFactor' => $estimate['fill_factor'],

            'hollowEnabled' => $estimate['hollow_enabled'],
            'hollowWallThicknessMm' => $estimate['hollow_wall_thickness_mm'],
            'hollowSavedCm3' => $estimate['hollow_saved_cm3'],

            'materialVolumeCm3' => $estimate['material_volume_cm3'],
            'weightG' => $estimate['weight_g'],
            'supportEnabled' => $estimate['support_enabled'],
            'supportVolumeCm3' => $estimate['support_volume_cm3'],
            'supportWeightG' => $estimate['support_weight_g'],
            'totalMaterialVolumeCm3' => $estimate['total_material_volume_cm3'],
            'totalWeightG' => $estimate['total_weight_g'],

            'finishing' => $estimate['finishing'],
            'finishingMinutes' => $estimate['finishing_minutes'],
            'unitMinutes' => $estimate['unit_minutes'],
            'totalMinutes' => $estimate['total_minutes'],

            'manualPricing' => $pricing['pricing_method'] === self::MANUAL,
            // null berarti harga menunggu Kalkulator Manual, BUKAN Rp0.
            'totalCost' => $pricing['selling_price'],
            'pricingVersion' => $pricing['pricing_version'],
            'inputFingerprint' => $pricing['input_fingerprint'],
        ];
    }

    /* ---------------------------------------------------------- internal --- */

    /**
     * Penanda Kalkulator Manual yang belum ditetapkan tim.
     *
     * @return array<string, mixed>
     */
    private function pendingManual(PricingInput $input): array
    {
        return [
            'technology' => $input->technology,
            'material_source' => $input->material,
            'quantity' => $input->quantity,

            // Ditunggu, bukan nol: yang membaca angka ini harus menampilkan
            // "Harga sedang dihitung oleh tim kami", bukan Rp0.
            'manual_pricing' => true,
            'pricing_source' => 'sla_industries',
            'selling_price' => null,
            'total' => null,
        ];
    }

    /**
     * Lengkapi hasil perhitungan menjadi Price Snapshot.
     *
     * @param  array<string, mixed>  $pricing
     * @param  array<string, mixed>|null  $estimate
     * @return array<string, mixed>
     */
    private function snapshot(array $pricing, PricingInput $input, ?array $estimate, string $method): array
    {
        $parameters = $method === self::MANUAL
            ? array_intersect_key($pricing, array_flip(['usd_rate', 'jlc_price_usd', 'jlc_shipping_usd', 'customs_idr', 'margin_percent']))
            : array_intersect_key($pricing, array_flip([
                'machine_source', 'machine_cost', 'material_source', 'material_price_per_g',
                'risk_percent', 'profit_percent', 'packaging_source', 'packaging', 'overtime', 'basic_fee_label', 'basic_fee',
            ]));

        return [
            ...$pricing,

            'pricing_method' => $method,
            'pricing_version' => self::VERSION,
            'final_price' => $pricing['selling_price'] ?? null,

            // Parameter Price List yang menghasilkan harga ini, beserta sidiknya.
            'parameters' => $parameters,
            'parameters_fingerprint' => sha1(json_encode($parameters, JSON_PRESERVE_ZERO_FRACTION)),

            // Masukan yang dihitung: spesifikasi dan geometri baku (mm/cm²/cm³).
            'input' => $input->toArray(),
            'input_fingerprint' => $input->fingerprint(),

            'estimate' => $estimate === null ? null : [
                'weight_g' => $estimate['weight_g'],
                'support_weight_g' => $estimate['support_weight_g'],
                'total_weight_g' => $estimate['total_weight_g'],
                'support_volume_cm3' => $estimate['support_volume_cm3'],
                'support_source' => match (true) {
                    ! $estimate['support_enabled'] => 'none',
                    (bool) $estimate['support_measured'] => 'measured',
                    default => 'estimated',
                },
                'total_minutes' => $estimate['total_minutes'],
                'finishing_minutes' => $estimate['finishing_minutes'],
            ],

            'calculated_at' => now()->toIso8601String(),
        ];
    }
}
