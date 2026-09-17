@extends('superadmin.price-list.layout')

@section('title', 'Price List · Packaging')
@section('price-list-group', 'Harga')
@section('price-list-page', 'Packaging')

@section('price-list')
    <a href="{{ route('superadmin.price-list.harga') }}"
       class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Rumus Harga Otomatis
    </a>

    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    {{-- ================= Packaging ================= --}}
    <section>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h3 class="font-display text-lg font-bold text-ink-900">Packaging</h3>
            <a href="{{ route('superadmin.price-list.packaging.create') }}" class="btn-primary">Tambah Packaging</a>
        </div>

        <form method="GET" class="mt-4 flex flex-wrap gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
            <div class="min-w-[240px] flex-1">
                <label for="packaging_q" class="field-label">Cari Packaging</label>
                <input type="search" id="packaging_q" name="packaging_q" value="{{ $search }}" placeholder="Item, ukuran, atau dimensi" class="field-input">
            </div>
            <div class="flex items-end"><button type="submit" class="btn-outline px-6 py-3">Cari</button></div>
        </form>

        <div class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-4 py-4 font-bold">No</th>
                            <th scope="col" class="px-4 py-4 font-bold">Item</th>
                            <th scope="col" class="px-4 py-4 font-bold">Ukuran</th>
                            <th scope="col" class="px-4 py-4 font-bold">Dimensi</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Harga</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($packaging as $index => $item)
                            <tr class="transition-colors hover:bg-brand-50/40">
                                <td class="px-4 py-3 text-ink-500">{{ $packaging->firstItem() + $index }}</td>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $item->item }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $item->ukuran ?: '-' }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $item->dimensi ?: '-' }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-ink-800">{{ $item->formatted_price }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('superadmin.price-list.packaging.edit', $item) }}" class="viewer-tool">Ubah</a>
                                        <form method="POST" action="{{ route('superadmin.price-list.packaging.destroy', $item) }}"
                                              onsubmit="return confirm('Hapus packaging {{ $item->item }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-ink-400">Belum ada data packaging.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($packaging->hasPages())
            <div class="mt-4">{{ $packaging->links() }}</div>
        @endif
    </section>
@endsection
