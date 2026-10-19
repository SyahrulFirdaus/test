{{--
    Export CSV Deal → impor konversi offline Google Ads (Data Manager).
    Hanya tampil untuk pemegang hak `lead.export`; route-nya dijaga hak yang sama.
--}}
@php
    $range = $export['range'];
    $result = $export['result'];
    $inputClass = 'mt-2 w-full rounded-xl border border-ink-200 px-4 py-2.5 text-sm focus:border-brand-600 focus:outline-none';
    $labelClass = 'block text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-ink-400';
@endphp

<section id="export-deal" class="mt-8 scroll-mt-24 rounded-2xl border border-ink-100 bg-white p-5 shadow-card" aria-labelledby="export-deal-title">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 id="export-deal-title" class="font-display text-base font-bold text-ink-900">Export CSV Deal untuk Google Ads</h3>
            <p class="mt-1 max-w-2xl text-xs leading-relaxed text-ink-500">
                Mengekspor lead berstatus <strong>Deal</strong> berdasarkan <strong>waktu ditandai Deal</strong> (zona Asia/Jakarta) untuk diimpor ke Google Ads Data Manager
                sebagai konversi "{{ config('leads.google_ads_export.conversion_name') }}". Nilai yang dipakai adalah nilai deal aktual, bukan estimasi quotation.
            </p>
        </div>
    </div>

    @if (session('export_error'))
        <p class="mt-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800" role="alert">{{ session('export_error') }}</p>
    @endif

    <form method="GET" action="{{ staff_route('leads.index') }}#export-deal" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <input type="hidden" name="export_preview" value="1">

        <div>
            <label for="export_from" class="{{ $labelClass }}">Tanggal Deal Mulai</label>
            <input type="date" id="export_from" name="export_from" required value="{{ old('export_from', $range['export_from']) }}" class="{{ $inputClass }}">
            @error('export_from') <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="export_to" class="{{ $labelClass }}">Tanggal Deal Akhir</label>
            <input type="date" id="export_to" name="export_to" required value="{{ old('export_to', $range['export_to']) }}" class="{{ $inputClass }}">
            @error('export_to') <p class="mt-1.5 text-xs font-semibold text-brand-700">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink-700 lg:pb-3">
            <input type="checkbox" name="only_new" value="1" @checked($range['only_new']) class="h-4 w-4 rounded border-ink-300 text-brand-600 focus:ring-brand-600">
            Hanya yang belum pernah diekspor
        </label>

        <div class="flex flex-wrap gap-2 lg:col-span-2 lg:justify-end">
            <button type="submit" class="viewer-tool px-5 py-2.5">Periksa Data</button>
            <button type="submit" formaction="{{ staff_route('leads.export-deals') }}" class="btn-primary px-5 py-2.5" data-export-deal>
                Export CSV Deal
            </button>
        </div>
    </form>

    @if ($result !== null)
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-emerald-700">Siap diekspor</p>
                <p class="mt-1 font-display text-2xl font-bold text-emerald-800">{{ count($result['rows']) }} deal</p>
            </div>
            <div class="rounded-xl border {{ count($result['skipped']) ? 'border-amber-300 bg-amber-50' : 'border-ink-100 bg-ink-50' }} px-4 py-3">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] {{ count($result['skipped']) ? 'text-amber-700' : 'text-ink-400' }}">Dilewati (data tidak lengkap)</p>
                <p class="mt-1 font-display text-2xl font-bold {{ count($result['skipped']) ? 'text-amber-800' : 'text-ink-500' }}">{{ count($result['skipped']) }} deal</p>
            </div>
        </div>

        @if ($result['rows'] === [] && $result['skipped'] === [])
            <p class="mt-4 text-sm text-ink-500">Tidak ada transaksi Deal pada rentang tanggal ini.</p>
        @endif

        @if ($result['rows'] !== [])
            <div class="mt-5 overflow-x-auto rounded-xl border border-ink-100">
                <table class="w-full min-w-[980px] text-left text-xs">
                    <thead>
                        <tr class="bg-ink-50/80 text-[0.6rem] uppercase tracking-[0.12em] text-ink-500">
                            <th class="px-3 py-2.5 font-bold">Lead</th>
                            @foreach ($export['headers'] as $header)
                                <th class="px-3 py-2.5 font-bold">{{ $header }}</th>
                            @endforeach
                            <th class="px-3 py-2.5 font-bold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($result['rows'] as $row)
                            <tr class="align-top">
                                <td class="px-3 py-2.5">
                                    <a href="{{ staff_route('leads.show', $row['lead']) }}" class="whitespace-nowrap font-semibold text-ink-900 hover:text-brand-600">{{ $row['lead']->name }}</a>
                                    @if ($row['lead']->ads_exported_at)
                                        <span class="mt-0.5 block text-[0.6rem] text-ink-400">sudah diekspor {{ $row['lead']->ads_export_count }}× · pertama {{ $row['lead']->ads_exported_at->setTimezone('Asia/Jakarta')->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                @foreach ($row['values'] as $value)
                                    <td class="max-w-[180px] truncate px-3 py-2.5 font-mono text-[0.7rem] text-ink-700" title="{{ $value }}">{{ $value !== '' ? $value : '—' }}</td>
                                @endforeach
                                <td class="px-3 py-2.5 text-[0.7rem] text-ink-500">{{ implode(' ', $row['notes']) ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($result['skipped'] !== [])
            <div class="mt-5">
                <p class="text-sm font-semibold text-amber-800">Deal berikut tidak ikut diekspor — lengkapi datanya lalu periksa ulang:</p>
                <ul class="mt-2 divide-y divide-amber-100 rounded-xl border border-amber-200 bg-amber-50/50">
                    @foreach ($result['skipped'] as $item)
                        <li class="flex flex-wrap items-start justify-between gap-2 px-4 py-2.5 text-xs">
                            <a href="{{ staff_route('leads.show', $item['lead']) }}" class="font-semibold text-ink-900 hover:text-brand-600">{{ $item['lead']->name }}</a>
                            <span class="text-amber-800">{{ implode(' ', $item['reasons']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</section>
