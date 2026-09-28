<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PrintTechnology;
use App\Models\User;
use App\Services\PriceList\Excel\TechnologyDataset;
use App\Support\ActivityAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import & Export Excel menu Teknologi — alur yang sama dengan Import Material.
 */
class TechnologyExcelTest extends TestCase
{
    use RefreshDatabase;

    private ?User $superAdmin = null;

    private function superAdmin(): User
    {
        return $this->superAdmin ??= User::factory()->superAdmin()->create(['email' => 'superadmin@nusama3d.com']);
    }

    private function headings(): array
    {
        return app(TechnologyDataset::class)->headings();
    }

    /** Satu baris berkas dari [judul => nilai]; judul yang tidak disebut dikosongkan. */
    private function line(array $values, int $no = 1): array
    {
        return array_map(fn (string $heading) => $heading === 'No' ? $no : ($values[$heading] ?? null), $this->headings());
    }

    private function dlp(array $overrides = []): array
    {
        return array_merge([
            'Kode' => 'DLP', 'Nama Lengkap' => 'Digital Light Processing', 'Keluarga Bahan' => 'Resin',
            'Lebar X (mm)' => 190, 'Kedalaman Y (mm)' => 120, 'Tinggi Z (mm)' => 245,
            'Shell Ratio' => 1, 'Infill Bawaan' => 1, 'Tebal Dinding Minimum (mm)' => 0.6,
            'Faktor Volume Support' => 0.14, 'Laju Cetak (cm³/jam)' => 12,
            'Waktu Persiapan (jam)' => 0.5, 'Biaya Persiapan' => 40000, 'Tarif Mesin (Rp/jam)' => 28000,
            'Tebal Lapisan Minimum (mm)' => 0.02, 'Tebal Lapisan Maksimum (mm)' => 0.1,
        ], $overrides);
    }

