@extends('layouts.dashboard')

@php $isEdit = $machineCost->exists; @endphp

@section('title', $isEdit ? 'Ubah Machine Cost' : 'Tambah Machine Cost')

@section('content')
    @php $value = fn (string $field, $fallback = '') => old($field, $machineCost->{$field} ?? $fallback); @endphp

    <a href="{{ route('superadmin.price-list.machine-cost.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Price List
    </a>

    <div class="mt-5 max-w-2xl">
        <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
            {{ $isEdit ? 'Ubah Machine Cost' : 'Tambah Machine Cost' }}
        </h2>
        <p class="mt-2 text-sm text-ink-500">
            Listrik/Hour, Machine Cost, dan Pembulatan dihitung otomatis dari Watt, Harga Listrik, dan Depresiasi.
            Spesifikasi fisik di bawahnya yang tampil pada expand detail mesin di Price List.
        </p>

        <form method="POST"
              action="{{ $isEdit ? route('superadmin.price-list.machine-cost.update', $machineCost) : route('superadmin.price-list.machine-cost.store') }}"
              class="mt-8 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-8">
            @csrf
            @if ($isEdit) @method('PATCH') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="mesin" class="field-label">Mesin <span class="text-brand-600">*</span></label>
                    <input type="text" id="mesin" name="mesin" value="{{ $value('mesin') }}" required maxlength="60"
                           class="field-input" placeholder="mis. Ender 3 V2">
                    @error('mesin') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Teknologi menentukan di bawah kelompok mana mesin ini tampil
                     pada Price List. Boleh dikosongkan; mesin yang belum
                     ditentukan berkumpul di kelompok "Tanpa Teknologi". --}}
                <div>
                    <label for="print_technology_id" class="field-label">Teknologi</label>
                    <select id="print_technology_id" name="print_technology_id" class="field-input">
                        <option value="">Tanpa teknologi</option>
                        @foreach ($technologies as $technology)
                            <option value="{{ $technology->id }}"
                                    @selected((string) $value('print_technology_id') === (string) $technology->id)>
                                {{ $technology->code }} ({{ $technology->name }})
                            </option>
                        @endforeach
                    </select>
                    @error('print_technology_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                {{-- Pemetaan eksplisit ke printer Calculator: Pricing Engine
                     memakai Machine Cost baris ini untuk model yang dicetak pada
                     printer tersebut. Printer tanpa pemetaan memakai Machine Cost
                     Rumus Harga Otomatis. --}}
                <div class="sm:col-span-2">
                    <label for="printer_key" class="field-label">Printer pada Calculator</label>
                    <select id="printer_key" name="printer_key" class="field-input">
                        <option value="">Tidak dipetakan</option>
                        @foreach ($printers as $key => $printer)
                            <option value="{{ $key }}" @selected((string) $value('printer_key') === (string) $key)>
                                {{ $printer['name'] }}@if (($takenPrinters[$key] ?? null) !== null) — dipakai {{ $takenPrinters[$key] }}@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-ink-400">
                        Model yang dicetak pada printer ini dihitung memakai Machine Cost mesin ini. Satu printer hanya dapat dipetakan ke satu mesin.
                    </p>
                    @error('printer_key') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="watt_kwh" class="field-label">Watt (KWH) <span class="text-brand-600">*</span></label>
                    <input type="number" id="watt_kwh" name="watt_kwh" value="{{ $value('watt_kwh') }}" required min="0" max="999.999" step="0.001"
                           class="field-input">
                    @error('watt_kwh') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="harga_listrik" class="field-label">Harga Listrik (Rp/KWH) <span class="text-brand-600">*</span></label>
                    <input type="number" id="harga_listrik" name="harga_listrik" value="{{ $value('harga_listrik') }}" required min="0" max="9999999999" step="0.01"
                           class="field-input">
                    @error('harga_listrik') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="depresiasi" class="field-label">Depresiasi (Rp/jam) <span class="text-brand-600">*</span></label>
                    <input type="number" id="depresiasi" name="depresiasi" value="{{ $value('depresiasi') }}" required min="0" max="9999999999" step="0.01"
                           class="field-input">
                    @error('depresiasi') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Spesifikasi fisik: seluruhnya opsional, yang kosong tampil "—". --}}
            <div class="mt-8 border-t border-ink-100 pt-7">
                <h3 class="font-display text-base font-bold text-ink-900">Detail Mesin</h3>
                <p class="mt-1 text-xs text-ink-400">
                    Tampil pada expand detail di Price List. Boleh dikosongkan dan dilengkapi belakangan.
                </p>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    @foreach ([
                        'width_mm' => 'Lebar W (mm)',
                        'depth_mm' => 'Kedalaman D (mm)',
                        'height_mm' => 'Tinggi H (mm)',
                    ] as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="field-label">{{ $label }}</label>
                            <input type="number" id="{{ $field }}" name="{{ $field }}" value="{{ $value($field) }}"
                                   min="0" max="99999" step="0.1" class="field-input" placeholder="mis. 389">
                            @error($field) <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <div>
                        <label for="weight_kg" class="field-label">Berat (kg)</label>
                        <input type="number" id="weight_kg" name="weight_kg" value="{{ $value('weight_kg') }}"
                               min="0" max="9999" step="0.01" class="field-input" placeholder="mis. 12.95">
                        @error('weight_kg') <p class="field-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <span class="field-label">Volume Cetak (mm)</span>
                        <div class="mt-2 grid grid-cols-3 gap-3">
                            @foreach ([
                                'build_volume_x' => 'Lebar meja',
                                'build_volume_y' => 'Kedalaman meja',
                                'build_volume_z' => 'Tinggi maksimum',
                            ] as $field => $hint)
                                <div>
                                    <input type="number" id="{{ $field }}" name="{{ $field }}" value="{{ $value($field) }}"
                                           min="1" max="100000" step="1" class="field-input" placeholder="256"
                                           aria-label="Volume cetak: {{ $hint }}">
                                    <p class="mt-1 text-[0.65rem] text-ink-400">{{ $hint }}</p>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[0.65rem] text-ink-400">
                            Ketiganya perlu diisi agar volume cetak tampil; mis. 256 &times; 256 &times; 256 mm.
                        </p>
                        @error('build_volume_x') <p class="field-error">{{ $message }}</p> @enderror
                        @error('build_volume_y') <p class="field-error">{{ $message }}</p> @enderror
                        @error('build_volume_z') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="mt-7 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('superadmin.price-list.machine-cost.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Mesin' }}</button>
            </div>
        </form>
    </div>
@endsection
