@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        /*
         * Warna badge status. Ditulis utuh per kelas — bukan disusun dari
         * potongan nama — agar seluruhnya ikut terbawa saat Tailwind memindai
         * berkas ini.
         */
        $badges = [
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'brand' => 'border-brand-200 bg-brand-50 text-brand-700',
            'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
            'rose' => 'border-rose-200 bg-rose-50 text-rose-700',
            'ink' => 'border-ink-200 bg-ink-50 text-ink-600',
        ];

        $badge = function (string $status) use ($badges) {
            $tone = match (true) {
                $status === \App\Support\QuotationStatus::COMPLETED => 'emerald',
                in_array($status, \App\Support\QuotationStatus::cancelledKeys(), true) => 'rose',
                $status === \App\Support\QuotationStatus::PAYMENT_REJECTED => 'rose',
                in_array($status, \App\Support\QuotationStatus::paymentKeys(), true) => 'amber',
                in_array($status, \App\Support\QuotationStatus::productionKeys(), true) => 'brand',
                default => 'ink',
            };

            return $badges[$tone];
        };
    @endphp

    @php
        $angka = fn ($value) => number_format((float) $value, 0, ',', '.');

        // Angka besar diringkas agar kartu tidak pecah di layar sempit:
        // 1.250.000 dibaca "Rp1,25 jt".
        $ringkas = function ($value) {
            $value = (float) $value;

            return $value >= 1_000_000
                ? 'Rp'.rtrim(rtrim(number_format($value / 1_000_000, 2, ',', '.'), '0'), ',').' jt'
                : 'Rp'.number_format($value, 0, ',', '.');
        };

        $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    @endphp

    {{-- ============================== HEADER ============================== --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h2 class="font-display text-2xl font-bold text-ink-900 sm:text-[1.75rem]">Halo, {{ $firstName }}</h2>
            <p class="mt-1 text-sm text-ink-500">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('dashboard.quotations.index') }}" class="btn-outline px-5 py-2.5">Riwayat Pesanan</a>
            <a href="{{ route('models') }}" class="btn-primary px-5 py-2.5">+ Buat Penawaran</a>
        </div>
    </div>

    {{-- ======================= PENGINGAT PEMBAYARAN ======================= --}}
    {{-- Kartu ini hanya muncul bila memang ada yang harus dibayar. --}}
    @if ($paymentDue)
        <div class="mt-6 flex flex-wrap items-center justify-between gap-5 rounded-3xl border-2 border-brand-300 bg-brand-50 p-6 shadow-card">
            <div class="flex min-w-0 items-center gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-600 text-white">
                    <x-icons.clock class="h-6 w-6" />
                </span>

                <div class="min-w-0">
                    <p class="font-display text-base font-bold text-brand-900">Pembayaran Menunggu</p>
                    <p class="mt-0.5 text-sm text-brand-800">
                        <span class="font-mono font-semibold">{{ $paymentDue->tracking_number }}</span>
                        &middot; <span class="font-bold">{{ $rupiah($paymentDue->payment_amount) }}</span>
                        @if ($paymentDue->payment_due_at)
                            &middot; batas {{ $paymentDue->payment_due_at->translatedFormat('d F Y, H:i') }} WIB
                        @endif
                    </p>
                </div>
            </div>

            <a href="{{ route('dashboard.quotations.payment', $paymentDue) }}" class="btn-primary shrink-0 px-6 py-3">
                Bayar Sekarang
            </a>
        </div>
    @endif

    {{-- ======================= RINGKASAN & GRAFIK ======================= --}}
    <div class="mt-6 grid gap-5 lg:grid-cols-12">

        {{-- Empat kartu statistik: yang pertama kartu utama berwarna brand. --}}
        <div class="grid gap-5 sm:grid-cols-2 lg:col-span-8">
            <x-dashboard.stat-card hero label="Total Belanja" :value="$ringkas($stats['spending'])"
                                   note="Pembayaran diterima vs bulan lalu" icon="spark" :trend="$insights['trends']['spending']" />

            <x-dashboard.stat-card label="Penawaran" :value="$angka($stats['quotations'])"
                                   note="Penawaran baru vs bulan lalu" icon="layers" :trend="$insights['trends']['quotations']" />

            <x-dashboard.stat-card label="Menunggu Bayar" :value="$angka($stats['awaiting_payment'])"
                                   note="Penawaran yang perlu dibayar" icon="clock"
                                   :pill="$paymentDue ? ['text' => 'Segera bayar', 'class' => 'bg-rose-100 text-rose-700'] : null" />

            <x-dashboard.stat-card label="Pesanan Aktif" :value="$angka($stats['active_orders'])"
                                   :note="$angka($stats['completed']).' pesanan selesai'" icon="printer" :trend="$insights['trends']['orders']" />
        </div>

        <x-dashboard.order-statistic class="lg:col-span-4"
                                     :distribution="$insights['distribution']"
                                     :trend="$insights['trends']['quotations']"
                                     title="Statistik Pesanan"
                                     subtitle="Sebaran penawaran menurut tahapnya"
                                     noun="Penawaran" />

        <x-dashboard.activity-chart class="lg:col-span-8"
                                    :monthly="$insights['monthly']"
                                    title="Aktivitas Penawaran"
                                    subtitle="Penawaran yang dibuat dan yang berlanjut menjadi pesanan"
                                    noun="penawaran" />

        {{-- ========================= STATUS PESANAN ========================= --}}
        <section class="flex flex-col rounded-3xl border border-ink-100/80 bg-white p-6 shadow-card lg:col-span-4">
            <div>
                <h3 class="font-display text-lg font-bold text-ink-900">Status Pesanan</h3>
                <p class="mt-0.5 text-xs text-ink-400">Posisi penawaran terakhir Anda saat ini</p>
            </div>

            @if ($tracked)
                @php
                    /*
                     * Empat tahap ringkas untuk pelanggan Personal. Sepuluh tahap
                     * penuh tetap ada di halaman tracking; di sini yang ditampilkan
                     * hanya yang bermakna bagi pemesan perorangan.
                     */
                    $position = \App\Support\QuotationStatus::position(
                        \App\Support\QuotationStatus::timelineAnchor($tracked->status, $tracked->status_before_cancellation)
                    );

                    $steps = [
                        ['label' => 'Review', 'hint' => 'Model diperiksa tim kami', 'at' => \App\Support\QuotationStatus::position(\App\Support\QuotationStatus::REVIEWING)],
                        ['label' => 'Pembayaran', 'hint' => 'Menunggu pembayaran Anda', 'at' => \App\Support\QuotationStatus::position(\App\Support\QuotationStatus::AWAITING_PAYMENT)],
                        ['label' => 'Produksi', 'hint' => 'Model sedang dicetak', 'at' => \App\Support\QuotationStatus::position(\App\Support\QuotationStatus::PRODUCTION)],
                        ['label' => 'Selesai', 'hint' => 'Pesanan diserahkan', 'at' => \App\Support\QuotationStatus::position(\App\Support\QuotationStatus::COMPLETED)],
                    ];
                @endphp

                <div class="mt-4 flex items-center justify-between gap-3 rounded-2xl bg-ink-50 px-4 py-3">
                    <span class="truncate font-mono text-sm font-semibold text-brand-600">{{ $tracked->tracking_number }}</span>
                    <span class="inline-flex shrink-0 whitespace-nowrap rounded-full border px-3 py-1 text-[0.7rem] font-bold {{ $badge($tracked->status) }}">
                        {{ $tracked->status_label }}
                    </span>
                </div>

                <ol class="mt-5 flex-1">
                    @foreach ($steps as $step)
                        @php
                            $state = match (true) {
                                $position < 0 => 'upcoming',
                                $position > $step['at'] => 'done',
                                $position === $step['at'] => 'current',
                                default => 'upcoming',
                            };
                        @endphp

                        <li class="relative flex gap-4 pb-5 last:pb-0">
                            {{-- Garis penghubung antartahap. --}}
                            @unless ($loop->last)
                                <span class="absolute left-4 top-9 -ml-px w-0.5 {{ $state === 'done' ? 'bg-emerald-400' : 'bg-ink-100' }}"
                                      style="height: calc(100% - 2.5rem)"></span>
                            @endunless

                            <span @class([
                                'relative inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                'bg-emerald-500 text-white' => $state === 'done',
                                'bg-brand-600 text-white ring-4 ring-brand-600/15' => $state === 'current',
                                'bg-ink-100 text-ink-400' => $state === 'upcoming',
                            ])>
                                {{ $state === 'done' ? '✓' : $loop->iteration }}
                            </span>

                            <div class="min-w-0 pt-1">
                                <p class="text-sm font-bold {{ $state === 'upcoming' ? 'text-ink-400' : 'text-ink-900' }}">{{ $step['label'] }}</p>
                                <p class="mt-0.5 text-xs text-ink-400">{{ $step['hint'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <a href="{{ route('dashboard.quotations.show', $tracked) }}" class="viewer-tool mt-5 justify-center">Lihat Detail</a>
            @else
                <div class="mt-6 flex flex-1 flex-col items-center justify-center rounded-2xl border border-dashed border-ink-200 px-4 py-10 text-center">
                    <x-icons.cube class="h-8 w-8 text-ink-300" />
                    <p class="mt-3 text-sm text-ink-500">Belum ada penawaran yang berjalan.</p>
                    <a href="{{ route('models') }}" class="btn-primary mt-4 px-5 py-2.5">Buat Penawaran</a>
                </div>
            @endif
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-12">

        {{-- ======================= PENAWARAN TERBARU ======================= --}}
        <section class="overflow-hidden rounded-3xl border border-ink-100/80 bg-white shadow-card lg:col-span-8">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-5">
                <h2 class="font-display text-base font-bold text-ink-900">Penawaran Terbaru</h2>
                <a href="{{ route('dashboard.quotations.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat semua</a>
            </div>

            {{-- Tabel digulir sendiri di layar sempit agar halaman tidak ikut
                 melebar ke samping. --}}
            <div class="overflow-x-auto">
                <table class="w-full min-w-[34rem] text-left text-sm">
                    <thead class="border-b border-ink-100 bg-ink-50/60">
                        <tr>
                            @foreach (['Nomor', 'Tanggal', 'Total', 'Status', ''] as $heading)
                                <th class="px-6 py-3 text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-400">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @forelse ($recent as $quotation)
                            <tr>
                                <td class="px-6 py-4 font-mono text-xs font-semibold text-brand-600">{{ $quotation->tracking_number }}</td>
                                <td class="px-6 py-4 text-ink-600">{{ $quotation->created_at->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-4 font-semibold text-ink-900">{{ harga_penawaran($quotation->display_price) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold {{ $badge($quotation->status) }}">
                                        {{ $quotation->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center">
                                    <p class="font-semibold text-ink-700">Belum ada penawaran.</p>
                                    <p class="mt-1.5 text-sm text-ink-400">Mulai dari halaman 3D Models untuk mengunggah model 3D Anda.</p>
                                    <a href="{{ route('models') }}" class="btn-primary mt-5">Buka 3D Models</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ========================== NOTIFIKASI ========================== --}}
        <section class="overflow-hidden rounded-3xl border border-ink-100/80 bg-white shadow-card lg:col-span-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-5">
                <h2 class="font-display text-base font-bold text-ink-900">Notifikasi</h2>
                <a href="{{ route('dashboard.notifications.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Semua</a>
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

    {{-- ========================= PESANAN TERBARU ========================= --}}
    @if ($orders->isNotEmpty())
        <section class="mt-6 overflow-hidden rounded-3xl border border-ink-100/80 bg-white shadow-card">
            <div class="border-b border-ink-100 px-6 py-5">
                <h2 class="font-display text-base font-bold text-ink-900">Pesanan Terbaru</h2>
                <p class="mt-1 text-sm text-ink-500">Pesanan yang sedang kami kerjakan.</p>
            </div>

            <ul class="divide-y divide-ink-100">
                @foreach ($orders as $order)
                    <li class="flex flex-wrap items-start justify-between gap-4 px-6 py-5">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-brand-600">{{ $order->tracking_number }}</p>
                            <p class="mt-1 truncate text-sm font-bold text-ink-900">{{ $order->file_summary }}</p>
                            <p class="mt-1 text-xs text-ink-500">
                                {{ $order->quantity }} pcs &middot; {{ $order->items_count }} file
                                @if ($order->estimated_finish)
                                    &middot; estimasi selesai {{ $order->estimated_finish->translatedFormat('d F Y') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold {{ $badge($order->status) }}">
                                {{ $order->status_label }}
                            </span>
                            <a href="{{ route('dashboard.quotations.show', $order) }}" class="viewer-tool">Lihat Detail</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
