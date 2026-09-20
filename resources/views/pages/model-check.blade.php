@extends('layouts.app')

@section('title', '3D Models')
@section('description', 'Pre-Print Analyzer: unggah beberapa file STL atau OBJ sekaligus. Setiap model mendapat mesin printer dan build plate sendiri, lengkap dengan viewer 3D, analisis kelayakan, pengaturan printing, dan estimasi biayanya masing-masing.')

{{-- Tanpa hero gelap di halaman ini, navbar diminta tampil solid sejak awal
     agar menunya tetap terbaca di atas latar putih. --}}
@section('navbar-style', 'solid')

@section('content')

    {{-- Halaman langsung dibuka dengan area unggah. Padding atasnya menyediakan
         ruang untuk navbar yang posisinya fixed. --}}
    <section id="viewer" class="section scroll-mt-24 pt-40 md:pt-44">
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

                    {{-- Dua kolom: area unggah beserta daftar modelnya di kiri,
                         Ringkasan Penawaran menempel di kanan sehingga totalnya
                         tetap terlihat tanpa perlu menggulir ke bawah. Di layar
                         kecil keduanya kembali menumpuk. --}}
                    <div class="grid items-start gap-8 lg:grid-cols-12 lg:gap-8">

                    {{-- ---------------- KOLOM KIRI: UNGGAH & DAFTAR MODEL ---------------- --}}
                    <div class="min-w-0 lg:col-span-8">

                    {{-- ================= 1. AREA UNGGAH ================= --}}
                    <div data-aos="fade-up">

                        {{-- Panduan menyiapkan model, sengaja diletakkan di atas area
                             unggah agar terbaca sebelum file pertama dipilih. --}}
                        <div class="mb-5 flex flex-col gap-4 rounded-2xl border border-brand-200 bg-brand-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm leading-relaxed text-brand-900">
                                Baru pertama kali mengunggah model? Pelajari dulu format file, ketebalan dinding, overhang,
                                dan batas ukuran yang aman untuk dicetak.
                            </p>

                            <a href="{{ route('models.guide') }}" class="btn-primary w-full shrink-0 sm:w-auto">
                                <x-icons.book class="h-4 w-4" />
                                3D Printing Guide
                            </a>
                        </div>

                        <div class="dropzone" data-dropzone>
                            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-[0_16px_34px_-14px_rgba(149,39,29,0.95)]">
                                <x-icons.upload class="h-8 w-8" />
                            </span>

                            {{-- Judul utama halaman sejak hero dihapus. --}}
                            <h1 class="mt-6 font-display text-xl font-bold text-ink-900 sm:text-2xl">
                                Tarik &amp; letakkan file 3D Anda di sini
                            </h1>
                            <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-500">
                                <span class="font-semibold text-ink-700">File Types:</span>
                                <span class="font-semibold text-brand-600">{{ \App\Support\ModelFormat::label() }}</span>
                                &middot; maksimal {{ $previewMaxFileSizeMb }} MB per file.
                            </p>
                            <p class="mt-2 max-w-md text-sm leading-relaxed text-ink-500">
                                Setiap file langsung dianalisis dan muncul sebagai kartu berisi gambar pratinjau serta
                                ringkasan modelnya. Maksimal {{ $maxModels }} model dalam satu permintaan penawaran.
                            </p>

                            <div class="mt-7 flex flex-col items-center gap-3 sm:flex-row">
                                <label class="btn-primary cursor-pointer">
                                    <x-icons.upload class="h-4 w-4" />
                                    Upload File
                                    <input type="file"
                                           class="sr-only"
                                           accept="{{ \App\Support\ModelFormat::accept() }}"
                                           multiple
                                           data-file-input>
                                </label>
                                <span class="text-xs font-medium uppercase tracking-[0.14em] text-ink-400">atau seret beberapa file ke area ini</span>
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
                                untuk melihat pratinjau 3D-nya.
                            </p>
                        </div>

                        <button type="button" class="viewer-tool" data-action="clear-all" disabled>
                            <x-icons.trash class="h-4 w-4" />
                            Hapus Semua Model
                        </button>
                    </div>

                    <p class="mt-6 rounded-3xl border border-dashed border-ink-200 bg-ink-50/60 p-8 text-center text-sm leading-relaxed text-ink-500"
                       data-printers-empty>
                        Belum ada model yang diunggah. Unggah file
                        <span class="font-semibold text-ink-700">{{ \App\Support\ModelFormat::label() }}</span>. Setiap file
                        langsung dibaca, dianalisis, dan muncul di daftar ini lengkap dengan gambar pratinjaunya.
                    </p>

                    {{-- Card daftar model, disusun JavaScript dari data yang tersimpan di browser --}}
                    <div class="mt-6 grid gap-3 sm:grid-cols-2" data-model-list></div>

                    </div>{{-- /kolom kiri --}}

                    {{-- ---------------- KOLOM KANAN: RINGKASAN PENAWARAN ----------------
                         Menempel saat halaman digulir agar total penawaran tetap
                         terlihat sambil pengguna menata model-modelnya. --}}
                    <aside class="min-w-0 lg:sticky lg:top-28 lg:col-span-4">
                    <div class="rounded-3xl border border-ink-100 bg-white p-6 shadow-card" data-aos="fade-up">
                        <div>
                            <h2 class="font-display text-base font-bold text-ink-900">Ringkasan Penawaran</h2>
                            <p class="mt-1 text-xs leading-relaxed text-ink-400">
                                Seluruh model di samping dikirim sebagai <span class="font-semibold text-ink-600">satu permintaan penawaran</span>
                                dengan satu Nomor Tracking.
                            </p>
                        </div>

                        <div class="mt-5" style="display: none" data-quote-summary>
                            <div class="-mx-1 overflow-x-auto px-1">
                                <table class="w-full min-w-[280px] text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                                            <th scope="col" class="py-3 pr-3 font-bold">Nama File</th>
                                            <th scope="col" class="px-2 py-3 text-right font-bold">Qty</th>
                                            <th scope="col" class="{{ auth()->check() ? 'px-2' : 'pl-2' }} py-3 text-right font-bold">Estimasi Lead Time</th>
                                            {{-- Kolom biaya hanya untuk pengguna yang sudah masuk. --}}
                                            @auth
                                                <th scope="col" class="py-3 pl-2 text-right font-bold">Estimasi Biaya</th>
                                            @endauth
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-ink-100" data-quote-rows></tbody>
                                </table>
                            </div>

                            {{-- Total keseluruhan. Angka biaya hanya tampil setelah pengguna masuk. --}}
                            @php
                                $totalCards = [
                                    'models' => ['label' => 'Total Model', 'accent' => false],
                                    'time' => ['label' => 'Estimasi Lead Time', 'accent' => false],
                                ];

                                if (auth()->check()) {
                                    $totalCards['cost'] = ['label' => 'Total Estimasi Biaya', 'accent' => true];
                                }
                            @endphp

                            <dl class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                                @foreach ($totalCards as $total => $meta)
                                    <div class="rounded-2xl border p-4 {{ $meta['accent'] ? 'border-transparent bg-gradient-to-br from-brand-600 to-brand-800 text-white' : 'border-ink-100 bg-ink-50/70' }}">
                                        <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] {{ $meta['accent'] ? 'text-white/70' : 'text-ink-400' }}">
                                            {{ $meta['label'] }}
                                        </dt>
                                        <dd class="mt-1.5 font-display text-lg font-bold {{ $meta['accent'] ? 'text-white' : 'text-ink-900' }}"
                                            data-total="{{ $total }}">-</dd>
                                    </div>
                                @endforeach
                            </dl>

                            <p class="mt-4 rounded-xl bg-ink-50 p-4 text-xs leading-relaxed text-ink-500">
                                Lead time dihitung sejak penawaran disetujui dan pembayaran diterima, sudah termasuk
                                antrean produksi, post-processing, dan quality control. Tanggal pastinya dikonfirmasi
                                tim kami saat penawaran ditinjau.
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
                                                Model yang sudah Anda unggah tetap tersimpan. Setelah masuk, harganya
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
                            {{-- Kolomnya disusun di PHP, bukan dengan @auth di tengah kalimat:
                                 direktif yang menempel pada kata sebelumnya tidak dikompilasi
                                 Blade sehingga tulisannya ikut tercetak apa adanya. --}}
                            @php
                                $kolomRingkasan = auth()->check()
                                    ? 'jumlah, lead time, dan estimasi biaya'
                                    : 'jumlah dan lead time';
                            @endphp

                            Ringkasan seluruh model ({{ $kolomRingkasan }} beserta totalnya) muncul di sini
                            setelah minimal satu model berhasil dimuat.
                        </p>
                    </div>
                    </aside>{{-- /kolom kanan --}}

                    </div>{{-- /grid dua kolom --}}
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
                    <div class="relative my-auto w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl" data-spec-dialog>

                        <button type="button"
                                class="absolute right-4 top-4 z-10 rounded-lg bg-white/80 p-2 text-ink-400 transition-colors hover:bg-ink-50 hover:text-ink-700"
                                data-spec-close
                                aria-label="Tutup pengaturan spesifikasi">
                            <x-icons.close class="h-5 w-5" />
                        </button>

                        <div class="grid lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)]">

                            {{-- ---------- PANEL KIRI: model & material terpilih ----------
                                 Seluruh isinya disusun ulang oleh JavaScript setiap kali
                                 material yang dipilih berganti. --}}
                            <aside class="border-b border-ink-100 bg-ink-50/70 p-6 lg:border-b-0 lg:border-r lg:p-7">
                                <div class="aspect-[4/3] w-full overflow-hidden rounded-2xl border border-ink-100 bg-white">
                                    <img src=""
                                         alt=""
                                         class="h-full w-full object-cover"
                                         style="display: none"
                                         data-spec-thumbnail>
                                    <span class="flex h-full w-full items-center justify-center text-xs text-ink-400"
                                          data-spec-thumbnail-empty>Pratinjau tidak tersedia</span>
                                </div>

                                <p class="mt-4 truncate font-display text-sm font-bold text-ink-900" data-spec-file>model.stl</p>

                                <dl class="mt-3 space-y-1.5">
                                    {{-- Berat model tidak ditampilkan kepada pelanggan di mana pun.
                                         Angkanya tetap dihitung sebagai dasar harga dan tetap terlihat
                                         oleh admin serta superadmin. --}}
                                    @foreach (['dimensions' => 'Dimensi', 'volume' => 'Volume'] as $key => $label)
                                        <div class="spec-info-row">
                                            <dt class="text-ink-400">{{ $label }}</dt>
                                            <dd class="text-right font-semibold text-ink-800" data-spec-model="{{ $key }}">-</dd>
                                        </div>
                                    @endforeach
                                </dl>

                                {{-- Keterangan material dibaca dari katalog yang sama dengan
                                     halaman 3D Printing Guide. --}}
                                <div class="mt-6 rounded-2xl border border-ink-100 bg-white p-5">
                                    <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-ink-400">Material Terpilih</p>
                                    <p class="mt-1 font-display text-base font-bold text-ink-900" data-spec-material-name>-</p>
                                    <p class="mt-2 text-xs leading-relaxed text-ink-500" data-spec-material-description></p>

                                    <dl class="mt-4 space-y-1" data-spec-material-characteristics></dl>

                                    <div class="mt-4">
                                        <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-emerald-700">Kelebihan</p>
                                        <ul class="mt-1.5 space-y-1" data-spec-material-pros></ul>
                                    </div>

                                    <div class="mt-3">
                                        <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-brand-700">Kekurangan</p>
                                        <ul class="mt-1.5 space-y-1" data-spec-material-cons></ul>
                                    </div>

                                    {{-- Kedua baris batas ukuran hanya ada bila angkanya memang
                                         ada. Ukuran maksimum berasal dari mesin material; material
                                         yang belum ditentukan mesinnya tidak menampilkan barisnya
                                         sama sekali — bukan "-" — dan bila keduanya kosong
                                         pembatas <dl> ini pun tidak digambar. --}}
                                    <dl class="mt-4 border-t border-ink-100 pt-3 space-y-1" data-spec-material-limits style="display: none">
                                        <div class="spec-info-row" data-spec-material-max-row style="display: none">
                                            <dt class="text-ink-400">Ukuran maksimum</dt>
                                            <dd class="text-right font-semibold text-ink-700" data-spec-material-max></dd>
                                        </div>
                                        <div class="spec-info-row" data-spec-material-min-row style="display: none">
                                            <dt class="text-ink-400">Ukuran minimum</dt>
                                            <dd class="text-right font-semibold text-ink-700" data-spec-material-min></dd>
                                        </div>
                                    </dl>

                                    <a href="{{ route('models.guide') }}"
                                       target="_blank"
                                       rel="noopener"
                                       class="mt-5 inline-flex items-center gap-2 text-xs font-bold text-brand-600 transition-colors hover:text-brand-700"
                                       data-spec-learn-more>
                                        <x-icons.book class="h-4 w-4" />
                                        Learn More
                                    </a>
                                </div>
                            </aside>

                            {{-- ---------- PANEL KANAN: pengaturan spesifikasi ---------- --}}
                            <div class="max-h-[85vh] overflow-y-auto p-6 sm:p-8">
                                <h2 id="spec-title" class="font-display text-xl font-bold text-ink-900">Edit Specification</h2>
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-500">
                                    Pilihan teknologi dan material mengikuti katalog pada halaman 3D Printing Guide, lengkap
                                    dengan batas ukuran cetaknya.
                                </p>

                                <form class="mt-6 space-y-6" data-spec-form>

                                    <div>
                                        <span class="field-label">3D Technology</span>
                                        <div class="mt-2 grid gap-2.5 sm:grid-cols-2"
                                             role="group"
                                             aria-label="Teknologi printing"
                                             data-spec-technology></div>
                                    </div>

                                    <div>
                                        <span class="field-label">Material</span>
                                        <div class="mt-2 grid gap-2.5 sm:grid-cols-2"
                                             role="group"
                                             aria-label="Material"
                                             data-spec-material></div>
                                    </div>

                                    <div>
                                        <span class="field-label">Color</span>
                                        <div class="mt-2 flex flex-wrap gap-2.5" role="group" aria-label="Warna material" data-spec-colors></div>
                                        <p class="mt-2 text-[0.7rem] leading-relaxed text-ink-400">
                                            Pilihan warna menyesuaikan material yang dipilih.
                                        </p>
                                    </div>

                                    <div>
                                        <span class="field-label">Surface Finish</span>
                                        <div class="mt-2 grid gap-2.5 sm:grid-cols-2"
                                             role="group"
                                             aria-label="Surface finish"
                                             data-spec-finishing></div>
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
                                    <dl class="grid gap-4 rounded-2xl border border-ink-100 bg-ink-50/70 p-5 sm:grid-cols-2">
                                        @foreach (auth()->check()
                                            ? ['time' => 'Estimasi Lead Time', 'cost' => 'Estimasi Harga']
                                            : ['time' => 'Estimasi Lead Time']
                                        as $key => $label)
                                            <div>
                                                <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                                                <dd class="mt-1 text-sm font-bold text-ink-900" data-spec-preview="{{ $key }}">-</dd>
                                            </div>
                                        @endforeach
                                    </dl>

                                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                        <button type="button" class="btn-outline w-full sm:w-auto" data-spec-close>Batal</button>
                                        <button type="submit" class="btn-primary w-full sm:w-auto">Save Specification</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= NOTICE UKURAN MODEL =================
                    Muncul di atas Edit Specification bila ukuran model berada di
                    luar batas material yang dipilih. Spesifikasinya tidak
                    tersimpan dan modal pengaturannya tetap terbuka di belakang.
                --}}
                <div class="fixed inset-0 z-[75] items-center justify-center bg-ink-950/60 p-4 backdrop-blur-sm"
                     style="display: none"
                     role="alertdialog"
                     aria-modal="true"
                     aria-labelledby="spec-notice-title"
                     data-spec-notice>
                    <div class="relative w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl" data-spec-notice-dialog>
                        <div class="flex items-start gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                                <x-icons.alert class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <h2 id="spec-notice-title" class="font-display text-lg font-bold text-ink-900">Notice</h2>
                                <p class="mt-2 text-sm leading-relaxed text-ink-600" data-spec-notice-message></p>
                            </div>
                        </div>

                        <dl class="mt-5 space-y-2 rounded-2xl border border-ink-100 bg-ink-50/70 p-4 text-sm">
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-ink-400">Ukuran model</dt>
                                <dd class="text-right font-semibold text-ink-900" data-spec-notice-model>-</dd>
                            </div>
                            <div class="flex items-start justify-between gap-3">
                                <dt class="text-ink-400" data-spec-notice-limit-label>Ukuran minimum material</dt>
                                <dd class="text-right font-semibold text-brand-700" data-spec-notice-limit>-</dd>
                            </div>
                        </dl>

                        <p class="mt-4 text-sm leading-relaxed text-ink-500" data-spec-notice-hint></p>

                        <div class="mt-6 flex justify-end">
                            <button type="button" class="btn-primary w-full sm:w-auto" data-spec-notice-close>Mengerti</button>
                        </div>
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

                            {{-- Data diri diambil dari profil akun yang sedang masuk dan
                                 ditampilkan terkunci. Penawaran tidak menyimpan salinannya
                                 sendiri: server membaca ulang dari akun saat permintaan
                                 disimpan, sehingga profil yang diperbarui langsung dipakai
                                 penawaran berikutnya tanpa perlu diketik lagi. --}}
                            @auth
                                @php
                                    /*
                                     * Akun Business ikut menampilkan nama perusahaannya —
                                     * terkunci seperti data diri lainnya, dibaca dari profil
                                     * perusahaan yang diisi saat pendaftaran. Akun Personal
                                     * tidak memiliki baris ini sama sekali.
                                     */
                                    $lockedFields = [
                                        ['Nama', $quotationUser['name'] ?? '-'],
                                        ['Email', $quotationUser['email'] ?? '-'],
                                        ['Nomor WhatsApp', $quotationUser['whatsapp'] ?? '-'],
                                    ];

                                    if ($quotationIsBusiness) {
                                        $lockedFields[] = [
                                            'Nama Perusahaan',
                                            $quotationUser['company'] ?: 'Belum diisi',
                                        ];
                                    }

                                    // Baris terakhir dibuat selebar dua kolom bila jumlahnya
                                    // ganjil, supaya kisinya tidak menyisakan sel kosong.
                                    $lastFullWidth = count($lockedFields) % 2 === 1
                                        ? count($lockedFields) - 1
                                        : null;
                                @endphp

                                <div class="mt-6 rounded-2xl border border-ink-100 bg-ink-50/50 p-5">
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        @foreach ($lockedFields as $index => [$label, $fieldValue])
                                            <div @class(['sm:col-span-2' => $index === $lastFullWidth])>
                                                <span class="field-label">{{ $label }}</span>

                                                <div class="relative mt-2">
                                                    <input type="text"
                                                           class="field-input mt-0 cursor-not-allowed bg-white pr-11 text-ink-500"
                                                           value="{{ $fieldValue }}"
                                                           readonly
                                                           tabindex="-1"
                                                           aria-readonly="true">
                                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-ink-300"
                                                          aria-hidden="true">
                                                        <x-icons.lock class="h-4 w-4" />
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 pt-4">
                                        <p class="text-xs leading-relaxed text-ink-400">
                                            @if ($quotationIsBusiness)
                                                Ingin mengubah informasi perusahaan? Silakan ubah melalui halaman Profil
                                                di Dashboard User.
                                            @else
                                                Ingin mengubah informasi ini? Silakan ubah melalui halaman Profil di Dashboard User.
                                            @endif
                                        </p>
                                        <a href="{{ $quotationIsBusiness ? route('dashboard.company-profile.edit') : route('dashboard.profile.edit') }}"
                                           target="_blank" rel="noopener"
                                           class="btn-outline shrink-0 px-4 py-2 text-xs">
                                            Edit Profil
                                        </a>
                                    </div>
                                </div>
                            @endauth

                            {{-- Nama perusahaan tidak lagi diketik di sini.

                                 Akun Business menampilkannya terkunci pada blok data
                                 akun di atas, dibaca dari profil perusahaannya; akun
                                 Personal memang tidak memilikinya. Nilainya ditetapkan
                                 server saat penawaran disimpan, jadi tidak ada kolom
                                 `company` yang dikirim formulir ini. --}}
                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                {{-- Alamat pengiriman diambil dari buku alamat akun
                                     (menu Alamat di dashboard), bukan diketik ulang di sini.

                                     Tiap alamat ditampilkan sebagai kartu berisi rincian
                                     lengkapnya — penerima, alamat, wilayah, kode pos — supaya
                                     pelanggan dapat memastikan tujuan pengirimannya sebelum
                                     mengirim permintaan. Alamat utama terpilih lebih dulu.

                                     Pilihannya memakai radio biasa dengan gaya lewat
                                     `peer-checked`, jadi tetap berfungsi tanpa JavaScript. --}}
                                @auth
                                    <div class="sm:col-span-2 rounded-2xl border border-ink-100 bg-ink-50/50 p-5">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="field-label">Alamat Pengiriman</span>
                                            <a href="{{ route('dashboard.addresses.index') }}" target="_blank" rel="noopener"
                                               class="text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">
                                                Kelola alamat &rarr;
                                            </a>
                                        </div>

                                        @if ($quotationAddresses->isNotEmpty())
                                            <p class="mt-1 text-xs text-ink-400">
                                                {{ $quotationAddresses->count() > 1
                                                    ? 'Pilih alamat tujuan pengiriman.'
                                                    : 'Paket dikirim ke alamat berikut.' }}
                                            </p>

                                            <div class="mt-4 grid gap-2.5">
                                                @foreach ($quotationAddresses as $address)
                                                    <div>
                                                        <input type="radio"
                                                               id="q-address-{{ $address->id }}"
                                                               name="address_id"
                                                               value="{{ $address->id }}"
                                                               class="peer sr-only"
                                                               @checked($address->is_default)>

                                                        <label for="q-address-{{ $address->id }}"
                                                               class="block cursor-pointer rounded-xl border border-ink-200 bg-white p-4 transition-all duration-200
                                                                      hover:border-brand-300
                                                                      peer-checked:border-brand-600 peer-checked:bg-brand-50
                                                                      peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-600">
                                                            <span class="flex flex-wrap items-center gap-2">
                                                                <span class="font-display text-sm font-bold text-ink-900">{{ $address->label }}</span>

                                                                @if ($address->is_default)
                                                                    <span class="rounded-full bg-brand-600 px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.1em] text-white">Utama</span>
                                                                @endif

                                                                @unless ($address->isComplete())
                                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.1em] text-amber-800">Belum Lengkap</span>
                                                                @endunless
                                                            </span>

                                                            <span class="mt-2 grid gap-1.5 text-xs sm:grid-cols-[7rem_1fr]">
                                                                <span class="font-semibold uppercase tracking-[0.1em] text-ink-400">Penerima</span>
                                                                <span class="text-ink-800">{{ $address->recipient_name }} &middot; {{ $address->recipient_phone }}</span>

                                                                <span class="font-semibold uppercase tracking-[0.1em] text-ink-400">Alamat</span>
                                                                <span class="text-ink-800">{{ $address->detail }}</span>

                                                                <span class="font-semibold uppercase tracking-[0.1em] text-ink-400">Wilayah</span>
                                                                <span class="text-ink-800">{{ $address->region_line ?: '-' }}</span>

                                                                @if ($address->note)
                                                                    <span class="font-semibold uppercase tracking-[0.1em] text-ink-400">Catatan</span>
                                                                    <span class="text-ink-600">{{ $address->note }}</span>
                                                                @endif
                                                            </span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="mt-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-900">
                                                Anda belum menyimpan alamat pengiriman.
                                                <a href="{{ route('dashboard.addresses.index') }}" target="_blank" rel="noopener" class="font-semibold underline">Tambahkan alamat</a>
                                                lebih dulu agar paket dapat langsung kami kirim setelah produksi selesai.
                                                Permintaan tetap dapat dikirim tanpa alamat, dan tim kami akan menanyakannya saat review.
                                            </p>
                                        @endif

                                        <p class="field-error" style="display: none" data-error-for="address_id"></p>
                                    </div>
                                @endauth

                                <div class="sm:col-span-2">
                                    <label for="q-notes" class="field-label">Catatan Tambahan <span class="text-ink-300">(opsional)</span></label>
                                    <textarea id="q-notes" name="notes" rows="4" class="field-input" maxlength="2000"
                                              placeholder="Misalnya: warna yang diinginkan, toleransi kritis, atau tenggat waktu proyek."></textarea>
                                    <p class="field-error" style="display: none" data-error-for="notes"></p>
                                </div>
                            </div>

                            {{-- Kesalahan yang menyangkut berkas atau pengaturan salah satu printer --}}
                            <p class="field-error" style="display: none" data-error-for="items"></p>

                            {{-- Progres unggah. File model bisa berukuran ratusan MB, jadi
                                 pengiriman diberi bar dan persentase supaya jelas bahwa
                                 permintaannya sedang berjalan — bukan macet. --}}
                            <div class="mt-6" style="display: none" data-quotation-progress>
                                <div class="flex items-center justify-between gap-3 text-xs font-semibold text-ink-500">
                                    <span data-quotation-progress-label>Mengunggah file model…</span>
                                    <span class="font-mono text-ink-700" data-quotation-progress-value>0%</span>
                                </div>
                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-ink-100">
                                    <div class="h-full w-0 rounded-full bg-brand-600 transition-[width] duration-150 ease-out"
                                         data-quotation-progress-bar></div>
                                </div>
                                <p class="mt-2 text-[0.7rem] leading-relaxed text-ink-400">
                                    Jangan tutup atau muat ulang halaman ini sampai unggahan selesai.
                                </p>
                            </div>

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
                                    <p class="mt-1.5 font-mono text-xl font-bold text-brand-600" data-quotation-reference>-</p>
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

    {{-- Halaman ini berhenti setelah alur utamanya selesai: unggah model, atur
         spesifikasi, baca ringkasan, lalu minta penawaran. Kartu catatan &
         privasi dan CTA penutup sengaja tidak ada lagi supaya perhatian tidak
         beralih dari langkah terakhir. --}}

@endsection

@push('scripts')
    @vite('resources/js/model-viewer.js')
@endpush
