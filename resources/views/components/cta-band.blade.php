@props([
    'eyebrow' => 'Mulai Proyek Anda',
    'title' => 'Punya file 3D yang siap dicetak?',
    'description' => 'Kirimkan file dan kebutuhan Anda. Tim engineer kami akan meninjaunya lalu memberikan rekomendasi teknologi, material, serta estimasi biaya dan waktu pengerjaan.',
    'primaryLabel' => 'Lihat Layanan Kami',
    'primaryHref' => null,
    'secondaryLabel' => 'Hubungi Tim Kami',
    'secondaryHref' => null,
])

<section class="section">
    <div class="container-page">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-900 px-6 py-14 shadow-glow sm:px-12 md:py-20"
             data-aos="zoom-in">
            <div class="blueprint-grid-dark absolute inset-0" aria-hidden="true"></div>
            <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full bg-accent-500/20 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-24 -left-10 h-72 w-72 rounded-full bg-brand-950/40 blur-3xl" aria-hidden="true"></div>

            <div class="relative mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-white/90">
                    {{ $eyebrow }}
                </span>

                <h2 class="mt-6 text-3xl font-bold leading-tight tracking-tight text-white sm:text-4xl md:text-[2.75rem]">
                    {{ $title }}
                </h2>

                <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-white/80 sm:text-lg">
                    {{ $description }}
                </p>

                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ $primaryHref ?? route('services') }}"
                       class="btn w-full bg-white text-brand-700 hover:-translate-y-0.5 hover:bg-brand-50 sm:w-auto">
                        {{ $primaryLabel }}
                        <x-icons.arrow-right class="h-4 w-4" />
                    </a>
                    <a href="{{ $secondaryHref ?? route('about').'#kontak' }}" class="btn-ghost-light w-full sm:w-auto">
                        {{ $secondaryLabel }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
