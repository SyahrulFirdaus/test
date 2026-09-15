@extends('layouts.dashboard')

@section('title', 'Payment Terms')

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        $tabs = [
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Berjalan',
            'completed' => 'Lunas',
            'rejected' => 'Ditolak',
        ];
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Payment Terms</h2>
            <p class="mt-2 text-sm text-ink-500">
                Penawaran pelanggan Business yang memakai pembayaran bertahap beserta kemajuan pembayarannya.
            </p>
        </div>

        <a href="{{ staff_route('payment-terms.settings.edit') }}" class="viewer-tool">
            Pengaturan Payment Term
        </a>
    </div>

    <div class="mt-8 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="{{ staff_route('payment-terms.index', ['filter' => $key]) }}"
               class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-bold transition-colors
                      {{ $filter === $key
                          ? 'border-transparent bg-brand-600 text-white'
                          : 'border-ink-200 bg-white text-ink-600 hover:border-brand-300 hover:text-brand-700' }}">
                {{ $label }}
                <span class="inline-flex min-w-[1.5rem] justify-center rounded-full px-2 py-0.5 text-[0.65rem]
                             {{ $filter === $key ? 'bg-white/20 text-white' : 'bg-ink-100 text-ink-600' }}">
                    {{ $counts[$key] }}
                </span>
            </a>
        @endforeach
    </div>

    <div class="mt-6 space-y-4">
        @forelse ($terms as $term)
            @php
                $quotation = $term->quotation;
                $current = $term->currentInstallment();
            @endphp

            <article class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
                        <h3 class="mt-1 font-display text-lg font-bold text-ink-900">
                            {{ $quotation->user?->businessProfile?->company_name ?: ($quotation->company ?: $quotation->name) }}
                        </h3>
                        <p class="mt-0.5 text-xs text-ink-400">
                            {{ $quotation->name }} &middot; {{ $quotation->email }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Total Penawaran</p>
                        <p class="mt-1 font-display text-xl font-bold text-brand-700">{{ $rupiah($term->total_amount) }}</p>
                    </div>
                </div>

                <dl class="mt-5 grid gap-4 border-t border-ink-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                        $facts = [
                            'Payment Term' => $term->scheme_label,
                            'Sudah Dibayar' => $rupiah($term->paidAmount()),
                            'Sisa Pembayaran' => $rupiah($term->outstandingAmount()),
                            'Termin Aktif' => $current
                                ? $current->title.' — '.$current->status_label
                                : ($term->isCompleted() ? 'Lunas' : 'Belum terbentuk'),
                        ];
                    @endphp

                    @foreach ($facts as $label => $value)
                        <div>
                            <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-ink-800">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($term->hasSchedule())
                    <div class="mt-5">
                        <div class="h-2 overflow-hidden rounded-full bg-ink-100">
                            <div class="h-full rounded-full bg-brand-600" style="width: {{ $term->paidPercentage() }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-ink-400">
                            {{ number_format($term->paidPercentage(), 0) }}% terbayar
                            @if ($current?->due_date)
                                &middot; jatuh tempo berikutnya {{ $current->due_date->translatedFormat('d F Y') }}
                            @endif
                        </p>
                    </div>
                @endif

                @if ($term->rejection_reason)
                    <p class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                        <span class="font-bold">Alasan penolakan:</span> {{ $term->rejection_reason }}
                    </p>
                @endif

                <div class="mt-5 flex flex-wrap gap-2 border-t border-ink-100 pt-5">
                    <a href="{{ staff_route('payment-terms.show', $term) }}" class="btn-primary px-5 py-2.5">
                        Kelola Payment Term
                    </a>
                    <a href="{{ staff_route('quotations.show', $quotation) }}" class="viewer-tool">
                        Lihat Penawaran
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-ink-200 bg-white p-10 text-center">
                <p class="text-sm text-ink-500">Tidak ada payment term pada kategori ini.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $terms->links() }}</div>
@endsection
