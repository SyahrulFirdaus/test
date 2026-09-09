@extends('layouts.dashboard')

@section('title', $installment->title.' '.$quotation->tracking_number)

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        $accountPlain = preg_replace('/\s+/', '', $bank['account_number']);
        $canUpload = $installment->acceptsProof();
        $underReview = $installment->isAwaitingVerification();
        $daysLeft = $installment->daysUntilDue();
    @endphp

    <a href="{{ route('dashboard.quotations.payment', $quotation) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke jadwal pembayaran
    </a>

    <div class="mt-5">
        <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
            {{ $installment->title }} dari {{ $term->installment_count }}
        </h2>
        <p class="mt-2 text-sm text-ink-500">
            {{ $installment->status_label }}@if ($installment->milestone) &middot; {{ $installment->milestone }} @endif
        </p>
    </div>

    @if ($underReview)
        <div class="mt-6 rounded-2xl border-2 border-emerald-300 bg-emerald-50 p-5">
            <p class="font-display text-sm font-bold text-emerald-900">
                Bukti pembayaran berhasil diunggah. Mohon tunggu verifikasi dari Admin.
            </p>
            <p class="mt-1.5 text-sm text-emerald-800">
                Diunggah {{ optional($installment->latestProof?->uploaded_at)->translatedFormat('d F Y, H:i') }} WIB.
                Anda tidak dapat mengunggah bukti baru selama bukti ini masih diperiksa.
            </p>
        </div>
    @endif

    @if ($installment->latestProof?->rejection_reason && $canUpload)
        <div class="mt-6 rounded-2xl border-2 border-rose-300 bg-rose-50 p-5">
            <p class="font-display text-sm font-bold text-rose-900">Bukti pembayaran Anda ditolak admin.</p>
            <p class="mt-1.5 text-sm text-rose-800">
                Alasan: <span class="font-semibold">{{ $installment->latestProof->rejection_reason }}</span>
            </p>
            <p class="mt-1.5 text-sm text-rose-800">Silakan unggah ulang bukti pembayaran yang sesuai.</p>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-12">

        {{-- ============ KOLOM KIRI: TAGIHAN & REKENING ============ --}}
        <div class="space-y-6 lg:col-span-7">

            <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Rincian Tagihan Termin</h3>

                <dl class="mt-5 space-y-4">
                    @php
                        $facts = [
                            'Nomor Penawaran' => $quotation->tracking_number,
                            'Skema Pembayaran' => $term->scheme_label,
                            'Nomor Termin' => $installment->title.' dari '.$term->installment_count,
                            'Milestone' => $installment->milestone ?: '-',
                            'Porsi Pembayaran' => number_format((float) $installment->percentage, 2, ',', '.').'% dari '.$rupiah($term->total_amount),
                        ];
                    @endphp

                    @foreach ($facts as $label => $value)
                        <div class="flex items-start justify-between gap-4 border-b border-ink-100 pb-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $label }}</dt>
                            <dd class="text-right text-sm font-semibold text-ink-800">{{ $value }}</dd>
                        </div>
                    @endforeach

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Nominal Termin Ini</dt>
                        <dd class="text-right font-display text-2xl font-bold text-brand-700">{{ $rupiah($installment->amount) }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Rekening tujuan sama dengan pembayaran sekali bayar. --}}
            <section class="overflow-hidden rounded-2xl border-2 border-brand-200 bg-white shadow-card">
                <div class="border-b border-brand-100 bg-brand-50 px-6 py-4">
                    <h3 class="font-display text-base font-bold text-brand-900">Informasi Rekening Pembayaran</h3>
                </div>

                <div class="p-6">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Bank</p>
                    <p class="mt-1 font-display text-xl font-bold text-ink-900">{{ $bank['name'] }}</p>

                    <p class="mt-5 text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Atas Nama</p>
                    <p class="mt-1 text-sm font-bold text-ink-900">{{ $bank['account_holder'] }}</p>

                    <p class="mt-5 text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">
                        No. Rekening ({{ $bank['currency'] }})
                    </p>
                    <div class="mt-1.5 flex flex-wrap items-center gap-3">
                        <p class="font-mono text-2xl font-bold tracking-wide text-brand-700">{{ $bank['account_number'] }}</p>

                        <button type="button"
                                class="viewer-tool"
                                data-copy="{{ $accountPlain }}"
                                data-copy-done="Tersalin!">
                            Salin Nomor
                        </button>
                    </div>

                    <p class="mt-6 rounded-xl bg-ink-50 p-4 text-sm leading-relaxed text-ink-600">
                        Transfer tepat sejumlah <span class="font-bold text-ink-800">{{ $rupiah($installment->amount) }}</span>
                        untuk {{ $installment->title }}, lalu unggah bukti transfernya pada formulir di halaman ini.
                    </p>
                </div>
            </section>

            {{-- Riwayat unggahan: percobaan yang pernah ditolak tetap terbaca. --}}
            @if ($installment->proofs->isNotEmpty())
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                    <h3 class="font-display text-base font-bold text-ink-900">Riwayat Bukti Pembayaran</h3>

                    <ul class="mt-4 space-y-3">
                        @foreach ($installment->proofs as $proof)
                            <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-ink-100 bg-ink-50/70 p-4">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-ink-800">{{ $proof->file_name }}</p>
                                    <p class="mt-0.5 text-xs text-ink-400">
                                        {{ $proof->uploaded_at->translatedFormat('d F Y, H:i') }} WIB &middot; {{ $proof->statusLabel() }}
                                    </p>
                                    @if ($proof->rejection_reason)
                                        <p class="mt-1 text-xs text-rose-600">{{ $proof->rejection_reason }}</p>
                                    @endif
                                </div>

                                <a href="{{ route('dashboard.quotations.installments.proof', [$quotation, $installment, $proof]) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="viewer-tool shrink-0">
                                    Lihat Bukti
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        {{-- ============ KOLOM KANAN: JATUH TEMPO & UNGGAH ============ --}}
        <div class="space-y-6 lg:col-span-5">

            <section class="overflow-hidden rounded-2xl border border-ink-100 bg-gradient-to-br from-brand-700 to-brand-900 p-6 text-white shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-white/70">Batas Waktu Pembayaran</p>

                @if ($installment->due_date === null)
                    <p class="mt-3 font-display text-2xl font-bold">Belum ditentukan</p>
                    <p class="mt-2 text-sm leading-relaxed text-white/80">
                        Admin belum menetapkan tanggal jatuh tempo untuk termin ini.
                    </p>
                @else
                    <p class="mt-3 font-display text-2xl font-bold">
                        {{ $installment->due_date->translatedFormat('d F Y') }}
                    </p>

                    <p class="mt-3 text-sm text-white/80">
                        @if ($daysLeft > 1)
                            Jatuh tempo dalam {{ $daysLeft }} hari lagi.
                        @elseif ($daysLeft === 1)
                            Jatuh tempo besok.
                        @elseif ($daysLeft === 0)
                            Jatuh tempo hari ini.
                        @else
                            Terlambat {{ abs($daysLeft) }} hari dari jatuh tempo.
                        @endif
                    </p>

                    <p class="mt-4 rounded-xl bg-white/10 p-4 text-xs leading-relaxed text-white/85">
                        Keterlambatan pembayaran termin tidak membatalkan penawaran Anda, namun dapat menunda
                        tahap pekerjaan berikutnya.
                    </p>
                @endif
            </section>

            <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Upload Bukti Pembayaran</h3>

                @if ($canUpload)
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-500">
                        Unggah gambar atau file bukti pembayaran Anda. Format yang didukung:
                        <span class="font-semibold text-ink-700">{{ strtoupper(implode(', ', $extensions)) }}</span>,
                        maksimal {{ round($maxKilobytes / 1024) }} MB.
                    </p>

                    <form method="POST"
                          action="{{ route('dashboard.quotations.installments.store', [$quotation, $installment]) }}"
                          enctype="multipart/form-data"
                          class="mt-5 space-y-4">
                        @csrf

                        <div>
                            <label for="proof" class="field-label">Bukti Pembayaran <span class="text-brand-600">*</span></label>
                            <input type="file"
                                   id="proof"
                                   name="proof"
                                   required
                                   accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                                   class="field-input file:mr-4 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-brand-700">
                            @error('proof') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-primary w-full">
                            <x-icons.upload class="h-4 w-4" />
                            Upload Bukti Pembayaran
                        </button>
                    </form>
                @elseif ($installment->isPaid())
                    <p class="mt-1.5 text-sm leading-relaxed text-emerald-700">
                        Pembayaran termin ini sudah diterima admin pada
                        {{ optional($installment->paid_at)->translatedFormat('d F Y, H:i') }} WIB.
                    </p>
                @else
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-500">
                        Bukti pembayaran Anda sudah kami terima dan sedang diperiksa admin. Anda akan menerima
                        notifikasi begitu verifikasinya selesai.
                    </p>
                @endif
            </section>
        </div>
    </div>
@endsection
