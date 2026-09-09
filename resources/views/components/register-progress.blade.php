{{--
    Penanda kemajuan pendaftaran.

    Dipakai halaman langkah maupun halaman ringkasan supaya keduanya menampilkan
    bar yang sama. Nomor langkah hanya muncul pada kelompok pertanyaan — langkah
    data akun dan ringkasan tidak ikut dinomori agar penomorannya sesuai dengan
    jumlah kelompok pertanyaan yang sebenarnya.
--}}
@props([
    'typeLabel',
    'label',
    'number' => null,
    'stepCount' => 0,
    'progress' => 0,
])

<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-brand-700">
            {{ $typeLabel }}
        </span>

        @if ($number && $stepCount > 1)
            <span class="text-xs font-semibold text-ink-400">Step {{ $number }} dari {{ $stepCount }}</span>
        @endif
    </div>

    <p class="mt-4 font-display text-xl font-bold tracking-tight text-ink-900">{{ $label }}</p>

    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-ink-100"
         role="progressbar"
         aria-valuenow="{{ $progress }}"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-label="Kemajuan pendaftaran">
        <div class="h-full rounded-full bg-brand-600 transition-all duration-500 ease-out"
             style="width: {{ $progress }}%"></div>
    </div>

    <p class="mt-2 text-right text-xs font-semibold text-ink-400">{{ $progress }}%</p>
</div>
