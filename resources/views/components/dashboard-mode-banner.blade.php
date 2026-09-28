@props(['mode', 'label'])

{{--
    Penanda mode Personal/Business pada dashboard pengelola.

    Seluruh angka pada halaman yang memuatnya hanya memotret SATU segmen
    pelanggan, jadi mode yang sedang aktif harus terbaca sekali lihat — bukan
    hanya dari posisi switch kecil di header.
--}}
@php $isBusiness = $mode === \App\Support\DashboardMode::BUSINESS; @endphp

<div {{ $attributes->class([
    'mb-5 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-2xl border-l-4 px-5 py-3.5',
    'border-brand-600 bg-brand-50' => $isBusiness,
    'border-ink-300 bg-ink-50' => ! $isBusiness,
]) }}>
    <span @class([
        'rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-[0.12em] text-white',
        'bg-brand-600' => $isBusiness,
        'bg-ink-600' => ! $isBusiness,
    ])>{{ $label }}</span>

    <p class="text-sm font-bold text-ink-900">Dashboard {{ $label }}</p>

    <p class="text-xs text-ink-500">
        Seluruh angka di halaman ini hanya mencakup pelanggan
        <span class="font-semibold text-ink-700">{{ $label }}</span>.
        Gunakan switch di header untuk berpindah mode.
    </p>
</div>
