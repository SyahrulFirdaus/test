<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Warna material yang tersedia, dikelola dari menu Color.
 *
 * Sebelumnya daftarnya ditulis di `printing.material_colors.options`, sehingga
 * menambah satu warna menuntut perubahan kode. Isinya dipindahkan apa adanya ke
 * tabel ini — kunci, label, dan hex yang sama persis — supaya penawaran lama
 * yang menyimpan `material_color` tetap menemukan warnanya.
 *
 * `key` adalah yang tersimpan pada `quotation_items.material_color` dan yang
 * disebut material lewat `technical_spec.colors`, jadi nilainya tidak pernah
 * ikut berubah saat namanya diganti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_colors', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            // "#RRGGBB" — tujuh karakter, selalu diawali tanda pagar.
            $table->string('hex', 7);
            // Urutan tampil pada Edit Specification dan menu Color.
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $rows = [];
        $position = 0;

        foreach ((array) config('printing.material_colors.options', []) as $key => $color) {
            $rows[] = [
                'key' => $key,
                'label' => $color['label'] ?? $key,
                'hex' => strtoupper($color['hex'] ?? '#000000'),
                'position' => ++$position,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('print_colors')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('print_colors');
    }
};
