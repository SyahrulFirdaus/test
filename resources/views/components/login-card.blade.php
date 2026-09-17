{{--
    Kartu masuk yang dipakai bersama /login dan /admin/login.

    Kartu sengaja DATAR — tanpa bayangan tebal, efek timbul, atau transform
    3D — supaya tetap mudah dibaca di atas latar 3D pada layouts/login.

    Props:
      action      alamat kirim formulir
      brandLabel  keterangan di bawah nama brand, mis. "Dashboard Admin"
      heading     judul kartu
      subheading  deskripsi singkat
    Slot:
      footer      isi tambahan di dasar kartu (mis. tautan daftar)
--}}
@props([
    'action',
    'brandLabel',
    'heading' => 'Masuk ke dashboard',
    'subheading' => null,
])

<div class="w-full max-w-md">
    <div class="login-card rounded-2xl border border-ink-200 bg-white p-7 sm:p-9">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <x-logo-mark class="h-11 w-11 shrink-0" />
            <span class="flex flex-col leading-tight">
                <span class="font-display text-lg font-bold text-ink-900">{{ $company->name }}</span>
                <span class="text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-brand-600">{{ $brandLabel }}</span>
            </span>
        </a>

        <h1 class="mt-8 font-display text-2xl font-bold tracking-tight text-ink-900">{{ $heading }}</h1>

        @if ($subheading)
            <p class="mt-2 text-sm leading-relaxed text-ink-500">{{ $subheading }}</p>
        @endif

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ $action }}" class="mt-7 space-y-5">
            @csrf

            <div>
                <label for="email" class="field-label">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" maxlength="160" class="field-input" placeholder="nama@email.com">
            </div>

            <div>
                <label for="password" class="field-label">Kata Sandi</label>
                <x-password-field id="password" name="password" required autocomplete="current-password"
                                  class="field-input mt-0" placeholder="Kata sandi Anda" />

                {{-- Tanpa "Ingat saya": tautan lupa kata sandi berdiri sendiri di kanan. --}}
                <div class="mt-2.5 flex justify-end">
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-600 transition-colors hover:text-brand-700">
                        Lupa Password?
                    </a>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full">Masuk</button>
        </form>

        @isset($footer)
            <div class="mt-7 border-t border-ink-100 pt-6 text-center text-sm text-ink-500">
                {{ $footer }}
            </div>
        @endisset
    </div>

    {{-- Di luar kartu, di atas latar gelap. --}}
    <p class="mt-6 text-center text-xs text-white/60">
        <a href="{{ route('home') }}" class="font-semibold transition-colors hover:text-white">&larr; Kembali ke website</a>
    </p>
</div>
