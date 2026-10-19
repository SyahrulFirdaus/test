@extends('layouts.dashboard')

@section('title', 'Lead '.$lead->name)

@php
    $labelClass = 'block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400';
    $canEdit = auth()->user()->can(\App\Support\AdminPermission::LEAD_EDIT);
    $canQuotation = auth()->user()->can(\App\Support\AdminPermission::QUOTATION_VIEW);

    $contact = [
        'Nama' => $lead->name,
        'Perusahaan' => $lead->company,
        'Email' => $lead->email,
        'WhatsApp' => $lead->whatsapp ? '+'.$lead->whatsapp : null,
        'Layanan' => $lead->service,
        'Jumlah Pesanan' => $lead->quantity ? number_format($lead->quantity, 0, ',', '.').' pcs' : null,
        'Dimensi / Ukuran' => $lead->dimensions,
        'Sumber' => $lead->source_label,
        'Akun' => $lead->user ? $lead->user->name.' ('.$lead->user->email.')' : null,
        'Tanggal Masuk' => $lead->created_at->translatedFormat('d F Y, H:i').' WIB',
        'Waktu Deal' => $lead->deal_at?->copy()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i').($lead->deal_at ? ' WIB' : ''),
        'Ekspor Google Ads' => $lead->ads_exported_at
            ? $lead->ads_export_count.'× · pertama '.$lead->ads_exported_at->copy()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y, H:i').' WIB'
            : null,
    ];

    $tracking = [
        'Halaman Asal' => $lead->source_url,
        'Halaman Masuk Pertama' => $lead->landing_page,
        'Referrer' => $lead->referrer,
        'Kunjungan Pertama' => $lead->first_touch_at?->translatedFormat('d F Y, H:i'),
        'GCLID' => $lead->gclid,
        'GBRAID' => $lead->gbraid,
        'WBRAID' => $lead->wbraid,
        'UTM Source' => $lead->utm_source,
        'UTM Medium' => $lead->utm_medium,
        'UTM Campaign' => $lead->utm_campaign,
        'UTM Term' => $lead->utm_term,
        'UTM Content' => $lead->utm_content,
    ];
@endphp

