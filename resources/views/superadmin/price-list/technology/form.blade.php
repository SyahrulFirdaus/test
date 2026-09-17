@extends('layouts.dashboard')

@php
    $isEdit = $technology->exists;
    $locked = $isEdit && $technology->isInUse();
@endphp

@section('title', $isEdit ? 'Ubah Teknologi' : 'Tambah Teknologi')

@section('content')
    <a href="{{ route('superadmin.price-list.technologies.index') }}"
       class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Price List
    </a>

    <h2 class="mt-5 font-display text-2xl font-bold tracking-tight text-ink-900">
        {{ $isEdit ? 'Ubah Teknologi '.$technology->code : 'Tambah Teknologi' }}
    </h2>
    <p class="mt-1.5 text-sm text-ink-500">
        {{ $isEdit
            ? 'Parameter di bawah dipakai estimator untuk menghitung berat, waktu, dan Harga Jual penawaran.'
            : 'Setelah disimpan, teknologi ini langsung mendapat tab materialnya sendiri dan muncul sebagai pilihan di Edit Specification.' }}
    </p>

    <form method="POST"
          action="{{ $isEdit ? route('superadmin.price-list.technologies.update', $technology) : route('superadmin.price-list.technologies.store') }}"
          class="mt-6 space-y-6">
        @csrf
        @if ($isEdit) @method('PATCH') @endif

        {{-- ---------------------------------------------------- identitas --- --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
            <h3 class="font-display text-base font-bold text-ink-900">Identitas</h3>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="code" class="field-label">
                        Kode @if ($locked) <span class="text-ink-300">(terkunci)</span> @endif
                    </label>
                    <input type="text" id="code" name="code" maxlength="12"
                           value="{{ old('code', $technology->code) }}"
                           @disabled($locked)
                           class="field-input uppercase {{ $locked ? 'cursor-not-allowed opacity-60' : '' }}"
                           placeholder="mis. DLP">
                    <p class="mt-1.5 text-xs text-ink-400">
                        {{ $locked
                            ? 'Teknologi ini sudah dipakai penawaran, jadi kodenya tidak dapat diubah.'
                            : 'Huruf dan angka saja, tanpa spasi. Inilah yang tersimpan pada penawaran.' }}
                    </p>
                    @error('code') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="field-label">Nama Lengkap</label>
                    <input type="text" id="name" name="name" maxlength="120" required
                           value="{{ old('name', $technology->name) }}" class="field-input"
                           placeholder="mis. Digital Light Processing">
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="family" class="field-label">Keluarga Bahan <span class="text-ink-300">(opsional)</span></label>
                    <input type="text" id="family" name="family" maxlength="60"
                           value="{{ old('family', $technology->family) }}" class="field-input"
                           placeholder="mis. Resin">
                    <p class="mt-1.5 text-xs text-ink-400">Dipakai pada label pilihan, mis. "DLP (Resin)".</p>
                    @error('family') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sort_order" class="field-label">Urutan Tab</label>
                    <input type="number" step="10" min="0" id="sort_order" name="sort_order"
                           value="{{ old('sort_order', $technology->sort_order) }}" class="field-input">
                    <p class="mt-1.5 text-xs text-ink-400">Makin kecil, makin kiri posisi tabnya.</p>
                    @error('sort_order') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="field-label">Deskripsi <span class="text-ink-300">(opsional)</span></label>
                    <textarea id="description" name="description" rows="3" maxlength="2000" class="field-input"
                              placeholder="Penjelasan singkat yang dibaca pelanggan di Edit Specification.">{{ old('description', $technology->description) }}</textarea>
                    @error('description') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------- area & bahan --- --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
            <h3 class="font-display text-base font-bold text-ink-900">Area Cetak &amp; Pemakaian Bahan</h3>
            <p class="mt-1 text-xs text-ink-400">Menentukan apakah model muat dicetak, dan berapa bahan yang terpakai.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach (['x' => 'Lebar (X)', 'y' => 'Kedalaman (Y)', 'z' => 'Tinggi (Z)'] as $axis => $label)
                    <div>
                        <label for="build_volume_{{ $axis }}" class="field-label">{{ $label }} (mm)</label>
                        <input type="number" step="1" min="10" max="5000" required
                               id="build_volume_{{ $axis }}" name="build_volume_{{ $axis }}"
                               value="{{ old('build_volume_'.$axis, $technology->{'build_volume_'.$axis}) }}" class="field-input">
                        @error('build_volume_'.$axis) <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="shell_ratio" class="field-label">Shell Ratio (0–1)</label>
                    <input type="number" step="0.01" min="0" max="1" required id="shell_ratio" name="shell_ratio"
                           value="{{ old('shell_ratio', $technology->shell_ratio) }}" class="field-input">
                    <p class="mt-1.5 text-xs text-ink-400">Bagian part yang selalu padat. Isi 1 bila part selalu penuh (resin/logam).</p>
                    @error('shell_ratio') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="default_infill" class="field-label">Infill Bawaan (0–1)</label>
                    <input type="number" step="0.01" min="0" max="1" required id="default_infill" name="default_infill"
                           value="{{ old('default_infill', $technology->default_infill) }}" class="field-input">
                    @error('default_infill') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="min_wall_thickness_mm" class="field-label">Tebal Dinding Minimum (mm)</label>
                    <input type="number" step="0.1" min="0.1" max="50" required
                           id="min_wall_thickness_mm" name="min_wall_thickness_mm"
                           value="{{ old('min_wall_thickness_mm', $technology->min_wall_thickness_mm) }}" class="field-input">
                    @error('min_wall_thickness_mm') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="support_volume_factor" class="field-label">Faktor Volume Support</label>
                    <input type="number" step="0.01" min="0" max="5" required
                           id="support_volume_factor" name="support_volume_factor"
                           value="{{ old('support_volume_factor', $technology->support_volume_factor) }}" class="field-input">
                    <p class="mt-1.5 text-xs text-ink-400">Isi 0 bila teknologi ini tidak memerlukan support sama sekali.</p>
                    @error('support_volume_factor') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="infill_note" class="field-label">Catatan Infill <span class="text-ink-300">(opsional)</span></label>
                    <input type="text" id="infill_note" name="infill_note" maxlength="500"
                           value="{{ old('infill_note', $technology->infill_note) }}" class="field-input"
                           placeholder="Ditampilkan bila infill tidak berpengaruh pada teknologi ini.">
                    @error('infill_note') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 rounded-xl border border-ink-100 p-4">
                    <label class="flex items-start gap-3 text-sm text-ink-700">
                        <input type="checkbox" name="allows_hollow" value="1"
                               @checked(old('allows_hollow', $technology->allows_hollow))
                               class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600">
                        <span>
                            <span class="font-semibold text-ink-900">Mendukung Hollow Model</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-ink-500">
                                Untuk teknologi yang partnya mengeras padat (resin, misalnya) sehingga bagian dalamnya
                                perlu dapat dikosongkan.
                            </span>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------ waktu & biaya --- --}}
        <section class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
            <h3 class="font-display text-base font-bold text-ink-900">Waktu &amp; Tarif Mesin</h3>
            <p class="mt-1 text-xs text-ink-400">Menentukan estimasi lama pengerjaan dan Harga Operasional Mesin.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="throughput_cm3_per_hour" class="field-label">Laju Cetak (cm³/jam)</label>
                    <input type="number" step="0.1" min="0.1" required
                           id="throughput_cm3_per_hour" name="throughput_cm3_per_hour"
                           value="{{ old('throughput_cm3_per_hour', $technology->throughput_cm3_per_hour) }}" class="field-input">
                    @error('throughput_cm3_per_hour') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="setup_hours" class="field-label">Waktu Persiapan (jam)</label>
                    <input type="number" step="0.1" min="0" required id="setup_hours" name="setup_hours"
                           value="{{ old('setup_hours', $technology->setup_hours) }}" class="field-input">
                    @error('setup_hours') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="setup_fee" class="field-label">Biaya Persiapan (Rp)</label>
                    <input type="number" step="500" min="0" required id="setup_fee" name="setup_fee"
                           value="{{ old('setup_fee', $technology->setup_fee) }}" class="field-input">
                    @error('setup_fee') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="machine_rate_per_hour" class="field-label">Tarif Mesin (Rp/jam)</label>
                    <input type="number" step="500" min="0" required
                           id="machine_rate_per_hour" name="machine_rate_per_hour"
                           value="{{ old('machine_rate_per_hour', $technology->machine_rate_per_hour) }}" class="field-input">
                    @error('machine_rate_per_hour') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="layer_height_min" class="field-label">Tebal Lapisan Minimum (mm)</label>
                    <input type="number" step="0.001" min="0.001" max="5" required
                           id="layer_height_min" name="layer_height_min"
                           value="{{ old('layer_height_min', $technology->layer_height_min) }}" class="field-input">
                    @error('layer_height_min') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="layer_height_max" class="field-label">Tebal Lapisan Maksimum (mm)</label>
                    <input type="number" step="0.001" min="0.001" max="5" required
                           id="layer_height_max" name="layer_height_max"
                           value="{{ old('layer_height_max', $technology->layer_height_max) }}" class="field-input">
                    @error('layer_height_max') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary px-6 py-3">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Teknologi' }}
            </button>
            <a href="{{ route('superadmin.price-list.technologies.index') }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection
