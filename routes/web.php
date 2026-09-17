<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyPathRedirectController;
use App\Http\Controllers\ModelCheckController;
use App\Support\AdminPermission;
use App\Http\Controllers\QuotationRequestController;
use App\Http\Controllers\QuotationTrackingController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SuperAdmin;
use App\Http\Controllers\TechnologyController;
use App\Models\PricingFormula;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman Publik
|--------------------------------------------------------------------------
| Lima menu utama company profile. Nama route dipakai navbar & footer untuk
| menandai halaman aktif, jadi jangan diubah tanpa menyesuaikan keduanya.
|
| Seluruh halaman ini — termasuk 3D Models beserta unggah model, pratinjau
| 3D, dan seluruh simulasinya — tetap terbuka untuk pengunjung tanpa akun.
| Yang membutuhkan akun hanya pengiriman permintaan penawaran.
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/services', [ServiceController::class, 'index'])->name('services');
Route::get('/technologies', [TechnologyController::class, 'index'])->name('technologies');
Route::get('/3d-models', [ModelCheckController::class, 'index'])->name('models');
// Viewer 3D satu model, dibuka di tab baru dari daftar halaman 3D Models.
Route::get('/3d-models/viewer', [ModelCheckController::class, 'viewer'])->name('models.viewer');
// Panduan menyiapkan model, ditautkan tombol di atas area unggah.
Route::get('/3d-models/panduan', [ModelCheckController::class, 'guide'])->name('models.guide');
Route::get('/about', [AboutController::class, 'index'])->name('about');

// Halaman "Cek Barang" berganti nama menjadi "3D Models". Tautan lama yang
// terlanjur tersebar tetap sampai ke tujuannya lewat redirect permanen.
Route::permanentRedirect('/cek-barang', '/3d-models');
Route::permanentRedirect('/cek-barang/viewer', '/3d-models/viewer');

