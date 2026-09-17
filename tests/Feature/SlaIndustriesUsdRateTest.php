<?php

namespace Tests\Feature;

use App\Models\ExchangeRate;
use App\Models\QuotationRequest;
use App\Models\SlaIndustriesQuote;
use App\Models\User;
use App\Services\UsdRate;
use App\Support\QuotationStatus;
use App\Support\SlaIndustries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kurs USD/IDR otomatis pada Form Perhitungan SLA Industries.
 *
 * Yang dijaga di sini bukan "kursnya berhasil diambil", melainkan hal-hal yang
 * merusak harga bila salah:
 *
 *  - kurs TIDAK boleh berasal dari kiriman formulir, sebab yang mengirim dapat
 *    menentukan harganya sendiri;
 *  - Final Price TIDAK boleh pernah dihitung dengan kurs nol;
 *  - penawaran yang harganya sudah ditetapkan harus tetap menyimpan kurs yang
 *    benar-benar dipakai, bukan kurs hari ini.
 *
 * Penyedia kursnya selalu dipalsukan: pengujian yang menghubungi internet akan
 * berubah hasilnya tiap hari dan gagal saat jaringan mati.
 */
class SlaIndustriesUsdRateTest extends TestCase
{
    use RefreshDatabase;

    private const KURS = 17690;

