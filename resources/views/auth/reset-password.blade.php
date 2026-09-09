@extends('layouts.auth')

@section('title', 'Buat Password Baru')
@section('heading', 'Buat password baru')
@section('subheading', 'Kata sandi baru langsung berlaku setelah disimpan, dan tautan ini tidak dapat dipakai lagi.')

@section('form')
    <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required
                   autocomplete="username" maxlength="160" class="field-input">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label">Password Baru</label>
            <input type="password" id="password" name="password" required autofocus autocomplete="new-password"
                   class="field-input" placeholder="Contoh: Nusama3D!">
            <p class="mt-1.5 text-xs leading-relaxed text-ink-400">{{ \App\Support\PasswordPolicy::hint() }}</p>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Konfirmasi Password Baru</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   autocomplete="new-password" class="field-input" placeholder="Ulangi kata sandi baru">
        </div>

        <button type="submit" class="btn-primary w-full">Simpan Password Baru</button>
    </form>
@endsection
