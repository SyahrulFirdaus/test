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
                        <a href="{{ staff_route('quotations.show', $quotation) }}" class="viewer-tool">Tinjau Pengajuan</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ================= SEBARAN STATUS =================
         Tiap kartu sekaligus menjadi tombol filter: mengekliknya menyaring
         daftar Penawaran Terbaru di bawah, mengeklik status yang sedang aktif
         melepas filternya kembali.

         Sejak grafik bulanan pindah ke Dashboard Superadmin, bagian ini berdiri
         sendiri selebar halaman — statusnya karena itu disusun sebagai kartu
         berdampingan, bukan daftar memanjang ke bawah. --}}
    <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
        <h2 class="font-display text-base font-bold text-ink-900">Sebaran Status</h2>
        <p class="mt-1 text-xs text-ink-400">Pilih salah satu untuk menyaring daftar penawaran di bawah.</p>

        <ul class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($status_breakdown as $status)
                @php
                    $isActive = $filters["status"] === $status["key"];
                    $share = $summary["quotations"] > 0 ? round(($status["total"] / $summary["quotations"]) * 100, 1) : 0;
                @endphp

                <li>
                    <a href="{{ staff_route("dashboard", $isActive ? [] : ["status" => $status["key"]]) }}#penawaran-terbaru"
                       @class([
                           "block h-full rounded-xl border p-4 transition-colors",
                           "border-brand-300 bg-brand-50" => $isActive,
                           "border-ink-100 hover:border-brand-200 hover:bg-ink-50" => ! $isActive,
                       ])
                       @if ($isActive) aria-current="true" @endif>
                        <span class="flex items-start justify-between gap-3">
                            <span @class([
                                "text-xs font-semibold leading-snug",
                                "text-brand-700" => $isActive,
                                "text-ink-600" => ! $isActive,
                            ])>{{ $status["label"] }}</span>

                            <span @class([
                                "font-display text-lg font-bold leading-none",
                                "text-brand-700" => $isActive,
                                "text-ink-900" => ! $isActive,
                            ])>{{ $angka($status["total"]) }}</span>
                        </span>

                        <span class="mt-3 block h-1.5 overflow-hidden rounded-full bg-ink-100">
                            <span @class(["block h-full rounded-full", "bg-brand-700" => $isActive, "bg-brand-600" => ! $isActive])
                                  style="width: {{ $share }}%"></span>
                        </span>

                        <span class="mt-1.5 block text-[0.65rem] text-ink-400">{{ $share }}% dari total</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ================= PENAWARAN TERBARU ================= --}}
    <section id="penawaran-terbaru" class="mt-6 scroll-mt-24 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="border-b border-ink-100 px-6 py-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-base font-bold text-ink-900">Penawaran Terbaru</h2>
                    <p class="mt-1 text-xs text-ink-400">
                        @if ($filters['status'])
                            {{ number_format($recent_total, 0, ',', '.') }} penawaran berstatus
                            &ldquo;{{ $statuses[$filters['status']] }}&rdquo;{{ $recent_total > $recent->count() ? ', menampilkan '.$recent->count().' terbaru' : '' }}.
                        @else
                            Seluruh status. Pilih status tertentu untuk menyaring daftar ini.
                        @endif
                    </p>
                </div>

                {{-- Filter dikirim lewat GET biasa: hasilnya tersimpan di URL,
                     jadi dapat ditandai atau dibagikan, dan tetap berfungsi
                     tanpa JavaScript. --}}
                <form method="GET" action="{{ staff_route('dashboard') }}" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="status" class="sr-only">Filter status</label>
                        <select id="status" name="status" class="field-input mt-0 py-2.5 text-xs">
                            <option value="">Semua status</option>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn-primary px-5 py-2.5 text-xs">Terapkan</button>

                    @if ($filters['status'])
                        <a href="{{ staff_route('dashboard') }}" class="btn-outline px-5 py-2.5 text-xs">Reset</a>
                    @endif
                </form>
            </div>

            <a href="{{ staff_route('quotations.index', array_filter(['status' => $filters['status']])) }}"
               class="mt-3 inline-block text-sm font-semibold text-brand-600 hover:text-brand-700">
                Lihat semua di halaman Penawaran &rarr;
            </a>
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
                                <a href="{{ staff_route('quotations.show', $quotation) }}" class="font-mono text-xs font-semibold text-brand-600 hover:text-brand-700">
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
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-ink-400">
                                @if ($filters['status'])
                                    Belum ada penawaran berstatus &ldquo;{{ $statuses[$filters['status']] }}&rdquo;.
                                    <a href="{{ staff_route('dashboard') }}" class="font-semibold text-brand-600 hover:text-brand-700">Tampilkan semua status</a>
                                @else
                                    Belum ada penawaran yang masuk.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
