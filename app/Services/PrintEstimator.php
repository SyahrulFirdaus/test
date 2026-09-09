<?php

namespace App\Services;

use App\Support\Finishing;
use App\Support\InfillPattern;
use App\Support\MaterialCatalog;
use App\Support\Printer;
use App\Support\PrintResolution;
use InvalidArgumentException;

/**
 * Menghitung estimasi berat, waktu, dan biaya cetak dari volume model.
 *
 * Kelas ini menjadi satu-satunya sumber kebenaran perhitungan: browser
 * memakainya lewat payload konfigurasi yang dikirim ke halaman, sedangkan
 * server memanggilnya ulang saat permintaan penawaran disimpan sehingga
 * angka yang tercatat tidak bergantung pada data yang dikirim klien.
 *
 * Urutan perhitungannya:
 *
 *   1. Volume geometri diskalakan (skala pangkat tiga) dan luas permukaannya
 *      diskalakan (pangkat dua).
 *   2. Volume material ditentukan dari infill — atau dari tebal cangkang bila
 *      Hollow Model aktif.
 *   3. Support ditambahkan sebagai volume terpisah.
 *   4. Waktu dihitung dari total volume dibagi laju mesin, ikut dipengaruhi
 *      resolusi, pola infill, dan kecepatan printer yang dipilih.
 *   5. Biaya dipecah menjadi lima komponen yang totalnya sama persis dengan
 *      total penawaran.
 */
class PrintEstimator
{
    public function __construct(private readonly SupportEstimator $support) {}

    /** @return array<string, mixed> */
    public function technologies(): array
    {
        return config('printing.technologies', []);
    }

    public function technology(string $code): ?array
    {
        return $this->technologies()[strtoupper($code)] ?? null;
    }

    public function material(string $technology, string $material): ?array
    {
        return $this->technology($technology)['materials'][$material] ?? null;
    }

    public function supports(string $technology, string $material): bool
    {
        return $this->material($technology, $material) !== null;
    }

    /** Apakah Hollow Model tersedia untuk teknologi ini. */
    public function allowsHollow(string $technology): bool
    {
        return in_array(strtoupper($technology), (array) config('printing.hollow.technologies', []), true);
    }

