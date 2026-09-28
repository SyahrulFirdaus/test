@props([
    // Daftar bulan dari CustomerDashboard::insights()['monthly'].
    'monthly',
    'title' => 'Quotation Activity',
    'subtitle' => 'Quotation yang dibuat dan yang berlanjut menjadi pesanan',
    'noun' => 'quotation',
])

@php
    $angka = fn ($value) => number_format((float) $value, 0, ',', '.');

    // Sumbu Y: empat garis bantu dengan kelipatan yang rapi.
    $peak = max(1, collect($monthly)->max('quotations'));
    $step = (int) max(1, ceil($peak / 4));
    $axisMax = $step * 4;
@endphp

<section {{ $attributes->class('rounded-3xl border border-ink-100/80 bg-white p-6 shadow-card') }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-display text-lg font-bold text-ink-900">{{ $title }}</h3>
            <p class="mt-0.5 text-xs text-ink-400">{{ $subtitle }}</p>
        </div>
        <span class="shrink-0 whitespace-nowrap rounded-xl border border-ink-100 px-3 py-1.5 text-xs font-semibold text-ink-500">{{ count($monthly) }} bulan terakhir</span>
    </div>

    <div class="mt-5 flex items-center gap-5 text-xs font-semibold text-ink-500">
        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-ink-200"></span>{{ ucfirst($noun) }}</span>
        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-brand-600"></span>Jadi Pesanan</span>
    </div>

    <div class="mt-4 flex gap-3">
        {{-- Label sumbu Y. --}}
        <div class="flex h-56 flex-col justify-between pb-6 text-right text-[0.65rem] font-semibold text-ink-400">
            @for ($tick = 4; $tick >= 0; $tick--)
                <span class="leading-none">{{ $angka($tick * $step) }}</span>
            @endfor
        </div>

        <div class="relative flex-1">
            {{-- Garis bantu. --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 flex flex-col justify-between" style="height: calc(100% - 1.5rem)">
                @for ($tick = 0; $tick <= 4; $tick++)
                    <span class="block border-t border-dashed border-ink-100"></span>
                @endfor
            </div>

            <div class="relative grid h-56" style="grid-template-columns: repeat({{ count($monthly) }}, minmax(0, 1fr))">
                @foreach ($monthly as $month)
                    <div class="group relative flex flex-col items-center outline-none" tabindex="0"
                         aria-label="{{ $month['month'] }}: {{ $month['quotations'] }} {{ $noun }}, {{ $month['orders'] }} jadi pesanan">
                        {{-- Tooltip: muncul saat bulan disorot atau difokus. Warna
                             tulisannya diambil dari token agar tetap kontras
                             saat tangga ink dibalik pada mode gelap. --}}
                        <div class="pointer-events-none absolute -top-2 left-1/2 z-10 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-xl bg-ink-900 px-3 py-2 text-[0.7rem] opacity-0 shadow-card transition-opacity group-hover:opacity-100 group-focus:opacity-100"
                             style="color: var(--color-ink-50)">
                            <p class="font-bold">{{ $month['month'] }}</p>
                            <p class="mt-1 flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-ink-300"></span>{{ $angka($month['quotations']) }} {{ $noun }}</p>
                            <p class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-400"></span>{{ $angka($month['orders']) }} jadi pesanan</p>
                        </div>

                        <div class="flex w-full flex-1 items-end justify-center gap-1 sm:gap-1.5">
                            <span class="w-3 rounded-t-full rounded-b-md bg-ink-200 transition-colors group-hover:bg-ink-300 sm:w-5"
                                  style="height: {{ $month['quotations'] / $axisMax * 100 }}%; min-height: {{ $month['quotations'] ? '6px' : '0' }}"></span>
                            <span class="w-3 rounded-t-full rounded-b-md bg-brand-600 transition-colors group-hover:bg-brand-500 sm:w-5"
                                  style="height: {{ $month['orders'] / $axisMax * 100 }}%; min-height: {{ $month['orders'] ? '6px' : '0' }}"></span>
                        </div>

                        <span class="mt-2 h-4 text-[0.7rem] font-semibold text-ink-400">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
