@extends('layouts.dashboard')

@section('title', 'Pembayaran '.$quotation->tracking_number)

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        $accountPlain = preg_replace('/\s+/', '', $bank['account_number']);
        $secondsLeft = $quotation->paymentSecondsLeft();
        $needsProof = $quotation->needsPaymentProof();
        $underReview = $quotation->status === \App\Support\QuotationStatus::PAYMENT_REVIEW;
        $wasRejected = $quotation->status === \App\Support\QuotationStatus::PAYMENT_REJECTED;
    @endphp

    <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke detail penawaran
    </a>

    <div class="mt-5">
        <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $quotation->status_label }}</h2>
        <p class="mt-2 text-sm text-ink-500">
            Selesaikan pembayaran agar penawaran Anda dapat masuk antrean produksi.
        </p>
    </div>

    {{-- Bukti sebelumnya ditolak: alasannya ditampilkan lebih dulu agar
         unggahan berikutnya tidak mengulang kesalahan yang sama. --}}
    @if ($wasRejected)
        <div class="mt-6 rounded-2xl border-2 border-brand-300 bg-brand-50 p-5">
            <p class="font-display text-sm font-bold text-brand-900">Bukti pembayaran Anda ditolak admin.</p>
            <p class="mt-1.5 text-sm text-brand-800">
                Alasan: <span class="font-semibold">{{ $quotation->payment_rejection_reason ?: 'Tidak disebutkan.' }}</span>
            </p>
            <p class="mt-1.5 text-sm text-brand-800">
                Silakan periksa kembali lalu unggah ulang bukti pembayaran yang sesuai sebelum batas waktu berakhir.
            </p>
        </div>
    @endif

    @if ($underReview)
        <div class="mt-6 rounded-2xl border-2 border-emerald-300 bg-emerald-50 p-5">
            <p class="font-display text-sm font-bold text-emerald-900">
                Bukti pembayaran berhasil diunggah. Mohon tunggu proses verifikasi dari Admin.
            </p>
            <p class="mt-1.5 text-sm text-emerald-800">
                Diunggah {{ optional($quotation->payment_proof_uploaded_at)->translatedFormat('d F Y, H:i') }} WIB.
                Status penawaran akan berubah begitu admin selesai memeriksa.
            </p>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-12">

        {{-- ============ KOLOM KIRI: TAGIHAN, REKENING, PETUNJUK ============ --}}
        <div class="space-y-6 lg:col-span-7">

            <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Rincian Tagihan</h3>

                <dl class="mt-5 space-y-4">
                    <div class="flex items-start justify-between gap-4 border-b border-ink-100 pb-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Nomor Penawaran</dt>
                        <dd class="text-right font-mono text-sm font-bold text-ink-900">{{ $quotation->tracking_number }}</dd>
                    </div>

                    <div class="flex items-start justify-between gap-4 border-b border-ink-100 pb-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Jumlah Model</dt>
                        <dd class="text-right text-sm font-semibold text-ink-800">{{ $quotation->items->count() }} file &middot; {{ $quotation->quantity }} unit</dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Total Pembayaran</dt>
                        <dd class="text-right font-display text-2xl font-bold text-brand-700">{{ $rupiah($quotation->payment_amount) }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Rekening tujuan, sengaja dibuat paling menonjol di halaman ini. --}}
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
                        Silakan melakukan pembayaran sesuai total tagihan ke rekening di atas, kemudian unggah bukti
                        pembayaran sebelum batas waktu berakhir.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Petunjuk Pembayaran</h3>

                <ol class="mt-4 space-y-3">
                    @foreach ([
                        'Transfer tepat sejumlah '.$rupiah($quotation->payment_amount).' ke rekening '.$bank['name'].' di atas.',
                        'Cantumkan nomor penawaran '.$quotation->tracking_number.' pada berita transfer bila tersedia.',
                        'Simpan bukti transfer dalam format '.strtoupper(implode(', ', $extensions)).'.',
                        'Unggah bukti tersebut pada formulir di halaman ini sebelum hitung mundur berakhir.',
                        'Admin memverifikasi pembayaran Anda, lalu status penawaran berubah menjadi "Pembayaran Diterima".',
                    ] as $index => $step)
                        <li class="flex gap-3">
                            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-[0.7rem] font-bold text-white">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-sm leading-relaxed text-ink-600">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        {{-- ============ KOLOM KANAN: COUNTDOWN & UNGGAH BUKTI ============ --}}
        <div class="space-y-6 lg:col-span-5">

            {{-- Hitung mundur. Angkanya dihitung ulang di browser dari batas
                 waktu yang dikirim server; begitu habis halaman dimuat ulang
                 agar server yang memutuskan penawarannya kedaluwarsa. --}}
            <section class="overflow-hidden rounded-2xl border border-ink-100 bg-gradient-to-br from-brand-700 to-brand-900 p-6 text-white shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-white/70">Sisa Waktu Pembayaran</p>

                @if ($quotation->payment_due_at === null)
                    <p class="mt-3 font-display text-2xl font-bold">Belum ditentukan</p>
                    <p class="mt-2 text-sm leading-relaxed text-white/80">
                        Batas waktu pembayaran akan muncul di sini setelah admin membuka tagihannya.
                    </p>
                @else
                    <p class="mt-3 font-display text-3xl font-bold"
                       data-payment-countdown
                       data-deadline="{{ $quotation->payment_due_at->toIso8601String() }}"
                       data-expired-label="Waktu pembayaran habis">
                        {{ $secondsLeft > 0
                            ? intdiv($secondsLeft, 3600).' Jam '.intdiv($secondsLeft % 3600, 60).' Menit '.($secondsLeft % 60).' Detik'
                            : 'Waktu pembayaran habis' }}
                    </p>

                    <p class="mt-3 text-xs text-white/70">
                        Batas waktu: {{ $quotation->payment_due_at->translatedFormat('d F Y, H:i') }} WIB
                    </p>

                    <p class="mt-4 rounded-xl bg-white/10 p-4 text-xs leading-relaxed text-white/85">
                        Pembayaran harus dilakukan dalam waktu {{ $windowHours }} jam. Jika melewati batas waktu
                        tersebut maka penawaran akan otomatis dibatalkan oleh sistem.
                    </p>
                @endif
            </section>

            {{-- Formulir unggah bukti --}}
            <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Upload Bukti Pembayaran</h3>

                @if ($needsProof)
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-500">
                        Unggah gambar atau file bukti pembayaran Anda. Format yang didukung:
                        <span class="font-semibold text-ink-700">{{ strtoupper(implode(', ', $extensions)) }}</span>,
                        maksimal {{ round($maxKilobytes / 1024) }} MB.
                    </p>

                    <form method="POST"
                          action="{{ route('dashboard.quotations.payment.store', $quotation) }}"
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
                @else
                    <p class="mt-1.5 text-sm leading-relaxed text-ink-500">
                        Bukti pembayaran Anda sudah kami terima dan sedang diperiksa admin. Anda akan menerima
                        notifikasi begitu verifikasinya selesai.
                    </p>
                @endif

                @if ($quotation->hasPaymentProof())
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-ink-100 bg-ink-50/70 p-4">
                        <div class="min-w-0">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Berkas Terunggah</p>
                            <p class="mt-0.5 truncate text-sm font-semibold text-ink-800">{{ $quotation->payment_proof_name }}</p>
                        </div>

                        <a href="{{ route('dashboard.quotations.payment.proof', $quotation) }}"
                           target="_blank"
                           rel="noopener"
                           class="viewer-tool shrink-0">
                            Lihat Bukti
                        </a>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
