@extends('superadmin.price-list.layout')

{{--
    Pratinjau Import: apa yang AKAN terjadi, sebelum apa pun disimpan.

    Halaman ini tidak pernah mengubah basis data. Berkasnya sudah dibaca dan
    diperiksa seluruhnya, hasilnya dititipkan di sesi, dan yang tersimpan nanti
    hanya baris yang ditandai sah di sini.
--}}

@section('title', 'Price List · Import Material '.$technology->tabLabel())
@section('price-list-group', 'Teknologi & Material')
@section('price-list-page', 'Import Material '.$technology->tabLabel())

@section('price-list')
    @php
        $rupiah = fn ($value) => $value === null ? '-' : 'Rp'.number_format((float) $value, 0, ',', '.');

        $valid = $result->valid();
        $invalid = $result->invalid();
        $duplicates = $result->duplicates();
        $baru = count($valid) - count($duplicates);

        // Metode Harga hanya dimiliki SLA/MJF/SLM; FDM tidak menampilkannya.
        $showsPricingMethod = \App\Services\PriceList\Excel\MaterialSheet::hasPricingMethod($technology);
        $methodLabels = \App\Models\PrintMaterial::PRICING_METHODS;
    @endphp

    <a href="{{ \App\Support\PriceListPage::technologyUrl($technology) }}"
       class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke Material {{ $technology->tabLabel() }}
    </a>

    <p class="text-sm text-ink-500">
        File <span class="font-semibold text-ink-800">{{ $result->fileName }}</span> sudah dibaca seluruhnya.
        Belum ada satu baris pun yang disimpan.
    </p>

    {{-- ================= Ringkasan ================= --}}
    <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Total Row', $result->total(), 'text-ink-900'],
            ['Valid', count($valid), 'text-emerald-700'],
            ['Error', count($invalid), count($invalid) > 0 ? 'text-brand-700' : 'text-ink-900'],
            ['Sudah Ada', count($duplicates), 'text-amber-700'],
        ] as [$label, $value, $tone])
            <div class="rounded-2xl border border-ink-100 bg-white p-5 shadow-card">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400">{{ $label }}</p>
                <p class="mt-2 font-display text-2xl font-bold {{ $tone }}">{{ number_format($value, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    {{-- ================= Baris bermasalah ================= --}}
    @if ($invalid !== [])
        <section class="mt-6 overflow-hidden rounded-2xl border border-brand-200 bg-brand-50/50 shadow-card">
            <div class="border-b border-brand-200 px-6 py-4">
                <h3 class="font-display text-base font-bold text-brand-800">
                    {{ count($invalid) }} baris tidak dapat diimpor
                </h3>
                <p class="mt-1 text-xs text-brand-700">
                    Baris ini dilewati seluruhnya. Perbaiki di file Excel lalu unggah ulang bila memang dibutuhkan.
                </p>
            </div>

            <ul class="divide-y divide-brand-100">
                @foreach ($invalid as $row)
                    <li class="px-6 py-3 text-sm">
                        <p class="font-semibold text-ink-900">
                            Row {{ $row->line }}{{ $row->material ? ' · '.$row->material : '' }}
                        </p>
                        <ul class="mt-1 list-inside list-disc text-brand-700">
                            @foreach ($row->errors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ================= Tabel pratinjau ================= --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
        <div class="border-b border-ink-100 px-6 py-4">
            <h3 class="font-display text-base font-bold text-ink-900">Preview Data</h3>
            <p class="mt-1 text-xs text-ink-400">
                Harga per gram, Pembulatan Harga, dan Harga/10 gram tidak disimpan dari file —
                ketiganya selalu dihitung ulang dari Harga Beli dan Harga Jual oleh Pricing Engine.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full {{ $showsPricingMethod ? 'min-w-[1040px]' : 'min-w-[900px]' }} text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                        <th scope="col" class="px-4 py-4 font-bold">Row</th>
                        <th scope="col" class="px-4 py-4 font-bold">Material</th>
                        <th scope="col" class="px-4 py-4 font-bold">Brand</th>
                        <th scope="col" class="px-4 py-4 text-right font-bold">Harga Beli</th>
                        <th scope="col" class="px-4 py-4 text-right font-bold">Harga Jual</th>
                        <th scope="col" class="px-4 py-4 font-bold">Remark</th>
                        @if ($showsPricingMethod)
                            <th scope="col" class="px-4 py-4 font-bold">Metode Harga</th>
                        @endif
                        <th scope="col" class="px-4 py-4 font-bold">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-ink-100">
                    @foreach ($result->rows as $row)
                        <tr class="{{ $row->isValid() ? '' : 'bg-brand-50/40' }}">
                            <td class="px-4 py-3 text-ink-500">{{ $row->line }}</td>
                            <td class="px-4 py-3 font-semibold text-ink-900">{{ $row->material ?? '-' }}</td>
                            <td class="px-4 py-3 text-ink-600">{{ $row->values['brand'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($row->values['purchase_price'] ?? null) }}</td>
                            <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($row->values['sale_price'] ?? null) }}</td>
                            <td class="px-4 py-3 text-ink-500">{{ $row->values['remark'] ?? '-' }}</td>
                            @if ($showsPricingMethod)
                                @php $method = $row->values['pricing_method'] ?? null; @endphp
                                <td class="px-4 py-3">
                                    @if ($method !== null)
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold {{ $method === \App\Models\PrintMaterial::PRICING_AUTOMATIC ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $methodLabels[$method] }}
                                        </span>
                                    @else
                                        <span class="text-ink-400">-</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-4 py-3">
                                @if (! $row->isValid())
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-brand-100 px-2.5 py-0.5 text-[0.65rem] font-bold text-brand-800">Error</span>
                                @elseif ($row->isDuplicate())
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-2.5 py-0.5 text-[0.65rem] font-bold text-amber-800">Sudah Ada</span>
                                @else
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-100 px-2.5 py-0.5 text-[0.65rem] font-bold text-emerald-800">Baru</span>
                                @endif

                                @foreach ($row->notes as $note)
                                    <p class="mt-1 text-[0.7rem] leading-relaxed text-ink-400">{{ $note }}</p>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ================= Keputusan ================= --}}
    <form method="POST" action="{{ route('superadmin.price-list.materials.excel.import', $technology) }}"
          class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
        @csrf

        @if ($duplicates !== [])
            {{-- Material yang namanya sudah ada tidak pernah ditambahkan sebagai
                 baris kedua; yang diputuskan hanyalah dilewati atau ditimpa. --}}
            <fieldset>
                <legend class="font-display text-base font-bold text-ink-900">
                    {{ count($duplicates) }} material sudah ada di Price List
                </legend>
                <p class="mt-1 text-sm text-ink-500">
                    Namanya sudah terdaftar pada teknologi {{ $technology->tabLabel() }}. Pilih perlakuannya:
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        [\App\Services\PriceList\Excel\MaterialImporter::ON_DUPLICATE_SKIP, 'Skip', 'Biarkan data yang sekarang. Hanya '.$baru.' material baru yang ditambahkan.'],
                        [\App\Services\PriceList\Excel\MaterialImporter::ON_DUPLICATE_UPDATE, 'Update data existing', 'Harga Beli, Harga Jual, Brand, dan Remark'.($showsPricingMethod ? ', beserta Metode Harga bila kolomnya diisi,' : '').' diperbarui mengikuti file. Mesin dan teknologinya tidak berubah.'],
                    ] as [$value, $title, $description])
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-ink-200 p-4 transition-colors has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/60">
                            <input type="radio" name="on_duplicate" value="{{ $value }}"
                                   @checked($loop->first)
                                   class="mt-0.5 h-4 w-4 border-ink-300 text-brand-600 focus:ring-brand-600">
                            <span>
                                <span class="block text-sm font-semibold text-ink-900">{{ $title }}</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-ink-500">{{ $description }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @else
            {{-- Tidak ada yang kembar: pilihannya tidak ditampilkan, tetapi
                 tetap dikirim supaya aturan validasinya satu dan sama. --}}
            <input type="hidden" name="on_duplicate" value="{{ \App\Services\PriceList\Excel\MaterialImporter::ON_DUPLICATE_SKIP }}">

            <p class="text-sm text-ink-500">
                Seluruh material pada file ini belum ada di Price List {{ $technology->tabLabel() }}.
            </p>
        @endif

        <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-5">
            <button type="submit" class="btn-primary" @disabled($valid === [])>
                Import {{ count($valid) }} Data Valid
            </button>

            <span class="text-xs text-ink-400">
                Seluruhnya disimpan dalam satu transaksi — bila ada yang gagal, tidak ada satu pun yang tersimpan.
            </span>
        </div>
    </form>

    <form method="POST" action="{{ route('superadmin.price-list.materials.excel.cancel', $technology) }}" class="mt-3">
        @csrf
        <button type="submit" class="viewer-tool">Batalkan Import</button>
    </form>
@endsection
