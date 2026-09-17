@extends('layouts.dashboard')

@section('title', 'Pembayaran '.$quotation->tracking_number)

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        // Warna penanda tiap keadaan termin ditulis utuh di sini — bukan
        // disusun dari nama warna — supaya kelasnya ikut terbawa Tailwind.
        $tones = [
            'emerald' => ['dot' => 'bg-emerald-500 text-white', 'chip' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'brand' => ['dot' => 'bg-brand-600 text-white', 'chip' => 'bg-brand-50 text-brand-700 border-brand-200'],
            'amber' => ['dot' => 'bg-amber-500 text-white', 'chip' => 'bg-amber-50 text-amber-700 border-amber-200'],
            'rose' => ['dot' => 'bg-rose-500 text-white', 'chip' => 'bg-rose-50 text-rose-700 border-rose-200'],
            'ink' => ['dot' => 'bg-ink-200 text-ink-500', 'chip' => 'bg-ink-50 text-ink-500 border-ink-200'],
        ];

        $tone = fn (string $status) => $tones[\App\Support\InstallmentStatus::tone($status)] ?? $tones['ink'];
    @endphp

    <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke detail penawaran
    </a>

    <div class="mt-5">
        <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Jadwal Pembayaran</h2>
        <p class="mt-2 text-sm text-ink-500">{{ $term->status_label }}</p>
    </div>

    {{-- ================= PENGAJUAN MASIH MENUNGGU KEPUTUSAN ================= --}}
    @if ($term->isPending())
        <div class="mt-6 rounded-2xl border-2 border-amber-300 bg-amber-50 p-5">
            <p class="font-display text-sm font-bold text-amber-900">Menunggu Persetujuan Payment Term</p>
            <p class="mt-1.5 text-sm text-amber-800">
                Pengajuan skema <span class="font-semibold">{{ $term->scheme_label }}</span> Anda sedang ditinjau admin.
                Jadwal terminnya akan muncul di halaman ini begitu disetujui.
            </p>
            <p class="mt-1.5 text-sm text-amber-800">
                Diajukan {{ optional($term->requested_at)->translatedFormat('d F Y, H:i') }} WIB.
            </p>
        </div>
    @endif

    {{-- ============================== RINGKASAN ============================== --}}
    <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $summary = [
                    'Total Penawaran' => $rupiah($term->total_amount),
                    'Skema Pembayaran' => $term->scheme_label,
                    'Sudah Dibayar' => $rupiah($term->paidAmount()),
                    'Sisa Pembayaran' => $rupiah($term->outstandingAmount()),
                ];
            @endphp

            @foreach ($summary as $label => $value)
                <div>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</p>
                    <p class="mt-1 font-display text-lg font-bold {{ $loop->first ? 'text-brand-700' : 'text-ink-900' }}">
                        {{ $value }}
                    </p>
                </div>
            @endforeach
        </div>

        @if ($term->hasSchedule())
            <div class="mt-5">
                <div class="h-2 overflow-hidden rounded-full bg-ink-100">
                    <div class="h-full rounded-full bg-brand-600" style="width: {{ $term->paidPercentage() }}%"></div>
                </div>
                <p class="mt-2 text-xs text-ink-400">{{ number_format($term->paidPercentage(), 0) }}% dari total penawaran sudah dibayar.</p>
            </div>
        @endif
    </section>

    {{-- ============================== TIMELINE ============================== --}}
    @if ($term->hasSchedule())
        <div class="mt-6 space-y-4">
            @foreach ($term->installments as $installment)
                @php $palette = $tone($installment->status); @endphp

                <article class="rounded-2xl border bg-white p-6 shadow-card
                                {{ $installment->acceptsProof() && ! $installment->isPaid() ? 'border-brand-300' : 'border-ink-100' }}">
                    <div class="flex flex-wrap items-start gap-4">

                        {{-- Penanda tahap: ✓ lunas, ● berjalan, ○ belum aktif --}}
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $palette['dot'] }}">
                            {{ $installment->marker }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-display text-base font-bold text-ink-900">{{ $installment->title }}</p>
                                    @if ($installment->milestone)
                                        <p class="mt-0.5 text-xs text-ink-400">{{ $installment->milestone }}</p>
                                    @endif
                                </div>

                                <div class="text-right">
                                    <p class="font-display text-xl font-bold text-ink-900">{{ $rupiah($installment->amount) }}</p>
                                    <p class="text-xs text-ink-400">{{ number_format((float) $installment->percentage, 2, ',', '.') }}%</p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $palette['chip'] }}">
                                    {{ $installment->status_label }}
                                </span>

                                @if ($installment->due_date)
                                    <span class="text-xs text-ink-500">
                                        Jatuh tempo {{ $installment->due_date->translatedFormat('d F Y') }}
                                    </span>
                                @endif

                                @if ($installment->paid_at)
                                    <span class="text-xs text-emerald-600">
                                        Dibayar {{ $installment->paid_at->translatedFormat('d F Y') }}
                                    </span>
                                @endif
                            </div>

                            {{-- Alasan penolakan bukti terakhir, agar unggahan
                                 berikutnya tidak mengulang kesalahan yang sama. --}}
                            @if ($installment->latestProof?->rejection_reason)
                                <p class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                                    <span class="font-bold">Bukti ditolak:</span> {{ $installment->latestProof->rejection_reason }}
                                </p>
                            @endif

                            @if ($installment->admin_note)
                                <p class="mt-3 rounded-xl bg-ink-50 p-3 text-sm text-ink-600">
                                    <span class="font-bold">Catatan admin:</span> {{ $installment->admin_note }}
                                </p>
                            @endif

                            <div class="mt-4">
                                @if ($installment->acceptsProof())
                                    <a href="{{ route('dashboard.quotations.installments.show', [$quotation, $installment]) }}"
                                       class="btn-primary px-5 py-2.5">
                                        Bayar Sekarang
                                    </a>
                                @elseif ($installment->isAwaitingVerification())
                                    <a href="{{ route('dashboard.quotations.installments.show', [$quotation, $installment]) }}"
                                       class="viewer-tool">
                                        Lihat Bukti Terkirim
                                    </a>
                                @elseif ($installment->isPaid())
                                    <a href="{{ route('dashboard.quotations.installments.show', [$quotation, $installment]) }}"
                                       class="viewer-tool">
                                        Lihat Rincian
                                    </a>
                                @else
                                    <p class="text-xs text-ink-400">
                                        Termin ini terbuka setelah termin sebelumnya lunas atau diaktifkan admin.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ============ SKEMA 1x: PEMBAYARAN MENGIKUTI ALUR SEKALI BAYAR ============ --}}
    @if ($term->isApproved() && ! $term->isInstalment())
        <div class="mt-6 rounded-2xl border-2 border-brand-300 bg-brand-50 p-5">
            <p class="font-display text-sm font-bold text-brand-900">Pembayaran sekaligus (lunas)</p>
            <p class="mt-1.5 text-sm text-brand-800">
                Skema 1x Anda disetujui. Total tagihan {{ $rupiah($term->total_amount) }} dibayarkan sekaligus ke
                rekening {{ $bank['name'] }} a.n. {{ $bank['account_holder'] }}, nomor {{ $bank['account_number'] }}.
            </p>
        </div>
    @endif
@endsection
