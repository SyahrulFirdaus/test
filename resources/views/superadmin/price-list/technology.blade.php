@extends('superadmin.price-list.layout')

{{-- Halaman material SATU teknologi, mis. /price-list/fdm. Teknologi lain tidak
     ditampilkan sama sekali di sini — masing-masing punya halamannya sendiri. --}}

@section('title', 'Price List · Teknologi '.$technology->tabLabel())
@section('price-list-group', 'Teknologi & Material')
@section('price-list-page', 'Teknologi '.$technology->tabLabel())

@section('price-list')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    @php $tab = $technology->tabKey(); @endphp

    <section>
        <form method="GET" class="mb-4 flex flex-wrap gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
            <div class="min-w-[240px] flex-1">
                <label for="{{ $tab }}_q" class="field-label">
                    Cari Material {{ $technology->isSlaIndustries() ? $technology->name : $technology->code }}
                </label>
                <input type="search" id="{{ $tab }}_q" name="{{ $tab }}_q"
                       value="{{ $search }}"
                       placeholder="Nama material, brand, atau remark" class="field-input">
            </div>
            <div class="flex items-end"><button type="submit" class="btn-outline px-6 py-3">Cari</button></div>
        </form>

        @include('superadmin.price-list.partials.material-table', [
            'technology' => $technology,
            'materials' => $materials,
            'numbers' => $materialNumbers,
            'rupiah' => $rupiah,
        ])
    </section>
@endsection
