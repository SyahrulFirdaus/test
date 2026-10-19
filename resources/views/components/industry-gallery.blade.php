{{--
    Galeri "Contoh Hasilnya" bertema merah untuk halaman industri.

    Setiap kartu menampilkan part 3D realistis yang dirender di browser
    (resources/js/industry-gallery.js). Kartu dapat diklik untuk membuka model
    yang bisa diputar dan di-zoom. Urutan sumber gambarnya:
    1. foto asli di public/<image> → ditampilkan apa adanya, tanpa viewer;
    2. file STL/OBJ asli di public/models/industries/{slug}/… (lihat
       App\Support\IndustryModel) → dirender 3D;
    3. model buatan program (`model`).
--}}
@props([
    'examples' => [],
    'slug' => null,
    'title' => 'Contoh Hasilnya',
])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 sm:p-10']) }}>
    <div class="blueprint-grid-dark absolute inset-0 opacity-40" aria-hidden="true"></div>

    <div class="relative flex flex-wrap items-end justify-between gap-3">
        <h2 class="font-mono text-xs font-semibold uppercase tracking-[0.24em] text-brand-100">{{ $title }}</h2>
        <p class="text-xs text-brand-100/80">Klik kartu untuk memutar model 3D</p>
    </div>

    <ul class="relative mt-6 grid gap-px overflow-hidden rounded-xl border border-white/15 bg-white/15 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($examples as $example)
            @php
                $hasImage = filled($example['image'] ?? null) && file_exists(public_path($example['image']));
                $file = $slug && ! $hasImage ? \App\Support\IndustryModel::forExample($slug, $example) : null;
            @endphp

            <li class="group relative aspect-[4/3] overflow-hidden bg-[radial-gradient(ellipse_at_50%_35%,var(--color-brand-500)_0%,var(--color-brand-700)_55%,var(--color-brand-900)_100%)]"
                data-industry-card>
                @if ($hasImage)
                    <img src="{{ asset($example['image']) }}"
                         alt="{{ $example['title'] }}"
                         loading="lazy"
                         class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                @else
                    <canvas class="absolute inset-0 h-full w-full"
                            data-industry-part="{{ $example['model'] ?? 'gear' }}"
                            @if ($file) data-industry-file="{{ $file }}" @endif
                            role="img"
                            aria-label="Model 3D: {{ $example['title'] }}"></canvas>

                    <button type="button"
                            class="absolute inset-0 z-10 cursor-zoom-in focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-white"
                            data-industry-open="{{ $example['model'] ?? 'gear' }}"
                            @if ($file) data-industry-file="{{ $file }}" @endif
                            data-industry-title="{{ $example['title'] }}"
                            aria-label="Putar model 3D: {{ $example['title'] }}">
                        <span class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[0.65rem] font-semibold uppercase tracking-wider text-white opacity-0 backdrop-blur transition-opacity duration-300 group-hover:opacity-100 group-focus-within:opacity-100">
                            <x-icons.orbit class="h-3.5 w-3.5" />
                            Putar 3D
                        </span>
                    </button>
                @endif

                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-brand-950/85 to-transparent" aria-hidden="true"></div>

                <p class="pointer-events-none absolute inset-x-0 bottom-4 border-l-2 border-white/60 py-0.5 pl-5 pr-4 font-display text-[0.95rem] font-semibold leading-snug text-white drop-shadow sm:text-base">
                    {{ $example['title'] }}
                </p>
            </li>
        @endforeach
    </ul>
</div>

@once
    {{-- Viewer 3D layar penuh; diisi oleh industry-gallery.js saat kartu diklik. --}}
    <dialog class="industry-viewer m-auto w-[min(56rem,calc(100vw-2rem))] overflow-hidden rounded-3xl bg-transparent p-0 backdrop:bg-ink-950/75 backdrop:backdrop-blur-sm"
            data-industry-viewer
            aria-label="Viewer model 3D">
        <div class="relative aspect-[4/3] w-full bg-[radial-gradient(ellipse_at_50%_35%,var(--color-brand-500)_0%,var(--color-brand-700)_55%,var(--color-brand-900)_100%)] sm:aspect-[16/10]">
            <canvas class="absolute inset-0 h-full w-full cursor-grab touch-none active:cursor-grabbing" data-industry-viewer-canvas></canvas>

            <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between gap-4 p-5 sm:p-6">
                <p class="max-w-[75%] border-l-2 border-white/60 pl-4 font-display text-lg font-semibold leading-snug text-white drop-shadow sm:text-xl" data-industry-viewer-title></p>
            </div>

            <button type="button"
                    class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition-colors hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-white"
                    data-industry-viewer-close
                    aria-label="Tutup viewer">
                <x-icons.close class="h-5 w-5" />
            </button>

            <p class="pointer-events-none absolute inset-x-0 bottom-4 text-center text-xs font-medium text-white/75">
                Seret untuk memutar · scroll atau cubit untuk zoom
            </p>
        </div>
    </dialog>
@endonce
