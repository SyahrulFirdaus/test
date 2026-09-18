{{--
    Hero halaman dalam.

    Tanpa `scene`: latar gelap bergrid seperti semula.
    Dengan `scene` (services | technologies | about): latar 3D bergaya hero
    halaman utama — components/scene-3d — dengan objek 3D milik halaman itu
    sendiri (resources/js/modules/hero-scenes.js). Objeknya digeser ke kanan
    supaya teks di kiri tetap lega dan terbaca.
--}}
@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'current' => null,
    'scene' => null,
])

@if ($scene)
    {{-- Di ponsel tingginya mengikuti isi (min-h) supaya tombol tidak terpotong. --}}
    <section class="relative flex min-h-[600px] items-center overflow-hidden bg-brand-950 pb-16 pt-40 sm:min-h-[620px] md:h-[660px] md:min-h-0 md:pb-0 md:pt-24 xl:h-[700px]"
             data-hero-3d>
        <x-scene-3d :variant="$scene" shift-x="0.25" shift-y="-0.04" scale="1.65" />

        {{-- Sisi kiri sedikit digelapkan agar teks kontras di atas latar 3D. --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-brand-950/75 via-brand-950/30 to-transparent" aria-hidden="true"></div>

        <div class="container-page relative w-full">
            @if ($current)
                <nav aria-label="Breadcrumb" class="mb-7" data-aos="fade-up">
                    <ol class="flex items-center gap-2 text-xs font-medium text-white/60">
                        <li><a href="{{ route('home') }}" class="transition-colors hover:text-brand-300">Home</a></li>
                        <li aria-hidden="true" class="text-white/30">/</li>
                        <li class="text-brand-300" aria-current="page">{{ $current }}</li>
                    </ol>
                </nav>
            @endif

            <div class="max-w-2xl">
                @if ($eyebrow)
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-brand-300 backdrop-blur"
                          data-aos="fade-up">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        {{ $eyebrow }}
                    </span>
                @endif

                <h1 class="mt-6 text-4xl font-bold leading-[1.1] tracking-tight text-white drop-shadow-lg sm:text-5xl lg:text-6xl [&_span]:text-brand-300"
                    data-aos="fade-up" data-aos-delay="80">
                    {!! $title !!}
                </h1>

                @if ($description)
                    <p class="mt-6 max-w-xl text-base leading-relaxed text-white/80 drop-shadow sm:text-lg"
                       data-aos="fade-up" data-aos-delay="140">
                        {{ $description }}
                    </p>
                @endif

                @if (isset($actions))
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row" data-aos="fade-up" data-aos-delay="200">
                        {{ $actions }}
                    </div>
                @endif
            </div>
        </div>
    </section>

    @once
        @push('scripts')
            {{-- Menyalakan kanvas 3D pada hero (data-auth-scene). --}}
            @vite('resources/js/auth-scene.js')
        @endpush
    @endonce
@else
    <section class="relative overflow-hidden bg-ink-950 pb-20 pt-44 md:pb-28 md:pt-52">
        <div class="blueprint-grid-dark absolute inset-0" aria-hidden="true"></div>
        <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-brand-700/35 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-brand-900/50 blur-3xl" aria-hidden="true"></div>

        <div class="container-page relative">
            @if ($current)
                <nav aria-label="Breadcrumb" class="mb-7" data-aos="fade-up">
                    <ol class="flex items-center gap-2 text-xs font-medium text-ink-400">
                        <li><a href="{{ route('home') }}" class="transition-colors hover:text-brand-300">Home</a></li>
                        <li aria-hidden="true" class="text-ink-600">/</li>
                        <li class="text-brand-300" aria-current="page">{{ $current }}</li>
                    </ol>
                </nav>
            @endif

            <div class="max-w-3xl">
                @if ($eyebrow)
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-brand-300"
                          data-aos="fade-up">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        {{ $eyebrow }}
                    </span>
                @endif

                <h1 class="mt-6 text-4xl font-bold leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-6xl"
                    data-aos="fade-up" data-aos-delay="80">
                    {!! $title !!}
                </h1>

                @if ($description)
                    <p class="mt-6 max-w-2xl text-base leading-relaxed text-ink-300 sm:text-lg"
                       data-aos="fade-up" data-aos-delay="140">
                        {{ $description }}
                    </p>
                @endif

                @if (isset($actions))
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row" data-aos="fade-up" data-aos-delay="200">
                        {{ $actions }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
