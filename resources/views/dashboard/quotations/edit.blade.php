@extends('layouts.dashboard')

@section('title', 'Ubah Penawaran '.$quotation->tracking_number)

@section('content')
    @php
        $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
        $angka = fn ($value, $digits = 2) => is_numeric($value) ? number_format((float) $value, $digits, ',', '.') : '-';
        $itemCount = $quotation->items->count();
    @endphp

    <a href="{{ route('dashboard.quotations.show', $quotation) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke detail penawaran
    </a>

    <div class="mt-5">
        <p class="font-mono text-sm font-semibold text-brand-600">{{ $quotation->tracking_number }}</p>
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Ubah Penawaran</h2>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-ink-500">
            Selama status masih <span class="font-semibold text-ink-700">"File Sedang Direview"</span>, Anda dapat menambah
            atau menghapus file 3D, mengubah pengaturan printing, dan mengganti jumlah cetaknya. Estimasi biaya dihitung
            ulang otomatis setiap kali perubahan disimpan.
        </p>
    </div>

    {{-- ================= TAMBAH FILE ================= --}}
    <section class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h3 class="font-display text-base font-bold text-ink-900">Tambah File 3D</h3>
                <p class="mt-1 text-sm text-ink-500">
                    Format .STL atau .OBJ, maksimal {{ $maxFileMb }} MB per file dan {{ $maxModels }} file dalam satu penawaran.
                </p>
            </div>

            <span class="rounded-full border border-brand-200 bg-brand-50 px-4 py-1.5 text-xs font-bold text-brand-700">
                {{ $itemCount }}/{{ $maxModels }} file
            </span>
        </div>

        @if ($itemCount >= $maxModels)
            <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                Batas {{ $maxModels }} file per penawaran sudah tercapai. Hapus salah satu file lebih dulu untuk menambah yang baru.
            </p>
        @else
            <form method="POST" action="{{ route('dashboard.quotations.items.store', $quotation) }}" enctype="multipart/form-data"
                  class="mt-5 flex flex-wrap items-end gap-3">
                @csrf

                <div class="min-w-[240px] flex-1">
                    <label for="model" class="field-label">Pilih File</label>
                    <input type="file" id="model" name="model" accept="{{ \App\Support\ModelFormat::accept() }}" required
                           class="field-input file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white">
                    @error('model') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primary px-6 py-3">
                    <x-icons.upload class="h-4 w-4" />
                    Tambahkan
                </button>
            </form>

            <p class="mt-3 text-xs leading-relaxed text-ink-400">
                Volume, dimensi, dan luas permukaan file yang ditambahkan dari sini diukur di server. Untuk pratinjau 3D
                beserta analisis kelayakan cetak yang lengkap, gunakan halaman
                <a href="{{ route('models') }}" class="font-semibold text-brand-600 hover:text-brand-700">3D Models</a>.
            </p>
        @endif
    </section>

    {{-- ================= DAFTAR FILE ================= --}}
    <div class="mt-6 space-y-6">
        @foreach ($quotation->items as $item)
            <section class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 bg-ink-50/60 px-6 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                            <x-icons.printer class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-display text-sm font-bold text-ink-900">{{ $item->file_name }}</p>
                            <p class="text-xs text-ink-500">
                                File #{{ $item->position }} &middot; {{ $item->file_format }} &middot;
                                {{ number_format($item->file_size / 1024, 0, ',', '.') }} KB
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full border border-ink-200 bg-white px-3 py-1 text-xs font-bold text-brand-700">
                            {{ harga_penawaran($item->display_price) }}
                        </span>

                        @if ($itemCount > 1)
                            <form method="POST" action="{{ route('dashboard.quotations.items.destroy', [$quotation, $item]) }}"
                                  onsubmit="return confirm('Hapus file {{ $item->file_name }} dari penawaran ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="viewer-tool border-brand-200 text-brand-700 hover:border-brand-600 hover:bg-brand-50">
                                    <x-icons.trash class="h-4 w-4" />
                                    Hapus
                                </button>
                            </form>
                        @endif
                    </div>
                </header>

                <form method="POST" action="{{ route('dashboard.quotations.items.update', [$quotation, $item]) }}" class="p-6" data-item-form>
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <label for="quantity-{{ $item->id }}" class="field-label">Jumlah Cetak</label>
                            <input type="number" id="quantity-{{ $item->id }}" name="quantity" min="1" max="10000" required
                                   value="{{ old('quantity', $item->quantity) }}" class="field-input">
                        </div>

                        <div>
                            <label for="technology-{{ $item->id }}" class="field-label">Teknologi</label>
                            <select id="technology-{{ $item->id }}" name="technology" class="field-input" data-technology-select>
                                @foreach ($technologies as $code => $config)
                                    <option value="{{ $code }}" @selected($item->technology === $code)>{{ $code }} ({{ $config['name'] }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="material-{{ $item->id }}" class="field-label">Material</label>
                            <select id="material-{{ $item->id }}" name="material" class="field-input" data-material-select>
                                @foreach ($technologies as $code => $config)
                                    {{-- Nilai option tetap nama katalog beserta brand-nya: itulah
                                         yang divalidasi server dan menentukan harga. Yang dibaca
                                         pelanggan hanya nama jenis bahannya. --}}
                                    @foreach ($config['materials'] as $material => $label)
                                        <option value="{{ $material }}"
                                                data-technology="{{ $code }}"
                                                @selected($item->technology === $code && $item->material === $material)
                                                @if ($item->technology !== $code) hidden @endif>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="printer-{{ $item->id }}" class="field-label">Printer</label>
                            <select id="printer-{{ $item->id }}" name="printer" class="field-input">
                                @foreach ($printers as $key => $printer)
                                    <option value="{{ $key }}" @selected($item->printer === $key)>{{ $printer['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="resolution-{{ $item->id }}" class="field-label">Resolusi</label>
                            <select id="resolution-{{ $item->id }}" name="resolution" class="field-input">
                                @foreach ($resolutions as $key => $resolution)
                                    <option value="{{ $key }}" @selected($item->resolution === $key)>
                                        {{ number_format($resolution['layer_height'], 2, ',', '.') }} mm ({{ $resolution['name'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="scale-{{ $item->id }}" class="field-label">Skala (%)</label>
                            <input type="number" id="scale-{{ $item->id }}" name="scale_percent" min="10" max="400" step="1"
                                   value="{{ old('scale_percent', (int) round((float) $item->scale_percent)) }}" class="field-input">
                        </div>

                        <div>
                            <label for="infill-{{ $item->id }}" class="field-label">Kepadatan Infill</label>
                            <select id="infill-{{ $item->id }}" name="infill_density" class="field-input">
                                @foreach ($infillDensities as $density)
                                    <option value="{{ $density }}" @selected(abs((float) $item->infill_density - $density) < 0.001)>
                                        {{ (int) round($density * 100) }}%
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="pattern-{{ $item->id }}" class="field-label">Pola Infill</label>
                            <select id="pattern-{{ $item->id }}" name="infill_pattern" class="field-input">
                                @foreach ($infillPatterns as $key => $pattern)
                                    <option value="{{ $key }}" @selected($item->infill_pattern === $key)>{{ $pattern['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="color-{{ $item->id }}" class="field-label">Warna Material</label>
                            <select id="color-{{ $item->id }}" name="material_color" class="field-input">
                                @foreach ($materialColors as $key => $color)
                                    <option value="{{ $key }}" @selected($item->material_color === $key)>{{ $color['label'] }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-[0.7rem] text-ink-400">
                                Warna yang tidak tersedia pada material terpilih otomatis diganti yang terdekat.
                            </p>
                        </div>

                        <div>
                            <label for="finishing-{{ $item->id }}" class="field-label">Finishing</label>
                            <select id="finishing-{{ $item->id }}" name="finishing" class="field-input">
                                @foreach ($finishings as $key => $finishing)
                                    <option value="{{ $key }}" @selected($item->finishing === $key)>{{ $finishing['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Support tidak lagi dipilih pelanggan: teknologinya yang
                             menentukan, dan Hollow Model sudah tidak ditawarkan.
                             Lihat App\Services\Pricing\PricingInput. --}}
                    </div>

                    <div class="mt-6 flex flex-wrap items-center justify-between gap-4 border-t border-ink-100 pt-5">
                        <dl class="flex flex-wrap gap-x-8 gap-y-2 text-xs">
                            {{-- Berat tidak ikut ditampilkan kepada pelanggan. --}}
                            @foreach ([
                                'Spesifikasi' => $item->specification_summary,
                                'Lead Time' => $item->lead_time ?? '-',
                                'Harga Penawaran' => harga_penawaran($item->display_price),
                            ] as $label => $value)
                                <div>
                                    <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                                    <dd class="mt-0.5 font-semibold text-ink-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        <button type="submit" class="btn-primary px-6 py-2.5">Simpan Pengaturan</button>
                    </div>
                </form>
            </section>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        /**
         * Daftar material mengikuti teknologi yang dipilih pada card yang sama.
         * Kombinasi tetap diperiksa ulang di server, jadi ini murni kenyamanan.
         */
        document.querySelectorAll('[data-item-form]').forEach((form) => {
            const technology = form.querySelector('[data-technology-select]');
            const material = form.querySelector('[data-material-select]');

            if (!technology || !material) {
                return;
            }

            technology.addEventListener('change', () => {
                let firstVisible = null;

                material.querySelectorAll('option').forEach((option) => {
                    const matches = option.dataset.technology === technology.value;
                    option.hidden = !matches;

                    if (matches && firstVisible === null) {
                        firstVisible = option;
                    }
                });

                if (material.selectedOptions[0]?.hidden && firstVisible) {
                    material.value = firstVisible.value;
                }
            });
        });
    </script>
@endpush
