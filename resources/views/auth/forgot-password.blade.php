@extends('layouts.auth')

@section('title', 'Lupa Password')
@section('heading', 'Lupa password')
@section('subheading', 'Masukkan email akun Anda. Kami mengirimkan tautan untuk membuat kata sandi baru.')

@section('form')
    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
        @csrf

        <div>
            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" maxlength="160" class="field-input" placeholder="nama@email.com">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary w-full">Kirim Tautan Reset</button>
    </form>

    <p class="mt-7 border-t border-ink-100 pt-6 text-center text-sm text-ink-500">
        Sudah ingat kata sandinya?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 transition-colors hover:text-brand-700">Kembali ke halaman masuk</a>
    </p>
@endsection
