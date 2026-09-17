@extends('layouts.dashboard')

@section('title', 'Penawaran Saya')

@section('content')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Penawaran Saya</h2>
            <p class="mt-2 text-sm text-ink-500">Seluruh penawaran yang pernah Anda kirim beserta status terkininya.</p>
        </div>

        <a href="{{ route('models') }}" class="btn-primary px-5 py-2.5">
            Buat Penawaran Baru
            <x-icons.arrow-right class="h-4 w-4" />
        </a>
    </div>

    <form method="GET" class="mt-8 grid gap-3 rounded-2xl border border-ink-100 bg-white p-5 shadow-card sm:grid-cols-[2fr_1fr_auto]">
        <div>
            <label for="q" class="field-label">Cari</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nomor penawaran atau nama file" class="field-input">
        </div>

        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field-input">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="btn-primary w-full px-6 py-3 sm:w-auto">Filter</button>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="overflow-x-auto">
            {{-- Kolom Total Berat sengaja tidak ada: pelanggan tidak melihat berat
                 model. Angkanya tetap tersimpan dan tetap terlihat oleh admin. --}}
            <table class="w-full min-w-[880px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                        <th scope="col" class="px-5 py-4 font-bold">Nomor Penawaran</th>
                        <th scope="col" class="px-5 py-4 font-bold">Tanggal</th>
                        <th scope="col" class="px-5 py-4 font-bold">Jumlah File</th>
                        <th scope="col" class="px-5 py-4 font-bold">Total Estimasi Biaya</th>
                        <th scope="col" class="px-5 py-4 font-bold">Status</th>
                        <th scope="col" class="px-5 py-4 font-bold">Tracking</th>
                        <th scope="col" class="px-5 py-4 text-right font-bold">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-100">
                    @forelse ($quotations as $quotation)
                        <tr class="transition-colors hover:bg-brand-50/40">
                            <td class="px-5 py-4">
                                <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="font-mono text-xs font-bold text-brand-600 hover:text-brand-700">
                                    {{ $quotation->tracking_number }}
                                </a>
                                @if ($quotation->isEditable())
                                    <span class="mt-1 block text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-emerald-600">Masih dapat diubah</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs text-ink-500">
                                {{ $quotation->created_at->translatedFormat('d M Y') }}<br>
                                <span class="text-ink-400">{{ $quotation->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-5 py-4 font-semibold text-ink-800">{{ $quotation->items_count }} file</td>
                            <td class="px-5 py-4 font-semibold text-ink-900">{{ harga_penawaran($quotation->display_price) }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full border border-ink-200 bg-ink-50 px-3 py-1 text-xs font-semibold text-ink-600">
                                    {{ $quotation->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('tracking.show', $quotation->tracking_number) }}" target="_blank" rel="noopener noreferrer"
                                   class="text-xs font-semibold text-brand-600 hover:text-brand-700">
                                    Buka Tracking &nearr;
                                </a>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($quotation->isEditable())
                                        <a href="{{ route('dashboard.quotations.edit', $quotation) }}" class="viewer-tool">Ubah</a>
                                    @endif
                                    <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="viewer-tool">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="font-semibold text-ink-700">Belum ada penawaran.</p>
                                <p class="mt-1.5 text-sm text-ink-400">Unggah model 3D Anda di halaman 3D Models untuk membuat penawaran pertama.</p>
                                <a href="{{ route('models') }}" class="btn-primary mt-5">Buka 3D Models</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($quotations->hasPages())
        <div class="mt-6">{{ $quotations->links() }}</div>
    @endif
@endsection
