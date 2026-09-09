@extends('layouts.dashboard')

@section('title', 'Business Dashboard')

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        // Angka besar diringkas agar kartu statistik tidak pecah di layar
        // sempit: 30.000.000 dibaca "Rp30 jt".
        $ringkas = function ($value) {
            $value = (float) $value;

            return match (true) {
                $value >= 1_000_000_000 => 'Rp'.rtrim(rtrim(number_format($value / 1_000_000_000, 1, ',', '.'), '0'), ',').' M',
                $value >= 1_000_000 => 'Rp'.rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',').' jt',
                default => 'Rp'.number_format($value, 0, ',', '.'),
            };
        };

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
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Selamat datang</p>
                <h2 class="mt-2 font-display text-2xl font-bold sm:text-3xl">{{ auth()->user()->name }}</h2>

                @if ($profile?->company_name)
                    <p class="mt-1.5 font-display text-lg font-semibold text-white/90">{{ $profile->company_name }}</p>
                @endif

                <span class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3.5 py-1.5 text-xs font-bold uppercase tracking-[0.14em] text-white">
                    Business Account
                </span>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('models') }}" class="btn-primary bg-white text-brand-700 shadow-none hover:bg-white/90 hover:text-brand-800">
                    + Buat Quotation
                </a>
                <a href="{{ route('dashboard.quotations.index') }}" class="btn-ghost-light">Quotations</a>
            </div>
        </div>
    </div>

    {{-- ============================ STATISTIK ============================ --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['label' => 'Active Quotations', 'value' => number_format($stats['active_quotations'], 0, ',', '.'), 'tone' => 'ink'],
            ['label' => 'Active Orders', 'value' => number_format($stats['active_orders'], 0, ',', '.'), 'tone' => 'ink'],
            ['label' => 'Completed Orders', 'value' => number_format($stats['completed_orders'], 0, ',', '.'), 'tone' => 'ink'],
            ['label' => 'Outstanding', 'value' => $ringkas($stats['outstanding']), 'tone' => 'brand'],
            ['label' => 'Total Spending', 'value' => $ringkas($stats['spending']), 'tone' => 'emerald'],
        ] as $card)
            <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                <p class="mt-2 font-display text-2xl font-bold
                          {{ $card['tone'] === 'brand' ? 'text-brand-700' : ($card['tone'] === 'emerald' ? 'text-emerald-600' : 'text-ink-900') }}">
                    {{ $card['value'] }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- ====================== PAYMENT REMINDER B2B ====================== --}}
    @foreach ($reminders as $reminder)
        @php
            $daysLeft = $reminder->daysUntilDue();
            $overdue = $daysLeft !== null && $daysLeft < 0;
        @endphp

        <div class="mt-6 rounded-2xl border-2 p-6 shadow-card
                    {{ $overdue ? 'border-rose-400 bg-rose-50' : 'border-amber-300 bg-amber-50' }}">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div class="min-w-0">
                    <p class="font-display text-base font-bold {{ $overdue ? 'text-rose-900' : 'text-amber-900' }}">
                        ⚠️ {{ $overdue ? 'Payment Overdue' : 'Payment Reminder' }}
                    </p>
                    <p class="mt-1.5 text-sm {{ $overdue ? 'text-rose-800' : 'text-amber-800' }}">
                        @if ($overdue)
                            Pembayaran {{ $reminder->title }} untuk quotation
                            <span class="font-mono font-semibold">{{ $reminder->term->quotation->tracking_number }}</span>
                            sebesar {{ $rupiah($reminder->amount) }} telah melewati batas waktu
                            {{ $reminder->due_date->translatedFormat('d F Y') }}.
                        @else
                            {{ $reminder->title }} untuk quotation
                            <span class="font-mono font-semibold">{{ $reminder->term->quotation->tracking_number }}</span>
                            sebesar {{ $rupiah($reminder->amount) }}
                            @if ($daysLeft === 0)
                                jatuh tempo hari ini.
                            @elseif ($daysLeft === 1)
                                akan jatuh tempo besok.
                            @else
                                akan jatuh tempo dalam {{ $daysLeft }} hari.
                            @endif
                        @endif
                    </p>
                </div>

                <a href="{{ route('dashboard.quotations.installments.show', [$reminder->term->quotation, $reminder]) }}"
                   class="btn-primary shrink-0 px-6 py-3">
                    Lihat Pembayaran
                </a>
            </div>
        </div>
    @endforeach

    {{-- Pembayaran sekali bayar yang masih menunggu. --}}
    @if ($paymentDue)
        <div class="mt-6 rounded-2xl border-2 border-brand-300 bg-brand-50 p-6 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div class="min-w-0">
                    <p class="font-display text-base font-bold text-brand-900">Pembayaran Menunggu</p>
                    <p class="mt-1.5 text-sm text-brand-800">
                        Quotation <span class="font-mono font-semibold">{{ $paymentDue->tracking_number }}</span>
                        sebesar {{ $rupiah($paymentDue->payment_amount) }} menunggu penyelesaian.
                        @if ($paymentDue->payment_due_at)
                            Batas {{ $paymentDue->payment_due_at->translatedFormat('d F Y, H:i') }} WIB.
                        @endif
                    </p>
                </div>

                <a href="{{ route('dashboard.quotations.payment', $paymentDue) }}" class="btn-primary shrink-0 px-6 py-3">
                    Bayar Sekarang
                </a>
            </div>
        </div>
    @endif

    {{-- ========================= PAYMENT TERM ========================= --}}
    <section id="payment-terms" class="mt-6 scroll-mt-24">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-lg font-bold text-ink-900">Payment Overview</h2>
                <p class="mt-1 text-sm text-ink-500">Skema pembayaran bertahap yang sedang berjalan.</p>
            </div>
        </div>

        @forelse ($terms as $term)
            <article class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                <div class="border-b border-ink-100 px-6 py-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="font-mono text-sm font-semibold text-brand-600">{{ $term->quotation->tracking_number }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ $term->status_label }}</p>
                        </div>

                        <span class="inline-flex rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">
                            {{ $term->scheme_label }}
                        </span>
                    </div>

                    <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                        @foreach ([
                            'Total Quotation' => $rupiah($term->total_amount),
                            'Paid' => $rupiah($term->paidAmount()),
                            'Outstanding' => $rupiah($term->outstandingAmount()),
                        ] as $label => $value)
                            <div>
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                                <dd class="mt-1 font-display text-lg font-bold {{ $label === 'Outstanding' ? 'text-brand-700' : 'text-ink-900' }}">
                                    {{ $value }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($term->hasSchedule())
                        <div class="mt-5">
                            <div class="h-2 overflow-hidden rounded-full bg-ink-100">
                                <div class="h-full rounded-full bg-brand-600" style="width: {{ $term->paidPercentage() }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-ink-400">{{ number_format($term->paidPercentage(), 0) }}% terbayar</p>
                        </div>
                    @endif
                </div>

                @if ($term->isPending())
                    <p class="px-6 py-5 text-sm text-amber-700">
                        Pengajuan skema ini sedang ditinjau admin. Jadwal terminnya muncul setelah disetujui.
                    </p>
                @else
                    <ul class="divide-y divide-ink-100">
                        @foreach ($term->installments as $installment)
                            @php
                                $tone = \App\Support\InstallmentStatus::tone($installment->status);
                                $dot = match ($tone) {
                                    'emerald' => 'bg-emerald-500 text-white',
                                    'brand' => 'bg-brand-600 text-white',
                                    'amber' => 'bg-amber-500 text-white',
                                    'rose' => 'bg-rose-500 text-white',
                                    default => 'bg-ink-200 text-ink-500',
                                };
                            @endphp

                            <li class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $dot }}">
                                        {{ $installment->marker }}
                                    </span>

                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-ink-900">
                                            {{ $installment->title }} &middot; {{ $rupiah($installment->amount) }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-ink-500">
                                            {{ $installment->status_label }}
                                            @if ($installment->milestone) &middot; {{ $installment->milestone }} @endif
                                            @if ($installment->due_date) &middot; due {{ $installment->due_date->translatedFormat('d M Y') }} @endif
                                        </p>
                                    </div>
                                </div>

                                @if ($installment->acceptsProof())
                                    <a href="{{ route('dashboard.quotations.installments.show', [$term->quotation, $installment]) }}"
                                       class="btn-primary shrink-0 px-5 py-2.5">
                                        Bayar Sekarang
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        @empty
            <div class="mt-4 rounded-2xl border border-dashed border-ink-200 bg-white p-10 text-center">
                <p class="text-sm text-ink-500">Belum ada skema pembayaran bertahap yang berjalan.</p>
            </div>
        @endforelse
    </section>

    {{-- ======================= PRODUCTION TRACKING ======================= --}}
    <section id="production" class="mt-8 scroll-mt-24">
        <h2 class="font-display text-lg font-bold text-ink-900">Active Production</h2>
        <p class="mt-1 text-sm text-ink-500">Pekerjaan yang sedang berjalan di lantai produksi.</p>

        @forelse ($production as $order)
            @php
                $position = \App\Support\QuotationStatus::position($order->status);

                $stages = [
                    ['label' => 'File Reviewed', 'key' => \App\Support\QuotationStatus::REVIEWING],
                    ['label' => 'Payment Confirmed', 'key' => \App\Support\QuotationStatus::PAYMENT_RECEIVED],
                    ['label' => 'Production', 'key' => \App\Support\QuotationStatus::PRODUCTION],
                    ['label' => 'Quality Control', 'key' => \App\Support\QuotationStatus::QUALITY_CONTROL],
                    ['label' => 'Ready to Ship', 'key' => \App\Support\QuotationStatus::READY_TO_SHIP],
                    ['label' => 'Completed', 'key' => \App\Support\QuotationStatus::COMPLETED],
                ];
            @endphp

            <article class="mt-4 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-brand-600">{{ $order->tracking_number }}</p>
                        @if ($profile?->company_name)
                            <p class="mt-1 text-sm font-semibold text-ink-700">{{ $profile->company_name }}</p>
                        @endif
                    </div>

                    <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold {{ $badge($order->status) }}">
                        {{ $order->status_label }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 border-t border-ink-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        '3D Object' => $order->file_summary,
                        'Quantity' => $order->quantity.' pcs',
                        'Teknologi' => $order->technology ?: '-',
                        'Estimasi Lead Time' => $order->estimated_finish
                            ? $order->estimated_finish->translatedFormat('d F Y')
                            : '3–5 Hari Kerja',
                    ] as $label => $value)
                        <div class="min-w-0">
                            <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                            <dd class="mt-1 truncate text-sm font-semibold text-ink-800">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <ul class="mt-5 space-y-2 border-t border-ink-100 pt-5">
                    @foreach ($stages as $stage)
                        @php
                            $at = \App\Support\QuotationStatus::position($stage['key']);
                            $state = match (true) {
                                $position > $at => 'done',
                                $position === $at => 'current',
                                default => 'upcoming',
                            };
                        @endphp

                        <li class="flex items-center gap-3 text-sm">
                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[0.7rem] font-bold
                                         {{ $state === 'done' ? 'bg-emerald-500 text-white'
                                            : ($state === 'current' ? 'bg-brand-600 text-white' : 'bg-ink-100 text-ink-400') }}">
                                {{ $state === 'done' ? '✓' : ($state === 'current' ? '●' : '○') }}
                            </span>
                            <span class="{{ $state === 'upcoming' ? 'text-ink-400' : 'font-semibold text-ink-800' }}">
                                {{ $stage['label'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('dashboard.quotations.show', $order) }}" class="viewer-tool mt-5">Detail Order</a>
            </article>
        @empty
            <div class="mt-4 rounded-2xl border border-dashed border-ink-200 bg-white p-10 text-center">
                <p class="text-sm text-ink-500">Tidak ada pekerjaan yang sedang diproduksi.</p>
            </div>
        @endforelse
    </section>

    {{-- ====================== RECENT QUOTATIONS ====================== --}}
    <section class="mt-8 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-5">
            <h2 class="font-display text-base font-bold text-ink-900">Recent Quotations</h2>
            <a href="{{ route('dashboard.quotations.index') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">
                Lihat Semua Quotation &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="border-b border-ink-100 bg-ink-50/60">
                    <tr>
                        @foreach (['Quotation', 'Tanggal', 'Total', 'Payment', 'Status', ''] as $heading)
                            <th class="px-6 py-3 text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-400">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-ink-100">
                    @forelse ($recent as $quotation)
                        <tr>
                            <td class="px-6 py-4 font-mono text-xs font-semibold text-brand-600">{{ $quotation->tracking_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-ink-600">{{ $quotation->created_at->translatedFormat('d M Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-ink-900">{{ $ringkas($quotation->display_price) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-ink-600">
                                @if ($quotation->paymentTerm?->isApproved())
                                    {{ $quotation->paymentTerm->isCompleted() ? 'Paid' : $quotation->paymentTerm->installment_count.'x' }}
                                @elseif ($quotation->paymentTerm?->isPending())
                                    Menunggu
                                @elseif ($quotation->payment_verified_at)
                                    Paid
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold {{ $badge($quotation->status) }}">
                                    {{ $quotation->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <p class="font-semibold text-ink-700">Belum ada quotation.</p>
                                <a href="{{ route('models') }}" class="btn-primary mt-5">Buat Quotation</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-12">

        {{-- ======================= COMPANY PROFILE ======================= --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card lg:col-span-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h2 class="font-display text-base font-bold text-ink-900">Company Profile</h2>
                <a href="{{ route('dashboard.company-profile.edit') }}" class="viewer-tool">Edit Company Profile</a>
            </div>

            @if ($profile)
                <p class="mt-4 font-display text-lg font-bold text-ink-900">{{ $profile->company_name }}</p>

                <dl class="mt-4 space-y-3">
                    @foreach ([
                        'Industry' => $profile->industry,
                        'PIC' => $profile->pic_name,
                        'Position' => $profile->position,
                        'Phone' => $profile->phone,
                        'Email' => $profile->email,
                        'Alamat' => $profile->full_address,
                    ] as $label => $value)
                        @if (filled($value))
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-ink-100 pb-3 last:border-0">
                                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $label }}</dt>
                                <dd class="max-w-[60%] text-right text-sm font-semibold text-ink-800">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            @else
                <p class="mt-4 text-sm text-ink-500">
                    Data perusahaan belum lengkap. Lengkapi profil agar admin dapat memproses quotation Anda lebih cepat.
                </p>
            @endif
        </section>

        {{-- ======================= BUSINESS PROFILE ======================= --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card lg:col-span-6">
            <h2 class="font-display text-base font-bold text-ink-900">Business Profile</h2>
            <p class="mt-1 text-sm text-ink-500">Kebutuhan yang Anda sampaikan saat mendaftar.</p>

            @if ($profile?->segment)
                <div class="mt-4 rounded-xl border border-brand-200 bg-brand-50 p-4">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-brand-800/60">Customer Segment</p>
                    <p class="mt-1 font-display text-base font-bold text-brand-800">{{ $profile->segment }}</p>
                </div>
            @endif

            @if ($answers->isNotEmpty())
                <dl class="mt-4 max-h-96 space-y-3 overflow-y-auto pr-1">
                    @foreach ($answers as $answer)
                        <div class="border-b border-ink-100 pb-3 last:border-0">
                            <dt class="text-xs font-semibold text-ink-400">{{ $answer['question'] }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink-800">{{ $answer['answer'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="mt-4 text-sm text-ink-500">Belum ada jawaban pendaftaran yang tersimpan.</p>
            @endif
        </section>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-12">

        {{-- ========================== DOCUMENTS ========================== --}}
        <section id="documents" class="scroll-mt-24 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card lg:col-span-6">
            <div class="border-b border-ink-100 px-6 py-5">
                <h2 class="font-display text-base font-bold text-ink-900">Documents</h2>
                <p class="mt-1 text-sm text-ink-500">Dokumen yang tersedia untuk diunduh.</p>
            </div>

            <ul class="divide-y divide-ink-100">
                @forelse ($documents as $document)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink-800">{{ $document['label'] }}</p>
                            <p class="mt-0.5 text-xs text-ink-400">{{ $document['meta'] }}</p>
                        </div>

                        <a href="{{ $document['url'] }}" target="_blank" rel="noopener" class="viewer-tool shrink-0">
                            <x-icons.download class="h-4 w-4" />
                            Download
                        </a>
                    </li>
                @empty
                    <li class="px-6 py-14 text-center text-sm text-ink-400">Belum ada dokumen.</li>
                @endforelse
            </ul>
        </section>

        {{-- =========================== RE-ORDER =========================== --}}
        <section id="reorder" class="scroll-mt-24 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card lg:col-span-6">
            <div class="border-b border-ink-100 px-6 py-5">
                <h2 class="font-display text-base font-bold text-ink-900">Previous Orders</h2>
                <p class="mt-1 text-sm text-ink-500">Pesan ulang memakai spesifikasi pesanan sebelumnya.</p>
            </div>

            <ul class="divide-y divide-ink-100">
                @forelse ($reorderable as $order)
                    <li class="flex flex-wrap items-start justify-between gap-4 px-6 py-5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-ink-900">{{ $order->file_summary }}</p>
                            <p class="mt-1 text-xs text-ink-500">
                                {{ $order->quantity }} pcs &middot; {{ $order->technology }}
                                @if ($order->material) &middot; {{ $order->material }} @endif
                            </p>
                            <p class="mt-1 text-xs text-ink-400">
                                Last order: {{ $order->created_at->translatedFormat('d F Y') }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('dashboard.quotations.reorder', $order) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="btn-primary px-5 py-2.5">Re-order</button>
                        </form>
                    </li>
                @empty
                    <li class="px-6 py-14 text-center text-sm text-ink-400">
                        Belum ada pesanan selesai yang dapat dipesan ulang.
                    </li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- ========================== NOTIFIKASI ========================== --}}
    <section class="mt-8 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 px-6 py-5">
            <h2 class="font-display text-base font-bold text-ink-900">Notifications</h2>
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
@endsection