@section('content')
    <div>

        <a href="{{ staff_route('leads.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
            Kembali ke daftar lead
        </a>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $lead->name }}</h2>
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ \App\Support\LeadStatus::tone($lead->status) }}">{{ $lead->status_label }}</span>
            <span class="font-display text-lg font-bold text-ink-900">{{ $lead->deal_value_label }}</span>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Kontak --}}
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                    <h3 class="font-display text-base font-bold text-ink-900">Data Lead</h3>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($contact as $label => $value)
                            <div>
                                <dt class="{{ $labelClass }}">{{ $label }}</dt>
                                <dd class="mt-1 break-words text-sm text-ink-800">{{ filled($value) ? $value : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <div class="mt-5">
                        <p class="{{ $labelClass }}">Kebutuhan</p>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $lead->notes ?: '—' }}</p>
                    </div>

                    <div class="mt-5">
                        <p class="{{ $labelClass }}">Lampiran Referensi</p>
                        @if (filled($lead->attachments))
                            <ul class="mt-2 space-y-2">
                                @foreach ($lead->attachments as $index => $file)
                                    <li class="flex flex-wrap items-center gap-3 text-sm">
                                        <a href="{{ staff_route('leads.attachment', [$lead, $index]) }}" class="viewer-tool">Unduh</a>
                                        <span class="break-all text-ink-700">{{ $file['name'] ?? 'Lampiran '.($index + 1) }}</span>
                                        <span class="text-xs text-ink-400">{{ number_format(($file['size'] ?? 0) / 1024, 0, ',', '.') }} KB</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-1 text-sm text-ink-700">—</p>
                        @endif
                    </div>
                </section>

                {{-- Tracking --}}
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                    <h3 class="font-display text-base font-bold text-ink-900">Sumber Kedatangan</h3>
                    <p class="mt-1 text-xs text-ink-400">Data first touch yang tercatat saat pengunjung pertama kali datang. "—" berarti tidak tercatat.</p>
                    <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($tracking as $label => $value)
                            <div class="min-w-0">
                                <dt class="{{ $labelClass }}">{{ $label }}</dt>
                                <dd class="mt-1 break-all text-sm text-ink-800">{{ filled($value) ? $value : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                {{-- Quotation terkait --}}
                <section class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                    <div class="p-6 pb-4">
                        <h3 class="font-display text-base font-bold text-ink-900">Quotation Terkait</h3>
                        <p class="mt-1 text-xs text-ink-400">Status quotation, pembayaran, dan produksi tetap dikelola di menu Penawaran.</p>
                    </div>

                    @if ($lead->quotations->isEmpty())
                        <p class="border-t border-ink-100 px-6 py-8 text-center text-sm text-ink-400">Belum ada quotation untuk lead ini.</p>
                    @else
                        <div class="overflow-x-auto border-t border-ink-100">
                            <table class="w-full min-w-[640px] text-left text-sm">
                                <thead>
                                    <tr class="bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                                        <th scope="col" class="px-5 py-3 font-bold">Nomor</th>
                                        <th scope="col" class="px-5 py-3 font-bold">Model</th>
                                        <th scope="col" class="px-5 py-3 font-bold">Estimasi</th>
                                        <th scope="col" class="px-5 py-3 font-bold">Status Quotation</th>
                                        <th scope="col" class="px-5 py-3 font-bold">Tanggal</th>
                                        <th scope="col" class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-100">
                                    @foreach ($lead->quotations as $quotation)
                                        <tr>
                                            <td class="px-5 py-3 font-mono text-xs text-brand-600">{{ $quotation->tracking_number }}</td>
                                            <td class="px-5 py-3 text-ink-700">{{ max(1, $quotation->items_count) }} model</td>
                                            <td class="px-5 py-3 text-ink-700">{{ harga_penawaran($quotation->estimated_cost, '—') }}</td>
                                            <td class="px-5 py-3">
                                                <span class="inline-flex whitespace-nowrap rounded-full border border-ink-200 bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">{{ $quotation->status_label }}</span>
                                            </td>
                                            <td class="px-5 py-3 text-xs text-ink-500">{{ $quotation->created_at->translatedFormat('d M Y, H:i') }}</td>
                                            <td class="px-5 py-3 text-right">
                                                @if ($canQuotation)
                                                    <a href="{{ staff_route('quotations.show', $quotation) }}" class="viewer-tool">Detail</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <div class="space-y-6">
                {{-- Kelola --}}
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                    <h3 class="font-display text-base font-bold text-ink-900">Status &amp; Nilai Deal</h3>

                    @if ($canEdit)
                        <form method="POST" action="{{ staff_route('leads.update', $lead) }}" class="mt-5 space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label for="status" class="{{ $labelClass }}">Status Lead</label>
                                <select id="status" name="status" class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $lead->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="deal_value" class="{{ $labelClass }}">Nilai Deal (Rp)</label>
                                <div class="mt-2 flex items-center rounded-xl border border-ink-200 focus-within:border-brand-600">
                                    <span class="pl-4 text-sm font-semibold text-ink-400">Rp</span>
                                    <input type="text" id="deal_value" name="deal_value" inputmode="numeric" autocomplete="off"
                                           value="{{ old('deal_value', $lead->deal_value === null ? '' : number_format($lead->deal_value, 0, ',', '.')) }}"
                                           placeholder="Contoh: 5.000.000"
                                           class="w-full rounded-xl border-0 px-2 py-2.5 text-sm focus:outline-none focus:ring-0">
                                </div>
                                <p class="mt-1.5 text-[0.65rem] text-ink-400">Diisi manual, tidak diambil dari estimasi quotation. Kosongkan bila belum ada nilai. Status Batal tidak menghapus nilai ini.</p>
                                @error('deal_value')
                                    <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn-primary w-full py-2.5">Simpan</button>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-ink-500">Anda hanya memiliki akses melihat lead ini.</p>
                    @endif
                </section>

                {{-- Riwayat --}}
                <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                    <h3 class="font-display text-base font-bold text-ink-900">Riwayat Perubahan</h3>

                    @if ($logs->isEmpty())
                        <p class="mt-3 text-sm text-ink-400">Belum ada riwayat.</p>
                    @else
                        <ol class="mt-5 space-y-4">
                            @foreach ($logs as $log)
                                @php
                                    $old = $log->old_values ?? [];
                                    $new = $log->new_values ?? [];
                                @endphp
                                <li class="border-l-2 border-ink-100 pl-4">
                                    <p class="text-sm font-semibold text-ink-900">{{ \App\Support\ActivityAction::label($log->action) }}</p>
                                    <p class="text-xs text-ink-400">
                                        {{ $log->created_at->translatedFormat('d M Y, H:i') }} &middot; {{ $log->user_name ?: 'Sistem / Pengunjung' }}
                                    </p>

                                    @if (array_key_exists('status', $old) || array_key_exists('status', $new))
                                        <p class="mt-1.5 text-xs text-ink-600">
                                            Status: <span class="font-semibold">{{ $old['status'] ?? '—' }}</span> → <span class="font-semibold">{{ $new['status'] ?? '—' }}</span>
                                        </p>
                                    @endif

                                    @if (array_key_exists('deal_value', $old) || array_key_exists('deal_value', $new))
                                        <p class="text-xs text-ink-600">
                                            Nilai deal: <span class="font-semibold">{{ $old['deal_value'] ?? '—' }}</span> → <span class="font-semibold">{{ $new['deal_value'] ?? '—' }}</span>
                                        </p>
                                    @endif

                                    @if ($log->description)
                                        <p class="mt-1 text-xs text-ink-400">{{ $log->description }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>
        </div>
    </div>
@endsection
