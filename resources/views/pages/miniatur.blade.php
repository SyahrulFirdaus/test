@extends('layouts.app')

{{-- Landing page Miniatur Teknik & Souvenir Korporat. Isi di
     config/miniatur.php; formulir konsultasi masuk ke Lead Management
     (route leads.store) beserta sumber halaman & GCLID/UTM. --}}

@section('title', config('miniatur.title'))
@section('og-image', 'services')
@section('description', config('miniatur.description'))

@php
    $price = config('miniatur.price');
    $waText = config('miniatur.wa_text');
    $waHref = wa_link($waText);
    $heroPhotos = collect($gallery)->filter(fn ($item) => $item['image'])->take(2)->values();

    $inputClass = 'mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 aria-[invalid=true]:border-brand-600';
    $attachment = config('miniatur.attachment');
@endphp

@section('wa-text', $waText)

@section('quote-href', '#konsultasi')

@section('content')

    {{-- ===================== HERO ===================== --}}
    <x-page-hero
        eyebrow="3D Printing untuk Korporat"
        current="Miniatur Teknik"
        :title="e(config('miniatur.title'))"
        description="Pembuatan miniatur teknik, maket, replika produk, dan souvenir perusahaan dengan teknologi 3D printing — detail presisi, desain custom, dan finishing sesuai kebutuhan presentasi, pameran, maupun hadiah korporat.">
        <x-slot:actions>
            <a href="#konsultasi" class="btn-primary w-full sm:w-auto" data-cta="hero-konsultasi">
                Konsultasikan Proyek
            </a>
            <a href="#galeri" class="btn-ghost-light w-full sm:w-auto" data-cta="hero-galeri">
                Lihat Galeri
            </a>
            <a href="{{ $waHref }}" @if ($company->whatsapp_link) target="_blank" rel="noopener noreferrer" @endif class="btn-ghost-light w-full sm:w-auto" data-wa-link data-cta="hero-wa">
                <x-icons.whatsapp class="h-4 w-4" />
                WhatsApp
            </a>
        </x-slot:actions>

        @if ($heroPhotos->isNotEmpty())
            <x-slot:visual>
                <div class="relative flex h-full items-center justify-center">
                    <div class="grid w-full max-w-lg grid-cols-5 items-start gap-4">
                        @foreach ($heroPhotos as $photo)
                            <figure class="{{ $loop->first ? 'col-span-3 mt-10' : 'col-span-2 -mt-6' }} overflow-hidden rounded-3xl border border-white/10 bg-white shadow-[0_30px_60px_-30px_rgba(0,0,0,0.8)]">
                                <x-picture :src="$photo['image']"
                                           alt=""
                                           loading="eager" decoding="async"
                                           class="aspect-[4/5] w-full {{ $photo['fit'] === 'contain' ? 'object-contain p-4' : 'object-cover' }}" />
                                <figcaption class="bg-ink-950/90 px-3 py-1.5 text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-white/70">
                                    {{ $photo['title'] }}{{ $photo['credit'] ? ' · ilustrasi' : '' }}
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </x-slot:visual>
        @endif
    </x-page-hero>

    {{-- ===================== INFORMASI HARGA ===================== --}}
    <section class="relative z-10 -mt-10 pb-4 md:-mt-14" aria-labelledby="harga-heading">
        <div class="container-page">
            <div class="grid gap-6 rounded-3xl border border-ink-100 bg-white p-6 shadow-card sm:p-8 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-600">Informasi Harga</p>
                    <h2 id="harga-heading" class="mt-2 font-display text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">{{ $price['headline'] }}</h2>
                </div>
                <ul class="space-y-3 text-sm leading-relaxed text-ink-600 lg:col-span-5">
                    <li class="flex gap-2.5">
                        <x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                        <span>{{ $price['note'] }}</span>
                    </li>
                    <li class="flex gap-2.5">
                        <x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                        <span>{{ $price['bulk'] }}</span>
                    </li>
                </ul>
                <div class="lg:col-span-2 lg:text-right">
                    <a href="#konsultasi" class="btn-primary w-full lg:w-auto">Minta Penawaran</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== GALERI ===================== --}}
    <section id="galeri" class="section scroll-mt-24">
        <div class="container-page">
            <x-section-heading
                eyebrow="Galeri Produk"
                title="Kategori miniatur yang dapat kami buat"
                description="Dari miniatur mesin hingga souvenir perusahaan — semuanya dibuat custom sesuai desain dan kebutuhan Anda." />

            @if (collect($gallery)->contains(fn ($item) => $item['credit']))
                <p class="mx-auto mt-4 max-w-2xl text-center text-xs text-ink-400">
                    Foto berlabel "Ilustrasi" adalah contoh kategori dari sumber berlisensi terbuka, bukan hasil proyek NUSAMA3D.
                </p>
            @endif

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-6">
                @foreach ($gallery as $item)
                    <article class="group flex flex-col overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card lg:col-span-2 {{ $loop->index === 3 ? 'lg:col-start-2' : '' }}"
                             data-aos="fade-up" data-aos-delay="{{ $loop->index * 60 }}">
                        <div class="relative overflow-hidden {{ $item['fit'] === 'contain' ? 'bg-white' : 'bg-ink-100' }}">
                            @if ($item['image'])
                                <x-picture :src="$item['image']"
                                           alt="{{ $item['title'] }}{{ $item['credit'] ? ' (ilustrasi)' : '' }}"
                                           loading="lazy" decoding="async"
                                           class="aspect-[4/3] w-full transition-transform duration-500 group-hover:scale-105 {{ $item['fit'] === 'contain' ? 'object-contain p-4' : 'object-cover' }}" />
                            @else
                                <div class="flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-brand-700 to-brand-950 text-white/80">
                                    <x-icons.cube class="h-12 w-12" />
                                </div>
                            @endif

                            @if ($item['credit'] || ! $item['image'])
                                <span class="absolute left-3 top-3 rounded-full bg-ink-950/75 px-2.5 py-1 text-[0.6rem] font-bold uppercase tracking-[0.12em] text-white backdrop-blur">Ilustrasi</span>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="font-display text-lg font-bold text-ink-900">{{ $item['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $item['description'] }}</p>

                            @if ($item['credit'])
                                <p class="mt-auto pt-4 text-[0.65rem] leading-snug text-ink-400">
                                    Ilustrasi:
                                    <a href="{{ $item['credit']['source_url'] }}" target="_blank" rel="noopener noreferrer" class="underline decoration-ink-200 underline-offset-2 hover:text-brand-600">{{ $item['credit']['title'] }}</a>
                                    oleh {{ $item['credit']['author'] }} ·
                                    @if ($item['credit']['license_url'])
                                        <a href="{{ $item['credit']['license_url'] }}" target="_blank" rel="noopener noreferrer license" class="underline decoration-ink-200 underline-offset-2 hover:text-brand-600">{{ $item['credit']['license'] }}</a>
                                    @else
                                        {{ $item['credit']['license'] }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== KEUNGGULAN ===================== --}}
    <section class="section bg-ink-50/70">
        <div class="container-page">
            <x-section-heading eyebrow="Keunggulan" :title="'Kenapa membuat miniatur di '.$company->name.'?'" />

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                @foreach (config('miniatur.advantages') as $item)
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

    {{-- ===================== FORMULIR KONSULTASI ===================== --}}
    <section id="konsultasi" class="section scroll-mt-24 bg-ink-950">
        <div class="container-page">
            <div class="grid gap-10 lg:grid-cols-12 lg:gap-14">
                <div class="lg:col-span-5">
                    <x-section-heading
                        theme="dark"
                        align="left"
                        eyebrow="Konsultasi Proyek"
                        title="Ceritakan miniatur yang Anda butuhkan"
                        description="Isi formulir tanpa perlu membuat akun. Tim kami meninjau kebutuhan dan desain Anda, lalu menghubungi Anda untuk konsultasi dan penawaran." />

                    <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-300">Harga</p>
                        <p class="mt-2 font-display text-2xl font-bold text-white">{{ $price['headline'] }}</p>
                        <p class="mt-3 text-sm leading-relaxed text-ink-300">{{ $price['note'] }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-ink-300">{{ $price['bulk'] }}</p>
                    </div>

                    <a href="{{ $waHref }}" @if ($company->whatsapp_link) target="_blank" rel="noopener noreferrer" @endif data-wa-link data-cta="form-wa"
                       class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-300 transition-colors hover:text-brand-200">
                        <x-icons.whatsapp class="h-4 w-4" />
                        Lebih suka chat? Konsultasi via WhatsApp
                    </a>
                </div>

                <div class="lg:col-span-7">
                    <form action="{{ route('leads.store') }}" method="POST" enctype="multipart/form-data" novalidate
                          class="rounded-3xl border border-white/10 bg-white p-6 shadow-card sm:p-8"
                          data-miniatur-form>
                        @csrf
                        <input type="hidden" name="page_url" value="{{ route('miniatur') }}">
                        <input type="hidden" name="form" value="miniatur">

                        {{-- Jebakan bot: tersembunyi dari pengunjung, tidak pernah diisi manusia. --}}
                        <div class="hidden" aria-hidden="true">
                            <label for="mt-website">Website</label>
                            <input id="mt-website" type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="{{ session('lead_sent') ? '' : 'hidden' }} mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"
                             role="status" tabindex="-1" data-form-success>
                            {{ session('lead_sent') ?: 'Terima kasih, kebutuhan Anda sudah kami terima. Tim kami akan segera menghubungi Anda.' }}
                        </div>

                        <div class="{{ $errors->any() ? '' : 'hidden' }} mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm font-semibold text-brand-800"
                             role="alert" tabindex="-1" data-form-error>
                            {{ $errors->first('form') ?: 'Periksa kembali isian yang ditandai.' }}
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach ([
                                ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true, 'max' => 100, 'autocomplete' => 'name'],
                                ['name' => 'company', 'label' => 'Nama Perusahaan/Instansi', 'type' => 'text', 'required' => false, 'max' => 120, 'autocomplete' => 'organization'],
                                ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'max' => 120, 'autocomplete' => 'email'],
                                ['name' => 'whatsapp', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'required' => true, 'max' => 30, 'autocomplete' => 'tel', 'placeholder' => '08xx-xxxx-xxxx', 'phone' => true],
                            ] as $field)
                                <div>
                                    <label for="mt-{{ $field['name'] }}" class="text-sm font-semibold text-ink-700">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-brand-600">*</span>
                                        @else
                                            <span class="font-normal text-ink-400">(opsional)</span>
                                        @endif
                                    </label>
                                    <input id="mt-{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}"
                                           value="{{ old($field['name']) }}"
                                           maxlength="{{ $field['max'] }}" autocomplete="{{ $field['autocomplete'] }}"
                                           @if ($field['required']) required @endif
                                           @isset($field['placeholder']) placeholder="{{ $field['placeholder'] }}" @endisset
                                           @isset($field['phone']) inputmode="tel" data-phone @endisset
                                           @error($field['name']) aria-invalid="true" @enderror
                                           aria-describedby="mt-{{ $field['name'] }}-error"
                                           class="{{ $inputClass }}">
                                    <p id="mt-{{ $field['name'] }}-error" class="mt-1.5 text-xs font-semibold text-brand-700" data-error-for="{{ $field['name'] }}">@error($field['name']){{ $message }}@enderror</p>
                                </div>
                            @endforeach

                            <div>
                                <label for="mt-service" class="text-sm font-semibold text-ink-700">Jenis Kebutuhan <span class="text-brand-600">*</span></label>
                                <select id="mt-service" name="service" required aria-describedby="mt-service-error"
                                        @error('service') aria-invalid="true" @enderror
                                        class="{{ $inputClass }} bg-white">
                                    <option value="">Pilih jenis kebutuhan</option>
                                    @foreach (config('miniatur.needs') as $need)
                                        <option value="{{ $need }}" @selected(old('service') === $need)>{{ $need }}</option>
                                    @endforeach
                                </select>
                                <p id="mt-service-error" class="mt-1.5 text-xs font-semibold text-brand-700" data-error-for="service">@error('service'){{ $message }}@enderror</p>
                            </div>

                            <div>
                                <label for="mt-quantity" class="text-sm font-semibold text-ink-700">Jumlah Pesanan (pcs) <span class="text-brand-600">*</span></label>
                                <input id="mt-quantity" name="quantity" type="number" min="1" max="1000000" step="1" inputmode="numeric" required
                                       value="{{ old('quantity') }}" placeholder="Contoh: 10"
                                       @error('quantity') aria-invalid="true" @enderror
                                       aria-describedby="mt-quantity-error"
                                       class="{{ $inputClass }}">
                                <p id="mt-quantity-error" class="mt-1.5 text-xs font-semibold text-brand-700" data-error-for="quantity">@error('quantity'){{ $message }}@enderror</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="mt-dimensions" class="text-sm font-semibold text-ink-700">Dimensi / Ukuran Perkiraan <span class="font-normal text-ink-400">(opsional)</span></label>
                                <input id="mt-dimensions" name="dimensions" type="text" maxlength="120"
                                       value="{{ old('dimensions') }}" placeholder="Contoh: 30 × 20 × 15 cm, skala 1:100"
                                       @error('dimensions') aria-invalid="true" @enderror
                                       aria-describedby="mt-dimensions-error"
                                       class="{{ $inputClass }}">
                                <p id="mt-dimensions-error" class="mt-1.5 text-xs font-semibold text-brand-700" data-error-for="dimensions">@error('dimensions'){{ $message }}@enderror</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="mt-notes" class="text-sm font-semibold text-ink-700">Deskripsi Kebutuhan <span class="text-brand-600">*</span></label>
                                <textarea id="mt-notes" name="notes" rows="4" required maxlength="1000"
                                          placeholder="Contoh: maket pabrik untuk pameran, tingkat detail, warna, tenggat, dan kegunaan."
                                          @error('notes') aria-invalid="true" @enderror
                                          aria-describedby="mt-notes-error"
                                          class="{{ $inputClass }}">{{ old('notes') }}</textarea>
                                <p id="mt-notes-error" class="mt-1.5 text-xs font-semibold text-brand-700" data-error-for="notes">@error('notes'){{ $message }}@enderror</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="mt-attachment" class="text-sm font-semibold text-ink-700">Gambar Referensi / Desain <span class="font-normal text-ink-400">(opsional)</span></label>
                                <input id="mt-attachment" name="attachment" type="file"
                                       accept="{{ collect($attachment['mimes'])->map(fn ($ext) => '.'.$ext)->implode(',') }}"
                                       data-max-kb="{{ $attachment['max_kb'] }}"
                                       aria-describedby="mt-attachment-help mt-attachment-error"
                                       class="mt-2 block w-full cursor-pointer rounded-xl border border-dashed border-ink-200 px-4 py-3 text-sm text-ink-600 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                                <p id="mt-attachment-help" class="mt-1.5 text-xs text-ink-400">JPG, PNG, WebP, atau PDF, maksimal {{ $attachment['max_kb'] / 1024 }} MB.</p>
                                <p id="mt-attachment-error" class="mt-1 text-xs font-semibold text-brand-700" data-error-for="attachment">@error('attachment'){{ $message }}@enderror</p>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary mt-6 w-full sm:w-auto" data-cta="form" data-submit>
                            Kirim Permintaan Konsultasi
                        </button>

                        <x-legal-note action="mengirim formulir ini" class="mt-4" />
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== FAQ ===================== --}}
    <x-faq-section :faqs="\App\Support\ServiceFaqs::for('miniatur')" />

    <x-cta-band
        eyebrow="Konsultasi Gratis"
        title="Siap mewujudkan miniatur atau souvenir perusahaan Anda?"
        description="Kirimkan desain, foto, atau gambar referensi Anda. Tim kami membantu menentukan skala, material, dan finishing yang paling sesuai."
        primary-label="Konsultasi via WhatsApp"
        :primary-href="$waHref"
        secondary-label="Isi Formulir"
        secondary-href="#konsultasi" />

