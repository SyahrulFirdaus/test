@extends('layouts.dashboard')

@section('title', 'Verifikasi Pembayaran')

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        $tabs = [
            'review' => 'Menunggu Verifikasi',
            'installments' => 'Bukti Termin',
            'awaiting' => 'Menunggu Pembayaran',
            'decided' => 'Sudah Diputuskan',
        ];
    @endphp

    <div>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Verifikasi Pembayaran</h2>
                <p class="mt-2 text-sm text-ink-500">
                    Bukti pembayaran yang diunggah pelanggan beserta keputusan terima atau tolaknya.
                </p>
            </div>
        </div>

        {{-- Tab antrean. Yang menunggu verifikasi selalu jadi tab pertama karena
             itulah pekerjaan yang benar-benar menunggu admin. --}}
        <div class="mt-8 flex flex-wrap gap-2">
            @foreach ($tabs as $key => $label)
                <a href="{{ staff_route('payments.index', ['filter' => $key]) }}"
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

        {{-- ============ ANTREAN BUKTI PEMBAYARAN PER TERMIN (B2B) ============ --}}
        @if ($filter === 'installments')
            <div class="mt-6 space-y-4">
                @forelse ($installments as $installment)
                    @php
                        $term = $installment->term;
                        $quotation = $term->quotation;
                        $proof = $installment->latestProof;
                    @endphp

                    <article class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
                                <h3 class="mt-1 font-display text-lg font-bold text-ink-900">{{ $quotation->name }}</h3>
                                <p class="mt-0.5 text-xs text-ink-400">
                                    {{ $quotation->email }}@if ($quotation->company) &middot; {{ $quotation->company }} @endif
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                                    {{ $installment->title }} dari {{ $term->installment_count }}
                                </p>
                                <p class="mt-1 font-display text-xl font-bold text-brand-700">{{ $rupiah($installment->amount) }}</p>
                            </div>
                        </div>

                        <dl class="mt-5 grid gap-4 border-t border-ink-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                            @php
                                $facts = [
                                    'Payment Term' => $term->scheme_label,
                                    'Milestone' => $installment->milestone ?: '-',
                                    'Tanggal Upload' => $proof
                                        ? $proof->uploaded_at->translatedFormat('d F Y, H:i').' WIB'
                                        : 'Belum diunggah',
                                    'Jatuh Tempo' => $installment->due_date
                                        ? $installment->due_date->translatedFormat('d F Y')
                                        : '-',
                                ];
                            @endphp

                            @foreach ($facts as $label => $value)
                                <div>
                                    <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-ink-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-ink-100 pt-5">
                            @can(\App\Support\AdminPermission::PAYMENT_TERM_VIEW)
                            <a href="{{ staff_route('payment-terms.show', $term) }}" class="viewer-tool">Kelola Payment Term</a>
                            @endcan

                            @if ($proof)
                                <a href="{{ staff_route('payments.installments.proof', [$installment, $proof]) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="viewer-tool">
                                    <x-icons.download class="h-4 w-4" />
                                    Lihat Bukti Pembayaran
                                </a>
                            @endif
                        </div>

                        {{-- Menerima termin ini sekaligus mengaktifkan termin
                             berikutnya sesuai jadwal. --}}
                        @can(\App\Support\AdminPermission::PAYMENT_VERIFY)
                        <div class="mt-5 grid gap-4 rounded-2xl border border-ink-100 bg-ink-50/70 p-5 lg:grid-cols-2">
                            <form method="POST" action="{{ staff_route('payments.installments.approve', $installment) }}">
                                @csrf
                                <p class="text-sm font-bold text-ink-900">Terima Pembayaran</p>
                                <p class="mt-1 text-xs leading-relaxed text-ink-500">
                                    {{ $installment->title }} ditandai lunas dan termin berikutnya diaktifkan.
                                </p>
                                <button type="submit" class="btn-primary mt-3 w-full">Terima Pembayaran</button>
                            </form>

                            <form method="POST" action="{{ staff_route('payments.installments.reject', $installment) }}">
                                @csrf
                                <label for="reason-termin-{{ $installment->id }}" class="text-sm font-bold text-ink-900">Tolak Pembayaran</label>
                                <textarea id="reason-termin-{{ $installment->id }}"
                                          name="reason"
                                          rows="2"
                                          required
                                          maxlength="2000"
                                          placeholder="Alasan penolakan, mis. nominal transfer tidak sesuai nominal termin."
                                          class="mt-1 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                                <button type="submit" class="btn-outline mt-3 w-full">Tolak Pembayaran</button>
                            </form>
                        </div>
                        @endcan
                    </article>
                @empty
                    <p class="rounded-2xl border border-dashed border-ink-200 bg-white p-10 text-center text-sm text-ink-500">
                        Tidak ada bukti pembayaran termin yang menunggu verifikasi.
                    </p>
                @endforelse
            </div>

            <div class="mt-6">{{ $installments->links() }}</div>
        @else

        <div class="mt-6 space-y-4">
            @forelse ($quotations as $quotation)
                <article class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">

                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
                            <h3 class="mt-1 font-display text-lg font-bold text-ink-900">{{ $quotation->name }}</h3>
                            <p class="mt-0.5 text-xs text-ink-400">
                                {{ $quotation->email }}@if ($quotation->company) &middot; {{ $quotation->company }} @endif
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Nominal Pembayaran</p>
                            <p class="mt-1 font-display text-xl font-bold text-brand-700">{{ $rupiah($quotation->payment_amount) }}</p>
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-4 border-t border-ink-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $facts = [
                                'Status' => $quotation->status_label,
                                'Tanggal Upload' => $quotation->payment_proof_uploaded_at
                                    ? $quotation->payment_proof_uploaded_at->translatedFormat('d F Y, H:i').' WIB'
                                    : 'Belum diunggah',
                                'Batas Pembayaran' => $quotation->payment_due_at
                                    ? $quotation->payment_due_at->translatedFormat('d F Y, H:i').' WIB'
                                    : '-',
                                'Akun' => $quotation->user?->name ?? 'Tanpa akun',
                            ];
                        @endphp

                        @foreach ($facts as $label => $value)
                            <div>
                                <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-ink-800">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($quotation->payment_rejection_reason)
                        <p class="mt-4 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-800">
                            <span class="font-bold">Alasan penolakan:</span> {{ $quotation->payment_rejection_reason }}
                        </p>
                    @endif

                    <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-ink-100 pt-5">
                        @can(\App\Support\AdminPermission::QUOTATION_VIEW)
                        <a href="{{ staff_route('quotations.show', $quotation) }}" class="viewer-tool">Detail Penawaran</a>
                        @endcan

                        @if ($quotation->hasPaymentProof())
                            <a href="{{ staff_route('payments.proof', $quotation) }}"
                               target="_blank"
                               rel="noopener"
                               class="viewer-tool">
                                <x-icons.download class="h-4 w-4" />
                                Lihat Bukti Pembayaran
                            </a>
                        @else
                            <span class="text-xs font-semibold text-ink-400">Bukti pembayaran belum diunggah pelanggan.</span>
                        @endif
                    </div>

                    {{-- Dua keputusan admin. Penolakan menuntut alasan agar
                         pelanggan tahu apa yang harus diperbaiki. --}}
                    @if ($quotation->status === \App\Support\QuotationStatus::PAYMENT_REVIEW && auth()->user()->can(\App\Support\AdminPermission::PAYMENT_VERIFY))
                        <div class="mt-5 grid gap-4 rounded-2xl border border-ink-100 bg-ink-50/70 p-5 lg:grid-cols-2">
                            <form method="POST" action="{{ staff_route('payments.approve', $quotation) }}">
                                @csrf
                                <p class="text-sm font-bold text-ink-900">Terima Pembayaran</p>
                                <p class="mt-1 text-xs leading-relaxed text-ink-500">
                                    Status berubah menjadi "Pembayaran Diterima" dan pelanggan langsung diberi tahu.
                                </p>
                                <button type="submit" class="btn-primary mt-3 w-full">Terima Pembayaran</button>
                            </form>

                            <form method="POST" action="{{ staff_route('payments.reject', $quotation) }}">
                                @csrf
                                <label for="reason-{{ $quotation->id }}" class="text-sm font-bold text-ink-900">Tolak Pembayaran</label>
                                <textarea id="reason-{{ $quotation->id }}"
                                          name="reason"
                                          rows="2"
                                          required
                                          maxlength="2000"
                                          placeholder="Alasan penolakan, mis. nominal transfer tidak sesuai tagihan."
                                          class="mt-1 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none"></textarea>
                                <button type="submit" class="btn-outline mt-3 w-full">Tolak Pembayaran</button>
                            </form>
                        </div>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-ink-200 bg-white p-10 text-center text-sm text-ink-500">
                    Tidak ada penawaran pada antrean ini.
                </p>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $quotations->links() }}
        </div>
        @endif
    </div>
@endsection
