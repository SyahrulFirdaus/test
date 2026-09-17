@extends('layouts.login')

{{-- Tampilan sama dengan /admin/login (layouts/login + components/login-card);
     yang berbeda hanya teks, alamat kirim, dan tautan pendaftaran. --}}

@section('title', 'Masuk · '.$company->name)

@section('content')
    <x-login-card :action="route('login.store')"
                  brand-label="Dashboard Akun"
                  heading="Masuk ke dashboard"
                  subheading="Masuk untuk membuat penawaran baru dan memantau seluruh pesanan Anda.">
        <x-slot:footer>
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-brand-600 transition-colors hover:text-brand-700">Daftar sekarang</a>
        </x-slot:footer>
    </x-login-card>
@endsection