    /** @param  array<int, array<int, mixed>>  $rows */
    private function xlsx(array $rows, string $name = 'Teknologi.xlsx'): UploadedFile
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

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
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
            ->post(route('superadmin.price-list.technologies.excel.preview'), ['file' => $file]);
    }

    /* ============================================================ unduh === */

    /** Kolom Hollow tidak ada lagi, baik di tabel Teknologi maupun di berkas Excel. */
    public function test_kolom_hollow_tidak_ada_di_tabel_maupun_excel(): void
    {
        $this->assertNotContains('Hollow', $this->headings());

        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technologies.index'))
            ->assertOk()
            ->assertDontSee('>Hollow<', false);

        $export = $this->actingAs($this->superAdmin())->get(route('superadmin.price-list.technologies.excel.export'));
        $this->assertNotContains('Hollow', $this->sheetOf($export->getContent())[0]);
    }

    /** Berkas lama yang masih memuat kolom Hollow tetap terbaca; kolomnya diabaikan. */
    public function test_berkas_lama_berkolom_hollow_diabaikan(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->first();
        $fdm->update(['allows_hollow' => true]);

        $this->preview($this->xlsx([
            [...$this->headings(), 'Hollow'],
            [...$this->line($this->dlp(['Kode' => 'FDM', 'Nama Lengkap' => 'FDM Baru'])), 'Tidak'],
        ]))->assertOk()->assertDontSee('baris tidak dapat diimpor');

        $this->post(route('superadmin.price-list.technologies.excel.import'), ['on_duplicate' => 'update']);

        $this->assertSame('FDM Baru', $fdm->fresh()->name);
        $this->assertTrue($fdm->fresh()->allows_hollow);
    }

    public function test_halaman_teknologi_menampilkan_tombol_excel(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technologies.index'))
            ->assertOk()
            ->assertSee('Import Excel')
            ->assertSee(route('superadmin.price-list.technologies.excel.export'))
            ->assertSee(route('superadmin.price-list.technologies.excel.template'))
            ->assertSee(route('superadmin.price-list.technologies.excel.example'));
    }

    public function test_export_mengambil_data_dari_basis_data(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('superadmin.price-list.technologies.excel.export'))
            ->assertOk();

        $this->assertStringContainsString(
            'filename="Teknologi_'.now()->format('Y-m-d').'.xlsx"',
            $response->headers->get('content-disposition'),
        );

        $rows = $this->sheetOf($response->getContent());
        $this->assertSame($this->headings(), $rows[0]);

        $fdm = PrintTechnology::where('code', 'FDM')->first();
        $line = collect($rows)->firstWhere(1, 'FDM');

        $this->assertSame($fdm->name, $line[2]);
        $this->assertSame((float) $fdm->machine_rate_per_hour, (float) $line[array_search('Tarif Mesin (Rp/jam)', $rows[0])]);
        $this->assertCount(PrintTechnology::managed()->count() + 1, array_filter($rows, fn ($row) => $row[1] !== null));

        $this->assertDatabaseHas('activity_logs', ['action' => ActivityAction::PRICE_LIST_EXPORT]);
    }

    public function test_template_kosong_dan_contoh_terisi(): void
    {
        $template = $this->actingAs($this->superAdmin())->get(route('superadmin.price-list.technologies.excel.template'))->assertOk();
        $this->assertStringContainsString('filename="Template_Teknologi.xlsx"', $template->headers->get('content-disposition'));
        $this->assertSame($this->headings(), $this->sheetOf($template->getContent())[0]);
        $this->assertNull($this->sheetOf($template->getContent())[1][1] ?? null);

        $example = $this->actingAs($this->superAdmin())->get(route('superadmin.price-list.technologies.excel.example'))->assertOk();
        $this->assertStringContainsString('filename="Contoh_Teknologi.xlsx"', $example->headers->get('content-disposition'));
        $this->assertSame('FDM', $this->sheetOf($example->getContent())[1][1]);
    }

    /* ======================================================== pratinjau === */

    public function test_pratinjau_tidak_menyimpan_apa_pun(): void
    {
        $this->preview($this->xlsx([$this->headings(), $this->line($this->dlp())]))
            ->assertOk()
            ->assertSee('Preview Data')
            ->assertSee('Digital Light Processing');

        $this->assertDatabaseMissing('print_technologies', ['code' => 'DLP']);
    }

    public function test_header_salah_ditolak(): void
    {
        $this->preview($this->xlsx([['Kode', 'Nama Lengkap'], ['DLP', 'Digital Light Processing']]))
            ->assertRedirect(route('superadmin.price-list.technologies.index'))
            ->assertSessionHasErrors('file');
    }

    public function test_baris_bermasalah_ditandai_per_baris(): void
    {
        $response = $this->preview($this->xlsx([
            $this->headings(),
            $this->line($this->dlp(['Kode' => 'DL P'])),
            $this->line($this->dlp(['Kode' => 'CLIP', 'Shell Ratio' => 'banyak']), 2),
            $this->line($this->dlp(['Kode' => 'LCD', 'Tebal Lapisan Maksimum (mm)' => 0.01]), 3),
            $this->line($this->dlp(['Kode' => 'PJ', 'Nama Lengkap' => null]), 4),
        ]))->assertOk();

        $response->assertSee('Kode hanya boleh huruf dan angka, tanpa spasi.')
            ->assertSee('Shell Ratio tidak valid: &quot;banyak&quot; bukan angka.', false)
            ->assertSee('Tebal Lapisan Maksimum tidak boleh lebih kecil daripada minimumnya.')
            ->assertSee('Nama Lengkap tidak boleh kosong.');
    }

    /* ========================================================== simpan === */

    public function test_import_menambah_teknologi_baru(): void
    {
        $this->preview($this->xlsx([$this->headings(), $this->line($this->dlp())]));

        $this->post(route('superadmin.price-list.technologies.excel.import'), ['on_duplicate' => 'skip'])
            ->assertRedirect(route('superadmin.price-list.technologies.index'))
            ->assertSessionHas('status', 'Import selesai: 1 teknologi ditambahkan, 0 diperbarui, 0 dilewati.');

        $dlp = PrintTechnology::where('code', 'DLP')->sole();
        // Hollow tidak ada di Excel: teknologi baru memakai bawaan kolomnya.
        $this->assertFalse($dlp->allows_hollow);
        $this->assertTrue($dlp->is_active);
        $this->assertSame(1.0, (float) $dlp->shell_ratio);
        $this->assertContains('DLP', PrintTechnology::codes());

        $this->assertDatabaseHas('activity_logs', ['action' => ActivityAction::PRICE_LIST_IMPORT]);
    }

    public function test_kode_kembar_dilewati_atau_diperbarui(): void
    {
        $fdm = PrintTechnology::where('code', 'FDM')->first();
        $row = $this->line($this->dlp(['Kode' => 'fdm', 'Nama Lengkap' => 'FDM Baru', 'Tarif Mesin (Rp/jam)' => 99000]));

        // Skip: tidak berubah, tidak berganda.
        $this->preview($this->xlsx([$this->headings(), $row]))->assertSee('Sudah Ada');
        $this->post(route('superadmin.price-list.technologies.excel.import'), ['on_duplicate' => 'skip']);

        $this->assertSame($fdm->name, $fdm->fresh()->name);
        $this->assertSame(1, PrintTechnology::where('code', 'FDM')->count());

        // Update: parameternya mengikuti file, kodenya tetap.
        $this->preview($this->xlsx([$this->headings(), $row]));
        $this->post(route('superadmin.price-list.technologies.excel.import'), ['on_duplicate' => 'update'])
            ->assertSessionHas('status', 'Import selesai: 0 teknologi ditambahkan, 1 diperbarui, 0 dilewati.');

        $this->assertSame('FDM Baru', $fdm->fresh()->name);
        $this->assertSame(99000.0, (float) $fdm->fresh()->machine_rate_per_hour);
        $this->assertSame(1, PrintTechnology::where('code', 'FDM')->count());
    }

    public function test_kode_ganda_di_dalam_file_ditolak(): void
    {
        $this->preview($this->xlsx([$this->headings(), $this->line($this->dlp()), $this->line($this->dlp(), 2)]))
            ->assertSee('Kode &quot;DLP&quot; ditulis dua kali di file ini', false);
    }

    public function test_seluruh_teknologi_nonaktif_membatalkan_import(): void
    {
        $rows = [$this->headings()];

        foreach (PrintTechnology::managed()->get() as $index => $technology) {
            $rows[] = $this->line($this->dlp(['Kode' => $technology->code, 'Status' => 'Nonaktif']), $index + 1);
        }

        $this->preview($this->xlsx($rows));
        $this->post(route('superadmin.price-list.technologies.excel.import'), ['on_duplicate' => 'update'])
            ->assertSessionHasErrors('file');

        // Transaksi dibatalkan seluruhnya.
        $this->assertTrue(PrintTechnology::where('code', 'FDM')->first()->is_active);
    }

    public function test_import_tanpa_pratinjau_ditolak(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.price-list.technologies.excel.import'), ['on_duplicate' => 'skip'])
            ->assertSessionHasErrors('file');
    }

    public function test_berkas_hasil_export_dapat_diimpor_kembali(): void
    {
        $export = $this->actingAs($this->superAdmin())->get(route('superadmin.price-list.technologies.excel.export'));
        $path = tempnam(sys_get_temp_dir(), 'rt').'.xlsx';
        file_put_contents($path, $export->getContent());

        $this->preview(new UploadedFile($path, 'Teknologi.xlsx', null, null, true))->assertOk()->assertDontSee('baris tidak dapat diimpor');

        $before = PrintTechnology::managed()->get()->map->only(['code', 'name', 'setup_fee', 'shell_ratio', 'is_active'])->all();
        $this->post(route('superadmin.price-list.technologies.excel.import'), ['on_duplicate' => 'update']);

        $this->assertEquals($before, PrintTechnology::managed()->get()->map->only(['code', 'name', 'setup_fee', 'shell_ratio', 'is_active'])->all());
    }

    public function test_admin_biasa_tidak_dapat_mengakses(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('superadmin.price-list.technologies.excel.export'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
