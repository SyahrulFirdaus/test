@extends('layouts.dashboard')

@section('title', 'Payment Term '.$term->quotation->tracking_number)

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

        $quotation = $term->quotation;
        $profile = $quotation->user?->businessProfile;
    @endphp

    <a href="{{ staff_route('payment-terms.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Payment Terms
    </a>

    <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
                {{ $profile?->company_name ?: ($quotation->company ?: $quotation->name) }}
            </h2>
            <p class="mt-2 text-sm text-ink-500">
                {{ $term->scheme_label }} &middot; {{ $term->status_label }}
                @if ($term->approver) &middot; diputuskan {{ $term->approver->name }} @endif
            </p>
        </div>

        @can(\App\Support\AdminPermission::QUOTATION_VIEW)
        <a href="{{ staff_route('quotations.show', $quotation) }}" class="viewer-tool">Lihat Penawaran</a>
        @endcan
    </div>

    {{-- ===================== RINGKASAN NILAI ===================== --}}
    <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $summary = [
                    'Total Penawaran' => $rupiah($term->total_amount),
                    'Jumlah Termin' => $term->installment_count.'x',
                    'Sudah Dibayar' => $rupiah($term->paidAmount()),
                    'Sisa Pembayaran' => $rupiah($term->outstandingAmount()),
                ];
            @endphp

            @foreach ($summary as $label => $value)
                <div>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</p>
                    <p class="mt-1 font-display text-lg font-bold {{ $loop->first ? 'text-brand-700' : 'text-ink-900' }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===================== KEPUTUSAN PENGAJUAN ===================== --}}
    @if ($term->isPending())
        <section class="mt-6 rounded-2xl border-2 border-amber-300 bg-amber-50 p-6">
            <h3 class="font-display text-base font-bold text-amber-900">Menunggu Persetujuan Payment Term</h3>
            <p class="mt-1.5 text-sm text-amber-800">
                Pelanggan mengajukan {{ $term->scheme_label }} pada
                {{ optional($term->requested_at)->translatedFormat('d F Y, H:i') }} WIB.
                Menyetujui pengajuan ini langsung membentuk jadwal terminnya dan mengaktifkan Termin 1.
            </p>

            @can(\App\Support\AdminPermission::PAYMENT_TERM_EDIT)
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <form method="POST" action="{{ staff_route('payment-terms.approve', $term) }}">
                    @csrf
                    <button type="submit" class="btn-primary w-full">Setujui Payment Term</button>
                </form>

                <form method="POST" action="{{ staff_route('payment-terms.reject', $term) }}" class="space-y-3">
                    @csrf
                    <textarea name="reason"
                              rows="2"
                              placeholder="Alasan penolakan (opsional, tetapi sangat dianjurkan)"
                              class="field-input">{{ old('reason') }}</textarea>
                    @error('reason') <p class="field-error">{{ $message }}</p> @enderror

                    <button type="submit" class="viewer-tool w-full justify-center">Tolak Payment Term</button>
                </form>
            </div>
            @else
                <p class="mt-4 text-xs font-semibold text-amber-800">Anda tidak memiliki hak akses Edit Payment Term untuk memutuskan pengajuan ini.</p>
            @endcan
        </section>
    @endif

    @if ($term->isRejected())
        <div class="mt-6 rounded-2xl border-2 border-rose-300 bg-rose-50 p-5">
            <p class="font-display text-sm font-bold text-rose-900">Payment Term Ditolak</p>
            <p class="mt-1.5 text-sm text-rose-800">
                Alasan: {{ $term->rejection_reason ?: 'Tidak disebutkan.' }}
                Pelanggan dapat mengajukan skema lain.
            </p>
        </div>
    @endif

    {{-- ===================== PEMBAGIAN TERMIN ===================== --}}
    @if ($term->hasSchedule())
        <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
            <h3 class="font-display text-base font-bold text-ink-900">Pembagian Termin</h3>
            <p class="mt-1.5 text-sm text-ink-500">
                Nominal dihitung dari persentase. Total persentase harus tepat 100%. Bila tidak, perubahan
                ditolak dan tidak ada yang tersimpan. Termin yang sudah dibayar terkunci nominalnya.
            </p>

            <form method="POST" action="{{ staff_route('payment-terms.schedule', $term) }}" class="mt-5">
                @csrf
                @method('PATCH')

                {{-- Tanpa hak Edit jadwal tetap terbaca, tetapi terkunci. --}}
                <fieldset @cannot(\App\Support\AdminPermission::PAYMENT_TERM_EDIT) disabled @endcannot>

                <div class="space-y-4">
                    @foreach ($term->installments as $index => $installment)
                        <div class="rounded-xl border border-ink-100 bg-ink-50/60 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="font-display text-sm font-bold text-ink-900">
                                    {{ $installment->title }}
                                    <span class="ml-2 rounded-full bg-white px-2.5 py-0.5 text-[0.65rem] font-bold text-ink-500">
                                        {{ $installment->status_label }}
                                    </span>
                                </p>
                                <p class="font-display text-base font-bold text-brand-700">{{ $rupiah($installment->amount) }}</p>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="field-label">Persentase (%)</label>
                                    <input type="number"
                                           step="0.01"
                                           min="0.01"
                                           max="100"
                                           name="installments[{{ $index }}][percentage]"
                                           value="{{ old('installments.'.$index.'.percentage', (float) $installment->percentage) }}"
                                           @disabled($installment->isPaid())
                                           class="field-input">
                                </div>

                                <div>
                                    <label class="field-label">Milestone</label>
                                    <input type="text"
                                           name="installments[{{ $index }}][milestone]"
                                           value="{{ old('installments.'.$index.'.milestone', $installment->milestone) }}"
                                           list="milestone-suggestions"
                                           class="field-input">
                                </div>

                                <div>
                                    <label class="field-label">Jatuh Tempo</label>
                                    <input type="date"
                                           name="installments[{{ $index }}][due_date]"
                                           value="{{ old('installments.'.$index.'.due_date', optional($installment->due_date)->format('Y-m-d')) }}"
                                           class="field-input">
                                </div>
                            </div>

                            {{-- Termin yang sudah lunas tetap harus mengirim
                                 persentasenya agar totalnya tetap terhitung 100%. --}}
                            @if ($installment->isPaid())
                                <input type="hidden"
                                       name="installments[{{ $index }}][percentage]"
                                       value="{{ (float) $installment->percentage }}">
                            @endif
                        </div>
                    @endforeach
                </div>

                <datalist id="milestone-suggestions">
                    @foreach ($milestones as $milestone)
                        <option value="{{ $milestone }}"></option>
                    @endforeach
                </datalist>

                @error('installments') <p class="field-error mt-3">{{ $message }}</p> @enderror
                @error('installments.*.percentage') <p class="field-error mt-3">{{ $message }}</p> @enderror

                </fieldset>

                @can(\App\Support\AdminPermission::PAYMENT_TERM_EDIT)
                <button type="submit" class="btn-primary mt-5 w-full sm:w-auto sm:px-8">Simpan Pembagian Termin</button>
                @endcan
            </form>
        </section>

        {{-- ===================== JADWAL & AKTIVASI ===================== --}}
        <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
            <h3 class="font-display text-base font-bold text-ink-900">Jadwal & Status Pembayaran</h3>

            <div class="mt-5 space-y-3">
                @foreach ($term->installments as $installment)
                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-ink-100 p-4">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-ink-900">
                                {{ $installment->marker }} {{ $installment->title }} &middot; {{ $rupiah($installment->amount) }}
                            </p>
                            <p class="mt-0.5 text-xs text-ink-400">
                                {{ $installment->status_label }}
                                @if ($installment->milestone) &middot; {{ $installment->milestone }} @endif
                                @if ($installment->due_date) &middot; jatuh tempo {{ $installment->due_date->translatedFormat('d F Y') }} @endif
                            </p>

                            @if ($installment->latestProof)
                                <p class="mt-1 text-xs text-ink-400">
                                    Bukti terakhir: {{ $installment->latestProof->file_name }}
                                    ({{ $installment->latestProof->uploaded_at->translatedFormat('d F Y, H:i') }} WIB)
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if ($installment->latestProof && auth()->user()->can(\App\Support\AdminPermission::PAYMENT_VIEW))
                                <a href="{{ staff_route('payments.installments.proof', [$installment, $installment->latestProof]) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="viewer-tool">Lihat Bukti</a>
                            @endif

                            @if ($installment->status === \App\Support\InstallmentStatus::INACTIVE && auth()->user()->can(\App\Support\AdminPermission::PAYMENT_TERM_EDIT))
                                <form method="POST" action="{{ staff_route('payment-terms.installments.activate', [$term, $installment]) }}">
                                    @csrf
                                    <button type="submit" class="viewer-tool">Aktifkan Termin</button>
                                </form>
                            @endif

                            @if ($installment->isAwaitingVerification() && auth()->user()->can(\App\Support\AdminPermission::PAYMENT_VIEW))
                                <a href="{{ staff_route('payments.index', ['filter' => 'installments']) }}" class="btn-primary px-4 py-2">
                                    Verifikasi
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