    /**
     * Jawaban penyedia kurs yang sedang berlaku.
     *
     * Disimpan sebagai properti, BUKAN dengan memanggil Http::fake() berulang:
     * panggilan Http::fake() berikutnya menumpuk stub, bukan menggantinya,
     * sehingga stub pertamalah yang selamanya menang. Dengan satu stub yang
     * membaca properti ini, pengujian dapat benar-benar mengubah keadaan
     * penyedia di tengah jalan.
     *
     * @var array<string, mixed>|null  null berarti penyedianya sedang mati
     */
    private ?array $jawabanKurs = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        Http::fake(fn () => $this->jawabanKurs === null
            ? Http::response('', 503)
            : Http::response($this->jawabanKurs));
    }

    private function fakeRate(float $rate = self::KURS, string $published = '2026-09-16T00:02:31+00:00'): void
    {
        $this->jawabanKurs = [
            'rates' => ['IDR' => $rate],
            'time_last_update_unix' => strtotime($published),
        ];
    }

    private function failRate(): void
    {
        $this->jawabanKurs = null;
    }

    private ?User $admin = null;

    /** Satu akun admin saja per pengujian: emailnya unik di basis data. */
    private function admin(): User
    {
        return $this->admin ??= User::factory()->admin()->create(['email' => 'admin@nusama3d.com']);
    }

    /* =========================================== 1. pengambilan kurs === */

    public function test_kurs_diambil_dari_penyedia_beserta_sumber_dan_waktunya(): void
    {
        $this->fakeRate();

        $rate = app(UsdRate::class)->current();

        $this->assertSame((float) self::KURS, $rate['rate']);
        $this->assertSame('ExchangeRate-API', $rate['source']);
        $this->assertFalse($rate['stale']);
        $this->assertNull($rate['error']);
        $this->assertNotNull($rate['published_at']);
    }

    /** Kurs yang sudah diambil disimpan, supaya ada cadangan saat penyedia mati. */
    public function test_kurs_yang_berhasil_diambil_ikut_disimpan(): void
    {
        $this->fakeRate();
        app(UsdRate::class)->current();

        $this->assertDatabaseHas('exchange_rates', ['pair' => UsdRate::PAIR]);
        $this->assertEqualsWithDelta(self::KURS, (float) ExchangeRate::first()->rate, 0.01);
    }

    /**
     * Jawaban kedua harus datang dari simpanan, bukan dari penyedia.
     *
     * Inilah yang membuat angka di layar admin dan angka yang dipakai server
     * saat menyimpan tidak mungkin berbeda: keduanya membaca simpanan yang sama.
     */
    public function test_permintaan_berikutnya_dilayani_dari_simpanan(): void
    {
        $this->fakeRate();

        app(UsdRate::class)->current();
        app(UsdRate::class)->current();
        app(UsdRate::class)->current();

        Http::assertSentCount(1);
    }

    public function test_coba_lagi_memaksa_menghubungi_penyedia_ulang(): void
    {
        $this->fakeRate();

        app(UsdRate::class)->current();
        app(UsdRate::class)->current(force: true);

        Http::assertSentCount(2);
    }

    /* ================================================ 2. saat gagal === */

    public function test_penyedia_gagal_memakai_kurs_terakhir_dan_menandainya_basi(): void
    {
        $this->fakeRate();
        app(UsdRate::class)->current();

        // Kursnya sudah tersimpan; kini penyedianya mati dan simpanan sementara
        // dibuang, persis seperti keadaan setelah masa simpannya habis.
        Cache::flush();
        $this->failRate();

        $rate = app(UsdRate::class)->current();

        $this->assertSame((float) self::KURS, $rate['rate']);
        $this->assertTrue($rate['stale']);
        $this->assertNotNull($rate['error']);
    }

    /** Belum pernah ada kurs sama sekali: null, BUKAN nol. */
    public function test_tanpa_kurs_sama_sekali_nilainya_null_bukan_nol(): void
    {
        $this->failRate();

        $rate = app(UsdRate::class)->current();

        $this->assertNull($rate['rate']);
        $this->assertNotSame(0.0, $rate['rate']);
        $this->assertTrue($rate['stale']);
    }

    /** Angka ngawur dari penyedia ditolak, bukan dipakai jadi harga. */
    public function test_kurs_di_luar_batas_wajar_ditolak(): void
    {
        $this->fakeRate(3.5);

        $this->assertNull(app(UsdRate::class)->current()['rate']);
    }

    /* ================================================= 3. endpoint === */

    public function test_endpoint_kurs_mengembalikan_keadaan_kurs(): void
    {
        $this->fakeRate();

        $this->actingAs($this->admin())
            ->getJson(route('admin.exchange-rate.usd'))
            ->assertOk()
            ->assertJsonPath('rate', self::KURS)
            ->assertJsonPath('source', 'ExchangeRate-API')
            ->assertJsonPath('stale', false);
    }

    public function test_endpoint_kurs_tertutup_bagi_yang_belum_masuk(): void
    {
        $this->fakeRate();

        $this->getJson(route('admin.exchange-rate.usd'))->assertUnauthorized();
    }

    /* ======================================= 4. pemakaian pada harga === */

    private function penawaran(): QuotationRequest
    {
        $technology = \App\Models\PrintTechnology::where('code', SlaIndustries::CODE)->firstOrFail();
        $technology->materials()->firstOrCreate(
            ['material' => 'Industrial Resin'],
            ['brand' => 'JLC', 'purchase_price' => 0, 'sale_price' => 0],
        );

        $quotation = QuotationRequest::create([
            'tracking_number' => 'QT-KURS01',
            'name' => 'Rani Puspita',
            'email' => 'rani@contoh.test',
            'whatsapp' => '081200000000',
            'quantity' => 1,
            'file_name' => 'impeller.stl',
            'file_path' => 'quotations/2026-09/impeller.stl',
            'file_format' => 'STL',
            'file_size' => 4096,
            'model_stats' => ['dimensions' => ['x' => 120, 'y' => 120, 'z' => 80]],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => SlaIndustries::CODE,
            'material' => 'Industrial Resin',
            'estimated_minutes' => 0,
            'estimated_cost' => null,
            'estimated_price' => null,
            'status' => QuotationStatus::REVIEWING,
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'impeller.stl',
            'file_path' => 'quotations/2026-09/impeller.stl',
            'file_format' => 'STL',
            'file_size' => 4096,
            'model_stats' => ['dimensions' => ['x' => 120, 'y' => 120, 'z' => 80]],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => SlaIndustries::CODE,
            'material' => 'Industrial Resin',
            'printer' => 'ender3',
            'printer_name' => 'Creality Ender 3',
            'quantity' => 1,
            'scale_percent' => 100,
            'model_volume_cm3' => 90,
            'estimated_weight_g' => 0,
            'support_weight_g' => 0,
            'estimated_minutes' => 0,
            'estimated_cost' => null,
            'cost_breakdown' => ['manual_pricing' => true, 'selling_price' => null, 'technology' => SlaIndustries::CODE],
        ]);

        return $quotation->fresh();
    }

    /** @return array<string, mixed> */
    private function parameter(float $margin = 50): array
    {
        return [
            'jlc_price_usd' => 115.76,
            'jlc_shipping_usd' => 2.70,
            'customs_idr' => 828000,
            'margin_percent' => $margin,
            'product_name' => 'Impeller',
        ];
    }

    /**
     * Kurs yang dikirim formulir TIDAK boleh dipakai.
     *
     * Kalau dipakai, siapa pun yang dapat mengirim formulir ini dapat
     * menentukan harga penawaran sesukanya hanya dengan mengubah satu angka.
     */
    public function test_kurs_kiriman_formulir_diabaikan(): void
    {
        $this->fakeRate();

        $quotation = $this->penawaran();
        $item = $quotation->items->first();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.items.sla-industries', [$quotation, $item]), [
                ...$this->parameter(),
                // Kurs palsu yang, bila dipercaya, akan melipatgandakan harganya.
                'usd_rate' => 999999,
            ])
            ->assertSessionHasNoErrors();

        $quote = $item->fresh()->slaIndustriesQuote;

        $this->assertEqualsWithDelta(self::KURS, (float) $quote->usd_rate, 0.01);
        $this->assertSame(4385337.0, $quote->final_price);
    }

    /** Tanpa kurs, harga tidak boleh ditetapkan sama sekali. */
    public function test_tanpa_kurs_harga_tidak_dapat_ditetapkan(): void
    {
        $this->failRate();

        $quotation = $this->penawaran();
        $item = $quotation->items->first();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.items.sla-industries', [$quotation, $item]), $this->parameter())
            ->assertSessionHasErrors('usd_rate');

        $this->assertNull($item->fresh()->slaIndustriesQuote);
        $this->assertNull($item->fresh()->estimated_cost);
        $this->assertTrue($quotation->fresh()->awaitsPricing());
    }

    /** Kurs berubah, seluruh rantai harganya ikut berubah. */
    public function test_kurs_berubah_menghitung_ulang_sampai_final_price(): void
    {
        $this->fakeRate(17750);

        $quotation = $this->penawaran();
        $item = $quotation->items->first();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.items.sla-industries', [$quotation, $item]), $this->parameter())
            ->assertSessionHasNoErrors();

        $quote = $item->fresh()->slaIndustriesQuote;
        $harapan = SlaIndustries::compute([
            'usd_rate' => 17750,
            'jlc_price_usd' => 115.76,
            'jlc_shipping_usd' => 2.70,
            'customs_idr' => 828000,
            'margin_percent' => 50,
        ]);

        $this->assertSame($harapan['jlc_price_idr'], $quote->jlc_price_idr);
        $this->assertSame($harapan['hpp'], $quote->hpp);
        $this->assertSame($harapan['final_price'], $quote->final_price);

        // Harga yang berbeda dari kurs 17.690 — kalau sama, kursnya tidak
        // benar-benar berpengaruh dan pengujian ini tidak membuktikan apa pun.
        $this->assertNotSame(4385337.0, $quote->final_price);
    }

    /* ================================== 5. kurs penawaran lama beku === */

    /**
     * Membuka penawaran lama tidak boleh menarik kurs terbaru.
     *
     * Harga yang sudah ditawarkan ke pelanggan dihitung dengan kurs tertentu;
     * itulah yang harus tetap terbaca, berapa pun kurs hari ini.
     */
    public function test_penawaran_lama_mempertahankan_kursnya(): void
    {
        $this->fakeRate(17690);

        $quotation = $this->penawaran();
        $item = $quotation->items->first();

        $this->actingAs($this->admin())
            ->patch(route('admin.quotations.items.sla-industries', [$quotation, $item]), $this->parameter());

        $quote = $item->fresh()->slaIndustriesQuote;
        $hargaAwal = $quote->final_price;

        // Kurs bergerak jauh, dan simpanan sementara dibuang.
        Cache::flush();
        $this->fakeRate(20000);

        $halaman = $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation->fresh()))
            ->assertOk();

        $segar = SlaIndustriesQuote::find($quote->id);

        // Yang tersimpan tidak bergeser sedikit pun.
        $this->assertEqualsWithDelta(17690, (float) $segar->usd_rate, 0.01);
        $this->assertSame($hargaAwal, $segar->final_price);

        // Dan halaman menyebutkan kurs yang benar-benar dipakai harga itu.
        $halaman->assertSee('Rp17.690');
        $halaman->assertSee('Harga yang berlaku sekarang dihitung dengan kurs');
    }

    /* ============================================== 6. tampilannya === */

    public function test_form_menampilkan_kurs_sumber_dan_penandanya(): void
    {
        $this->fakeRate();

        $quotation = $this->penawaran();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk();

        $response->assertSee('Rp17.690');
        $response->assertSee('Sumber:');
        $response->assertSee('ExchangeRate-API');
        $response->assertSee('Terakhir diperbarui:');

        // Kurs tidak dapat diketik: yang ada hanya isian tersembunyi.
        $response->assertSee('<input type="hidden" name="usd_rate"', false);
        $response->assertDontSee('data-sla-input="usd_rate" aria-label', false);
    }

    /** Saat kursnya tidak ada, formulir menolak menghitung — bukan menulis Rp0. */
    public function test_form_menahan_perhitungan_saat_kurs_tidak_ada(): void
    {
        $this->failRate();

        $quotation = $this->penawaran();

        $this->actingAs($this->admin())
            ->get(route('admin.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Tidak dapat mengambil kurs terbaru.')
            ->assertSee('Coba Lagi');
    }
}
