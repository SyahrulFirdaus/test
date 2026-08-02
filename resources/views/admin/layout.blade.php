{{--
    Kerangka halaman masuk admin.

    Halaman dashboard admin memakai layouts/dashboard.blade.php yang sudah
    membawa sidebar dan ikon lonceng; layout ini tinggal melayani halaman
    masuk yang memang belum punya sesi.
--}}
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'Dashboard') &middot; Admin {{ $company->name }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-ink-50">

    <main class="flex min-h-screen items-center justify-center py-12">
        @yield('content')
    </main>

</body>
</html>
