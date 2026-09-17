@extends('layouts.app')

@section('title', '3D Printing Guide')
@section('description', 'Panduan menyiapkan file 3D sebelum diunggah: format yang didukung, ketebalan dinding minimum, support, overhang, batas ukuran cetak, pilihan material, serta tips mendesain model agar siap dicetak.')

@section('content')

    @php
        $sections = [
            ['id' => 'build-size', 'label' => 'Build Size'],
            ['id' => 'persiapan', 'label' => 'Persiapan Model'],
            ['id' => 'ketebalan-dinding', 'label' => 'Ketebalan Dinding'],
            ['id' => 'support', 'label' => 'Support Structure'],
            ['id' => 'overhang', 'label' => 'Overhang'],
            ['id' => 'ukuran', 'label' => 'Ukuran Maksimum'],
            ['id' => 'material', 'label' => 'Material'],
            ['id' => 'tips', 'label' => 'Tips Mendesain'],
            ['id' => 'faq', 'label' => 'FAQ'],
        ];

        $formatList = \App\Support\ModelFormat::label();

        // Angka milimeter ditulis dengan koma desimal, dan nol di belakang koma
        // dibuang supaya "2,0 mm" cukup terbaca "2 mm".
        $mm = fn ($value) => rtrim(rtrim(number_format((float) $value, 1, ',', '.'), '0'), ',');

        // Batas ukuran material ditulis "250 × 250 × 300 mm".
        $sizeLabel = fn (?array $size) => \App\Support\MaterialCatalog::sizeLabel($size) ?? '-';
    @endphp

    <x-page-hero
        eyebrow="Panduan"
        current="3D Printing Guide"
        title='Siapkan model Anda <span class="text-brand-400">sebelum diunggah</span>'
        description="Sebagian besar kegagalan cetak sudah dapat dicegah sejak tahap desain. Panduan ini merangkum aturan dasar yang kami pakai saat meninjau file, mulai dari format, ketebalan dinding, support, hingga batas area cetak tiap teknologi.">
        <x-slot:actions>
            <a href="{{ route('models') }}" class="btn-primary w-full sm:w-auto">
                Unggah Model Sekarang
                <x-icons.arrow-right class="h-4 w-4" />
            </a>
            <a href="#persiapan" class="btn-ghost-light w-full sm:w-auto">Baca Panduan</a>
        </x-slot:actions>
    </x-page-hero>

    {{-- ===================== NAVIGASI CEPAT ===================== --}}
    <section class="border-b border-ink-100 bg-white/80 py-6 backdrop-blur">
        <div class="container-page">
            <div class="flex flex-wrap items-center justify-center gap-2.5">
                @foreach ($sections as $section)
                    <a href="#{{ $section['id'] }}"
                       class="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-600">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        {{ $section['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== BUILD SIZE ===================== --}}
    <section id="build-size" class="section scroll-mt-24">
        <div class="container-page">
            <x-section-heading
                eyebrow="Build Size"
                title="3D Printing Build Size"
                description="Ukuran cetak maksimum dan minimum untuk setiap kombinasi teknologi dan material. Pastikan dimensi model Anda berada di dalam rentang ini sebelum mengunggah file." />

            <div class="mt-12 overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-card" data-aos="fade-up">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <caption class="sr-only">Ukuran cetak maksimum dan minimum tiap teknologi dan material</caption>
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80">
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Teknologi</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Material</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Ukuran Maks. (P × L × T) mm</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Ukuran Min. (P × L × T) mm</th>
                        </tr>
                    </thead>

                    @foreach ($technologies as $technology)
                        <tbody class="divide-y divide-ink-100 border-b border-ink-100 last:border-0">
                            @foreach ($technology['materials'] as $material)
                                <tr class="transition-colors hover:bg-brand-50/50">
                                    @if ($loop->first)
                                        <th scope="rowgroup"
                                            rowspan="{{ count($technology['materials']) }}"
                                            class="border-r border-ink-100 bg-ink-50/40 px-6 py-5 align-top font-semibold text-ink-900">
                                            {{ $technology['label'] }}
                                        </th>
                                    @endif

                                    <td class="px-6 py-5 font-semibold text-ink-800">
                                        <a href="#material-{{ $material['slug'] }}" class="transition-colors hover:text-brand-600">
                                            {{ $material['name'] }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-5 text-brand-700">{{ $sizeLabel($material['maxSize']) }}</td>
                                    <td class="px-6 py-5 text-ink-600">
                                        {{ $sizeLabel($material['minSize']) }}
                                        @if ($material['minSizeSlender'])
                                            <span class="text-ink-400">/ {{ $sizeLabel($material['minSizeSlender']) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>

            <p class="mt-5 text-sm leading-relaxed text-ink-500" data-aos="fade-up">
                Ukuran minimum ditulis dua angka bila part memanjang: model padat terkecil yang masih dapat dicetak
                berukuran 5 × 5 × 5 mm, sedangkan part berbentuk batang boleh setipis 10 × 2 × 2 mm. Model yang memenuhi
                salah satunya sudah dianggap layak. Batas ini juga dipakai sebagai validasi saat Anda menyimpan
                spesifikasi pada halaman 3D Models, dan model yang melebihi ukuran maksimum tetap dapat dikerjakan
                dengan cara dipecah menjadi beberapa bagian.
            </p>
        </div>
    </section>

    {{-- ===================== PERSIAPAN MODEL ===================== --}}
    <section id="persiapan" class="section scroll-mt-24 bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Langkah Pertama"
                title="Persiapan Model"
                description="Tiga hal ini diperiksa lebih dulu begitu file Anda masuk. Model yang lolos ketiganya hampir selalu dapat langsung di-slice." />

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    [
                        'icon' => 'layers',
                        'title' => 'Format file yang didukung',
                        'text' => 'Kami menerima '.$formatList.' dengan ukuran maksimal '.$previewMaxFileSizeMb.' MB per file. STL paling ringan dan paling aman; OBJ dan 3MF dipakai bila model Anda terdiri dari beberapa bagian; STEP/STP diterima langsung dari CAD dan ditesselasi otomatis di browser. Ekspor dalam satuan milimeter agar skalanya terbaca benar.',
                    ],
                    [
                        'icon' => 'shield',
                        'title' => 'Pastikan model tidak rusak',
                        'text' => 'Mesh harus tertutup rapat (watertight): tanpa lubang, tanpa tepi non-manifold, dan seluruh normal menghadap ke luar. Mesh yang belum tertutup membuat slicer tidak dapat membedakan bagian dalam dan luar part.',
                    ],
                    [
                        'icon' => 'axis',
                        'title' => 'Pastikan ukurannya sudah sesuai',
                        'text' => 'Sisi terkecil model minimal '.$mm($limits['min_dimension_mm']).' mm; di bawah '.$mm($limits['warn_dimension_mm']).' mm fiturnya rawan patah. Salah satuan adalah penyebab tersering: model yang didesain dalam inci akan terbaca 25,4 kali lebih kecil.',
                    ],
                ] as $item)
                    <div class="card p-7" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                            <x-dynamic-component :component="'icons.'.$item['icon']" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-5 font-display text-base font-bold text-ink-900">{{ $item['title'] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-ink-500">{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 rounded-2xl border border-ink-100 bg-ink-50/70 p-6" data-aos="fade-up">
                <h3 class="font-display text-base font-bold text-ink-900">Diperiksa otomatis saat diunggah</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-500">
                    Begitu file dibuka, browser Anda langsung menjalankan pemeriksaan berikut dan menampilkan hasilnya
                    pada viewer 3D. Berkas belum dikirim ke server pada tahap ini.
                </p>

                <ul class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'Mesh tertutup (watertight)',
                        'Lubang pada permukaan',
                        'Non-manifold edge',
                        'Arah normal muka',
                        'Ukuran model',
                        'Kesesuaian dengan area cetak',
                    ] as $check)
                        <li class="flex items-start gap-2.5 text-sm text-ink-600">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                <x-icons.check class="h-3 w-3" />
                            </span>
                            {{ $check }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ===================== KETEBALAN DINDING ===================== --}}
    <section id="ketebalan-dinding" class="section scroll-mt-24">
        <div class="container-page">
            <x-section-heading
                eyebrow="Wall Thickness"
                title="Ketebalan Dinding"
                description="Dinding adalah bagian yang paling sering membuat part gagal. Setiap teknologi punya batas minimum sendiri karena cara membentuk materialnya berbeda." />

            <div class="mt-12 overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-card" data-aos="fade-up">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <caption class="sr-only">Ketebalan dinding minimum tiap teknologi</caption>
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80">
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Teknologi</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Dinding Minimum</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Disarankan</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($technologies as $technology)
                            <tr class="transition-colors hover:bg-brand-50/50">
                                <th scope="row" class="px-6 py-5 font-semibold text-ink-900">{{ $technology['code'] }}</th>
                                <td class="px-6 py-5 font-semibold text-brand-700">{{ $mm($technology['minWallMm']) }} mm</td>
                                <td class="px-6 py-5 text-ink-600">{{ $mm($technology['minWallMm'] * 1.5) }} mm ke atas</td>
                                <td class="px-6 py-5 text-ink-600">
                                    @switch($technology['code'])
                                        @case('FDM') Dinding mengikuti lebar nozzle, jadi tebalnya sebaiknya kelipatan lebar jalur. @break
                                        @case('SLA') Dinding tipis bisa melengkung saat part ditarik dari vat tiap lapisan. @break
                                        @case('MJF') Dinding sangat tipis rawan melengkung sewaktu powder cake didinginkan. @break
                                        @case('SLM') Tegangan termal tinggi; dinding tipis pada part besar mudah retak. @break
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <div class="card p-7" data-aos="fade-up">
                    <h3 class="font-display text-base font-bold text-ink-900">Dampak jika dinding terlalu tipis</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-relaxed text-ink-600">
                        @foreach ([
                            'Dinding tidak terbentuk penuh sehingga muncul celah atau lubang pada permukaan.',
                            'Part patah saat support dilepas atau ketika baru dipegang.',
                            'Bagian tipis melengkung mengikuti panas, membuat dimensinya meleset.',
                            'Fitur halus seperti teks timbul, sirip, dan jaring hilang sama sekali.',
                        ] as $impact)
                            <li class="flex items-start gap-2.5">
                                <span class="mt-1.5 inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>
                                {{ $impact }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card p-7" data-aos="fade-up" data-aos-delay="80">
                    <h3 class="font-display text-base font-bold text-ink-900">Cara memeriksanya</h3>
                    <p class="mt-4 text-sm leading-relaxed text-ink-600">
                        Buka model Anda di viewer 3D, lalu aktifkan mode <span class="font-semibold text-ink-800">Wall Thickness</span>.
                        Bagian yang berada di bawah batas aman diwarnai merah, sisanya hijau, sehingga terlihat langsung
                        bagian mana yang perlu ditebalkan sebelum dicetak.
                    </p>
                    <p class="mt-3 text-sm leading-relaxed text-ink-600">
                        Untuk part SLA berongga (Hollow Model), tebal cangkang dapat diatur sendiri antara
                        <span class="font-semibold text-ink-800">{{ $mm($hollowWall['min']) }}–{{ $mm($hollowWall['max']) }} mm</span>
                        dengan nilai bawaan {{ $mm($hollowWall['default']) }} mm.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== SUPPORT STRUCTURE ===================== --}}
    <section id="support" class="section scroll-mt-24 bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Support"
                title="Support Structure"
                description="Support adalah material sementara yang menahan bagian menggantung selama proses cetak, lalu dilepas setelah part selesai. Support menambah material, waktu, dan biaya, jadi semakin sedikit semakin baik." />

            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                <div class="card p-7" data-aos="fade-up">
                    <h3 class="font-display text-base font-bold text-ink-900">Kapan model membutuhkan support</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-relaxed text-ink-600">
                        @foreach ([
                            'Ada permukaan yang menggantung lebih dari '.$supportAngleDeg.'° dari bidang tegak.',
                            'Terdapat bagian yang sama sekali tidak terhubung ke meja cetak (floating geometry).',
                            'Jembatan mendatar yang bentangannya panjang tanpa penopang di bawahnya.',
                            'Part tinggi dan ramping yang berisiko goyah saat dicetak.',
                        ] as $need)
                            <li class="flex items-start gap-2.5">
                                <span class="mt-1.5 inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>
                                {{ $need }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card p-7" data-aos="fade-up" data-aos-delay="80">
                    <h3 class="font-display text-base font-bold text-ink-900">Cara mengurangi kebutuhan support</h3>
                    <ul class="mt-4 space-y-3 text-sm leading-relaxed text-ink-600">
                        @foreach ([
                            'Putar orientasi model sehingga bidang terluas menempel di meja cetak.',
                            'Ubah overhang tajam menjadi chamfer atau fillet agar sudutnya lebih landai.',
                            'Ganti lubang horizontal berbentuk lingkaran dengan bentuk tetesan air atau belah ketupat.',
                            'Pecah model menjadi beberapa bagian, lalu satukan kembali setelah dicetak.',
                            'Pilih MJF bila geometrinya rumit, karena part tertopang serbuk sehingga tidak perlu support sama sekali.',
                        ] as $tip)
                            <li class="flex items-start gap-2.5">
                                <span class="mt-1.5 inline-block h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500"></span>
                                {{ $tip }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-aos="fade-up">
                @foreach ($technologies as $technology)
                    <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                        <p class="font-display text-sm font-bold text-ink-900">{{ $technology['code'] }}</p>
                        <p class="mt-2 text-sm font-semibold {{ $technology['needsSupport'] ? 'text-amber-700' : 'text-emerald-700' }}">
                            {{ $technology['needsSupport'] ? 'Perlu support' : 'Tanpa support' }}
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-ink-500">
                            {{ $technology['needsSupport']
                                ? 'Bagian menggantung ditopang struktur sementara yang dilepas setelah cetak.'
                                : 'Part tertopang serbuk di sekelilingnya, jadi geometri rumit bebas dibuat.' }}
                        </p>
                    </div>
                @endforeach
            </div>

            <p class="mt-6 text-sm leading-relaxed text-ink-500" data-aos="fade-up">
                Support dapat Anda aktifkan atau matikan sendiri lewat <span class="font-semibold text-ink-700">Edit Specification</span>
                pada setiap model, dan pengaruhnya terhadap waktu dan biaya langsung terlihat pada estimasi.
            </p>
        </div>
    </section>

    {{-- ===================== OVERHANG ===================== --}}
    <section id="overhang" class="section scroll-mt-24">
        <div class="container-page">
            <x-section-heading
                eyebrow="Overhang"
                title="Sudut Overhang yang Aman"
                description="Sudut diukur dari bidang tegak: dinding lurus berdiri 0°, sedangkan langit-langit yang benar-benar mendatar 90°. Semakin mendekati mendatar, semakin sulit lapisan barunya ditopang lapisan di bawahnya." />

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    [
                        'range' => '0° – '.$overhang['safe_deg'].'°',
                        'label' => 'Aman',
                        'color' => $overhang['colors']['safe'],
                        'text' => 'Setiap lapisan masih tertopang cukup oleh lapisan di bawahnya. Tidak memerlukan support.',
                    ],
                    [
                        'range' => $overhang['safe_deg'].'° – '.$overhang['warn_deg'].'°',
                        'label' => 'Perlu diperhatikan',
                        'color' => $overhang['colors']['warn'],
                        'text' => 'Masih dapat dicetak, tetapi permukaannya berpotensi kasar. Support disarankan bila permukaan tersebut terlihat.',
                    ],
                    [
                        'range' => 'di atas '.$overhang['warn_deg'].'°',
                        'label' => 'Berisiko',
                        'color' => $overhang['colors']['critical'],
                        'text' => 'Lapisan hampir tidak memiliki tumpuan. Support wajib, atau orientasi model perlu diubah.',
                    ],
                ] as $band)
                    <div class="card p-7" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                        <span class="inline-flex items-center gap-2.5 rounded-full border border-ink-100 bg-white px-3.5 py-1.5">
                            <span class="inline-block h-3 w-3 rounded-full" style="background-color: {{ $band['color'] }}"></span>
                            <span class="text-xs font-bold uppercase tracking-[0.14em] text-ink-600">{{ $band['label'] }}</span>
                        </span>
                        <p class="mt-5 font-display text-xl font-bold text-ink-900">{{ $band['range'] }}</p>
                        <p class="mt-3 text-sm leading-relaxed text-ink-500">{{ $band['text'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 rounded-2xl border border-ink-100 bg-white p-7 shadow-card" data-aos="fade-up">
                <h3 class="font-display text-base font-bold text-ink-900">Kapan support diperlukan</h3>
                <p class="mt-3 text-sm leading-relaxed text-ink-600">
                    Sebagai patokan praktis, permukaan yang menggantung lebih dari
                    <span class="font-semibold text-ink-900">{{ $overhang['safe_deg'] }}°</span> sebaiknya ditopang, dan di atas
                    <span class="font-semibold text-ink-900">{{ $overhang['warn_deg'] }}°</span> hampir selalu wajib ditopang.
                    Aktifkan mode <span class="font-semibold text-ink-800">Overhang</span> pada viewer 3D untuk melihat sendiri
                    bagian mana yang masuk kategori mana, karena model diwarnai persis mengikuti tiga rentang di atas.
                </p>
            </div>
        </div>
    </section>

    {{-- ===================== UKURAN MAKSIMUM ===================== --}}
    <section id="ukuran" class="section scroll-mt-24 bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Build Volume"
                title="Ukuran Maksimum Model"
                description="Model harus muat seluruhnya di dalam area cetak mesin. Bila melebihi, model perlu diperkecil, diputar orientasinya, atau dipecah menjadi beberapa bagian yang disatukan setelah dicetak." />

            <div class="mt-12 overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-card" data-aos="fade-up">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <caption class="sr-only">Area cetak maksimum tiap teknologi</caption>
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80">
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Teknologi</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Area Cetak (P × L × T)</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Cocok Untuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($technologies as $technology)
                            <tr class="transition-colors hover:bg-brand-50/50">
                                <th scope="row" class="px-6 py-5">
                                    <span class="block font-semibold text-ink-900">{{ $technology['code'] }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-400">{{ $technology['name'] }}</span>
                                </th>
                                <td class="px-6 py-5 font-semibold text-brand-700">
                                    {{ $technology['buildVolume']['x'] }} × {{ $technology['buildVolume']['y'] }} × {{ $technology['buildVolume']['z'] }} mm
                                </td>
                                <td class="px-6 py-5 text-ink-600">{{ $technology['description'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <div class="card p-7" data-aos="fade-up">
                    <h3 class="font-display text-base font-bold text-ink-900">Mesin FDM yang tersedia</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">
                        Pada halaman 3D Models, setiap model mendapat satu mesin sendiri. Ukuran build plate mengikuti mesin
                        yang Anda pilih.
                    </p>

                    <ul class="mt-5 space-y-3">
                        @foreach ($printers as $printer)
                            <li class="flex items-start justify-between gap-4 border-b border-ink-100 pb-3 last:border-0 last:pb-0">
                                <span class="text-sm font-semibold text-ink-800">{{ $printer['name'] }}</span>
                                <span class="shrink-0 text-sm text-ink-500">
                                    {{ $printer['buildVolume']['x'] }} × {{ $printer['buildVolume']['y'] }} × {{ $printer['buildVolume']['z'] }} mm
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card p-7" data-aos="fade-up" data-aos-delay="80">
                    <h3 class="font-display text-base font-bold text-ink-900">Batas lain yang perlu diingat</h3>
                    <dl class="mt-5 space-y-4 text-sm">
                        @foreach ([
                            ['Ukuran minimum sisi', $mm($limits['min_dimension_mm']).' mm', 'Di bawah ini part terlalu kecil untuk dibentuk dengan andal.'],
                            ['Perlu perhatian khusus', 'di bawah '.$mm($limits['warn_dimension_mm']).' mm', 'Masih dapat dicetak, tetapi rawan patah, terutama pada FDM.'],
                            ['Ukuran file', 'maksimal '.$previewMaxFileSizeMb.' MB per file', 'Batas agar pratinjau dan analisis tetap berjalan lancar di browser.'],
                            ['Jumlah model', 'maksimal '.$maxModels.' model', 'Banyaknya model dalam satu permintaan penawaran.'],
                        ] as [$term, $value, $note])
                            <div class="border-b border-ink-100 pb-4 last:border-0 last:pb-0">
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="font-semibold text-ink-800">{{ $term }}</dt>
                                    <dd class="shrink-0 text-right font-semibold text-brand-700">{{ $value }}</dd>
                                </div>
                                <p class="mt-1 text-xs leading-relaxed text-ink-500">{{ $note }}</p>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </section>

    {{-- ===================== MATERIAL ===================== --}}
    <section id="material" class="section scroll-mt-24">
        <div class="container-page">
            <x-section-heading
                eyebrow="Material"
                title="Material yang Tersedia"
                description="Pilihan material menentukan kekuatan, tampilan, dan biaya part. Berikut ringkasan singkat beserta kelebihan dan kekurangan masing-masing." />

            <div class="mt-12 space-y-8">
                @foreach ($technologies as $technology)
                    <div class="rounded-3xl border border-ink-100 bg-white p-7 shadow-card" data-aos="fade-up">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <h3 class="font-display text-lg font-bold text-ink-900">{{ $technology['label'] }}</h3>
                            <span class="text-sm text-ink-400">{{ $technology['name'] }}</span>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            @foreach ($technology['materials'] as $material)
                                {{-- Anchor tujuan tombol "Learn More" di Edit Specification. --}}
                                <div id="material-{{ $material['slug'] }}" class="scroll-mt-28 rounded-2xl border border-ink-100 bg-ink-50/60 p-5">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="font-display text-sm font-bold text-ink-900">{{ $material['name'] }}</p>
                                        <span class="flex items-center gap-1.5">
                                            @foreach ($material['colors'] as $color)
                                                <span class="inline-block h-3.5 w-3.5 rounded-full border border-ink-200"
                                                      style="background: {{ $color['hex'] }}"
                                                      title="{{ $color['label'] }}"></span>
                                            @endforeach
                                        </span>
                                    </div>

                                    <p class="mt-2 text-xs leading-relaxed text-ink-500">{{ $material['description'] }}</p>

                                    @if ($material['characteristics'])
                                        <dl class="mt-4 space-y-1">
                                            @foreach ($material['characteristics'] as $label => $value)
                                                <div class="flex items-start justify-between gap-3 text-xs">
                                                    <dt class="text-ink-400">{{ $label }}</dt>
                                                    <dd class="text-right font-semibold text-ink-700">{{ $value }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    @endif

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-emerald-700">Kelebihan</p>
                                            <ul class="mt-1.5 space-y-1">
                                                @foreach ($material['pros'] as $pro)
                                                    <li class="text-xs leading-relaxed text-ink-600">+ {{ $pro }}</li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        <div>
                                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-brand-700">Kekurangan</p>
                                            <ul class="mt-1.5 space-y-1">
                                                @foreach ($material['cons'] as $con)
                                                    <li class="text-xs leading-relaxed text-ink-600">− {{ $con }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>

                                    <dl class="mt-4 border-t border-ink-200/70 pt-3 text-xs">
                                        <div class="flex items-start justify-between gap-3">
                                            <dt class="text-ink-400">Ukuran maksimum</dt>
                                            <dd class="text-right font-semibold text-ink-700">{{ $sizeLabel($material['maxSize']) }}</dd>
                                        </div>
                                        <div class="mt-1 flex items-start justify-between gap-3">
                                            <dt class="text-ink-400">Ukuran minimum</dt>
                                            <dd class="text-right font-semibold text-ink-700">
                                                {{ $sizeLabel($material['minSize']) }}
                                                @if ($material['minSizeSlender'])
                                                    <span class="font-normal text-ink-400">/ {{ $sizeLabel($material['minSizeSlender']) }}</span>
                                                @endif
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-6 text-center text-sm text-ink-400" data-aos="fade-up">
                Belum yakin material mana yang tepat? Ceritakan fungsi part Anda pada catatan penawaran. Tim kami akan
                merekomendasikan pilihan yang paling sesuai.
            </p>
        </div>
    </section>

    {{-- ===================== TIPS MENDESAIN ===================== --}}
    <section id="tips" class="section scroll-mt-24 bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Best Practice"
                title="Tips Mendesain Model"
                description="Empat kebiasaan sederhana yang mencegah sebagian besar revisi file sebelum produksi." />

            <div class="mt-12 grid gap-6 sm:grid-cols-2">
                @foreach ([
                    [
                        'icon' => 'layers',
                        'title' => 'Hindari bagian yang terlalu tipis',
                        'text' => 'Tebalkan sirip, teks timbul, dan jaring hingga melewati dinding minimum teknologi yang dipilih. Bila sebuah fitur harus tipis, pertimbangkan SLA yang batas dindingnya paling kecil.',
                    ],
                    [
                        'icon' => 'orbit',
                        'title' => 'Hindari floating geometry',
                        'text' => 'Bagian yang melayang tanpa hubungan ke bodi utama tidak dapat dicetak tanpa support besar. Pastikan setiap bagian benar-benar menempel pada model.',
                    ],
                    [
                        'icon' => 'grid',
                        'title' => 'Pastikan semua bagian menyatu',
                        'text' => 'Gabungkan (boolean union) seluruh body menjadi satu solid sebelum diekspor. Permukaan yang sekadar bersentuhan atau bertumpuk memunculkan tepi non-manifold.',
                    ],
                    [
                        'icon' => 'axis',
                        'title' => 'Gunakan ukuran yang realistis',
                        'text' => 'Desain dalam milimeter, sisakan celah suaian sekitar 0,2–0,4 mm untuk part yang harus berpasangan, dan periksa dimensinya sekali lagi sebelum mengekspor.',
                    ],
                ] as $tip)
                    <div class="card p-7" data-aos="fade-up" data-aos-delay="{{ $loop->index * 60 }}">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                            <x-dynamic-component :component="'icons.'.$tip['icon']" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-5 font-display text-base font-bold text-ink-900">{{ $tip['title'] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-ink-500">{{ $tip['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== FAQ ===================== --}}
    <section id="faq" class="section scroll-mt-24">
        <div class="container-page">
            <x-section-heading
                eyebrow="FAQ"
                title="Pertanyaan yang Sering Diajukan"
                description="Jawaban singkat untuk hal-hal yang paling sering ditanyakan sebelum mengunggah model." />

            <div class="mx-auto mt-12 max-w-3xl space-y-3">
                @foreach ([
                    [
                        'q' => 'Mengapa model saya tidak bisa dicetak?',
                        'a' => 'Penyebab tersering ada empat: mesh belum tertutup rapat sehingga slicer tidak dapat menentukan bagian dalam part, dindingnya lebih tipis daripada batas minimum teknologi, ukurannya melebihi area cetak mesin, atau satuan filenya salah sehingga model terbaca jauh lebih kecil. Hasil pemeriksaan pada viewer 3D menyebutkan persis masalah mana yang ditemukan pada file Anda.',
                    ],
                    [
                        'q' => 'Mengapa harga berubah setelah spesifikasi diubah?',
                        'a' => 'Estimasi dihitung dari volume material yang benar-benar dipakai, lama mesin bekerja, serta pekerjaan tambahan setelah cetak. Mengganti teknologi, material, finishing, jumlah, support, atau hollow mengubah salah satu dari ketiganya, jadi angkanya ikut menyesuaikan seketika.',
                    ],
                    [
                        'q' => 'Kapan saya harus menggunakan support?',
                        'a' => 'Gunakan support bila ada permukaan yang menggantung lebih dari '.$overhang['safe_deg'].'° dari bidang tegak, ada bagian yang tidak terhubung ke meja cetak, atau ada bentangan mendatar yang panjang. Untuk MJF support tidak diperlukan sama sekali karena part tertopang serbuk di sekelilingnya.',
                    ],
                    [
                        'q' => 'Format file apa yang direkomendasikan?',
                        'a' => 'STL adalah pilihan paling aman dan paling ringan; ekspor dengan resolusi tinggi agar permukaan lengkung tetap mulus. OBJ dan 3MF dipakai bila model terdiri dari beberapa bagian, sedangkan STEP/STP dapat dikirim apa adanya dari software CAD Anda. Seluruhnya kami terima dengan ukuran maksimal '.$previewMaxFileSizeMb.' MB per file.',
                    ],
                    [
                        'q' => 'Apakah file saya langsung terkirim ke server?',
                        'a' => 'Tidak. Pratinjau, analisis, dan seluruh estimasi berjalan di browser Anda sendiri. File baru dikirim ke server ketika Anda menekan "Minta Penawaran", dan hanya dapat diakses tim internal kami.',
                    ],
                    [
                        'q' => 'Bagaimana jika model saya lebih besar daripada area cetak?',
                        'a' => 'Coba ubah orientasinya terlebih dahulu, karena banyak model yang sebenarnya masih muat setelah diputar. Bila tetap tidak muat, perkecil skalanya, pilih teknologi dengan area cetak lebih besar, atau pecah model menjadi beberapa bagian yang disatukan kembali setelah dicetak.',
                    ],
                ] as $faq)
                    <details class="card-section" data-aos="fade-up">
                        <summary class="card-section-summary">{{ $faq['q'] }}</summary>
                        <div class="card-section-body">
                            <p class="text-sm leading-relaxed text-ink-600">{{ $faq['a'] }}</p>
                        </div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <x-cta-band
        eyebrow="Sudah Siap"
        title="Model Anda siap diunggah?"
        description="Unggah file STL atau OBJ Anda, lihat hasil analisis kelayakan cetaknya, lalu kirimkan permintaan penawaran langsung dari halaman 3D Models."
        primary-label="Unggah 3D Model"
        :primary-href="route('models')" />

@endsection
