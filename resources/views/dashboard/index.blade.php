@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    <div class="rounded-3xl border border-transparent bg-gradient-to-br from-brand-700 to-brand-950 p-7 text-white shadow-card sm:p-9">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Selamat datang kembali</p>
        <h2 class="mt-2 font-display text-2xl font-bold sm:text-3xl">{{ auth()->user()->name }}</h2>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/75">
            Unggah model di halaman 3D Models, atur simulasi cetaknya, lalu kirim penawaran. Seluruh perkembangannya
            dapat Anda pantau dari sini.
        </p>

        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('models') }}" class="btn-primary bg-white text-brand-700 shadow-none hover:bg-white/90 hover:text-brand-800">
                Buat Penawaran Baru
                <x-icons.arrow-right class="h-4 w-4" />
            </a>
            <a href="{{ route('dashboard.quotations.index') }}" class="btn-ghost-light">Lihat Penawaran Saya</a>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Penawaran', 'value' => number_format($summary['total'], 0, ',', '.')],
            ['label' => 'Sedang Berjalan', 'value' => number_format($summary['active'], 0, ',', '.')],
            ['label' => 'Pesanan Selesai', 'value' => number_format($summary['completed'], 0, ',', '.')],
            ['label' => 'Total File 3D', 'value' => number_format($summary['models'], 0, ',', '.')],
        ] as $card)
            <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                <p class="mt-2 font-display text-2xl font-bold text-ink-900">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-12">

        {{-- Penawaran terbaru --}}
        <section class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card lg:col-span-8">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-5">
                <h2 class="font-display text-base font-bold text-ink-900">Penawaran Terbaru</h2>
                <a href="{{ route('dashboard.quotations.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat semua &rarr;</a>
            </div>

            <ul class="divide-y divide-ink-100">
                @forelse ($recent as $quotation)
                    <li class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="font-mono text-xs font-semibold text-brand-600 hover:text-brand-700">
                                {{ $quotation->tracking_number }}
                            </a>
                            <p class="mt-1 text-sm text-ink-600">
                                {{ $quotation->items_count }} file &middot; {{ $rupiah($quotation->display_price) }}
                            </p>
                            <p class="text-xs text-ink-400">{{ $quotation->created_at->translatedFormat('d F Y') }}</p>
                        </div>

                        <span class="rounded-full border border-ink-200 bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">
                            {{ $quotation->status_label }}
                        </span>
                    </li>
                @empty
                    <li class="px-6 py-14 text-center">
                        <p class="font-semibold text-ink-700">Belum ada penawaran.</p>
                        <p class="mt-1.5 text-sm text-ink-400">Mulai dari halaman 3D Models untuk mengunggah model 3D Anda.</p>
                        <a href="{{ route('models') }}" class="btn-primary mt-5">Buka 3D Models</a>
                    </li>
                @endforelse
            </ul>
        </section>

        {{-- Notifikasi terbaru --}}
        <section class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card lg:col-span-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-5">
                <h2 class="font-display text-base font-bold text-ink-900">Notifikasi</h2>
                <a href="{{ route('dashboard.notifications.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Semua &rarr;</a>
            </div>

            <ul class="divide-y divide-ink-100">
                @forelse ($notifications as $notification)
                    <li class="px-6 py-4 {{ $notification->read_at === null ? 'bg-brand-50/40' : '' }}">
                        <p class="text-sm font-bold text-ink-900">{{ $notification->data['title'] ?? 'Notifikasi' }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-ink-500">{{ $notification->data['message'] ?? '' }}</p>
                        <p class="mt-1 text-[0.65rem] text-ink-400">{{ $notification->created_at->diffForHumans() }}</p>
                    </li>
                @empty
                    <li class="px-6 py-14 text-center text-sm text-ink-400">Belum ada notifikasi.</li>
                @endforelse
            </ul>
        </section>
    </div>
@endsection
