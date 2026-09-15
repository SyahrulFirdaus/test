<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teknologi cetak sebagai data, bukan lagi daftar tetap di config.
 *
 * Sebelumnya FDM/SLA/MJF/SLM ditulis di `config/printing.php`, sehingga
 * menambah teknologi berarti menyunting kode. Setelah ini daftarnya dikelola
 * Superadmin lewat Price List, dan seluruh sistem — tab Price List, pilihan
 * Technology pada Edit Specification, validasi, sampai perhitungan — membaca
 * tabel ini.
 *
 * Namanya `print_technologies`, bukan `technologies`, karena tabel bernama itu
 * SUDAH ADA dan dipakai halaman "Technologies" pada website publik (slug,
 * gambar, tagline). Keduanya sengaja dipisah: yang satu isi halaman pemasaran,
 * yang ini parameter produksi.
 *
 * Isi awalnya disalin PERSIS dari config supaya tidak ada satu pun angka yang
 * bergeser saat migrasi dijalankan. Blok `technologies` di config tetap
 * ditinggalkan sebagai nilai bawaan bagi pemasangan baru dan sebagai rujukan
 * bentuk datanya.
 *
 * Disisipkan lewat migrasi, bukan seeder, karena seluruh alur FDM — termasuk
 * pengujian yang memakai `RefreshDatabase` — tidak dapat berjalan tanpa baris
 * ini, sama seperti material FDM/SLA pada migrasinya masing-masing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_technologies', function (Blueprint $table) {
            $table->id();

            // Kode inilah yang tersimpan pada `quotation_items.technology` dan
            // menjadi kunci di seluruh sistem, jadi dijaga unik dan tetap.
            $table->string('code', 12)->unique();
            $table->string('name');
            $table->string('family')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('build_volume_x')->default(200);
            $table->unsignedInteger('build_volume_y')->default(200);
            $table->unsignedInteger('build_volume_z')->default(200);

            $table->decimal('shell_ratio', 4, 3)->default(0.25);
            $table->decimal('default_infill', 4, 3)->default(0.2);
            $table->text('infill_note')->nullable();
            $table->decimal('min_wall_thickness_mm', 6, 2)->default(0.8);

            // Nol berarti teknologinya tidak memerlukan support sama sekali.
            $table->decimal('support_volume_factor', 5, 3)->default(0);

            $table->decimal('layer_height_min', 6, 3)->default(0.05);
            $table->decimal('layer_height_max', 6, 3)->default(0.3);

            $table->decimal('throughput_cm3_per_hour', 10, 2)->default(10);
            $table->decimal('setup_hours', 6, 2)->default(0.5);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->decimal('machine_rate_per_hour', 12, 2)->default(0);

            // Hollow Model hanya masuk akal pada teknologi yang partnya
            // mengeras padat — resin, misalnya.
            $table->boolean('allows_hollow')->default(false);

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $rows = [];
        $order = 0;
        $hollow = (array) config('printing.hollow.technologies', []);

        foreach ((array) config('printing.technologies', []) as $code => $technology) {
            $rows[] = [
                'code' => $code,
                'name' => $technology['name'],
                'family' => $technology['family'] ?? null,
                'description' => $technology['description'] ?? null,

                'build_volume_x' => (int) ($technology['build_volume']['x'] ?? 200),
                'build_volume_y' => (int) ($technology['build_volume']['y'] ?? 200),
                'build_volume_z' => (int) ($technology['build_volume']['z'] ?? 200),

                'shell_ratio' => $technology['shell_ratio'] ?? 0.25,
                'default_infill' => $technology['default_infill'] ?? 0.2,
                'infill_note' => $technology['infill_note'] ?? null,
                'min_wall_thickness_mm' => $technology['min_wall_thickness_mm'] ?? 0.8,
                'support_volume_factor' => $technology['support_volume_factor'] ?? 0,

                'layer_height_min' => $technology['layer_height_range']['min'] ?? 0.05,
                'layer_height_max' => $technology['layer_height_range']['max'] ?? 0.3,

                'throughput_cm3_per_hour' => $technology['throughput_cm3_per_hour'] ?? 10,
                'setup_hours' => $technology['setup_hours'] ?? 0.5,
                'setup_fee' => $technology['setup_fee'] ?? 0,
                'machine_rate_per_hour' => $technology['machine_rate_per_hour'] ?? 0,

                'allows_hollow' => in_array($code, $hollow, true),

                'sort_order' => $order += 10,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('print_technologies')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('print_technologies');
    }
};
