{{--
    Bagian PEMBAYARAN pada Detail Penawaran.

    Inilah tempat pembayaran SEKALI BAYAR diputuskan — satu-satunya tempatnya.
    Penawaran yang berjalan dengan Payment Term tidak diputuskan di sini
    melainkan per termin lewat menu Pembayaran, dan bagian ini hanya
    menunjukkan ke sana supaya tidak ada dua antrean untuk bukti yang sama.

    Diharapkan:
      $quotation  App\Models\QuotationRequest
      $rupiah     penata angka rupiah
--}}
@php
    use App\Support\QuotationStatus;

    $pakaiTermin = $quotation->usesInstallments();
    $menunggu = $quotation->awaitsPaymentDecision();

    // Bagian ini hanya berarti setelah pembayaran benar-benar dibuka; sebelum
    // itu belum ada apa pun untuk ditampilkan.
    $adaRiwayat = $quotation->payment_proof_uploaded_at
        || $quotation->payment_verified_at
        || $quotation->payment_rejected_at
        || in_array($quotation->status, [
            QuotationStatus::AWAITING_PAYMENT,
            QuotationStatus::PAYMENT_REVIEW,
            QuotationStatus::PAYMENT_RECEIVED,
            QuotationStatus::PAYMENT_REJECTED,
        ], true);

    $bolehMemutuskan = auth()->user()->can(\App\Support\AdminPermission::PAYMENT_VERIFY);
@endphp

