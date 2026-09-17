@extends('superadmin.price-list.layout')

@section('title', 'Price List · Machine Cost')
@section('price-list-group', 'Machine Cost')
@section('price-list-page', 'Machine Cost')

@section('price-list')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    {{-- ================= Machine Cost ================= --}}
    <section>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h3 class="font-display text-lg font-bold text-ink-900">Machine Cost</h3>
            <a href="{{ route('superadmin.price-list.machine-cost.create') }}" class="btn-primary">Tambah Mesin</a>
        </div>

        <form method="GET" class="mt-4 flex flex-wrap gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
            <div class="min-w-[240px] flex-1">
                <label for="machine_q" class="field-label">Cari Mesin</label>
                <input type="search" id="machine_q" name="machine_q" value="{{ $search }}" placeholder="Nama mesin" class="field-input">
            </div>
            <div class="flex items-end"><button type="submit" class="btn-outline px-6 py-3">Cari</button></div>
        </form>

        <div class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-4 py-4 font-bold">No</th>
                            <th scope="col" class="px-4 py-4 font-bold">Mesin</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Watt (KWH)</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Harga Listrik</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Depresiasi</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Listrik/Hour</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Machine Cost</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Pembulatan</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    {{-- Mesin dikelompokkan di bawah teknologinya, dan tiap
                         baris punya tombol + yang membuka spesifikasi fisiknya
                         pada baris tersembunyi tepat di bawahnya. Kolom harga
                         yang sudah ada tidak berubah sedikit pun.

                         Isi detailnya datang dari accessor `spec_rows` pada
                         App\Models\MachineCost — tidak ada angka yang ditulis
                         di berkas ini. JS-nya: initMachineDetails() di
                         resources/js/dashboard.js. --}}
                    @php $nomor = $machineCosts->firstItem(); @endphp

                    <tbody class="divide-y divide-ink-100">
                        @forelse ($machineCosts->getCollection()->groupBy(fn ($machine) => $machine->technology?->code ?? '') as $rows)
                            @php $technology = $rows->first()->technology; @endphp

                            <tr class="bg-ink-50/70">
                                <th colspan="9" scope="colgroup" class="px-4 py-2.5 text-left">
                                    <span class="text-[0.7rem] font-bold uppercase tracking-[0.14em] text-ink-700">
                                        {{ $technology?->code ?? 'Tanpa Teknologi' }}
                                    </span>
                                    <span class="ml-2 text-[0.65rem] font-medium normal-case tracking-normal text-ink-400">
                                        {{ $technology?->name ?? 'Belum ditentukan teknologinya' }}
                                        &middot; {{ $rows->count() }} mesin
                                    </span>
                                </th>
                            </tr>

                            @foreach ($rows as $machine)
                                <tr class="transition-colors hover:bg-brand-50/40">
                                    <td class="px-4 py-3 text-ink-500">{{ $nomor++ }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    data-machine-toggle="{{ $machine->id }}"
                                                    aria-expanded="false"
                                                    aria-controls="machine-detail-{{ $machine->id }}"
                                                    aria-label="Buka detail mesin {{ $machine->mesin }}"
                                                    title="Lihat detail mesin"
                                                    class="machine-toggle">
                                                <span data-machine-toggle-icon aria-hidden="true">+</span>
                                            </button>
                                            <span class="font-semibold text-ink-900">{{ $machine->mesin }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ number_format((float) $machine->watt_kwh, 3, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($machine->harga_listrik) }}</td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($machine->depresiasi) }}</td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($machine->electricity_per_hour) }}</td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($machine->machine_cost) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-brand-700">{{ $rupiah($machine->rounded_machine_cost) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('superadmin.price-list.machine-cost.edit', $machine) }}" class="viewer-tool">Ubah</a>
                                            <form method="POST" action="{{ route('superadmin.price-list.machine-cost.destroy', $machine) }}"
                                                  onsubmit="return confirm('Hapus mesin {{ $machine->mesin }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <tr id="machine-detail-{{ $machine->id }}" data-machine-detail="{{ $machine->id }}" class="hidden">
                                    <td colspan="9" class="bg-ink-50/50 px-4 py-4">
                                        <div class="max-w-md rounded-xl border border-ink-100 bg-white p-4">
                                            <table class="w-full text-left text-sm">
                                                <caption class="sr-only">Detail mesin {{ $machine->mesin }}</caption>
                                                <thead>
                                                    <tr class="border-b border-ink-100 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                                                        <th scope="col" class="py-2 font-bold">Bagian</th>
                                                        <th scope="col" class="py-2 text-right font-bold">Ukuran</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-ink-100">
                                                    @foreach ($machine->spec_rows as $spec)
                                                        <tr>
                                                            <th scope="row" class="py-2 font-medium text-ink-600">{{ $spec['label'] }}</th>
                                                            <td class="py-2 text-right font-semibold text-ink-900">{{ $spec['value'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>

                                            @unless ($machine->hasSpecs())
                                                <p class="mt-3 border-t border-ink-100 pt-3 text-xs text-ink-400">
                                                    Detail mesin belum diisi.
                                                    <a href="{{ route('superadmin.price-list.machine-cost.edit', $machine) }}"
                                                       class="font-semibold text-brand-600 hover:text-brand-800">Lengkapi sekarang</a>.
                                                </p>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="9" class="px-4 py-12 text-center text-ink-400">Belum ada data Machine Cost.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($machineCosts->hasPages())
            <div class="mt-4">{{ $machineCosts->links() }}</div>
        @endif
    </section>
@endsection
