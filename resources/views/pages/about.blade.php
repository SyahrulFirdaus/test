@extends('layouts.app')

@section('title', 'About')
@section('description', 'Profil ' . $company->name . ', visi dan misi perusahaan, keunggulan layanan, serta informasi kontak lengkap.')

@section('content')

    <x-page-hero
        eyebrow="Tentang Kami"
        current="About"
        title='Tim yang percaya bahwa <span class="text-brand-400">ide baik</span> layak diwujudkan'
        :description="$company->short_description">
        <x-slot:actions>
            <a href="#kontak" class="btn-primary w-full sm:w-auto">
                Informasi Kontak
                <x-icons.arrow-down class="h-4 w-4" />
            </a>
            <a href="{{ route('services') }}" class="btn-ghost-light w-full sm:w-auto">Lihat Layanan</a>
        </x-slot:actions>
    </x-page-hero>

    {{-- ===================== PROFIL PERUSAHAAN ===================== --}}
    <section class="section">
        <div class="container-page">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
                <div class="lg:col-span-7" data-aos="fade-right">
                    <span class="eyebrow">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        Profil Perusahaan
                    </span>

                    <h2 class="mt-6 text-3xl font-bold leading-tight tracking-tight text-ink-900 sm:text-4xl">
                        {{ $company->legal_name ?: $company->name }}
                    </h2>

                    <div class="prose-brand mt-6 space-y-5">
                        @foreach (preg_split('/(?<=\.)\s+(?=[A-Z])/u', $company->about, 3) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>

                    <dl class="mt-10 grid gap-6 border-t border-ink-100 pt-8 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Berdiri Sejak</dt>
                            <dd class="mt-2 font-display text-2xl font-bold text-ink-900">{{ $company->founded_year }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Lokasi Workshop</dt>
                            <dd class="mt-2 font-display text-2xl font-bold text-ink-900">{{ \Illuminate\Support\Str::before($company->city, ',') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Fokus Layanan</dt>
                            <dd class="mt-2 font-display text-2xl font-bold text-ink-900">Additive Mfg.</dd>
                        </div>
                    </dl>
                </div>

                <div class="lg:col-span-5" data-aos="fade-left">
                    <div class="relative">
                        <div class="overflow-hidden rounded-3xl border border-ink-100 bg-white shadow-card">
                            <img src="{{ asset('images/services/product-development.svg') }}"
                                 alt="Alur kerja pengembangan produk di {{ $company->name }}"
                                 loading="lazy" width="640" height="400" class="w-full object-cover">
                        </div>

                        <div class="mt-6 grid grid-cols-2 gap-4">
                            @foreach ($company->stats as $stat)
                                <div class="rounded-2xl border border-ink-100 bg-gradient-to-br from-brand-50 to-white p-5 text-center shadow-card">
                                    <p class="font-display text-3xl font-bold text-brand-600">
                                        <span data-counter="{{ $stat['value'] }}">0</span>{{ $stat['suffix'] }}
                                    </p>
                                    <p class="mt-1.5 text-xs font-semibold uppercase tracking-[0.1em] text-ink-500">{{ $stat['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== VISI & MISI ===================== --}}
    <section class="section bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Arah Perusahaan"
                title='Visi dan Misi'
                description="Dua hal yang menjadi acuan kami saat mengambil keputusan, besar maupun kecil." />

            <div class="mt-14 grid gap-6 lg:grid-cols-12">
                <div class="lg:col-span-5" data-aos="fade-right">
                    <div class="relative h-full overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-900 p-8 text-white shadow-glow sm:p-10">
                        <div class="blueprint-grid-dark absolute inset-0" aria-hidden="true"></div>
                        <div class="relative">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                                <x-icons.spark class="h-7 w-7 text-white" />
                            </span>
                            <h3 class="mt-6 font-display text-2xl font-bold">Visi</h3>
                            <p class="mt-4 text-base leading-relaxed text-white/85">{{ $company->vision }}</p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-7" data-aos="fade-left">
                    <div class="h-full rounded-3xl border border-ink-100 bg-white p-8 shadow-card sm:p-10">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600/10 text-brand-600">
                            <x-icons.layers class="h-7 w-7" />
                        </span>
                        <h3 class="mt-6 font-display text-2xl font-bold text-ink-900">Misi</h3>

                        <ol class="mt-6 space-y-5">
                            @foreach ($company->missions as $mission)
                                <li class="flex gap-4">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 font-display text-sm font-bold text-brand-600">
                                        {{ $loop->iteration }}
                                    </span>
                                    <p class="text-sm leading-relaxed text-ink-600 sm:text-base">{{ $mission }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== KEUNGGULAN ===================== --}}
    <section class="section">
        <div class="container-page">
            <x-section-heading
                eyebrow="Keunggulan"
                title='Alasan klien mempercayakan produksinya kepada kami'
                description="Bukan sekadar daftar mesin, ini komitmen yang kami jaga pada setiap pesanan." />

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($company->advantages as $advantage)
                    <div class="card-interactive group p-7" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 80 }}">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600/10 text-brand-600 transition-colors duration-300 group-hover:bg-brand-600 group-hover:text-white">
                            <x-dynamic-component :component="'icons.'.$advantage['icon']" class="h-7 w-7" />
                        </span>
                        <h3 class="mt-6 text-lg font-bold leading-snug text-ink-900">{{ $advantage['title'] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-ink-500">{{ $advantage['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== KONTAK ===================== --}}
    <section id="kontak" class="section scroll-mt-24 bg-ink-950">
        <div class="container-page">
            <x-section-heading
                theme="dark"
                eyebrow="Hubungi Kami"
                title='Mari bicarakan proyek Anda'
                description="Workshop kami terbuka untuk kunjungan pada jam kerja. Silakan hubungi lebih dulu agar tim dapat menyiapkan waktu khusus untuk Anda." />

            <div class="mt-14 grid gap-6 lg:grid-cols-12">
                <div class="lg:col-span-5" data-aos="fade-right">
                    <div class="h-full rounded-3xl border border-white/10 bg-white/5 p-8 backdrop-blur sm:p-10">
                        <h3 class="font-display text-xl font-bold text-white">Kantor &amp; Workshop</h3>

                        <ul class="mt-7 space-y-6">
                            <li class="flex gap-4">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-600/20 text-brand-300">
                                    <x-icons.map-pin class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Alamat</p>
                                    <p class="mt-1.5 text-sm leading-relaxed text-ink-200">{{ $company->address }}<br>{{ $company->city }}</p>
                                    @if ($company->maps_url)
                                        <a href="{{ $company->maps_url }}" target="_blank" rel="noopener noreferrer"
                                           class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-300 transition-colors hover:text-brand-200">
                                            Buka di Google Maps
                                            <x-icons.arrow-right class="h-3.5 w-3.5" />
                                        </a>
                                    @endif
                                </div>
                            </li>

                            <li class="flex gap-4">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-600/20 text-brand-300">
                                    <x-icons.clock class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Jam Operasional</p>
                                    <p class="mt-1.5 text-sm text-ink-200">{{ $company->operational_hours }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="lg:col-span-7" data-aos="fade-left">
                    <div class="grid h-full gap-4 sm:grid-cols-2">
                        <a href="tel:{{ preg_replace('/\s+/', '', $company->phone) }}"
                           class="group rounded-3xl border border-white/10 bg-white/5 p-7 backdrop-blur transition-all duration-300 hover:-translate-y-1 hover:border-brand-400/50 hover:bg-white/10">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white">
                                <x-icons.phone class="h-5 w-5" />
                            </span>
                            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Telepon</p>
                            <p class="mt-1.5 font-display text-lg font-bold text-white">{{ $company->phone }}</p>
                        </a>

                        <a href="{{ $company->whatsapp_link }}" target="_blank" rel="noopener noreferrer"
                           class="group rounded-3xl border border-white/10 bg-white/5 p-7 backdrop-blur transition-all duration-300 hover:-translate-y-1 hover:border-brand-400/50 hover:bg-white/10">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white">
                                <x-icons.whatsapp class="h-5 w-5" />
                            </span>
                            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">WhatsApp</p>
                            <p class="mt-1.5 font-display text-lg font-bold text-white">{{ $company->whatsapp }}</p>
                        </a>

                        <a href="mailto:{{ $company->email }}"
                           class="group rounded-3xl border border-white/10 bg-white/5 p-7 backdrop-blur transition-all duration-300 hover:-translate-y-1 hover:border-brand-400/50 hover:bg-white/10 sm:col-span-2">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white">
                                <x-icons.mail class="h-5 w-5" />
                            </span>
                            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Email</p>
                            <p class="mt-1.5 break-all font-display text-lg font-bold text-white">{{ $company->email }}</p>
                            <p class="mt-2 text-sm text-ink-400">Lampirkan file 3D beserta kebutuhan Anda agar kami dapat langsung meninjaunya.</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
