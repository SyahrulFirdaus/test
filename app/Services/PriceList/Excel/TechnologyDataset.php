<?php

namespace App\Services\PriceList\Excel;

use App\Models\PrintTechnology;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Lembar Excel menu Teknologi pada Price List.
 *
 * Kolomnya adalah isian form Tambah/Ubah Teknologi, satu per satu, dengan batas
 * nilai yang sama seperti App\Http\Requests\StorePrintTechnologyRequest. Kode
 * menjadi pengenal baris: kode yang sudah ada dilewati atau diperbarui sesuai
 * pilihan pada pratinjau — kodenya sendiri tidak pernah berubah, jadi
 * penawaran lama yang menunjuk kode itu tetap terbaca.
 *
 * Hollow Model tidak ada di berkas Excel: bila tertulis di berkas lama,
 * kolomnya diabaikan dan pengaturan Hollow teknologi yang ada tidak berubah
 * (hanya dapat diubah lewat form Ubah Teknologi).
 *
 * Yang tidak disentuh: material, mesin, Rumus Harga, dan teknologi yang
 * diarsipkan. Kode milik teknologi arsip boleh dipakai lagi; barisnya
 * dihidupkan kembali, persis seperti form Tambah Teknologi.
 */
class TechnologyDataset extends SpreadsheetDataset
{
    /** Kolom rupiah `decimal(12,2)`. */
    private const MAX_RUPIAH = 9999999999.99;

    public function key(): string
    {
        return 'technology';
    }

    public function title(): string
    {
        return 'Teknologi';
    }

    public function noun(): string
    {
        return 'teknologi';
    }

    public function fileStem(): string
    {
        return 'Teknologi';
    }

    public function identityKey(): string
    {
        return 'code';
    }

    protected function headerAnchors(): array
    {
        return ['code', 'name'];
    }

    protected function query(): Builder
    {
        return PrintTechnology::query()->managed()->ordered();
    }

