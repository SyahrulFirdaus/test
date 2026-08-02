@extends('layouts.app')

@section('title', '3D Models')
@section('description', 'Pre-Print Analyzer: unggah beberapa file STL atau OBJ sekaligus. Setiap model mendapat mesin printer dan build plate sendiri, lengkap dengan viewer 3D, analisis kelayakan, pengaturan printing, dan estimasi biayanya masing-masing.')

@section('content')

    <x-page-hero
        eyebrow="3D Models"
        current="3D Models"
        title='Tinjau, analisis, dan hitung biaya <span class="text-brand-400">sebelum dicetak</span>'
        description="Unggah satu atau beberapa file STL/OBJ sekaligus. Setiap model langsung mendapat mesin printer dan build plate-nya sendiri — satu printer, satu objek — lengkap dengan viewer 3D, analisis, dan estimasi terpisah.">
        <x-slot:actions>
            <a href="#viewer" class="btn-primary w-full sm:w-auto">
                Mulai Unggah File
                <x-icons.arrow-down class="h-4 w-4" />
            </a>
            <a href="{{ route('services') }}" class="btn-ghost-light w-full sm:w-auto">Lihat Layanan Cetak</a>
        </x-slot:actions>
    </x-page-hero>

    <section id="viewer" class="section scroll-mt-24">
        <div class="container-page">
            <div data-model-viewer>

                {{-- Konfigurasi estimasi & batas analisis, dibaca oleh JavaScript --}}
                <script type="application/json" data-printing-config>@json($printingConfig)</script>

                {{-- Peringatan bila browser tidak mendukung WebGL --}}
                <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 p-6" style="display: none" data-viewer-unsupported>
                    <div class="flex gap-4">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <x-icons.alert class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="font-display text-base font-bold text-amber-900">Browser Anda belum mendukung WebGL</h2>
                            <p class="mt-1.5 text-sm leading-relaxed text-amber-800">
                                Viewer 3D memerlukan WebGL untuk menggambar model. Silakan gunakan versi terbaru Chrome, Edge, Firefox, atau Safari,
                                lalu pastikan akselerasi perangkat keras (hardware acceleration) aktif pada pengaturan browser.
                            </p>
                        </div>
                    </div>
                </div>

                <div data-viewer-panel>

                    {{-- ================= 1. AREA UNGGAH ================= --}}
                    <div class="grid gap-6 lg:grid-cols-12">
                        <div class="lg:col-span-8" data-aos="fade-up">
                            <div class="dropzone" data-dropzone>
                                <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-[0_16px_34px_-14px_rgba(149,39,29,0.95)]">
                                    <x-icons.upload class="h-8 w-8" />
                                </span>

                                <h2 class="mt-6 font-display text-xl font-bold text-ink-900 sm:text-2xl">
                                    Tarik &amp; letakkan file 3D Anda di sini
                                </h2>
                                <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-500">
                                    Mendukung format
                                    @foreach ($supportedFormats as $format)
                                        <span class="font-semibold text-brand-600">.{{ strtolower($format) }}</span>@if (! $loop->last) dan @endif
                                    @endforeach
                                    dengan ukuran maksimal {{ $previewMaxFileSizeMb }} MB per file.
                                </p>
                                <p class="mt-2 max-w-md text-sm leading-relaxed text-ink-500">
                                    Setiap file langsung dianalisis dan muncul sebagai kartu berisi gambar pratinjau serta
                                    ringkasan modelnya — maksimal {{ $maxModels }} model dalam satu permintaan penawaran.
                                </p>

                                <div class="mt-7 flex flex-col items-center gap-3 sm:flex-row">
                                    <label class="btn-primary cursor-pointer">
                                        <x-icons.upload class="h-4 w-4" />
                                        Upload File
                                        <input type="file"
                                               class="sr-only"
                                               accept=".stl,.obj,model/stl,model/obj"
                                               multiple
                                               data-file-input>
                                    </label>
                                    <span class="text-xs font-medium uppercase tracking-[0.14em] text-ink-400">atau seret beberapa file ke area ini</span>
                                </div>
                            </div>
                        </div>

                        {{-- Panduan singkat --}}
                        <div class="lg:col-span-4" data-aos="fade-up" data-aos-delay="80">
                            <div class="h-full rounded-3xl border border-ink-100 bg-white p-7 shadow-card">
                                <h2 class="font-display text-base font-bold text-ink-900">Satu printer, satu objek</h2>
                                <p class="mt-2 text-xs leading-relaxed text-ink-400">
                                    Tidak ada dua model dalam satu build plate. Setiap model berdiri sendiri, jadi
                                    pengaturannya tidak pernah saling memengaruhi.
                                </p>

                                <ul class="mt-5 space-y-4 text-sm text-ink-600">
                                    @foreach ([
                                        ['icon' => 'printer', 'label' => 'Printer sendiri', 'text' => 'Tiap file langsung mendapat mesin dan build plate-nya sendiri.'],
                                        ['icon' => 'scan', 'label' => 'Pratinjau otomatis', 'text' => 'Gambar kartu dibuat dari model Anda sendiri, bukan ikon file.'],
                                        ['icon' => 'orbit', 'label' => 'Viewer di tab baru', 'text' => 'Analisis lengkap dibuka terpisah agar halaman ini tetap ringan.'],
                                        ['icon' => 'spark', 'label' => 'Estimasi sendiri', 'text' => 'Berat, waktu, dan biaya dihitung per model, lalu ditotal di bawah.'],
                                    ] as $tip)
                                        <li class="flex gap-3">
                                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600/10 text-brand-600">
                                                <x-dynamic-component :component="'icons.'.$tip['icon']" class="h-4.5 w-4.5" />
                                            </span>
                                            <span>
                                                <span class="block font-semibold text-ink-900">{{ $tip['label'] }}</span>
                                                <span class="mt-0.5 block text-xs leading-relaxed text-ink-500">{{ $tip['text'] }}</span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- ================= PESAN KESALAHAN ================= --}}
                    <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-6" style="display: none" role="alert" data-viewer-error>
                        <div class="flex gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                                <x-icons.alert class="h-5 w-5" />
                            </span>
                            <div class="flex-1">
                                <h2 class="font-display text-base font-bold text-brand-900" data-viewer-error-title>Terjadi kesalahan</h2>
                                <p class="mt-1.5 text-sm leading-relaxed text-brand-800" data-viewer-error-message></p>

                                {{-- Saat beberapa file diunggah sekaligus, hanya file yang bermasalah
                                     yang dilaporkan di sini — file lain tetap mendapat printernya. --}}
                                <ul class="mt-3 space-y-2" style="display: none" data-viewer-error-list></ul>
                            </div>
                            <button type="button"
                                    class="shrink-0 rounded-lg p-2 text-brand-700 transition-colors hover:bg-brand-100"
                                    data-error-dismiss
                                    aria-label="Tutup pesan kesalahan">
                                <x-icons.close class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    {{-- Indikator saat berkas sedang dibaca --}}
                    <div class="mt-6 items-center gap-4 rounded-2xl border border-ink-100 bg-white p-6 shadow-card"
                         style="display: none"
                         data-viewer-loading>
                        <span class="h-10 w-10 animate-spin rounded-full border-4 border-brand-100 border-t-brand-600"></span>
                        <p class="text-sm font-semibold text-ink-700" data-viewer-loading-label>Memuat model…</p>
                    </div>

                    {{-- ================= DAFTAR MODEL YANG DIUNGGAH ================= --}}
                    <div class="mt-10 flex flex-wrap items-end justify-between gap-4" data-aos="fade-up">
                        <div>
                            <h2 class="font-display text-xl font-bold text-ink-900 sm:text-2xl">Model yang Diunggah</h2>
                            <p class="mt-1.5 text-sm leading-relaxed text-ink-500">
                                <span class="font-semibold text-brand-600" data-printer-count>0/{{ $maxModels }}</span>
                                model &middot; klik thumbnail atau tombol <span class="font-semibold text-ink-700">Lihat 3D</span>
                                untuk membuka viewer beserta seluruh analisisnya di tab baru.
                            </p>
                        </div>

                        <button type="button" class="viewer-tool" data-action="clear-all" disabled>
                            <x-icons.trash class="h-4 w-4" />
                            Hapus Semua Model
                        </button>
                    </div>

                    <p class="mt-6 rounded-3xl border border-dashed border-ink-200 bg-ink-50/60 p-8 text-center text-sm leading-relaxed text-ink-500"
                       data-printers-empty>
                        Belum ada model yang diunggah. Unggah file <span class="font-semibold text-ink-700">.stl</span> atau
                        <span class="font-semibold text-ink-700">.obj</span> — setiap file langsung dibaca, dianalisis, dan
                        muncul di daftar ini lengkap dengan gambar pratinjaunya.
                    </p>

                    {{-- Card daftar model, disusun JavaScript dari data yang tersimpan di browser --}}
                    <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3" data-model-list></div>

                    {{-- ================= RINGKASAN PENAWARAN ================= --}}
                    <div class="mt-10 rounded-3xl border border-ink-100 bg-white p-7 shadow-card" data-aos="fade-up">
                        <div>
                            <h2 class="font-display text-base font-bold text-ink-900">Ringkasan Penawaran</h2>
                            <p class="mt-1 max-w-2xl text-xs leading-relaxed text-ink-400">
                                Seluruh mesin di atas dikirim sebagai <span class="font-semibold text-ink-600">satu permintaan penawaran</span>
                                dengan satu Nomor Tracking.
                            </p>
                        </div>

                        <div class="mt-6" style="display: none" data-quote-summary>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[720px] text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                                            <th scope="col" class="py-3 pr-3 font-bold">Printer</th>
                                            <th scope="col" class="px-3 py-3 font-bold">Nama File</th>
                                            <th scope="col" class="px-3 py-3 font-bold">Mesin &amp; Material</th>
                                            <th scope="col" class="px-3 py-3 text-right font-bold">Jumlah</th>
                                            <th scope="col" class="px-3 py-3 text-right font-bold">Berat Total</th>
                                            <th scope="col" class="px-3 py-3 text-right font-bold">Waktu</th>
                                            {{-- Kolom biaya hanya untuk pengguna yang sudah masuk. --}}
                                            @auth
                                                <th scope="col" class="py-3 pl-3 text-right font-bold">Estimasi Biaya</th>
                                            @endauth
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-ink-100" data-quote-rows></tbody>
                                </table>
                            </div>

                            {{-- Total keseluruhan. Angka biaya hanya tampil setelah pengguna masuk. --}}
                            @php
                                $totalCards = [
                                    'printers' => ['label' => 'Total Printer Digunakan', 'accent' => false],
                                    'models' => ['label' => 'Total Model', 'accent' => false],
                                    'weight' => ['label' => 'Total Berat', 'accent' => false],
                                    'time' => ['label' => 'Total Estimasi Waktu', 'accent' => false],
                                ];

                                if (auth()->check()) {
                                    $totalCards['cost'] = ['label' => 'Total Estimasi Biaya', 'accent' => true];
                                }
                            @endphp

                            <dl class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                                @foreach ($totalCards as $total => $meta)
                                    <div class="rounded-2xl border p-5 {{ $meta['accent'] ? 'border-transparent bg-gradient-to-br from-brand-600 to-brand-800 text-white' : 'border-ink-100 bg-ink-50/70' }}">
                                        <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] {{ $meta['accent'] ? 'text-white/70' : 'text-ink-400' }}">
                                            {{ $meta['label'] }}
                                        </dt>
                                        <dd class="mt-1.5 font-display text-lg font-bold {{ $meta['accent'] ? 'text-white' : 'text-ink-900' }}"
                                            data-total="{{ $total }}">—</dd>
                                    </div>
                                @endforeach
                            </dl>

                            <p class="mt-5 rounded-xl bg-ink-50 p-4 text-xs leading-relaxed text-ink-500">
                                Total waktu adalah penjumlahan jam mesin seluruh printer. Karena setiap model dicetak pada
                                mesinnya sendiri, pengerjaan dapat berjalan bersamaan — mesin terlama selesai dalam
                                <span class="font-semibold text-ink-700" data-total="longest">—</span>.
                            </p>

                            @auth
                                <button type="button" class="btn-primary mt-6 w-full sm:w-auto" data-open-quotation disabled>
                                    Minta Penawaran
                                    <x-icons.arrow-right class="h-4 w-4" />
                                </button>
                            @else
                                {{-- Informasi komersial ditahan sampai pengunjung masuk. Model yang
                                     sudah diunggah tetap tersimpan di browser, jadi setelah masuk
                                     harganya langsung tampil tanpa perlu mengunggah ulang. --}}
                                <div class="mt-6 rounded-2xl border border-brand-200 bg-brand-50 p-6">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                                            <x-icons.lock class="h-5 w-5" />
                                        </span>
                                        <div>
                                            <p class="font-display text-base font-bold text-brand-900">
                                                Login untuk melihat estimasi harga dan membuat penawaran.
                                            </p>
                                            <p class="mt-1.5 text-sm leading-relaxed text-brand-800">
                                                Model yang sudah Anda unggah tetap tersimpan — setelah masuk, harganya
                                                langsung muncul tanpa perlu mengunggah ulang.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                                        <a href="{{ route('login') }}" class="btn-primary w-full sm:w-auto">Sign In</a>
                                        <a href="{{ route('register') }}" class="btn-outline w-full sm:w-auto">Daftar Akun</a>
                                    </div>
                                </div>
                            @endauth
                        </div>

                        <p class="mt-5 text-sm leading-relaxed text-ink-500" data-quote-summary-placeholder>
                            Ringkasan seluruh model — berat, waktu@auth , dan estimasi biaya@endauth beserta totalnya —
                            muncul di sini setelah minimal satu model berhasil dimuat.
                        </p>
                    </div>
                </div>

                {{-- ================= TEMPLATE CARD SATU PRINTER ================= --}}
                {{--
                    Card ini tidak lagi ditampilkan di halaman ini. JavaScript
                    mengkloningnya ke luar layar hanya untuk membaca geometri,
                    menjalankan analisis, menghitung estimasi, dan mengambil
                    gambar thumbnail; setelah itu salinannya langsung dibuang.

                    Card yang sama dirender utuh di halaman Viewer 3D, sehingga
                    seluruh fitur analisis dan simulasinya persis sama.
                --}}
                <template data-printer-template>
                    <x-model-check.printer-card />
                </template>

                {{-- Tempat kloning card bekerja: berada di luar layar, tidak dapat
                     difokus, dan isinya dibuang segera setelah thumbnail beserta
                     hasil analisisnya diambil. --}}
                <div aria-hidden="true"
                     style="position: fixed; left: -10000px; top: 0; width: 760px; height: 520px; overflow: hidden; opacity: 0; pointer-events: none"
                     data-headless-host></div>

                {{-- ================= MODAL EDIT SPECIFICATION =================
                    Mengatur teknologi, material, warna, finishing, dan jumlah
                    untuk satu model. Seluruh perhitungannya berjalan di browser
                    sehingga harga dan estimasi langsung berubah begitu disimpan.
                --}}
                <div class="fixed inset-0 z-[65] items-start justify-center overflow-y-auto bg-ink-950/70 p-4 backdrop-blur-sm sm:p-8"
                     style="display: none"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="spec-title"
                     data-spec-modal>
                    <div class="relative my-auto w-full max-w-xl rounded-3xl bg-white p-7 shadow-2xl sm:p-9" data-spec-dialog>

                        <button type="button"
                                class="absolute right-5 top-5 rounded-lg p-2 text-ink-400 transition-colors hover:bg-ink-50 hover:text-ink-700"
                                data-spec-close
                                aria-label="Tutup pengaturan spesifikasi">
                            <x-icons.close class="h-5 w-5" />
                        </button>

                        <h2 id="spec-title" class="font-display text-xl font-bold text-ink-900">Edit Specification</h2>
                        <p class="mt-1.5 truncate text-sm text-ink-500" data-spec-file>model.stl</p>

                        <form class="mt-6 space-y-5" data-spec-form>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="spec-technology" class="field-label">Teknologi Printing</label>
                                    <select id="spec-technology" class="field-input" data-spec-technology></select>
                                </div>

                                <div>
                                    <label for="spec-material" class="field-label">Material</label>
                                    <select id="spec-material" class="field-input" data-spec-material></select>
                                </div>
                            </div>

                            <div>
                                <span class="field-label">Warna Material</span>
                                <div class="mt-2 flex flex-wrap gap-2.5" role="group" aria-label="Warna material" data-spec-colors></div>
                                <p class="mt-2 text-[0.7rem] leading-relaxed text-ink-400">
                                    Pilihan warna menyesuaikan material yang dipilih.
                                </p>
                            </div>

                            <div>
                                <label for="spec-finishing" class="field-label">Finishing</label>
                                <select id="spec-finishing" class="field-input" data-spec-finishing></select>
                                <p class="mt-2 text-[0.7rem] leading-relaxed text-ink-400" data-spec-finishing-note></p>
                            </div>

                            <div>
                                <label for="spec-quantity" class="field-label">Quantity</label>
                                <div class="mt-2 flex items-center gap-2">
                                    <button type="button" class="qty-step" data-spec-qty-step="-1" aria-label="Kurangi jumlah">−</button>
                                    <input type="number" id="spec-quantity" class="qty-input w-24" value="1" min="1" max="10000" step="1" inputmode="numeric" data-spec-quantity>
                                    <button type="button" class="qty-step" data-spec-qty-step="1" aria-label="Tambah jumlah">+</button>
                                    <span class="text-xs text-ink-400">pcs</span>
                                </div>
                            </div>

                            {{-- Support structure & hollow model ikut diatur di sini sejak
                                 halaman viewer 3D hanya berfungsi sebagai alat analisis. --}}
                            <div class="rounded-xl border border-ink-200 bg-ink-50/60 p-4" data-spec-support-field>
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox"
                                           class="mt-0.5 h-4.5 w-4.5 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-600 disabled:cursor-not-allowed"
                                           data-spec-support>
                                    <span>
                                        <span class="block text-sm font-bold text-ink-900">Tambahkan Support Structure</span>
                                        <span class="mt-1 block text-[0.7rem] leading-relaxed text-ink-500">
                                            Material penopang untuk bagian yang menggantung. Dilepas setelah dicetak, dan
                                            menambah pemakaian material, waktu, serta biaya.
                                        </span>
                                    </span>
                                </label>

                                <p class="mt-2 pl-7 text-[0.7rem] font-semibold leading-relaxed text-amber-700"
                                   style="display: none"
                                   data-spec-support-note></p>
                            </div>

                            <div class="rounded-xl border border-ink-200 bg-ink-50/60 p-4" style="display: none" data-spec-hollow-field>
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox"
                                           class="mt-0.5 h-4.5 w-4.5 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-600"
                                           data-spec-hollow>
                                    <span>
                                        <span class="block text-sm font-bold text-ink-900">Hollow Model</span>
                                        <span class="mt-1 block text-[0.7rem] leading-relaxed text-ink-500">
                                            Mengosongkan bagian dalam part resin sehingga jauh lebih hemat material.
                                        </span>
                                    </span>
                                </label>

                                <div class="mt-3 pl-7" style="display: none" data-spec-hollow-settings>
                                    <label for="spec-hollow-wall" class="field-label">Tebal Dinding (mm)</label>
                                    <input type="number"
                                           id="spec-hollow-wall"
                                           class="field-input"
                                           min="{{ $hollow['wall_thickness_mm']['min'] }}"
                                           max="{{ $hollow['wall_thickness_mm']['max'] }}"
                                           step="{{ $hollow['wall_thickness_mm']['step'] }}"
                                           value="{{ $hollow['wall_thickness_mm']['default'] }}"
                                           data-spec-hollow-wall>
                                </div>
                            </div>

                            {{-- Pratinjau angka sebelum disimpan; harga hanya untuk yang sudah masuk. --}}
                            <dl class="grid gap-4 rounded-2xl border border-ink-100 bg-ink-50/70 p-5 sm:grid-cols-3">
                                @foreach (auth()->check()
                                    ? ['weight' => 'Estimasi Berat', 'time' => 'Estimasi Waktu', 'cost' => 'Estimasi Harga']
                                    : ['weight' => 'Estimasi Berat', 'time' => 'Estimasi Waktu']
                                as $key => $label)
                                    <div>
                                        <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                                        <dd class="mt-1 text-sm font-bold text-ink-900" data-spec-preview="{{ $key }}">—</dd>
                                    </div>
                                @endforeach
                            </dl>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" class="btn-outline w-full sm:w-auto" data-spec-close>Batal</button>
                                <button type="submit" class="btn-primary w-full sm:w-auto">Simpan Spesifikasi</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ================= MODAL WAJIB LOGIN =================
                    Ditampilkan bila pengunjung menekan "Minta Penawaran" tanpa
                    akun. Seluruh pratinjau dan simulasi di atas tetap dapat
                    dipakai tanpa login — hanya pengiriman penawaran yang tidak.
                --}}
                <div class="fixed inset-0 z-[70] items-center justify-center bg-ink-950/70 p-4 backdrop-blur-sm"
                     style="display: none"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="login-required-title"
                     data-login-modal>
                    <div class="relative w-full max-w-md rounded-3xl bg-white p-7 text-center shadow-2xl sm:p-9" data-login-dialog>
                        <button type="button"
                                class="absolute right-5 top-5 rounded-lg p-2 text-ink-400 transition-colors hover:bg-ink-50 hover:text-ink-700"
                                data-login-close
                                aria-label="Tutup pemberitahuan">
                            <x-icons.close class="h-5 w-5" />
                        </button>

                        <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-600/10 text-brand-600">
                            <x-icons.lock class="h-8 w-8" />
                        </span>

                        <h2 id="login-required-title" class="mt-6 font-display text-xl font-bold text-ink-900">
                            Silakan login atau membuat akun terlebih dahulu untuk membuat penawaran.
                        </h2>
                        <p class="mt-3 text-sm leading-relaxed text-ink-500">
                            Penawaran disimpan pada akun Anda supaya dapat dipantau, disunting selama masih menunggu
                            review, dan menerima notifikasi setiap perubahan status.
                        </p>

                        <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('login') }}" class="btn-primary w-full">Login</a>
                            <a href="{{ route('register') }}" class="btn-outline w-full">Register</a>
                        </div>

                        <p class="mt-5 text-xs leading-relaxed text-ink-400">
                            Model yang sedang Anda tinjau tidak terkirim ke mana pun. Setelah masuk, unggah kembali
                            file Anda lalu tekan "Minta Penawaran".
                        </p>
                    </div>
                </div>

                {{-- ================= MODAL REQUEST QUOTATION ================= --}}
                <div class="fixed inset-0 z-[60] items-start justify-center overflow-y-auto bg-ink-950/70 p-4 backdrop-blur-sm sm:p-8"
                     style="display: none"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="quotation-title"
                     data-quotation-modal>
                    <div class="relative my-auto w-full max-w-2xl rounded-3xl bg-white p-7 shadow-2xl sm:p-9" data-quotation-dialog>

                        <button type="button"
                                class="absolute right-5 top-5 rounded-lg p-2 text-ink-400 transition-colors hover:bg-ink-50 hover:text-ink-700"
                                data-quotation-close
                                aria-label="Tutup form penawaran">
                            <x-icons.close class="h-5 w-5" />
                        </button>

                        {{-- Form --}}
                        <form method="POST" action="{{ route('quotations.store') }}" data-quotation-form>
                            @csrf

                            <h2 id="quotation-title" class="font-display text-xl font-bold text-ink-900 sm:text-2xl">Minta Penawaran</h2>
                            <p class="mt-2 text-sm leading-relaxed text-ink-500">
                                Seluruh file model beserta mesin, pengaturan, dan hasil analisis tiap printer ikut terkirim
                                dalam satu permintaan, sehingga tim kami dapat langsung meninjaunya.
                            </p>

                            <div class="mt-6 rounded-2xl border border-ink-100 bg-ink-50/70 p-5" data-quotation-summary></div>

                            <div class="mt-5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800"
                                 style="display: none"
                                 data-quotation-alert></div>

                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="q-name" class="field-label">Nama <span class="text-brand-600">*</span></label>
                                    <input type="text" id="q-name" name="name" class="field-input" required maxlength="120" autocomplete="name" placeholder="Nama lengkap Anda"
                                           value="{{ $quotationUser['name'] ?? '' }}">
                                    <p class="field-error" style="display: none" data-error-for="name"></p>
                                </div>

                                <div>
                                    <label for="q-email" class="field-label">Email <span class="text-brand-600">*</span></label>
                                    <input type="email" id="q-email" name="email" class="field-input" required maxlength="160" autocomplete="email" placeholder="nama@perusahaan.com"
                                           value="{{ $quotationUser['email'] ?? '' }}">
                                    <p class="field-error" style="display: none" data-error-for="email"></p>
                                </div>

                                <div>
                                    <label for="q-whatsapp" class="field-label">Nomor WhatsApp <span class="text-brand-600">*</span></label>
                                    <input type="tel" id="q-whatsapp" name="whatsapp" class="field-input" required maxlength="32" autocomplete="tel" placeholder="0812 3456 7890"
                                           value="{{ $quotationUser['whatsapp'] ?? '' }}">
                                    <p class="field-error" style="display: none" data-error-for="whatsapp"></p>
                                </div>

                                <div>
                                    <label for="q-company" class="field-label">Nama Perusahaan <span class="text-ink-300">(opsional)</span></label>
                                    <input type="text" id="q-company" name="company" class="field-input" maxlength="160" autocomplete="organization" placeholder="PT Contoh Sejahtera">
                                    <p class="field-error" style="display: none" data-error-for="company"></p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="q-notes" class="field-label">Catatan Tambahan <span class="text-ink-300">(opsional)</span></label>
                                    <textarea id="q-notes" name="notes" rows="4" class="field-input" maxlength="2000"
                                              placeholder="Misalnya: warna yang diinginkan, toleransi kritis, atau tenggat waktu proyek."></textarea>
                                    <p class="field-error" style="display: none" data-error-for="notes"></p>
                                </div>
                            </div>

                            {{-- Kesalahan yang menyangkut berkas atau pengaturan salah satu printer --}}
                            <p class="field-error" style="display: none" data-error-for="items"></p>

                            <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button type="button" class="btn-outline w-full sm:w-auto" data-quotation-close>Batal</button>
                                <button type="submit" class="btn-primary w-full sm:w-auto" data-quotation-submit>
                                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" style="display: none" data-quotation-spinner></span>
                                    <span data-quotation-submit-label>Kirim Permintaan</span>
                                </button>
                            </div>

                            <p class="mt-4 text-xs leading-relaxed text-ink-400">
                                Dengan mengirim permintaan ini, Anda menyetujui file model diunggah ke server kami untuk keperluan peninjauan produksi.
                                File diperlakukan sebagai rahasia dan hanya dapat diakses tim internal.
                            </p>
                        </form>

                        {{-- Status berhasil --}}
                        <div style="display: none" data-quotation-success>
                            <div class="py-6 text-center">
                                <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                    <x-icons.check class="h-8 w-8" />
                                </span>
                                <h2 class="mt-6 font-display text-xl font-bold text-ink-900 sm:text-2xl">Permintaan Anda sudah kami terima</h2>
                                <p class="mt-3 text-sm font-semibold leading-relaxed text-ink-700" data-quotation-success-note></p>
                                <p class="mt-2 text-sm leading-relaxed text-ink-500">
                                    Tim engineer kami akan meninjau seluruh file dan mengirimkan penawaran resmi melalui email maupun
                                    WhatsApp Anda pada jam kerja berikutnya.
                                </p>

                                <div class="mx-auto mt-6 inline-flex flex-col items-center rounded-2xl border border-ink-100 bg-ink-50 px-8 py-5">
                                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Nomor Penawaran / Tracking</p>
                                    <p class="mt-1.5 font-mono text-xl font-bold text-brand-600" data-quotation-reference>—</p>
                                </div>

                                <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:justify-center">
                                    <a href="#" class="btn-primary w-full sm:w-auto" data-quotation-document>
                                        <x-icons.download class="h-4 w-4" />
                                        Download Bukti Penawaran (PDF)
                                    </a>
                                    <a href="#" class="btn-outline w-full sm:w-auto" data-quotation-tracking>
                                        Buka Halaman Tracking
                                    </a>
                                </div>

                                <p class="mt-5 text-xs leading-relaxed text-ink-400">
                                    Simpan dokumen ini sebagai bukti permintaan penawaran. Gunakan Nomor Tracking
                                    untuk memantau perkembangan proses penawaran.
                                </p>

                                <div class="mt-6">
                                    <button type="button" class="text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600" data-quotation-close>Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ================= CATATAN & PRIVASI ================= --}}
    <section class="section bg-ink-50/70">
        <div class="container-page">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['icon' => 'lock', 'title' => 'Pratinjau tanpa unggah', 'text' => 'Model dibaca dan dianalisis langsung oleh browser Anda. Berkas baru dikirim ke server hanya bila Anda menekan "Minta Penawaran".'],
                    ['icon' => 'printer', 'title' => 'Satu printer, satu objek', 'text' => 'Setiap file mendapat mesin dan build plate sendiri, sehingga pengaturan dan estimasinya tidak pernah tercampur dengan model lain.'],
                    ['icon' => 'shield', 'title' => 'Analisis awal, bukan final', 'text' => 'Pemeriksaan otomatis menangkap masalah geometri yang umum. Kelayakan akhir tetap dipastikan engineer kami sebelum produksi berjalan.'],
                ] as $note)
                    <div class="card p-7" data-aos="fade-up" data-aos-delay="{{ $loop->index * 80 }}">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                            <x-dynamic-component :component="'icons.'.$note['icon']" class="h-6 w-6" />
                        </span>
                        <h2 class="mt-5 text-base font-bold text-ink-900">{{ $note['title'] }}</h2>
                        <p class="mt-3 text-sm leading-relaxed text-ink-500">{{ $note['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-cta-band
        eyebrow="Sudah Yakin dengan Modelnya"
        title="Lanjutkan ke proses cetak"
        description="Setelah model Anda terlihat sesuai, kirimkan permintaan penawaran langsung dari halaman ini. Tim kami akan meninjau kelayakan cetak lalu mengirimkan penawaran resmi."
        primary-label="Lihat Layanan Cetak" />

@endsection

@push('scripts')
    @vite('resources/js/model-viewer.js')
@endpush
