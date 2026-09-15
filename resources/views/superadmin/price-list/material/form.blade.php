@extends('layouts.dashboard')

@php
    $isEdit = $material->exists;
@endphp

@section('title', $isEdit ? 'Ubah Material' : 'Tambah Material')

@section('content')
    <a href="{{ route('superadmin.price-list.index', ['tab' => $technology->tabKey()]) }}"
       class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Material {{ $technology->code }}
    </a>

    <h2 class="mt-5 font-display text-2xl font-bold tracking-tight text-ink-900">
        {{ $isEdit ? 'Ubah Material '.$technology->code : 'Tambah Material '.$technology->code }}
    </h2>
    <p class="mt-1.5 text-sm text-ink-500">
        Nama yang diisi di sini langsung menjadi pilihan Material pada Edit Specification untuk teknologi
        <span class="font-semibold text-ink-700">{{ $technology->code }}</span>, dan harganya dipakai perhitungan penawaran berikutnya.
    </p>

    <form method="POST"
          action="{{ $isEdit
              ? route('superadmin.price-list.materials.update', [$technology, $material])
              : route('superadmin.price-list.materials.store', $technology) }}"
          class="mt-6 max-w-2xl rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
        @csrf
        @if ($isEdit) @method('PATCH') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="material" class="field-label">Nama Material</label>
                <input type="text" id="material" name="material" maxlength="120" required
                       value="{{ old('material', $material->material) }}" class="field-input"
                       placeholder="mis. PLA+">
                <p class="mt-1.5 text-xs text-ink-400">Inilah yang dibaca pelanggan — tulis nama jenis bahannya, bukan brand.</p>
                @error('material') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="brand" class="field-label">Brand</label>
                <input type="text" id="brand" name="brand" maxlength="60" required
                       value="{{ old('brand', $material->brand) }}" class="field-input"
                       placeholder="mis. ESUN">
                <p class="mt-1.5 text-xs text-ink-400">Keterangan internal; tidak ditampilkan kepada pelanggan.</p>
                @error('brand') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            {{-- Pilihan mesin datang dari Price List → Machine Cost, bukan
                 daftar yang ditulis di sini. Dikelompokkan per teknologi agar
                 mesin yang relevan mudah ditemukan, namun SELURUH mesin tetap
                 dapat dipilih. --}}
            <div class="sm:col-span-2">
                <label for="machine_cost_id" class="field-label">Nama Mesin</label>
                <select id="machine_cost_id" name="machine_cost_id" class="field-input">
                    <option value="">&mdash; Tanpa mesin &mdash;</option>

                    @foreach ($machines->groupBy(fn ($machine) => $machine->technology?->code ?? 'Tanpa Teknologi') as $group => $rows)
                        <optgroup label="{{ $group }}">
                            @foreach ($rows as $machine)
                                <option value="{{ $machine->id }}"
                                        @selected((string) old('machine_cost_id', $material->machine_cost_id) === (string) $machine->id)>
                                    {{ $machine->mesin }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-ink-400">
                    Diambil dari <a href="{{ route('superadmin.price-list.index', ['tab' => 'machine-cost']) }}"
                                    class="font-semibold text-brand-600 hover:text-brand-800">Machine Cost</a>;
                    material dikelompokkan di bawah mesin ini pada Price List.
                    @if ($machines->isEmpty())
                        <span class="font-semibold text-brand-700">Belum ada mesin terdaftar.</span>
                    @endif
                </p>
                @error('machine_cost_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="purchase_price" class="field-label">Harga Beli — Rp</label>
                <input type="number" step="1" min="0" max="9999999999" required id="purchase_price" name="purchase_price"
                       value="{{ old('purchase_price', $material->purchase_price) }}" class="field-input">
                <p class="mt-1.5 text-xs text-ink-400">Harga satu spool/botol. Harga per gram dihitung otomatis darinya.</p>
                @error('purchase_price') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="sale_price" class="field-label">Harga Jual — Rp</label>
                <input type="number" step="1" min="0" max="9999999999" required id="sale_price" name="sale_price"
                       value="{{ old('sale_price', $material->sale_price) }}" class="field-input">
                <p class="mt-1.5 text-xs text-ink-400">Dibulatkan ke atas kelipatan seratus; itulah harga per gram yang dikutip ke pelanggan.</p>
                @error('sale_price') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="remark" class="field-label">Remark <span class="text-ink-300">(opsional)</span></label>
                <input type="text" id="remark" name="remark" maxlength="120"
                       value="{{ old('remark', $material->remark) }}" class="field-input"
                       placeholder="mis. Standard Material">
                @error('remark') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-6">
            <button type="submit" class="btn-primary px-6 py-2.5">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Material' }}
            </button>
            <a href="{{ route('superadmin.price-list.index', ['tab' => $technology->tabKey()]) }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection
