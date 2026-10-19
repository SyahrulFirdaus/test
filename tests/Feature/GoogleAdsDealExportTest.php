<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use App\Services\GoogleAdsDealExport;
use App\Support\ActivityAction;
use App\Support\AdminPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Export CSV Deal untuk impor konversi Google Ads. */
class GoogleAdsDealExportTest extends TestCase
{
    use RefreshDatabase;

    private function deal(array $attributes = []): Lead
    {
        return Lead::create(array_merge([
            'source' => 'form',
            'name' => 'Pelanggan Uji',
            'email' => 'pelanggan@contoh.test',
            'whatsapp' => '6281234567890',
            'gclid' => 'CjwKCAjwTESTgclid_123-abc',
            'status' => 'deal',
            'deal_value' => 5000000,
            // 07:30 UTC = 14:30 WIB
            'deal_at' => Carbon::parse('2026-10-19 07:30:00', 'UTC'),
        ], $attributes));
    }

    private function admin(array $permissions = [AdminPermission::LEAD_VIEW, AdminPermission::LEAD_EXPORT]): User
    {
        return User::factory()->withPermissions($permissions)->create();
    }

    private function export(User $user, array $query = [])
    {
        return $this->actingAs($user)->get(route('admin.leads.export-deals', array_merge([
            'export_from' => '2026-10-01',
            'export_to' => '2026-10-31',
        ], $query)));
    }

    /** @return array<int, array<int, string>> */
    private function parse(string $csv): array
    {
        return array_map(fn ($line) => str_getcsv($line, ',', '"', ''), array_values(array_filter(explode("\n", $csv))));
    }

    public function test_csv_berisi_kolom_dan_format_yang_ditentukan(): void
    {
        $lead = $this->deal();

        $response = $this->export($this->admin())->assertOk();

        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="Google_Ads_Deal_', $response->headers->get('Content-Disposition'));

        $csv = $response->getContent();
        $this->assertStringStartsNotWith("\xEF\xBB\xBF", $csv, 'Tanpa BOM');

        $rows = $this->parse($csv);
        $this->assertSame(['Email', 'Phone Number', 'Google Click ID', 'Conversion Name', 'Conversion Time', 'Conversion Value', 'Conversion Currency', 'Order ID'], $rows[0]);
        $this->assertSame([
            'pelanggan@contoh.test',
            '+6281234567890',
            'CjwKCAjwTESTgclid_123-abc',
            'Deal Closing',
            '2026-10-19 14:30:00+07:00',
            '5000000',
            'IDR',
            'NSM-LEAD-'.$lead->id,
        ], $rows[1]);
        $this->assertCount(2, $rows);
    }

    public function test_hanya_deal_dalam_rentang_waktu_deal_asia_jakarta(): void
    {
        $this->deal(['name' => 'Dalam']);
        // 2026-10-31 23:30 WIB (masih 31 Okt di Jakarta, sudah 31 Okt 16:30 UTC).
        $this->deal(['name' => 'Akhir Hari', 'email' => 'akhir@contoh.test', 'deal_at' => Carbon::parse('2026-10-31 16:30:00', 'UTC')]);
        // 2026-11-01 00:30 WIB = 2026-10-31 17:30 UTC → di luar rentang Oktober WIB.
        $this->deal(['name' => 'Lewat', 'email' => 'lewat@contoh.test', 'deal_at' => Carbon::parse('2026-10-31 17:30:00', 'UTC')]);
        $this->deal(['name' => 'Bukan Deal', 'email' => 'batal@contoh.test', 'status' => 'batal']);
        $this->deal(['name' => 'Quotation', 'email' => 'q@contoh.test', 'status' => 'quotation']);

        $rows = $this->parse($this->export($this->admin())->getContent());

        $this->assertSame(['pelanggan@contoh.test', 'akhir@contoh.test'], array_column(array_slice($rows, 1), 0));
    }

    public function test_normalisasi_nomor_dan_validasi_tanpa_data_palsu(): void
    {
        $this->assertSame('+6281234567890', GoogleAdsDealExport::phone('081234567890'));
        $this->assertSame('+6281234567890', GoogleAdsDealExport::phone('+62 812-3456-7890'));
        $this->assertSame('+6281234567890', GoogleAdsDealExport::phone('6281234567890'));
        $this->assertSame('+6281234567890', GoogleAdsDealExport::phone('81234567890'));
        $this->assertNull(GoogleAdsDealExport::phone('12345'));
        $this->assertNull(GoogleAdsDealExport::phone('+1 415 555 0100'));
        $this->assertNull(GoogleAdsDealExport::email('bukan-email'));
        $this->assertSame('a@b.co', GoogleAdsDealExport::email(' A@B.CO '));
        $this->assertNull(GoogleAdsDealExport::gclid('ada spasi, koma'));

        // Email tidak valid & tanpa GCLID: tetap diekspor lewat HP, email dikosongkan.
        $this->deal(['email' => 'salah', 'gclid' => null, 'whatsapp' => '0812 3456 7890']);

        $rows = $this->parse($this->export($this->admin())->getContent());
        $this->assertSame(['', '+6281234567890', ''], array_slice($rows[1], 0, 3));
    }

