<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pemetaan eksplisit printer Calculator → baris Machine Cost.
 *
 * Sebelumnya Machine Cost dicari dengan mencocokkan kata pada nama mesin
 * ("Creality Ender 3" ≈ "Ender 3 V2"). Cara itu rapuh: mengganti nama mesin di
 * Price List, atau memilih printer yang namanya tidak mirip mesin mana pun,
 * diam-diam menjatuhkan harga ke Machine Cost Rumus Harga Otomatis — pada data
 * saat ini Rp61.000/jam alih-alih Rp4.000/jam, sehingga model yang sama bisa
 * berharga empat kali lipat hanya karena printer yang tersimpan berbeda.
 *
 * Kolom `printer_key` membuat pemetaannya tersimpan di basis data. Isinya
 * diturunkan dari hasil pencocokan nama yang berlaku SEKARANG, jadi tidak ada
 * harga yang berubah karena migrasi ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_costs', function (Blueprint $table) {
            $table->string('printer_key', 40)->nullable()->after('print_technology_id')->unique();
        });

        $machines = DB::table('machine_costs')->orderBy('mesin')->get(['id', 'mesin']);

        foreach ((array) config('printing.printers.options', []) as $key => $printer) {
            $best = null;
            $bestScore = 0;

            foreach ($machines as $machine) {
                $score = count(array_intersect(self::tokens($printer['name'] ?? ''), self::tokens($machine->mesin)));

                // Aturan lama: minimal dua kata sama, skor tertinggi menang,
                // seri dimenangkan urutan nama mesin.
                if ($score >= 2 && $score > $bestScore) {
                    $best = $machine;
                    $bestScore = $score;
                }
            }

            if ($best !== null && ! DB::table('machine_costs')->where('printer_key', $key)->exists()
                && DB::table('machine_costs')->where('id', $best->id)->whereNull('printer_key')->exists()) {
                DB::table('machine_costs')->where('id', $best->id)->update(['printer_key' => $key]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('machine_costs', function (Blueprint $table) {
            $table->dropUnique(['printer_key']);
            $table->dropColumn('printer_key');
        });
    }

    /** @return array<int, string> */
    private static function tokens(string $name): array
    {
        return array_values(array_unique(array_filter(preg_split('/[^a-z0-9]+/', strtolower($name)) ?: [])));
    }
};
