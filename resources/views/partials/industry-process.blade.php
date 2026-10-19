{{--
    Alur kerja pemesanan (config/industry-guide.php → process).

    `$technologies` berisi teknologi yang relevan; durasi tahap Produksi
    diambil dari spesifikasi "Waktu Produksi" masing-masing. Bila kosong,
    tahap itu cukup menyebut bahwa lamanya bergantung pada teknologi.
--}}
<ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($process as $step)
        <li class="relative flex flex-col rounded-2xl border border-ink-100 bg-white p-6 shadow-card" data-aos="fade-up" data-aos-delay="{{ ($loop->index % 3) * 60 }}">
            <span class="font-mono text-xs font-semibold tracking-[0.2em] text-brand-600">LANGKAH {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            <h3 class="mt-3 font-display text-lg font-bold text-ink-900">{{ $step['title'] }}</h3>
            <p class="mt-2 flex-1 text-sm leading-relaxed text-ink-500">{{ $step['description'] }}</p>

            @if ($step['duration'] === 'production')
                <div class="mt-4 space-y-1.5 border-t border-ink-100 pt-4">
                    @forelse ($technologies as $technology)
                        <p class="flex items-center justify-between gap-3 text-xs">
                            <span class="font-display font-bold text-ink-700">{{ $technology->code }}</span>
                            <span class="font-semibold text-brand-600">{{ $technology->specs['Waktu Produksi'] ?? 'Sesuai penawaran' }}</span>
                        </p>
                    @empty
                        <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-600">
                            <x-icons.clock class="h-3.5 w-3.5" />
                            Bergantung teknologi
                        </p>
                    @endforelse
                </div>
            @elseif ($step['duration'])
                <p class="mt-4 inline-flex items-center gap-1.5 border-t border-ink-100 pt-4 text-xs font-semibold text-brand-600">
                    <x-icons.clock class="h-3.5 w-3.5" />
                    {{ $step['duration'] }}
                </p>
            @endif
        </li>
    @endforeach
</ol>
