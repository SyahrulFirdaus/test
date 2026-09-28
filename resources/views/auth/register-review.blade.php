{{--
    Ringkasan sebelum akun dibuat.

    Setiap kelompok membawa tautan Edit ke langkahnya sendiri, jadi memperbaiki
    satu jawaban tidak menuntut pengulangan dari awal. Akun baru benar-benar
    dibuat ketika tombol "Daftar Sekarang" ditekan.
--}}
@extends('layouts.auth')

@section('title', 'Ringkasan Pendaftaran')
@section('panelWidth', 'max-w-2xl')
@section('heading', 'Ringkasan informasi')
@section('subheading', 'Periksa kembali informasi Anda. Setelah akun dibuat, Anda dapat langsung masuk dan mulai mengunggah model 3D.')

@section('brandVisual')
    @include('auth.partials.register-visual')
@endsection

@section('form')
    <div class="mt-7 border-t border-ink-100 pt-7">
        <x-register-progress
            :type-label="$typeLabel"
            :label="$step['label']"
            :number="$step['number']"
            :step-count="$stepCount"
            :progress="$progress" />
    </div>

    {{-- Tipe akun --}}
    <div class="mt-8 rounded-2xl border border-ink-100 bg-ink-50/60 p-5">
        <p class="text-[0.65rem] font-bold uppercase tracking-[0.14em] text-ink-400">Tipe Akun</p>
        <p class="mt-1 font-display text-lg font-bold text-ink-900">{{ $typeLabel }}</p>
    </div>

    {{-- Data akun --}}
    @php
        $isBusiness = $customerType === \App\Support\CustomerType::BUSINESS;

        // Pelanggan perusahaan tidak mengisi alamat pada langkah ini — alamatnya
        // ada di bagian Data Perusahaan di bawah.
        $accountRows = $isBusiness
            ? [
                'Nama' => $account['name'] ?? '-',
                'Email' => $account['email'] ?? '-',
                'Nomor WhatsApp' => $account['phone'] ?? '-',
            ]
            : [
                'Nama' => $account['name'] ?? '-',
                'Email' => $account['email'] ?? '-',
                'Nomor Telepon' => $account['phone'] ?? '-',
                'Kota' => $account['city'] ?? '-',
                'Kode Pos' => $account['postal_code'] ?? '-',
                'Alamat' => $account['address'] ?? '-',
            ];
    @endphp

    <section class="mt-6">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-display text-sm font-bold uppercase tracking-[0.12em] text-ink-700">
                {{ $isBusiness ? 'Data Kontak' : 'Data Akun' }}
            </h2>
            <a href="{{ $accountEditUrl }}" class="text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">Edit</a>
        </div>

        <dl class="mt-3 divide-y divide-ink-100 rounded-2xl border border-ink-100">
            @foreach ($accountRows as $label => $value)
                <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-400">{{ $label }}</dt>
                    <dd class="max-w-[60%] text-right text-sm font-semibold text-ink-800">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Data perusahaan; hanya pada pendaftaran Business --}}
    @if ($companyData !== [])
        <section class="mt-6">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-display text-sm font-bold uppercase tracking-[0.12em] text-ink-700">Data Perusahaan</h2>
                <a href="{{ $companyEditUrl }}" class="text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">Edit</a>
            </div>

            <dl class="mt-3 divide-y divide-ink-100 rounded-2xl border border-ink-100">
                @foreach ($companyData as $label => $value)
                    <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-400">{{ $label }}</dt>
                        <dd class="max-w-[60%] text-right text-sm font-semibold text-ink-800">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    {{-- Jawaban per kelompok pertanyaan --}}
    @foreach ($groups as $group)
        <section class="mt-6">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-display text-sm font-bold uppercase tracking-[0.12em] text-ink-700">{{ $group['label'] }}</h2>
                <a href="{{ $group['editUrl'] }}" class="text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700">Edit</a>
            </div>

            <dl class="mt-3 divide-y divide-ink-100 rounded-2xl border border-ink-100">
                @foreach ($group['items'] as $item)
                    <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-3">
                        <dt class="max-w-[55%] text-xs font-semibold uppercase tracking-[0.1em] text-ink-400">{{ $item['question'] }}</dt>
                        <dd class="max-w-[45%] text-right text-sm font-semibold text-ink-800">{{ $item['answer'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach

    <form method="POST" action="{{ route('register.store') }}" class="mt-9 border-t border-ink-100 pt-6">
        @csrf

        {{-- CAPTCHA: diperiksa di server tepat sebelum akun dibuat
             (RegisteredUserController::store). Gambarnya dibuat server; kode
             yang benar tidak pernah dikirim ke halaman ini. --}}
        <div class="rounded-2xl border border-ink-100 bg-ink-50/60 p-4 sm:p-5" data-captcha>
            <label for="captcha" class="field-label">Verifikasi CAPTCHA <span class="text-brand-600">*</span></label>
            <p class="mt-1 text-xs text-ink-400">Ketik {{ \App\Support\Captcha::LENGTH }} karakter pada gambar untuk memastikan Anda bukan robot.</p>

            <div class="mt-3 flex flex-wrap items-center gap-3">
                <img src="{{ route('register.captcha') }}?v={{ now()->getTimestamp() }}"
                     width="190" height="64" alt="Kode CAPTCHA"
                     class="h-16 w-[190px] shrink-0 rounded-xl border border-ink-200 bg-white"
                     data-captcha-image>

                <button type="button" class="viewer-tool" data-captcha-refresh>Ganti kode</button>
            </div>

            <input type="text" id="captcha" name="captcha" required
                   maxlength="{{ \App\Support\Captcha::LENGTH }}" autocomplete="off" autocapitalize="characters" spellcheck="false"
                   class="field-input mt-3 max-w-[16rem] font-mono uppercase tracking-[0.3em]"
                   placeholder="Kode">
            @error('captcha') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <a href="{{ $previousUrl }}" class="btn-outline px-6 py-3">Kembali</a>
            <button type="submit" class="btn-primary flex-1 px-6 py-3">Daftar Sekarang</button>
        </div>
    </form>

    @push('scripts')
        <script>
            // "Ganti kode": muat gambar baru; server membuat kode baru sekaligus.
            (() => {
                const box = document.querySelector('[data-captcha]');
                const image = box?.querySelector('[data-captcha-image]');
                const input = box?.querySelector('#captcha');

                box?.querySelector('[data-captcha-refresh]')?.addEventListener('click', () => {
                    image.src = image.src.replace(/\?v=\d+$/, '') + '?v=' + Date.now();
                    input.value = '';
                    input.focus();
                });
            })();
        </script>
    @endpush

    <p class="mt-7 border-t border-ink-100 pt-6 text-center text-sm text-ink-500">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-brand-600 transition-colors hover:text-brand-700">Masuk di sini</a>
    </p>
@endsection
