<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Daftar warna MILIK tiap material — satu material, banyak warna.
 *
 * Sebelumnya warna adalah satu daftar bersama (`print_colors`) yang dipakai
 * seluruh material, dan material hanya menyebut kunci mana yang boleh dipakai
 * lewat `technical_spec.colors`. Akibatnya Superadmin tidak dapat memberi PLA
 * Plus dan PETG daftar warna yang benar-benar berbeda tanpa menyentuh kode.
 *
 * Kolom `color_name`/`color_hex` sekali warna yang ditambahkan sehari sebelumnya
 * digantikan tabel ini dan ikut dipindahkan ke sini sebelum dihapus.
 *
 * `key` adalah yang tersimpan pada `quotation_items.material_color`, jadi nilai
 * lama HARUS tetap ditemukan. Karena itu pemindahannya menyalin kunci, nama, dan
 * hex apa adanya dari palet: material yang hari ini menawarkan kedelapan warna
 * tetap menawarkan kedelapan warna itu, dengan kunci yang sama persis, dan
 * penawaran lama tidak kehilangan warnanya sama sekali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_material_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_material_id')->constrained('print_materials')->cascadeOnDelete();
            // Tersimpan pada penawaran; tidak pernah ikut berubah saat namanya diganti.
            $table->string('key');
            $table->string('name');
            // "#RRGGBB" — tujuh karakter, selalu diawali tanda pagar.
            $table->string('hex', 7);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // Satu material tidak boleh punya dua warna berkunci sama.
            $table->unique(['print_material_id', 'key']);
        });

        $this->backfill();

        Schema::table('print_materials', function (Blueprint $table) {
            $table->dropColumn(['color_name', 'color_hex']);
        });
    }

    public function down(): void
    {
        Schema::table('print_materials', function (Blueprint $table) {
            $table->string('color_name')->nullable()->after('material');
            $table->string('color_hex', 7)->nullable()->after('color_name');
        });

        Schema::dropIfExists('print_material_colors');
    }

    /**
     * Beri tiap material daftar warna yang hari ini benar-benar ditawarkannya.
     *
     * Aturannya sama persis dengan App\Support\MaterialColor::forMaterial()
     * yang berlaku sebelum tabel ini ada: `technical_spec.colors` yang berisi
     * membatasi pilihannya, dan yang kosong berarti seluruh palet.
     */
    private function backfill(): void
    {
        $palette = DB::table('print_colors')->orderBy('position')->orderBy('label')->get()
            ->mapWithKeys(fn ($color) => [$color->key => ['name' => $color->label, 'hex' => strtoupper($color->hex)]])
            ->all();

        if ($palette === []) {
            $palette = collect((array) config('printing.material_colors.options', []))
                ->mapWithKeys(fn ($color, $key) => [$key => [
                    'name' => $color['label'] ?? $key,
                    'hex' => strtoupper($color['hex'] ?? '#000000'),
                ]])
                ->all();
        }

        $rows = [];

        foreach (DB::table('print_materials')->get() as $material) {
            $spec = json_decode((string) $material->technical_spec, true);
            $allowed = array_values(array_filter(
                (array) ($spec['colors'] ?? []),
                fn ($key) => isset($palette[$key]),
            ));

            $colors = $allowed !== []
                ? collect($allowed)->mapWithKeys(fn (string $key) => [$key => $palette[$key]])->all()
                : $palette;

            // Warna tunggal yang sempat diisi lewat kolom lama ikut terbawa,
            // kecuali namanya memang sudah ada di daftar di atas.
            if (filled($material->color_name ?? null) && filled($material->color_hex ?? null)) {
                $key = Str::slug($material->color_name) ?: 'warna';

                if (! isset($colors[$key])) {
                    $colors[$key] = ['name' => $material->color_name, 'hex' => strtoupper($material->color_hex)];
                }
            }

            $position = 0;

            foreach ($colors as $key => $color) {
                $rows[] = [
                    'print_material_id' => $material->id,
                    'key' => $key,
                    'name' => $color['name'],
                    'hex' => $color['hex'],
                    'position' => ++$position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('print_material_colors')->insert($chunk);
        }
    }
};
