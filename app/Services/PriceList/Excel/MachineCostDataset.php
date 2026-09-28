<?php

namespace App\Services\PriceList\Excel;

use App\Models\MachineCost;
use App\Models\PrintTechnology;
use App\Support\Printer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Lembar Excel menu Machine Cost pada Price List.
 *
 * Kolomnya adalah isian form Tambah/Ubah Mesin dengan batas nilai yang sama
 * seperti App\Http\Requests\StoreMachineCostRequest. Nama mesin menjadi
 * pengenal baris — sama seperti PriceListSeeder memperlakukannya.
 *
 * Listrik/Hour, Machine Cost, dan Pembulatan ikut ditulis karena itulah bentuk
 * tabel yang dipakai tim, tetapi tidak pernah disimpan: ketiganya selalu
 * dihitung App\Models\MachineCost dari Watt, Harga Listrik, dan Depresiasi.
 * Saat Import nilainya hanya dicocokkan, dan selisihnya dilaporkan.
 *
 * Teknologi ditulis dengan kodenya dan Printer dengan namanya; keduanya
 * diterjemahkan ke `print_technology_id` dan `printer_key` sebelum disimpan.
 * Material yang menunjuk mesin tidak disentuh sama sekali.
 */
class MachineCostDataset extends SpreadsheetDataset
{
    private const MAX_RUPIAH = 9999999999.99;

    /** Printer yang sudah dipakai baris sebelumnya di berkas yang sama. */
    private array $printersInFile = [];

    /** @var Collection<int, PrintTechnology>|null */
    private ?Collection $technologies = null;

    public function key(): string
    {
        return 'machine-cost';
    }

    public function title(): string
    {
        return 'Machine Cost';
    }

    public function noun(): string
    {
        return 'mesin';
    }

    public function fileStem(): string
    {
        return 'Machine_Cost';
    }

    public function identityKey(): string
    {
        return 'mesin';
    }

    protected function headerAnchors(): array
    {
        return ['mesin', 'watt_kwh'];
    }

    protected function query(): Builder
    {
        return MachineCost::query()->with('technology')->ordered();
    }

    protected function extraStoredKeys(): array
    {
        return ['print_technology_id', 'printer_key'];
    }

