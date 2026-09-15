@extends('layouts.dashboard')

@section('title', 'Permintaan Penawaran')

@section('content')
    <div>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Permintaan Penawaran</h2>
                <p class="mt-2 text-sm text-ink-500">Seluruh permintaan yang dikirim melalui halaman 3D Models.</p>
            </div>

            @if ($pendingCancellations > 0)
                <a href="{{ staff_route('quotations.index', ['status' => \App\Support\QuotationStatus::CANCELLATION_REQUESTED]) }}"
                   class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-800 transition-colors hover:bg-amber-100">
                    <x-icons.alert class="h-4 w-4" />
                    {{ $pendingCancellations }} permintaan pembatalan menunggu persetujuan
                </a>
            @endif
        </div>

        {{-- Ringkasan --}}
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'Total Permintaan', 'value' => number_format($summary['total'], 0, ',', '.')],
                ['label' => 'Belum Ditindaklanjuti', 'value' => number_format($summary['new'], 0, ',', '.')],
                ['label' => 'Model Ready to Print', 'value' => number_format($summary['ready'], 0, ',', '.').' / '.number_format($summary['models'], 0, ',', '.')],
                ['label' => 'Nilai Estimasi', 'value' => 'Rp'.number_format((float) $summary['value'], 0, ',', '.')],
            ] as $card)
                <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $card['label'] }}</p>
                    <p class="mt-2 font-display text-2xl font-bold text-ink-900">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Filter --}}
        <form method="GET" class="mt-8 grid gap-3 rounded-2xl border border-ink-100 bg-white p-5 shadow-card sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label for="q" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Cari</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nama, email, perusahaan, nama file, atau referensi"
                       class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
            </div>

            <div>
                <label for="status" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Status</label>
                <select id="status" name="status" class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="technology" class="block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Teknologi</label>
                <div class="mt-2 flex gap-2">
                    <select id="technology" name="technology" class="w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none">
                        <option value="">Semua</option>
                        @foreach ($technologies as $code)
                            <option value="{{ $code }}" @selected($filters['technology'] === $code)>{{ $code }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary shrink-0 px-5 py-2.5">Filter</button>
                </div>
            </div>
        </form>

        {{-- Tabel --}}
        <div class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-5 py-4 font-bold">Pelanggan</th>
                            <th scope="col" class="px-5 py-4 font-bold">Kontak</th>
                            <th scope="col" class="px-5 py-4 font-bold">File</th>
                            <th scope="col" class="px-5 py-4 font-bold">Teknologi &amp; Material</th>
                            <th scope="col" class="px-5 py-4 font-bold">Analisis</th>
                            <th scope="col" class="px-5 py-4 font-bold">Estimasi</th>
                            <th scope="col" class="px-5 py-4 font-bold">Status</th>
                            <th scope="col" class="px-5 py-4 font-bold">Tanggal</th>
                            <th scope="col" class="px-5 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($quotations as $quotation)
                            <tr class="transition-colors hover:bg-brand-50/40">
                                <td class="px-5 py-4">
                                    @if ($quotation->user_id)
                                        <a href="{{ route('superadmin.users.show', $quotation->user_id) }}" class="font-semibold text-ink-900 transition-colors hover:text-brand-600">
                                            {{ $quotation->name }}
                                        </a>
                                    @else
                                        <p class="font-semibold text-ink-900">{{ $quotation->name }}</p>
                                    @endif
                                    <p class="text-xs text-ink-400">{{ $quotation->company ?: '-' }}</p>
                                    <p class="mt-1 font-mono text-[0.65rem] text-brand-600">{{ $quotation->tracking_number }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="mailto:{{ $quotation->email }}" class="block text-ink-700 transition-colors hover:text-brand-600">{{ $quotation->email }}</a>
                                    <span class="text-xs text-ink-400">{{ $quotation->whatsapp }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="max-w-[180px] truncate font-medium text-ink-700" title="{{ $quotation->file_name }}">{{ $quotation->file_name }}</p>
                                    <p class="text-xs text-ink-400">{{ $quotation->file_format }} &middot; {{ number_format($quotation->file_size / 1024, 0, ',', '.') }} KB</p>

                                    @if ($quotation->hasMultipleModels())
                                        <p class="mt-1 inline-block rounded-full border border-brand-200 bg-brand-50 px-2 py-0.5 text-[0.6rem] font-bold text-brand-700">
                                            +{{ $quotation->model_count - 1 }} model lain
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-ink-900">{{ $quotation->technology }}</p>
                                    <p class="text-xs text-ink-400">{{ $quotation->material }} &middot; {{ $quotation->quantity }} unit</p>

                                    @if ($quotation->hasMultipleModels())
                                        <p class="text-[0.65rem] text-ink-400">pilihan model pertama</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <x-admin.analysis-badge :status="$quotation->analysis_status" />
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-ink-900">Rp{{ number_format((float) $quotation->estimated_cost, 0, ',', '.') }}</p>
                                    <p class="text-xs text-ink-400">{{ $quotation->estimated_duration ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full border border-ink-200 bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">
                                        {{ $quotation->status_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs text-ink-500">
                                    {{ $quotation->created_at->translatedFormat('d M Y') }}<br>
                                    <span class="text-ink-400">{{ $quotation->created_at->format('H:i') }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ staff_route('quotations.show', $quotation) }}" class="viewer-tool">Detail</a>
                                        @if ($quotation->fileExists())
                                            <a href="{{ staff_route('quotations.download', $quotation) }}" class="viewer-tool">Unduh</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-16 text-center">
                                    <p class="font-semibold text-ink-700">Belum ada permintaan penawaran.</p>
                                    <p class="mt-1.5 text-sm text-ink-400">Permintaan akan muncul di sini setelah pengunjung mengirimkannya dari halaman 3D Models.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($quotations->hasPages())
            <div class="mt-6">
                {{ $quotations->links() }}
            </div>
        @endif
    </div>
@endsection
