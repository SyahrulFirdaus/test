{{--
    Satu langkah pengisian pendaftaran.

    Halaman ini tidak memuat daftar pertanyaan apa pun: isinya dibentuk dari
    tabel `registration_questions`. Langkah `account` menampilkan data akun,
    langkah lainnya menampilkan pertanyaan milik kelompok tersebut.

    Seluruh pilihan memakai radio/checkbox biasa dengan gaya lewat `peer-checked`,
    jadi formulir tetap dapat diisi dan dikirim walaupun JavaScript mati. Validasi
    yang menentukan tetap berjalan di server.
--}}
@extends('layouts.auth')

@section('title', 'Daftar')
@section('panelWidth', $stepKey === \App\Support\RegistrationFlow::STEP_ACCOUNT && $customerType === \App\Support\CustomerType::PERSONAL ? 'max-w-lg' : 'max-w-2xl')
@section('heading', 'Buat akun baru')
@section('subheading', $step['description'] ?? 'Lengkapi informasi di bawah ini untuk melanjutkan.')

@section('form')
    <div class="mt-7 border-t border-ink-100 pt-7">
        <x-register-progress
            :type-label="$typeLabel"
            :label="$step['label']"
            :number="$step['number']"
            :step-count="$stepCount"
            :progress="$progress" />
    </div>

    <form method="POST" action="{{ route('register.step.store', $stepKey) }}" class="mt-8">
        @csrf

        @if ($stepKey === \App\Support\RegistrationFlow::STEP_ACCOUNT)
            @include('auth.register-account', ['account' => $account, 'customerType' => $customerType])
        @else
            {{-- Pertanyaan dikelompokkan menjadi bagian bertajuk. Pendaftaran
                 Personal tidak memakai kategori sehingga seluruhnya jatuh ke
                 satu kelompok tanpa tajuk — tampilannya persis seperti dulu. --}}
            <div class="grid gap-9">
                @foreach ($sections as $category => $sectionQuestions)
                    <section class="grid gap-7">
                        @if ($category !== '')
                            <div class="border-b border-ink-100 pb-3">
                                <h2 class="font-display text-sm font-bold uppercase tracking-[0.12em] text-brand-700">
                                    {{ $category }}
                                </h2>
                            </div>
                        @endif

                        @foreach ($sectionQuestions as $question)
                            @include('auth.register-question', [
                                'question' => $question,
                                'answers' => $answers,
                                'prefill' => $prefill,
                            ])
                        @endforeach
                    </section>
                @endforeach
            </div>
        @endif

        <div class="mt-9 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-6">
            <a href="{{ $previousUrl }}" class="btn-outline px-6 py-3">&larr; Kembali</a>
            <button type="submit" class="btn-primary flex-1 px-6 py-3">Lanjut &rarr;</button>
        </div>
    </form>
@endsection