@if ($adaRiwayat || $pakaiTermin)
    <section id="pembayaran"
             class="scroll-mt-24 rounded-2xl border p-6 shadow-card sm:p-7
                    {{ $menunggu ? 'border-2 border-amber-300 bg-amber-50' : 'border-ink-100 bg-white' }}">

        <div class="flex flex-wrap items-start justify-between gap-3">
            <h2 class="font-display text-base font-bold {{ $menunggu ? 'text-amber-900' : 'text-ink-900' }}">Pembayaran</h2>

            <span class="inline-flex whitespace-nowrap rounded-full border px-3 py-1 text-xs font-bold
                         {{ $menunggu ? 'border-amber-300 bg-white text-amber-800' : 'border-ink-200 bg-ink-50 text-ink-600' }}">
                {{ $quotation->status_label }}
            </span>
        </div>

        @if ($pakaiTermin)
            {{-- Pembayaran bertahap: keputusannya per termin, bukan per
                 penawaran. Satu bukti hanya punya satu tempat verifikasi. --}}
            <p class="mt-4 text-sm leading-relaxed text-ink-600">
                Penawaran ini memakai <strong class="font-semibold text-ink-800">Payment Term</strong>
                {{ $quotation->paymentTerm->installment_count }} termin, jadi setiap terminnya
                diverifikasi terpisah beserta bukti dan nominalnya masing-masing.
            </p>

            <a href="{{ staff_route('payment-terms.show', $quotation->paymentTerm) }}" class="btn-primary mt-5 inline-flex">
                Buka Jadwal &amp; Verifikasi Termin
            </a>
        @else
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Total Penawaran</dt>
                    <dd class="mt-1.5 font-display text-lg font-bold text-ink-900">
                        {{ $quotation->display_price === null ? 'Menunggu Perhitungan' : $rupiah($quotation->payment_amount) }}
                    </dd>
                </div>

                <div>
                    <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Bukti Pembayaran</dt>
                    <dd class="mt-1.5 break-words text-sm font-semibold text-ink-800">
                        {{ $quotation->payment_proof_name ?: 'Belum diunggah' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Tanggal Upload</dt>
                    <dd class="mt-1.5 text-sm font-semibold text-ink-800">
                        {{ $quotation->payment_proof_uploaded_at?->translatedFormat('d F Y H:i') ?? '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Batas Pembayaran</dt>
                    <dd class="mt-1.5 text-sm font-semibold text-ink-800">
                        {{ $quotation->payment_due_at?->translatedFormat('d F Y H:i') ?? '-' }}
                    </dd>
                </div>
            </dl>

            {{-- Jejak keputusan: siapa, dan kapan. --}}
            @if ($quotation->payment_verified_at)
                <p class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Pembayaran diterima {{ $quotation->payment_verified_at->translatedFormat('d F Y H:i') }}
                    @if ($quotation->paymentVerifier)
                        oleh <strong class="font-semibold">{{ $quotation->paymentVerifier->name }}</strong>
                    @endif.
                </p>
            @elseif ($quotation->payment_rejected_at)
                <div class="mt-5 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                    <p>
                        Pembayaran ditolak {{ $quotation->payment_rejected_at->translatedFormat('d F Y H:i') }}
                        @if ($quotation->paymentRejecter)
                            oleh <strong class="font-semibold">{{ $quotation->paymentRejecter->name }}</strong>
                        @endif.
                    </p>
                    @if ($quotation->payment_rejection_reason)
                        <p class="mt-1.5 whitespace-pre-line leading-relaxed">
                            Alasan: {{ $quotation->payment_rejection_reason }}
                        </p>
                    @endif
                </div>
            @endif

            @if ($quotation->payment_proof_uploaded_at)
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ staff_route('quotations.payment.proof', $quotation) }}"
                       target="_blank" rel="noopener"
                       class="viewer-tool">
                        Lihat Bukti Pembayaran
                    </a>

                    {{-- Keputusan hanya muncul selama memang ada yang menunggu.
                         Haknya tetap diperiksa backend, bukan hanya di sini. --}}
                    @if ($menunggu && $bolehMemutuskan)
                        <form method="POST" action="{{ staff_route('quotations.payment.accept', $quotation) }}"
                              onsubmit="return confirm('Terima pembayaran {{ $quotation->tracking_number }}? Status penawaran berpindah ke Pembayaran Diterima dan pelanggan diberi tahu.');">
                            @csrf
                            <button type="submit" class="btn-primary">Terima Pembayaran</button>
                        </form>

                        <button type="button" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50"
                                data-reject-open>
                            Tolak Pembayaran
                        </button>
                    @endif
                </div>

                @if ($menunggu && ! $bolehMemutuskan)
                    <p class="mt-3 text-xs text-ink-400">
                        Bukti pembayaran menunggu verifikasi. Akun Anda tidak memiliki hak Verifikasi Pembayaran.
                    </p>
                @endif
            @elseif ($quotation->status === QuotationStatus::AWAITING_PAYMENT)
                <p class="mt-5 text-sm text-ink-500">
                    Menunggu pelanggan mengunggah bukti pembayaran.
                </p>
            @endif
        @endif
    </section>

    {{-- ============ MODAL TOLAK PEMBAYARAN ============ --}}
    @if (! $pakaiTermin && $menunggu && $bolehMemutuskan)
        <div class="fixed inset-0 z-50 hidden items-center justify-center bg-ink-900/60 p-4 backdrop-blur-sm"
             data-reject-modal role="dialog" aria-modal="true" aria-labelledby="reject-title">

            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl sm:p-7" data-reject-dialog>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 id="reject-title" class="font-display text-lg font-bold text-ink-900">Tolak Pembayaran</h3>
                        <p class="mt-1 text-sm text-ink-500">{{ $quotation->tracking_number }}</p>
                    </div>

                    <button type="button" class="shrink-0 rounded-lg p-1.5 text-ink-400 transition-colors hover:bg-ink-50 hover:text-ink-700"
                            data-reject-close aria-label="Tutup">
                        <x-icons.close class="h-5 w-5" />
                    </button>
                </div>

                <form method="POST" action="{{ staff_route('quotations.payment.reject', $quotation) }}" class="mt-5">
                    @csrf

                    <label for="reject-reason" class="field-label">Alasan Penolakan</label>
                    <textarea id="reject-reason" name="reason" rows="4" required maxlength="2000"
                              class="field-input"
                              placeholder="mis. Nominal transfer tidak sesuai dengan total penawaran.">{{ old('reason') }}</textarea>
                    <p class="mt-1.5 text-xs text-ink-400">
                        Alasannya dikirim ke pelanggan dan tampil pada dashboard-nya, jadi tuliskan yang dapat ditindaklanjuti.
                    </p>
                    @error('reason') <p class="field-error">{{ $message }}</p> @enderror

                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                        <button type="button" class="viewer-tool" data-reject-close>Batal</button>
                        <button type="submit" class="btn-primary">Tolak Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
        <script>
            /**
             * Modal Tolak Pembayaran.
             *
             * Alasannya wajib diisi — itulah yang dibaca pelanggan untuk tahu
             * apa yang harus diperbaiki, jadi penolakan tanpa keterangan tidak
             * pernah dikirim.
             */
            (function () {
                const modal = document.querySelector('[data-reject-modal]');
                const open = document.querySelector('[data-reject-open]');

                if (!modal || !open) {
                    return;
                }

                const dialog = modal.querySelector('[data-reject-dialog]');
                const reason = modal.querySelector('[name="reason"]');

                const setOpen = (isOpen) => {
                    modal.style.display = isOpen ? 'flex' : 'none';
                    document.body.style.overflow = isOpen ? 'hidden' : '';

                    if (isOpen) {
                        reason.focus();
                    } else {
                        open.focus();
                    }
                };

                open.addEventListener('click', () => setOpen(true));
                modal.querySelectorAll('[data-reject-close]').forEach((el) => el.addEventListener('click', () => setOpen(false)));

                modal.addEventListener('mousedown', (event) => {
                    if (!dialog.contains(event.target)) {
                        setOpen(false);
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal.style.display === 'flex') {
                        setOpen(false);
                    }
                });

                // Kiriman yang gagal validasi membuka kembali modalnya, supaya
                // pesannya terbaca di tempat isiannya berada.
                @error('reason') setOpen(true); @enderror
            })();
        </script>
        @endpush
    @endif
@endif
