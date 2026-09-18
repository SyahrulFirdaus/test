@extends('layouts.dashboard')

@section('title', 'Lihat 3D · '.$item->file_name)

@section('content')
    @php
        $material = \App\Support\MaterialCatalog::displayName((string) $item->technology, (string) $item->material) ?: $item->material;
    @endphp

    <div>
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
            &larr; Kembali ke Penawaran
        </a>

        {{-- ============ INFORMASI MODEL ============ --}}
        <section class="mt-5 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-brand-600">
                        Printer {{ $item->position }} &middot; {{ $quotation->tracking_number }}
                    </p>
                    <h2 class="mt-1 break-all font-display text-lg font-bold text-ink-900">{{ $item->file_name }}</h2>
                </div>

                @if ($fileAvailable)
                    <a href="{{ $fileUrl }}" class="viewer-tool">Unduh {{ $item->file_format }}</a>
                @endif
            </div>

            <dl class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    'Nama File' => $item->file_name,
                    'Mesin' => $item->printer_label ?: ($item->printer_name ?: '-'),
                    'Technology' => $item->technology ?: '-',
                    'Material' => $material ?: '-',
                ] as $label => $value)
                    <div class="min-w-0">
                        <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                        <dd class="mt-1.5 break-all text-sm font-semibold text-ink-800">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- ============ 3D VIEWER ============
             Hanya pratinjau: berkas diambil apa adanya lewat route unduh model
             dan digambar di browser. Tidak ada yang disimpan kembali. --}}
        <section class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card"
                 data-admin-model-viewer
                 data-viewer-fullscreen-target
                 data-file-url="{{ $fileAvailable ? $fileUrl : '' }}"
                 data-file-name="{{ $item->file_name }}">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-5 py-4">
                <p class="text-xs text-ink-400">
                    Seret untuk memutar &middot; scroll untuk zoom &middot; klik kanan + seret untuk menggeser
                </p>

                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['iso' => 'Isometrik', 'front' => 'Depan', 'side' => 'Samping', 'top' => 'Atas'] as $key => $label)
                        <button type="button" class="view-preset" data-view="{{ $key }}" disabled>{{ $label }}</button>
                    @endforeach
                    <button type="button" class="view-preset" data-wireframe aria-pressed="false" disabled>Wireframe</button>
                    <button type="button" class="view-preset" data-fullscreen disabled>Layar Penuh</button>
                </div>
            </div>

            <div class="relative h-[65vh] min-h-[420px] bg-gradient-to-b from-ink-50 to-white" data-viewer-canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-sm text-ink-400" data-viewer-loading>
                    <span class="h-8 w-8 animate-spin rounded-full border-2 border-ink-200 border-t-brand-600"></span>
                    Memuat object 3D&hellip;
                </div>

                <div class="absolute inset-0 hidden flex-col items-center justify-center gap-3 px-6 text-center" data-viewer-error>
                    <x-icons.alert class="h-8 w-8 text-amber-600" />
                    <p class="max-w-md text-sm font-semibold text-ink-700">
                        Object 3D tidak dapat ditampilkan. Silakan unduh file untuk melihatnya secara lokal.
                    </p>
                    <p class="max-w-md text-xs text-ink-400" data-viewer-error-detail></p>
                    @if ($fileAvailable)
                        <a href="{{ $fileUrl }}" class="btn-outline mt-1 px-5 py-2.5 text-xs">Unduh {{ $item->file_format }}</a>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 px-5 py-3 text-xs text-ink-500">
                <span data-viewer-dimensions>Dimensi: -</span>
                <span data-viewer-triangles>Segitiga: -</span>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin-model-viewer.js')
@endpush
