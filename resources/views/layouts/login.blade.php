{{--
    Kerangka halaman masuk — dipakai /login dan /admin/login.

    Latarnya yang bernuansa 3D (lantai grid berperspektif, kubus rangka yang
    berputar, dan animasi mesin cetak), sedangkan kartu formulirnya sengaja
    datar: lihat components/login-card.blade.php.

    Halaman mengisi:
      title    judul tab browser (lengkap)
      content  isi di tengah layar, biasanya <x-login-card>
--}}
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title')</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/auth-scene.js'])
</head>
<body class="min-h-screen bg-brand-950">

    {{-- ================= LATAR 3D ================= --}}
    {{-- Mesin digeser ke kiri bawah supaya tidak tertutup kartu di tengah. --}}
    <x-scene-3d position="fixed inset-0" shift-x="-0.22" shift-y="-0.02" scale="1.45" />

    <main class="relative flex min-h-screen items-center justify-center px-5 py-12">
        @yield('content')
    </main>

    @stack('scripts')

</body>
</html>
