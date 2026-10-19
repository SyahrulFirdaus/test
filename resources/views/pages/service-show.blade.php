@extends('layouts.app')

{{-- Landing page satu layanan untuk iklan & SEO. Elemen mengikuti sheet
     "Landing Page Layanan": H1 & title sesuai keyword, 2 CTA di layar pertama
     (WA + Penawaran), form singkat tanpa login, foto hasil kerja, logo klien +
     400+ customer, keunggulan, FAQ, dan teks WA otomatis per halaman. --}}

@section('title', $landing['h1'])
@section('og-image', 'services')
@section('description', $landing['description'])

@php
    $pageUrl = route('services.show', $page);
    $pageLabel = preg_replace('#^https?://#', '', $pageUrl);
    $waText = 'Halo '.$company->name.', saya ingin konsultasi '.$landing['wa_service'].' (dari halaman '.$pageLabel.')';
    $waHref = wa_link($waText);

    $customerStat = \App\Support\CompanyStats::find('Customer');
    $customers = $customerStat && $customerStat['value'] !== null ? $customerStat['display'].$customerStat['suffix'] : null;

    // Keunggulan sesuai matriks: review gratis, 1 hari kerja, NDA, QC.
    // Teksnya diambil dari keunggulan perusahaan (Profil Perusahaan).
    $advantages = collect($company->advantages ?? [])->keyBy('icon');
    $keunggulan = collect([
        ['icon' => 'users', 'title' => 'Review File Gratis', 'source' => 'users'],
        ['icon' => 'clock', 'title' => 'Mulai 1 Hari Kerja', 'source' => 'clock'],
        ['icon' => 'lock', 'title' => 'Kerahasiaan & NDA', 'source' => 'lock'],
        ['icon' => 'shield', 'title' => 'Quality Control Setiap Order', 'source' => 'shield'],
    ])->filter(fn ($item) => $advantages->has($item['source']))
      ->map(fn ($item) => $item + ['description' => $advantages[$item['source']]['description']]);
@endphp

@section('wa-text', $waText)

@section('quote-href', '#minta-penawaran')

