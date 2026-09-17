{{--
    Card satu mesin printer: viewer 3D, orientasi, skala, infill, hollow, warna,
    analisis kelayakan, pengaturan produksi, dan rincian estimasinya.

    Dipakai di dua tempat:
      - halaman 3D Models sebagai isi <template>, dikloning JavaScript untuk
        memproses tiap model secara headless (metrik, analisis, estimasi, thumbnail);
      - halaman Viewer 3D, dirender langsung sebagai satu-satunya card.

    `cardId` mengganti angka unik pada atribut id/for/name supaya beberapa card
    tidak berbagi satu grup radio. Sebagai template nilainya `__CARD__` dan
    diganti saat kloning; pada halaman viewer nilainya sudah pasti.
--}}
@props(['cardId' => '__CARD__'])

@php
    // Component anonim tidak mewarisi data halaman, jadi seluruh pilihan
    // produksinya dibaca langsung dari config/printing.php di sini. Dengan
    // begitu halaman daftar dan halaman viewer selalu menampilkan pilihan yang
    // sama tanpa perlu meneruskan belasan variabel.
    $resolutions = \App\Support\PrintResolution::all();
    $defaultResolution = \App\Support\PrintResolution::default();

    $printers = \App\Support\Printer::all();
    $defaultPrinter = \App\Support\Printer::default();
    $customPrinterKey = config('printing.printers.custom_key', 'custom');
    $customPrinterLimits = config('printing.printers.custom_limits');

    $infillDensities = \App\Support\InfillPattern::densities();
    $infillPatterns = \App\Support\InfillPattern::all();
    $defaultInfillPattern = \App\Support\InfillPattern::default();

    $hollow = config('printing.hollow');
    $materialColors = \App\Support\MaterialColor::all();
    $defaultMaterialColor = \App\Support\MaterialColor::default();

    $finishings = \App\Support\Finishing::all();
    $defaultFinishing = \App\Support\Finishing::default();

    $scalePresets = [50, 80, 100, 120, 150];
@endphp

