@extends('layouts.auth')

@section('title', 'Daftar')
@section('heading', 'Buat akun baru')
@section('subheading', 'Data pengiriman di bawah ini akan mengisi otomatis setiap penawaran yang Anda buat.')

@section('form')
    <form method="POST" action="{{ route('register.store') }}" class="mt-7 grid gap-5 sm:grid-cols-2">
        @csrf

        <div class="sm:col-span-2">
            <label for="name" class="field-label">Nama Lengkap <span class="text-brand-600">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                   autocomplete="name" maxlength="120" class="field-input" placeholder="Nama sesuai identitas">
            @error('name') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="field-label">Nomor Telepon <span class="text-brand-600">*</span></label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                   autocomplete="tel" maxlength="32" class="field-input" placeholder="0812 3456 7890">
            @error('phone') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="city" class="field-label">Kota Asal <span class="text-brand-600">*</span></label>
            <input type="text" id="city" name="city" value="{{ old('city') }}" required
                   autocomplete="address-level2" maxlength="120" class="field-input" placeholder="Bandung">
            @error('city') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="postal_code" class="field-label">Kode Pos <span class="text-brand-600">*</span></label>
            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}" required
                   inputmode="numeric" autocomplete="postal-code" maxlength="12" class="field-input" placeholder="40123">
            @error('postal_code') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="field-label">Email <span class="text-brand-600">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                   autocomplete="email" maxlength="160" class="field-input" placeholder="nama@email.com">
            @error('email') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="address" class="field-label">Alamat Lengkap <span class="text-brand-600">*</span></label>
            <textarea id="address" name="address" rows="3" required autocomplete="street-address" maxlength="500"
                      class="field-input" placeholder="Nama jalan, nomor, RT/RW, kelurahan, kecamatan">{{ old('address') }}</textarea>
            @error('address') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="field-label">Password <span class="text-brand-600">*</span></label>
            <input type="password" id="password" name="password" required autocomplete="new-password"
                   class="field-input" placeholder="Minimal 8 karakter">
            @error('password') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Konfirmasi Password <span class="text-brand-600">*</span></label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   autocomplete="new-password" class="field-input" placeholder="Ulangi kata sandi">
        </div>

        <div class="sm:col-span-2">
            <button type="submit" class="btn-primary w-full">Daftar</button>
        </div>
    </form>

    <p class="mt-7 border-t border-ink-100 pt-6 text-center text-sm text-ink-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 transition-colors hover:text-brand-700">Masuk di sini</a>
    </p>
@endsection
