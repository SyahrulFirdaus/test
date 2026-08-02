@extends('admin.layout')

@section('title', 'Masuk')

@section('content')
    <div class="w-full max-w-md px-5">
        <div class="rounded-3xl border border-ink-100 bg-white p-8 shadow-card sm:p-10">
            <div class="flex items-center gap-3">
                <x-logo-mark class="h-12 w-12" />
                <span class="flex flex-col leading-tight">
                    <span class="font-display text-lg font-bold text-ink-900">{{ $company->name }}</span>
                    <span class="text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-brand-600">Dashboard Admin</span>
                </span>
            </div>

            <h1 class="mt-8 text-2xl font-bold tracking-tight text-ink-900">Masuk ke dashboard</h1>
            <p class="mt-2 text-sm text-ink-500">Kelola permintaan penawaran yang masuk dari halaman 3D Models.</p>

            @if ($errors->any())
                <div class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-7 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 transition-colors focus:border-brand-600 focus:outline-none">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-[0.14em] text-ink-400">Kata Sandi</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 transition-colors focus:border-brand-600 focus:outline-none">
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="flex items-center gap-2.5 text-sm text-ink-600">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600">
                        Ingat saya di perangkat ini
                    </label>

                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-600 transition-colors hover:text-brand-700">
                        Lupa Password?
                    </a>
                </div>

                <button type="submit" class="btn-primary w-full">Masuk</button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-ink-400">
            <a href="{{ route('home') }}" class="font-semibold transition-colors hover:text-brand-600">&larr; Kembali ke website</a>
        </p>
    </div>
@endsection
