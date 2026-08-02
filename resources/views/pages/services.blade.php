@extends('layouts.app')

@section('title', 'Services')
@section('description', 'Enam layanan 3D printing terpadu: 3D Printing Services, Product Development, Reverse Engineering, 3D Design, 3D Scanning, serta Paint & Finishing.')

@section('content')

    <x-page-hero
        eyebrow="Layanan"
        current="Services"
        title='Layanan lengkap dari <span class="text-brand-400">file digital</span> sampai part siap pakai'
        description="Setiap layanan dapat berdiri sendiri maupun dirangkai menjadi satu alur kerja utuh — mulai dari memindai objek nyata, menyempurnakan desainnya, mencetaknya, hingga memberi sentuhan akhir.">
        <x-slot:actions>
            <a href="#daftar-layanan" class="btn-primary w-full sm:w-auto">
                Lihat Semua Layanan
                <x-icons.arrow-down class="h-4 w-4" />
            </a>
            <a href="{{ route('about') }}#kontak" class="btn-ghost-light w-full sm:w-auto">Konsultasi Gratis</a>
        </x-slot:actions>
    </x-page-hero>

    {{-- ===================== GRID LAYANAN ===================== --}}
    <section id="daftar-layanan" class="section scroll-mt-24">
        <div class="container-page">
            <x-section-heading
                eyebrow="Daftar Layanan"
                title='Enam layanan yang saling melengkapi'
                description="Klik salah satu layanan untuk membaca uraian lengkapnya." />

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <x-service-card
                        :service="$service"
                        :delay="($loop->index % 3) * 80"
                        :href="'#'.$service->slug" />
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== URAIAN TIAP LAYANAN ===================== --}}
    <section class="section bg-ink-50/70">
        <div class="container-page space-y-20 md:space-y-28">
            @foreach ($services as $service)
                @php $reverse = $loop->odd; @endphp

                <article id="{{ $service->slug }}" class="scroll-mt-28">
                    <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                        <div class="{{ $reverse ? 'lg:order-2' : '' }}" data-aos="{{ $reverse ? 'fade-left' : 'fade-right' }}">
                            <div class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                                <img src="{{ asset($service->image) }}"
                                     alt="Ilustrasi layanan {{ $service->title }}"
                                     loading="lazy"
                                     decoding="async"
                                     width="640" height="400"
                                     class="aspect-[16/10] w-full object-cover">
                            </div>
                        </div>

                        <div class="{{ $reverse ? 'lg:order-1' : '' }}" data-aos="{{ $reverse ? 'fade-right' : 'fade-left' }}">
                            <div class="flex items-center gap-4">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-[0_14px_30px_-14px_rgba(149,39,29,0.9)]">
                                    <x-dynamic-component :component="'icons.'.$service->icon" class="h-7 w-7" />
                                </span>
                                <span class="font-display text-sm font-bold uppercase tracking-[0.16em] text-brand-600">
                                    0{{ $loop->iteration }} &mdash; Layanan
                                </span>
                            </div>

                            <h2 class="mt-6 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $service->title }}</h2>
                            <p class="mt-2 text-base font-semibold text-brand-600">{{ $service->tagline }}</p>

                            <p class="mt-6 text-base leading-relaxed text-ink-600">{{ $service->description }}</p>

                            @if (filled($service->highlights))
                                <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                                    @foreach ($service->highlights as $highlight)
                                        <li class="flex items-start gap-2.5 rounded-xl border border-ink-100 bg-white p-4 text-sm font-medium text-ink-700">
                                            <x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                                            <span>{{ $highlight }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <a href="{{ route('about') }}#kontak" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition-colors hover:text-brand-800">
                                Diskusikan kebutuhan {{ $service->title }}
                                <x-icons.arrow-right class="h-4 w-4" />
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ===================== FORMAT FILE ===================== --}}
    <section class="section">
        <div class="container-page">
            <div class="grid items-center gap-10 rounded-3xl border border-ink-100 bg-white p-8 shadow-card sm:p-12 lg:grid-cols-12" data-aos="fade-up">
                <div class="lg:col-span-5">
                    <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Format file yang kami terima</h2>
                    <p class="mt-4 text-base leading-relaxed text-ink-600">
                        Belum punya file 3D? Tidak masalah — kirimkan sketsa, foto, atau bahkan part fisiknya.
                        Tim kami akan membantu membuatkan modelnya melalui layanan 3D Design atau 3D Scanning.
                    </p>
                </div>

                <div class="lg:col-span-7">
                    <div class="flex flex-wrap gap-3">
                        @foreach (['STL', 'STEP', 'IGES', 'OBJ', 'SLDPRT', 'IPT', '3MF', 'DXF', 'PDF Drawing'] as $format)
                            <span class="rounded-xl border border-ink-200 bg-ink-50 px-4 py-2.5 text-sm font-semibold text-ink-700">
                                {{ $format }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-cta-band
        eyebrow="Siap Memulai"
        title="Kirim file Anda, kami tinjau hari ini juga"
        primary-label="Lihat Teknologi Cetak"
        :primary-href="route('technologies')" />

@endsection
