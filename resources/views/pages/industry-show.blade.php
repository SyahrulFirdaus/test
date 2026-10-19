@extends('layouts.app')

@section('title', $industry['name'])
@section('og-image', 'industries')
@section('description', $industry['summary'].' '.Str::limit($industry['description'], 110))

{{-- Teks WhatsApp otomatis halaman ini (App\Support\WhatsAppLink). --}}
@section('wa-topic', '3D printing untuk industri '.$industry['name'])

@php
    $consultHref = wa_link(\App\Support\WhatsAppLink::message('3D printing untuk industri '.$industry['name']));

    $sections = collect([
        ['id' => 'ringkasan', 'label' => 'Ringkasan', 'show' => true],
        ['id' => 'tantangan', 'label' => 'Tantangan & Solusi', 'show' => ! empty($industry['challenges'])],
        ['id' => 'contoh', 'label' => 'Contoh Hasil', 'show' => ! empty($industry['examples'])],
        ['id' => 'studi-kasus', 'label' => 'Studi Kasus', 'show' => ! empty($industry['case_studies'])],
        ['id' => 'material', 'label' => 'Material', 'show' => ! empty($industry['materials'])],
        ['id' => 'finishing', 'label' => 'Finishing', 'show' => ! empty($industry['finishing'])],
        ['id' => 'alur-kerja', 'label' => 'Alur Kerja', 'show' => true],
        ['id' => 'klien', 'label' => 'Klien', 'show' => $clients->isNotEmpty()],
        ['id' => 'faq', 'label' => 'FAQ', 'show' => ! empty($industry['faqs'])],
    ])->where('show', true);
@endphp

@push('head')
    @if (! empty($industry['faqs']))
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => collect($industry['faqs'])->map(fn (array $faq) => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                ])->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    @endif
@endpush

@section('quote-href', '#form-kontak')

