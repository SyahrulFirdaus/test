@extends('layouts.dashboard')

@php $isEdit = $machineCost->exists; @endphp

@section('title', $isEdit ? 'Ubah Machine Cost' : 'Tambah Machine Cost')

@section('content')
    @php $value = fn (string $field, $fallback = '') => old($field, $machineCost->{$field} ?? $fallback); @endphp

    <a href="{{ route('superadmin.price-list.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Price List
    </a>

    <div class="mt-5 max-w-2xl">
        <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
            {{ $isEdit ? 'Ubah Machine Cost' : 'Tambah Machine Cost' }}
        </h2>
        <p class="mt-2 text-sm text-ink-500">
            Listrik/Hour, Machine Cost, dan Pembulatan dihitung otomatis dari Watt, Harga Listrik, dan Depresiasi.
        </p>

        <form method="POST"
              action="{{ $isEdit ? route('superadmin.price-list.machine-cost.update', $machineCost) : route('superadmin.price-list.machine-cost.store') }}"
              class="mt-8 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-8">
            @csrf
            @if ($isEdit) @method('PATCH') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="mesin" class="field-label">Mesin <span class="text-brand-600">*</span></label>
                    <input type="text" id="mesin" name="mesin" value="{{ $value('mesin') }}" required maxlength="60"
                           class="field-input" placeholder="mis. Ender 3 V2">
                    @error('mesin') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="watt_kwh" class="field-label">Watt (KWH) <span class="text-brand-600">*</span></label>
                    <input type="number" id="watt_kwh" name="watt_kwh" value="{{ $value('watt_kwh') }}" required min="0" step="0.001"
                           class="field-input">
                    @error('watt_kwh') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="harga_listrik" class="field-label">Harga Listrik (Rp/KWH) <span class="text-brand-600">*</span></label>
                    <input type="number" id="harga_listrik" name="harga_listrik" value="{{ $value('harga_listrik') }}" required min="0" step="0.01"
                           class="field-input">
                    @error('harga_listrik') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="depresiasi" class="field-label">Depresiasi (Rp/jam) <span class="text-brand-600">*</span></label>
                    <input type="number" id="depresiasi" name="depresiasi" value="{{ $value('depresiasi') }}" required min="0" step="0.01"
                           class="field-input">
                    @error('depresiasi') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-7 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('superadmin.price-list.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Mesin' }}</button>
            </div>
        </form>
    </div>
@endsection
