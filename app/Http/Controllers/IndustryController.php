<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Technology;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class IndustryController extends Controller
{
    /** Ringkasan seluruh industri yang dilayani. */
    public function index(): View
    {
        return view('pages.industries', [
            'industries' => $this->industries(),
            'process' => config('industry-guide.process'),
        ]);
    }

    /**
     * Halaman detail satu industri: tantangan & solusi, galeri contoh,
     * perbandingan material, finishing, alur kerja, klien, dan FAQ.
     */
    public function show(string $industry): View
    {
        $industries = $this->industries();
        $index = $industries->search(fn (array $item) => $item['slug'] === $industry);

        abort_if($index === false, 404);

        $current = $industries[$index] + config("industry-details.{$industry}", []);

        // Lama produksi diambil dari spesifikasi teknologi agar selalu sama
        // dengan yang tercantum di halaman Technologies.
        $technologies = Technology::query()
            ->active()
            ->whereIn('code', $current['technologies'])
            ->get()
            ->sortBy(fn (Technology $technology) => array_search($technology->code, $current['technologies'], true))
            ->values();

        $clients = empty($current['clients'])
            ? collect()
            : Client::query()->active()->ordered()->whereIn('slug', $current['clients'])->get();

        return view('pages.industry-show', [
            'industry' => $current,
            'industries' => $industries,
            'previous' => $industries[($index - 1 + $industries->count()) % $industries->count()],
            'next' => $industries[($index + 1) % $industries->count()],
            'technologies' => $technologies,
            'clients' => $clients,
            'materialCatalog' => config('industry-guide.materials'),
            'materialProperties' => config('industry-guide.material_properties'),
            'process' => config('industry-guide.process'),
        ]);
    }

    private function industries(): Collection
    {
        return collect(config('industries'))->values();
    }
}
