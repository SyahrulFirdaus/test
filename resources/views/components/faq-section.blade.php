{{--
    Blok FAQ accordion + FAQPage JSON-LD.

    - Accordion memakai <details>/<summary>: dapat dibuka-tutup dengan mouse,
      Enter/Spasi pada keyboard, dibacakan pembaca layar sebagai tombol
      buka/tutup, dan tetap berfungsi tanpa JavaScript.
    - JSON-LD dibentuk dari daftar yang SAMA dengan yang tampil (lihat
      App\Support\FaqSchema), dipasang sekali per halaman di <head>.

    Props: faqs (array of [question, answer]), eyebrow, title, id.
--}}
@props([
    'faqs' => [],
    'eyebrow' => 'FAQ',
    'title' => 'Pertanyaan yang sering diajukan',
    'id' => 'faq',
])

@php
    $items = \App\Support\FaqSchema::normalize($faqs);
@endphp

@if ($items !== [])
    @once
        @push('head')
            <script type="application/ld+json">{!! \App\Support\FaqSchema::toJsonLd($items, url()->current()) !!}</script>
        @endpush
    @endonce

    <section id="{{ $id }}" class="section scroll-mt-24" data-faq>
        <div class="container-page max-w-3xl">
            <x-section-heading :eyebrow="$eyebrow" :title="$title" />

            <div class="mt-10 divide-y divide-ink-100 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                @foreach ($items as $faq)
                    <details class="group" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5 font-display text-base font-semibold text-ink-900 transition-colors hover:text-brand-600 focus-visible:bg-brand-50/60 focus-visible:text-brand-700 focus-visible:outline-none [&::-webkit-details-marker]:hidden">
                            <span>{{ $faq['question'] }}</span>
                            <x-icons.chevron-down class="h-4 w-4 shrink-0 text-ink-400 transition-transform duration-200 group-open:rotate-180 group-open:text-brand-600" aria-hidden="true" />
                        </summary>
                        <p class="px-6 pb-6 text-sm leading-relaxed text-ink-500">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
