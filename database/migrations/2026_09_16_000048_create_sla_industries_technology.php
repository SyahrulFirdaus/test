<?php

use App\Support\SlaIndustries;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Teknologi SLA Industries beserta tabnya sendiri pada Price List.
 *
 * Disisipkan lewat migrasi, bukan dibuat manual Superadmin, karena teknologi
 * ini bukan sekadar satu baris data: alur harganya berbeda dari teknologi lain
 * (lihat App\Support\SlaIndustries) dan kode `SLAI` dirujuk langsung oleh
 * kodenya. Kalau barisnya boleh tidak ada, seluruh cabang SLA Industries harus
 * dijaga terhadap teknologi yang hilang — termasuk pada pengujian yang memakai
 * `RefreshDatabase`.
 *
 * `sort_order` 25 menempatkannya persis di antara SLA (20) dan MJF (30),
 * sehingga tab Price List maupun pilihan Technology pada Edit Specification
 * terbaca FDM | SLA | SLA Industries | MJF | SLM.
 *
 * SENGAJA TIDAK membuat baris `pricing_formulas`: rumus di tab "Harga" adalah
 * pola HPP/Risk/Packaging/Profit milik teknologi yang dicetak sendiri, dan
 * SLA Industries justru tidak memakainya. Parameternya tinggal di tabel
 * `sla_industries_formulas`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('print_technologies')->where('code', SlaIndustries::CODE)->exists()) {
            return;
        }

        DB::table('print_technologies')->insert([
            'code' => SlaIndustries::CODE,
            'name' => SlaIndustries::NAME,
            'family' => 'Industrial Resin',
            'description' => 'Part resin industri yang dikerjakan mitra produksi kami. '
                .'Harga ditetapkan tim setelah kuotasi vendor diterima, bukan dihitung otomatis dari berat model.',

            // Parameter geometri tetap diisi karena viewer memakainya untuk
            // memeriksa apakah model muat di area cetak. Angka throughput dan
            // tarif mesin dibiarkan nol: tidak ada mesin sendiri yang dihitung,
            // dan harganya memang tidak pernah melewati rumus per jam mesin.
            'build_volume_x' => 600,
            'build_volume_y' => 600,
            'build_volume_z' => 400,

            'shell_ratio' => 0.25,
            'default_infill' => 0.2,
            'infill_note' => null,
            'min_wall_thickness_mm' => 0.8,
            'support_volume_factor' => 0.12,

            'layer_height_min' => 0.025,
            'layer_height_max' => 0.1,

            'throughput_cm3_per_hour' => 0,
            'setup_hours' => 0,
            'setup_fee' => 0,
            'machine_rate_per_hour' => 0,

            'allows_hollow' => true,

            'sort_order' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Materialnya ikut terhapus lewat cascade pada `print_materials`.
        DB::table('print_technologies')->where('code', SlaIndustries::CODE)->delete();
    }
};