    public function columns(): array
    {
        $specs = fn (array $record) => new MachineCost([
            'watt_kwh' => $record['watt_kwh'] ?? 0,
            'harga_listrik' => $record['harga_listrik'] ?? 0,
            'depresiasi' => $record['depresiasi'] ?? 0,
        ]);

        return [
            ['key' => 'no', 'heading' => 'No', 'width' => 6, 'money' => false],
            ['key' => 'mesin', 'heading' => 'Mesin', 'width' => 26, 'money' => false, 'wrap' => true, 'type' => 'text', 'required' => true, 'max_length' => 60],
            ['key' => 'technology', 'heading' => 'Teknologi', 'width' => 12, 'money' => false, 'type' => 'text', 'stored' => false],
            ['key' => 'printer', 'heading' => 'Printer pada Calculator', 'aliases' => ['Printer', 'Printer Calculator'], 'width' => 22, 'money' => false, 'type' => 'text', 'stored' => false],
            ['key' => 'watt_kwh', 'heading' => 'Watt (KWH)', 'width' => 12, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0, 'max' => 999.999, 'decimals' => 3],
            ['key' => 'harga_listrik', 'heading' => 'Harga Listrik', 'aliases' => ['Harga Listrik (Rp/KWH)'], 'width' => 16, 'money' => true, 'type' => 'money', 'required' => true, 'min' => 0, 'max' => self::MAX_RUPIAH],
            ['key' => 'depresiasi', 'heading' => 'Depresiasi', 'aliases' => ['Depresiasi (Rp/jam)'], 'width' => 16, 'money' => true, 'type' => 'money', 'required' => true, 'min' => 0, 'max' => self::MAX_RUPIAH],
            ['key' => 'electricity_per_hour', 'heading' => 'Listrik/Hour', 'width' => 14, 'money' => true, 'type' => 'money', 'derived' => true, 'compute' => fn (array $r) => (int) round($specs($r)->electricity_per_hour)],
            ['key' => 'machine_cost', 'heading' => 'Machine Cost', 'width' => 14, 'money' => true, 'type' => 'money', 'derived' => true, 'compute' => fn (array $r) => $specs($r)->machine_cost],
            ['key' => 'rounded_machine_cost', 'heading' => 'Pembulatan', 'width' => 14, 'money' => true, 'type' => 'money', 'derived' => true, 'compute' => fn (array $r) => $specs($r)->rounded_machine_cost],
            ['key' => 'width_mm', 'heading' => 'Lebar W (mm)', 'width' => 12, 'money' => false, 'type' => 'decimal', 'min' => 0, 'max' => 99999, 'decimals' => 1, 'blank' => 'keep'],
            ['key' => 'depth_mm', 'heading' => 'Kedalaman D (mm)', 'width' => 14, 'money' => false, 'type' => 'decimal', 'min' => 0, 'max' => 99999, 'decimals' => 1, 'blank' => 'keep'],
            ['key' => 'height_mm', 'heading' => 'Tinggi H (mm)', 'width' => 12, 'money' => false, 'type' => 'decimal', 'min' => 0, 'max' => 99999, 'decimals' => 1, 'blank' => 'keep'],
            ['key' => 'weight_kg', 'heading' => 'Berat (kg)', 'width' => 11, 'money' => false, 'type' => 'decimal', 'min' => 0, 'max' => 9999, 'decimals' => 2, 'blank' => 'keep'],
            ['key' => 'build_volume_x', 'heading' => 'Volume Cetak X (mm)', 'width' => 14, 'money' => false, 'type' => 'integer', 'min' => 1, 'max' => 100000, 'blank' => 'keep'],
            ['key' => 'build_volume_y', 'heading' => 'Volume Cetak Y (mm)', 'width' => 14, 'money' => false, 'type' => 'integer', 'min' => 1, 'max' => 100000, 'blank' => 'keep'],
            ['key' => 'build_volume_z', 'heading' => 'Volume Cetak Z (mm)', 'width' => 14, 'money' => false, 'type' => 'integer', 'min' => 1, 'max' => 100000, 'blank' => 'keep'],
        ];
    }

    protected function beforeRead(): void
    {
        $this->printersInFile = [];
        $this->technologies = PrintTechnology::query()->managed()->ordered()->get();
    }

