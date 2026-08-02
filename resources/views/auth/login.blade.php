@extends('layouts.auth')

@section('title', 'Masuk')
@section('heading', 'Masuk ke akun Anda')
@section('subheading', 'Masuk untuk membuat penawaran baru dan memantau seluruh pesanan Anda.')

@section('form')
    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" maxlength="160" class="field-input" placeholder="nama@email.com">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   class="field-input" placeholder="Kata sandi Anda">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label class="flex items-center gap-2.5 text-sm text-ink-600">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                       class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600">
                Remember Me
            </label>

            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-600 transition-colors hover:text-brand-700">
                Lupa Password?
            </a>
        </div>

        <button type="submit" class="btn-primary w-full">Masuk</button>
    </form>

    <p class="mt-7 border-t border-ink-100 pt-6 text-center text-sm text-ink-500">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-brand-600 transition-colors hover:text-brand-700">Daftar sekarang</a>
    </p>
@endsection
