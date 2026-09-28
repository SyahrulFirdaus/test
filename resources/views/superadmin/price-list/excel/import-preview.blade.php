@extends('superadmin.price-list.layout')

{{--
    Pratinjau Import Teknologi / Machine Cost — pasangan pratinjau Import
    Material, dengan susunan dan perilaku yang sama.

    Halaman ini tidak pernah mengubah basis data. Berkasnya sudah dibaca dan
    diperiksa seluruhnya, hasilnya dititipkan di sesi, dan yang tersimpan nanti
    hanya baris yang ditandai sah di sini.

    Diharapkan:
      $dataset      App\Services\PriceList\Excel\SpreadsheetDataset
      $result       App\Services\PriceList\Excel\MaterialImportResult
      $routePrefix  awalan nama route Excel
      $backUrl      halaman tabel asalnya
      $group        judul grup sidebar
--}}

@section('title', 'Price List · Import '.$dataset->title())
@section('price-list-group', $group)
@section('price-list-page', 'Import '.$dataset->title())

@section('price-list')
    @php
        $valid = $result->valid();
        $invalid = $result->invalid();
        $duplicates = $result->duplicates();
        $baru = count($valid) - count($duplicates);
        $columns = $dataset->previewColumns();
        $identity = mb_strtolower(collect($columns)->firstWhere('key', $dataset->identityKey())['heading'] ?? '');
    @endphp

    <a href="{{ $backUrl }}"
       class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        Kembali ke {{ $dataset->title() }}
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
                Sel kosong pada kolom opsional bertanda &ldquo;tetap&rdquo; mempertahankan nilai yang sekarang;
                baris baru memakai nilai bawaan form.
                @if (collect($dataset->columns())->contains(fn ($column) => $column['derived'] ?? false))
                    Kolom hitungan ({{ collect($dataset->columns())->filter(fn ($column) => $column['derived'] ?? false)->pluck('heading')->implode(', ') }})
                    tidak disimpan dari file — selalu dihitung ulang dari kolom asalnya.
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" style="min-width: {{ max(900, 120 * (count($columns) + 2)) }}px">
                <thead>
                    <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                        <th scope="col" class="px-4 py-4 font-bold">Row</th>
                        @foreach ($columns as $column)
                            <th scope="col" @class([
                                'px-4 py-4 font-bold',
                                'text-right' => in_array($column['type'] ?? 'text', ['money', 'integer', 'decimal'], true),
                            ])>
                                {{ $column['heading'] }}
                                @if (($column['blank'] ?? null) === 'keep')
                                    <span class="block text-[0.55rem] font-medium normal-case tracking-normal text-ink-400">kosong = tetap</span>
                                @endif
                            </th>
                        @endforeach
                        <th scope="col" class="px-4 py-4 font-bold">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-ink-100">
                    @foreach ($result->rows as $row)
                        <tr class="{{ $row->isValid() ? '' : 'bg-brand-50/40' }}">
                            <td class="px-4 py-3 text-ink-500">{{ $row->line }}</td>
                            @foreach ($columns as $column)
                                <td @class([
                                    'px-4 py-3',
                                    'font-semibold text-ink-900' => $column['key'] === $dataset->identityKey(),
                                    'text-ink-700' => $column['key'] !== $dataset->identityKey(),
                                    'text-right whitespace-nowrap' => in_array($column['type'] ?? 'text', ['money', 'integer', 'decimal'], true),
                                    'max-w-xs' => $column['wrap'] ?? false,
                                ])>
                                    {{ $dataset->display($column, $row->values[$column['key']] ?? null) }}
                                </td>
                            @endforeach
                            <td class="px-4 py-3">
                                @if (! $row->isValid())
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-brand-100 px-2.5 py-0.5 text-[0.65rem] font-bold text-brand-800">Error</span>
                                @elseif ($row->isDuplicate())
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-amber-100 px-2.5 py-0.5 text-[0.65rem] font-bold text-amber-800">Sudah Ada</span>
                                @else
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-100 px-2.5 py-0.5 text-[0.65rem] font-bold text-emerald-800">Baru</span>
                                @endif

                                @foreach ($row->notes as $note)
                                    <p class="mt-1 min-w-[14rem] text-[0.7rem] leading-relaxed text-ink-400">{{ $note }}</p>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ================= Keputusan ================= --}}
    <form method="POST" action="{{ route($routePrefix.'.import') }}"
          class="mt-6 rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
        @csrf

        @if ($duplicates !== [])
            {{-- Baris yang pengenalnya sudah ada tidak pernah ditambahkan sebagai
                 baris kedua; yang diputuskan hanyalah dilewati atau ditimpa. --}}
            <fieldset>
                <legend class="font-display text-base font-bold text-ink-900">
                    {{ count($duplicates) }} {{ $dataset->noun() }} sudah ada di Price List
                </legend>
                <p class="mt-1 text-sm text-ink-500">
                    {{ ucfirst($identity) }}-nya sudah terdaftar pada {{ $dataset->title() }}. Pilih perlakuannya:
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ([
                        [\App\Services\PriceList\Excel\SpreadsheetDataset::ON_DUPLICATE_SKIP, 'Skip', 'Biarkan data yang sekarang. Hanya '.$baru.' '.$dataset->noun().' baru yang ditambahkan.'],
                        [\App\Services\PriceList\Excel\SpreadsheetDataset::ON_DUPLICATE_UPDATE, 'Update data existing', 'Seluruh kolom diperbarui mengikuti file, kecuali '.$identity.'-nya sendiri.'],
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
            <input type="hidden" name="on_duplicate" value="{{ \App\Services\PriceList\Excel\SpreadsheetDataset::ON_DUPLICATE_SKIP }}">

            <p class="text-sm text-ink-500">
                Seluruh {{ $dataset->noun() }} pada file ini belum ada di Price List {{ $dataset->title() }}.
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

    <form method="POST" action="{{ route($routePrefix.'.cancel') }}" class="mt-3">
        @csrf
        <button type="submit" class="viewer-tool">Batalkan Import</button>
    </form>
@endsection
