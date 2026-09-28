<?php

namespace Tests\Feature;

use App\Models\MachineCost;
use App\Models\PrintMaterial;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PriceList\Excel\MachineCostDataset;
use App\Support\ActivityAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import & Export Excel menu Machine Cost — alur yang sama dengan Import Material.
 */
class MachineCostExcelTest extends TestCase
{
    use RefreshDatabase;

    private ?User $superAdmin = null;

    private function superAdmin(): User
    {
        return $this->superAdmin ??= User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function headings(): array
    {
        return app(MachineCostDataset::class)->headings();
    }

    private function line(array $values, int $no = 1): array
    {
        return array_map(fn (string $heading) => $heading === 'No' ? $no : ($values[$heading] ?? null), $this->headings());
    }

    private function machine(array $overrides = []): array
    {
        return array_merge([
            'Mesin' => 'Mesin Uji', 'Teknologi' => 'FDM', 'Watt (KWH)' => 0.35,
            'Harga Listrik' => 1700, 'Depresiasi' => 5300,
        ], $overrides);
    }

    /** @param  array<int, array<int, mixed>>  $rows */
    private function xlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $line => $cells) {
            foreach ($cells as $column => $value) {
                $sheet->setCellValue(chr(65 + $column).($line + 1), $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'Machine_Cost.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function sheetOf(string $contents): array
    {
        $path = tempnam(sys_get_temp_dir(), 'dl').'.xlsx';
        file_put_contents($path, $contents);

        return IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
    }

    private function preview(UploadedFile $file)
    {
        return $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.machine-cost.excel.preview'), ['file' => $file]);
    }

    private function import(string $onDuplicate = 'skip')
    {
        return $this->post(route('superadmin.price-list.machine-cost.excel.import'), ['on_duplicate' => $onDuplicate]);
    }

    /* ============================================================ unduh === */

    public function test_halaman_machine_cost_menampilkan_tombol_excel(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.machine-cost.index'))
            ->assertOk()
            ->assertSee('Import Excel')
            ->assertSee(route('superadmin.price-list.machine-cost.excel.export'))
            ->assertSee(route('superadmin.price-list.machine-cost.excel.template'))
            ->assertSee(route('superadmin.price-list.machine-cost.excel.example'));
    }

    public function test_export_mengambil_data_dari_basis_data_beserta_hitungannya(): void
    {
        $machine = MachineCost::create([
            'mesin' => 'Bambu Uji', 'print_technology_id' => PrintTechnology::where('code', 'FDM')->value('id'),
            'printer_key' => 'bambu_x1c', 'watt_kwh' => 0.35, 'harga_listrik' => 1700, 'depresiasi' => 5300, 'weight_kg' => 12.95,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.machine-cost.excel.export'))
            ->assertOk();

        $this->assertStringContainsString(
            'filename="Machine_Cost_'.now()->format('Y-m-d').'.xlsx"',
            $response->headers->get('content-disposition'),
        );

        $rows = $this->sheetOf($response->getContent());
        $this->assertSame($this->headings(), $rows[0]);

        $line = array_combine($rows[0], collect($rows)->firstWhere(1, 'Bambu Uji'));

        $this->assertSame('FDM', $line['Teknologi']);
        $this->assertSame('Bambu Lab X1 Carbon', $line['Printer pada Calculator']);
        $this->assertSame($machine->machine_cost, (int) $line['Machine Cost']);
        $this->assertSame($machine->rounded_machine_cost, (int) $line['Pembulatan']);
        $this->assertSame(12.95, (float) $line['Berat (kg)']);

        $this->assertDatabaseHas('activity_logs', ['action' => ActivityAction::PRICE_LIST_EXPORT]);
    }

    public function test_template_kosong_dan_contoh_terisi(): void
    {
        $template = $this->actingAs($this->superAdmin())->get(route('superadmin.price-list.machine-cost.excel.template'))->assertOk();
        $this->assertStringContainsString('filename="Template_Machine_Cost.xlsx"', $template->headers->get('content-disposition'));
        $this->assertSame($this->headings(), $this->sheetOf($template->getContent())[0]);

        $example = $this->actingAs($this->superAdmin())->get(route('superadmin.price-list.machine-cost.excel.example'))->assertOk();
        $this->assertStringContainsString('filename="Contoh_Machine_Cost.xlsx"', $example->headers->get('content-disposition'));
        $this->assertSame('Elegoo Neptune Max 4', $this->sheetOf($example->getContent())[1][1]);
    }

    /* ==================================================== pratinjau/simpan === */

    public function test_pratinjau_tidak_menyimpan_lalu_import_menambah(): void
    {
        $this->preview($this->xlsx([$this->headings(), $this->line($this->machine(['Printer pada Calculator' => 'Prusa MK4']))]))
            ->assertOk()
            ->assertSee('Mesin Uji');

        $this->assertDatabaseMissing('machine_costs', ['mesin' => 'Mesin Uji']);

        $this->import()
            ->assertRedirect(route('superadmin.price-list.machine-cost.index'))
            ->assertSessionHas('status', 'Import selesai: 1 mesin ditambahkan, 0 diperbarui, 0 dilewati.');

        $machine = MachineCost::where('mesin', 'Mesin Uji')->sole();
        $this->assertSame('FDM', $machine->technology->code);
        $this->assertSame('prusa_mk4', $machine->printer_key);
        $this->assertSame(0.35, (float) $machine->watt_kwh);

        $this->assertDatabaseHas('activity_logs', ['action' => ActivityAction::PRICE_LIST_IMPORT]);
    }

    public function test_baris_bermasalah_ditandai_per_baris(): void
    {
        MachineCost::create(['mesin' => 'Pemegang Ender', 'printer_key' => 'ender3', 'watt_kwh' => 0.25, 'harga_listrik' => 1700, 'depresiasi' => 2100]);

        $this->preview($this->xlsx([
            $this->headings(),
            $this->line($this->machine(['Mesin' => 'A', 'Teknologi' => 'XYZ'])),
            $this->line($this->machine(['Mesin' => 'B', 'Watt (KWH)' => 'dua'])),
            $this->line($this->machine(['Mesin' => 'C', 'Printer pada Calculator' => 'Creality Ender 3'])),
            $this->line($this->machine(['Mesin' => 'D', 'Depresiasi' => -5])),
            $this->line($this->machine(['Mesin' => null])),
            $this->line($this->machine(['Mesin' => 'E', 'Watt (KWH)' => 1000])),
        ]))
            ->assertOk()
            ->assertSee('Teknologi &quot;XYZ&quot; tidak ditemukan', false)
            ->assertSee('Watt (KWH) tidak valid: &quot;dua&quot; bukan angka.', false)
            ->assertSee('sudah dipetakan ke mesin &quot;Pemegang Ender&quot;', false)
            ->assertSee('Depresiasi tidak boleh negatif.')
            ->assertSee('Mesin tidak boleh kosong.')
            ->assertSee('Watt (KWH) terlalu besar');
    }

    public function test_kolom_hitungan_hanya_dicocokkan(): void
    {
        $this->preview($this->xlsx([$this->headings(), $this->line($this->machine(['Machine Cost' => 1]))]))
            ->assertSee('Machine Cost di file (Rp1) berbeda dari hasil rumus');

        $this->import();
        $this->assertSame(8843, MachineCost::where('mesin', 'Mesin Uji')->first()->machine_cost);
    }

    public function test_mesin_kembar_dilewati_atau_diperbarui_tanpa_menyentuh_material(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->first();
        $machine = MachineCost::create([
            'mesin' => 'Mesin Uji', 'print_technology_id' => $fdm->id, 'watt_kwh' => 0.25,
            'harga_listrik' => 1700, 'depresiasi' => 2100, 'weight_kg' => 7.5,
        ]);
        $material = $fdm->materials()->create([
            'material' => 'PLA Uji', 'brand' => 'ESUN', 'purchase_price' => 185000, 'sale_price' => 463, 'machine_cost_id' => $machine->id,
        ]);

        $row = $this->line($this->machine(['Mesin' => 'mesin uji', 'Teknologi' => null, 'Depresiasi' => 9000]));

        $this->preview($this->xlsx([$this->headings(), $row]))->assertSee('Sudah Ada');
        $this->import('skip');
        $this->assertSame(2100.0, (float) $machine->fresh()->depresiasi);

        $this->preview($this->xlsx([$this->headings(), $row]));
        $this->import('update')->assertSessionHas('status', 'Import selesai: 0 mesin ditambahkan, 1 diperbarui, 0 dilewati.');

        $machine->refresh();
        $this->assertSame(9000.0, (float) $machine->depresiasi);
        // Sel kosong mempertahankan nilai yang ada.
        $this->assertSame($fdm->id, $machine->print_technology_id);
        $this->assertSame(7.5, (float) $machine->weight_kg);
        $this->assertSame(1, MachineCost::count());
        // Relasi material ↔ mesin tidak disentuh.
        $this->assertSame($machine->id, PrintMaterial::find($material->id)->machine_cost_id);
    }

    public function test_berkas_hasil_export_dapat_diimpor_kembali(): void
    {
        MachineCost::create(['mesin' => 'Ender Uji', 'print_technology_id' => PrintTechnology::where('code', 'FDM')->value('id'), 'printer_key' => 'ender3', 'watt_kwh' => 0.25, 'harga_listrik' => 1700, 'depresiasi' => 2100]);

        $export = $this->actingAs($this->superAdmin())->get(route('superadmin.price-list.machine-cost.excel.export'));
        $path = tempnam(sys_get_temp_dir(), 'rt').'.xlsx';
        file_put_contents($path, $export->getContent());

        $this->preview(new UploadedFile($path, 'Machine_Cost.xlsx', null, null, true))
            ->assertOk()
            ->assertDontSee('baris tidak dapat diimpor')
            ->assertDontSee('berbeda dari hasil rumus');

        $this->import('update')->assertSessionHas('status', 'Import selesai: 0 mesin ditambahkan, 1 diperbarui, 0 dilewati.');
        $this->assertSame('ender3', MachineCost::where('mesin', 'Ender Uji')->value('printer_key'));
    }
}
