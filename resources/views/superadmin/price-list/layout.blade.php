{{--
    Kerangka bersama halaman-halaman Price List.

    Navigasinya ada di sidebar (resources/views/layouts/dashboard.blade.php,
    menu dari App\Support\PriceListPage). Tiap halaman mengisi:
      title               judul tab browser
      price-list-group    nama grup menu, mis. "Teknologi & Material"
      price-list-page     nama item menu, mis. "FDM"
      price-list          isi halamannya
--}}
@extends('layouts.dashboard')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">
            Price List &rsaquo; @yield('price-list-group')
        </p>
        <h2 class="mt-1.5 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">@yield('price-list-page')</h2>
    </div>

    <div class="mt-6">
        @yield('price-list')
    </div>
@endsection
