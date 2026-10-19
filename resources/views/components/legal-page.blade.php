{{--
    Kerangka halaman legal (Kebijakan Privasi, Syarat & Ketentuan): hero,
    daftar isi yang menempel di layar besar, dan isi dokumen.

    $sections: [['id' => 'anchor', 'title' => 'Judul bagian'], ...] — urutannya
    sama dengan <section id="..."> di dalam slot.
--}}
@props([
    'title',
    'description' => null,
    'updated',
    'sections' => [],
    'other' => null,
])

<x-page-hero eyebrow="Legal" :current="$title" :title="e($title)" :description="$description" />

<div class="container-page py-12 md:py-16">
    <div class="lg:grid lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12">
        <aside class="mb-10 lg:mb-0">
            <div class="lg:sticky lg:top-36">
                <p class="font-mono text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-ink-400">Daftar isi</p>
                <ol class="mt-3 space-y-1 text-sm">
                    @foreach ($sections as $section)
                        <li>
                            <a href="#{{ $section['id'] }}" class="flex gap-2 rounded-lg px-2 py-1 text-ink-500 transition-colors hover:bg-ink-50 hover:text-brand-600">
                                <span class="w-5 shrink-0 font-mono text-xs text-ink-300">{{ $loop->iteration }}.</span>
                                {{ $section['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ol>

                @if ($other)
                    <a href="{{ $other['url'] }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700">
                        {{ $other['label'] }}
                        <x-icons.arrow-right class="h-4 w-4" />
                    </a>
                @endif
            </div>
        </aside>

        <article class="legal-content min-w-0">
            <p class="!mt-0 text-sm text-ink-400">Terakhir diperbarui: {{ $updated }}</p>

            {{ $slot }}
        </article>
    </div>
</div>
