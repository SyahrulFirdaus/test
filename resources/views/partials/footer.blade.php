@php
    $socialIcons = [
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'facebook' => 'Facebook',
    ];
@endphp

<footer class="relative overflow-hidden bg-ink-950 text-ink-300">
    <div class="blueprint-grid-dark absolute inset-0 opacity-60" aria-hidden="true"></div>
    <div class="absolute -left-32 -top-32 h-80 w-80 rounded-full bg-brand-700/25 blur-3xl" aria-hidden="true"></div>

    <div class="container-page relative">
        <div class="grid gap-12 py-16 md:py-20 lg:grid-cols-12">
            {{-- Identitas --}}
            <div class="lg:col-span-4">
                <div class="flex items-start gap-3">
                    <x-logo-mark class="h-12 w-12 shrink-0" />
                    <span class="flex flex-col gap-1 leading-tight">
                        <span class="font-display text-xl font-bold text-white">{{ $company->name }}</span>
                        <span class="text-[0.7rem] font-semibold uppercase tracking-[0.1em] text-brand-300">{{ $company->tagline }}</span>
                    </span>
                </div>

                <p class="mt-6 max-w-sm text-sm leading-relaxed text-ink-400">
                    {{ $company->short_description }}
                </p>

                @if (filled($company->socials))
                    <div class="mt-7 flex flex-wrap gap-3">
                        @foreach ($company->socials as $platform => $url)
                            @continue(! isset($socialIcons[$platform]))
                            <a href="{{ $url }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-ink-300 transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-400 hover:bg-brand-600 hover:text-white"
                               aria-label="{{ $socialIcons[$platform] }} {{ $company->name }}">
                                <x-dynamic-component :component="'icons.'.$platform" class="h-5 w-5" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Navigasi --}}
            <div class="sm:col-span-1 lg:col-span-2">
                <h2 class="font-display text-sm font-semibold uppercase tracking-[0.16em] text-white">Menu</h2>
                <ul class="mt-5 space-y-3 text-sm">
                    @foreach ([['Home', 'home'], ['Services', 'services'], ['Technologies', 'technologies'], ['3D Models', 'models'], ['About', 'about'], ['Tracking Penawaran', 'tracking.index']] as [$label, $route])
                        <li>
                            <a href="{{ route($route) }}" class="inline-flex items-center gap-2 text-ink-400 transition-colors hover:text-brand-300">
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Layanan --}}
            <div class="sm:col-span-1 lg:col-span-3">
                <h2 class="font-display text-sm font-semibold uppercase tracking-[0.16em] text-white">Layanan</h2>
                <ul class="mt-5 space-y-3 text-sm">
                    @foreach ($footerServices as $service)
                        <li>
                            <a href="{{ route('services') }}#{{ $service->slug }}" class="text-ink-400 transition-colors hover:text-brand-300">
                                {{ $service->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Kontak --}}
            <div class="lg:col-span-3">
                <h2 class="font-display text-sm font-semibold uppercase tracking-[0.16em] text-white">Kontak</h2>
                <ul class="mt-5 space-y-4 text-sm">
                    <li class="flex gap-3">
                        <x-icons.map-pin class="mt-0.5 h-5 w-5 shrink-0 text-brand-400" />
                        <span class="text-ink-400">{{ $company->address }}<br>{{ $company->city }}</span>
                    </li>
                    @if ($company->phone)
                        <li class="flex gap-3">
                            <x-icons.phone class="mt-0.5 h-5 w-5 shrink-0 text-brand-400" />
                            <a href="tel:{{ preg_replace('/\s+/', '', $company->phone) }}" class="text-ink-400 transition-colors hover:text-brand-300">{{ $company->phone }}</a>
                        </li>
                    @endif
                    @if ($company->email)
                        <li class="flex gap-3">
                            <x-icons.mail class="mt-0.5 h-5 w-5 shrink-0 text-brand-400" />
                            <a href="mailto:{{ $company->email }}" class="break-all text-ink-400 transition-colors hover:text-brand-300">{{ $company->email }}</a>
                        </li>
                    @endif
                    @if ($company->operational_hours)
                        <li class="flex gap-3">
                            <x-icons.clock class="mt-0.5 h-5 w-5 shrink-0 text-brand-400" />
                            <span class="text-ink-400">{{ $company->operational_hours }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="flex flex-col items-center justify-between gap-4 border-t border-white/10 py-7 text-xs text-ink-500 sm:flex-row">
            <p>&copy; {{ now()->year }} {{ $company->legal_name ?: $company->name }}. Seluruh hak cipta dilindungi.</p>
            <p class="flex items-center gap-2">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                Dibangun dengan Laravel {{ \Illuminate\Foundation\Application::VERSION }}
            </p>
        </div>
    </div>
</footer>
