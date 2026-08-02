<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModelCheckController;
use App\Http\Controllers\QuotationRequestController;
use App\Http\Controllers\QuotationTrackingController;
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

    Route::get('/register', [Auth\RegisteredUserController::class, 'create'])->name('register');
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

        Route::get('notifikasi', [Dashboard\NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifikasi/terbaru', [Dashboard\NotificationController::class, 'latest'])->name('notifications.latest');
        Route::post('notifikasi/baca-semua', [Dashboard\NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('notifikasi/{notification}/baca', [Dashboard\NotificationController::class, 'read'])->name('notifications.read');

        Route::get('profil', [Dashboard\ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profil', [Dashboard\ProfileController::class, 'update'])->name('profile.update');

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

        Route::get('pengguna', [Admin\UserController::class, 'index'])->name('users.index');
        Route::get('pengguna/{user}', [Admin\UserController::class, 'show'])->name('users.show');

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
