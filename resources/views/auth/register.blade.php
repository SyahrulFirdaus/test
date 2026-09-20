{{--
    Langkah pertama pendaftaran: memilih tipe akun.

    Istilah yang dilihat pelanggan hanya "Personal" dan "Business" — penyebutan
    B2C maupun B2B sengaja tidak pernah muncul. Nilai yang tersimpan di basis
    data tetap `personal` dan `business`.

    Kartunya adalah label untuk radio yang disembunyikan, jadi pemilihannya
    berjalan tanpa JavaScript sama sekali.

    Yang ditawarkan hanya tipe yang sedang dibuka (lihat config/registration.php);
    saat tinggal satu, kartunya sudah tercentang sejak halaman dimuat dan grid
    dua kolomnya menyusut menjadi satu.
--}}
@extends('layouts.auth')

@section('title', 'Daftar')
@section('panelWidth', 'max-w-2xl')
@section('heading', 'Buat akun baru')
@section('subheading', count($types) > 1
    ? 'Pilih tipe akun Anda terlebih dahulu agar kami dapat menyesuaikan pertanyaan dengan kebutuhan Anda.'
    : 'Lengkapi beberapa langkah singkat agar kami dapat menyesuaikan layanan dengan kebutuhan Anda.')

@section('form')
    <form method="POST" action="{{ route('register.type') }}" class="mt-7">
        @csrf

        <fieldset>
            <legend class="field-label">
                {{ count($types) > 1 ? 'Pilih tipe akun Anda' : 'Tipe akun Anda' }}
                <span class="text-brand-600">*</span>
            </legend>

            <div class="mt-4 grid gap-4 {{ count($types) > 1 ? 'sm:grid-cols-2' : '' }}">
                @foreach ($types as $key => $type)
                    <div>
                        <input type="radio" id="type-{{ $key }}" name="customer_type" value="{{ $key }}"
                               class="peer sr-only"
                               @checked(old('customer_type', $selected) === $key)>

                        <label for="type-{{ $key }}" class="type-card">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white">
                                <x-dynamic-component :component="'icons.'.$type['icon']" class="h-6 w-6" />
                            </span>

                            <span class="mt-4 font-display text-lg font-bold text-ink-900">{{ $type['label'] }}</span>
                            <span class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">{{ $type['tagline'] }}</span>
                            <span class="mt-3 text-sm leading-relaxed text-ink-500">{{ $type['description'] }}</span>
                        </label>
                    </div>
                @endforeach
            </div>

            @error('customer_type') <p class="field-error">{{ $message }}</p> @enderror
        </fieldset>

        <button type="submit" class="btn-primary mt-7 w-full">Lanjut &rarr;</button>
    </form>

    <p class="mt-7 border-t border-ink-100 pt-6 text-center text-sm text-ink-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 transition-colors hover:text-brand-700">Masuk di sini</a>
    </p>
@endsection
