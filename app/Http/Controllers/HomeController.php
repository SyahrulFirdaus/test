<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Service;
use App\Models\Technology;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Halaman utama: hero, logo klien, profil singkat, sorotan layanan &
     * teknologi, alur kerja, testimoni, CTA.
     */
    public function index(): View
    {
        return view('pages.home', [
            'services' => Service::query()->active()->ordered()->get(),
            'technologies' => Technology::query()->active()->ordered()->get(),
            'clients' => Client::query()->active()->ordered()->get(),
            'testimonials' => Testimonial::query()->active()->ordered()->get(),
        ]);
    }
}
