<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use App\Models\Service;
use App\Models\User;
use App\Support\AdminPermission;
use App\Support\AdminPresence;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Profil perusahaan dipakai di navbar, footer, dan skema SEO pada
        // setiap halaman. Di-resolve sekali per request lewat singleton.
        $this->app->singleton(CompanyProfile::class, fn () => CompanyProfile::current() ?? $this->fallbackProfile());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $view->with('company', $this->app->make(CompanyProfile::class));
        });

        // Footer menampilkan daftar layanan di setiap halaman.
        View::composer('partials.footer', function ($view) {
            $view->with('footerServices', Service::query()->active()->ordered()->get(['slug', 'title']));
        });

        // Satu Gate per hak akses Admin (`quotation.view`, `quotation.delete`,
        // …) sehingga route, controller, dan Blade (`@can`) memakai
        // pemeriksaan yang sama. Aturannya hanya ada di
        // User::hasAdminPermission(): Superadmin selalu lolos. Sengaja tidak
        // memakai Gate::before agar Gate/Policy lain tidak ikut terpengaruh.
        foreach (AdminPermission::keys() as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasAdminPermission($permission));
        }

        // Status Login Admin pada menu Akun Admin, dicatat langsung dari
        // event autentikasi yang sudah ada. Lihat App\Support\AdminPresence.
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User && AdminPresence::tracks($event->user)) {
                AdminPresence::markOnline($event->user);
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user instanceof User && AdminPresence::tracks($event->user)) {
                AdminPresence::markOffline($event->user);
            }
        });

        // Gambar CAPTCHA pendaftaran punya hitungan sendiri. `throttle:X,Y`
        // biasa menghitung SELURUH route ber-throttle milik satu IP bersama,
        // jadi memuat ulang kode tidak boleh menghabiskan jatah Daftar.
        RateLimiter::for('register-captcha', fn (Request $request) => Limit::perMinute(30)->by('register-captcha|'.$request->ip()));

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Nilai cadangan bila tabel company_profiles belum di-seed,
     * supaya halaman tetap dapat dirender tanpa error.
     */
    private function fallbackProfile(): CompanyProfile
    {
        return new CompanyProfile([
            'name' => config('app.name'),
            'tagline' => 'Additive Manufacturing Solutions',
            'short_description' => '',
            'about' => '',
            'vision' => '',
            'missions' => [],
            'advantages' => [],
            'stats' => [],
            'address' => '',
            'socials' => [],
        ]);
    }
}
