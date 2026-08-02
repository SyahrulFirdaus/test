<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Technology;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** Sitemap XML sederhana untuk keempat halaman utama. */
    public function __invoke(): Response
    {
        $lastModified = collect([
            Service::query()->max('updated_at'),
            Technology::query()->max('updated_at'),
        ])->filter()->max();

        $pages = [
            ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'monthly'],
            ['url' => route('services'), 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['url' => route('technologies'), 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['url' => route('models'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['url' => route('about'), 'priority' => '0.7', 'changefreq' => 'yearly'],
            // Hanya formulir pencariannya yang diindeks; halaman tracking milik
            // masing-masing pelanggan sengaja ditandai noindex.
            ['url' => route('tracking.index'), 'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        return response()
            ->view('sitemap', [
                'pages' => $pages,
                'lastModified' => $lastModified ? date('Y-m-d', strtotime($lastModified)) : date('Y-m-d'),
            ])
            ->header('Content-Type', 'application/xml');
    }
}
