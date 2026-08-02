@props([
    'service',
    'delay' => 0,
    'href' => null,
])

<article {{ $attributes->merge(['class' => 'card-interactive group']) }} data-aos="fade-up" data-aos-delay="{{ $delay }}">
    {{-- Ilustrasi --}}
    <div class="relative aspect-[16/10] overflow-hidden bg-gradient-to-br from-ink-50 to-brand-50">
        <img src="{{ asset($service->image) }}"
             alt="Ilustrasi layanan {{ $service->title }}"
             loading="lazy"
             decoding="async"
             width="640" height="400"
             class="h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105">

        <div class="absolute left-5 top-5 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white shadow-[0_12px_28px_-12px_rgba(149,39,29,0.95)]">
            <x-dynamic-component :component="'icons.'.$service->icon" class="h-6 w-6" />
        </div>
    </div>

    <div class="flex flex-1 flex-col p-6 sm:p-7">
        <h3 class="text-xl font-bold tracking-tight text-ink-900 transition-colors duration-300 group-hover:text-brand-600">
            {{ $service->title }}
        </h3>
        <p class="mt-1.5 text-sm font-semibold text-brand-600">{{ $service->tagline }}</p>

        <p class="mt-4 flex-1 text-sm leading-relaxed text-ink-500">
            {{ $service->excerpt }}
        </p>

        @if (filled($service->highlights))
            <ul class="mt-5 space-y-2.5 border-t border-ink-100 pt-5">
                @foreach ($service->highlights as $highlight)
                    <li class="flex items-start gap-2.5 text-sm text-ink-600">
                        <x-icons.check class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                        <span>{{ $highlight }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($href)
            <a href="{{ $href }}"
               class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition-colors hover:text-brand-800">
                Selengkapnya
                <x-icons.arrow-right class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
            </a>
        @endif
    </div>
</article>