    /**
     * Konfigurasi ringkas untuk estimator di browser.
     *
     * @return array<string, mixed>
     */
    public function browserPayload(): array
    {
        $payload = [];

        foreach ($this->technologies() as $code => $technology) {
            $payload[$code] = [
                'name' => $technology['name'],
                // Label pilihan teknologi pada Edit Specification, mis.
                // "FDM (Plastic)". Sumbernya sama dengan halaman panduan.
                'family' => $technology['family'] ?? null,
                'label' => isset($technology['family'])
                    ? $code.' ('.$technology['family'].')'
                    : $code,
                'description' => $technology['description'],
                'buildVolume' => $technology['build_volume'],
                'shellRatio' => $technology['shell_ratio'],
                'defaultInfill' => $technology['default_infill'],
                'infillNote' => $technology['infill_note'] ?? null,
                'minWallThicknessMm' => $technology['min_wall_thickness_mm'],
                'throughput' => $technology['throughput_cm3_per_hour'],
                'setupHours' => $technology['setup_hours'],
                'setupFee' => $technology['setup_fee'],
                'machineRate' => $technology['machine_rate_per_hour'],
                'supportFactor' => $technology['support_volume_factor'] ?? 0.0,
                'supportRequired' => $this->support->isRequiredFor($code),
                'supportNote' => $this->support->unavailableReason($code),
                'layerHeightRange' => $technology['layer_height_range'] ?? null,
                'allowsHollow' => $this->allowsHollow($code),
                'materials' => collect($technology['materials'])
                    ->map(fn (array $material, string $name) => [
                        'name' => $name,
                        'density' => $material['density'],
                        'pricePerGram' => $material['price_per_gram'],
                        // Warna yang tersedia untuk material ini; kosong berarti
                        // seluruh warna boleh dipakai.
                        'colors' => array_values((array) ($material['colors'] ?? [])),

                        // Keterangan dan batas ukuran yang ditampilkan panduan
                        // maupun panel kiri Edit Specification. Keduanya membaca
                        // katalog yang sama, jadi tidak ada data kembar.
                        'slug' => MaterialCatalog::slug($code, $name),
                        'description' => $material['description'] ?? null,
                        'characteristics' => (object) ($material['characteristics'] ?? []),
                        'pros' => array_values((array) ($material['pros'] ?? [])),
                        'cons' => array_values((array) ($material['cons'] ?? [])),
                        'maxSize' => $material['max_size'] ?? null,
                        'minSize' => $material['min_size'] ?? null,
                        'minSizeSlender' => $material['min_size_slender'] ?? null,
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return $payload;
    }

    /**
     * Estimasi berat, waktu, dan biaya cetak.
     *
     * Kunci `material_volume_cm3` dan `weight_g` tetap merujuk pada model saja,
     * sedangkan kontribusi support dilaporkan terpisah supaya rinciannya dapat
     * ditampilkan (Berat Model / Berat Support / Total Berat). Waktu dan biaya
     * selalu dihitung dari total, karena support ikut tercetak.
     *
     * @param  array{
     *     support?: bool,
     *     support_type?: string|null,
     *     dimensions?: array<string, float>|null,
     *     support_volume_cm3?: float|null,
     *     resolution?: string|null,
     *     scale?: float|null,
     *     infill_density?: float|null,
     *     infill_pattern?: string|null,
     *     finishing?: string|null,
     *     hollow?: array<string, mixed>|null,
     *     surface_area_cm2?: float|null,
     *     printer?: string|null,
     *     build_volume?: array<string, float>|null
     * }  $options
     * @return array<string, mixed>
     */
    public function estimate(string $technology, string $material, float $modelVolumeCm3, int $quantity = 1, array $options = []): array
    {
        $tech = $this->technology($technology);
        $mat = $this->material($technology, $material);

        if ($tech === null || $mat === null) {
            throw new InvalidArgumentException("Kombinasi teknologi {$technology} dan material {$material} tidak dikenal.");
        }

        $quantity = max(1, $quantity);

        // --- 1. skala -------------------------------------------------------
        $scale = $this->scale($options['scale'] ?? null);
        $geometryVolume = max(0.0, $modelVolumeCm3);
        $solidVolume = $geometryVolume * ($scale ** 3);
        $surfaceArea = max(0.0, (float) ($options['surface_area_cm2'] ?? 0.0)) * ($scale ** 2);

        // Resolusi memengaruhi jumlah lapisan yang harus dicetak, jadi waktu
        // ikut berubah; volume bahan hanya bergeser sedikit.
        $resolutionKey = PrintResolution::exists($options['resolution'] ?? null)
            ? (string) $options['resolution']
            : PrintResolution::default();
        $resolution = PrintResolution::resolve($resolutionKey);

        // --- 2. volume material --------------------------------------------
        $density = InfillPattern::density($options['infill_density'] ?? null, (float) $tech['default_infill']);
        $pattern = InfillPattern::exists($options['infill_pattern'] ?? null)
            ? (string) $options['infill_pattern']
            : InfillPattern::default();

        $hollow = $this->hollow($technology, $options['hollow'] ?? null, $solidVolume, $surfaceArea);

        if ($hollow['enabled']) {
            // Part yang dikosongkan hanya menyisakan cangkang, jadi infill tidak
            // lagi berperan — volumenya ditentukan tebal dinding.
            $fillFactor = $solidVolume > 0 ? $hollow['volume_cm3'] / $solidVolume : 0.0;
            $materialVolume = $hollow['volume_cm3'] * $resolution['material_multiplier'];
        } else {
            $fillFactor = InfillPattern::fillFactor((float) $tech['shell_ratio'], $density, $pattern);
            $materialVolume = $solidVolume * $fillFactor * $resolution['material_multiplier'];
        }

        $weight = $materialVolume * $mat['density'];

        // --- 3. support -----------------------------------------------------
        $support = $this->support->estimate($technology, (float) $mat['density'], $solidVolume, [
            'enabled' => (bool) ($options['support'] ?? false),
            'type' => $options['support_type'] ?? null,
            'dimensions' => $options['dimensions'] ?? null,
            'measured_volume_cm3' => $options['support_volume_cm3'] ?? null,
        ]);

        $totalVolume = $materialVolume + $support['volume_cm3'];
        $totalWeight = $weight + $support['weight_g'];

        // --- 4. waktu -------------------------------------------------------
        $printer = Printer::resolve($options['printer'] ?? null);
        $speedFactor = max(0.1, (float) $printer['speed_factor']);
        $patternTime = InfillPattern::timeMultiplier($density, $pattern);
        $throughput = $tech['throughput_cm3_per_hour'] * $speedFactor;

        $unitHours = $throughput > 0
            ? ($totalVolume / $throughput) * $resolution['time_multiplier'] * ($hollow['enabled'] ? 1.0 : $patternTime)
            : 0.0;

        $totalHours = $tech['setup_hours'] + ($unitHours * $quantity);

        // --- 5. finishing ---------------------------------------------------
        // Pengerjaan setelah cetak tidak memakai mesin, jadi waktunya berdiri
        // sendiri dan tidak ikut dikalikan tarif mesin.
        $finishing = Finishing::exists($options['finishing'] ?? null)
            ? (string) $options['finishing']
            : Finishing::default();
        $finishingHours = Finishing::hoursPerUnit($finishing) * $quantity;

        // --- 6. biaya -------------------------------------------------------
        $machineRate = $tech['machine_rate_per_hour'] * max(0.1, (float) $printer['rate_factor']);
        $breakdown = $this->breakdown($tech, $mat, [
            'quantity' => $quantity,
            'model_weight_g' => $weight,
            'support_weight_g' => $support['weight_g'],
            'support_enabled' => $support['enabled'],
            'total_hours' => $totalHours,
            'machine_rate' => $machineRate,
            'surface_area_cm2' => $surfaceArea,
            'finishing_multiplier' => Finishing::costMultiplier($finishing),
        ]);

        $unitHoursCost = $unitHours * $machineRate;
        $unitCost = ($totalWeight * $mat['price_per_gram']) + $unitHoursCost;

        return [
            'scale' => $scale,
            'model_volume_cm3' => round($solidVolume, 3),
            'surface_area_cm2' => round($surfaceArea, 2),

            'infill_density' => $density,
            'infill_pattern' => $pattern,
            'fill_factor' => round($fillFactor, 4),

            'hollow_enabled' => $hollow['enabled'],
            'hollow_wall_thickness_mm' => $hollow['wall_thickness_mm'],
            'hollow_drain_diameter_mm' => $hollow['drain_diameter_mm'],
            'hollow_drain_position' => $hollow['drain_position'],
            'hollow_saved_cm3' => $hollow['enabled'] ? round($solidVolume - $hollow['volume_cm3'], 3) : 0.0,

            'printer' => Printer::exists($options['printer'] ?? null) ? (string) $options['printer'] : Printer::default(),
            'build_volume' => Printer::buildVolume($options['printer'] ?? null, $options['build_volume'] ?? null),

            'material_volume_cm3' => round($materialVolume, 3),
            'weight_g' => round($weight, 2),

            'support_enabled' => $support['enabled'],
            'support_required' => $support['required'],
            'support_measured' => $support['measured'],
            'support_volume_cm3' => $support['volume_cm3'],
            'support_weight_g' => $support['weight_g'],
            'support_note' => $support['note'],

            'resolution' => $resolutionKey,
            'layer_height_mm' => (float) $resolution['layer_height'],
            'quality' => $resolution['quality'],
            'resolution_within_range' => PrintResolution::isWithinRange($resolutionKey, $technology),
            'resolution_notice' => PrintResolution::rangeNotice($resolutionKey, $technology),

            'total_material_volume_cm3' => round($totalVolume, 3),
            'total_weight_g' => round($totalWeight, 2),

            'finishing' => $finishing,
            'finishing_label' => Finishing::label($finishing),
            'finishing_minutes' => (int) round($finishingHours * 60),

            'unit_minutes' => (int) max(1, round($unitHours * 60)),
            // Waktu total mencakup pengerjaan finishing setelah part dicetak.
            'total_minutes' => (int) max(1, round(($totalHours + $finishingHours) * 60)),
            'unit_cost' => $this->roundCost($unitCost),
            'total_cost' => $breakdown['total'],
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Rincian biaya menjadi lima komponen.
     *
     * Totalnya sengaja dihitung dari penjumlahan komponen — bukan sebaliknya —
     * supaya tabel rincian yang dilihat pelanggan selalu berjumlah persis sama
     * dengan total penawaran, berapa pun pembulatannya.
     *
     * @param  array<string, mixed>  $tech
     * @param  array<string, mixed>  $mat
     * @param  array<string, mixed>  $context
     * @return array<string, float>
     */
    private function breakdown(array $tech, array $mat, array $context): array
    {
        $cost = config('printing.cost', []);
        $quantity = (int) $context['quantity'];

        $materialCost = $context['model_weight_g'] * $mat['price_per_gram'] * $quantity;

        // Biaya setup mesin ikut komponen waktu: sama-sama okupansi mesin.
        $timeCost = $tech['setup_fee'] + ($context['total_hours'] * $context['machine_rate']);

        $supportCost = $context['support_enabled']
            ? ($context['support_weight_g'] * $mat['price_per_gram'] * $quantity)
                + ((float) ($cost['support_removal_fee'] ?? 0) * $quantity)
            : 0.0;

        // Pembersihan dasar berlaku untuk setiap part; pilihan finishing
        // tambahan mengalikannya sesuai `finishing.options` di config.
        $baseFinishing = $context['surface_area_cm2'] > 0
            ? max(
                (float) ($cost['finishing']['minimum'] ?? 0),
                $context['surface_area_cm2'] * (float) ($cost['finishing']['rate_per_cm2'] ?? 0)
            ) * $quantity
            : (float) ($cost['finishing']['minimum'] ?? 0) * $quantity;

        $finishingCost = $baseFinishing * (float) ($context['finishing_multiplier'] ?? 1.0);

        $subtotal = $materialCost + $timeCost + $supportCost + $finishingCost;

        $qualityCost = max(
            (float) ($cost['quality_control']['minimum'] ?? 0),
            $subtotal * (float) ($cost['quality_control']['percent'] ?? 0)
        );

        $components = [
            'material' => $this->roundCost($materialCost),
            'machine_time' => $this->roundCost($timeCost),
            'support' => $this->roundCost($supportCost),
            'finishing' => $this->roundCost($finishingCost),
            'quality_control' => $this->roundCost($qualityCost),
        ];

        return [...$components, 'total' => round(array_sum($components), 2)];
    }

    /**
     * Perkiraan volume part yang dikosongkan.
     *
     * Cangkang setebal `t` pada permukaan seluas `A` menyisakan material
     * sebanyak A x t, dikurangi lubang pembuangan yang dibor menembusnya.
     * Hasilnya tidak pernah melebihi volume padat aslinya — pada part yang
     * tipis, mengosongkan bagian dalam memang tidak menyisakan apa-apa lagi.
     *
     * @param  array<string, mixed>|null  $options
     * @return array<string, mixed>
     */
    private function hollow(string $technology, ?array $options, float $solidVolumeCm3, float $surfaceAreaCm2): array
    {
        $config = config('printing.hollow', []);
        $wall = $config['wall_thickness_mm'] ?? [];
        $drain = $config['drain_hole'] ?? [];

        $thickness = $this->clamp(
            $options['wall_thickness_mm'] ?? null,
            (float) ($wall['default'] ?? 2.0),
            (float) ($wall['min'] ?? 0.8),
            (float) ($wall['max'] ?? 5.0),
        );

        $diameter = $this->clamp(
            $options['drain_hole_diameter_mm'] ?? null,
            (float) ($drain['diameter_mm']['default'] ?? 3.5),
            (float) ($drain['diameter_mm']['min'] ?? 1.0),
            (float) ($drain['diameter_mm']['max'] ?? 10.0),
        );

        $positions = array_keys($drain['positions'] ?? []);
        $position = in_array($options['drain_hole_position'] ?? null, $positions, true)
            ? (string) $options['drain_hole_position']
            : (string) ($drain['default_position'] ?? ($positions[0] ?? 'bottom'));

        $enabled = (bool) ($options['enabled'] ?? false)
            && $this->allowsHollow($technology)
            && $surfaceAreaCm2 > 0
            && $solidVolumeCm3 > 0;

        $volume = $solidVolumeCm3;

        if ($enabled) {
            // 1 cm2 x 1 mm = 0,1 cm3, jadi hasilnya dibagi 10.
            $shell = ($surfaceAreaCm2 * $thickness) / 10;

            // Lubang pembuangan menembus cangkang, jadi materialnya ikut hilang.
            $holes = (int) ($drain['count'] ?? 2);
            $drainVolume = $holes * M_PI * (($diameter / 2) ** 2) * $thickness / 1000;

            $volume = max(0.0, min($solidVolumeCm3, $shell - $drainVolume));
        }

        return [
            'enabled' => $enabled,
            'volume_cm3' => $volume,
            'wall_thickness_mm' => $enabled ? $thickness : null,
            'drain_diameter_mm' => $enabled ? $diameter : null,
            'drain_position' => $enabled ? $position : null,
        ];
    }

    /** Skala model dalam bentuk pengali, dijepit ke rentang yang wajar. */
    private function scale(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 1.0;
        }

        return min(10.0, max(0.05, (float) $value));
    }

    private function clamp(mixed $value, float $fallback, float $min, float $max): float
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return min($max, max($min, (float) $value));
    }

    /** Biaya dibulatkan ke atas pada kelipatan yang diatur di config agar enak dibaca. */
    private function roundCost(float $value): float
    {
        $step = (float) config('printing.cost.rounding', 500);

        if ($step <= 0) {
            return round($value, 2);
        }

        return round(ceil($value / $step) * $step, 2);
    }
}
