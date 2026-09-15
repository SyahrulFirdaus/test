<?php

namespace App\Services;

/**
 * Estimasi kebutuhan support structure.
 *
 * Saat ini perhitungannya berupa simulasi: volume support diperkirakan sebagai
 * proporsi dari volume model, disesuaikan dengan kelangsingan part (part tinggi
 * dan langsing butuh lebih banyak penopang). Seluruh angkanya berada di
 * config/printing.php sehingga mudah dikalibrasi.
 *
 * Kelas ini sengaja dipisahkan dari PrintEstimator agar tahap berikutnya —
 * deteksi otomatis kebutuhan support, analisis sudut overhang dari mesh, dan
 * perhitungan volume support yang sebenarnya — cukup mengganti isi
 * estimate() tanpa menyentuh rumus biaya dan waktu.
 */
class SupportEstimator
{
    /** @return array<string, mixed> */
    public function config(): array
    {
        return config('printing.support', []);
    }

    /** Apakah teknologi ini memang membutuhkan support. */
    public function isRequiredFor(string $technology): bool
    {
        return $this->volumeFactor($technology) > 0;
    }

    public function volumeFactor(string $technology): float
    {
        // Dibaca dari teknologi yang dikelola Superadmin, bukan lagi config:
        // teknologi baru langsung membawa kebutuhan supportnya sendiri.
        return (float) (\App\Models\PrintTechnology::findByCode($technology)?->support_volume_factor ?? 0.0);
    }

    /** Alasan singkat mengapa support tidak diperlukan, bila memang begitu. */
    public function unavailableReason(string $technology): ?string
    {
        if ($this->isRequiredFor($technology)) {
            return null;
        }

        return 'Teknologi '.strtoupper($technology).' tidak memerlukan support karena part tertopang serbuk di sekelilingnya sepanjang proses cetak.';
    }

    /**
     * @param  array{
     *     enabled?: bool,
     *     dimensions?: array{x?: float, y?: float, z?: float}|null,
     *     type?: string|null,
     *     measured_volume_cm3?: float|null
     * }  $options
     * @return array<string, mixed>
     */
    public function estimate(string $technology, float $density, float $modelVolumeCm3, array $options = []): array
    {
        $support = $this->config();
        $infill = (float) ($support['infill'] ?? 0.3);
        $required = $this->isRequiredFor($technology);
        $enabled = ($options['enabled'] ?? false) && $required;

        $type = $options['type'] ?? ($support['default_type'] ?? 'normal');
        $typeMultiplier = (float) ($support['types'][$type]['multiplier'] ?? 1.0);

        $aspectMultiplier = $this->aspectMultiplier($options['dimensions'] ?? null);

        // Bila viewer sudah membentuk support sungguhan, volumenya terukur dari
        // geometri tersebut. Rumus simulasi hanya dipakai sebagai cadangan bila
        // pengukuran memang tidak dikirim.
        //
        // Nol yang terukur tetap dihormati: pada orientasi tertentu bisa saja
        // tidak ada overhang yang perlu ditopang sama sekali.
        $measuredVolume = $options['measured_volume_cm3'] ?? null;
        $measured = $enabled && is_numeric($measuredVolume) && (float) $measuredVolume >= 0;

        $grossVolume = 0.0;
        $materialVolume = 0.0;
        $weight = 0.0;

        if ($enabled) {
            if ($measured) {
                $materialVolume = (float) $measuredVolume;
                $grossVolume = $infill > 0 ? $materialVolume / $infill : $materialVolume;
            } else {
                $grossVolume = max(0.0, $modelVolumeCm3) * $this->volumeFactor($technology) * $typeMultiplier * $aspectMultiplier;
                $materialVolume = $grossVolume * $infill;
            }

            $weight = $materialVolume * max(0.0, $density);
        }

        return [
            'enabled' => $enabled,
            'required' => $required,
            'measured' => $measured,
            'type' => $type,
            'gross_volume_cm3' => round($grossVolume, 3),
            'volume_cm3' => round($materialVolume, 3),
            'weight_g' => round($weight, 2),
            'factor' => $this->volumeFactor($technology),
            'aspect_multiplier' => round($aspectMultiplier, 3),
            'note' => $this->unavailableReason($technology),
        ];
    }

    /**
     * Part yang tinggi dan langsing memerlukan lebih banyak support daripada
     * part pendek dan lebar, jadi rasio tinggi terhadap tapak ikut diperhitungkan.
     *
     * @param  array{x?: float, y?: float, z?: float}|null  $dimensions
     */
    private function aspectMultiplier(?array $dimensions): float
    {
        $support = $this->config();

        $height = (float) ($dimensions['y'] ?? 0);
        $footprint = max((float) ($dimensions['x'] ?? 0), (float) ($dimensions['z'] ?? 0));

        if ($height <= 0 || $footprint <= 0) {
            return 1.0;
        }

        $aspect = min($height / $footprint, (float) ($support['max_aspect'] ?? 3.0));

        return 1.0 + ($aspect * (float) ($support['height_influence'] ?? 0.25));
    }
}
