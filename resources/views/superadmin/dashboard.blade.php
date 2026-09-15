@extends("layouts.dashboard")

@section("title", "Dashboard Superadmin")

@section("content")
    @php
        $rupiah = fn ($value) => "Rp".number_format((float) $value, 0, ",", ".");
        $angka = fn ($value) => number_format((int) $value, 0, ",", ".");
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink-900">Dashboard Superadmin</h1>
            <p class="mt-1 text-sm text-ink-500">Gambaran keseluruhan sistem NUSAMA3D, dihitung langsung dari basis data.</p>
        </div>
        <a href="{{ route("admin.dashboard") }}" class="viewer-tool">Dashboard Operasional Admin</a>
    </div>

    {{-- ================= KARTU STATISTIK =================
         Dua angka uang sengaja bersebelahan: Pendapatan Kotor adalah seluruh
         Harga Jual yang tertagih, Profit Bersih adalah bagian yang benar-benar
         menjadi keuntungan sesudah modal. Keterangan di bawah Profit Bersih
         menyebut marginnya supaya keduanya dapat dibaca sebagai sepasang. --}}
    @php
        $margin = $summary["revenue"] > 0
            ? round(($summary["net_profit"] / $summary["revenue"]) * 100, 1)
            : 0;
    @endphp

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ["label" => "Total Penawaran", "value" => $angka($summary["quotations"]), "icon" => "layers", "accent" => false],
            ["label" => "Pesanan Selesai", "value" => $angka($summary["completed"]), "icon" => "check", "accent" => false],
            ["label" => "Object 3D Diproses", "value" => $angka($summary["printed_models"]), "icon" => "cube", "accent" => false],
            ["label" => "Total Pendapatan Kotor", "value" => $rupiah($summary["revenue"]), "icon" => "spark", "accent" => true,
             "note" => "Seluruh Harga Jual penawaran yang selesai."],
            ["label" => "Profit Bersih", "value" => $rupiah($summary["net_profit"]), "icon" => "tag", "accent" => true,
             "note" => "Penjumlahan komponen Profit tiap penawaran &middot; margin {$margin}%"],
            ["label" => "Total User", "value" => $angka($summary["users"]), "icon" => "users", "accent" => false],
            ["label" => "Akun Admin", "value" => $angka($summary["admins"]), "icon" => "shield", "accent" => false],
        ] as $card)
            <div class="rounded-2xl border p-5 shadow-card {{ $card["accent"] ? "border-transparent bg-gradient-to-br from-brand-600 to-brand-800" : "border-ink-100 bg-white" }}">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] {{ $card["accent"] ? "text-white/70" : "text-ink-400" }}">
                        {{ $card["label"] }}
                    </p>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $card["accent"] ? "bg-white/15 text-white" : "bg-brand-600/10 text-brand-600" }}">
                        <x-dynamic-component :component="'icons.'.$card['icon']" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 font-display text-2xl font-bold {{ $card["accent"] ? "text-white" : "text-ink-900" }}">{{ $card["value"] }}</p>

                @isset($card["note"])
                    <p class="mt-1.5 text-[0.65rem] leading-relaxed {{ $card["accent"] ? "text-white/70" : "text-ink-400" }}">{!! $card["note"] !!}</p>
                @endisset
            </div>
        @endforeach
    </div>

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
                                 title="{{ $month['label'] }}: {{ $month['incoming'] }} masuk, {{ $month['completed'] }} selesai, {{ $rupiah($month['revenue']) }}">
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
            <p class="mt-1 text-xs text-ink-400">Posisi seluruh penawaran saat ini.</p>

            <ul class="mt-5 space-y-2.5">
                @foreach ($status_breakdown as $status)
                    <li class="flex items-center justify-between gap-3 rounded-xl border border-ink-100 px-4 py-2.5">
                        <span class="text-sm font-semibold text-ink-700">{{ $status["label"] }}</span>
                        <span class="font-display text-sm font-bold text-brand-700">{{ $angka($status["total"]) }}</span>
                    </li>
                @endforeach
            </ul>

            <a href="{{ staff_route('quotations.index') }}" class="btn-outline mt-5 w-full justify-center">Buka Daftar Penawaran</a>
        </section>
    </div>
@endsection