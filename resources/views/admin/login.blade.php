@extends('layouts.login')

{{-- Tampilan sama dengan /login (layouts/login + components/login-card);
     yang berbeda hanya teks dan alamat kirimnya. --}}

@section('title', 'Masuk · Admin '.$company->name)

@section('content')
    <x-login-card :action="route('admin.login.store')"
                  :brand-label="$brandLabel ?? 'Dashboard Admin'"
                  heading="Masuk ke dashboard"
                  subheading="Kelola permintaan penawaran yang masuk dari halaman 3D Models." />
@endsection
