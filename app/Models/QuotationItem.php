<?php

namespace App\Models;

use App\Models\Concerns\DescribesPrintJob;
use App\Support\Finishing;
use App\Support\InfillPattern;
use App\Support\MaterialColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Satu model 3D di dalam sebuah permintaan penawaran.
 *
 * Setiap item memegang berkasnya sendiri beserta pengaturan printing, hasil
 * analisis, dan estimasinya — sehingga mengubah resolusi atau material pada
 * satu model tidak menyentuh model lain dalam penawaran yang sama.
 */
class QuotationItem extends Model
{
    use DescribesPrintJob;

    protected $fillable = [
        'position',
        'file_name',
        'file_path',
        'file_format',
        'file_size',
        'model_stats',
        'analysis_status',
        'analysis',
        'technology',
        'material',
        'printer',
        'printer_name',
        'build_volume',
        'quantity',
        'scale_percent',
        'resolution',
        'layer_height_mm',
        'infill_density',
        'infill_pattern',
        'support_enabled',
        'support_type',
        'hollow_enabled',
        'hollow_wall_thickness_mm',
        'hollow_drain_diameter_mm',
        'hollow_drain_position',
        'material_color',
        'finishing',
        'fits_build_volume',
        'model_volume_cm3',
        'material_volume_cm3',
        'support_volume_cm3',
        'estimated_weight_g',
        'support_weight_g',
        'estimated_minutes',
        'estimated_cost',
        'cost_breakdown',
        'estimated_price',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'model_stats' => 'array',
            'analysis' => 'array',
            'cost_breakdown' => 'array',
            'build_volume' => 'array',
            'position' => 'integer',
            'quantity' => 'integer',
            'file_size' => 'integer',
            'scale_percent' => 'decimal:2',
            'infill_density' => 'decimal:4',
            'support_enabled' => 'boolean',
            'hollow_enabled' => 'boolean',
            'hollow_wall_thickness_mm' => 'decimal:2',
            'hollow_drain_diameter_mm' => 'decimal:2',
            'fits_build_volume' => 'boolean',
            'layer_height_mm' => 'decimal:3',
            'model_volume_cm3' => 'decimal:3',
            'material_volume_cm3' => 'decimal:3',
            'support_volume_cm3' => 'decimal:3',
            'estimated_weight_g' => 'decimal:2',
            'support_weight_g' => 'decimal:2',
            'estimated_minutes' => 'integer',
            'estimated_cost' => 'decimal:2',
            'estimated_price' => 'decimal:2',
        ];
    }

    public function quotationRequest(): BelongsTo
    {
        return $this->belongsTo(QuotationRequest::class);
    }

    /** Dimensi model dalam milimeter, bila tercatat saat analisis di browser. */
    public function getDimensionsAttribute(): ?array
    {
        $dimensions = $this->model_stats['dimensions'] ?? null;

        return is_array($dimensions) ? $dimensions : null;
    }

    /** Skala dalam bentuk persen bulat, mis. "120%". */
    public function getScaleLabelAttribute(): string
    {
        return rtrim(rtrim(number_format((float) $this->scale_percent, 1, ',', '.'), '0'), ',').'%';
    }

    /** Apakah model dicetak pada ukuran aslinya. */
    public function isOriginalScale(): bool
    {
        return abs((float) $this->scale_percent - 100) < 0.01;
    }

    /** Ringkasan infill, mis. "20% · Gyroid". */
    public function getInfillLabelAttribute(): string
    {
        $density = number_format((float) $this->infill_density * 100, 0, ',', '.');

        return $density.'% · '.InfillPattern::label($this->infill_pattern);
    }

    /** Ringkasan hollow, mis. "Ya (dinding 2,0 mm, drain 3,5 mm di Dasar Model)". */
    public function getHollowLabelAttribute(): string
    {
        if (! $this->hollow_enabled) {
            return 'Tidak';
        }

        $position = config('printing.hollow.drain_hole.positions.'.$this->hollow_drain_position.'.label')
            ?? $this->hollow_drain_position;

        return sprintf(
            'Ya — dinding %s mm, lubang %s mm di %s',
            number_format((float) $this->hollow_wall_thickness_mm, 1, ',', '.'),
            number_format((float) $this->hollow_drain_diameter_mm, 1, ',', '.'),
            $position,
        );
    }

    public function getMaterialColorLabelAttribute(): string
    {
        return MaterialColor::label($this->material_color);
    }

    /** Label finishing, mis. "Sanding". */
    public function getFinishingLabelAttribute(): string
    {
        return Finishing::label($this->finishing);
    }

    /**
     * Ringkasan spesifikasi satu model, mis.
     * "FDM · PLA · Putih · Sanding · 8 pcs".
     */
    public function getSpecificationSummaryAttribute(): string
    {
        return implode(' · ', [
            $this->technology,
            $this->material,
            $this->material_color_label,
            $this->finishing_label,
            $this->quantity.' pcs',
        ]);
    }

    public function getMaterialColorHexAttribute(): string
    {
        return MaterialColor::hex($this->material_color);
    }

    /**
     * Hapus berkas model saat itemnya dihapus.
     *
     * Penghapusan penawaran memakai `cascadeOnDelete` di level basis data yang
     * tidak memicu event model, jadi App\Models\QuotationRequest menghapus
     * berkas seluruh itemnya lebih dulu.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $item) {
            if ($item->fileExists()) {
                Storage::disk('local')->delete($item->file_path);
            }
        });
    }
}
