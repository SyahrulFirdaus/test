<?php

namespace Tests\Feature;

use App\Models\BusinessProfile;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Services\CustomerDashboard;
use App\Services\PaymentTermFlow;
use App\Support\CustomerType;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Dua dashboard pelanggan yang berbeda menurut tipe akunnya.
 *
 * Yang diuji bukan sekadar "halaman terbuka", melainkan bahwa isinya memang
 * berlainan: Personal tidak pernah melihat perangkat Business, dan angka-angka
 * pada keduanya benar-benar dihitung dari data yang tersimpan.
 */
class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $personal;

    private User $business;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->personal = User::factory()->create([
            'name' => 'Andi Saputra',
            'customer_type' => CustomerType::PERSONAL,
        ]);

        $this->business = User::factory()->create([
            'name' => 'John Doe',
            'customer_type' => CustomerType::BUSINESS,
        ]);

        BusinessProfile::create([
            'user_id' => $this->business->id,
            'company_name' => 'PT ABC Manufacturing',
            'pic_name' => 'John Doe',
            'position' => 'Engineering Manager',
            'phone' => '0811111111',
            'email' => 'procurement@abc.test',
            'industry' => 'Manufacturing',
            'address' => 'Kawasan Industri Jababeka Blok A1',
            'postal_code' => '17530',
            'segment' => 'Industrial Customer',
        ]);
    }

    private function quotation(array $overrides = [], ?User $owner = null): QuotationRequest
    {
        $owner ??= $this->personal;

        $quotation = QuotationRequest::create(array_merge([
            'user_id' => $owner->id,
            'tracking_number' => QuotationRequest::generateTrackingNumber(),
            'name' => $owner->name,
            'email' => $owner->email,
            'whatsapp' => '081234567890',
            'quantity' => 100,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-08/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'estimated_minutes' => 200,
            'estimated_cost' => 30000000,
            'estimated_price' => 30000000,
            'status' => QuotationStatus::REVIEWING,
        ], $overrides));

        Storage::disk('local')->put('quotations/2026-08/bracket-'.$quotation->id.'.stl', 'solid cube endsolid cube');

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-08/bracket-'.$quotation->id.'.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 100,
            'estimated_minutes' => 200,
            'estimated_cost' => 30000000,
        ]);

        return $quotation->load('items');
    }

    /* ------------------------------------------------ pemilihan dashboard --- */

    public function test_akun_personal_mendapat_dashboard_sederhana(): void
    {
        $this->quotation();

        $response = $this->actingAs($this->personal)->get(route('dashboard'))->assertOk();

        $response->assertViewIs('dashboard.personal')
            ->assertSee('Andi Saputra')
            ->assertSee('Kelola penawaran, pesanan, dan pembayaran Anda.')
            ->assertSee('Penawaran Terbaru')
            ->assertSee('+ Buat Penawaran');
    }

    public function test_akun_business_mendapat_dashboard_perusahaan(): void
    {
        $this->quotation([], $this->business);

        $response = $this->actingAs($this->business)->get(route('dashboard'))->assertOk();

        $response->assertViewIs('dashboard.business')
            ->assertSee('John Doe')
            ->assertSee('PT ABC Manufacturing')
            ->assertSee('Business Account')
            ->assertSee('Payment Overview')
            ->assertSee('Active Production')
            ->assertSee('Company Profile')
            ->assertSee('Business Profile')
            ->assertSee('Documents')
            ->assertSee('Previous Orders');
    }

    public function test_dashboard_personal_tidak_memuat_perangkat_business(): void
    {
        $this->quotation();

        $this->actingAs($this->personal)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Business Account')
            ->assertDontSee('Payment Overview')
            ->assertDontSee('Previous Orders')
            ->assertDontSee('Total Spending')
            ->assertDontSee('Company Profile');
    }

    public function test_sidebar_berbeda_antara_personal_dan_business(): void
    {
        $this->actingAs($this->business)
            ->get(route('dashboard'))
            ->assertSee('Payment Terms')
            ->assertSee('Re-order')
            ->assertSee('Orders');

        $this->actingAs($this->personal)
            ->get(route('dashboard'))
            ->assertSee('Penawaran Saya')
            ->assertDontSee('Re-order');
    }

    /* --------------------------------------------------------- statistik --- */

    public function test_statistik_personal_dihitung_dari_data_sebenarnya(): void
    {
        $this->quotation();
        $this->quotation(['status' => QuotationStatus::PRODUCTION]);
        $this->quotation(['status' => QuotationStatus::AWAITING_PAYMENT]);
        $this->quotation(['status' => QuotationStatus::COMPLETED]);
        $this->quotation(['status' => QuotationStatus::COMPLETED]);

        // Milik akun lain tidak boleh ikut terhitung.
        $this->quotation(['status' => QuotationStatus::COMPLETED], $this->business);

        $stats = app(CustomerDashboard::class)->personalStats($this->personal);

        $this->assertSame(5, $stats['quotations']);
        $this->assertSame(1, $stats['active_orders']);
        $this->assertSame(1, $stats['awaiting_payment']);
        $this->assertSame(2, $stats['completed']);
    }

    public function test_outstanding_dan_spending_business_dari_termin_yang_tercatat(): void
    {
        Notification::fake();

        $quotation = $this->quotation([], $this->business);

        $flow = app(PaymentTermFlow::class);
        $term = $flow->approve($flow->request($quotation, 3, $this->business));

        $first = $term->fresh()->installments[0];
        $flow->submitProof($first, UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'), $this->business);
        $flow->approveProof($first->fresh());

        $stats = app(CustomerDashboard::class)->businessStats($this->business);

        // Rp30 juta dibagi tiga; satu termin lunas.
        $this->assertSame(10000000.0, $stats['spending']);
        $this->assertSame(20000000.0, $stats['outstanding']);
    }

    public function test_dashboard_business_menampilkan_progres_termin(): void
    {
        Notification::fake();

        $quotation = $this->quotation([], $this->business);

        $flow = app(PaymentTermFlow::class);
        $flow->approve($flow->request($quotation, 3, $this->business));

        $this->actingAs($this->business)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($quotation->tracking_number)
            ->assertSee('3x Pembayaran')
            ->assertSee('Termin 1')
            ->assertSee('Termin 3')
            ->assertSee('Rp10.000.000')
            ->assertSee('Bayar Sekarang');
    }

    /* -------------------------------------------------- pengingat bayar --- */

    public function test_kartu_pembayaran_hanya_muncul_bila_ada_tagihan(): void
    {
        $this->quotation(['status' => QuotationStatus::REVIEWING]);

        $this->actingAs($this->personal)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Pembayaran Menunggu');

        $this->quotation([
            'status' => QuotationStatus::AWAITING_PAYMENT,
            'payment_due_at' => now()->addDay(),
        ]);

        $this->actingAs($this->personal)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pembayaran Menunggu')
            ->assertSee('Bayar Sekarang');
    }

    public function test_termin_yang_terlambat_ditandai_overdue_di_dashboard(): void
    {
        Notification::fake();

        $quotation = $this->quotation([], $this->business);

        $flow = app(PaymentTermFlow::class);
        $term = $flow->approve($flow->request($quotation, 3, $this->business));

        $term->fresh()->installments[0]->forceFill(['due_date' => now()->subDays(3)])->save();

        $this->actingAs($this->business)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Payment Overdue')
            ->assertSee('telah melewati batas waktu');
    }

    /* ---------------------------------------------------------- re-order --- */

    public function test_business_dapat_memesan_ulang_pesanan_yang_selesai(): void
    {
        Notification::fake();

        $selesai = $this->quotation(['status' => QuotationStatus::COMPLETED], $this->business);

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.reorder', $selesai))
            ->assertSessionHas('status');

        $baru = QuotationRequest::where('user_id', $this->business->id)
            ->where('id', '!=', $selesai->id)
            ->first();

        $this->assertNotNull($baru);
        $this->assertSame(QuotationStatus::first(), $baru->status);
        $this->assertSame($selesai->technology, $baru->technology);
        $this->assertSame($selesai->quantity, $baru->quantity);
        $this->assertCount(1, $baru->items);

        // Berkasnya disalin, bukan dipakai bersama — menghapus penawaran lama
        // tidak boleh membuat pesanan barunya kehilangan model.
        $this->assertNotSame($selesai->items[0]->file_path, $baru->items[0]->file_path);
        Storage::disk('local')->assertExists($baru->items[0]->file_path);

        $selesai->delete();
        Storage::disk('local')->assertExists($baru->items[0]->file_path);

        // Harga lama tidak ikut terbawa; ditetapkan ulang saat ditinjau.
        $this->assertNull($baru->items[0]->estimated_price);
    }

    public function test_akun_personal_tidak_dapat_memakai_re_order(): void
    {
        $selesai = $this->quotation(['status' => QuotationStatus::COMPLETED]);

        $this->actingAs($this->personal)
            ->post(route('dashboard.quotations.reorder', $selesai))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->assertSame(1, QuotationRequest::where('user_id', $this->personal->id)->count());
    }

    public function test_re_order_penawaran_akun_lain_ditolak(): void
    {
        $milikOrangLain = $this->quotation(['status' => QuotationStatus::COMPLETED]);

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.reorder', $milikOrangLain))
            ->assertNotFound();
    }

    public function test_re_order_ditolak_bila_berkasnya_sudah_hilang(): void
    {
        $selesai = $this->quotation(['status' => QuotationStatus::COMPLETED], $this->business);

        Storage::disk('local')->delete($selesai->items[0]->file_path);

        $this->actingAs($this->business)
            ->post(route('dashboard.quotations.reorder', $selesai))
            ->assertSessionHas('error');

        $this->assertSame(1, QuotationRequest::where('user_id', $this->business->id)->count());
    }

    /* --------------------------------------------------------- dokumen --- */

    public function test_dokumen_hanya_memuat_berkas_yang_benar_benar_ada(): void
    {
        $quotation = $this->quotation([], $this->business);

        $documents = app(CustomerDashboard::class)->documents($this->business);

        // Bukti penawaran selalu dapat dibuat sistem.
        $this->assertCount(1, $documents);
        $this->assertStringContainsString($quotation->tracking_number, $documents->first()['label']);
    }

    /* ------------------------------------------------- data milik sendiri --- */

    public function test_dashboard_tidak_membocorkan_data_akun_lain(): void
    {
        $milikOrangLain = $this->quotation([], $this->business);

        $this->actingAs($this->personal)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($milikOrangLain->tracking_number);
    }

    public function test_admin_tetap_diarahkan_ke_dashboard_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_menghapus_penawaran_ikut_membersihkan_itemnya(): void
    {
        $quotation = $this->quotation();

        $quotation->delete();

        $this->assertSame(0, QuotationItem::where('quotation_request_id', $quotation->id)->count());
    }
}
