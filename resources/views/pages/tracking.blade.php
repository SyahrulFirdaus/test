@extends('layouts.app')

@section('title', 'Tracking Penawaran '.$quotation->tracking_number)
@section('description', 'Pantau perkembangan permintaan penawaran 3D printing Anda melalui nomor tracking.')

{{-- Halaman memuat data permintaan pelanggan, jadi tidak boleh diindeks. --}}
@section('robots', 'noindex, nofollow')

@section('content')

    <x-page-hero
        eyebrow="Tracking Penawaran"
        current="Tracking"
        title='Perkembangan permintaan <span class="text-brand-400">penawaran Anda</span>'
        :description="'Nomor tracking '.$quotation->tracking_number.', diajukan '.$quotation->created_at->translatedFormat('d F Y, H:i').' WIB.'">
        <x-slot:actions>
            <a href="{{ route('tracking.document', $quotation->tracking_number) }}" class="btn-primary w-full sm:w-auto">
                <x-icons.download class="h-4 w-4" />
                Download Bukti Penawaran (PDF)
            </a>
            <a href="{{ route('tracking.index') }}" class="btn-ghost-light w-full sm:w-auto">Lacak Nomor Lain</a>
        </x-slot:actions>
    </x-page-hero>

    <section class="section">
        <div class="container-page">
            <div class="grid gap-6 lg:grid-cols-12">

                {{-- ============== TIMELINE STATUS ============== --}}
                <div class="lg:col-span-7" data-aos="fade-up">
                    <div class="rounded-3xl border border-ink-100 bg-white p-7 shadow-card sm:p-8">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="font-display text-base font-bold text-ink-900">Progress Status</h2>
                            <span class="rounded-full border border-brand-200 bg-brand-50 px-4 py-1.5 text-xs font-bold text-brand-700">
                                {{ $quotation->status_label }}
                            </span>
                        </div>

                        <ol class="relative mt-8">
                            @foreach ($timeline as $step)
                                @php
                                    [$dot, $ring, $title, $line] = match ($step['state']) {
                                        'done' => ['bg-emerald-500 text-white', 'ring-emerald-100', 'text-ink-900', 'bg-emerald-300'],
                                        'current' => ['bg-brand-600 text-white', 'ring-brand-100', 'text-brand-700', 'bg-ink-200'],
                                        default => ['bg-ink-200 text-ink-500', 'ring-ink-100', 'text-ink-400', 'bg-ink-200'],
                                    };
                                @endphp

                                <li class="relative flex gap-4 pb-7 last:pb-0">
                                    {{-- Garis penghubung antar langkah --}}
                                    @unless ($loop->last)
                                        <span class="absolute left-[15px] top-8 h-[calc(100%-1rem)] w-0.5 {{ $line }}" aria-hidden="true"></span>
                                    @endunless

                                    <span class="relative z-10 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-4 {{ $dot }} {{ $ring }}">
                                        @if ($step['state'] === 'done')
                                            <x-icons.check class="h-4 w-4" />
                                        @else
                                            <span class="text-[0.65rem] font-bold">{{ $loop->iteration }}</span>
                                        @endif
                                    </span>

                                    <div class="min-w-0 flex-1 pt-1">
                                        <p class="text-sm font-bold {{ $title }}">
                                            {{ $step['label'] }}
                                            @if ($step['optional'])
                                                <span class="ml-1 text-[0.65rem] font-medium text-ink-400">(opsional)</span>
                                            @endif
                                            @if ($step['state'] === 'current')
                                                <span class="ml-2 inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.1em] text-white">
                                                    Sedang berjalan
                                                </span>
                                            @endif
                                        </p>
                                        @if ($step['description'])
                                            <p class="mt-1 text-xs leading-relaxed text-ink-500">{{ $step['description'] }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    {{-- ============== RIWAYAT TRACKING ============== --}}
                    <div class="mt-6 overflow-hidden rounded-3xl border border-ink-100 bg-white shadow-card">
                        <div class="border-b border-ink-100 px-7 py-5">
                            <h2 class="font-display text-base font-bold text-ink-900">Riwayat Tracking</h2>
                            <p class="mt-1 text-xs text-ink-400">Seluruh perubahan tersimpan dan tidak terhapus saat status berpindah.</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[520px] text-left text-sm">
                                <thead>
                                    <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                                        <th scope="col" class="px-6 py-3.5 font-bold">Tanggal</th>
                                        <th scope="col" class="px-6 py-3.5 font-bold">Status</th>
                                        <th scope="col" class="px-6 py-3.5 font-bold">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-100">
                                    @forelse ($histories as $history)
                                        <tr>
                                            <td class="whitespace-nowrap px-6 py-4 text-xs text-ink-500">
                                                {{ $history->created_at->translatedFormat('d F Y') }}<br>
                                                <span class="text-ink-400">{{ $history->created_at->format('H:i') }} WIB</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="font-semibold text-ink-900">{{ $history->status_label }}</span>
                                                @if ($history->created_by)
                                                    <span class="mt-0.5 block text-[0.65rem] text-ink-400">oleh {{ $history->created_by }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-xs leading-relaxed text-ink-600">{{ $history->note ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-6 py-10 text-center text-sm text-ink-400">Belum ada riwayat.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ============== RINGKASAN PERMINTAAN ============== --}}
                <div class="space-y-6 lg:col-span-5" data-aos="fade-up" data-aos-delay="80">

                    <div class="rounded-3xl border border-ink-100 bg-white p-7 shadow-card">
                        <h2 class="font-display text-base font-bold text-ink-900">Detail Permintaan</h2>

                        <dl class="mt-5 space-y-4">
                            @foreach ([
                                'Nomor Tracking' => $quotation->tracking_number,
                                'Nama Pelanggan' => $quotation->name,
                                'Perusahaan' => $quotation->company ?: '-',
                                'Email' => $quotation->masked_email,
                                'WhatsApp' => $quotation->masked_whatsapp,
                                'Printer' => $quotation->printer_summary,
                                'Jumlah Model' => $quotation->model_count.' model · 1 mesin per model',
                                'Total Jumlah Cetak' => $quotation->quantity.' unit',
                                'Tanggal Pengajuan' => $quotation->created_at->translatedFormat('d F Y, H:i').' WIB',
                            ] as $label => $value)
                                <div class="flex items-start justify-between gap-4 border-b border-ink-100 pb-4 last:border-0 last:pb-0">
                                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $label }}</dt>
                                    <dd class="max-w-[58%] break-words text-right text-sm font-semibold text-ink-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <p class="mt-5 rounded-xl bg-ink-50 p-4 text-[0.7rem] leading-relaxed text-ink-500">
                            Email dan nomor WhatsApp sengaja disamarkan pada halaman ini. Data lengkapnya tersedia
                            di dokumen PDF yang dapat Anda unduh.
                        </p>
                    </div>

                    {{-- ============== MODEL DALAM PERMINTAAN INI ============== --}}
                    <div class="rounded-3xl border border-ink-100 bg-white p-7 shadow-card">
                        <h2 class="font-display text-base font-bold text-ink-900">Model yang Dipesan</h2>
                        <p class="mt-1 text-xs text-ink-400">
                            Setiap model dicetak pada mesinnya sendiri. Seluruhnya termasuk dalam satu
                            Nomor Tracking yang sama.
                        </p>

                        <ul class="mt-5 space-y-4">
                            @foreach ($quotation->items as $item)
                                <li class="rounded-2xl border border-ink-100 bg-ink-50/60 p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="flex items-center gap-2">
                                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-600 text-[0.65rem] font-bold text-white">
                                                    {{ $item->position }}
                                                </span>
                                                <span class="break-all font-display text-sm font-bold text-ink-900">{{ $item->file_name }}</span>
                                            </p>
                                            <p class="mt-1.5 pl-8 text-xs font-semibold text-brand-600">{{ $item->printer_label }}</p>
                                            <p class="mt-1 pl-8 text-xs text-ink-500">
                                                {{ $item->file_format }} &middot; {{ $item->technology }} {{ $item->material_label }} &middot;
                                                {{ $item->resolution_label }} &middot; {{ $item->quantity }} unit
                                            </p>
                                            <p class="mt-1 pl-8 text-[0.7rem] text-ink-400">
                                                Infill {{ $item->infill_label }} &middot; skala {{ $item->scale_label }}
                                                &middot; warna {{ $item->material_color_label }}
                                                &middot; finishing {{ $item->finishing_label }}
                                                @if ($item->hollow_enabled)
                                                    &middot; <span class="font-semibold text-brand-600">hollow {{ number_format((float) $item->hollow_wall_thickness_mm, 1, ',', '.') }} mm</span>
                                                @endif
                                            </p>
                                        </div>

                                        <div class="text-right">
                                            <p class="font-display text-sm font-bold text-brand-700">
                                                Rp{{ number_format((float) $item->display_price, 0, ',', '.') }}
                                            </p>
                                            <p class="text-[0.65rem] text-ink-400">
                                                {{ number_format($item->total_weight_g * $item->quantity, 1, ',', '.') }} gram
                                            </p>
                                        </div>
                                    </div>

                                    @if ($item->admin_note)
                                        <p class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
                                            <span class="font-bold">Catatan tim kami:</span> {{ $item->admin_note }}
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Estimasi --}}
                    <div class="rounded-3xl border border-ink-100 bg-white p-7 shadow-card">
                        <h2 class="font-display text-base font-bold text-ink-900">Estimasi</h2>

                        <div class="mt-5 rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-5 text-white">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-white/70">
                                {{ $quotation->estimated_price !== null ? 'Harga Penawaran' : 'Estimasi Biaya Sistem' }}
                            </p>
                            <p class="mt-1.5 font-display text-2xl font-bold">
                                @if ($quotation->display_price !== null)
                                    Rp{{ number_format($quotation->display_price, 0, ',', '.') }}
                                @else
                                    Belum tersedia
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-white/70">
                                untuk {{ $quotation->model_count }} model &middot; {{ $quotation->quantity }} unit
                            </p>
                        </div>


                        <dl class="mt-5 space-y-4">
                            <div class="flex items-start justify-between gap-4 border-b border-ink-100 pb-4">
                                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Estimasi Lead Time</dt>
                                <dd class="text-right text-sm font-semibold text-ink-800">{{ $quotation->lead_time ?? 'Belum tersedia' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Estimasi Penyelesaian</dt>
                                <dd class="text-right text-sm font-semibold text-ink-800">
                                    {{ $quotation->estimated_finish?->translatedFormat('d F Y') ?? 'Belum tersedia' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Foto proses --}}
                    @if ($quotation->production_photo || $quotation->result_photo)
                        <div class="rounded-3xl border border-ink-100 bg-white p-7 shadow-card">
                            <h2 class="font-display text-base font-bold text-ink-900">Foto Proses</h2>

                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                @foreach ([
                                    ['path' => $quotation->production_photo, 'label' => 'Proses Produksi'],
                                    ['path' => $quotation->result_photo, 'label' => 'Hasil Akhir'],
                                ] as $photo)
                                    @if ($photo['path'])
                                        <figure>
                                            <div class="overflow-hidden rounded-2xl border border-ink-100 bg-ink-50">
                                                <img src="{{ Storage::disk('public')->url($photo['path']) }}"
                                                     alt="{{ $photo['label'] }} untuk {{ $quotation->tracking_number }}"
                                                     loading="lazy"
                                                     class="aspect-[4/3] w-full object-cover">
                                            </div>
                                            <figcaption class="mt-2 text-center text-[0.7rem] font-semibold text-ink-500">{{ $photo['label'] }}</figcaption>
                                        </figure>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Bantuan --}}
                    <div class="rounded-3xl border border-ink-100 bg-ink-50/70 p-7">
                        <h2 class="font-display text-sm font-bold text-ink-900">Ada yang ingin ditanyakan?</h2>
                        <p class="mt-2 text-xs leading-relaxed text-ink-500">
                            Sebutkan nomor tracking <strong class="font-semibold text-ink-700">{{ $quotation->tracking_number }}</strong>
                            saat menghubungi kami agar tim dapat langsung membuka permintaan Anda.
                        </p>

                        <div class="mt-4 flex flex-col gap-2">
                            @if ($company->whatsapp_link)
                                <a href="{{ $company->whatsapp_link }}" target="_blank" rel="noopener noreferrer" class="viewer-tool justify-center">
                                    <x-icons.whatsapp class="h-4 w-4" />
                                    Hubungi via WhatsApp
                                </a>
                            @endif
                            <a href="mailto:{{ $company->email }}?subject=Tracking%20{{ $quotation->tracking_number }}" class="viewer-tool justify-center">
                                <x-icons.mail class="h-4 w-4" />
                                Kirim Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