    public function columns(): array
    {
        return [
            ['key' => 'no', 'heading' => 'No', 'width' => 6, 'money' => false],
            [
                'key' => 'code', 'heading' => 'Kode', 'width' => 10, 'money' => false,
                'type' => 'text', 'required' => true, 'max_length' => 12, 'uppercase' => true,
                'pattern' => '/^[A-Za-z0-9]+$/', 'pattern_message' => 'Kode hanya boleh huruf dan angka, tanpa spasi.',
            ],
            ['key' => 'name', 'heading' => 'Nama Lengkap', 'aliases' => ['Nama'], 'width' => 28, 'money' => false, 'wrap' => true, 'type' => 'text', 'required' => true, 'max_length' => 120],
            ['key' => 'family', 'heading' => 'Keluarga Bahan', 'aliases' => ['Keluarga'], 'width' => 16, 'money' => false, 'type' => 'text', 'max_length' => 60],
            ['key' => 'description', 'heading' => 'Deskripsi', 'width' => 40, 'money' => false, 'wrap' => true, 'type' => 'text', 'max_length' => 2000],
            [
                'key' => 'sort_order', 'heading' => 'Urutan Tab', 'width' => 12, 'money' => false,
                'type' => 'integer', 'min' => 0, 'max' => 100000,
                'blank' => 'keep', 'default' => fn () => (int) (PrintTechnology::max('sort_order') ?? 0) + 10,
            ],
            ['key' => 'build_volume_x', 'heading' => 'Lebar X (mm)', 'aliases' => ['Lebar (X) (mm)', 'Lebar (X)'], 'width' => 12, 'money' => false, 'type' => 'integer', 'required' => true, 'min' => 10, 'max' => 5000],
            ['key' => 'build_volume_y', 'heading' => 'Kedalaman Y (mm)', 'aliases' => ['Kedalaman (Y) (mm)', 'Kedalaman (Y)'], 'width' => 14, 'money' => false, 'type' => 'integer', 'required' => true, 'min' => 10, 'max' => 5000],
            ['key' => 'build_volume_z', 'heading' => 'Tinggi Z (mm)', 'aliases' => ['Tinggi (Z) (mm)', 'Tinggi (Z)'], 'width' => 12, 'money' => false, 'type' => 'integer', 'required' => true, 'min' => 10, 'max' => 5000],
            ['key' => 'shell_ratio', 'heading' => 'Shell Ratio', 'aliases' => ['Shell Ratio (0–1)', 'Shell Ratio (0-1)'], 'width' => 12, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0, 'max' => 1],
            ['key' => 'default_infill', 'heading' => 'Infill Bawaan', 'aliases' => ['Infill Bawaan (0–1)', 'Infill Bawaan (0-1)'], 'width' => 13, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0, 'max' => 1],
            ['key' => 'min_wall_thickness_mm', 'heading' => 'Tebal Dinding Minimum (mm)', 'width' => 16, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0.1, 'max' => 50],
            ['key' => 'support_volume_factor', 'heading' => 'Faktor Volume Support', 'width' => 14, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0, 'max' => 5],
            ['key' => 'infill_note', 'heading' => 'Catatan Infill', 'width' => 24, 'money' => false, 'wrap' => true, 'type' => 'text', 'max_length' => 500],
            ['key' => 'throughput_cm3_per_hour', 'heading' => 'Laju Cetak (cm³/jam)', 'aliases' => ['Laju Cetak (cm3/jam)', 'Laju Cetak'], 'width' => 14, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0.1, 'max' => 100000, 'decimals' => 2],
            ['key' => 'setup_hours', 'heading' => 'Waktu Persiapan (jam)', 'width' => 14, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0, 'max' => 1000, 'decimals' => 2],
            ['key' => 'setup_fee', 'heading' => 'Biaya Persiapan', 'aliases' => ['Biaya Persiapan (Rp)'], 'width' => 16, 'money' => true, 'type' => 'money', 'required' => true, 'min' => 0, 'max' => self::MAX_RUPIAH],
            ['key' => 'machine_rate_per_hour', 'heading' => 'Tarif Mesin (Rp/jam)', 'aliases' => ['Tarif Mesin'], 'width' => 16, 'money' => true, 'type' => 'money', 'required' => true, 'min' => 0, 'max' => self::MAX_RUPIAH],
            ['key' => 'layer_height_min', 'heading' => 'Tebal Lapisan Minimum (mm)', 'width' => 16, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0.001, 'max' => 5],
            ['key' => 'layer_height_max', 'heading' => 'Tebal Lapisan Maksimum (mm)', 'width' => 16, 'money' => false, 'type' => 'decimal', 'required' => true, 'min' => 0.001, 'max' => 5],
            [
                'key' => 'is_active', 'heading' => 'Status', 'width' => 12, 'money' => false,
                'type' => 'boolean', 'labels' => ['Aktif', 'Nonaktif'], 'blank' => 'keep', 'default' => true,
            ],
        ];
    }

    protected function validateRow(array &$values, array &$errors, array &$notes, ?Model $current): void
    {
        if ($values['layer_height_max'] < $values['layer_height_min']) {
            $errors[] = 'Tebal Lapisan Maksimum tidak boleh lebih kecil daripada minimumnya.';
        }

        // Penawaran lama menunjuk kodenya; yang diperbarui hanya parameternya.
        if ($current === null && PrintTechnology::where('code', $values['code'])->whereNotNull('archived_at')->exists()) {
            $notes[] = 'Kode ini milik teknologi yang diarsipkan; barisnya dipakai lagi dengan isian dari file.';
        }
    }

    /**
     * Kode milik teknologi arsip dihidupkan kembali, bukan dibuat kembar —
     * sama seperti form Tambah Teknologi.
     */
    protected function createRecord(array $values): Model
    {
        $archived = PrintTechnology::where('code', $values['code'])->whereNotNull('archived_at')->first();

        if ($archived !== null) {
            $archived->update($values + ['archived_at' => null]);

            return $archived;
        }

        return PrintTechnology::create($values);
    }

