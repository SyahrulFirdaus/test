@extends('layouts.dashboard')

@section('title', 'Verifikasi Pembayaran')

{{--
    Pusat verifikasi pembayaran BERTAHAP.

    Pembayaran sekali bayar tidak lagi mengantre di sini: keputusannya diambil
    langsung dari Penawaran › Detail Penawaran, tempat admin memang sudah
    berada saat memeriksa penawarannya. Yang tinggal adalah bukti per termin
    milik pelanggan Business dengan Payment Term — satu penawaran dengan
    beberapa pembayaran, masing-masing dengan jadwal dan keputusannya sendiri.
--}}

@section('content')
    @php
        use App\Support\InstallmentStatus;

        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        $tabs = [
            'review' => 'Menunggu Verifikasi',
            'scheduled' => 'Jadwal Berjalan',
            'decided' => 'Sudah Lunas',
        ];
    @endphp

    <div>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Verifikasi Pembayaran</h2>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-500">
                    Bukti pembayaran per termin dari pelanggan Business yang memakai Payment Term.
                    Pembayaran sekali bayar diverifikasi langsung dari
                    <strong class="font-semibold text-ink-700">Penawaran &rsaquo; Detail Penawaran</strong>.
                </p>
            </div>

            @can(\App\Support\AdminPermission::PAYMENT_TERM_VIEW)
                <a href="{{ staff_route('payment-terms.index') }}" class="viewer-tool">
                    Kelola Payment Term
                    <span class="ml-1 rounded-full bg-ink-100 px-2 py-0.5 text-[0.65rem] font-bold text-ink-600">{{ $activeTerms }}</span>
                </a>
            @endcan
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

        <div class="mt-6 space-y-4">
            @forelse ($installments as $installment)
                @php
                    $term = $installment->term;
                    $quotation = $term->quotation;
                    $proof = $installment->latestProof;
                    $menunggu = $installment->isAwaitingVerification();
                @endphp

                <article class="rounded-2xl border p-6 shadow-card
                                {{ $menunggu ? 'border-2 border-amber-300 bg-amber-50' : 'border-ink-100 bg-white' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <a href="{{ staff_route('quotations.show', $quotation) }}"
                               class="font-mono text-sm font-semibold text-brand-600 hover:text-brand-700">
                                {{ $quotation->tracking_number }}
                            </a>
                            <h3 class="mt-1 font-display text-lg font-bold text-ink-900">{{ $quotation->name }}</h3>
                            <p class="mt-0.5 text-xs text-ink-400">
                                {{ $quotation->email }}@if ($quotation->company) &middot; {{ $quotation->company }} @endif
                            </p>
                            <p class="mt-1.5">
                                <span class="inline-flex rounded-full bg-ink-100 px-2.5 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.1em] text-ink-600">
                                    {{ $quotation->user?->customer_type_label ?? 'Business' }}
                                </span>
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                                {{ $installment->title }} dari {{ $term->installment_count }}
                            </p>
                            <p class="mt-1 font-display text-xl font-bold text-brand-700">{{ $rupiah($installment->amount) }}</p>
                            <p class="mt-1.5 text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-500">
                                {{ InstallmentStatus::label($installment->status) }}
                            </p>
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-4 border-t border-ink-100 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                        @php
                            $facts = [
                                'Total Penawaran' => $quotation->display_price === null
                                    ? 'Menunggu Perhitungan'
                                    : $rupiah($quotation->payment_amount),
                                'Payment Term' => $term->scheme_label,
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

                    {{-- Keputusan hanya pada termin yang memang menunggu, dan
                         hanya menyentuh termin ini — termin lain tidak ikut
                         berubah. Menerima sekaligus mengaktifkan termin
                         berikutnya sesuai jadwalnya. --}}
                    @if ($menunggu)
                        @can(\App\Support\AdminPermission::PAYMENT_VERIFY)
                            <div class="mt-5 grid gap-4 rounded-2xl border border-ink-100 bg-white p-5 lg:grid-cols-2">
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
                        @else
                            <p class="mt-5 border-t border-ink-100 pt-5 text-xs text-ink-400">
                                Bukti pembayaran menunggu verifikasi. Akun Anda tidak memiliki hak Verifikasi Pembayaran.
                            </p>
                        @endcan
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-ink-200 bg-white p-10 text-center">
                    <p class="text-sm text-ink-500">
                        @if ($filter === 'review')
                            Tidak ada bukti pembayaran termin yang menunggu verifikasi.
                        @elseif ($filter === 'scheduled')
                            Tidak ada termin yang sedang berjalan.
                        @else
                            Belum ada termin yang lunas.
                        @endif
                    </p>
                    <p class="mt-2 text-xs text-ink-400">
                        Pembayaran sekali bayar diverifikasi dari Penawaran &rsaquo; Detail Penawaran, bukan dari halaman ini.
                    </p>
                </div>
            @endforelse
        </div>

        @if ($installments->hasPages())
            <div class="mt-6">{{ $installments->links() }}</div>
        @endif
    </div>
@endsection
