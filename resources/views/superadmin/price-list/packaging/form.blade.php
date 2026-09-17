@extends('layouts.dashboard')

@php $isEdit = $packagingItem->exists; @endphp

@section('title', $isEdit ? 'Ubah Packaging' : 'Tambah Packaging')

@section('content')
    @php $value = fn (string $field, $fallback = '') => old($field, $packagingItem->{$field} ?? $fallback); @endphp

    <a href="{{ route('superadmin.price-list.packaging.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Price List
    </a>

    <div class="mt-5 max-w-2xl">
        <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
            {{ $isEdit ? 'Ubah Packaging' : 'Tambah Packaging' }}
        </h2>

        <form method="POST"
              action="{{ $isEdit ? route('superadmin.price-list.packaging.update', $packagingItem) : route('superadmin.price-list.packaging.store') }}"
              class="mt-8 rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-8">
            @csrf
            @if ($isEdit) @method('PATCH') @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="item" class="field-label">Item <span class="text-brand-600">*</span></label>
                    <input type="text" id="item" name="item" value="{{ $value('item') }}" required maxlength="60"
                           class="field-input" placeholder="mis. Kardus, Foam, Bubble">
                    @error('item') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="ukuran" class="field-label">Ukuran</label>
                    <input type="text" id="ukuran" name="ukuran" value="{{ $value('ukuran') }}" maxlength="30"
                           class="field-input" placeholder="mis. XS, S, M, L, XL, atau -">
                    @error('ukuran') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="dimensi" class="field-label">Dimensi</label>
                    <input type="text" id="dimensi" name="dimensi" value="{{ $value('dimensi') }}" maxlength="60"
                           class="field-input" placeholder="mis. 10 x 10 x 5 cm, atau Tebal 5 cm">
                    @error('dimensi') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="price" class="field-label">Harga (Rp) <span class="text-brand-600">*</span></label>
                    <input type="number" id="price" name="price" value="{{ $value('price') }}" required min="0" step="0.01"
                           class="field-input">
                    @error('price') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="price_unit" class="field-label">Satuan Harga <span class="text-brand-600">*</span></label>
                    <select id="price_unit" name="price_unit" required class="field-input">
                        <option value="{{ \App\Models\PackagingItem::UNIT_FLAT }}" @selected($value('price_unit', \App\Models\PackagingItem::UNIT_FLAT) === \App\Models\PackagingItem::UNIT_FLAT)>Flat per item</option>
                        <option value="{{ \App\Models\PackagingItem::UNIT_PER_CM }}" @selected($value('price_unit') === \App\Models\PackagingItem::UNIT_PER_CM)>Per sentimeter (/cm)</option>
                    </select>
                    @error('price_unit') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-7 flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('superadmin.price-list.packaging.index') }}" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Packaging' }}</button>
            </div>
        </form>
    </div>
@endsection
