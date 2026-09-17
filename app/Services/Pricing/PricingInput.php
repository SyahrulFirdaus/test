<?php

namespace App\Services\Pricing;

use App\Models\QuotationItem;
use App\Support\Finishing;
use App\Support\InfillPattern;
use App\Support\Printer;
use App\Support\PrintResolution;

/**
 * Seluruh masukan Pricing Engine untuk satu model, dalam bentuk baku.
 *
 * Inilah jaminan "model dan spesifikasi sama → harga sama": setiap jalan masuk
 * (Calculator di browser, pengiriman penawaran, tambah/ubah model di dashboard)
 * lebih dulu menyusun objek ini, dan Pricing Engine hanya menerima objek ini.
 * Dua masukan dengan sidik (`fingerprint()`) yang sama pasti berharga sama.
 *
 * Standar satuan internal — tidak ada satuan lain yang boleh masuk:
 *
 *   panjang/dimensi  milimeter (mm)
 *   luas permukaan   sentimeter persegi (cm²)
 *   volume           sentimeter kubik (cm³)
 *
 * Geometri disimpan PADA SKALA 100%. Skala berdiri sendiri sebagai pengali dan
 * baru diberlakukan di dalam engine. Dengan begitu dimensi tidak pernah
 * terskalakan dua kali (atau tidak sama sekali) tergantung jalan masuknya —
 * penyebab Basic Fee dan kardus yang berbeda untuk model yang sama.
 *
 * Seluruh angka dibulatkan ke presisi tetap sebelum dipakai, supaya selisih
 * floating point antara pengukuran browser dan server tidak menghasilkan dua
 * harga yang berbeda sepersekian rupiah.
 */
final class PricingInput
{
    /** Presisi baku: volume & luas 4 desimal, panjang 3 desimal (0,001 mm). */
    private const VOLUME_DECIMALS = 4;

    private const AREA_DECIMALS = 4;

    private const LENGTH_DECIMALS = 3;

    /**
     * @param  array{x: float, y: float, z: float}|null  $dimensionsMm  dimensi pada skala 100%
     * @param  array{enabled: bool, wall_thickness_mm: float|null, drain_hole_diameter_mm: float|null, drain_hole_position: string|null}  $hollow
     * @param  array{x: float, y: float, z: float}|null  $buildVolume
     */
    private function __construct(
        public readonly string $technology,
        public readonly string $material,
        public readonly int $quantity,
        public readonly string $printer,
        public readonly ?array $buildVolume,
        public readonly string $resolution,
        public readonly float $scale,
        public readonly ?float $infillDensity,
        public readonly string $infillPattern,
        public readonly string $finishing,
        public readonly bool $supportEnabled,
        public readonly ?float $measuredSupportVolumeCm3,
        public readonly array $hollow,
        public readonly float $volumeCm3,
        public readonly float $surfaceAreaCm2,
        public readonly ?array $dimensionsMm,
    ) {}