    public function test_data_wajib_kosong_dilewati_dengan_alasan_dan_tanpa_file_kosong(): void
    {
        $this->deal(['name' => 'Tanpa Nilai', 'deal_value' => null]);
        $this->deal(['name' => 'Tanpa Identitas', 'email' => null, 'whatsapp' => null, 'gclid' => null]);
        $this->deal(['name' => 'Tanpa Waktu', 'deal_at' => null]);

        $admin = $this->admin();
        $response = $this->export($admin);

        $response->assertRedirect();
        $this->assertStringNotContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $response->assertSessionHas('export_error');

        // Pratinjau menjelaskan deal yang dilewati.
        $this->actingAs($admin)->get(route('admin.leads.index', ['export_preview' => 1, 'export_from' => '2026-10-01', 'export_to' => '2026-10-31']))
            ->assertSee('Nilai deal belum diisi.')
            ->assertSee('Tidak ada GCLID, email, maupun nomor HP yang valid')
            // Tanpa deal_at tidak masuk rentang mana pun: yang dilewati hanya 2.
            ->assertSeeInOrder(['Dilewati (data tidak lengkap)', '2 deal'])
            ->assertSeeInOrder(['Siap diekspor', '0 deal']);
    }

    public function test_rentang_kosong_tidak_mengunduh_file(): void
    {
        $this->export($this->admin(), ['export_from' => '2025-01-01', 'export_to' => '2025-01-31'])
            ->assertRedirect()
            ->assertSessionHas('export_error', 'Tidak ada transaksi Deal pada rentang tanggal ini. Tidak ada file yang diunduh.');
    }

    public function test_escape_csv_tidak_menggeser_kolom(): void
    {
        config(['leads.google_ads_export.conversion_name' => 'Deal, "Closing"']);
        $this->deal();

        $rows = $this->parse($this->export($this->admin())->getContent());

        $this->assertCount(8, $rows[1]);
        $this->assertSame('Deal, "Closing"', $rows[1][3]);
    }

    public function test_ekspor_ulang_konsisten_dan_tercatat_tanpa_mengubah_lead(): void
    {
        $lead = $this->deal();
        $before = $lead->fresh()->only(['status', 'deal_value', 'deal_at', 'updated_at']);
        $admin = $this->admin();

        $first = $this->export($admin)->getContent();
        $second = $this->export($admin)->getContent();

        $this->assertSame($first, $second);
        $this->assertEquals($before, $lead->fresh()->only(['status', 'deal_value', 'deal_at', 'updated_at']));
        $this->assertNotNull($lead->fresh()->ads_exported_at);
        $this->assertSame(2, $lead->fresh()->ads_export_count);

        // "Hanya yang belum pernah diekspor" → tidak ada lagi.
        $this->export($admin, ['only_new' => 1])->assertRedirect()->assertSessionHas('export_error');

        $log = ActivityLog::where('action', ActivityAction::LEAD_EXPORT)->latest('id')->first();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringContainsString('2026-10-01 s.d. 2026-10-31', $log->new_values['rentang']);
    }

    public function test_status_menjadi_deal_mencatat_waktu_konversi(): void
    {
        $this->travelTo(Carbon::parse('2026-10-19 07:30:00', 'UTC'));
        $lead = Lead::create(['source' => 'form', 'name' => 'X', 'status' => 'quotation']);
        $admin = User::factory()->withPermissions([AdminPermission::LEAD_VIEW, AdminPermission::LEAD_EDIT])->create();

        $this->actingAs($admin)->patch(route('admin.leads.update', $lead), ['status' => 'deal', 'deal_value' => '1.000.000']);

        $this->assertSame('2026-10-19 14:30:00', $lead->fresh()->deal_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));

        // Mengubah nilai saja tidak menggeser waktu konversi.
        $this->travel(2)->days();
        $this->actingAs($admin)->patch(route('admin.leads.update', $lead), ['status' => 'deal', 'deal_value' => '2.000.000']);
        $this->assertSame('2026-10-19 14:30:00', $lead->fresh()->deal_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
    }

    public function test_hak_akses_dan_validasi_backend(): void
    {
        $this->deal();

        $this->export($this->admin([AdminPermission::LEAD_VIEW]))->assertForbidden();
        $this->export($this->admin([AdminPermission::QUOTATION_VIEW]))->assertForbidden();

        auth()->logout();
        $this->get(route('admin.leads.export-deals', ['export_from' => '2026-10-01', 'export_to' => '2026-10-31']))->assertRedirect(route('admin.login'));

        $this->export($this->admin(), ['export_from' => '2026-10-31', 'export_to' => '2026-10-01'])->assertSessionHasErrors('export_to');
        $this->export($this->admin(), ['export_from' => 'kemarin'])->assertSessionHasErrors('export_from');

        // Tombol hanya tampil bagi pemegang hak ekspor.
        $this->actingAs($this->admin([AdminPermission::LEAD_VIEW]))->get(route('admin.leads.index'))->assertDontSee('Export CSV Deal');
        $this->actingAs($this->admin())->get(route('admin.leads.index'))->assertSee('Export CSV Deal');
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('superadmin.leads.export-deals', ['export_from' => '2026-10-01', 'export_to' => '2026-10-31']))->assertOk();
    }
}
