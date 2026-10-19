@extends('layouts.app')

@section('title', 'Industri')
@section('og-image', 'industries')
@section('description', 'Solusi 3D printing untuk berbagai industri: otomotif, aerospace & drone, manufaktur & robotika, energi & migas, elektronik & IoT, arsitektur, desain produk, mainan, dan lainnya.')

@push('head')
    {{-- Tautan lama /industri#slug kini punya halaman sendiri. --}}
    <script>
        (function () {
            var slugs = @json($industries->pluck('slug'));
            var hash = window.location.hash.slice(1);

            if (slugs.indexOf(hash) !== -1) {
                window.location.replace(@json(url('industri')) + '/' + hash);
            }
        })();
    </script>
@endpush

@section('content')

    <x-page-hero
        eyebrow="Industri"
        current="Industri"
        title='Solusi 3D printing untuk <span class="text-brand-400">setiap industri</span>'
        description="Dari prototipe hingga part fungsional, kami membantu berbagai sektor mempercepat pengembangan produk, memangkas biaya tooling, dan menjaga lini produksi tetap berjalan.">
        <x-slot:actions>
            <a href="#daftar-industri" class="btn-primary w-full sm:w-auto">
                Jelajahi Industri
                <x-icons.arrow-down class="h-4 w-4" />
            </a>
            <a href="{{ route('technologies') }}" class="btn-ghost-light w-full sm:w-auto">Teknologi Kami</a>
        </x-slot:actions>
    </x-page-hero>

    {{-- ===================== DAFTAR INDUSTRI ===================== --}}
    <section id="daftar-industri" class="section scroll-mt-24">
        <div class="container-page">
            <x-section-heading
                eyebrow="Berdasarkan Industri"
                title="Industri yang kami layani"
                description="Pilih industri Anda untuk melihat tantangan yang kami selesaikan, contoh hasil, rekomendasi material, hingga pertanyaan yang sering diajukan." />

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($industries as $industry)
                    @php
                        $heroModel = config("industry-details.{$industry['slug']}.hero_model")
                            ?? ($industry['examples'][0]['model'] ?? 'gear');
                        // File STL/OBJ asli bila sudah diunggah (App\Support\IndustryModel).
                        $heroFile = \App\Support\IndustryModel::forIndustry($industry);
                        // Foto hasil cetak asli; bila ada, menggantikan model 3D.
                        $heroPhoto = \App\Support\IndustryModel::photoForIndustry($industry);
                        // Kredit foto berlisensi bebas (config/industry-photos.php).
                        $photoCredit = $heroPhoto ? config("industry-photos.{$industry['slug']}") : null;
                        $photoFit = $photoCredit['fit'] ?? 'cover';
                    @endphp

                    <a href="{{ route('industries.show', $industry['slug']) }}"
                       class="group flex flex-col overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-card-hover"
                       data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 60 }}">
                        {{-- Part khas industri, dirender 3D (industry-gallery.js). --}}
                        <div class="relative aspect-[4/3] overflow-hidden bg-[radial-gradient(ellipse_at_50%_40%,var(--color-brand-500)_0%,var(--color-brand-700)_55%,var(--color-brand-900)_100%)]"
                             data-industry-card>
                            <div class="blueprint-grid-dark absolute inset-0 opacity-30" aria-hidden="true"></div>
                            @if ($heroPhoto)
                                {{-- `fit` => 'contain' (di config/industry-photos.php) menampilkan
                                     foto utuh di atas latar putih, cocok untuk foto produk tegak;
                                     selain itu foto memenuhi kartu. --}}
                                <div class="absolute inset-0 bg-white"></div>
                                <img src="{{ $heroPhoto }}"
                                     alt="{{ $photoCredit['title'] ?? 'Contoh hasil cetak 3D untuk industri '.$industry['name'] }}"
                                     loading="lazy"
                                     class="absolute inset-0 h-full w-full transition-transform duration-500 group-hover:scale-105 {{ $photoFit === 'contain' ? 'object-contain p-3' : 'object-cover' }}">

                                @if ($photoCredit)
                                    <span class="absolute bottom-2 right-2 max-w-[85%] truncate rounded bg-ink-950/60 px-1.5 py-0.5 text-[0.6rem] font-medium text-white/90 backdrop-blur">
                                        Foto: {{ $photoCredit['author'] }} · {{ $photoCredit['license'] }}
                                    </span>
                                @endif
                            @else
                                <canvas class="absolute inset-0 h-full w-full"
                                        data-industry-part="{{ $heroModel }}"
                                        @if ($heroFile) data-industry-file="{{ $heroFile }}" @endif
                                        data-industry-framing="center"
                                        role="img"
                                        aria-label="Model 3D contoh part industri {{ $industry['name'] }}"></canvas>
                            @endif
                            <span class="absolute left-4 top-4 inline-flex h-9 w-9 items-center justify-center rounded-lg text-white {{ $heroPhoto ? 'bg-brand-600' : 'bg-white/15 backdrop-blur' }}">
                                <x-dynamic-component :component="'icons.'.$industry['icon']" class="h-4 w-4" />
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col p-6">
                            <h3 class="font-display text-lg font-bold leading-snug text-ink-900">{{ $industry['name'] }}</h3>
                            <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-500">{{ $industry['summary'] }}</p>

                            <div class="mt-5 flex flex-wrap gap-1.5">
                                @foreach ($industry['technologies'] as $technology)
                                    <span class="inline-flex items-center rounded-full bg-ink-50 px-2.5 py-0.5 font-display text-[0.7rem] font-bold text-ink-600">{{ $technology }}</span>
                                @endforeach
                            </div>

                            <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">
                                Lihat detail industri
                                <x-icons.arrow-right class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== ALUR KERJA ===================== --}}
    <section class="section bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Alur Kerja"
                title="Dari file hingga part di tangan Anda"
                description="Alur yang sama berlaku untuk semua industri. Lama produksi bergantung pada teknologi yang dipakai." />

            <div class="mt-12">
                @include('partials.industry-process', ['process' => $process, 'technologies' => collect()])
            </div>
        </div>
    </section>

    {{-- Tujuan tautan "dan lainnya" pada menu Industri di navbar. --}}
    <section id="lainnya" class="section scroll-mt-32">
        <div class="container-page">
            <div class="rounded-3xl border border-dashed border-ink-200 bg-white p-8 text-center sm:p-12" data-aos="fade-up">
                <h2 class="text-2xl font-bold tracking-tight text-ink-900">Industri Anda tidak ada di daftar?</h2>
                <p class="mx-auto mt-3 max-w-2xl text-base leading-relaxed text-ink-500">
                    Daftar di atas hanyalah sebagian. Selama part Anda dapat dimodelkan dalam 3D, kemungkinan besar kami dapat membantu mencetaknya. Ceritakan kebutuhan Anda dan tim engineer kami akan memberikan rekomendasi.
                </p>
                <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('models') }}" class="btn-primary">Unggah File 3D</a>
                    <a href="{{ route('about') }}#kontak" class="btn-outline">Hubungi Tim Kami</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Kredit foto: wajib untuk lisensi CC BY / CC BY-SA. --}}
    @php $credits = collect(config('industry-photos'))->only($industries->pluck('slug')); @endphp
    @if ($credits->isNotEmpty())
        <section class="pb-4">
            <div class="container-page">
                <details class="rounded-2xl border border-ink-100 bg-ink-50/60 px-5 py-4 text-xs text-ink-500">
                    <summary class="cursor-pointer font-semibold text-ink-600">Kredit foto</summary>
                    <p class="mt-3">Foto ilustrasi pada kartu industri berasal dari Wikimedia Commons dan digunakan sesuai lisensinya; bukan hasil pengerjaan {{ $company->name }}.</p>
                    <ul class="mt-3 space-y-1.5">
                        @foreach ($credits as $slug => $credit)
                            <li>
                                <a href="{{ $credit['source_url'] }}" target="_blank" rel="noopener noreferrer" class="font-medium text-ink-700 underline decoration-ink-300 underline-offset-2 hover:text-brand-600">{{ $credit['title'] }}</a>
                                oleh {{ $credit['author'] }},
                                @if ($credit['license_url'])
                                    <a href="{{ $credit['license_url'] }}" target="_blank" rel="noopener noreferrer license" class="underline decoration-ink-300 underline-offset-2 hover:text-brand-600">{{ $credit['license'] }}</a>
                                @else
                                    {{ $credit['license'] }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </details>
            </div>
        </section>
    @endif

    <x-cta-band
        eyebrow="Konsultasi Gratis"
        title="Butuh solusi untuk industri Anda?"
        description="Kirimkan file dan ceritakan fungsi part yang Anda butuhkan. Kami akan merekomendasikan teknologi serta material yang paling sesuai dengan anggaran dan tenggat Anda." />

@endsection

@push('scripts')
    {{-- Model 3D pada kartu ringkasan industri. --}}
    @vite('resources/js/industry-gallery.js')
@endpush
