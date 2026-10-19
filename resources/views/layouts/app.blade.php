<!DOCTYPE html>
{{-- overflow-x-clip: elemen animasi yang belum masuk (AOS fade-left/right,
     bergeser 100px) tidak melebarkan halaman — tanpa ini ponsel memperkecil
     tampilan dan bilah CTA sticky ikut mengecil. --}}
<html lang="id" class="scroll-smooth overflow-x-clip">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Consent Mode v2 default → GTM → Google tag: sedekat mungkin dengan awal <head>. --}}
    @include('partials.google-tag')

    @include('partials.seo')

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.svg') }}">

    @include('partials.fonts')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
{{-- Di ponsel ada bilah CTA sticky (partials/sticky-cta): konten diberi ruang
     bawah setinggi bilah agar tidak pernah tertutup. --}}
<body class="min-h-screen bg-white {{ View::hasSection('bare') ? '' : 'pb-[calc(4.5rem+env(safe-area-inset-bottom))] md:pb-0' }}">
    @include('partials.google-tag-noscript')

    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-full focus:bg-brand-600 focus:px-5 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white">
        Lompat ke konten utama
    </a>

    {{-- Halaman layar penuh (mis. 3D Viewer) memasang @section('bare') untuk
         menyembunyikan navbar, footer, dan tombol kembali ke atas. --}}
    @unless (View::hasSection('bare'))
        @include('partials.navbar')
    @endunless

    <main id="main">
        @yield('content')
    </main>

    @unless (View::hasSection('bare'))
        @include('partials.footer')
        @include('partials.consult-cta')
        @include('partials.sticky-cta')
        @include('partials.cookie-banner')
    @endunless

    {{-- Modal konfirmasi bersama; dipakai tombol Logout pada navbar. --}}
    <x-confirm-dialog />

    @stack('scripts')
</body>
</html>
