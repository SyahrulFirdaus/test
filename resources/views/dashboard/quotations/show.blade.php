@extends('layouts.dashboard')

@section('title', 'Penawaran '.$quotation->tracking_number)

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
        $angka = fn ($value, $digits = 2) => is_numeric($value) ? number_format((float) $value, $digits, ',', '.') : '—';
    @endphp

    <a href="{{ route('dashboard.quotations.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Penawaran Saya
    </a>

    <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $quotation->status_label }}</h2>
            <p class="mt-2 text-sm text-ink-500">
                Dikirim {{ $quotation->created_at->translatedFormat('d F Y, H:i') }} WIB &middot;
                <span class="font-semibold text-ink-700">{{ $quotation->items->count() }} file 3D</span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($quotation->isEditable())
                <a href="{{ route('dashboard.quotations.edit', $quotation) }}" class="btn-primary px-5 py-2.5">Ubah Penawaran</a>
            @endif

            <a href="{{ route('tracking.show', $quotation->tracking_number) }}" target="_blank" rel="noopener noreferrer" class="viewer-tool">
                Halaman Tracking
            </a>
            <a href="{{ route('tracking.document', $quotation->tracking_number) }}" class="viewer-tool">
                <x-icons.download class="h-4 w-4" />
                Bukti Penawaran (PDF)
            </a>
        </div>
    </div>

    {{-- Keadaan penyuntingan & pembatalan --}}
    @if ($quotation->hasPendingCancellation())
        <div class="mt-6 rounded-2xl border-2 border-amber-300 bg-amber-50 p-5">
            <p class="font-display text-sm font-bold text-amber-900">Permintaan pembatalan Anda sedang ditinjau admin.</p>
            <p class="mt-1.5 text-sm text-amber-800">
                Diajukan {{ optional($quotation->cancellation_requested_at)->translatedFormat('d F Y, H:i') }} WIB.
                Kami akan memberi tahu Anda begitu ada keputusan.
            </p>
        </div>
    @elseif ($quotation->isEditable())
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="font-display text-sm font-bold text-emerald-900">Penawaran masih dapat diubah.</p>
            <p class="mt-1.5 text-sm text-emerald-800">
                Selama statusnya masih "Menunggu Review", Anda dapat menambah atau menghapus file, mengubah pengaturan
                printing, dan mengganti jumlah cetak. Setelah admin mulai mereview, isinya menjadi tetap.
            </p>
        </div>
    @elseif ($quotation->isClosed())
        <div class="mt-6 rounded-2xl border border-ink-200 bg-white p-5">
            <p class="font-display text-sm font-bold text-ink-900">Penawaran ini sudah berakhir.</p>
            @if ($quotation->cancellation_admin_note)
                <p class="mt-1.5 text-sm text-ink-600">Catatan admin: {{ $quotation->cancellation_admin_note }}</p>
            @endif
        </div>
    @else
        <div class="mt-6 rounded-2xl border border-ink-200 bg-white p-5">
            <p class="font-display text-sm font-bold text-ink-900">Penawaran sedang diproses tim kami.</p>
            <p class="mt-1.5 text-sm text-ink-600">
                Isi penawaran kini bersifat read only. Bila ada yang perlu diubah, hubungi kami atau ajukan pembatalan
                di bawah halaman ini.
            </p>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-12">

        {{-- Kolom kiri: file & ringkasan --}}
        <div class="space-y-6 lg:col-span-8">

            <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Ringkasan Penawaran</h3>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        'Jumlah File' => $quotation->items->count().' file',
                        'Total Unit' => $quotation->quantity.' unit',
                        'Total Berat' => $angka($quotation->total_weight_g, 1).' gram',
                        'Total Estimasi Biaya' => $rupiah($quotation->display_price),
                    ] as $label => $value)
                        <div>
                            <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                            <dd class="mt-1.5 text-sm font-bold text-ink-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if ($quotation->estimated_price !== null)
                    <p class="mt-5 rounded-xl bg-brand-50 p-4 text-xs leading-relaxed text-brand-800">
                        Angka di atas adalah harga penawaran resmi yang sudah ditetapkan tim kami.
                    </p>
                @else
                    <p class="mt-5 rounded-xl bg-ink-50 p-4 text-xs leading-relaxed text-ink-500">
                        Angka di atas masih estimasi sistem. Harga resmi dikirimkan setelah engineer kami menyelesaikan review.
                    </p>
                @endif
            </section>

            {{-- Daftar file --}}
            <section class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                <div class="border-b border-ink-100 px-6 py-5">
                    <h3 class="font-display text-base font-bold text-ink-900">File 3D dalam Penawaran Ini</h3>
                </div>

                <ul class="divide-y divide-ink-100">
                    @foreach ($quotation->items as $item)
                        <li class="px-6 py-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="flex items-center gap-2">
                                        <span class="font-mono text-[0.65rem] text-ink-400">#{{ $item->position }}</span>
                                        <span class="truncate font-bold text-ink-900">{{ $item->file_name }}</span>
                                    </p>
                                    <p class="mt-1 text-xs text-ink-500">
                                        {{ $item->printer_name }} &middot; {{ $item->technology }} &middot; {{ $item->material }} &middot;
                                        {{ $item->quantity }} unit &middot; {{ $item->resolution_label }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-ink-400">
                                        Infill {{ $item->infill_label }} &middot; Skala {{ $item->scale_label }} &middot;
                                        Support {{ $item->support_enabled ? 'aktif' : 'nonaktif' }} &middot;
                                        Warna {{ $item->material_color_label }} &middot;
                                        Finishing {{ $item->finishing_label }}
                                    </p>
                                </div>

                                <div class="text-right">
                                    <p class="font-display text-sm font-bold text-brand-700">{{ $rupiah($item->display_price) }}</p>
                                    <p class="mt-0.5 text-xs text-ink-400">{{ $angka($item->total_weight_g, 1) }} g &middot; {{ $item->estimated_duration ?? '—' }}</p>
                                </div>
                            </div>

                            @if ($item->admin_note)
                                <p class="mt-3 rounded-xl bg-ink-50 p-3 text-xs leading-relaxed text-ink-600">
                                    Catatan tim: {{ $item->admin_note }}
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Pembatalan --}}
            @if ($quotation->canBeCancelledDirectly() || $quotation->canRequestCancellation())
                <section class="rounded-2xl border border-brand-200 bg-white p-6 shadow-card">
                    <h3 class="font-display text-base font-bold text-ink-900">Batalkan Penawaran</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">
                        @if ($quotation->canBeCancelledDirectly())
                            Penawaran belum masuk proses review, sehingga pembatalan langsung berlaku.
                        @else
                            Berkas Anda sudah masuk proses review, jadi pembatalan perlu disetujui admin lebih dulu.
                            Statusnya akan menjadi "Permintaan Pembatalan" sampai ada keputusan.
                        @endif
                    </p>

                    <form method="POST" action="{{ route('dashboard.quotations.cancel', $quotation) }}" class="mt-5"
                          onsubmit="return confirm('{{ $quotation->canBeCancelledDirectly() ? 'Batalkan penawaran ini?' : 'Ajukan pembatalan penawaran ini?' }}');">
                        @csrf

                        <label for="reason" class="field-label">Alasan Pembatalan <span class="text-ink-300">(opsional)</span></label>
                        <textarea id="reason" name="reason" rows="3" maxlength="1000" class="field-input"
                                  placeholder="Misalnya: desain masih akan direvisi, atau proyek ditunda.">{{ old('reason') }}</textarea>

                        <button type="submit" class="btn-outline mt-4 border-brand-300 text-brand-700 hover:border-brand-600 hover:text-brand-700">
                            {{ $quotation->canBeCancelledDirectly() ? 'Batalkan Penawaran' : 'Ajukan Pembatalan' }}
                        </button>
                    </form>
                </section>
            @endif
        </div>

        {{-- Kolom kanan: timeline & riwayat --}}
        <div class="space-y-6 lg:col-span-4">

            <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Alur Status</h3>

                <ol class="mt-5 space-y-4">
                    @foreach ($quotation->timeline as $step)
                        <li class="flex gap-3">
                            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 text-[0.6rem] font-bold
                                {{ match ($step['state']) {
                                    'done' => 'border-emerald-500 bg-emerald-500 text-white',
                                    'current' => 'border-brand-600 bg-brand-600 text-white',
                                    default => 'border-ink-200 bg-white text-ink-300',
                                } }}">
                                {{ $step['state'] === 'done' ? '✓' : $loop->iteration }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold {{ $step['state'] === 'upcoming' ? 'text-ink-400' : 'text-ink-900' }}">
                                    {{ $step['label'] }}
                                </p>
                                @if ($step['state'] === 'current' && $step['description'])
                                    <p class="mt-0.5 text-xs leading-relaxed text-ink-500">{{ $step['description'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                <h3 class="font-display text-base font-bold text-ink-900">Riwayat</h3>

                <ul class="mt-5 space-y-4">
                    @forelse ($quotation->timelineHistories as $history)
                        <li class="border-l-2 border-ink-100 pl-4">
                            <p class="text-sm font-semibold text-ink-900">{{ $history->status_label }}</p>
                            @if ($history->note)
                                <p class="mt-0.5 text-xs leading-relaxed text-ink-500">{{ $history->note }}</p>
                            @endif
                            <p class="mt-1 text-[0.65rem] text-ink-400">
                                {{ $history->created_at->translatedFormat('d M Y, H:i') }} WIB
                                @if ($history->created_by) &middot; {{ $history->created_by }} @endif
                            </p>
                        </li>
                    @empty
                        <li class="text-sm text-ink-400">Belum ada riwayat.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
@endsection
