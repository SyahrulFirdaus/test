<?php

use App\Support\InfillPattern;
use App\Support\MaterialColor;
use App\Support\Printer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan pilihan simulasi ala Cura yang kini tersedia di halaman Cek Barang.
     *
     * Printer berlaku untuk keseluruhan penawaran — satu build plate, satu
     * mesin — jadi kolomnya berada di `quotation_requests`. Skala, infill,
     * hollow, dan warna material diatur per model sehingga ikut ke
     * `quotation_items`.
     *
     * Rincian biaya disimpan sebagai JSON di kedua tabel: per model apa adanya,
     * dan pada penawaran sebagai penjumlahan seluruh modelnya, supaya dokumen
     * lama tetap dapat menampilkan angka yang sama seperti saat dikirim
     * meskipun tarif di config kelak berubah.
     */
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->string('printer', 40)->nullable()->after('material');
            $table->string('printer_name')->nullable()->after('printer');
            $table->json('build_volume')->nullable()->after('printer_name')
                ->comment('Area cetak mesin yang dipilih: x lebar, y kedalaman, z tinggi');
            $table->json('cost_breakdown')->nullable()->after('estimated_cost')
                ->comment('Material, waktu printing, support, finishing, quality control');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->decimal('scale_percent', 8, 2)->default(100)->after('quantity');
            $table->decimal('infill_density', 5, 4)->nullable()->after('layer_height_mm');
            $table->string('infill_pattern', 20)->nullable()->after('infill_density');

            $table->boolean('hollow_enabled')->default(false)->after('support_type');
            $table->decimal('hollow_wall_thickness_mm', 6, 2)->nullable()->after('hollow_enabled');
            $table->decimal('hollow_drain_diameter_mm', 6, 2)->nullable()->after('hollow_wall_thickness_mm');
            $table->string('hollow_drain_position', 20)->nullable()->after('hollow_drain_diameter_mm');

            $table->string('material_color', 20)->nullable()->after('hollow_drain_position');
            $table->boolean('fits_build_volume')->default(true)->after('material_color')
                ->comment('False bila model melewati area cetak mesin yang dipilih');

            $table->json('cost_breakdown')->nullable()->after('estimated_cost');
        });

        // Permintaan lama dibuat sebelum pilihan mesin ada. Nilainya diisi
        // dengan default yang berlaku sekarang supaya tampilan admin, halaman
        // tracking, dan PDF tidak menampilkan kolom kosong.
        $printer = Printer::default();
        $volume = Printer::buildVolume($printer);

        DB::table('quotation_requests')->whereNull('printer')->update([
            'printer' => $printer,
            'printer_name' => Printer::name($printer),
            'build_volume' => json_encode($volume),
        ]);

        DB::table('quotation_items')->whereNull('infill_pattern')->update([
            'infill_pattern' => InfillPattern::default(),
            'material_color' => MaterialColor::default(),
        ]);

        // Kepadatan infill lama mengikuti bawaan teknologinya masing-masing.
        foreach (config('printing.technologies', []) as $code => $technology) {
            DB::table('quotation_items')
                ->whereNull('infill_density')
                ->where('technology', $code)
                ->update(['infill_density' => $technology['default_infill']]);
        }
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn([
                'scale_percent',
                'infill_density',
                'infill_pattern',
                'hollow_enabled',
                'hollow_wall_thickness_mm',
                'hollow_drain_diameter_mm',
                'hollow_drain_position',
                'material_color',
                'fits_build_volume',
                'cost_breakdown',
            ]);
        });

        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn(['printer', 'printer_name', 'build_volume', 'cost_breakdown']);
        });
    }
};
