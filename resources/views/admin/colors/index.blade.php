@extends('layouts.dashboard')

@section('title', 'Color')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-ink-900">Color</h1>
            <p class="mt-1 text-sm text-ink-500">
                Warna material yang tersedia untuk pelanggan pada Edit Specification.
            </p>
        </div>

        @can(\App\Support\AdminPermission::COLOR_CREATE)
            <a href="{{ staff_route('colors.create') }}" class="btn-primary">Tambah Warna</a>
        @endcan
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <form method="GET" class="flex flex-wrap items-end gap-3 border-b border-ink-100 px-6 py-5">
            <div class="min-w-[220px] flex-1">
                <label for="q" class="field-label">Cari</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="field-input"
                       placeholder="Nama warna atau kode hexa">
            </div>

            <button type="submit" class="btn-primary">Cari</button>
            <a href="{{ staff_route('colors.index') }}" class="viewer-tool">Reset</a>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                        <th scope="col" class="px-6 py-4 font-bold">Warna</th>
                        <th scope="col" class="px-6 py-4 font-bold">Nama Warna</th>
                        <th scope="col" class="px-6 py-4 font-bold">Kode Hexa</th>
                        <th scope="col" class="px-6 py-4 font-bold">Dipakai</th>
                        <th scope="col" class="px-6 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-ink-100">
                    @forelse ($colors as $color)
                        @php $used = $usage[$color->key] ?? 0; @endphp

                        <tr>
                            <td class="px-6 py-4">
                                {{-- Contoh warnanya sendiri: satu-satunya cara memastikan
                                     kode hexa yang tersimpan memang warna yang dimaksud. --}}
                                <span class="inline-block h-9 w-14 rounded-lg border border-ink-200 shadow-inner"
                                      style="background-color: {{ $color->hex }}"
                                      title="{{ $color->label }} {{ $color->hex }}"></span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-ink-900">{{ $color->label }}</td>
                            <td class="px-6 py-4 font-mono text-ink-600">{{ $color->hex }}</td>
                            <td class="px-6 py-4 text-ink-600">
                                {{ $used > 0 ? $used.' model' : '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    @can(\App\Support\AdminPermission::COLOR_EDIT)
                                        <a href="{{ staff_route('colors.edit', $color) }}" class="viewer-tool">Ubah</a>
                                    @endcan

                                    @can(\App\Support\AdminPermission::COLOR_DELETE)
                                        {{-- Warna yang sedang dipakai penawaran tidak dapat
                                             dihapus; controller menolaknya juga. --}}
                                        @if ($used > 0)
                                            <span class="viewer-tool cursor-not-allowed opacity-40"
                                                  title="Sedang dipakai {{ $used }} model penawaran">Hapus</span>
                                        @else
                                            <form method="POST" action="{{ staff_route('colors.destroy', $color) }}"
                                                  onsubmit="return confirm('Hapus warna {{ $color->label }}? Warna ini tidak akan muncul lagi sebagai pilihan pelanggan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-ink-400">
                                {{ $search === '' ? 'Belum ada warna.' : 'Tidak ada warna yang cocok dengan pencarian itu.' }}

                                @can(\App\Support\AdminPermission::COLOR_CREATE)
                                    <a href="{{ staff_route('colors.create') }}" class="font-semibold text-brand-600 hover:text-brand-700">Tambah Warna</a>.
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($colors->hasPages())
            <div class="border-t border-ink-100 px-6 py-4">{{ $colors->links() }}</div>
        @endif
    </section>
@endsection