    /**
     * Terjemahkan Teknologi dan Printer, lalu pastikan satu printer hanya
     * dipetakan ke satu mesin — aturan yang sama dengan form Ubah Mesin.
     */
    protected function validateRow(array &$values, array &$errors, array &$notes, ?Model $current): void
    {
        /** @var MachineCost|null $current */

        // Teknologi: kosong berarti tetap seperti sekarang (atau tanpa teknologi).
        if ($values['technology'] === null) {
            $values['print_technology_id'] = $current?->print_technology_id;
            $values['technology'] = $current?->technology?->code;
        } else {
            $technology = $this->findTechnology($values['technology']);

            if ($technology === null) {
                $errors[] = 'Teknologi "'.$values['technology'].'" tidak ditemukan. Isi dengan salah satu kode: '
                    .$this->technologies->pluck('code')->implode(', ').'.';
            } else {
                $values['print_technology_id'] = $technology->id;
                $values['technology'] = $technology->code;
            }
        }

        // Printer: kosong berarti pemetaan yang ada dipertahankan.
        if ($values['printer'] === null) {
            $values['printer_key'] = $current?->printer_key;
            $values['printer'] = $current?->printer_label;

            return;
        }

        $key = $this->findPrinter($values['printer']);

        if ($key === null) {
            $errors[] = 'Printer "'.$values['printer'].'" tidak dikenal. Isi dengan salah satu: '
                .collect(Printer::all())->pluck('name')->implode(', ').'.';

            return;
        }

        if (isset($this->printersInFile[$key])) {
            $errors[] = 'Printer '.Printer::name($key).' sudah dipetakan ke mesin "'.$this->printersInFile[$key]
                .'" di file ini. Satu printer hanya untuk satu mesin.';

            return;
        }

        $takenBy = MachineCost::where('printer_key', $key)
            ->when($current !== null, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->value('mesin');

        if ($takenBy !== null) {
            $errors[] = 'Printer '.Printer::name($key).' sudah dipetakan ke mesin "'.$takenBy.'". Lepaskan pemetaannya lebih dulu.';

            return;
        }

        $this->printersInFile[$key] = $values['mesin'];
        $values['printer_key'] = $key;
        $values['printer'] = Printer::name($key);
    }

    private function findTechnology(string $text): ?PrintTechnology
    {
        $needle = mb_strtolower(trim($text));

        return $this->technologies->first(fn (PrintTechnology $technology) => in_array($needle, [
            mb_strtolower($technology->code),
            mb_strtolower($technology->name),
            mb_strtolower($technology->tabLabel()),
        ], true));
    }

    private function findPrinter(string $text): ?string
    {
        $needle = mb_strtolower(trim($text));

        foreach (Printer::all() as $key => $printer) {
            if ($needle === mb_strtolower($key) || $needle === mb_strtolower((string) $printer['name'])) {
                return $key;
            }
        }

        return null;
    }

    protected function exportRecord(Model $model): array
    {
        /** @var MachineCost $model */
        return [
            ...parent::exportRecord($model),
            'technology' => $model->technology?->code,
            'printer' => $model->printer_label,
        ];
    }

    protected function snapshot(Model $model): array
    {
        /** @var MachineCost $model */
        return [
            'mesin' => $model->mesin,
            'teknologi' => $model->technology()->value('code'),
            'printer_calculator' => $model->printer_label,
            'watt_kwh' => (float) $model->watt_kwh,
            'harga_listrik' => (float) $model->harga_listrik,
            'depresiasi' => (float) $model->depresiasi,
            'lebar_mm' => $model->width_mm,
            'kedalaman_mm' => $model->depth_mm,
            'tinggi_mm' => $model->height_mm,
            'berat_kg' => $model->weight_kg,
            'volume_cetak' => $model->build_volume_label,
        ];
    }

    /**
     * Data acuan berkas Contoh: mesin yang dipakai NUSAMA3D (lihat
     * PriceListSeeder). Spesifikasi fisik hanya diisi yang sudah diketahui;
     * sisanya dibiarkan kosong, sebagaimana form juga mengizinkannya.
     */
    protected function exampleRecords(): array
    {
        return [
            ['mesin' => 'Elegoo Neptune Max 4', 'technology' => 'FDM', 'printer' => null, 'watt_kwh' => 0.55, 'harga_listrik' => 1700, 'depresiasi' => 5000, 'width_mm' => null, 'depth_mm' => null, 'height_mm' => null, 'weight_kg' => null, 'build_volume_x' => null, 'build_volume_y' => null, 'build_volume_z' => null],
            ['mesin' => 'Ender 3 V2', 'technology' => 'FDM', 'printer' => 'Creality Ender 3', 'watt_kwh' => 0.25, 'harga_listrik' => 1700, 'depresiasi' => 2100, 'width_mm' => null, 'depth_mm' => null, 'height_mm' => null, 'weight_kg' => null, 'build_volume_x' => 220, 'build_volume_y' => 220, 'build_volume_z' => 250],
            ['mesin' => 'Bambu Lab P1S', 'technology' => 'FDM', 'printer' => 'Bambu Lab X1 Carbon', 'watt_kwh' => 0.35, 'harga_listrik' => 1700, 'depresiasi' => 5300, 'width_mm' => 389, 'depth_mm' => 389, 'height_mm' => 458, 'weight_kg' => 12.95, 'build_volume_x' => 256, 'build_volume_y' => 256, 'build_volume_z' => 256],
            ['mesin' => 'Elegoo Saturn 4 12 K', 'technology' => 'SLA', 'printer' => null, 'watt_kwh' => 0.144, 'harga_listrik' => 1700, 'depresiasi' => 5100, 'width_mm' => null, 'depth_mm' => null, 'height_mm' => null, 'weight_kg' => null, 'build_volume_x' => null, 'build_volume_y' => null, 'build_volume_z' => null],
        ];
    }
}
