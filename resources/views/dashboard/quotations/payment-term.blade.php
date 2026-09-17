@extends('layouts.dashboard')

@section('title', 'Skema Pembayaran '.$quotation->tracking_number)

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
        $wasRejected = $term?->isRejected();
    @endphp

    <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke detail penawaran
    </a>

    <div class="mt-5">
        <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Pilih Skema Pembayaran</h2>
        <p class="mt-2 text-sm text-ink-500">
            Sebagai pelanggan Business, Anda dapat menyelesaikan pembayaran sekaligus atau membaginya menjadi
            beberapa termin. Pilihan yang tersedia mengikuti nilai penawaran Anda.
        </p>
    </div>

    {{-- Pengajuan sebelumnya ditolak: alasannya ditampilkan lebih dulu agar
         pilihan berikutnya tidak mengulang penolakan yang sama. --}}
    @if ($wasRejected)
        <div class="mt-6 rounded-2xl border-2 border-rose-300 bg-rose-50 p-5">
            <p class="font-display text-sm font-bold text-rose-900">
                Pengajuan {{ $term->scheme_label }} Anda ditolak admin.
            </p>
            <p class="mt-1.5 text-sm text-rose-800">
                Alasan: <span class="font-semibold">{{ $term->rejection_reason ?: 'Tidak disebutkan.' }}</span>
            </p>
            <p class="mt-1.5 text-sm text-rose-800">Silakan pilih skema pembayaran lain di bawah ini.</p>
        </div>
    @endif

    <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Total Penawaran</p>
                <p class="mt-1 font-display text-3xl font-bold text-brand-700">{{ $rupiah($total) }}</p>
            </div>
            <p class="text-xs text-ink-400">{{ $quotation->items->count() }} model &middot; {{ $quotation->quantity }} unit</p>
        </div>
    </section>

    @if ($options->isEmpty())
        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm text-amber-800">
                Belum ada skema pembayaran yang tersedia untuk nilai penawaran ini. Silakan hubungi admin.
            </p>
        </div>
    @else
        <form method="POST" action="{{ route('dashboard.quotations.payment-term.store', $quotation) }}" class="mt-6">
            @csrf

            <div class="space-y-4">
                @foreach ($options as $option)
                    @php
                        $count = $option->installment_count;
                        $preview = $previews[$count] ?? [];
                        $checked = old('installment_count', $term?->installment_count) == $count;
                    @endphp

                    <label class="block cursor-pointer rounded-2xl border-2 bg-white p-5 shadow-card transition-colors
                                  {{ $checked ? 'border-brand-500' : 'border-ink-100 hover:border-brand-300' }}">
                        <div class="flex flex-wrap items-start gap-4">
                            <input type="radio"
                                   name="installment_count"
                                   value="{{ $count }}"
                                   @checked($checked)
                                   class="mt-1 h-5 w-5 shrink-0 accent-brand-600">

                            <div class="min-w-0 flex-1">
                                <p class="font-display text-base font-bold text-ink-900">{{ $option->label }}</p>

                                @if ($count === 1)
                                    <p class="mt-1 text-sm text-ink-500">
                                        Bayar penuh {{ $rupiah($total) }} sekaligus.
                                    </p>
                                @else
                                    <p class="mt-1 text-sm text-ink-500">
                                        Dibagi menjadi {{ $count }} termin. Pembagian di bawah adalah usulan awal;
                                        admin dapat menyesuaikannya saat menyetujui.
                                    </p>

                                    {{-- Kelas kolom ditulis utuh, bukan disusun
                                         dari variabel, agar tetap ikut terbawa
                                         saat Tailwind memindai berkas ini. --}}
                                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($preview as $index => $amount)
                                            <div class="rounded-xl border border-ink-100 bg-ink-50/70 p-3">
                                                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                                                    Termin {{ $index + 1 }}
                                                </p>
                                                <p class="mt-0.5 text-sm font-bold text-ink-800">{{ $rupiah($amount) }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>

            @error('installment_count') <p class="field-error mt-3">{{ $message }}</p> @enderror

            <div class="mt-6 rounded-2xl border border-ink-100 bg-ink-50/70 p-5">
                <p class="text-sm leading-relaxed text-ink-600">
                    Skema pembayaran bertahap <span class="font-semibold text-ink-800">tidak langsung berlaku</span>.
                    Pengajuan Anda akan ditinjau admin lebih dulu, dan jadwal terminnya baru terbentuk setelah disetujui.
                </p>
            </div>

            <button type="submit" class="btn-primary mt-6 w-full sm:w-auto sm:px-8">
                Ajukan Skema Pembayaran
            </button>
        </form>
    @endif
@endsection