<article class="overflow-hidden rounded-3xl border-2 border-ink-100 bg-white shadow-card">

    {{-- Kepala card --}}
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-ink-100 bg-ink-50/60 px-6 py-5">
        <div class="flex min-w-0 items-center gap-3">
            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-icons.printer class="h-5.5 w-5.5" />
            </span>
            <div class="min-w-0">
                <p class="font-display text-base font-bold text-ink-900" data-card-title>Printer 1</p>
                <p class="truncate text-xs text-ink-500" data-card-file>model.stl</p>
            </div>
        </div>

        {{-- Status kelayakan cetak sengaja tidak ditampilkan kepada pelanggan:
             hasilnya tetap dihitung dan ikut terkirim bersama penawaran untuk
             ditindaklanjuti engineer lewat dashboard admin. --}}
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50" data-action="remove">
                <x-icons.trash class="h-4 w-4" />
                Hapus
            </button>
        </div>
    </header>

    <div class="grid gap-6 p-6 lg:grid-cols-12">

        {{-- ---------- Viewer 3D ---------- --}}
        <div class="lg:col-span-7">
            <div class="overflow-hidden rounded-2xl border border-ink-100">
                <div class="border-b border-ink-100 px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1 rounded-xl border border-ink-200 bg-ink-50 p-1" role="group" aria-label="Mode tampilan model">
                            <button type="button" class="viewer-tool border-0 bg-transparent px-2.5 py-1.5" data-mode="solid" aria-pressed="true">Solid</button>
                            <button type="button" class="viewer-tool border-0 bg-transparent px-2.5 py-1.5" data-mode="wireframe" aria-pressed="false">Wireframe</button>
                            <button type="button" class="viewer-tool border-0 bg-transparent px-2.5 py-1.5" data-mode="transparent" aria-pressed="false">Transparan</button>
                        </div>

                        <div class="flex items-center gap-1 rounded-xl border border-ink-200 bg-ink-50 p-1" role="group" aria-label="Mode analisis model">
                            <button type="button" class="viewer-tool border-0 bg-transparent px-2.5 py-1.5" data-view-mode="material" aria-pressed="true">Normal</button>
                            <button type="button" class="viewer-tool border-0 bg-transparent px-2.5 py-1.5" data-view-mode="overhang" aria-pressed="false">Overhang</button>
                            <button type="button" class="viewer-tool border-0 bg-transparent px-2.5 py-1.5" data-view-mode="thickness" aria-pressed="false">Wall Thickness</button>
                        </div>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                        <button type="button" class="viewer-tool" data-action="grid" aria-pressed="true">
                            <x-icons.grid class="h-4 w-4" />
                            Build Plate
                        </button>
                        <button type="button" class="viewer-tool" data-action="axis" aria-pressed="false">
                            <x-icons.axis class="h-4 w-4" />
                            Axis
                        </button>
                        <button type="button" class="viewer-tool" data-action="rotate" aria-pressed="false">
                            <x-icons.orbit class="h-4 w-4" />
                            Auto-rotate
                        </button>
                        <button type="button" class="viewer-tool" data-action="reset">
                            <x-icons.reset class="h-4 w-4" />
                            Reset Camera
                        </button>
                        <button type="button" class="viewer-tool" data-action="focus">
                            <x-icons.scan class="h-4 w-4" />
                            Fokus Model
                        </button>
                        <button type="button" class="viewer-tool" data-action="support-visibility" aria-pressed="true" disabled>
                            <span class="inline-block h-2.5 w-2.5 rounded-sm bg-[#5AD4DE]"></span>
                            Support
                        </button>
                    </div>

                    <div class="mt-2.5 flex flex-wrap items-center gap-2 border-t border-ink-100 pt-2.5">
                        <span class="text-[0.6rem] font-bold uppercase tracking-[0.16em] text-ink-400">Sudut pandang</span>
                        @foreach (['front' => 'Front', 'back' => 'Back', 'left' => 'Left', 'right' => 'Right', 'top' => 'Top', 'bottom' => 'Bottom', 'isometric' => 'Isometric'] as $preset => $label)
                            <button type="button" class="view-preset" data-view="{{ $preset }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="relative bg-ink-50">
                    {{-- Kanvas 2D; isinya digambar SharedRenderer agar seluruh
                         card cukup memakai satu context WebGL. --}}
                    <canvas class="block h-[340px] w-full touch-none sm:h-[400px]" data-card-canvas></canvas>

                    <div class="pointer-events-none absolute bottom-3 right-3 rounded-lg bg-ink-900/75 px-3 py-2 backdrop-blur" style="display: none" data-support-legend>
                        <p class="flex items-center gap-2 text-[0.65rem] font-semibold text-white">
                            <span class="inline-block h-2.5 w-2.5 rounded-sm bg-[#5AD4DE]"></span>
                            Support
                        </p>
                        <p class="mt-1 flex items-center gap-2 text-[0.65rem] font-semibold text-white">
                            <span class="inline-block h-2.5 w-2.5 rounded-sm bg-[#C0392B]"></span>
                            Di luar area cetak
                        </p>
                    </div>

                    <div class="pointer-events-none absolute left-3 top-3 rounded-xl bg-ink-900/80 px-4 py-3 backdrop-blur" style="display: none" data-analysis-legend>
                        <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-white/70" data-analysis-legend-title></p>
                        <ul class="mt-2 space-y-1.5" data-analysis-legend-items></ul>
                        <p class="mt-2 max-w-[14rem] text-[0.6rem] leading-relaxed text-white/60" data-analysis-legend-note></p>
                    </div>
                </div>
            </div>

            {{-- Peringatan model melewati area cetak mesin ini --}}
            <div class="mt-4 rounded-2xl border border-brand-200 bg-brand-50 p-5" style="display: none" role="alert" data-build-warning>
                <p class="flex items-center gap-2 font-display text-sm font-bold text-brand-800">
                    <x-icons.alert class="h-4.5 w-4.5 shrink-0" />
                    Object exceeds build volume
                </p>
                <p class="mt-2 text-xs leading-relaxed text-brand-800" data-build-warning-detail></p>
                <p class="mt-2 text-[0.7rem] leading-relaxed text-brand-700">
                    Bagian yang melewati batas ditandai merah di viewer. Perkecil skala model,
                    ubah orientasinya, atau pilih mesin dengan area cetak lebih besar.
                </p>
            </div>
        </div>

        {{-- ---------- Mesin, build volume, informasi & estimasi ---------- --}}
        <div class="space-y-5 lg:col-span-5">

            {{-- Build volume --}}
            <div class="rounded-2xl border border-ink-100 p-5">
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-500">Informasi Build Volume</p>

                <dl class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        'printer' => 'Printer',
                        'model' => 'Model',
                        'usage' => 'Build Volume Used',
                    ] as $key => $label)
                        <div>
                            <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $label }}</dt>
                            <dd class="mt-1 font-display text-sm font-bold text-ink-900" data-build="{{ $key }}">-</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-ink-100">
                    <div class="h-full rounded-full bg-brand-600 transition-all duration-300 ease-out" style="width: 0%" data-build-usage-bar></div>
                </div>
            </div>

            {{-- Estimasi ringkas --}}
            <div class="rounded-2xl border border-ink-100 p-5">
                <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-500">Estimasi Printing</p>

                {{-- Sengaja ringkas: mesin, resolusi, dan infill tetap dapat
                     diatur pada panel Pilihan Produksi, tetapi tidak lagi
                     mengisi ringkasan estimasi agar mudah dibaca sekilas. --}}
                <dl class="mt-4 space-y-2.5">
                    @foreach ([
                        'technology' => 'Teknologi Printing',
                        'material' => 'Material',
                        'support' => 'Support Structure',
                        'quantity' => 'Jumlah',
                        'volume' => 'Volume Material',
                    ] as $key => $label)
                        <div class="flex items-start justify-between gap-4 border-b border-ink-100 pb-2.5">
                            <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-ink-400">{{ $label }}</dt>
                            <dd class="text-right text-xs font-semibold text-ink-800" data-estimate="{{ $key }}">-</dd>
                        </div>
                    @endforeach

                    {{-- Berat model, berat support, dan total berat tidak
                         ditampilkan kepada pelanggan. Angkanya tetap dihitung
                         karena menjadi dasar harga, tetapi hanya dipakai di
                         sisi internal (admin dan superadmin). --}}

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-ink-400">Estimasi Lead Time</dt>
                        <dd class="text-right text-xs font-semibold text-ink-800" data-estimate="time">-</dd>
                    </div>
                </dl>

                {{-- Estimasi harga hanya ditampilkan kepada pengguna yang sudah masuk. --}}
                @auth
                    <div class="mt-4 rounded-xl bg-gradient-to-br from-brand-600 to-brand-800 p-4 text-white">
                        <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-white/70">Estimasi Harga</p>
                        <p class="mt-1 font-display text-2xl font-bold" data-estimate="cost">-</p>
                    </div>
                @else
                    <div class="mt-4 rounded-xl border border-brand-200 bg-brand-50 p-4">
                        <p class="text-sm font-bold leading-relaxed text-brand-900">
                            Login untuk melihat estimasi harga dan membuat penawaran.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a href="{{ route('login') }}" class="btn-primary px-4 py-2 text-xs">Sign In</a>
                            <a href="{{ route('register') }}" class="btn-outline px-4 py-2 text-xs">Daftar Akun</a>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>

    {{-- ---------- Panel rinci, dilipat agar halaman tetap ringkas ---------- --}}
    <div class="space-y-3 border-t border-ink-100 bg-ink-50/40 px-6 py-5">

        {{-- Informasi model --}}
        <details class="card-section">
            <summary class="card-section-summary">Informasi Model</summary>
            <div class="card-section-body">
                <dl class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'name' => 'Nama File',
                        'format' => 'Format File',
                        'size' => 'Ukuran File',
                        'vertices' => 'Jumlah Vertex',
                        'triangles' => 'Jumlah Face / Triangle',
                        'dimensions' => 'Dimensi (P × L × T)',
                        'volume' => 'Volume Model',
                        'surface-area' => 'Luas Permukaan',
                    ] as $stat => $label)
                        <div>
                            <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $label }}</dt>
                            <dd class="mt-1 break-words text-sm font-bold text-ink-900" data-stat="{{ $stat }}">-</dd>
                        </div>
                    @endforeach

                    <div class="sm:col-span-2">
                        <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">Bounding Box</dt>
                        <dd class="mt-1 font-mono text-xs leading-relaxed text-ink-600" data-stat="bounding-box">-</dd>
                    </div>
                </dl>

                <p class="mt-4 rounded-xl bg-amber-50 p-4 text-xs leading-relaxed text-amber-800" style="display: none" data-volume-note>
                    Volume ditandai <strong>±</strong> karena mesh belum tertutup sempurna, sehingga angkanya merupakan perkiraan.
                </p>
            </div>
        </details>

        {{-- Orientasi & skala --}}
        <details class="card-section">
            <summary class="card-section-summary">Orientasi &amp; Skala Model</summary>
            <div class="card-section-body space-y-6">

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-600">Orientasi Model</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="viewer-tool" data-action="rotate-object-mode" aria-pressed="false">
                                <x-icons.orbit class="h-4 w-4" />
                                Putar dengan Drag
                            </button>
                            <button type="button" class="viewer-tool" data-action="reset-orientation">
                                <x-icons.reset class="h-4 w-4" />
                                Reset Orientasi
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-3">
                        @foreach ([
                            'x' => ['label' => 'Putar sumbu X', 'color' => 'bg-brand-600'],
                            'y' => ['label' => 'Putar sumbu Y', 'color' => 'bg-emerald-600'],
                            'z' => ['label' => 'Putar sumbu Z', 'color' => 'bg-sky-600'],
                        ] as $axis => $meta)
                            <div class="rounded-xl border border-ink-100 bg-white p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex items-center gap-2 text-[0.65rem] font-bold uppercase tracking-[0.12em] text-ink-600">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full {{ $meta['color'] }}"></span>
                                        {{ $meta['label'] }}
                                    </span>
                                    <span class="font-display text-sm font-bold text-brand-600" data-rotate-value="{{ $axis }}">0°</span>
                                </div>

                                <input type="range"
                                       class="mt-3 w-full accent-brand-600"
                                       min="0" max="359" step="1" value="0"
                                       data-rotate-slider="{{ $axis }}"
                                       aria-label="Rotasi sumbu {{ strtoupper($axis) }} dalam derajat">

                                <div class="mt-3 grid grid-cols-4 gap-2">
                                    @foreach ([-90, -15, 15, 90] as $step)
                                        <button type="button"
                                                class="view-preset"
                                                data-rotate-step="{{ $step }}"
                                                data-axis="{{ $axis }}">{{ $step > 0 ? '+'.$step : $step }}&deg;</button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-400">Balik / cerminkan</span>
                        @foreach (['x' => 'Balik X', 'y' => 'Balik Y', 'z' => 'Balik Z'] as $axis => $label)
                            <button type="button" class="viewer-tool" data-mirror="{{ $axis }}" aria-pressed="false">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-ink-100 pt-5">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-brand-600">Scale Model</p>

                    <div class="mt-4 grid gap-5 lg:grid-cols-2">
                        <div>
                            <div class="flex items-center gap-3">
                                <button type="button" class="scale-step" data-scale-step="-10" aria-label="Kurangi skala 10 persen">
                                    <span aria-hidden="true">&minus;</span>
                                </button>

                                <div class="relative flex-1">
                                    <input type="number"
                                           class="field-input mt-0 pr-10 text-center font-display text-base font-bold"
                                           min="10" max="400" step="1" value="100"
                                           data-scale-input
                                           aria-label="Skala model dalam persen">
                                    <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-ink-400">%</span>
                                </div>

                                <button type="button" class="scale-step" data-scale-step="10" aria-label="Tambah skala 10 persen">
                                    <span aria-hidden="true">+</span>
                                </button>
                            </div>

                            <input type="range"
                                   class="mt-3 w-full accent-brand-600"
                                   min="10" max="400" step="1" value="100"
                                   data-scale-slider
                                   aria-label="Geser skala model">

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($scalePresets as $preset)
                                    <button type="button" class="view-preset" data-scale-preset="{{ $preset }}">{{ $preset }}%</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-xl border border-ink-100 bg-white p-4">
                            <p class="text-[0.6rem] font-bold uppercase tracking-[0.12em] text-ink-500">Hasil Setelah Diskalakan</p>
                            <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                                {{-- Berat tidak ikut ditampilkan di sini; lihat catatan
                                     pada ringkasan estimasi di atas. --}}
                                @foreach ([
                                    'dimensions' => 'Dimensi',
                                    'volume' => 'Volume',
                                    'time' => 'Estimasi Lead Time',
                                ] as $key => $label)
                                    <div>
                                        <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.1em] text-ink-400">{{ $label }}</dt>
                                        <dd class="mt-0.5 font-display text-xs font-bold text-ink-900" data-scale-result="{{ $key }}">-</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </details>

    </div>
</article>
