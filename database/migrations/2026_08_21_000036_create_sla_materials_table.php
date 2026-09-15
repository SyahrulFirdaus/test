<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master data harga material SLA. Struktur identik dengan `fdm_materials`
 * — lihat migrasinya untuk penjelasan tiap kolom dan kenapa baris awalnya
 * disisipkan langsung di sini alih-alih lewat Seeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_materials', function (Blueprint $table) {
            $table->id();
            $table->string('material');
            $table->string('brand');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->string('remark')->nullable();
            $table->json('technical_spec')->nullable();
            $table->timestamps();
        });

        $standardColors = ['abu', 'putih', 'hitam'];
        $spec = fn (float $density, array $colors, string $description) => $this->spec($density, $colors, $description);

        $rows = [
            ['material' => 'Standard Resin Plus Sunlu', 'brand' => 'Sunlu', 'purchase_price' => 275000, 'sale_price' => 1031, 'remark' => 'Standard Material', 'technical_spec' => $spec(1.10, $standardColors, 'Resin serbaguna, pilihan dasar untuk model presentasi.')],
            ['material' => 'Standard Resin High Clear Sunlu', 'brand' => 'Sunlu', 'purchase_price' => 450000, 'sale_price' => 1688, 'remark' => 'Standard Material', 'technical_spec' => $spec(1.10, ['bening'], 'Resin bening untuk part tembus pandang seperti lensa dan housing.')],
            ['material' => 'Standard Resin Nylon like Resin Sunlu', 'brand' => 'Sunlu', 'purchase_price' => 550000, 'sale_price' => 2063, 'remark' => 'Engineering Material', 'technical_spec' => $spec(1.12, $standardColors, 'Resin menyerupai nylon, lebih liat dan tahan benturan.')],
            ['material' => 'Standard Resin ABS like resin Sunlu', 'brand' => 'Sunlu', 'purchase_price' => 450000, 'sale_price' => 1688, 'remark' => 'Engineering Material', 'technical_spec' => $spec(1.12, $standardColors, 'Resin menyerupai ABS, tahan benturan untuk part fungsional.')],
            ['material' => 'Standard Resin High Temp Sunlu', 'brand' => 'Sunlu', 'purchase_price' => 550000, 'sale_price' => 2063, 'remark' => 'Engineering Material', 'technical_spec' => $spec(1.13, $standardColors, 'Resin tahan suhu tinggi untuk aplikasi yang terkena panas.')],
            ['material' => 'Standard Resin High Toughness Sunlu', 'brand' => 'Sunlu', 'purchase_price' => 450000, 'sale_price' => 1688, 'remark' => 'Engineering Material', 'technical_spec' => $spec(1.12, $standardColors, 'Resin dengan ketangguhan tinggi, tahan retak dan benturan.')],
        ];

        foreach ($rows as &$row) {
            $row['technical_spec'] = json_encode($row['technical_spec']);
            $row['created_at'] = now();
            $row['updated_at'] = now();
        }

        DB::table('sla_materials')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_materials');
    }

    /** @return array<string, mixed> */
    private function spec(float $density, array $colors, string $description): array
    {
        return [
            'density' => $density,
            'maxSize' => ['x' => 300, 'y' => 200, 'z' => 300],
            'minSize' => ['x' => 5, 'y' => 5, 'z' => 5],
            'minSizeSlender' => ['x' => 10, 'y' => 2, 'z' => 2],
            'colors' => $colors,
            'description' => $description,
            'characteristics' => [],
            'pros' => [],
            'cons' => [],
        ];
    }
};
