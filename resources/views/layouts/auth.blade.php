<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title') &middot; {{ $company->name }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-ink-50">

    <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">

        {{-- Panel brand — hanya dekoratif, disembunyikan di layar kecil agar
             formulir langsung terlihat tanpa perlu digulir. --}}
        <aside class="relative hidden overflow-hidden bg-gradient-to-br from-brand-800 via-brand-700 to-brand-950 p-12 lg:flex lg:flex-col lg:justify-between">
            <div class="blueprint-grid-dark absolute inset-0"></div>

            <a href="{{ route('home') }}" class="relative flex items-center gap-3">
                <x-logo-mark class="h-11 w-11 shrink-0" />
                <span class="font-display text-xl font-bold tracking-tight text-white">{{ $company->name }}</span>
            </a>

            <div class="relative max-w-md">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Sistem Penawaran 3D Printing</p>
                <h2 class="mt-4 font-display text-3xl font-bold leading-tight text-white">
                    Satu akun untuk seluruh penawaran Anda.
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-white/70">
                    Unggah model, simulasikan pengaturan cetak, lalu kirim penawaran — seluruh riwayat, status
                    produksi, dan notifikasinya tersimpan rapi di dashboard Anda.
                </p>

                <ul class="mt-8 space-y-3 text-sm text-white/80">
                    @foreach ([
                        'Hingga 25 file 3D dalam satu penawaran',
                        'Pantau status dari review sampai selesai',
                        'Notifikasi setiap kali status berubah',
                    ] as $point)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white/15">
                                <x-icons.check class="h-3 w-3 text-white" />
                            </span>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative text-xs text-white/50">&copy; {{ now()->year }} {{ $company->name }}</p>
        </aside>

        {{-- Formulir --}}
        <main class="flex items-center justify-center px-5 py-12 sm:px-8">
            <div class="w-full max-w-lg">

                <a href="{{ route('home') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                    <x-logo-mark class="h-10 w-10 shrink-0" />
                    <span class="font-display text-lg font-bold tracking-tight text-ink-900">{{ $company->name }}</span>
                </a>

                <div class="rounded-3xl border border-ink-100 bg-white p-7 shadow-card sm:p-9">
                    <h1 class="font-display text-2xl font-bold tracking-tight text-ink-900">@yield('heading')</h1>
                    <p class="mt-2 text-sm leading-relaxed text-ink-500">@yield('subheading')</p>

                    @if (session('status'))
                        <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @yield('form')
                </div>

                <p class="mt-6 text-center text-xs text-ink-400">
                    <a href="{{ route('home') }}" class="font-semibold transition-colors hover:text-brand-600">&larr; Kembali ke website</a>
                </p>
            </div>
        </main>
    </div>

</body>
</html>