@section('content')

    {{-- ===================== HERO + 2 CTA ===================== --}}
    <x-page-hero
        eyebrow="Layanan"
        :current="$service->title"
        :title="e($landing['h1'])"
        :description="$service->tagline.'. '.$service->excerpt">
        <x-slot:actions>
            <a href="{{ $waHref }}"
               @if ($company->whatsapp_link) target="_blank" rel="noopener noreferrer" @endif
               class="btn-primary w-full sm:w-auto"
               data-wa-link
               data-cta="hero">
                <x-icons.whatsapp class="h-4 w-4" />
                Konsultasi via WhatsApp
            </a>
            <a href="#minta-penawaran" class="btn-ghost-light w-full sm:w-auto" data-cta="hero-quote">
                Minta Penawaran
            </a>
        </x-slot:actions>
    </x-page-hero>

    {{-- ===================== LOGO KLIEN + 400+ CUSTOMER ===================== --}}
    @if ($clients->isNotEmpty() || $customers)
        <section class="border-b border-ink-100 bg-white py-10">
            <div class="container-page">
                <p class="text-center text-sm font-semibold text-ink-500">
                    @if ($customers)
                        Dipercaya <span class="font-display text-base font-bold text-brand-600">{{ $customers }} customer</span>, termasuk:
                    @else
                        Dipercaya oleh:
                    @endif
                </p>

                @if ($clients->isNotEmpty())
                    <ul class="mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-6">
                        @foreach ($clients as $client)
                            <li class="flex items-center justify-center">
                                <x-picture :src="$client->logo"
                                           alt="Logo {{ $client->name }}"
                                           title="{{ $client->name }}"
                                           loading="lazy" decoding="async"
                                           class="max-h-10 w-auto max-w-[120px] object-contain opacity-60 grayscale transition-all duration-500 ease-out hover:opacity-100 hover:grayscale-0 sm:max-h-12" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    {{-- ===================== URAIAN LAYANAN ===================== --}}
    <section class="section">
        <div class="container-page">
            <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                <div data-aos="fade-right">
                    <div class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                        <img src="{{ asset($service->image) }}"
                             alt="Ilustrasi layanan {{ $service->title }}"
                             loading="lazy" decoding="async"
                             width="640" height="400"
                             class="aspect-[16/10] w-full object-cover">
                    </div>
                </div>

                <div data-aos="fade-left">
                    <div class="flex items-center gap-4">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-[0_14px_30px_-14px_rgba(149,39,29,0.9)]">
                            <x-dynamic-component :component="'icons.'.$service->icon" class="h-7 w-7" />
                        </span>
                        <span class="font-display text-sm font-bold uppercase tracking-[0.16em] text-brand-600">{{ $service->title }}</span>
                    </div>

                    <h2 class="mt-6 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $service->tagline }}</h2>
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
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== KEUNGGULAN ===================== --}}
    @if ($keunggulan->isNotEmpty())
        <section class="section bg-ink-50/70">
            <div class="container-page">
                <x-section-heading
                    eyebrow="Keunggulan"
                    :title="'Kenapa memilih '.$company->name.'?'" />

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($keunggulan as $item)
                        <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card" data-aos="fade-up" data-aos-delay="{{ $loop->index * 60 }}">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                                <x-dynamic-component :component="'icons.'.$item['icon']" class="h-5 w-5" />
                            </span>
                            <h3 class="mt-5 font-display text-base font-bold text-ink-900">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $item['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== FOTO HASIL KERJA ===================== --}}
    {{-- Diisi dari public/images/services/{slug}/; tidak tampil selama kosong. --}}
    @if (count($gallery) > 0)
        <section class="section" data-service-gallery>
            <div class="container-page">
                <x-section-heading eyebrow="Hasil Kerja" :title="'Contoh hasil '.$service->title" />

                <div class="mt-12 grid grid-cols-2 gap-4 lg:grid-cols-3">
                    @foreach ($gallery as $photo)
                        <div class="overflow-hidden rounded-2xl border border-ink-100 bg-ink-100 shadow-card" data-aos="fade-up">
                            <x-picture :src="$photo"
                                       alt="Hasil kerja {{ $service->title }} {{ $loop->iteration }}"
                                       loading="lazy" decoding="async"
                                       class="aspect-[4/3] w-full object-cover" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== FORM SINGKAT (TANPA LOGIN) ===================== --}}
    {{-- Dikirim lewat WhatsApp: isian disusun menjadi pesan ke nomor
         perusahaan, tanpa login dan tanpa disimpan di server. --}}
    <section id="minta-penawaran" class="section scroll-mt-24 bg-ink-950">
        <div class="container-page">
            <div class="grid gap-10 lg:grid-cols-12 lg:gap-14">
                <div class="lg:col-span-5">
                    <x-section-heading
                        theme="dark"
                        align="left"
                        eyebrow="Minta Penawaran"
                        :title="'Ceritakan kebutuhan '.$service->title.' Anda'"
                        description="Isi form singkat ini tanpa perlu membuat akun. Pesan Anda langsung terkirim ke tim kami melalui WhatsApp, dan file pendukung dapat dilampirkan di chat yang sama." />

                    <a href="{{ route('models') }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-brand-300 transition-colors hover:text-brand-200">
                        Sudah punya file 3D? Unggah untuk estimasi instan
                        <x-icons.arrow-right class="h-4 w-4" />
                    </a>
                </div>

                <div class="lg:col-span-7">
                    <form action="{{ $company->whatsapp_link ?: route('about') }}" method="get" target="_blank"
                          class="rounded-3xl border border-white/10 bg-white p-6 shadow-card sm:p-8"
                          data-service-lead-form
                          data-wa-base="{{ $company->whatsapp_link }}"
                          data-page-label="{{ $pageLabel }}"
                          data-lead-endpoint="{{ route('leads.store') }}"
                          data-from-google="{{ \App\Support\LeadTracking::fromGoogleAds(\App\Support\LeadTracking::fromRequest(request())) ? '1' : '' }}">
                        {{-- Cadangan tanpa JavaScript: tetap membuka WhatsApp. --}}
                        <input type="hidden" name="text" value="{{ $waText }}" data-lead-fallback>

                        {{-- Jebakan bot: tersembunyi dari pengunjung, tidak pernah diisi manusia. --}}
                        <div class="hidden" aria-hidden="true">
                            <label for="lead-website">Website</label>
                            <input id="lead-website" type="text" tabindex="-1" autocomplete="off" data-lead-key="website">
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="lead-name" class="text-sm font-semibold text-ink-700">Nama <span class="text-brand-600">*</span></label>
                                <input id="lead-name" type="text" required maxlength="100" autocomplete="name"
                                       class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                       data-lead-field="Nama" data-lead-key="name">
                            </div>
                            <div>
                                <label for="lead-company" class="text-sm font-semibold text-ink-700">Perusahaan <span class="font-normal text-ink-400">(opsional)</span></label>
                                <input id="lead-company" type="text" maxlength="120" autocomplete="organization"
                                       class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                       data-lead-field="Perusahaan" data-lead-key="company">
                            </div>
                            <div>
                                <label for="lead-email" class="text-sm font-semibold text-ink-700">Email <span class="font-normal text-ink-400">(opsional)</span></label>
                                <input id="lead-email" type="email" maxlength="120" autocomplete="email"
                                       class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                       data-lead-field="Email" data-lead-key="email">
                            </div>
                            <div>
                                <label for="lead-whatsapp" class="text-sm font-semibold text-ink-700">No. WhatsApp <span class="font-normal text-ink-400">(opsional)</span></label>
                                <input id="lead-whatsapp" type="tel" maxlength="30" autocomplete="tel" inputmode="tel"
                                       placeholder="08xx-xxxx-xxxx"
                                       class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                       data-lead-field="WhatsApp" data-lead-key="whatsapp">
                            </div>
                            <div>
                                <label for="lead-service" class="text-sm font-semibold text-ink-700">Layanan</label>
                                <select id="lead-service"
                                        class="mt-2 w-full rounded-xl border border-ink-200 bg-white px-4 py-3 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                        data-lead-field="Layanan" data-lead-key="service">
                                    @foreach (config('service-pages.pages') as $slug => $item)
                                        <option value="{{ $item['wa_service'] }}" @selected($slug === $page)>{{ $item['wa_service'] }}</option>
                                    @endforeach
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="lead-need" class="text-sm font-semibold text-ink-700">Kebutuhan <span class="text-brand-600">*</span></label>
                                <textarea id="lead-need" rows="4" required maxlength="1000"
                                          placeholder="Contoh: jumlah part, ukuran, material, tenggat, atau fungsi part."
                                          class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                          data-lead-field="Kebutuhan" data-lead-key="notes"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary mt-6 w-full sm:w-auto" data-cta="form">
                            <x-icons.whatsapp class="h-4 w-4" />
                            Kirim via WhatsApp
                        </button>

                        <x-legal-note action="mengirim form ini" class="mt-4" />
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== FAQ ===================== --}}
    {{-- Accordion + FAQPage JSON-LD dari config/service-faqs.php. --}}
    <x-faq-section :faqs="$faqs" />

    {{-- ===================== LAYANAN LAIN ===================== --}}
    <section class="section bg-ink-50/70">
        <div class="container-page">
            <x-section-heading eyebrow="Layanan Lain" title="Layanan yang saling melengkapi" />

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($otherServices as $other)
                    @php $otherUrl = $landingUrls[$other->slug] ?? route('services').'#'.$other->slug; @endphp
                    <a href="{{ $otherUrl }}" class="group flex items-center gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-card transition-colors hover:border-brand-200">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                            <x-dynamic-component :component="'icons.'.$other->icon" class="h-5 w-5" />
                        </span>
                        <span class="text-sm font-semibold text-ink-800 group-hover:text-brand-600">{{ $other->title }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <x-cta-band
        eyebrow="Konsultasi Gratis"
        :title="'Siap memulai '.$service->title.'?'"
        description="Kirimkan file atau ceritakan kebutuhan Anda. Tim engineer kami meninjaunya dan memberikan rekomendasi serta penawaran."
        primary-label="Konsultasi via WhatsApp"
        :primary-href="$waHref"
        secondary-label="Minta Penawaran"
        secondary-href="#minta-penawaran" />

@endsection

@push('scripts')
    <script>
        (function () {
            // Teks WA otomatis per halaman (UX-04): tambahkan "[Google]" bila
            // pengunjung datang dari iklan Google (gclid di URL atau cookie).
            var leadForm = document.querySelector('[data-service-lead-form]');
            var fromGoogle = /[?&](gclid|gbraid|wbraid)=/.test(location.search)
                || /(?:^|;\s*)gclid=/.test(document.cookie)
                || !!(leadForm && leadForm.dataset.fromGoogle);

            if (fromGoogle) {
                document.querySelectorAll('a[data-wa-link]').forEach(function (link) {
                    try {
                        var url = new URL(link.href);
                        var text = url.searchParams.get('text');

                        // Disusun ulang dengan encodeURIComponent (spasi = %20),
                        // bukan URLSearchParams yang mengubah spasi menjadi "+".
                        if (text && text.indexOf('[Google]') === -1) {
                            link.href = url.origin + url.pathname + '?text=' + encodeURIComponent(text + ' [Google]');
                        }
                    } catch (error) {}
                });
            }

            // Form singkat → WhatsApp.
            var form = document.querySelector('[data-service-lead-form]');

            if (!form || !form.dataset.waBase) {
                return;
            }

            function saveLead() {
                try {
                    var data = new FormData();
                    var token = document.querySelector('meta[name="csrf-token"]');

                    data.append('_token', token ? token.content : '');
                    data.append('page_url', location.origin + location.pathname);

                    form.querySelectorAll('[data-lead-key]').forEach(function (field) {
                        data.append(field.dataset.leadKey, field.value.trim());
                    });

                    fetch(form.dataset.leadEndpoint, {
                        method: 'POST',
                        body: data,
                        credentials: 'same-origin',
                        keepalive: true,
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (response) {
                        // Konversi lead hanya setelah server menyimpannya.
                        if (response.status !== 201 || typeof window.nusamaTrack !== 'function') {
                            return;
                        }

                        var field = function (key) {
                            var el = form.querySelector('[data-lead-key="' + key + '"]');
                            return el ? el.value.trim() : '';
                        };
                        var email = field('email').toLowerCase();
                        var phone = field('whatsapp').replace(/\D/g, '').replace(/^0/, '62').replace(/^8/, '628');
                        var userData = {};

                        if (email) { userData.email = email; }
                        if (phone) { userData.phone_number = '+' + phone; }

                        window.nusamaTrack('generate_lead', Object.assign({
                            form_location: location.pathname.replace(/^\/+|\/+$/g, '').replace(/\//g, '-') || 'home',
                            service: field('service'),
                            form_name: 'landing'
                        }, Object.keys(userData).length ? { user_data: userData } : {}));
                    }).catch(function () {});
                } catch (error) {}
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var service = form.querySelector('[data-lead-field="Layanan"]').value;
                var lines = ['Halo ' + @json($company->name) + ', saya ingin minta penawaran ' + service + ' (dari halaman ' + form.dataset.pageLabel + ')', ''];

                form.querySelectorAll('[data-lead-field]').forEach(function (field) {
                    if (field.dataset.leadField !== 'Layanan' && field.value.trim() !== '') {
                        lines.push(field.dataset.leadField + ': ' + field.value.trim());
                    }
                });

                var text = lines.join('\n') + (fromGoogle ? '\n[Google]' : '');

                // Simpan ke Lead Management tanpa menunggu: WhatsApp tetap
                // terbuka meski penyimpanan gagal, dan tidak diblokir popup
                // blocker karena dibuka langsung pada klik.
                saveLead();

                window.open(form.dataset.waBase + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
            });
        })();
    </script>
@endpush