@endsection

@push('scripts')
    <script>
        (function () {
            var form = document.querySelector('[data-miniatur-form]');

            if (!form || !window.fetch || !window.FormData) {
                return;
            }

            var success = form.querySelector('[data-form-success]');
            var failure = form.querySelector('[data-form-error]');
            var button = form.querySelector('[data-submit]');
            var file = form.querySelector('[name="attachment"]');

            var messages = {
                name: 'Nama wajib diisi.',
                email: 'Masukkan email yang valid.',
                whatsapp: 'Masukkan nomor WhatsApp yang valid (angka saja).',
                service: 'Pilih jenis kebutuhan.',
                quantity: 'Masukkan jumlah pesanan minimal 1.',
                notes: 'Deskripsi kebutuhan wajib diisi.'
            };

            function setError(name, message) {
                var field = form.querySelector('[name="' + name + '"]');
                var slot = form.querySelector('[data-error-for="' + name + '"]');

                if (field) {
                    field.setAttribute('aria-invalid', message ? 'true' : 'false');
                }

                if (slot) {
                    slot.textContent = message || '';
                }
            }

            function validate() {
                var firstInvalid = null;

                Object.keys(messages).forEach(function (name) {
                    var field = form.querySelector('[name="' + name + '"]');
                    var value = field.value.trim();
                    var valid = value !== '' && field.checkValidity()
                        && (!field.hasAttribute('data-phone') || /^[0-9+()\-\s.]{6,30}$/.test(value));

                    setError(name, valid ? '' : messages[name]);

                    if (!valid && !firstInvalid) {
                        firstInvalid = field;
                    }
                });

                setError('attachment', '');

                if (file.files.length) {
                    var picked = file.files[0];
                    var allowed = file.accept.split(',');
                    var extension = '.' + picked.name.split('.').pop().toLowerCase();

                    if (allowed.indexOf(extension) === -1) {
                        setError('attachment', 'Lampiran harus berupa gambar (JPG, PNG, WebP) atau PDF.');
                        firstInvalid = firstInvalid || file;
                    } else if (picked.size > Number(file.dataset.maxKb) * 1024) {
                        setError('attachment', 'Ukuran lampiran maksimal ' + Math.round(Number(file.dataset.maxKb) / 1024) + ' MB.');
                        firstInvalid = firstInvalid || file;
                    }
                }

                if (firstInvalid) {
                    firstInvalid.focus();
                }

                return !firstInvalid;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                success.classList.add('hidden');
                failure.classList.add('hidden');

                if (!validate()) {
                    return;
                }

                button.disabled = true;
                button.textContent = 'Mengirim…';

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (body) {
                        return { status: response.status, body: body };
                    });
                }).then(function (result) {
                    if (result.status === 201) {
                        // Halaman Terima Kasih yang mengirim event generate_lead.
                        if (result.body.redirect) {
                            window.location.href = result.body.redirect;
                            return;
                        }

                        form.reset();
                        success.textContent = result.body.message || success.textContent;
                        success.classList.remove('hidden');
                        success.focus();

                        return;
                    }

                    if (result.status === 422 && result.body.errors) {
                        Object.keys(result.body.errors).forEach(function (name) {
                            setError(name, result.body.errors[name][0]);
                        });
                        failure.textContent = 'Periksa kembali isian yang ditandai.';
                    } else if (result.status === 419) {
                        failure.textContent = 'Sesi halaman sudah kedaluwarsa. Muat ulang halaman lalu kirim kembali.';
                    } else if (result.status === 429) {
                        failure.textContent = 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.';
                    } else {
                        failure.textContent = result.body.message || 'Formulir belum dapat dikirim. Silakan coba lagi atau hubungi kami melalui WhatsApp.';
                    }

                    failure.classList.remove('hidden');
                    failure.focus();
                }).catch(function () {
                    failure.textContent = 'Koneksi terputus. Periksa internet Anda lalu coba lagi.';
                    failure.classList.remove('hidden');
                }).finally(function () {
                    button.disabled = false;
                    button.textContent = 'Kirim Permintaan Konsultasi';
                });
            });
        })();
    </script>
@endpush
