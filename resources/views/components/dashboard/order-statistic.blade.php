@props([
    // ['total', 'in_progress', 'production', 'completed'] dari CustomerDashboard::insights().
    'distribution',
    'trend' => null,
    'title' => 'Order Statistic',
    'subtitle' => 'Sebaran quotation menurut tahapnya',
    'noun' => 'Quotation',
])

@php
    $angka = fn ($value) => number_format((float) $value, 0, ',', '.');

    $rings = [
        ['key' => 'completed', 'label' => 'Selesai', 'r' => 84, 'color' => 'var(--color-brand-600)', 'dot' => 'bg-brand-600'],
        ['key' => 'production', 'label' => 'Produksi', 'r' => 66, 'color' => 'var(--color-accent-500)', 'dot' => 'bg-accent-500'],
        ['key' => 'in_progress', 'label' => 'Dalam Proses', 'r' => 48, 'color' => 'var(--color-brand-400)', 'dot' => 'bg-brand-400'],
    ];

    $total = $distribution['total'];
@endphp

<section {{ $attributes->class('rounded-3xl border border-ink-100/80 bg-white p-6 shadow-card') }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="font-display text-lg font-bold text-ink-900">{{ $title }}</h3>
            <p class="mt-0.5 text-xs text-ink-400">{{ $subtitle }}</p>
        </div>
        <span class="shrink-0 whitespace-nowrap rounded-xl border border-ink-100 px-3 py-1.5 text-xs font-semibold text-ink-500">Semua waktu</span>
    </div>

    <div class="relative mx-auto mt-4 aspect-square w-full max-w-[15rem]">
        {{-- Tiap cincin terbuka 270°: dimulai di bawah, memutar searah jarum
             jam lewat kiri dan atas, berakhir di kanan. --}}
        <svg viewBox="0 0 200 200" class="h-full w-full" role="img"
             aria-label="Selesai {{ $distribution['completed'] }}, Produksi {{ $distribution['production'] }}, Dalam Proses {{ $distribution['in_progress'] }} dari {{ $total }} {{ strtolower($noun) }}">
            @foreach ($rings as $ring)
                @php
                    $circumference = 2 * M_PI * $ring['r'];
                    $track = $circumference * 0.75;
                    $share = $total > 0 ? $distribution[$ring['key']] / $total : 0;
                @endphp
                <g transform="rotate(90 100 100)" fill="none" stroke-width="12" stroke-linecap="round">
                    <circle cx="100" cy="100" r="{{ $ring['r'] }}" stroke="var(--color-ink-100)"
                            stroke-dasharray="{{ $track }} {{ $circumference }}" />
                    @if ($share > 0)
                        <circle cx="100" cy="100" r="{{ $ring['r'] }}" stroke="{{ $ring['color'] }}"
                                stroke-dasharray="{{ max(0.01, $track * $share) }} {{ $circumference }}" />
                    @endif
                </g>
            @endforeach
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
            <p class="font-display text-2xl font-bold leading-none text-ink-900">{{ $angka($total) }}</p>
            <p class="mt-1 text-[0.7rem] text-ink-400">Total {{ $noun }}</p>
            @if ($trend !== null)
                <span class="mt-1.5 inline-block rounded-full px-2 py-0.5 text-[0.65rem] font-bold {{ $trend >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                    {{ ($trend >= 0 ? '+' : '').number_format($trend, 1, ',', '.') }}%
                </span>
            @endif
        </div>
    </div>

    <ul class="mt-4 space-y-3">
        @foreach ($rings as $ring)
            @php $share = $total > 0 ? round($distribution[$ring['key']] / $total * 100, 1) : 0; @endphp
            <li class="flex items-center gap-3 text-sm">
                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full {{ $ring['dot'] }}"></span>
                <span class="flex-1 font-semibold text-ink-700">{{ $ring['label'] }}</span>
                <span class="font-display font-bold text-ink-900">{{ $angka($distribution[$ring['key']]) }}</span>
                <span class="w-14 rounded-full bg-ink-50 py-0.5 text-center text-[0.65rem] font-bold text-ink-600">
                    {{ number_format($share, 1, ',', '.') }}%
                </span>
            </li>
        @endforeach
    </ul>
</section>
