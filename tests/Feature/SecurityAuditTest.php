<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PricingFormula;
use App\Models\QuotationItem;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Rules\ModelFile;
use App\Services\ActivityLogger;
use App\Services\SellingPriceEstimator;
use App\Support\ActivityAction;
use App\Support\AdminPermission as P;
use App\Support\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Security & Permission Audit — skenario minimal yang wajib lolos.
 *
 * Setiap tes memanggil endpoint secara langsung (seperti lewat Postman), bukan
 * lewat tombol, sehingga yang diuji adalah penjagaan backend — bukan sekadar
 * menu atau tombol yang disembunyikan.
 */
class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function quotation(?User $owner = null, string $status = QuotationStatus::REVIEWING, array $attributes = []): QuotationRequest
    {
        static $sequence = 0;
        $sequence++;

        $quotation = QuotationRequest::create([
            'user_id' => $owner?->id,
            'tracking_number' => 'QT-SEC'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
            'name' => $owner?->name ?? 'Tamu', 'email' => $owner?->email ?? 'tamu@contoh.test', 'whatsapp' => '081200000000',
            'quantity' => 1, 'file_name' => 'part.stl', 'file_path' => 'quotations/part.stl',
            'file_format' => 'STL', 'file_size' => 4096,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM', 'material' => 'PLA Plus', 'estimated_minutes' => 60,
            'estimated_cost' => 150000,
            'status' => $status,
            ...$attributes,
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'part.stl', 'file_path' => 'quotations/part.stl', 'file_format' => 'STL', 'file_size' => 4096,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'technology' => 'FDM', 'material' => 'PLA Plus Standart ESUN', 'quantity' => 1,
            'estimated_minutes' => 60, 'estimated_cost' => 150000,
        ]);

        return $quotation;
    }

    private function cubeStl(float $size = 10.0): string
    {
        $c = [[0, 0, 0], [$size, 0, 0], [$size, $size, 0], [0, $size, 0], [0, 0, $size], [$size, 0, $size], [$size, $size, $size], [0, $size, $size]];
        $faces = [[0, 1, 2], [0, 2, 3], [4, 6, 5], [4, 7, 6], [0, 5, 1], [0, 4, 5], [1, 6, 2], [1, 5, 6], [2, 7, 3], [2, 6, 7], [3, 4, 0], [3, 7, 4]];

        $stl = "solid cube\n";
        foreach ($faces as $face) {
            $stl .= "facet normal 0 0 0\nouter loop\n";
            foreach ($face as $i) {
                $stl .= 'vertex '.implode(' ', $c[$i])."\n";
            }
            $stl .= "endloop\nendfacet\n";
        }

        return $stl."endsolid cube\n";
    }

    /** Berkas unggahan sungguhan (MIME dibaca dari isinya, bukan dari nama). */
    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'sec');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function itemPayload(UploadedFile $file, array $overrides = []): array
    {
        return array_merge([
            'model' => $file,
            'quantity' => 1,
            'technology' => 'FDM',
            'material' => 'PLA Plus Standart ESUN',
            'model_volume_cm3' => 1,
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'model_stats' => json_encode(['dimensions' => ['x' => 10, 'y' => 10, 'z' => 10]]),
        ], $overrides);
    }

    /* ---------------------------------------------- TEST 1 & 2: role isolasi --- */

    public function test_1_user_tidak_dapat_membuka_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/admin/penawaran/penawaran')->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/admin/pembayaran/verifikasi')->assertRedirect(route('dashboard'));
    }

    public function test_2_user_tidak_dapat_membuka_superadmin(): void
    {
        $user = User::factory()->create();

        foreach (['/superadmin', '/superadmin/price-list', '/superadmin/akun/user', '/superadmin/akun/activity-log', '/superadmin/akun/admin'] as $url) {
            $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));
        }
    }

    public function test_2b_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/superadmin/price-list')->assertRedirect(route('admin.login'));
        $this->get('/dashboard/penawaran')->assertRedirect(route('login'));
        $this->getJson('/admin/penawaran/notifikasi/terbaru')->assertUnauthorized();
    }

    /* -------------------------------------------- TEST 3: modul tanpa izin --- */

    public function test_3_admin_tidak_dapat_membuka_menu_superadmin_maupun_modul_tanpa_izin(): void
    {
        // Admin dengan SELURUH hak akses tetap bukan Superadmin.
        $admin = User::factory()->admin()->create();

        foreach (['/superadmin/price-list', '/superadmin/akun/admin', '/superadmin/akun/activity-log'] as $url) {
            $this->actingAs($admin)->get($url)->assertRedirect(route('admin.dashboard'));
        }

        $this->actingAs($admin)
            ->patch(route('superadmin.price-list.harga.update'), ['profit_percent' => 0])
            ->assertRedirect(route('admin.dashboard'));

        // Admin tanpa hak modul: 403 lewat URL langsung.
        $limited = User::factory()->withPermissions([P::PROFILE_EDIT])->create();

        $this->actingAs($limited)->get('/admin/penawaran/penawaran')->assertForbidden();
        $this->actingAs($limited)->get('/admin/pembayaran/verifikasi')->assertForbidden();
        $this->actingAs($limited)->get('/admin/akun/user')->assertForbidden();
        $this->actingAs($limited)->getJson('/admin/penawaran/notifikasi/terbaru')->assertForbidden();
    }

    public function test_3b_dashboard_admin_tidak_memuat_daftar_penawaran_tanpa_izin(): void
    {
        $this->quotation(User::factory()->create(['name' => 'Pelanggan Rahasia']));
        $limited = User::factory()->withPermissions([P::PROFILE_EDIT])->create();

        $response = $this->actingAs($limited)->get('/admin')->assertOk();

        $response->assertDontSee('Pelanggan Rahasia');
        $this->assertCount(0, $response->viewData('recent'));
    }

    /* --------------------------------------- TEST 4: verifikasi pembayaran --- */

    public function test_4_admin_tanpa_payment_verify_tidak_dapat_memverifikasi(): void
    {
        $quotation = $this->quotation(User::factory()->create(), QuotationStatus::PAYMENT_REVIEW);
        $admin = User::factory()->withPermissions([P::PAYMENT_VIEW])->create();

        $this->actingAs($admin)->post(route('admin.quotations.payment.accept', $quotation))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.quotations.payment.reject', $quotation), ['reason' => 'x'])->assertForbidden();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }

    public function test_4b_dropdown_status_tidak_dapat_dipakai_melewati_payment_verify(): void
    {
        $quotation = $this->quotation(User::factory()->create(), QuotationStatus::PAYMENT_REVIEW);
        $admin = User::factory()->withPermissions([P::QUOTATION_VIEW, P::QUOTATION_UPDATE_STATUS])->create();

        $this->actingAs($admin)
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::PAYMENT_RECEIVED])
            ->assertForbidden();

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);

        // Dengan hak Verifikasi Pembayaran, perpindahan yang sama diterima.
        $verifier = User::factory()->withPermissions([P::QUOTATION_VIEW, P::QUOTATION_UPDATE_STATUS, P::PAYMENT_VERIFY])->create();

        $this->actingAs($verifier)
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::PAYMENT_RECEIVED])
            ->assertSessionHasNoErrors();

        $this->assertSame(QuotationStatus::PAYMENT_RECEIVED, $quotation->fresh()->status);
    }

    /* ------------------------------------------------ TEST 5: admin nonaktif --- */

    public function test_5_admin_nonaktif_tidak_dapat_masuk_dari_halaman_mana_pun(): void
    {
        User::factory()->admin()->create(['email' => 'nonaktif@nusama3d.com', 'is_active' => false]);

        foreach ([route('admin.login.store'), route('login.store')] as $url) {
            $this->post($url, ['email' => 'nonaktif@nusama3d.com', 'password' => 'password'])
                ->assertSessionHasErrors('email');

            $this->assertGuest();
        }
    }

    public function test_5b_sesi_admin_yang_dinonaktifkan_langsung_berakhir(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();

        $admin->forceFill(['is_active' => false])->save();

        $this->actingAs($admin->fresh())->get('/admin/penawaran/penawaran')->assertRedirect(route('admin.login'));
        $this->assertGuest();

        $this->actingAs($admin->fresh())->getJson('/admin/penawaran/notifikasi/terbaru')->assertForbidden();
    }

    /* ----------------------------------------------------- TEST 6: IDOR --- */

    public function test_6_user_a_tidak_dapat_membuka_data_user_b(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $quotation = $this->quotation($b, QuotationStatus::REVIEWING);
        $item = $quotation->items()->first();

        $this->actingAs($a)->get(route('dashboard.quotations.show', $quotation))->assertNotFound();
        $this->actingAs($a)->get(route('dashboard.quotations.edit', $quotation))->assertNotFound();
        $this->actingAs($a)->get(route('dashboard.quotations.payment', $quotation))->assertNotFound();
        $this->actingAs($a)->get(route('dashboard.quotations.payment.proof', $quotation))->assertNotFound();
        $this->actingAs($a)->post(route('dashboard.quotations.cancel', $quotation))->assertNotFound();
        $this->actingAs($a)->delete(route('dashboard.quotations.items.destroy', [$quotation, $item]))->assertNotFound();
        $this->actingAs($a)->patch(route('dashboard.quotations.items.update', [$quotation, $item]), [
            'quantity' => 99, 'technology' => 'FDM', 'material' => 'PLA Plus Standart ESUN', 'printer' => \App\Support\Printer::default(),
        ])->assertNotFound();

        $this->assertSame(QuotationStatus::REVIEWING, $quotation->fresh()->status);
        $this->assertSame(1, (int) $item->fresh()->quantity);
    }

    public function test_6b_model_milik_penawaran_lain_tidak_dapat_disentuh_lewat_penawaran_sendiri(): void
    {
        $a = User::factory()->create();
        $own = $this->quotation($a);
        $foreignItem = $this->quotation(User::factory()->create())->items()->first();

        $this->actingAs($a)->delete(route('dashboard.quotations.items.destroy', [$own, $foreignItem]))->assertNotFound();
        $this->assertNotNull($foreignItem->fresh());
    }

    /* --------------------------------------------- TEST 7: manipulasi harga --- */

    public function test_7_harga_dan_geometri_kiriman_browser_tidak_dipercaya(): void
    {
        $user = User::factory()->create();

        // Kubus 10 mm = 1 cm³, tetapi volumenya diaku jauh lebih kecil dan
        // harga/biaya internal ikut dikirim.
        $this->actingAs($user)->postJson(route('quotations.store'), [
            'items' => [$this->itemPayload($this->realUpload('kubus.stl', $this->cubeStl()), [
                'model_volume_cm3' => 0.0001,
                'model_stats' => json_encode(['dimensions' => ['x' => 0.1, 'y' => 0.1, 'z' => 0.1]]),
                'estimated_cost' => 1,
                'final_price' => 1,
                'selling_price' => 1,
                'profit' => 0,
                'basic_fee' => 0,
            ])],
            'estimated_cost' => 1,
            'estimated_price' => 1,
            'final_price' => 1,
            'status' => QuotationStatus::PAYMENT_RECEIVED,
            'user_id' => User::factory()->create()->id,
        ])->assertCreated();

        $quotation = QuotationRequest::sole();
        $item = $quotation->items->first();

        $this->assertSame($user->id, $quotation->user_id);
        $this->assertSame(QuotationStatus::first(), $quotation->status);
        $this->assertEqualsWithDelta(1.0, (float) $item->model_volume_cm3, 0.01);
        $this->assertSame('server', $item->model_stats['measured_by']);
        $this->assertEqualsWithDelta(10.0, (float) $item->model_stats['dimensions']['x'], 0.01);

        // Harga persis hasil hitung server dari volume & ukuran asli.
        $this->assertGreaterThan(1, (float) $item->estimated_cost);
        $this->assertEquals($item->cost_breakdown['selling_price'], (float) $item->estimated_cost);
    }

    public function test_7b_user_tidak_dapat_mengubah_harga_lewat_edit_spesifikasi(): void
    {
        $user = User::factory()->create();
        $quotation = $this->quotation($user);
        $item = $quotation->items()->first();

        $this->actingAs($user)->patch(route('dashboard.quotations.items.update', [$quotation, $item]), [
            'quantity' => 1, 'technology' => 'FDM', 'material' => 'PLA Plus Standart ESUN', 'printer' => \App\Support\Printer::default(),
            'estimated_cost' => 1, 'cost_breakdown' => ['selling_price' => 1], 'estimated_price' => 1, 'status' => QuotationStatus::COMPLETED,
        ]);

        $fresh = $item->fresh();
        $this->assertNotEquals(1.0, (float) $fresh->estimated_cost);
        $this->assertEquals($fresh->cost_breakdown['selling_price'], (float) $fresh->estimated_cost);
        $this->assertSame(QuotationStatus::REVIEWING, $quotation->fresh()->status);
    }

    /* ----------------------------------------- TEST 8: status sensitif --- */

    public function test_8_user_tidak_dapat_mengubah_status_menjadi_pembayaran_diterima(): void
    {
        $user = User::factory()->create();
        $quotation = $this->quotation($user, QuotationStatus::PAYMENT_REVIEW);

        $this->actingAs($user)
            ->patch(route('admin.quotations.update', $quotation), ['status' => QuotationStatus::PAYMENT_RECEIVED])
            ->assertRedirect(route('dashboard'));
        $this->actingAs($user)
            ->post(route('admin.quotations.payment.accept', $quotation))
            ->assertRedirect(route('dashboard'));
        $this->actingAs($user)
            ->patch(route('superadmin.quotations.update', $quotation), ['status' => QuotationStatus::COMPLETED])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(QuotationStatus::PAYMENT_REVIEW, $quotation->fresh()->status);
    }

    /* ------------------------------------------ TEST 9: eskalasi role --- */

    public function test_9_user_tidak_dapat_menjadikan_dirinya_superadmin(): void
    {
        $user = User::factory()->create(['phone' => '081234567890']);

        $this->actingAs($user)->patch(route('dashboard.profile.update'), [
            'name' => 'Nama Baru',
            'email' => $user->email,
            'phone' => '081234567890',
            'role' => User::ROLE_SUPERADMIN,
            'is_active' => '1',
            'permissions' => [P::QUOTATION_VIEW],
        ]);

        $fresh = $user->fresh();
        $this->assertSame(User::ROLE_USER, $fresh->role);
        $this->assertSame([], $fresh->adminPermissionKeys());

        // Mass assignment: role & is_active tidak dapat terisi lewat fill().
        $fresh->fill(['role' => User::ROLE_SUPERADMIN, 'is_active' => false]);
        $this->assertSame(User::ROLE_USER, $fresh->role);
        $this->assertTrue($fresh->isActive());
    }

    public function test_9b_admin_tidak_dapat_mengubah_akun_atau_hak_aksesnya_sendiri(): void
    {
        $admin = User::factory()->withPermissions([P::PROFILE_EDIT])->create();

        $this->actingAs($admin)->patch(route('superadmin.admins.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'is_active' => '1',
            'permissions' => P::keys(),
        ])->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->patch(route('admin.profile.update'), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => User::ROLE_SUPERADMIN,
        ]);

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
        $this->assertSame([P::PROFILE_EDIT], $admin->fresh()->adminPermissionKeys());
    }

    /* ------------------------------------------ TEST 10: upload berbahaya --- */

    public function test_10_ekstensi_dan_isi_berkas_berbahaya_ditolak(): void
    {
        $user = User::factory()->create();

        foreach ([
            $this->realUpload('shell.php', '<?php system($_GET["c"]); ?>'),
            $this->realUpload('shell.stl.php', 'solid x'),
            // Ekstensi sah, isi bukan model 3D.
            $this->realUpload('shell.stl', '<?php system($_GET["c"]); ?>'),
            $this->realUpload('page.obj', "<html>\0<script>alert(1)</script>"),
            $this->realUpload('fake.3mf', 'bukan zip'),
            $this->realUpload('fake.step', '<html></html>'),
        ] as $file) {
            $this->actingAs($user)
                ->postJson(route('quotations.store'), ['items' => [$this->itemPayload($file)]])
                ->assertStatus(422)
                ->assertJsonValidationErrors('items.0.model');
        }

        $this->assertSame(0, QuotationRequest::count());
    }

    public function test_10b_bukti_pembayaran_html_berkedok_gambar_ditolak(): void
    {
        $user = User::factory()->create();
        $quotation = $this->quotation($user, QuotationStatus::AWAITING_PAYMENT, ['payment_due_at' => now()->addDay()]);

        $this->actingAs($user)
            ->post(route('dashboard.quotations.payment.store', $quotation), [
                'proof' => $this->realUpload('bukti.png', '<html><script>alert(document.cookie)</script></html>'),
            ])
            ->assertSessionHasErrors('proof');

        $this->assertSame(QuotationStatus::AWAITING_PAYMENT, $quotation->fresh()->status);
    }

    public function test_10c_bukti_pembayaran_disajikan_dengan_tipe_tetap_dan_nosniff(): void
    {
        $user = User::factory()->create();
        Storage::disk('local')->put('payments/bukti.png', '<html><script>alert(1)</script></html>');
        $quotation = $this->quotation($user, QuotationStatus::PAYMENT_REVIEW, [
            'payment_proof_path' => 'payments/bukti.png',
            'payment_proof_name' => 'bukti.png',
        ]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('superadmin.quotations.payment.proof', $quotation))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_10d_tanda_khas_format_model(): void
    {
        $check = fn (string $ext, string $content) => ModelFile::looksLike(
            tap(tempnam(sys_get_temp_dir(), 'mdl'), fn ($p) => file_put_contents($p, $content)),
            $ext,
        );

        $this->assertTrue($check('stl', $this->cubeStl()));
        $this->assertTrue($check('stl', str_repeat("\0", 80).pack('V', 1).str_repeat("\0", 50)));
        $this->assertTrue($check('obj', "# kubus\nv 0 0 0\nf 1 2 3\n"));
        $this->assertTrue($check('obj', "# hanya komentar\n"));
        $this->assertTrue($check('obj', "mtllib a.mtl\no Cube\nv 1 1 1\n"));
        $this->assertFalse($check('obj', "<html><body>bukan obj</body></html>"));
        $this->assertTrue($check('3mf', "PK\x03\x04rest"));
        $this->assertTrue($check('step', "ISO-10303-21;\nHEADER;"));

        $this->assertFalse($check('stl', '<?php echo 1;'));
        $this->assertFalse($check('obj', "\0\0\0binary"));
        $this->assertFalse($check('stp', 'hello'));
    }

    /* ---------------------------------- TEST 11: perubahan izin langsung berlaku --- */

    public function test_11_perubahan_hak_akses_langsung_berlaku(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->withPermissions([P::QUOTATION_VIEW, P::PAYMENT_VIEW])->create();

        $this->actingAs($admin)->get('/admin/pembayaran/verifikasi')->assertOk();

        $this->actingAs($superAdmin)->patch(route('superadmin.admins.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'is_active' => '1',
            'permissions' => [P::QUOTATION_VIEW],
        ])->assertRedirect(route('superadmin.admins.index'));

        // Permintaan berikutnya dari sesi yang masih berjalan (User dimuat ulang
        // dari sesi, seperti pada request HTTP sungguhan) langsung ditolak —
        // tanpa perlu logout/login ulang.
        $this->actingAs($admin->fresh())->get('/admin/pembayaran/verifikasi')->assertForbidden();
        $this->actingAs($admin->fresh())->get('/admin/penawaran/penawaran')->assertOk();

        $this->assertTrue(ActivityLog::where('action', ActivityAction::ADMIN_PERMISSION_UPDATE)->exists());
    }

    /* ----------------------------------------- TEST 12: Price Snapshot --- */

    public function test_12_perubahan_price_list_tidak_mengubah_penawaran_lama(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('quotations.store'), [
            'items' => [$this->itemPayload($this->realUpload('kubus.stl', $this->cubeStl(40)))],
        ])->assertCreated();

        $item = QuotationItem::sole();
        $price = (float) $item->estimated_cost;
        $breakdown = $item->cost_breakdown;

        PricingFormula::general()->update(['profit_percent' => 500, 'risk_percent' => 90, 'machine_cost' => 999999]);

        $fresh = $item->fresh();
        $this->assertSame($price, (float) $fresh->estimated_cost);
        $this->assertEquals($breakdown, $fresh->cost_breakdown);

        $read = app(SellingPriceEstimator::class)->forQuotation(QuotationRequest::sole()->fresh());
        $this->assertEqualsWithDelta($price, $read['selling_price'], 0.01);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('superadmin.quotations.show', QuotationRequest::sole()))
            ->assertOk();
        $this->assertSame($price, (float) $item->fresh()->estimated_cost);
    }

    /* --------------------------------- data harga internal & jejak audit --- */

    public function test_halaman_publik_tidak_membocorkan_parameter_harga_internal(): void
    {
        PricingFormula::general()->update(['risk_percent' => 13, 'profit_percent' => 37, 'machine_cost' => 12345]);

        $response = $this->get(route('models'))->assertOk();

        preg_match('/<script type="application\/json" data-printing-config>(.*?)<\/script>/s', $response->getContent(), $m);
        $config = json_decode(html_entity_decode($m[1] ?? ''), true);

        $this->assertIsArray($config);
        $this->assertSame(0, $config['pricing']['formula']['riskPercent']);
        $this->assertSame(0, $config['pricing']['formula']['profitPercent']);
        $this->assertNotEquals(12345.0, (float) $config['pricing']['formula']['machineCost']);

        foreach ($config['technologies'] as $technology) {
            $this->assertArrayNotHasKey('machineRate', $technology);
            $this->assertArrayNotHasKey('setupFee', $technology);
        }

        // Halaman viewer tidak memuat parameter harga sama sekali.
        $this->get(route('models.viewer.show', 'b6f1c2d4-3e5a-4f70-9a1b-2c3d4e5f6a7b'))
            ->assertOk()
            ->assertDontSee('data-printing-config', false)
            ->assertDontSee('pricePerGram', false);
    }

    public function test_activity_log_tidak_menyimpan_rahasia_tetapi_menyimpan_kolom_biasa(): void
    {
        $log = app(ActivityLogger::class)->log(
            action: ActivityAction::PROFILE_UPDATE,
            new: [
                'password' => 'x', 'password_confirmation' => 'x', 'current_password' => 'x',
                'remember_token' => 'x', 'api_token' => 'x', 'user_pin' => 'x', 'otp' => 'x',
                'shipping_address' => 'Jl. Merdeka 1', 'author' => 'Budi',
            ],
        );

        $this->assertSame(['shipping_address' => 'Jl. Merdeka 1', 'author' => 'Budi'], $log->new_values);
    }
}
