<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use App\Models\Service;
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
