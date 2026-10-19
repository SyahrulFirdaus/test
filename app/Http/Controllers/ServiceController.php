<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Service;
use App\Support\ServiceFaqs;
use App\Support\ServicePages;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class ServiceController extends Controller
{
    /** Daftar seluruh layanan beserta uraian lengkapnya. */
    public function index(): View
    {
        return view('pages.services', [
            'services' => Service::query()->active()->ordered()->get(),
            // slug layanan di database → URL landing page-nya.
            'landingUrls' => ServicePages::urls(),
        ]);
    }

    /**
     * Landing page satu layanan (/services/{page}), untuk tujuan iklan & SEO.
     * Elemen halaman mengikuti sheet "Landing Page Layanan".
     */
    public function show(string $page): View
    {
        $config = config("service-pages.pages.{$page}");

        abort_if($config === null, 404);

        $service = Service::query()->active()->where('slug', $config['service'])->firstOrFail();

        return view('pages.service-show', [
            'page' => $page,
            'landing' => $config,
            'service' => $service,
            'clients' => Client::query()->active()->ordered()->get(),
            'gallery' => $this->gallery($page),
            'faqs' => ServiceFaqs::for($page),
            'otherServices' => Service::query()->active()->ordered()->where('id', '!=', $service->id)->get(),
            'landingUrls' => ServicePages::urls(),
        ]);
    }

    /**
     * Foto hasil kerja nyata di public/images/services/{page}/ (maks. 6).
     * Kosong = bagian galeri tidak ditampilkan.
     *
     * @return array<int, string>
     */
    private function gallery(string $page): array
    {
        $directory = public_path("images/services/{$page}");

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
            // Versi .webp hasil optimasi tidak ditampilkan dua kali.
            ->reject(fn ($file) => strtolower($file->getExtension()) === 'webp'
                && collect(['jpg', 'jpeg', 'png'])->contains(fn ($ext) => File::exists(substr($file->getPathname(), 0, -4).$ext)))
            ->sortBy(fn ($file) => $file->getFilename())
            ->take(6)
            ->map(fn ($file) => "images/services/{$page}/".$file->getFilename())
            ->values()
            ->all();
    }
}
