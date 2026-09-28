@props([
    'label',
    'value',
    'note' => null,
    'icon' => 'layers',
    // Perubahan bulan ini terhadap bulan lalu, dalam persen. Null berarti
    // tidak ada pembanding, dan lencananya tidak ditampilkan.
    'trend' => null,
    // Lencana bebas ['text' => ..., 'class' => ...]; menggantikan `trend`.
    'pill' => null,
    // Kartu utama berwarna brand.
    'hero' => false,
])

@php
    if (! $pill && $trend !== null) {
        $pill = [
            'text' => ($trend >= 0 ? '+' : '').number_format($trend, 1, ',', '.').'%',
            'class' => $trend >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700',
        ];
    }
@endphp

<div {{ $attributes->class([
    'relative overflow-hidden rounded-3xl p-6',
    'bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-glow' => $hero,
    'border border-ink-100/80 bg-white shadow-card' => ! $hero,
]) }}>
    @if ($hero)
        {{-- Lingkaran dekoratif di sudut kartu utama. --}}
        <span class="pointer-events-none absolute -right-10 -top-12 h-40 w-40 rounded-full bg-white/10"></span>
        <span class="pointer-events-none absolute -bottom-16 right-10 h-32 w-32 rounded-full bg-white/5"></span>
    @endif

    <div class="relative flex items-start justify-between gap-3">
        <span @class([
            'inline-flex h-11 w-11 items-center justify-center rounded-2xl',
            'bg-white/15 text-white ring-1 ring-white/25' => $hero,
            'border border-ink-100 bg-ink-50 text-ink-700' => ! $hero,
        ])>
            <x-dynamic-component :component="'icons.'.$icon" class="h-5 w-5" />
        </span>

        @if ($pill)
            <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-[0.7rem] font-bold {{ $pill['class'] }}">
                {{ $pill['text'] }}
            </span>
        @endif
    </div>

    <p class="relative mt-8 text-sm font-semibold {{ $hero ? 'text-white/85' : 'text-ink-700' }}">{{ $label }}</p>

    <div class="relative mt-1.5 flex flex-wrap items-end gap-x-3 gap-y-1">
        <p class="font-display text-[1.75rem] font-bold leading-none {{ $hero ? 'text-white' : 'text-ink-900' }}">{{ $value }}</p>

        @if ($note)
            <p class="max-w-[9rem] text-xs leading-snug {{ $hero ? 'text-white/70' : 'text-ink-400' }}">{{ $note }}</p>
        @endif
    </div>
</div>
