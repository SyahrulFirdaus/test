@extends('layouts.app')

{{-- Halaman Terima Kasih setelah Form Kontak/Miniatur (tipe=kontak) atau
     quotation (tipe=quote) terkirim. Tidak diindeks mesin pencari. --}}

@section('title', 'Terima Kasih')
@section('description', 'Terima kasih, permintaan Anda sudah kami terima.')
@section('robots', 'noindex, nofollow')

@php
    $isQuote = $type === 'quote';
    $conversion = config('services.google_ads.lead_conversion');
@endphp

@section('content')
    <section class="relative overflow-hidden bg-ink-950 pb-24 pt-40 md:pb-32 md:pt-48">
        <div class="blueprint-grid-dark absolute inset-0" aria-hidden="true"></div>
        <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-brand-700/35 blur-3xl" aria-hidden="true"></div>

        <div class="container-page relative">
            <div class="mx-auto max-w-2xl rounded-3xl border border-white/10 bg-white p-8 text-center shadow-card sm:p-12" data-hero-reveal>
                <span class="mx-auto inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    <x-icons.check class="h-8 w-8" />
                </span>

                <h1 class="mt-6 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Terima kasih!</h1>

                <p class="mt-4 text-base leading-relaxed text-ink-600">
                    @if ($isQuote)
                        Permintaan penawaran Anda sudah kami terima dan sedang ditinjau tim kami.
                    @else
                        Pesan dan kebutuhan Anda sudah kami terima. Tim kami akan meninjaunya dan menghubungi Anda melalui email atau WhatsApp.
                    @endif
                </p>

                <div class="mt-6 rounded-2xl border border-ink-100 bg-ink-50 px-5 py-4 text-sm text-ink-700" data-reply-estimate>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Estimasi waktu balasan</p>
                    <p class="mt-1.5 font-semibold">{{ $replyEstimate }}</p>
                    <p class="mt-1 text-xs text-ink-400">Jam kerja: {{ $company->operational_hours }}</p>
                </div>

                @if ($quotation)
                    <p class="mt-6 text-sm text-ink-500">Nomor tracking Anda</p>
                    <p class="mt-1 font-mono text-2xl font-bold tracking-[0.2em] text-brand-600">{{ $quotation->tracking_number }}</p>
                @endif

                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ wa_link() }}" target="_blank" rel="noopener noreferrer" data-wa-link data-cta="thanks-wa" class="btn-primary">
                        <x-icons.whatsapp class="h-4 w-4" />
                        Chat via WhatsApp
                    </a>

                    @if ($quotation)
                        <a href="{{ route('tracking.show', $quotation->tracking_number) }}" class="btn-outline" data-cta="thanks-tracking">Lacak Penawaran</a>
                    @else
                        <a href="{{ route('tracking.index') }}" class="btn-outline" data-cta="thanks-tracking">Lacak Penawaran</a>
                    @endif
                </div>

                @if ($quotation)
                    <a href="{{ route('tracking.document', $quotation->tracking_number) }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600 hover:text-brand-700">
                        Unduh bukti penawaran (PDF)
                    </a>
                @else
                    <p class="mt-4 text-xs text-ink-400">
                        Sudah punya nomor tracking dari penawaran sebelumnya? Cek statusnya di halaman
                        <a href="{{ route('tracking.index') }}" class="font-semibold text-brand-600 hover:text-brand-700">Tracking Penawaran</a>.
                    </p>
                @endif

                <a href="{{ route('home') }}" class="mt-8 block text-sm font-semibold text-ink-500 hover:text-brand-600">Kembali ke beranda</a>
            </div>
        </div>
    </section>
@endsection

@if ($leadEvent)
    @push('scripts')
        {{-- Konversi lead formulir: sekali per kiriman yang tersimpan (lihat ThanksController). --}}
        <script>
            (function () {
                var params = @json($leadEvent);

                window.nusamaTrack('generate_lead', params);

                @if (filled($conversion))
                    window.gtag('event', 'conversion', { send_to: @json($conversion) });
                @endif
            })();
        </script>
    @endpush
@endif
