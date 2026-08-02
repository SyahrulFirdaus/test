@extends('layouts.app')

@section('title', 'Technologies')
@section('description', 'Teknologi 3D printing yang kami operasikan: FDM, SLA, MJF, dan SLM — lengkap dengan kelebihan, material, spesifikasi, serta contoh aplikasinya.')

@section('content')

    <x-page-hero
        eyebrow="Teknologi"
        current="Technologies"
        title='Empat teknologi cetak, <span class="text-brand-400">satu standar kualitas</span>'
        description="Tidak ada satu teknologi yang unggul untuk semua kebutuhan. Halaman ini menjelaskan cara kerja, kelebihan, dan contoh penerapan tiap teknologi agar Anda dapat memilih dengan yakin.">
        <x-slot:actions>
            <a href="#perbandingan" class="btn-primary w-full sm:w-auto">
                Lihat Perbandingan
                <x-icons.arrow-down class="h-4 w-4" />
            </a>
            <a href="{{ route('services') }}" class="btn-ghost-light w-full sm:w-auto">Layanan Kami</a>
        </x-slot:actions>
    </x-page-hero>

    {{-- ===================== NAVIGASI CEPAT ===================== --}}
    <section class="border-b border-ink-100 bg-white/80 py-6 backdrop-blur">
        <div class="container-page">
            <div class="flex flex-wrap items-center justify-center gap-3">
                @foreach ($technologies as $technology)
                    <a href="#{{ $technology->slug }}"
                       class="inline-flex items-center gap-2.5 rounded-full border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-300 hover:text-brand-600">
                        <span class="inline-block h-2.5 w-2.5 rounded-full" style="background-color: {{ $technology->accent_color }}"></span>
                        {{ $technology->code }}
                        <span class="hidden text-ink-400 sm:inline">&mdash; {{ $technology->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== DETAIL TIAP TEKNOLOGI ===================== --}}
    <section class="section">
        <div class="container-page space-y-24 md:space-y-32">
            @foreach ($technologies as $technology)
                <x-technology-block :technology="$technology" :reverse="$loop->odd" />
            @endforeach
        </div>
    </section>

    {{-- ===================== TABEL PERBANDINGAN ===================== --}}
    <section id="perbandingan" class="section scroll-mt-24 bg-ink-50/70">
        <div class="container-page">
            <x-section-heading
                eyebrow="Perbandingan"
                title='Teknologi mana yang cocok untuk part Anda?'
                description="Ringkasan cepat untuk membantu Anda mempersempit pilihan sebelum berdiskusi dengan tim kami." />

            <div class="mt-12 overflow-x-auto rounded-2xl border border-ink-100 bg-white shadow-card" data-aos="fade-up">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <caption class="sr-only">Perbandingan teknologi FDM, SLA, MJF, dan SLM</caption>
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80">
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Teknologi</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Ketebalan Layer</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Toleransi</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Build Volume</th>
                            <th scope="col" class="px-6 py-4 font-display text-xs font-bold uppercase tracking-[0.14em] text-ink-500">Paling Cocok Untuk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($technologies as $technology)
                            <tr class="transition-colors hover:bg-brand-50/50">
                                <th scope="row" class="px-6 py-5">
                                    <span class="flex items-center gap-3">
                                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl font-display text-xs font-bold text-white"
                                              style="background-color: {{ $technology->accent_color }}">{{ $technology->code }}</span>
                                        <span class="font-semibold text-ink-900">{{ $technology->name }}</span>
                                    </span>
                                </th>
                                <td class="px-6 py-5 text-ink-600">{{ $technology->specs['Ketebalan Layer'] ?? '—' }}</td>
                                <td class="px-6 py-5 text-ink-600">{{ $technology->specs['Toleransi Dimensi'] ?? '—' }}</td>
                                <td class="px-6 py-5 text-ink-600">{{ $technology->specs['Build Volume'] ?? '—' }}</td>
                                <td class="px-6 py-5 text-ink-600">{{ $technology->applications[0] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-6 text-center text-sm text-ink-400" data-aos="fade-up">
                Masih ragu? Kirimkan file Anda &mdash; kami bantu pilihkan teknologi yang paling ekonomis untuk kebutuhan tersebut.
            </p>
        </div>
    </section>

    <x-cta-band
        eyebrow="Butuh Rekomendasi"
        title="Belum yakin teknologi mana yang tepat?"
        description="Kirimkan file dan ceritakan fungsi part yang Anda butuhkan. Kami akan merekomendasikan kombinasi teknologi serta material yang paling sesuai dengan anggaran dan tenggat Anda."
        primary-label="Lihat Layanan Kami" />

@endsection
