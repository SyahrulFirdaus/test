@extends('layouts.app')

@section('title', 'Jasa 3D Printing & Additive Manufacturing')
@section('description', $company->short_description)

@section('content')

    {{-- ===================== HERO =====================
         Latar 3D yang sama dengan halaman masuk (components/scene-3d): mesin
         cetak Three.js, lantai grid berperspektif, dan kubus rangka. Mesinnya
         digeser ke kanan supaya teks di kiri tetap lega dan terbaca. --}}
    {{-- Di ponsel tingginya mengikuti isi (min-h) supaya tombol tidak terpotong. --}}
    <section class="relative flex min-h-[600px] items-center overflow-hidden bg-brand-950 pb-14 sm:h-[620px] sm:min-h-0 sm:pb-0 lg:h-[700px] xl:h-[750px]"
             data-hero-3d>

        <x-scene-3d shift-x="0.3" shift-y="-0.04" scale="1.35" />

        {{-- Sisi kiri sedikit digelapkan agar teks kontras di atas latar 3D. --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-brand-950/70 via-brand-950/25 to-transparent" aria-hidden="true"></div>

        <div class="container-page relative w-full pt-36 sm:pt-24">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-brand-300 backdrop-blur">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-500"></span>
                    </span>
                    Menerima order harian di {{ $company->city }}
                </span>

                <h1 class="mt-6 text-4xl font-bold leading-[1.08] tracking-tight text-white drop-shadow-lg sm:text-5xl lg:text-6xl">
                    Wujudkan ide Anda<br>
                    menjadi <span class="text-brand-300">part nyata</span>
                </h1>

                <p class="mt-6 max-w-xl text-base leading-relaxed text-white/80 drop-shadow sm:text-lg">
                    {{ $company->short_description }}
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('models') }}" class="btn-primary w-full sm:w-auto">
                        <x-icons.upload class="h-4 w-4" />
                        Order Now
                    </a>
                    <a href="{{ route('services') }}" class="btn-ghost-light w-full sm:w-auto">
                        Jelajahi Layanan
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== ANGKA KUNCI ===================== --}}
    <section class="bg-ink-950 py-10">
        <div class="container-page">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-4" data-aos="fade-up">
                @foreach ($company->stats as $stat)
                    <div>
                        <dt class="font-display text-3xl font-bold text-white sm:text-4xl">
                            <span data-counter="{{ $stat['value'] }}">0</span><span class="text-brand-400">{{ $stat['suffix'] }}</span>
                        </dt>
                        <dd class="mt-1.5 text-xs font-medium uppercase tracking-[0.1em] text-ink-400">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ===================== LOGO KLIEN ===================== --}}
    @if ($clients->isNotEmpty())
        <section aria-labelledby="klien-heading" class="border-b border-ink-100 bg-white py-14 md:py-16">
            <div class="container-page">
                <h2 id="klien-heading" class="text-center text-[0.7rem] font-bold uppercase tracking-[0.24em] text-ink-400"
                    data-aos="fade-up">
                    Dipercaya oleh tim engineering &amp; manufaktur
                </h2>

                <ul class="mt-10 grid grid-cols-2 items-center gap-x-8 gap-y-10 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($clients as $client)
                        <li class="flex items-center justify-center"
                            data-aos="fade-up" data-aos-delay="{{ ($loop->index % 5) * 60 }}">
                            {{-- Grayscale saat diam, berwarna saat disentuh: deretan logo jadi
                                 terlihat menyatu tanpa mengalahkan warna brand. --}}
                            <img src="{{ asset($client->logo) }}"
                                 alt="Logo {{ $client->name }}"
                                 title="{{ $client->name }}{{ $client->industry ? ' ('.$client->industry.')' : '' }}"
                                 loading="lazy" decoding="async"
                                 class="max-h-12 w-auto max-w-[150px] object-contain opacity-60 grayscale transition-all duration-500 ease-out hover:opacity-100 hover:grayscale-0 sm:max-h-14">
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ===================== TENTANG SINGKAT ===================== --}}
    <section class="section">
        <div class="container-page">
            <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-16">
                <div class="lg:col-span-6" data-aos="fade-right">
                    <span class="eyebrow">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        Tentang {{ $company->name }}
                    </span>

                    <h2 class="mt-6 text-3xl font-bold leading-tight tracking-tight text-ink-900 sm:text-4xl">
                        Mitra additive manufacturing yang memahami
                        <span class="text-gradient-brand">kebutuhan teknis Anda</span>
                    </h2>

                    <p class="mt-6 text-base leading-relaxed text-ink-600">
                        {{ \Illuminate\Support\Str::limit($company->about, 420) }}
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        @foreach (array_slice($company->advantages, 0, 4) as $advantage)
                            <div class="flex gap-3 rounded-xl border border-ink-100 bg-ink-50/60 p-4">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-600/10 text-brand-600">
                                    <x-dynamic-component :component="'icons.'.$advantage['icon']" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-ink-900">{{ $advantage['title'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <a href="{{ route('about') }}" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition-colors hover:text-brand-800">
                        Kenali perusahaan kami lebih jauh
                        <x-icons.arrow-right class="h-4 w-4" />
                    </a>
                </div>

                <div class="lg:col-span-6" data-aos="fade-left">
                    <div class="relative">
                        {{-- Mosaik foto: satu foto utama besar + dua foto pendukung --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 overflow-hidden rounded-3xl border border-ink-100 bg-ink-100 shadow-card">
                                <img src="{{ asset('images/photos/machine-01.jpg') }}"
                                     alt="Mesin 3D printing sedang mencetak komponen berstruktur lattice"
                                     loading="lazy" decoding="async" width="900" height="675"
                                     class="aspect-[4/3] w-full object-cover">
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-ink-100 bg-ink-100 shadow-card">
                                <img src="{{ asset('images/photos/workshop-01.jpg') }}"
                                     alt="Suasana workshop dengan mesin cetak dan spool filamen"
                                     loading="lazy" decoding="async" width="900" height="675"
                                     class="aspect-[4/3] w-full object-cover">
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-ink-100 bg-ink-100 shadow-card">
                                <img src="{{ asset('images/photos/detail-01.jpg') }}"
                                     alt="Detail hotend dan filamen saat proses ekstrusi berjalan"
                                     loading="lazy" decoding="async" width="900" height="675"
                                     class="aspect-[4/3] w-full object-cover">
                            </div>
                        </div>

                        {{-- Kartu melayang: fakta, bukan sekadar hiasan --}}
                        <div class="absolute -top-5 right-3 rounded-2xl border border-brand-200 bg-brand-600 px-5 py-4 shadow-glow sm:right-6">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-white/70">Berdiri Sejak</p>
                            <p class="mt-1 font-display text-xl font-bold text-white">{{ $company->founded_year }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== PRE-PRINT ANALYZER ===================== --}}
    <section class="relative overflow-hidden bg-ink-950 py-20 md:py-28">
        <div class="blueprint-grid-dark absolute inset-0" aria-hidden="true"></div>
        <div class="absolute -right-32 top-0 h-96 w-96 rounded-full bg-brand-700/25 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -left-24 bottom-0 h-80 w-80 rounded-full bg-brand-900/40 blur-3xl" aria-hidden="true"></div>

        <div class="container-page relative">
            <div class="grid items-center gap-14 lg:grid-cols-12 lg:gap-16">

                <div class="lg:col-span-6" data-aos="fade-right">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-brand-300">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        Gratis &amp; Instan
                    </span>

                    <h2 class="mt-6 text-3xl font-bold leading-tight tracking-tight text-white sm:text-4xl lg:text-[2.6rem]">
                        Cek kelayakan cetak model Anda
                        <span class="text-brand-400">sebelum menghubungi kami</span>
                    </h2>

                    <p class="mt-6 max-w-xl text-base leading-relaxed text-ink-300 sm:text-lg">
                        Unggah file STL atau OBJ langsung di browser. Dalam hitungan detik Anda tahu apakah
                        model sudah siap dicetak, di mana masalahnya bila belum, serta perkiraan waktu dan biayanya.
                    </p>

                    <ul class="mt-9 space-y-4">
                        @foreach ([
                            ['icon' => 'cube', 'title' => 'Viewer 3D interaktif', 'text' => 'Putar, zoom, dan geser model. Lengkap dengan mode wireframe serta tujuh preset sudut pandang.'],
                            ['icon' => 'shield', 'title' => 'Analisis kelayakan otomatis', 'text' => 'Mesh tertutup, lubang, non-manifold edge, arah normal, ukuran, dan kesesuaian area cetak.'],
                            ['icon' => 'spark', 'title' => 'Estimasi lead time & biaya', 'text' => 'Pilih teknologi dan material, lead time pengerjaan serta biayanya langsung dihitung.'],
                        ] as $feature)
                            <li class="flex gap-4">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-600/20 text-brand-300">
                                    <x-dynamic-component :component="'icons.'.$feature['icon']" class="h-5 w-5" />
                                </span>
                                <div>
                                    <p class="font-semibold text-white">{{ $feature['title'] }}</p>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-400">{{ $feature['text'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('models') }}" class="btn-primary w-full sm:w-auto">
                            <x-icons.upload class="h-4 w-4" />
                            Mulai 3D Models
                        </a>
                        <a href="{{ route('technologies') }}" class="btn-ghost-light w-full sm:w-auto">
                            Lihat Teknologi Kami
                        </a>
                    </div>

                    <p class="mt-5 flex items-start gap-2.5 text-xs leading-relaxed text-ink-400">
                        <x-icons.lock class="mt-0.5 h-4 w-4 shrink-0 text-brand-400" />
                        File diproses sepenuhnya di perangkat Anda. Tidak ada berkas yang terkirim ke server
                        kecuali Anda sendiri yang meminta penawaran.
                    </p>
                </div>

                {{-- Pratinjau tampilan analyzer --}}
                <div class="lg:col-span-6" data-aos="fade-left" data-aos-delay="100">
                    <div class="relative mx-auto max-w-lg lg:max-w-none">
                        <div class="overflow-hidden rounded-3xl border border-white/10 bg-ink-900/70 shadow-glow backdrop-blur">

                            <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">
                                        <x-icons.cube class="h-4 w-4" />
                                    </span>
                                    <span class="font-display text-sm font-bold text-white">bracket-motor.stl</span>
                                </div>
                                <span class="rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-[0.65rem] font-bold text-emerald-300">
                                    &#128994; Ready to Print
                                </span>
                            </div>

                            <div class="grid gap-px bg-white/10 sm:grid-cols-3">
                                @foreach ([
                                    ['Dimensi', '84 × 52 × 26 mm'],
                                    ['Volume', '38,4 cm³'],
                                    ['Segitiga', '48.212'],
                                ] as [$label, $value])
                                    <div class="bg-ink-900/80 px-5 py-4">
                                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-500">{{ $label }}</p>
                                        <p class="mt-1 font-display text-sm font-bold text-white">{{ $value }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="space-y-2.5 px-5 py-5">
                                @foreach ([
                                    ['pass', 'Mesh tertutup (watertight)'],
                                    ['pass', 'Tidak ada lubang pada permukaan'],
                                    ['pass', 'Tidak ada non-manifold edge'],
                                    ['warn', 'Sisi tertipis 1,4 mm, rawan pada FDM'],
                                ] as [$state, $label])
                                    <div class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/5 px-4 py-2.5">
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $state === 'pass' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                        <span class="text-xs text-ink-300">{{ $label }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="border-t border-white/10 bg-gradient-to-br from-brand-700/80 to-brand-900/80 px-5 py-5">
                                <div class="flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-white/60">Estimasi untuk FDM / PLA</p>
                                        <p class="mt-1 font-display text-2xl font-bold text-white">Rp 68.000</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-white/60">Estimasi Lead Time</p>
                                        <p class="mt-1 font-display text-sm font-bold text-white">3–5 Hari Kerja</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p class="mt-4 text-center text-[0.7rem] text-ink-500">
                            Contoh tampilan hasil analisis pada halaman 3D Models.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== HIGHLIGHT LAYANAN ===================== --}}
    <section class="section bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Layanan Kami"
                title='Enam layanan yang saling melengkapi'
                description="Mulai dari mendigitalkan objek nyata sampai part siap pakai dengan hasil akhir rapi, seluruhnya dikerjakan satu tim di satu tempat." />

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <x-service-card
                        :service="$service"
                        :delay="($loop->index % 3) * 80"
                        :href="route('services').'#'.$service->slug" />
                @endforeach
            </div>

            <div class="mt-12 text-center" data-aos="fade-up">
                <a href="{{ route('services') }}" class="btn-primary">
                    Lihat Detail Seluruh Layanan
                    <x-icons.arrow-right class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    {{-- ===================== AJAKAN KUNJUNGAN ===================== --}}
    {{-- Full-bleed, tanpa kartu — sengaja memotong ritme grid di atas dan di bawahnya. --}}
    <section aria-labelledby="workshop-heading" class="relative overflow-hidden bg-brand-600">
        <h2 id="workshop-heading" class="sr-only">Di dalam workshop kami</h2>

        <div class="blueprint-grid-dark absolute inset-0" aria-hidden="true"></div>

        <div class="container-page relative py-14 md:py-16">
            <div class="max-w-2xl" data-aos="fade-up">
                <span class="text-[0.65rem] font-bold uppercase tracking-[0.2em] text-white/70">Di dalam workshop kami</span>
                <p class="mt-3 font-display text-xl font-bold leading-snug text-white sm:text-2xl">
                    Ingin diskusi teknis project atau lihat sample?
                </p>
                <p class="mt-2.5 text-sm leading-relaxed text-white/80">
                    Workshop kami terbuka untuk kunjungan pada jam kerja. Hubungi lebih dulu agar
                    tim dapat menyiapkan waktu khusus untuk Anda.
                </p>

                <a href="{{ route('about') }}#kontak"
                   class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-white transition-colors hover:text-brand-100">
                    Jadwalkan kunjungan
                    <x-icons.arrow-right class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    {{-- ===================== HIGHLIGHT TEKNOLOGI ===================== --}}
    <section class="section">
        <div class="container-page">
            <x-section-heading
                eyebrow="Teknologi"
                title='Empat teknologi cetak, satu standar kualitas'
                description="Setiap part punya kebutuhan berbeda. Kami memilihkan proses yang paling sesuai, bukan memaksakan satu-satunya mesin yang tersedia." />

            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($technologies as $technology)
                    <a href="{{ route('technologies') }}#{{ $technology->slug }}"
                       class="card-interactive group p-6"
                       data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl font-display text-base font-bold text-white shadow-[0_14px_30px_-14px_rgba(149,39,29,0.9)]"
                              style="background-color: {{ $technology->accent_color }}">
                            {{ $technology->code }}
                        </span>

                        <h3 class="mt-5 text-lg font-bold leading-snug text-ink-900 transition-colors group-hover:text-brand-600">
                            {{ $technology->name }}
                        </h3>

                        <p class="mt-3 flex-1 text-sm leading-relaxed text-ink-500">
                            {{ $technology->tagline }}
                        </p>

                        <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-600">
                            Pelajari
                            <x-icons.arrow-right class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== ALUR KERJA ===================== --}}
    <section class="section bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Cara Kerja"
                title='Empat langkah, tanpa tebak-tebakan'
                description="Alur kerja yang sama kami terapkan untuk satu unit prototipe maupun ratusan part produksi." />

            {{-- Timeline, bukan kartu — bentuknya sengaja beda dari section lain --}}
            <div class="relative mt-16">
                {{-- Garis penghubung: horizontal di desktop, vertikal di mobile --}}
                <span class="absolute left-[1.4rem] top-2 bottom-2 w-0.5 bg-gradient-to-b from-brand-200 via-brand-300 to-transparent lg:left-0 lg:right-0 lg:top-[1.4rem] lg:bottom-auto lg:h-0.5 lg:w-auto lg:bg-gradient-to-r lg:from-brand-200 lg:via-brand-400 lg:to-brand-200"
                      aria-hidden="true"></span>

                <ol class="relative grid gap-10 lg:grid-cols-4 lg:gap-8">
                    @foreach ([
                        ['step' => '01', 'title' => 'Kirim File & Kebutuhan', 'text' => 'Kirimkan file 3D beserta kebutuhan fungsi, jumlah, dan tenggat waktu Anda, atau cek sendiri lebih dulu di halaman 3D Models.'],
                        ['step' => '02', 'title' => 'Review & Penawaran', 'text' => 'Engineer kami meninjau file, menyarankan teknologi dan material, lalu mengirim penawaran.'],
                        ['step' => '03', 'title' => 'Produksi & Kontrol Mutu', 'text' => 'Part diproduksi lalu diperiksa dimensi dan tampilannya sebelum lanjut ke tahap akhir.'],
                        ['step' => '04', 'title' => 'Finishing & Pengiriman', 'text' => 'Post-processing sesuai permintaan, dikemas aman, lalu dikirim ke lokasi Anda.'],
                    ] as $index => $step)
                        <li class="relative flex gap-5 lg:block" data-aos="fade-up" data-aos-delay="{{ $index * 90 }}">
                            <span class="relative z-10 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-600 font-display text-sm font-bold text-white shadow-[0_10px_24px_-10px_rgba(149,39,29,0.9)] ring-4 ring-ink-50">
                                {{ $step['step'] }}
                            </span>

                            <div class="lg:mt-6 lg:pr-4">
                                <h3 class="text-lg font-bold leading-snug text-ink-900">{{ $step['title'] }}</h3>
                                <p class="mt-2.5 text-sm leading-relaxed text-ink-500">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- ===================== TESTIMONI ===================== --}}
    @if ($testimonials->isNotEmpty())
        @php
            // Rata-rata dihitung dari ulasan yang benar-benar ditampilkan saja,
            // bukan dari klaim total ulasan yang tidak dapat diverifikasi.
            $averageRating = round($testimonials->avg('rating'), 1);
        @endphp

        <section class="section">
            <div class="container-page">
                <x-section-heading
                    eyebrow="Kata Klien"
                    title='Yang dikatakan mereka yang sudah mencetak di sini'
                    description="Ulasan asli dari Google Review, dikutip apa adanya." />

                {{-- Ringkasan penilaian --}}
                <div class="mx-auto mt-8 flex max-w-md flex-col items-center gap-3 rounded-2xl border border-ink-100 bg-white px-6 py-5 shadow-card sm:flex-row sm:justify-center sm:gap-6"
                     data-aos="fade-up">
                    <div class="flex items-center gap-2">
                        <span class="font-display text-3xl font-bold text-ink-900">{{ number_format($averageRating, 1, ',', '.') }}</span>
                        <span class="flex" aria-hidden="true">
                            @for ($i = 0; $i < 5; $i++)
                                <x-icons.star class="h-4 w-4 text-accent-500" />
                            @endfor
                        </span>
                    </div>
                    <p class="text-center text-xs leading-relaxed text-ink-500 sm:text-left">
                        Rata-rata dari <strong class="font-semibold text-ink-700">{{ $testimonials->count() }} ulasan</strong>
                        yang ditampilkan di halaman ini
                    </p>
                </div>

                {{-- Panjang ulasan sangat beragam, jadi memakai kolom masonry
                     agar tidak ada kartu yang menyisakan ruang kosong besar. --}}
                <div class="mt-12 gap-6 sm:columns-2 lg:columns-3">
                    @foreach ($testimonials as $testimonial)
                        <figure class="mb-6 break-inside-avoid rounded-2xl border border-ink-100 bg-white p-6 shadow-card transition-all duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-card-hover"
                                data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 70 }}">
                            <div class="flex" aria-label="{{ $testimonial->rating }} dari 5 bintang">
                                @for ($i = 0; $i < $testimonial->rating; $i++)
                                    <x-icons.star class="h-4 w-4 text-accent-500" />
                                @endfor
                            </div>

                            <blockquote class="mt-4 whitespace-pre-line text-sm leading-relaxed text-ink-700">
                                {{ $testimonial->quote }}
                            </blockquote>

                            <figcaption class="mt-5 flex items-center gap-3 border-t border-ink-100 pt-4">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-600/10 font-display text-xs font-bold text-brand-700"
                                      aria-hidden="true">
                                    {{ $testimonial->initials }}
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-bold text-ink-900">{{ $testimonial->name }}</span>
                                    <span class="block text-[0.7rem] text-ink-400">
                                        {{ $testimonial->source }}@if ($testimonial->reviewed_label) &middot; {{ $testimonial->reviewed_label }}@endif
                                    </span>
                                </span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===================== CTA ===================== --}}
    <x-cta-band />

@endsection

@push('scripts')
    {{-- Menyalakan animasi 3D pada hero (kanvas data-auth-scene). --}}
    @vite('resources/js/auth-scene.js')
@endpush
