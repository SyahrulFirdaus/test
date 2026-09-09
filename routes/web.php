<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModelCheckController;
use App\Http\Controllers\QuotationRequestController;
use App\Http\Controllers\QuotationTrackingController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TechnologyController;
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

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [Admin\LoginController::class, 'create'])->name('login');
    Route::post('login', [Admin\LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [Admin\LoginController::class, 'destroy'])->name('logout');

        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('permintaan', [Admin\QuotationRequestController::class, 'index'])->name('quotations.index');
        Route::get('permintaan/{quotation}', [Admin\QuotationRequestController::class, 'show'])->name('quotations.show');
        Route::patch('permintaan/{quotation}', [Admin\QuotationRequestController::class, 'update'])->name('quotations.update');
        Route::get('permintaan/{quotation}/unduh', [Admin\QuotationRequestController::class, 'download'])->name('quotations.download');
        Route::delete('permintaan/{quotation}', [Admin\QuotationRequestController::class, 'destroy'])->name('quotations.destroy');

        // Keputusan atas permintaan pembatalan yang diajukan pelanggan.
        Route::post('permintaan/{quotation}/pembatalan/setujui', [Admin\QuotationRequestController::class, 'approveCancellation'])->name('quotations.cancellation.approve');
        Route::post('permintaan/{quotation}/pembatalan/tolak', [Admin\QuotationRequestController::class, 'rejectCancellation'])->name('quotations.cancellation.reject');

        // Satu penawaran dapat berisi beberapa model; tiap model punya berkas,
        // estimasi, dan catatannya sendiri.
        Route::get('permintaan/{quotation}/model/{item}/unduh', [Admin\QuotationRequestController::class, 'downloadItem'])
            ->scopeBindings()
            ->name('quotations.items.download');
        Route::patch('permintaan/{quotation}/model/{item}', [Admin\QuotationRequestController::class, 'updateItem'])
            ->scopeBindings()
            ->name('quotations.items.update');

        // Verifikasi Pembayaran: bukti transfer yang masuk beserta keputusan
        // terima atau tolak.
        Route::get('verifikasi-pembayaran', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('verifikasi-pembayaran/{quotation}/bukti', [Admin\PaymentController::class, 'proof'])->name('payments.proof');
        Route::post('verifikasi-pembayaran/{quotation}/terima', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('verifikasi-pembayaran/{quotation}/tolak', [Admin\PaymentController::class, 'reject'])->name('payments.reject');

        // Verifikasi bukti pembayaran per termin, satu antrean dengan menu di
        // atas namun pada tab tersendiri.
        Route::get('verifikasi-pembayaran/termin/{installment}/bukti/{proof}', [Admin\PaymentController::class, 'installmentProof'])->name('payments.installments.proof');
        Route::post('verifikasi-pembayaran/termin/{installment}/terima', [Admin\PaymentController::class, 'approveInstallment'])->name('payments.installments.approve');
        Route::post('verifikasi-pembayaran/termin/{installment}/tolak', [Admin\PaymentController::class, 'rejectInstallment'])->name('payments.installments.reject');

        /*
        | Payment Terms: seluruh penawaran Business yang memakai pembayaran
        | bertahap, persetujuan skemanya, dan pengaturan batas nominalnya.
        |
        | Route pengaturan didaftarkan sebelum route berparameter agar
        | "pengaturan" tidak tertangkap sebagai id payment term.
        */
        Route::get('payment-terms', [Admin\PaymentTermController::class, 'index'])->name('payment-terms.index');
        Route::get('payment-terms/pengaturan', [Admin\PaymentTermSettingController::class, 'edit'])->name('payment-terms.settings.edit');
        Route::patch('payment-terms/pengaturan', [Admin\PaymentTermSettingController::class, 'update'])->name('payment-terms.settings.update');

        Route::get('payment-terms/{term}', [Admin\PaymentTermController::class, 'show'])->name('payment-terms.show');
        Route::post('payment-terms/{term}/setujui', [Admin\PaymentTermController::class, 'approve'])->name('payment-terms.approve');
        Route::post('payment-terms/{term}/tolak', [Admin\PaymentTermController::class, 'reject'])->name('payment-terms.reject');
        Route::patch('payment-terms/{term}/jadwal', [Admin\PaymentTermController::class, 'updateSchedule'])->name('payment-terms.schedule');
        Route::post('payment-terms/{term}/termin/{installment}/aktifkan', [Admin\PaymentTermController::class, 'activate'])->name('payment-terms.installments.activate');

        Route::get('pengguna', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('pengguna/{user}', [Admin\UserController::class, 'show'])->name('users.show');

        /*
        | Activity Logs: jejak audit seluruh aktivitas penting pelanggan dan
        | pengelola.
        |
        | Hanya dua route baca. Jejak audit tidak menyediakan penyuntingan
        | maupun penghapusan dengan sengaja — riwayat yang dapat diubah tidak
        | lagi dapat dijadikan bukti.
        */
        Route::get('activity-logs', [Admin\ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{activityLog}', [Admin\ActivityLogController::class, 'show'])->name('activity-logs.show');

        Route::get('notifikasi', [Admin\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifikasi/terbaru', [Admin\NotificationController::class, 'latest'])->name('notifications.latest');
        Route::post('notifikasi/baca-semua', [Admin\NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('notifikasi/{notification}/baca', [Admin\NotificationController::class, 'read'])->name('notifications.read');

        Route::get('profil', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profil', [Admin\ProfileController::class, 'update'])->name('profile.update');

        Route::get('ganti-password', [Auth\PasswordController::class, 'edit'])->name('password.edit');
        Route::put('ganti-password', [Auth\PasswordController::class, 'update'])->name('password.update');
    });
});
