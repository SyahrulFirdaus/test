<?php

namespace App\Models\Concerns;

use App\Support\AnalysisStatus;
use App\Support\LeadTime;
use App\Support\MaterialCatalog;
use App\Support\Printer;
use App\Support\PrintResolution;
use Illuminate\Support\Facades\Storage;

/**
 * Atribut turunan yang dipakai bersama oleh penawaran dan tiap modelnya.
 *
 * Sebelum satu penawaran dapat berisi banyak model, seluruh atribut ini hanya
 * ada di App\Models\QuotationRequest. Isinya dipindahkan ke trait agar
 * App\Models\QuotationItem menampilkan angka dengan format yang persis sama —
 * bukan versi yang perlahan berbeda karena disalin.
 */
trait DescribesPrintJob
{
    /** Label ringkas hasil analisis kelayakan cetak. */
    public function getAnalysisLabelAttribute(): string
    {
        return AnalysisStatus::label($this->analysis_status);
    }

    /**
     * Label mesin beserta area cetaknya, mis. "Prusa MK4 (250 × 210 × 220 mm)".
     *
     * Sejak satu model dicetak pada satu mesin, atribut ini berlaku untuk
     * modelnya masing-masing; pada penawaran nilainya mewakili mesin model
     * pertama.
     */
    public function getPrinterLabelAttribute(): string
    {
        $name = $this->printer_name ?: Printer::name($this->printer);
        $volume = $this->build_volume;

        if (! is_array($volume)) {
            return $name;
        }

        $format = fn ($value) => rtrim(rtrim(number_format((float) $value, 1, ',', '.'), '0'), ',');

        return $name.' ('.$format($volume['x'] ?? 0).' × '.$format($volume['y'] ?? 0).' × '.$format($volume['z'] ?? 0).' mm)';
    }

    /**
     * Nama material yang dilihat pelanggan, mis. "PLA+".
     *
     * Yang tersimpan tetap nama katalog beserta brand-nya ("PLA Plus Standart
     * ESUN") — itulah yang menentukan harga dan yang dibaca admin. Accessor ini
     * hanya menerjemahkannya untuk halaman pelanggan; material lama yang tidak
     * lagi ditawarkan dikembalikan apa adanya. Lihat App\Support\MaterialCatalog.
     */
    public function getMaterialLabelAttribute(): ?string
    {
        return blank($this->material)
            ? null
            : MaterialCatalog::displayName((string) $this->technology, (string) $this->material);
    }

    /**
     * Lead time pengerjaan, mis. "3–5 Hari Kerja".
     *
     * Inilah angka yang ditampilkan kepada pelanggan: bukan lama mesin
     * berputar, melainkan perkiraan kapan pesanannya selesai — sudah termasuk
     * antrean produksi, post-processing, dan quality control. Jam mesinnya
     * sendiri tetap tersimpan dan dipakai halaman admin lewat
     * `estimated_duration`.
     */
    public function getLeadTimeAttribute(): ?string
    {
        return blank($this->estimated_minutes)
            ? null
            : LeadTime::label((float) $this->estimated_minutes);
    }

    /**
     * Estimasi waktu dalam bentuk "1 hari 14 jam 20 menit".
     * Format sengaja dibuat sama dengan formatDuration() di sisi browser agar
     * angka yang dilihat admin identik dengan yang dilihat pelanggan.
     */
    public function getEstimatedDurationAttribute(): ?string
    {
        if (blank($this->estimated_minutes)) {
            return null;
        }

        $total = (int) $this->estimated_minutes;
        $days = intdiv($total, 1440);
        $hours = intdiv($total % 1440, 60);
        $minutes = $total % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days} hari";
        }

        if ($hours > 0) {
            $parts[] = "{$hours} jam";
        }

        if ($minutes > 0 || $parts === []) {
            $parts[] = "{$minutes} menit";
        }

        return implode(' ', $parts);
    }

    /** Label resolusi, mis. "0,25 mm (Normal)". */
    public function getResolutionLabelAttribute(): string
    {
        return PrintResolution::label($this->resolution);
    }

    /** Tingkat kualitas hasil cetak sesuai resolusi yang dipilih. */
    public function getQualityLabelAttribute(): string
    {
        return PrintResolution::quality($this->resolution);
    }

    /** Berat model ditambah berat support, dalam gram. */
    public function getTotalWeightGAttribute(): float
    {
        return round((float) $this->estimated_weight_g + (float) $this->support_weight_g, 2);
    }

    /**
     * Harga yang ditawarkan admin bila sudah ditetapkan, jika belum pakai estimasi sistem.
     *
     * Bernilai null selama harganya belum dapat ditetapkan sama sekali —
     * teknologi SLA Industries menunggu kuotasi vendor diisi tim. Pemanggilnya
     * harus menampilkan keterangan "Menunggu Perhitungan", BUKAN Rp0: angka nol
     * terbaca sebagai harga yang sudah pasti.
     */
    public function getDisplayPriceAttribute(): ?float
    {
        if ($this->awaitsPricing()) {
            return null;
        }

        return $this->estimated_price !== null
            ? (float) $this->estimated_price
            : ($this->estimated_cost !== null ? (float) $this->estimated_cost : null);
    }

    /**
     * Harganya belum dapat ditetapkan dan tidak boleh ditampilkan.
     *
     * Nilai bawaannya false; App\Models\QuotationItem dan
     * App\Models\QuotationRequest menimpanya masing-masing.
     */
    public function awaitsPricing(): bool
    {
        return false;
    }

    /** Berkas model disimpan pada disk privat, tidak dapat diakses lewat URL. */
    public function fileExists(): bool
    {
        return filled($this->file_path) && Storage::disk('local')->exists($this->file_path);
    }
}
