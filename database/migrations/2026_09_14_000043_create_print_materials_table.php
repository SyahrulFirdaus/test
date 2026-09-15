<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu tabel material untuk SELURUH teknologi.
 *
 * Sebelumnya hanya FDM dan SLA yang punya tabel sendiri (`fdm_materials`,
 * `sla_materials`), sedangkan material MJF/SLM ditulis di config. Begitu
 * teknologi dapat ditambah Superadmin, keduanya tidak lagi memadai: teknologi
 * baru tidak punya tabel dan tidak boleh menuntut perubahan kode.
 *
 * Kolomnya sengaja sama persis dengan kedua tabel lama ditambah
 * `print_technology_id`, sehingga App\Models\Concerns\HasMaterialPricing —
 * beserta seluruh rumus harga per gram dan pembulatannya — berlaku tanpa
 * perubahan sedikit pun.
 *
 * SELURUH baris lama dipindahkan ke sini apa adanya, termasuk `technical_spec`
 * (densitas, warna, batas ukuran) dan material MJF/SLM yang selama ini hanya
 * ada di config. Tidak ada satu pun yang dihapus: tabel `fdm_materials` dan
 * `sla_materials` ditinggalkan utuh sebagai cadangan, sehingga migrasi ini
 * dapat dibalik tanpa kehilangan data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('print_technology_id')->constrained('print_technologies')->cascadeOnDelete();
            $table->string('material');
            $table->string('brand');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->string('remark')->nullable();
            $table->json('technical_spec')->nullable();
            $table->timestamps();

            // Satu teknologi tidak boleh punya dua material bernama sama:
            // namanya dipakai sebagai kunci pada `quotation_items.material`.
            $table->unique(['print_technology_id', 'material']);
        });

        $technologies = DB::table('print_technologies')->pluck('id', 'code');

        $this->copyTable('fdm_materials', 'FDM', $technologies);
        $this->copyTable('sla_materials', 'SLA', $technologies);
        $this->copyConfigMaterials($technologies);
    }

    public function down(): void
    {
        Schema::dropIfExists('print_materials');
    }

    /**
     * Pindahkan baris dari tabel material lama.
     *
     * @param  \Illuminate\Support\Collection<string, int>  $technologies
     */
    private function copyTable(string $table, string $code, $technologies): void
    {
        if (! Schema::hasTable($table) || ! isset($technologies[$code])) {
            return;
        }

        $rows = DB::table($table)->get()->map(fn ($row) => [
            'print_technology_id' => $technologies[$code],
            'material' => $row->material,
            'brand' => $row->brand,
            'purchase_price' => $row->purchase_price,
            'sale_price' => $row->sale_price,
            'remark' => $row->remark,
            'technical_spec' => $row->technical_spec,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ])->all();

        if ($rows !== []) {
            DB::table('print_materials')->insert($rows);
        }
    }

    /**
     * Material MJF/SLM yang selama ini hanya ada di config.
     *
     * Harga di config berupa `price_per_gram` jadi (harga jual per gram),
     * sedangkan tabel ini menyimpan Harga Beli/Harga Jual lalu menurunkannya.
     * Agar harga yang berlaku TIDAK bergeser, `sale_price` diisi angka yang
     * sama — pembulatannya (kelipatan seratus ke atas) mengembalikan nilai
     * semula karena seluruh harga config memang kelipatan seratus. Harga beli
     * ditaksir dari asumsi berat spool yang sama dengan material lain.
     *
     * @param  \Illuminate\Support\Collection<string, int>  $technologies
     */
    private function copyConfigMaterials($technologies): void
    {
        $rows = [];

        foreach ((array) config('printing.technologies', []) as $code => $technology) {
            // FDM dan SLA sudah dipindahkan dari tabelnya masing-masing.
            if (in_array($code, ['FDM', 'SLA'], true) || ! isset($technologies[$code])) {
                continue;
            }

            foreach ((array) ($technology['materials'] ?? []) as $name => $material) {
                $perGram = (float) ($material['price_per_gram'] ?? 0);

                $rows[] = [
                    'print_technology_id' => $technologies[$code],
                    'material' => $name,
                    'brand' => $technology['family'] ?? $code,
                    // 800 g = asumsi berat bersih spool pada HasMaterialPricing.
                    'purchase_price' => round($perGram * 800, 2),
                    'sale_price' => $perGram,
                    'remark' => null,
                    'technical_spec' => json_encode([
                        'density' => $material['density'] ?? 1.0,
                        'colors' => $material['colors'] ?? [],
                        'description' => $material['description'] ?? null,
                        'characteristics' => $material['characteristics'] ?? [],
                        'pros' => $material['pros'] ?? [],
                        'cons' => $material['cons'] ?? [],
                        'maxSize' => $material['max_size'] ?? null,
                        'minSize' => $material['min_size'] ?? null,
                        'minSizeSlender' => $material['min_size_slender'] ?? null,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            DB::table('print_materials')->insert($rows);
        }
    }
};
