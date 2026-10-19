<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Contracts\View\View;

/**
 * Landing page Miniatur Teknik & Souvenir Korporat (/miniatur-teknik).
 * Isi halaman di config/miniatur.php; formulirnya masuk ke Lead Management
 * lewat route `leads.store`.
 */
class MiniaturController extends Controller
{
    public function show(): View
    {
        return view('pages.miniatur', [
            'gallery' => $this->gallery(),
            'clients' => Client::query()->active()->ordered()->get(),
        ]);
    }

    /**
     * Foto per kategori: foto proyek asli di public/images/miniatur/{slug}.*
     * bila ada, selain itu foto ilustrasi berlisensi beserta kreditnya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function gallery(): array
    {
        $credits = config('industry-photos');

        return collect(config('miniatur.gallery'))
            ->map(function (array $item, string $slug) use ($credits) {
                foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
                    if (is_file(public_path("images/miniatur/{$slug}.{$extension}"))) {
                        return $item + ['slug' => $slug, 'image' => "images/miniatur/{$slug}.{$extension}", 'credit' => null, 'fit' => 'cover'];
                    }
                }

                $illustration = $item['illustration'];
                $image = collect(['jpg', 'png'])
                    ->map(fn ($extension) => "images/industries/{$illustration}/utama.{$extension}")
                    ->first(fn ($path) => is_file(public_path($path)));
                $credit = $credits[$illustration] ?? null;

                return $item + [
                    'slug' => $slug,
                    'image' => $image,
                    'credit' => $credit,
                    'fit' => $credit['fit'] ?? 'cover',
                ];
            })
            ->values()
            ->all();
    }
}