@section('content')

    <x-page-hero
        eyebrow="Industri"
        :current="$industry['name']"
        :title="e($industry['name'])"
        :description="$industry['summary']">
        <x-slot:actions>
            <a href="{{ $consultHref }}" data-cta="content"
               @if ($company->whatsapp_link) target="_blank" rel="noopener noreferrer" @endif
               class="btn-primary w-full sm:w-auto">
                <x-icons.whatsapp class="h-4 w-4" />
                Konsultasi via WhatsApp
            </a>
            <a href="{{ route('models') }}" class="btn-ghost-light w-full sm:w-auto">Unggah File 3D</a>
        </x-slot:actions>

        {{-- Part khas industri, dirender oleh industry-gallery.js. --}}
        <x-slot:visual>
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_55%,rgba(199,77,56,0.35)_0%,rgba(149,39,29,0.12)_38%,transparent_68%)]"></div>
            <canvas class="absolute inset-0 h-full w-full opacity-0 transition-opacity duration-700 [&.is-ready]:opacity-100"
                    data-industry-hero="{{ $industry['hero_model'] ?? $industry['examples'][0]['model'] ?? 'gear' }}"
                    @if ($heroFile = \App\Support\IndustryModel::forIndustry($industry)) data-industry-file="{{ $heroFile }}" @endif></canvas>
        </x-slot:visual>
    </x-page-hero>

    <div class="container-page py-12 md:py-16">
        {{-- Breadcrumb lengkap: hero hanya memuat Home / halaman ini. --}}
        <nav aria-label="Lokasi halaman" class="mb-8 text-xs font-medium text-ink-400">
            <a href="{{ route('industries') }}" class="inline-flex items-center gap-1.5 transition-colors hover:text-brand-600">
                <x-icons.arrow-right class="h-3.5 w-3.5 rotate-180" />
                Semua industri
            </a>
        </nav>

        {{-- Navigasi bagian untuk layar kecil. --}}
        <nav aria-label="Bagian halaman" class="-mx-5 mb-10 overflow-x-auto px-5 lg:hidden">
            <ul class="flex w-max gap-2">
                @foreach ($sections as $section)
                    <li>
                        <a href="#{{ $section['id'] }}" class="inline-flex rounded-full border border-ink-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-ink-600">{{ $section['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12 xl:grid-cols-[16rem_minmax(0,1fr)]">

            {{-- ===================== SIDEBAR STICKY ===================== --}}
            <aside class="hidden lg:block">
                <div class="sticky top-36 space-y-8" data-industry-toc>
                    <nav aria-label="Daftar isi">
                        <p class="font-mono text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-ink-400">Di halaman ini</p>
                        <ul class="mt-3 space-y-0.5 border-l border-ink-100">
                            @foreach ($sections as $section)
                                <li>
                                    <a href="#{{ $section['id'] }}"
                                       class="industry-toc-link"
                                       data-toc-link="{{ $section['id'] }}">{{ $section['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>

                    <nav aria-label="Industri lain">
                        <p class="font-mono text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-ink-400">Industri lain</p>
                        <ul class="mt-3 space-y-0.5">
                            @foreach ($industries as $item)
                                <li>
                                    <a href="{{ route('industries.show', $item['slug']) }}"
                                       class="flex items-start gap-2.5 rounded-lg px-2 py-1.5 text-[0.8rem] leading-snug transition-colors {{ $item['slug'] === $industry['slug'] ? 'bg-brand-50 font-semibold text-brand-700' : 'text-ink-500 hover:bg-ink-50 hover:text-ink-800' }}"
                                       @if ($item['slug'] === $industry['slug']) aria-current="page" @endif>
                                        <x-dynamic-component :component="'icons.'.$item['icon']" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                        {{ $item['name'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </div>
            </aside>

            {{-- ===================== KONTEN ===================== --}}
            <div class="min-w-0 space-y-20 md:space-y-24">

                {{-- Ringkasan --}}
                <section id="ringkasan" class="scroll-mt-36">
                    <div class="grid gap-8 xl:grid-cols-5 xl:gap-10">
                        <div class="xl:col-span-3">
                            <span class="eyebrow">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                Ringkasan
                            </span>
                            <h2 class="mt-5 text-2xl font-bold leading-tight tracking-tight text-ink-900 sm:text-3xl">Bagaimana kami membantu industri ini</h2>
                            <p class="mt-4 text-base leading-relaxed text-ink-500">{{ $industry['description'] }}</p>

                            <h3 class="mt-8 font-display text-sm font-semibold uppercase tracking-[0.14em] text-ink-400">Contoh Aplikasi</h3>
                            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($industry['applications'] as $application)
                                    <li class="flex items-start gap-2.5 text-sm text-ink-700">
                                        <x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                                        {{ $application }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="space-y-6 self-start rounded-2xl bg-ink-50/80 p-6 xl:col-span-2">
                            <div>
                                <h3 class="font-display text-sm font-semibold uppercase tracking-[0.14em] text-ink-400">Teknologi yang Direkomendasikan</h3>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($industry['technologies'] as $technology)
                                        <a href="{{ route('technologies') }}" class="inline-flex items-center rounded-full bg-brand-600 px-3 py-1 font-display text-xs font-bold text-white transition-colors hover:bg-brand-700">{{ $technology }}</a>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <h3 class="font-display text-sm font-semibold uppercase tracking-[0.14em] text-ink-400">Material Populer</h3>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($industry['materials'] as $material)
                                        <span class="inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1 text-xs font-semibold text-ink-700">{{ $material }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <a href="{{ $consultHref }}" data-cta="content"
                               @if ($company->whatsapp_link) target="_blank" rel="noopener noreferrer" @endif
                               class="btn-primary w-full">
                                <x-icons.whatsapp class="h-4 w-4" />
                                Konsultasi Gratis
                            </a>
                        </div>
                    </div>

                    {{-- Angka hasil nyata; hanya tampil bila diisi di config. --}}
                    @if (! empty($industry['stats']))
                        <dl class="mt-10 grid gap-4 sm:grid-cols-3">
                            @foreach ($industry['stats'] as $stat)
                                <div class="flex flex-col rounded-2xl border border-ink-100 bg-white p-5 text-center shadow-card">
                                    <dt class="order-2 mt-1 text-sm text-ink-500">{{ $stat['label'] }}</dt>
                                    <dd class="font-display text-3xl font-bold text-brand-600">{{ $stat['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </section>

                {{-- Tantangan & solusi --}}
                @if (! empty($industry['challenges']))
                    <section id="tantangan" class="scroll-mt-36">
                        <x-section-heading align="left" eyebrow="Tantangan & Solusi" title="Masalah yang sering kami selesaikan" />

                        <div class="mt-10 space-y-4">
                            @foreach ($industry['challenges'] as $challenge)
                                <div class="grid overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card md:grid-cols-2" data-aos="fade-up">
                                    <div class="flex gap-4 border-b border-ink-100 p-6 md:border-b-0 md:border-r">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-500">
                                            <x-icons.alert class="h-4 w-4" />
                                        </span>
                                        <div>
                                            <p class="font-mono text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-ink-400">Tantangan</p>
                                            <p class="mt-1.5 text-sm font-semibold leading-relaxed text-ink-800">{{ $challenge['problem'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-4 bg-brand-50/50 p-6">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                                            <x-icons.check class="h-4 w-4" />
                                        </span>
                                        <div>
                                            <p class="font-mono text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-brand-600">Solusi</p>
                                            <p class="mt-1.5 text-sm leading-relaxed text-ink-700">{{ $challenge['solution'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Contoh hasil (galeri 3D) --}}
                @if (! empty($industry['examples']))
                    <section id="contoh" class="scroll-mt-36">
                        <x-industry-gallery :examples="$industry['examples']" :slug="$industry['slug']" />
                    </section>
                @endif

                {{-- Studi kasus nyata; hanya tampil bila diisi di config. --}}
                @if (! empty($industry['case_studies']))
                    <section id="studi-kasus" class="scroll-mt-36">
                        <x-section-heading align="left" eyebrow="Studi Kasus" title="Proyek yang pernah kami kerjakan" />

                        <div class="mt-10 grid gap-5 sm:grid-cols-2">
                            @foreach ($industry['case_studies'] as $case)
                                <article class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                                    @if (! empty($case['image']) && file_exists(public_path($case['image'])))
                                        <img src="{{ asset($case['image']) }}" alt="{{ $case['title'] }}" loading="lazy" class="aspect-[16/9] w-full object-cover">
                                    @endif
                                    <div class="p-6">
                                        <h3 class="font-display text-lg font-bold text-ink-900">{{ $case['title'] }}</h3>
                                        <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $case['summary'] }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Perbandingan material --}}
                @if (! empty($industry['materials']))
                    <section id="material" class="scroll-mt-36">
                        <x-section-heading align="left"
                            eyebrow="Material"
                            title="Perbandingan material untuk industri ini"
                            description="Nilai bersifat relatif antar-material untuk membantu mempersempit pilihan. Tim kami akan mengonfirmasi material yang paling tepat setelah meninjau fungsi part Anda." />

                        <div class="mt-10 overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-card">
                            <table class="w-full min-w-[760px] text-left text-sm">
                                <caption class="sr-only">Perbandingan material untuk {{ $industry['name'] }}</caption>
                                <thead>
                                    <tr class="border-b border-ink-100 bg-ink-50/80">
                                        <th scope="col" class="px-5 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Material</th>
                                        @foreach ($materialProperties as $label)
                                            <th scope="col" class="px-4 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">{{ $label }}</th>
                                        @endforeach
                                        <th scope="col" class="px-5 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Cocok Untuk</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-100">
                                    @foreach ($industry['materials'] as $material)
                                        @php $props = $materialCatalog[$material] ?? null; @endphp
                                        <tr class="align-top transition-colors hover:bg-brand-50/40">
                                            <th scope="row" class="px-5 py-4">
                                                <span class="block font-semibold text-ink-900">{{ $material }}</span>
                                                @if ($props)
                                                    <span class="mt-1 inline-flex items-center gap-1.5 text-xs text-ink-400">
                                                        <span class="rounded bg-ink-100 px-1.5 py-0.5 font-display font-bold text-ink-600">{{ $props['technology'] }}</span>
                                                        {{ $props['note'] ?? '' }}
                                                    </span>
                                                @endif
                                            </th>
                                            @foreach ($materialProperties as $key => $label)
                                                <td class="px-4 py-4">
                                                    @if ($props)
                                                        @php $level = $props[$key]; @endphp
                                                        <span class="inline-flex items-center gap-1" role="img" aria-label="{{ $label }}: {{ ['', 'rendah', 'sedang', 'tinggi'][$level] }}">
                                                            @for ($i = 1; $i <= 3; $i++)
                                                                <span class="h-2 w-5 rounded-full {{ $i <= $level ? 'bg-brand-600' : 'bg-ink-100' }}"></span>
                                                            @endfor
                                                        </span>
                                                        <span class="mt-1 block text-[0.7rem] text-ink-400">{{ ['', 'Rendah', 'Sedang', 'Tinggi'][$level] }}</span>
                                                    @else
                                                        <span class="text-ink-300">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="px-5 py-4 text-ink-600">{{ $industry['material_notes'][$material] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                {{-- Finishing --}}
                @if (! empty($industry['finishing']))
                    <section id="finishing" class="scroll-mt-36">
                        <x-section-heading align="left"
                            eyebrow="Finishing"
                            title="Pilihan finishing yang paling sering dipakai"
                            description="Semua finishing dikerjakan oleh tim Paint & Finishing kami, termasuk pencocokan warna RAL dan Pantone." />

                        <div class="mt-10 grid gap-4 sm:grid-cols-3">
                            @foreach ($industry['finishing'] as $finish)
                                <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card" data-aos="fade-up" data-aos-delay="{{ $loop->index * 60 }}">
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                                        <x-icons.brush class="h-5 w-5" />
                                    </span>
                                    <h3 class="mt-4 font-display text-base font-bold text-ink-900">{{ $finish['name'] }}</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $finish['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Alur kerja --}}
                <section id="alur-kerja" class="scroll-mt-36">
                    <x-section-heading align="left"
                        eyebrow="Alur Kerja"
                        title="Dari file hingga part di tangan Anda"
                        description="Perkiraan lama produksi di bawah ini sesuai teknologi yang direkomendasikan untuk industri ini." />

                    <div class="mt-10">
                        @include('partials.industry-process', ['process' => $process, 'technologies' => $technologies])
                    </div>
                </section>

                {{-- Klien dari sektor ini --}}
                @if ($clients->isNotEmpty())
                    <section id="klien" class="scroll-mt-36">
                        <x-section-heading align="left" eyebrow="Klien" title="Dipercaya perusahaan di sektor ini" />

                        <ul class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3">
                            @foreach ($clients as $client)
                                <li class="flex h-28 flex-col items-center justify-center gap-2 rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                                    @if ($client->logo && file_exists(public_path($client->logo)))
                                        <x-picture :src="$client->logo" alt="Logo {{ $client->name }}" loading="lazy" decoding="async" class="max-h-10 w-auto max-w-[80%] object-contain" />
                                    @else
                                        <span class="font-display text-base font-bold text-ink-700">{{ $client->name }}</span>
                                    @endif
                                    @if ($client->industry)
                                        <span class="text-[0.68rem] font-medium text-ink-400">{{ $client->industry }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- FAQ --}}
                @if (! empty($industry['faqs']))
                    <section id="faq" class="scroll-mt-36">
                        <x-section-heading align="left" eyebrow="FAQ" title="Pertanyaan yang sering diajukan" />

                        <div class="mt-10 divide-y divide-ink-100 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                            @foreach ($industry['faqs'] as $faq)
                                <details class="group" @if ($loop->first) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 font-display text-base font-semibold text-ink-900 transition-colors hover:text-brand-600">
                                        {{ $faq['question'] }}
                                        <x-icons.chevron-down class="h-4 w-4 shrink-0 text-ink-400 transition-transform duration-200 group-open:rotate-180" />
                                    </summary>
                                    <p class="px-6 pb-6 text-sm leading-relaxed text-ink-500">{{ $faq['answer'] }}</p>
                                </details>
                            @endforeach
                        </div>

                        <div class="mt-6 flex flex-col items-start gap-3 rounded-2xl bg-ink-50/80 p-6 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm font-medium text-ink-700">Pertanyaan Anda belum terjawab?</p>
                            <a href="{{ $consultHref }}" data-cta="content"
                               @if ($company->whatsapp_link) target="_blank" rel="noopener noreferrer" @endif
                               class="btn-primary">
                                <x-icons.whatsapp class="h-4 w-4" />
                                Tanya via WhatsApp
                            </a>
                        </div>
                    </section>
                @endif

                {{-- Industri sebelum / sesudah --}}
                <nav aria-label="Industri lainnya" class="grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('industries.show', $previous['slug']) }}" class="group rounded-2xl border border-ink-100 bg-white p-5 shadow-card transition-colors hover:border-brand-200">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-400">
                            <x-icons.arrow-right class="h-3.5 w-3.5 rotate-180" />
                            Sebelumnya
                        </span>
                        <span class="mt-2 block font-display font-bold text-ink-900 group-hover:text-brand-600">{{ $previous['name'] }}</span>
                    </a>
                    <a href="{{ route('industries.show', $next['slug']) }}" class="group rounded-2xl border border-ink-100 bg-white p-5 text-right shadow-card transition-colors hover:border-brand-200">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-400">
                            Berikutnya
                            <x-icons.arrow-right class="h-3.5 w-3.5" />
                        </span>
                        <span class="mt-2 block font-display font-bold text-ink-900 group-hover:text-brand-600">{{ $next['name'] }}</span>
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <x-contact-form
        eyebrow="Konsultasi Industri"
        :title="'Konsultasi 3D printing untuk '.$industry['name']" />

    <x-cta-band
        eyebrow="Konsultasi Gratis"
        :title="'Siap mencetak part untuk '.$industry['name'].'?'"
        description="Kirimkan file dan ceritakan fungsi part yang Anda butuhkan. Kami akan merekomendasikan teknologi serta material yang paling sesuai dengan anggaran dan tenggat Anda." />

@endsection

@push('scripts')
    {{-- Model 3D galeri, viewer putar 3D, dan penanda daftar isi aktif. --}}
    @vite('resources/js/industry-gallery.js')
@endpush
