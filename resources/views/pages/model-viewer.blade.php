@extends('layouts.app')

@section('title', 'Viewer 3D')
@section('description', 'Viewer 3D satu model beserta seluruh analisis kelayakan cetak, pengaturan printing, dan estimasi biayanya.')

@section('content')

    {{-- Halaman ini dibuka dari daftar pada halaman 3D Models. Modelnya tidak
         dikirim lewat URL — berkasnya tetap berada di browser pengguna dan
         dipanggil JavaScript memakai id pada query string. --}}
    <section class="bg-ink-950 pb-10 pt-36">
        <div class="container-page">
            <a href="{{ route('models') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-white/70 transition-colors hover:text-white">
                &larr; Kembali ke daftar model
            </a>

            <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-400">Viewer 3D</p>
                    <h1 class="mt-2 truncate font-display text-2xl font-bold text-white sm:text-3xl" data-detail-title>Memuat model…</h1>
                    <p class="mt-2 text-sm text-white/60" data-detail-subtitle>
                        Rotate, zoom, pan, analisis kelayakan, dan seluruh simulasi cetak tersedia di halaman ini.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="section pt-10">
        <div class="container-page">
            <div data-model-detail data-back-url="{{ route('models') }}">

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
                                Viewer 3D memerlukan WebGL untuk menggambar model. Silakan gunakan versi terbaru Chrome, Edge,
                                Firefox, atau Safari, lalu pastikan akselerasi perangkat keras aktif pada pengaturan browser.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Indikator saat model diambil dari penyimpanan browser --}}
                <div class="flex items-center gap-4 rounded-2xl border border-ink-100 bg-white p-6 shadow-card" data-detail-loading>
                    <span class="h-10 w-10 animate-spin rounded-full border-4 border-brand-100 border-t-brand-600"></span>
                    <p class="text-sm font-semibold text-ink-700">Memuat model dari penyimpanan browser…</p>
                </div>

                {{-- Model tidak ditemukan: id salah, sudah dihapus, atau dibuka di browser lain --}}
                <div class="rounded-2xl border border-brand-200 bg-brand-50 p-8 text-center" style="display: none" data-detail-missing>
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-white">
                        <x-icons.alert class="h-7 w-7" />
                    </span>
                    <h2 class="mt-5 font-display text-lg font-bold text-brand-900">Model tidak ditemukan</h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-brand-800">
                        Model yang Anda buka sudah dihapus dari daftar, atau tautan ini dibuka pada browser maupun perangkat
                        yang berbeda. File 3D disimpan di browser Anda sendiri dan tidak pernah dikirim ke server sebelum
                        penawaran dibuat, sehingga tidak dapat dibuka dari tempat lain.
                    </p>
                    <a href="{{ route('models') }}" class="btn-primary mt-6">Kembali ke 3D Models</a>
                </div>

                {{-- Card viewer: markup yang sama persis dengan yang dipakai halaman daftar --}}
                <div style="display: none" data-detail-card>
                    <x-model-check.printer-card card-id="view" />
                </div>

                <p class="mt-6 rounded-2xl bg-ink-50 p-5 text-xs leading-relaxed text-ink-500">
                    Perubahan pengaturan pada halaman ini otomatis tersimpan dan langsung tercermin pada daftar model di
                    halaman 3D Models, termasuk gambar pratinjaunya bila Anda mengubah skala, orientasi, atau warna material.
                </p>
            </div>
        </div>
    </section>

@endsection

@push('scripts')
    @vite('resources/js/model-detail.js')
@endpush
