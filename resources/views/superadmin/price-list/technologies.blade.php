@extends('superadmin.price-list.layout')

@section('title', 'Price List · Teknologi')
@section('price-list-group', 'Teknologi')
@section('price-list-page', 'Teknologi')

@section('price-list')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    {{-- ================= TEKNOLOGI ================= --}}
    <section>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h3 class="font-display text-lg font-bold text-ink-900">Teknologi Cetak</h3>
                <p class="mt-1.5 text-sm text-ink-500">
                    Menambah teknologi di sini langsung memunculkan halaman materialnya sendiri di menu Teknologi &amp; Material,
                    pilihan Technology pada Edit Specification, dan baris parameternya pada halaman Rumus Harga Otomatis.
                </p>
            </div>

            <a href="{{ route('superadmin.price-list.technologies.create') }}" class="btn-primary">Tambah Teknologi</a>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-4 py-4 font-bold">Kode</th>
                            <th scope="col" class="px-4 py-4 font-bold">Nama</th>
                            <th scope="col" class="px-4 py-4 font-bold">Keluarga</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Material</th>
                            <th scope="col" class="px-4 py-4 font-bold">Area Cetak</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Tarif Mesin</th>
                            <th scope="col" class="px-4 py-4 font-bold">Hollow</th>
                            <th scope="col" class="px-4 py-4 font-bold">Status</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @forelse ($technologies as $technology)
                            <tr class="transition-colors hover:bg-brand-50/40 {{ $technology->is_active ? '' : 'bg-ink-50/60' }}">
                                <td class="px-4 py-3 font-mono font-bold {{ $technology->is_active ? 'text-brand-700' : 'text-ink-400' }}">{{ $technology->code }}</td>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $technology->name }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $technology->family ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($technology->materials_count > 0)
                                        <span class="font-semibold text-ink-800">{{ $technology->materials_count }}</span>
                                    @else
                                        {{-- Tanpa material, teknologinya tidak dapat dipilih pelanggan. --}}
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-amber-800">
                                            Belum ada
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-600">
                                    {{ $technology->build_volume_x }} × {{ $technology->build_volume_y }} × {{ $technology->build_volume_z }} mm
                                </td>
                                <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($technology->machine_rate_per_hour) }}/jam</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em]',
                                        'bg-emerald-100 text-emerald-800' => $technology->allows_hollow,
                                        'bg-ink-100 text-ink-500' => ! $technology->allows_hollow,
                                    ])>
                                        {{ $technology->allows_hollow ? 'Ya' : 'Tidak' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    {{-- Switch Status: aktif = tampil di Edit Specification.
                                         Formulir terkirim begitu switch diubah. --}}
                                    <form method="POST" action="{{ route('superadmin.price-list.technologies.status', $technology) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="0">

                                        <label class="inline-flex cursor-pointer items-center gap-2.5"
                                               title="{{ $technology->is_active ? 'Aktif: tampil di Edit Specification' : 'Nonaktif: tidak tampil di Edit Specification' }}">
                                            <input type="checkbox" role="switch" name="is_active" value="1"
                                                   class="peer sr-only" data-auto-submit
                                                   aria-label="Status teknologi {{ $technology->code }}"
                                                   @checked($technology->is_active)>
                                            <span class="relative h-6 w-11 shrink-0 rounded-full bg-ink-200 transition-colors
                                                         peer-checked:bg-brand-600 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-600
                                                         after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform
                                                         peer-checked:after:translate-x-5"></span>
                                            <span class="w-16 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-ink-400 peer-checked:hidden">Nonaktif</span>
                                            <span class="hidden w-16 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-emerald-700 peer-checked:inline">Aktif</span>
                                        </label>
                                    </form>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('superadmin.price-list.technologies.edit', $technology) }}" class="viewer-tool">Ubah</a>

                                        @if ($technology->isInUse())
                                            {{-- Penawaran lama menunjuk teknologinya lewat kode, jadi
                                                 tombolnya dimatikan — penjaganya juga ada di controller. --}}
                                            <span class="viewer-tool cursor-not-allowed opacity-50"
                                                  title="Sudah dipakai penawaran, tidak dapat dihapus">Hapus</span>
                                        @else
                                            <form method="POST" action="{{ route('superadmin.price-list.technologies.destroy', $technology) }}"
                                                  onsubmit="return confirm('Hapus teknologi {{ $technology->code }} beserta {{ $technology->materials_count }} materialnya? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-ink-400">
                                    Belum ada teknologi.
                                    <a href="{{ route('superadmin.price-list.technologies.create') }}" class="font-semibold text-brand-600 hover:text-brand-700">Tambahkan sekarang</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
