@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
        $angka = fn ($value) => number_format((int) $value, 0, ',', '.');
    @endphp

    {{-- ================= KARTU STATISTIK ================= --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Total Penawaran', 'value' => $angka($summary['quotations']), 'icon' => 'layers', 'accent' => false],
            ['label' => 'Pesanan Selesai', 'value' => $angka($summary['completed']), 'icon' => 'check', 'accent' => false],
            ['label' => 'Object 3D Dicetak', 'value' => $angka($summary['printed_models']), 'icon' => 'cube', 'accent' => false],
            ['label' => 'Total Pendapatan', 'value' => $rupiah($summary['revenue']), 'icon' => 'spark', 'accent' => true],
            ['label' => 'User Terdaftar', 'value' => $angka($summary['users']), 'icon' => 'users', 'accent' => false],
        ] as $card)
            <div class="rounded-2xl border p-5 shadow-card {{ $card['accent'] ? 'border-transparent bg-gradient-to-br from-brand-600 to-brand-800' : 'border-ink-100 bg-white' }}">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] {{ $card['accent'] ? 'text-white/70' : 'text-ink-400' }}">
                        {{ $card['label'] }}
                    </p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $card['accent'] ? 'bg-white/15 text-white' : 'bg-brand-600/10 text-brand-600' }}">
                        <x-dynamic-component :component="'icons.'.$card['icon']" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 font-display text-2xl font-bold {{ $card['accent'] ? 'text-white' : 'text-ink-900' }}">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ================= PERMINTAAN PEMBATALAN ================= --}}
    @if ($pending_cancellations->isNotEmpty())
        <section class="mt-6 rounded-2xl border-2 border-amber-300 bg-amber-50 p-6 shadow-card">
            <h2 class="flex items-center gap-2 font-display text-base font-bold text-amber-900">
                <x-icons.alert class="h-5 w-5" />
                Permintaan Pembatalan Menunggu Persetujuan
            </h2>

            <ul class="mt-4 space-y-2">
                @foreach ($pending_cancellations as $quotation)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-white px-4 py-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink-900">{{ $quotation->name }}</p>
                            <p class="font-mono text-[0.65rem] text-brand-600">{{ $quotation->tracking_number }}</p>
                        </div>
                        <a href="{{ route('admin.quotations.show', $quotation) }}" class="viewer-tool">Tinjau Pengajuan</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-12">

        {{-- ================= GRAFIK BULANAN ================= --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card lg:col-span-8">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-display text-base font-bold text-ink-900">Statistik 12 Bulan Terakhir</h2>
                    <p class="mt-1 text-xs text-ink-400">Penawaran masuk, pesanan selesai, dan pendapatannya.</p>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-[0.65rem] font-semibold text-ink-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-brand-600"></span>Penawaran Masuk</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>Selesai</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-accent-500"></span>Pendapatan</span>
                </div>
            </div>

            {{-- Grafik digambar sebagai batang CSS biasa: tanpa pustaka tambahan,
                 tetap terbaca saat dicetak, dan angkanya tersedia sebagai teks. --}}
            <div class="mt-6 overflow-x-auto">
                <div class="flex min-w-[640px] items-end gap-3" style="height: 15rem">
                    @foreach ($chart['months'] as $month)
                        @php
                            $incomingHeight = round(($month['incoming'] / $chart['max_incoming']) * 100, 1);
                            $completedHeight = round(($month['completed'] / $chart['max_incoming']) * 100, 1);
                            $revenueHeight = round(($month['revenue'] / $chart['max_revenue']) * 100, 1);
                        @endphp

                        <div class="flex h-full flex-1 flex-col justify-end">
                            <div class="flex h-full items-end justify-center gap-1"
                                 title="{{ $month['label'] }} — {{ $month['incoming'] }} masuk, {{ $month['completed'] }} selesai, {{ $rupiah($month['revenue']) }}">
                                <span class="w-2.5 rounded-t bg-brand-600 transition-all" style="height: {{ max($incomingHeight, 1) }}%"></span>
                                <span class="w-2.5 rounded-t bg-emerald-500 transition-all" style="height: {{ max($completedHeight, 1) }}%"></span>
                                <span class="w-2.5 rounded-t bg-accent-500 transition-all" style="height: {{ max($revenueHeight, 1) }}%"></span>
                            </div>
                            <p class="mt-2 text-center text-[0.6rem] font-semibold text-ink-400">{{ $month['short'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <table class="mt-6 w-full text-left text-xs">
                <caption class="sr-only">Rincian statistik bulanan</caption>
                <thead>
                    <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                        <th scope="col" class="py-2 font-bold">Bulan</th>
                        <th scope="col" class="py-2 text-right font-bold">Masuk</th>
                        <th scope="col" class="py-2 text-right font-bold">Selesai</th>
                        <th scope="col" class="py-2 text-right font-bold">Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @foreach (array_slice($chart['months'], -6) as $month)
                        <tr>
                            <th scope="row" class="py-2 font-semibold text-ink-700">{{ $month['label'] }}</th>
                            <td class="py-2 text-right text-ink-600">{{ $month['incoming'] }}</td>
                            <td class="py-2 text-right text-ink-600">{{ $month['completed'] }}</td>
                            <td class="py-2 text-right font-semibold text-ink-800">{{ $rupiah($month['revenue']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        {{-- ================= SEBARAN STATUS ================= --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card lg:col-span-4">
            <h2 class="font-display text-base font-bold text-ink-900">Sebaran Status</h2>

            <ul class="mt-5 space-y-3">
                @foreach ($status_breakdown as $status)
                    <li>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate text-ink-600">{{ $status['label'] }}</span>
                            <span class="font-bold text-ink-900">{{ $status['total'] }}</span>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-ink-100">
                            <span class="block h-full rounded-full bg-brand-600"
                                  style="width: {{ $summary['quotations'] > 0 ? round(($status['total'] / $summary['quotations']) * 100, 1) : 0 }}%"></span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- ================= PENAWARAN TERBARU ================= --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-5">
            <h2 class="font-display text-base font-bold text-ink-900">Penawaran Terbaru</h2>
            <a href="{{ route('admin.quotations.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat semua &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                        <th scope="col" class="px-6 py-3 font-bold">Nomor</th>
                        <th scope="col" class="px-6 py-3 font-bold">Pelanggan</th>
                        <th scope="col" class="px-6 py-3 font-bold">File</th>
                        <th scope="col" class="px-6 py-3 font-bold">Nilai</th>
                        <th scope="col" class="px-6 py-3 font-bold">Status</th>
                        <th scope="col" class="px-6 py-3 font-bold">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($recent as $quotation)
                        <tr class="transition-colors hover:bg-brand-50/40">
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.quotations.show', $quotation) }}" class="font-mono text-xs font-semibold text-brand-600 hover:text-brand-700">
                                    {{ $quotation->tracking_number }}
                                </a>
                            </td>
                            <td class="px-6 py-3 font-semibold text-ink-800">{{ $quotation->name }}</td>
                            <td class="px-6 py-3 text-ink-600">{{ $quotation->items_count }} file</td>
                            <td class="px-6 py-3 font-semibold text-ink-800">{{ $rupiah($quotation->display_price) }}</td>
                            <td class="px-6 py-3">
                                <span class="rounded-full border border-ink-200 bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">
                                    {{ $quotation->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-xs text-ink-500">{{ $quotation->created_at->translatedFormat('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-ink-400">Belum ada penawaran yang masuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
