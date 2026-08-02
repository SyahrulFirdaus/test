@props([
    'technology',
    'reverse' => false,
])

<article id="{{ $technology->slug }}" class="scroll-mt-28">
    <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
        {{-- Visual --}}
        <div class="{{ $reverse ? 'lg:order-2' : '' }}" data-aos="{{ $reverse ? 'fade-left' : 'fade-right' }}">
            <div class="relative">
                <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-brand-100 to-transparent opacity-70 blur-2xl" aria-hidden="true"></div>
                <div class="relative overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                    <img src="{{ asset($technology->image) }}"
                         alt="Ilustrasi teknologi {{ $technology->code }} — {{ $technology->name }}"
                         loading="lazy"
                         decoding="async"
                         width="720" height="540"
                         class="aspect-[4/3] w-full object-cover">
                </div>

                {{-- Kartu spesifikasi melayang --}}
                @if (filled($technology->specs))
                    <div class="relative z-10 -mt-10 ml-4 mr-4 rounded-2xl border border-ink-100 bg-white/95 p-5 shadow-card-hover backdrop-blur sm:ml-8 sm:mr-8">
                        <dl class="grid grid-cols-2 gap-4">
                            @foreach ($technology->specs as $label => $value)
                                <div>
                                    <dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-ink-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        </div>

        {{-- Teks --}}
        <div class="{{ $reverse ? 'lg:order-1' : '' }}" data-aos="{{ $reverse ? 'fade-right' : 'fade-left' }}">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl font-display text-lg font-bold text-white shadow-[0_14px_30px_-14px_rgba(149,39,29,0.9)]"
                      style="background-color: {{ $technology->accent_color }}">
                    {{ $technology->code }}
                </span>
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $technology->name }}</h2>
                    <p class="mt-1 text-sm font-semibold text-brand-600">{{ $technology->tagline }}</p>
                </div>
            </div>

            <p class="mt-6 text-base leading-relaxed text-ink-600">
                {{ $technology->description }}
            </p>

            <div class="mt-8 grid gap-8 sm:grid-cols-2">
                <div>
                    <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.14em] text-ink-900">
                        <x-icons.spark class="h-4 w-4 text-brand-600" />
                        Kelebihan
                    </h3>
                    <ul class="mt-4 space-y-3">
                        @foreach ($technology->advantages as $advantage)
                            <li class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-600">
                                <x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                                <span>{{ $advantage }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.14em] text-ink-900">
                        <x-icons.cube class="h-4 w-4 text-brand-600" />
                        Contoh Aplikasi
                    </h3>
                    <ul class="mt-4 space-y-3">
                        @foreach ($technology->applications as $application)
                            <li class="flex items-start gap-2.5 text-sm leading-relaxed text-ink-600">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                                <span>{{ $application }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @if (filled($technology->materials))
                <div class="mt-8 border-t border-ink-100 pt-6">
                    <h3 class="text-xs font-bold uppercase tracking-[0.14em] text-ink-400">Material Tersedia</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($technology->materials as $material)
                            <span class="rounded-full border border-ink-200 bg-ink-50 px-3 py-1.5 text-xs font-semibold text-ink-600">
                                {{ $material }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</article>
