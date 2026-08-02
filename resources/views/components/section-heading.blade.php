@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'align' => 'center',
    'theme' => 'light',
])

@php
    $wrapper = $align === 'center' ? 'mx-auto max-w-3xl text-center' : 'max-w-2xl text-left';
    $titleColor = $theme === 'dark' ? 'text-white' : 'text-ink-900';
    $descColor = $theme === 'dark' ? 'text-ink-300' : 'text-ink-500';
    $eyebrowClass = $theme === 'dark'
        ? 'inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-brand-300'
        : 'eyebrow';
@endphp

<div {{ $attributes->merge(['class' => $wrapper]) }}>
    @if ($eyebrow)
        <span class="{{ $eyebrowClass }}" data-aos="fade-up">
            <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
            {{ $eyebrow }}
        </span>
    @endif

    <h2 class="mt-5 text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-[2.6rem] {{ $titleColor }}"
        data-aos="fade-up" data-aos-delay="60">
        {!! $title !!}
    </h2>

    @if ($description)
        <p class="mt-5 text-base leading-relaxed sm:text-lg {{ $descColor }}" data-aos="fade-up" data-aos-delay="120">
            {{ $description }}
        </p>
    @endif
</div>
