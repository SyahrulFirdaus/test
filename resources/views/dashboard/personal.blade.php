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

    {{-- ============================== HEADER ============================== --}}
    <div class="rounded-3xl border border-transparent bg-gradient-to-br from-brand-700 to-brand-950 p-7 text-white shadow-card sm:p-9">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Selamat datang</p>
        <h2 class="mt-2 font-display text-2xl font-bold sm:text-3xl">{{ auth()->user()->name }}</h2>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/75">
            Kelola penawaran, pesanan, dan pembayaran Anda.
        </p>

        {{-- Quick action --}}
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('models') }}" class="btn-primary bg-white text-brand-700 shadow-none hover:bg-white/90 hover:text-brand-800">
                + Buat Penawaran
            </a>
            <a href="{{ route('models') }}" class="btn-ghost-light">3D Models</a>
            <a href="{{ route('dashboard.quotations.index') }}" class="btn-ghost-light">Riwayat Pesanan</a>
            <a href="{{ route('dashboard.profile.edit') }}" class="btn-ghost-light">Profile</a>
        </div>
    </div>

    {{-- ============================ STATISTIK ============================ --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Penawaran', 'value' => $stats['quotations']],
            ['label' => 'Pesanan Aktif', 'value' => $stats['active_orders']],
            ['label' => 'Menunggu Bayar', 'value' => $stats['awaiting_payment']],
            ['label' => 'Pesanan Selesai', 'value' => $stats['completed']],
        ] as $card)
            <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                <p class="mt-2 font-display text-3xl font-bold text-ink-900">{{ number_format($card['value'], 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- ======================= PENGINGAT PEMBAYARAN ======================= --}}
    {{-- Kartu ini hanya muncul bila memang ada yang harus dibayar. --}}
    @if ($paymentDue)
        <div class="mt-6 overflow-hidden rounded-2xl border-2 border-brand-300 bg-brand-50 p-6 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div class="min-w-0">
                    <p class="font-display text-base font-bold text-brand-900">Pembayaran Menunggu</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-brand-700">{{ $paymentDue->tracking_number }}</p>

                    <dl class="mt-4 flex flex-wrap gap-x-10 gap-y-3">
                        <div>
                            <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-brand-800/60">Total</dt>
                            <dd class="mt-0.5 font-display text-xl font-bold text-brand-800">{{ $rupiah($paymentDue->payment_amount) }}</dd>
                        </div>

                        @if ($paymentDue->payment_due_at)
                            <div>
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-brand-800/60">Batas Pembayaran</dt>
                                <dd class="mt-0.5 text-sm font-bold text-brand-800">
                                    {{ $paymentDue->payment_due_at->translatedFormat('d F Y, H:i') }} WIB
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <a href="{{ route('dashboard.quotations.payment', $paymentDue) }}" class="btn-primary shrink-0 px-6 py-3">
                    Bayar Sekarang
                </a>
            </div>
        </div>
    @endif

    {{-- ========================= STATUS PESANAN ========================= --}}
    @if ($tracked)
        <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display text-base font-bold text-ink-900">Status Pesanan</h2>
                    <p class="mt-1 text-sm text-ink-500">
                        Posisi penawaran <span class="font-mono font-semibold text-brand-600">{{ $tracked->tracking_number }}</span> saat ini.
                    </p>
                </div>

                <a href="{{ route('dashboard.quotations.show', $tracked) }}" class="viewer-tool">Lihat Detail</a>
            </div>

            @php
                /*
                 * Lima tahap ringkas untuk pelanggan Personal. Sepuluh tahap
                 * penuh tetap ada di halaman tracking; di sini yang ditampilkan
                 * hanya yang bermakna bagi pemesan perorangan.
                 */
                $position = \App\Support\QuotationStatus::position(
                    \App\Support\QuotationStatus::timelineAnchor($tracked->status, $tracked->status_before_cancellation)
                );

                $steps = [
                    ['label' => 'Review', 'at' => \App\Support\QuotationStatus::position(\App\Support\QuotationStatus::REVIEWING)],
                    ['label' => 'Payment', 'at' => \App\Support\QuotationStatus::position(\App\Support\QuotationStatus::AWAITING_PAYMENT)],
                    ['label' => 'Production', 'at' => \App\Support\QuotationStatus::position(\App\Support\QuotationStatus::PRODUCTION)],
                    ['label' => 'Completed', 'at' => \App\Support\QuotationStatus::position(\App\Support\QuotationStatus::COMPLETED)],
                ];
            @endphp

            <ol class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($steps as $step)
                    @php
                        $state = match (true) {
                            $position < 0 => 'upcoming',
                            $position > $step['at'] => 'done',
                            $position === $step['at'] => 'current',
                            default => 'upcoming',
                        };
                    @endphp

                    <li class="flex flex-col items-center gap-2 text-center">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold
                                     {{ $state === 'done' ? 'bg-emerald-500 text-white'
                                        : ($state === 'current' ? 'bg-brand-600 text-white' : 'bg-ink-100 text-ink-400') }}">
                            {{ $state === 'done' ? '✓' : ($state === 'current' ? '●' : '○') }}
                        </span>
                        <span class="text-xs font-semibold {{ $state === 'upcoming' ? 'text-ink-400' : 'text-ink-800' }}">
                            {{ $step['label'] }}
                        </span>
                    </li>
                @endforeach
            </ol>

            <p class="mt-5 rounded-xl bg-ink-50 p-4 text-sm text-ink-600">
                Status sekarang: <span class="font-bold text-ink-900">{{ $tracked->status_label }}</span>
            </p>
        </section>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-12">

        {{-- ======================= PENAWARAN TERBARU ======================= --}}
        <section class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card lg:col-span-8">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-5">
                <h2 class="font-display text-base font-bold text-ink-900">Penawaran Terbaru</h2>
                <a href="{{ route('dashboard.quotations.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Lihat semua &rarr;</a>
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
                                <td class="px-6 py-4 font-semibold text-ink-900">{{ $rupiah($quotation->display_price) }}</td>
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

    {{-- ========================= PESANAN TERBARU ========================= --}}
    @if ($orders->isNotEmpty())
        <section class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
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