    /**
     * Dari data mentah berkunci snake_case (kiriman browser / formulir).
     *
     * Kunci geometri: `volume_cm3`, `surface_area_cm2`, dan `dimensions`
     * — ketiganya pada skala 100%.
     *
     * `support_volume_cm3` hanya boleh berisi volume support yang BENAR-BENAR
     * diukur dari geometri support. Nol yang lahir karena support sedang
     * dimatikan bukan hasil ukur dan harus dikirim null.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $scale = is_numeric($data['scale_percent'] ?? null)
            ? ((float) $data['scale_percent']) / 100
            : (is_numeric($data['scale'] ?? null) ? (float) $data['scale'] : 1.0);

        $printer = Printer::exists($data['printer'] ?? null) ? (string) $data['printer'] : Printer::default();
        $hollow = is_array($data['hollow'] ?? null) ? $data['hollow'] : [
            'enabled' => $data['hollow_enabled'] ?? false,
            'wall_thickness_mm' => $data['hollow_wall_thickness_mm'] ?? null,
            'drain_hole_diameter_mm' => $data['hollow_drain_diameter_mm'] ?? null,
            'drain_hole_position' => $data['hollow_drain_position'] ?? null,
        ];

        return new self(
            technology: strtoupper(trim((string) ($data['technology'] ?? ''))),
            material: trim((string) ($data['material'] ?? '')),
            quantity: max(1, (int) ($data['quantity'] ?? 1)),
            printer: $printer,
            buildVolume: is_array($data['build_volume'] ?? null)
                ? Printer::buildVolume($printer, $data['build_volume'])
                : Printer::buildVolume($printer, null),
            resolution: PrintResolution::exists($data['resolution'] ?? null) ? (string) $data['resolution'] : PrintResolution::default(),
            scale: round(min(10.0, max(0.05, $scale)), 4),
            infillDensity: is_numeric($data['infill_density'] ?? null) ? round(min(1.0, max(0.0, (float) $data['infill_density'])), 4) : null,
            infillPattern: InfillPattern::exists($data['infill_pattern'] ?? null) ? (string) $data['infill_pattern'] : InfillPattern::default(),
            finishing: Finishing::exists($data['finishing'] ?? null) ? (string) $data['finishing'] : Finishing::default(),
            supportEnabled: filter_var($data['support_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            measuredSupportVolumeCm3: is_numeric($data['support_volume_cm3'] ?? null)
                ? round(max(0.0, (float) $data['support_volume_cm3']), self::VOLUME_DECIMALS)
                : null,
            hollow: [
                'enabled' => filter_var($hollow['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'wall_thickness_mm' => is_numeric($hollow['wall_thickness_mm'] ?? null) ? round((float) $hollow['wall_thickness_mm'], 2) : null,
                'drain_hole_diameter_mm' => is_numeric($hollow['drain_hole_diameter_mm'] ?? null) ? round((float) $hollow['drain_hole_diameter_mm'], 2) : null,
                'drain_hole_position' => filled($hollow['drain_hole_position'] ?? null) ? (string) $hollow['drain_hole_position'] : null,
            ],
            volumeCm3: round(max(0.0, (float) ($data['volume_cm3'] ?? 0)), self::VOLUME_DECIMALS),
            surfaceAreaCm2: round(max(0.0, (float) ($data['surface_area_cm2'] ?? 0)), self::AREA_DECIMALS),
            dimensionsMm: self::normalizeDimensions($data['dimensions'] ?? null),
        );
    }

    /**
     * Dari model penawaran yang tersimpan, dengan pengaturan yang boleh ditimpa.
     *
     * Dipakai saat pemilik penawaran mengubah spesifikasi di dashboard: geometri
     * tetap milik berkas yang tersimpan, pengaturannya yang baru.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function fromItem(QuotationItem $item, array $overrides = []): self
    {
        $stats = is_array($item->model_stats) ? $item->model_stats : [];

        return self::fromArray([
            'technology' => $item->technology,
            'material' => $item->material,
            'quantity' => $item->quantity,
            'printer' => $item->printer,
            'build_volume' => $item->build_volume,
            'resolution' => $item->resolution,
            'scale_percent' => $item->scale_percent,
            'infill_density' => $item->infill_density,
            'infill_pattern' => $item->infill_pattern,
            'finishing' => $item->finishing,
            'support_enabled' => $item->support_enabled,
            'support_volume_cm3' => self::storedMeasuredSupport($item),
            'hollow_enabled' => $item->hollow_enabled,
            'hollow_wall_thickness_mm' => $item->hollow_wall_thickness_mm,
            'hollow_drain_diameter_mm' => $item->hollow_drain_diameter_mm,
            'hollow_drain_position' => $item->hollow_drain_position,
            ...ModelGeometry::fromStats($stats, (float) ($item->model_volume_cm3 ?? 0)),
            ...$overrides,
        ]);
    }

    /**
     * Volume support terukur yang tersimpan pada model, atau null.
     *
     * Kolom `support_volume_cm3` juga terisi 0 saat support dimatikan — nol itu
     * bukan hasil ukur. Hanya model yang supportnya menyala DAN tercatat diukur
     * di viewer yang boleh memakai angkanya kembali.
     */
    public static function storedMeasuredSupport(QuotationItem $item): ?float
    {
        $stats = is_array($item->model_stats) ? $item->model_stats : [];

        if (! $item->support_enabled || $item->support_volume_cm3 === null) {
            return null;
        }

        // Model lama tidak menyimpan penandanya; volume positif pada support
        // yang menyala hanya bisa berasal dari pengukuran viewer.
        $measured = $stats['support_measured'] ?? ((float) $item->support_volume_cm3 > 0);

        return $measured ? (float) $item->support_volume_cm3 : null;
    }

    /** Salinan dengan beberapa nilai diganti. @param  array<string, mixed>  $changes */
    public function with(array $changes): self
    {
        return self::fromArray([...$this->toArray(), ...$changes]);
    }

    /** Dimensi setelah skala diberlakukan, dalam mm. @return array{x: float, y: float, z: float}|null */
    public function scaledDimensionsMm(): ?array
    {
        if ($this->dimensionsMm === null) {
            return null;
        }

        return array_map(fn (float $side) => round($side * $this->scale, self::LENGTH_DECIMALS), $this->dimensionsMm);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'technology' => $this->technology,
            'material' => $this->material,
            'quantity' => $this->quantity,
            'printer' => $this->printer,
            'build_volume' => $this->buildVolume,
            'resolution' => $this->resolution,
            'scale' => $this->scale,
            'infill_density' => $this->infillDensity,
            'infill_pattern' => $this->infillPattern,
            'finishing' => $this->finishing,
            'support_enabled' => $this->supportEnabled,
            'support_volume_cm3' => $this->measuredSupportVolumeCm3,
            'hollow' => $this->hollow,
            'volume_cm3' => $this->volumeCm3,
            'surface_area_cm2' => $this->surfaceAreaCm2,
            'dimensions' => $this->dimensionsMm,
            'units' => ['length' => 'mm', 'area' => 'cm2', 'volume' => 'cm3'],
        ];
    }

    /** Sidik masukan: sama persis berarti harga pasti sama. */
    public function fingerprint(): string
    {
        return sha1(json_encode($this->toArray(), JSON_PRESERVE_ZERO_FRACTION));
    }

    /** @return array{x: float, y: float, z: float}|null */
    private static function normalizeDimensions(mixed $dimensions): ?array
    {
        if (! is_array($dimensions)) {
            return null;
        }

        $sides = [];

        foreach (['x', 'y', 'z'] as $axis) {
            if (! is_numeric($dimensions[$axis] ?? null)) {
                return null;
            }

            $sides[$axis] = round(max(0.0, (float) $dimensions[$axis]), self::LENGTH_DECIMALS);
        }

        return $sides;
    }
}
