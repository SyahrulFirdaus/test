<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @include('partials.seo')

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/favicon.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="min-h-screen bg-white">
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
        @include('partials.scroll-top')
    @endunless

    @stack('scripts')
</body>
</html>
