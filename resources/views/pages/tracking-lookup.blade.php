@extends('layouts.app')

@section('title', 'Tracking Penawaran')
@section('description', 'Masukkan nomor tracking untuk memantau perkembangan permintaan penawaran 3D printing Anda.')

@section('content')

    <x-page-hero
        eyebrow="Tracking Penawaran"
        current="Tracking"
        title='Pantau perkembangan <span class="text-brand-400">permintaan penawaran</span> Anda'
        description="Masukkan nomor tracking yang Anda terima setelah mengirim permintaan penawaran, atau pindai QR code pada dokumen bukti penawaran." />

    <section class="section">
        <div class="container-page">
            <div class="mx-auto max-w-xl">
                <div class="rounded-3xl border border-ink-100 bg-white p-8 shadow-card sm:p-10" data-aos="fade-up">
                    <form method="POST" action="{{ route('tracking.lookup') }}">
                        @csrf

                        <label for="tracking_number" class="field-label">Nomor Tracking</label>
                        <input type="text"
                               id="tracking_number"
                               name="tracking_number"
                               value="{{ old('tracking_number') }}"
                               class="field-input font-mono uppercase"
                               placeholder="QTN-20260729-A7K2QX"
                               autocomplete="off"
                               autofocus
                               required
                               maxlength="40">

                        @error('tracking_number')
                            <p class="field-error">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="btn-primary mt-6 w-full">
                            Lacak Penawaran
                            <x-icons.arrow-right class="h-4 w-4" />
                        </button>
                    </form>

                    <div class="mt-8 border-t border-ink-100 pt-6">
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-ink-400">Nomor tracking Anda ada di mana?</p>
                        <ul class="mt-3 space-y-2.5 text-sm text-ink-600">
                            @foreach ([
                                'Ditampilkan tepat setelah permintaan penawaran berhasil dikirim.',
                                'Tercantum pada dokumen Bukti Permintaan Penawaran (PDF) yang Anda unduh.',
                                'Dapat dibuka langsung dengan memindai QR code pada dokumen tersebut.',
                            ] as $hint)
                                <li class="flex gap-2.5">
                                    <x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                                    <span>{{ $hint }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <p class="mt-6 text-center text-sm text-ink-500" data-aos="fade-up">
                    Belum pernah mengirim permintaan?
                    <a href="{{ route('models') }}" class="font-semibold text-brand-600 transition-colors hover:text-brand-800">
                        Cek model Anda dulu di halaman 3D Models
                    </a>
                </p>
            </div>
        </div>
    </section>

@endsection