    /** Edit Specification butuh setidaknya satu teknologi untuk dipilih. */
    protected function afterRows(): void
    {
        if (PrintTechnology::query()->active()->doesntExist()) {
            throw new SpreadsheetImportException(
                'Import dibatalkan: setelah import tidak ada satu pun teknologi yang aktif, '
                .'padahal Edit Specification membutuhkan setidaknya satu. Tidak ada data yang berubah.'
            );
        }
    }

    protected function afterImport(): void
    {
        PrintTechnology::forgetCache();
    }

    protected function snapshot(Model $model): array
    {
        /** @var PrintTechnology $model */
        return [
            ...$model->only([
                'code', 'name', 'family', 'description', 'build_volume_x', 'build_volume_y', 'build_volume_z',
                'shell_ratio', 'default_infill', 'infill_note', 'min_wall_thickness_mm', 'support_volume_factor',
                'layer_height_min', 'layer_height_max', 'throughput_cm3_per_hour',
                'setup_hours', 'setup_fee', 'machine_rate_per_hour', 'allows_hollow', 'sort_order',
            ]),
            'status' => $model->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }

    /**
     * Data acuan berkas Contoh: parameter teknologi yang dipakai NUSAMA3D,
     * supaya pengelola melihat bentuk isian yang benar-benar berlaku.
     */
    protected function exampleRecords(): array
    {
        return [
            [
                'code' => 'FDM', 'name' => 'Fused Deposition Modeling', 'family' => 'Plastic',
                'description' => 'Filamen termoplastik dilelehkan lalu diekstrusi lapis demi lapis.',
                'sort_order' => 10, 'build_volume_x' => 500, 'build_volume_y' => 500, 'build_volume_z' => 600,
                'shell_ratio' => 0.313, 'default_infill' => 0.2, 'min_wall_thickness_mm' => 1.2, 'support_volume_factor' => 0.18,
                'infill_note' => null, 'throughput_cm3_per_hour' => 16, 'setup_hours' => 0.3,
                'setup_fee' => 25000, 'machine_rate_per_hour' => 12000, 'layer_height_min' => 0.1, 'layer_height_max' => 0.3,
                'is_active' => true,
            ],
            [
                'code' => 'MJF', 'name' => 'Multi Jet Fusion', 'family' => 'Nylon',
                'description' => 'Serbuk nylon dilebur oleh fusing agent dan lampu inframerah. Tanpa support.',
                'sort_order' => 30, 'build_volume_x' => 380, 'build_volume_y' => 284, 'build_volume_z' => 380,
                'shell_ratio' => 0.55, 'default_infill' => 1, 'min_wall_thickness_mm' => 0.8, 'support_volume_factor' => 0,
                'infill_note' => null, 'throughput_cm3_per_hour' => 22, 'setup_hours' => 3.5,
                'setup_fee' => 120000, 'machine_rate_per_hour' => 45000, 'layer_height_min' => 0.08, 'layer_height_max' => 0.08,
                'is_active' => true,
            ],
            [
                'code' => 'SLM', 'name' => 'Selective Laser Melting', 'family' => 'Metal',
                'description' => 'Serbuk logam dilelehkan sepenuhnya oleh laser berdaya tinggi.',
                'sort_order' => 40, 'build_volume_x' => 250, 'build_volume_y' => 250, 'build_volume_z' => 300,
                'shell_ratio' => 0.6, 'default_infill' => 1, 'min_wall_thickness_mm' => 0.5, 'support_volume_factor' => 0.22,
                'infill_note' => null, 'throughput_cm3_per_hour' => 3, 'setup_hours' => 2,
                'setup_fee' => 500000, 'machine_rate_per_hour' => 250000, 'layer_height_min' => 0.02, 'layer_height_max' => 0.06,
                'is_active' => true,
            ],
        ];
    }
}
