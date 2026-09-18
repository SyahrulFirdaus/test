@extends('layouts.app')

@section('title', '3D Viewer')
@section('description', 'Pratinjau 3D satu model: putar, zoom, dan geser object dari segala sudut.')

{{-- Layar penuh: tanpa navbar, footer, maupun tombol kembali ke atas. --}}
@section('bare', true)

@section('content')

    {{-- Halaman ini hanya pratinjau. Modelnya tidak diambil dari server: id pada
         URL menunjuk berkas yang tersimpan di browser pengunjung (IndexedDB)
         sejak diunggah di halaman 3D Models, dan dibaca oleh
         resources/js/model-preview.js. Spesifikasi cetak, berat, dan harga
         sengaja tidak ada di sini. --}}
    <div class="flex h-dvh flex-col overflow-hidden bg-white"
         data-model-preview
         data-model-id="{{ $modelId }}">

        {{-- ================= BAR ATAS ================= --}}
        <header class="flex shrink-0 items-center gap-3 border-b border-ink-100 bg-white px-3 py-2.5 sm:gap-4 sm:px-5">
            <a href="{{ route('models') }}"
               class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-ink-200 px-3 py-1.5 text-xs font-semibold text-ink-700 transition-colors hover:border-brand-300 hover:text-brand-600 sm:px-4 sm:text-sm">
                &larr; <span class="hidden sm:inline">Kembali ke 3D Models</span><span class="sm:hidden">Kembali</span>
            </a>

            <div class="min-w-0 flex-1">
                <p class="text-[0.6rem] font-semibold uppercase tracking-[0.2em] text-brand-600">3D Viewer</p>
                <h1 class="truncate font-display text-sm font-bold text-ink-900 sm:text-base" data-model-info="name">Memuat model&hellip;</h1>
            </div>
        </header>

        {{-- ================= AREA VIEWER ================= --}}
        <div class="relative min-h-0 flex-1 bg-gradient-to-b from-ink-50 to-white" data-viewer-canvas>
            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-sm text-ink-400" data-viewer-loading>
                <span class="h-8 w-8 animate-spin rounded-full border-2 border-ink-200 border-t-brand-600"></span>
                Memuat object 3D&hellip;
            </div>

            {{-- Model tidak ada di browser ini: sudah dihapus, id salah, atau
                 tautan dibuka di browser/perangkat lain. --}}
            <div class="absolute inset-0 hidden flex-col items-center justify-center gap-3 px-6 text-center" data-viewer-state="missing">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-white">
                    <x-icons.alert class="h-6 w-6" />
                </span>
                <h2 class="font-display text-lg font-bold text-ink-900">Model tidak ditemukan</h2>
                <p class="max-w-md text-sm leading-relaxed text-ink-500">
                    Model ini sudah dihapus dari daftar, atau tautannya dibuka di browser/perangkat lain.
                    File 3D tersimpan di browser Anda sendiri sampai penawaran dikirim.
                </p>
                <a href="{{ route('models') }}" class="btn-primary mt-2">Kembali ke 3D Models</a>
            </div>

            <div class="absolute inset-0 hidden flex-col items-center justify-center gap-3 px-6 text-center" data-viewer-state="error">
                <x-icons.alert class="h-8 w-8 text-amber-600" />
                <p class="max-w-md text-sm font-semibold text-ink-700">Object 3D tidak dapat ditampilkan.</p>
                <p class="max-w-md text-xs text-ink-400" data-viewer-state-detail></p>
                <a href="{{ route('models') }}" class="btn-outline mt-1">Kembali ke 3D Models</a>
            </div>

            {{-- Petunjuk gestur, melayang di pojok kiri atas area viewer. --}}
            <p class="pointer-events-none absolute left-3 top-3 max-w-[70%] rounded-lg bg-white/80 px-2.5 py-1.5 text-[0.65rem] leading-relaxed text-ink-500 backdrop-blur sm:left-5 sm:top-4 sm:text-xs"
               data-viewer-hint>
                <span class="hidden sm:inline">Seret untuk memutar &middot; scroll untuk zoom &middot; klik kanan + seret untuk menggeser</span>
                <span class="sm:hidden">Satu jari memutar &middot; cubit untuk zoom &middot; dua jari menggeser</span>
            </p>
        </div>

        {{-- ================= BAR BAWAH: KONTROL & INFORMASI ================= --}}
        <footer class="shrink-0 border-t border-ink-100 bg-white" data-model-info-panel>
            <div class="flex flex-col gap-3 px-3 py-3 lg:flex-row lg:items-center lg:justify-between lg:gap-6 lg:px-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3">
                    {{-- Rotasi object: memutar model itu sendiri 90° per klik pada
                         sumbu slicer (Z ke atas). Hanya tampilan — berkas asli,
                         spesifikasi, dan harga tidak berubah. --}}
                    <div class="flex items-center gap-2" role="group" aria-label="Rotasi object">
                        <span class="shrink-0 text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Rotasi<br class="sm:hidden"> Object</span>
                        <div class="grid flex-1 grid-cols-3 gap-2 sm:flex sm:flex-none">
                            @foreach (['x' => 'text-red-600', 'y' => 'text-emerald-600', 'z' => 'text-blue-600'] as $axis => $color)
                                <button type="button" class="view-preset gap-1" data-rotate-object="{{ $axis }}" disabled
                                        title="Putar object 90° pada sumbu {{ strtoupper($axis) }}"
                                        aria-label="Putar object 90 derajat pada sumbu {{ strtoupper($axis) }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                                    <span class="whitespace-nowrap"><span class="font-bold {{ $color }}">{{ strtoupper($axis) }}</span> 90&deg;</span>
                                </button>
                            @endforeach
                        </div>
                        <span class="hidden whitespace-nowrap font-mono text-[0.65rem] text-ink-400 sm:inline" data-rotation-readout>X 0° · Y 0° · Z 0°</span>
                    </div>

                    <div class="grid grid-cols-4 gap-2 sm:flex sm:shrink-0">
                        <button type="button" class="view-preset gap-1.5" data-viewer-action="rotate" aria-pressed="false" disabled
                                title="Kamera berputar mengelilingi object">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                            <span class="whitespace-nowrap"><span class="hidden sm:inline">Putar </span>Otomatis</span>
                        </button>
                        <button type="button" class="view-preset gap-1.5" data-viewer-action="zoom-in" disabled aria-label="Zoom in">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-3.5 w-3.5 shrink-0" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M11 8v6M8 11h6"/></svg>
                            <span class="whitespace-nowrap"><span class="hidden sm:inline">Zoom </span>+</span>
                        </button>
                        <button type="button" class="view-preset gap-1.5" data-viewer-action="zoom-out" disabled aria-label="Zoom out">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-3.5 w-3.5 shrink-0" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3M8 11h6"/></svg>
                            <span class="whitespace-nowrap"><span class="hidden sm:inline">Zoom </span>&minus;</span>
                        </button>
                        <button type="button" class="view-preset gap-1.5" data-viewer-action="reset" disabled
                                title="Kembalikan sudut pandang dan orientasi object">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 shrink-0" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                            Reset
                        </button>
                    </div>
                </div>

                <dl class="grid min-w-0 grid-cols-3 gap-x-4 gap-y-1 sm:flex sm:flex-wrap sm:gap-x-6 lg:justify-end">
                    @foreach (['format' => 'Format', 'dimensions' => 'Dimensi', 'volume' => 'Volume'] as $key => $label)
                        <div class="min-w-0">
                            <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                            <dd class="truncate text-xs font-semibold text-ink-800 sm:text-sm" data-model-info="{{ $key }}">-</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </footer>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/model-preview.js')
@endpush