Route::post('/3d-models/penawaran', [QuotationRequestController::class, 'store'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('quotations.store');

/*
|--------------------------------------------------------------------------
| Tracking Penawaran
|--------------------------------------------------------------------------
| Dapat diakses tanpa login memakai nomor tracking. Nomor berisi enam karakter
| acak sehingga tidak praktis ditebak, dan halamannya menyamarkan email serta
| nomor WhatsApp pelanggan.
*/

Route::get('/tracking', [QuotationTrackingController::class, 'index'])->name('tracking.index');
Route::post('/tracking', [QuotationTrackingController::class, 'lookup'])
    ->middleware('throttle:20,1')
    ->name('tracking.lookup');
Route::get('/tracking/{trackingNumber}', [QuotationTrackingController::class, 'show'])->name('tracking.show');
Route::get('/tracking/{trackingNumber}/bukti-penawaran', [QuotationTrackingController::class, 'document'])
    ->middleware('throttle:30,1')
    ->name('tracking.document');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| Wilayah Indonesia
|--------------------------------------------------------------------------
| Isi dropdown bertingkat provinsi → kabupaten/kota → kecamatan → kelurahan.
| Dipakai buku alamat di dashboard sekaligus data perusahaan pada pendaftaran
| Business — yang kedua diisi sebelum akun ada, jadi endpointnya terbuka.
|
| Daftar wilayah administratif memang informasi publik; pembatasan laju di
| bawah hanya menjaga agar tidak dipakai menggerus basis data.
*/

Route::middleware('throttle:120,1')->name('regions.')->group(function () {
    Route::get('/wilayah/provinsi/{province}/kabupaten', [RegionController::class, 'regencies'])->name('regencies');
    Route::get('/wilayah/kabupaten/{regency}/kecamatan', [RegionController::class, 'districts'])->name('districts');
    Route::get('/wilayah/kecamatan/{district}/kelurahan', [RegionController::class, 'villages'])->name('villages');
});

/*
|--------------------------------------------------------------------------
| Autentikasi Pelanggan
|--------------------------------------------------------------------------
| Admin memakai halaman masuknya sendiri di /admin/login, tetapi keduanya
| berbagi guard, tabel pengguna, dan alur reset kata sandi yang sama.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [Auth\AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    /*
    | Pendaftaran bertahap.
    |
    | Halaman pertama hanya meminta tipe akun (Personal atau Business), lalu
    | alurnya berlanjut ke data akun, pertanyaan per langkah, dan ringkasan.
    | Susunan langkahnya dibaca dari tabel `registration_questions`, jadi
    | menambah pertanyaan tidak menuntut route baru.
    */
    Route::get('/register', [Auth\RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register/tipe', [Auth\RegisteredUserController::class, 'type'])
        ->middleware('throttle:20,1')
        ->name('register.type');

    Route::get('/register/langkah/{step}', [Auth\RegisteredUserController::class, 'step'])->name('register.step');
    Route::post('/register/langkah/{step}', [Auth\RegisteredUserController::class, 'storeStep'])
        ->middleware('throttle:30,1')
        ->name('register.step.store');

    Route::post('/register', [Auth\RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.store');

    Route::get('/lupa-password', [Auth\PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/lupa-password', [Auth\PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    // Nama route ini dipakai notifikasi bawaan Laravel untuk menyusun tautan
    // reset di dalam email, jadi tidak boleh diubah.
    Route::get('/reset-password/{token}', [Auth\NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [Auth\NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::post('/logout', [Auth\AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Dashboard Pelanggan
|--------------------------------------------------------------------------
| Seluruh penawaran milik akun yang sedang masuk: daftar, detail, penyuntingan
| selama masih "Menunggu Review", pembatalan, notifikasi, dan profil.
*/

Route::middleware(['auth', 'customer'])->prefix('dashboard')->group(function () {
    Route::get('/', [Dashboard\DashboardController::class, 'index'])->name('dashboard');

    Route::name('dashboard.')->group(function () {
        Route::get('penawaran', [Dashboard\QuotationController::class, 'index'])->name('quotations.index');
        Route::get('penawaran/{quotation}', [Dashboard\QuotationController::class, 'show'])->name('quotations.show');
        Route::get('penawaran/{quotation}/ubah', [Dashboard\QuotationController::class, 'edit'])->name('quotations.edit');

        // Penyuntingan hanya diterima selama status masih "Menunggu Review";
        // pemeriksaannya ada di controller, bukan di sini, agar pesan
        // penolakannya dapat dijelaskan ke pengguna.
        Route::post('penawaran/{quotation}/model', [Dashboard\QuotationController::class, 'storeItem'])->name('quotations.items.store');
        Route::patch('penawaran/{quotation}/model/{item}', [Dashboard\QuotationController::class, 'updateItem'])
            ->scopeBindings()
            ->name('quotations.items.update');
        Route::delete('penawaran/{quotation}/model/{item}', [Dashboard\QuotationController::class, 'destroyItem'])
            ->scopeBindings()
            ->name('quotations.items.destroy');

        Route::post('penawaran/{quotation}/pembatalan', [Dashboard\QuotationController::class, 'cancel'])->name('quotations.cancel');

        // Halaman "Menunggu Pembayaran": rekening tujuan, hitung mundur 24 jam,
        // dan unggah bukti transfer.
        Route::get('penawaran/{quotation}/pembayaran', [Dashboard\PaymentController::class, 'show'])->name('quotations.payment');
        Route::post('penawaran/{quotation}/pembayaran', [Dashboard\PaymentController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('quotations.payment.store');
        Route::get('penawaran/{quotation}/pembayaran/bukti', [Dashboard\PaymentController::class, 'proof'])->name('quotations.payment.proof');

        /*
        | Pembayaran bertahap, khusus akun Business.
        |
        | Pemeriksaan tipe akun ada di controller — bukan middleware — agar
        | pelanggan Personal yang tersasar ke sini menerima penjelasan, bukan
        | halaman galat. Alur pembayaran mereka sendiri tidak berubah.
        */
        Route::get('penawaran/{quotation}/skema-pembayaran', [Dashboard\PaymentTermController::class, 'create'])->name('quotations.payment-term');
        Route::post('penawaran/{quotation}/skema-pembayaran', [Dashboard\PaymentTermController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('quotations.payment-term.store');

        Route::get('penawaran/{quotation}/termin/{installment}', [Dashboard\InstallmentController::class, 'show'])->name('quotations.installments.show');
        Route::post('penawaran/{quotation}/termin/{installment}', [Dashboard\InstallmentController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('quotations.installments.store');
        Route::get('penawaran/{quotation}/termin/{installment}/bukti/{proof}', [Dashboard\InstallmentController::class, 'proof'])->name('quotations.installments.proof');

        /*
        | Buku alamat pengiriman.
        |
        | Alamat utama terpilih lebih dulu pada formulir "Minta Penawaran" dan
        | dicerminkan ke kolom alamat pada akun, jadi pelanggan cukup
        | mengurusnya di satu tempat.
        */
        Route::get('alamat', [Dashboard\AddressController::class, 'index'])->name('addresses.index');
        Route::get('alamat/baru', [Dashboard\AddressController::class, 'create'])->name('addresses.create');
        Route::post('alamat', [Dashboard\AddressController::class, 'store'])->name('addresses.store');
        Route::get('alamat/{address}/ubah', [Dashboard\AddressController::class, 'edit'])->name('addresses.edit');
        Route::patch('alamat/{address}', [Dashboard\AddressController::class, 'update'])->name('addresses.update');
        Route::delete('alamat/{address}', [Dashboard\AddressController::class, 'destroy'])->name('addresses.destroy');
        Route::post('alamat/{address}/utama', [Dashboard\AddressController::class, 'makeDefault'])->name('addresses.default');

        /*
        | Pemesanan ulang, khusus akun Business.
        |
        | Dijaga middleware `business` supaya akun Personal tidak dapat
        | memakainya lewat penebakan alamat, sesuai pemisahan dashboard.
        */
        Route::post('penawaran/{quotation}/pesan-ulang', [Dashboard\ReorderController::class, 'store'])
            ->middleware(['business', 'throttle:10,1'])
            ->name('quotations.reorder');

        Route::get('notifikasi', [Dashboard\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifikasi/terbaru', [Dashboard\NotificationController::class, 'latest'])->name('notifications.latest');
        Route::post('notifikasi/baca-semua', [Dashboard\NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('notifikasi/{notification}/baca', [Dashboard\NotificationController::class, 'read'])->name('notifications.read');

        Route::get('profil', [Dashboard\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profil', [Dashboard\ProfileController::class, 'update'])->name('profile.update');

        /*
        | Informasi Perusahaan, khusus akun Business.
        |
        | Nama perusahaan pada modal "Minta Penawaran" dibaca dari sini dan
        | tidak dapat diketik di sana, jadi halaman ini yang menjadi tempat
        | mengubahnya.
        */
        Route::middleware('business')->group(function () {
            Route::get('profil-perusahaan', [Dashboard\CompanyProfileController::class, 'edit'])->name('company-profile.edit');
            Route::patch('profil-perusahaan', [Dashboard\CompanyProfileController::class, 'update'])->name('company-profile.update');
        });

        Route::get('ganti-password', [Auth\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('ganti-password', [Auth\PasswordController::class, 'update'])->name('password.update');
    });
});

/*
|--------------------------------------------------------------------------
| Dashboard Admin
|--------------------------------------------------------------------------
| Pengelolaan permintaan penawaran, pengguna, dan persetujuan pembatalan.
*/

/*
|--------------------------------------------------------------------------
| Menu Pengelola yang Dipakai Bersama
|--------------------------------------------------------------------------
| Penawaran, Verifikasi Pembayaran, Payment Term, Notifikasi, Profil, dan
| Ganti Password adalah hak Admin maupun Superadmin. Definisinya ditulis
| sekali di sini lalu dipasang dua kali — di bawah /admin dengan nama
| admin.* dan di bawah /superadmin dengan nama superadmin.* — sehingga
| keduanya punya alamat sendiri tanpa pernah bisa berbeda isi.
|
| Yang memilih nama route saat menyusun tautan adalah helper staff_route();
| lihat app/Support/helpers.php.
*/
$staffRoutes = function () {
    /*
    | Setiap menu staf dijaga hak aksesnya sendiri (`admin.permission`), yang
    | diatur Superadmin per akun Admin. Superadmin selalu lolos. Lihat
    | App\Support\AdminPermission.
    */
    /*
    | Menu staf dikelompokkan di sidebar — Akun, Penawaran, Pembayaran — dan
    | alamatnya mengikuti kelompok itu. Alamat lama diteruskan ke alamat baru
    | (lihat LegacyPathRedirectController) karena notifikasi yang sudah
    | tersimpan dan bookmark masih menunjuknya. Hanya GET: formulir selalu
    | dibangun dari nama route, jadi sudah memakai alamat baru.
    */
    foreach ([
        'permintaan' => 'penawaran/penawaran',
        'verifikasi-pembayaran' => 'pembayaran/verifikasi',
        'payment-terms' => 'pembayaran/payment-term',
        'notifikasi' => 'penawaran/notifikasi',
        'profil' => 'akun/profil',
        'ganti-password' => 'akun/ganti-password',
    ] as $from => $to) {
        Route::get($from.'/{legacyPath?}', LegacyPathRedirectController::class)
            ->where('legacyPath', '.*')
            ->defaults('from', $from)
            ->defaults('to', $to);
    }

    /*
    | Hak akses Admin berbentuk `<modul>.<aksi>` (App\Support\AdminPermission).
    | Setiap grup menu dijaga hak MELIHAT-nya (`*.view`); setiap route yang
    | mengubah data dijaga lagi oleh hak TINDAKAN-nya sendiri, sehingga Admin
    | yang hanya boleh melihat tetap ditolak (403) bila memanggil endpoint
    | edit/hapus secara manual. Superadmin selalu lolos.
    */
    $can = fn (string $permission) => 'admin.permission:'.$permission;

    // Penawaran.
    Route::middleware($can(AdminPermission::QUOTATION_VIEW))->group(function () use ($can) {
        Route::get('penawaran/penawaran', [Admin\QuotationRequestController::class, 'index'])->name('quotations.index');
        Route::get('penawaran/penawaran/{quotation}', [Admin\QuotationRequestController::class, 'show'])->name('quotations.show');
        Route::get('penawaran/penawaran/{quotation}/unduh', [Admin\QuotationRequestController::class, 'download'])->name('quotations.download');

        // Tindak lanjut status (beserta estimasi, catatan, dan foto prosesnya).
        Route::patch('penawaran/penawaran/{quotation}', [Admin\QuotationRequestController::class, 'update'])
            ->middleware($can(AdminPermission::QUOTATION_UPDATE_STATUS))
            ->name('quotations.update');
        Route::delete('penawaran/penawaran/{quotation}', [Admin\QuotationRequestController::class, 'destroy'])
            ->middleware($can(AdminPermission::QUOTATION_DELETE))
            ->name('quotations.destroy');

        // Keputusan atas permintaan pembatalan yang diajukan pelanggan — ikut
        // mengubah status penawaran.
        Route::post('penawaran/penawaran/{quotation}/pembatalan/setujui', [Admin\QuotationRequestController::class, 'approveCancellation'])
            ->middleware($can(AdminPermission::QUOTATION_UPDATE_STATUS))
            ->name('quotations.cancellation.approve');
        Route::post('penawaran/penawaran/{quotation}/pembatalan/tolak', [Admin\QuotationRequestController::class, 'rejectCancellation'])
            ->middleware($can(AdminPermission::QUOTATION_UPDATE_STATUS))
            ->name('quotations.cancellation.reject');

        // Satu penawaran dapat berisi beberapa model; tiap model punya berkas,
        // estimasi, dan catatannya sendiri.
        Route::get('penawaran/penawaran/{quotation}/model/{item}/unduh', [Admin\QuotationRequestController::class, 'downloadItem'])
            ->scopeBindings()
            ->name('quotations.items.download');
        Route::patch('penawaran/penawaran/{quotation}/model/{item}', [Admin\QuotationRequestController::class, 'updateItem'])
            ->scopeBindings()
            ->middleware($can(AdminPermission::QUOTATION_EDIT))
            ->name('quotations.items.update');

        /*
        | Kurs USD/IDR untuk Form Perhitungan SLA Industries. Dibaca formulirnya
        | saat dibuka, pada tiap penyegaran berkala, dan oleh tombol "Coba Lagi".
        | Hanya dipakai formulir penetapan harga, jadi mengikuti hak Edit.
        */
        Route::get('kurs-usd', [Admin\ExchangeRateController::class, 'usd'])
            ->middleware($can(AdminPermission::QUOTATION_EDIT))
            ->name('exchange-rate.usd');

        // Form Perhitungan SLA Industries satu model: kuotasi JLC yang menetapkan
        // harganya. Hanya berlaku bagi model berteknologi SLA Industries;
        // controllernya menolak sisanya.
        Route::patch('penawaran/penawaran/{quotation}/model/{item}/sla-industries', [Admin\QuotationRequestController::class, 'updateSlaIndustriesQuote'])
            ->scopeBindings()
            ->middleware($can(AdminPermission::QUOTATION_EDIT))
            ->name('quotations.items.sla-industries');
    });

    // Verifikasi Pembayaran: bukti transfer yang masuk beserta keputusan
    // terima atau tolak.
    Route::middleware($can(AdminPermission::PAYMENT_VIEW))->group(function () use ($can) {
        Route::get('pembayaran/verifikasi', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('pembayaran/verifikasi/{quotation}/bukti', [Admin\PaymentController::class, 'proof'])->name('payments.proof');
        Route::get('pembayaran/verifikasi/termin/{installment}/bukti/{proof}', [Admin\PaymentController::class, 'installmentProof'])->name('payments.installments.proof');

        // Keputusan pembayaran penuh maupun per termin.
        Route::middleware($can(AdminPermission::PAYMENT_VERIFY))->group(function () {
            Route::post('pembayaran/verifikasi/{quotation}/terima', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
            Route::post('pembayaran/verifikasi/{quotation}/tolak', [Admin\PaymentController::class, 'reject'])->name('payments.reject');
            Route::post('pembayaran/verifikasi/termin/{installment}/terima', [Admin\PaymentController::class, 'approveInstallment'])->name('payments.installments.approve');
            Route::post('pembayaran/verifikasi/termin/{installment}/tolak', [Admin\PaymentController::class, 'rejectInstallment'])->name('payments.installments.reject');
        });
    });

    /*
    | Payment Terms: seluruh penawaran Business yang memakai pembayaran
    | bertahap, persetujuan skemanya, dan pengaturan batas nominalnya.
    |
    | Route pengaturan didaftarkan sebelum route berparameter agar
    | "pengaturan" tidak tertangkap sebagai id payment term.
    */
    Route::middleware($can(AdminPermission::PAYMENT_TERM_VIEW))->group(function () use ($can) {
        Route::get('pembayaran/payment-term', [Admin\PaymentTermController::class, 'index'])->name('payment-terms.index');
        Route::get('pembayaran/payment-term/pengaturan', [Admin\PaymentTermSettingController::class, 'edit'])
            ->middleware($can(AdminPermission::PAYMENT_TERM_EDIT))
            ->name('payment-terms.settings.edit');
        Route::patch('pembayaran/payment-term/pengaturan', [Admin\PaymentTermSettingController::class, 'update'])
            ->middleware($can(AdminPermission::PAYMENT_TERM_EDIT))
            ->name('payment-terms.settings.update');

        Route::get('pembayaran/payment-term/{term}', [Admin\PaymentTermController::class, 'show'])->name('payment-terms.show');

        Route::middleware($can(AdminPermission::PAYMENT_TERM_EDIT))->group(function () {
            Route::post('pembayaran/payment-term/{term}/setujui', [Admin\PaymentTermController::class, 'approve'])->name('payment-terms.approve');
            Route::post('pembayaran/payment-term/{term}/tolak', [Admin\PaymentTermController::class, 'reject'])->name('payment-terms.reject');
            Route::patch('pembayaran/payment-term/{term}/jadwal', [Admin\PaymentTermController::class, 'updateSchedule'])->name('payment-terms.schedule');
            Route::post('pembayaran/payment-term/{term}/termin/{installment}/aktifkan', [Admin\PaymentTermController::class, 'activate'])->name('payment-terms.installments.activate');
        });
    });

    // Notifikasi (juga lonceng di header).
    Route::middleware($can(AdminPermission::NOTIFICATION_VIEW))->group(function () {
        Route::get('penawaran/notifikasi', [Admin\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('penawaran/notifikasi/terbaru', [Admin\NotificationController::class, 'latest'])->name('notifications.latest');
        Route::post('penawaran/notifikasi/baca-semua', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('penawaran/notifikasi/{notification}/baca', [Admin\NotificationController::class, 'read'])->name('notifications.read');
    });

    /*
    | User: daftar pelanggan dan detailnya, hanya membaca. Sebelumnya khusus
    | Superadmin; kini juga dapat dibuka Admin yang diberi hak `user.view`.
    */
    Route::middleware($can(AdminPermission::USER_VIEW))->group(function () {
        Route::get('akun/user', [SuperAdmin\UserController::class, 'index'])->name('users.index');
        Route::get('akun/user/{user}', [SuperAdmin\UserController::class, 'show'])->name('users.show');
    });

    // Profil.
    Route::middleware($can(AdminPermission::PROFILE_EDIT))->group(function () {
        Route::get('akun/profil', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('akun/profil', [Admin\ProfileController::class, 'update'])->name('profile.update');
    });

    // Ganti Password.
    Route::middleware($can(AdminPermission::PROFILE_SECURITY))->group(function () {
        Route::get('akun/ganti-password', [Auth\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('akun/ganti-password', [Auth\PasswordController::class, 'update'])->name('password.update');
    });
};

Route::prefix('admin')->name('admin.')->group(function () use ($staffRoutes) {
    Route::get('login', [Admin\LoginController::class, 'create'])->name('login');
    Route::post('login', [Admin\LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () use ($staffRoutes) {
        Route::post('logout', [Admin\LoginController::class, 'destroy'])->name('logout');

        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        $staffRoutes();
    });
});

/*
|--------------------------------------------------------------------------
| Dashboard Superadmin
|--------------------------------------------------------------------------
| Wilayah dengan akses tertinggi: statistik keseluruhan sistem, master data
| harga, seluruh akun pelanggan, jejak audit, dan pengelolaan akun Admin.
|
| Tugas operasional harian — penawaran, verifikasi pembayaran, payment term,
| notifikasi, profil, dan ganti password — tetap berada pada grup admin dan
| dipakai bersama, karena Superadmin memang menjalankannya juga: lihat
| App\Models\User::isAdmin(), yang ikut bernilai true baginya.
|
| Yang berada DI SINI hanya milik Superadmin, dijaga middleware tersendiri
| sehingga URL-nya benar-benar tertutup bagi Admin biasa — bukan sekadar
| disembunyikan dari sidebar.
*/

// Halaman masuk Superadmin — formulir yang sama dengan /admin/login. Sengaja
// di luar grup berikutnya, yang mewajibkan sudah masuk.
Route::get('superadmin/login', [Admin\LoginController::class, 'create'])->name('superadmin.login');

Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'superadmin'])->group(function () use ($staffRoutes) {
    // Alamat sendiri untuk seluruh menu operasional, isinya sama persis
    // dengan milik Admin.
    $staffRoutes();

    Route::get("/", [SuperAdmin\DashboardController::class, "index"])->name("dashboard");

    // Alamat lama menu Akun milik Superadmin, diteruskan ke alamat barunya.
    foreach (['akun-admin' => 'akun/admin', 'pengguna' => 'akun/user', 'activity-logs' => 'akun/activity-log'] as $from => $to) {
        Route::get($from.'/{legacyPath?}', LegacyPathRedirectController::class)
            ->where('legacyPath', '.*')
            ->defaults('from', $from)
            ->defaults('to', $to);
    }

    /*
    | Akun Admin: satu-satunya tempat akun pengelola dibuat, disunting, dan
    | dinonaktifkan. Akun Superadmin sendiri tidak dapat disentuh dari sini.
    */
    Route::get("akun/admin", [SuperAdmin\AdminAccountController::class, "index"])->name("admins.index");
    Route::get("akun/admin/tambah", [SuperAdmin\AdminAccountController::class, "create"])->name("admins.create");
    Route::post("akun/admin", [SuperAdmin\AdminAccountController::class, "store"])->name("admins.store");
    Route::get("akun/admin/{admin}/edit", [SuperAdmin\AdminAccountController::class, "edit"])->name("admins.edit");
    Route::patch("akun/admin/{admin}", [SuperAdmin\AdminAccountController::class, "update"])->name("admins.update");
    Route::delete("akun/admin/{admin}", [SuperAdmin\AdminAccountController::class, "destroy"])->name("admins.destroy");
    /*
    | Price List: harga material, packaging, dan mesin — sumber data
    | Calculator/Quotation. Tiap item menu sidebar punya halamannya sendiri
    | (lihat App\Support\PriceListPage). Alamat lama /price-list?tab=…
    | diteruskan ke halaman barunya.
    |
    | Halaman per teknologi (/price-list/fdm, /price-list/sla, …) didaftarkan
    | PALING AKHIR di bawah, supaya alamat tetap seperti /price-list/harga
    | tidak tertangkap sebagai kode teknologi.
    */
    Route::get('price-list', [SuperAdmin\PriceListController::class, 'index'])->name('price-list.index');
    Route::get('price-list/machine-cost', [SuperAdmin\PriceListController::class, 'machineCost'])->name('price-list.machine-cost.index');
    Route::get('price-list/rumus-harga-otomatis', [SuperAdmin\PriceListController::class, 'harga'])->name('price-list.harga');
    Route::get('price-list/rumus-harga-manual', [SuperAdmin\PriceListController::class, 'hargaManual'])->name('price-list.harga-manual');
    // Alamat lama halaman Harga.
    Route::redirect('price-list/harga', '/superadmin/price-list/rumus-harga-otomatis');
    Route::get('price-list/packaging', [SuperAdmin\PriceListController::class, 'packaging'])->name('price-list.packaging.index');
    Route::get('price-list/teknologi', [SuperAdmin\PriceListController::class, 'technologies'])->name('price-list.technologies.index');

    /*
    | Teknologi cetak: menambah satu di sini langsung memunculkan tabnya
    | sendiri pada Price List, pilihannya pada Edit Specification, dan baris
    | parameternya pada tab Harga.
    |
    | Route "tambah" didaftarkan sebelum route berparameter agar tidak
    | tertangkap sebagai id teknologi.
    */
    Route::get('price-list/teknologi/tambah', [SuperAdmin\PrintTechnologyController::class, 'create'])->name('price-list.technologies.create');
    Route::post('price-list/teknologi', [SuperAdmin\PrintTechnologyController::class, 'store'])->name('price-list.technologies.store');
    Route::get('price-list/teknologi/{technology}/edit', [SuperAdmin\PrintTechnologyController::class, 'edit'])->name('price-list.technologies.edit');
    Route::patch('price-list/teknologi/{technology}', [SuperAdmin\PrintTechnologyController::class, 'update'])->name('price-list.technologies.update');
    // Switch Status pada tabel Teknologi: aktif = tampil di Edit Specification.
    Route::patch('price-list/teknologi/{technology}/status', [SuperAdmin\PrintTechnologyController::class, 'updateStatus'])->name('price-list.technologies.status');
    Route::delete('price-list/teknologi/{technology}', [SuperAdmin\PrintTechnologyController::class, 'destroy'])->name('price-list.technologies.destroy');

    /*
    | Material milik satu teknologi. Teknologinya menjadi parameter route,
    | jadi satu controller melayani seluruh tab — termasuk tab teknologi yang
    | baru ditambahkan Superadmin.
    */
    Route::get('price-list/material/{technology}/tambah', [SuperAdmin\PriceListMaterialController::class, 'create'])->name('price-list.materials.create');
    Route::post('price-list/material/{technology}', [SuperAdmin\PriceListMaterialController::class, 'store'])->name('price-list.materials.store');
    Route::delete('price-list/material/{technology}/hapus-terpilih', [SuperAdmin\PriceListMaterialController::class, 'destroyMany'])->name('price-list.materials.destroy-many');
    Route::get('price-list/material/{technology}/{material}/edit', [SuperAdmin\PriceListMaterialController::class, 'edit'])->name('price-list.materials.edit');
    Route::patch('price-list/material/{technology}/{material}', [SuperAdmin\PriceListMaterialController::class, 'update'])->name('price-list.materials.update');
    // Switch Status material: aktif = tampil di Edit Specification.
    Route::patch('price-list/material/{technology}/{material}/status', [SuperAdmin\PriceListMaterialController::class, 'updateStatus'])->name('price-list.materials.status');
    Route::delete('price-list/material/{technology}/{material}', [SuperAdmin\PriceListMaterialController::class, 'destroy'])->name('price-list.materials.destroy');


    // Penghapusan massal didaftarkan SEBELUM route berparameter agar
    // "hapus-terpilih" tidak tertangkap sebagai id material.

    // Penghapusan massal didaftarkan SEBELUM route berparameter agar
    // "hapus-terpilih" tidak tertangkap sebagai id material.

    Route::get('price-list/packaging/create', [SuperAdmin\PackagingItemController::class, 'create'])->name('price-list.packaging.create');
    Route::post('price-list/packaging', [SuperAdmin\PackagingItemController::class, 'store'])->name('price-list.packaging.store');
    Route::get('price-list/packaging/{packagingItem}/edit', [SuperAdmin\PackagingItemController::class, 'edit'])->name('price-list.packaging.edit');
    Route::patch('price-list/packaging/{packagingItem}', [SuperAdmin\PackagingItemController::class, 'update'])->name('price-list.packaging.update');
    Route::delete('price-list/packaging/{packagingItem}', [SuperAdmin\PackagingItemController::class, 'destroy'])->name('price-list.packaging.destroy');

    Route::get('price-list/machine-cost/create', [SuperAdmin\MachineCostController::class, 'create'])->name('price-list.machine-cost.create');
    Route::post('price-list/machine-cost', [SuperAdmin\MachineCostController::class, 'store'])->name('price-list.machine-cost.store');
    Route::get('price-list/machine-cost/{machineCost}/edit', [SuperAdmin\MachineCostController::class, 'edit'])->name('price-list.machine-cost.edit');
    Route::patch('price-list/machine-cost/{machineCost}', [SuperAdmin\MachineCostController::class, 'update'])->name('price-list.machine-cost.update');
    Route::delete('price-list/machine-cost/{machineCost}', [SuperAdmin\MachineCostController::class, 'destroy'])->name('price-list.machine-cost.destroy');

    /*
    | Rumus Harga SLA Industries: parameter BAWAAN yang mengisi form
    | perhitungan tiap model saat Admin membukanya pertama kali. Satu baris
    | saja, jadi hanya ada aksi simpan — tanpa tambah maupun hapus.
    */
    Route::patch('price-list/sla-industries/rumus', [SuperAdmin\SlaIndustriesFormulaController::class, 'update'])
        ->name('price-list.sla-industries.update');

    // Tab Harga: rumus & parameter simulasi Harga Jual per teknologi.
    // Satu baris per teknologi, dibuat otomatis saat teknologinya ditambah;
    // tidak ada tambah/hapus dari sini. Kode teknologi selalu huruf kapital,
    // keberadaannya diperiksa controller karena daftarnya kini dapat berubah.
    Route::patch('price-list/rumus-harga-otomatis', [SuperAdmin\PricingFormulaController::class, 'update'])
        ->name('price-list.harga.update');

    // Material satu teknologi, mis. /price-list/fdm. Terakhir — lihat catatan di atas.
    Route::get('price-list/{slug}', [SuperAdmin\PriceListController::class, 'technology'])
        ->where('slug', '[a-z0-9]+')
        ->name('price-list.technology');

    // Menu User (akun/user) kini didaftarkan lewat $staffRoutes di atas.

    /*
    | Activity Logs: jejak audit seluruh aktivitas penting pelanggan dan
    | pengelola.
    |
    | Hanya dua route baca. Jejak audit tidak menyediakan penyuntingan
    | maupun penghapusan dengan sengaja — riwayat yang dapat diubah tidak
    | lagi dapat dijadikan bukti.
    */
    Route::get('akun/activity-log', [SuperAdmin\ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('akun/activity-log/{activityLog}', [SuperAdmin\ActivityLogController::class, 'show'])->name('activity-logs.show');

});
