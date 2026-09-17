{{--
    Detail Perhitungan Harga tiap model — INTERNAL, hanya dirender di halaman
    admin. Seluruh angkanya dibaca dari perhitungan yang tersimpan pada
    `cost_breakdown` masing-masing model saat penawaran dibuat, jadi membuka
    halaman ini tidak menghitung ulang apa pun dan tidak dapat menggeser harga
    yang sudah ditawarkan ke pelanggan.

    Diharapkan: $sellingPrice (hasil SellingPriceEstimator::forQuotation)
--}}
@php
    $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
    $models = $sellingPrice['models'];
    $groups = $sellingPrice['groups'];

    // Satu model SLA yang belum dikuotasi membuat TOTAL penawaran
    // belum berarti; menjumlahkan sisanya akan terbaca sebagai harga penuh.
    $awaitsPricing = $quotation->awaitsPricing();
@endphp

<div id="rincian-harga" class="mt-8 scroll-mt-24 border-t border-ink-100 pt-6">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-display text-sm font-bold text-ink-900">Detail Perhitungan Harga</h3>
            <p class="mt-1 text-xs text-ink-400">
                Asal harga tiap model: material, mesin, risk, packaging, overtime, profit, dan Basic Fee.
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-[0.12em] text-amber-800">
            Internal &middot; Tidak Terlihat Pelanggan
        </span>
    </div>

    @if ($models->isEmpty())
        <p class="mt-5 text-sm text-ink-400">Belum ada model pada penawaran ini, jadi harganya belum dapat dirinci.</p>
    @else
        @if ($sellingPrice['reconstructed'])
            {{-- Penawaran lama tidak menyimpan perhitungannya; rinciannya
                 disusun ulang dengan parameter Price List yang berlaku
                 sekarang, jadi belum tentu berjumlah sama dengan harga yang
                 sudah ditawarkan. --}}
            <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-900">
                Penawaran ini dibuat sebelum rincian perhitungan ikut disimpan, jadi angka di bawah
                <span class="font-semibold">disusun ulang</span> memakai parameter Price List yang berlaku sekarang.
                Harga yang berlaku bagi pelanggan tetap {{ $rupiah($sellingPrice['quotation_total']) }} seperti tercatat pada penawarannya.
            </p>
        @endif

        {{-- ===================== ACCORDION PER MODEL ===================== --}}
        <div class="mt-5 space-y-3">
            @foreach ($models as $entry)
                @php
                    $item = $entry['item'];
                    $calculation = $entry['calculation'];
                @endphp

                <details class="group overflow-hidden rounded-2xl border border-ink-100 bg-white">
                    <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-3 px-5 py-4 hover:bg-ink-50/60">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-ink-900">
                                <span class="font-mono text-xs font-semibold text-ink-400">{{ $item->position }}</span>
                                {{ $item->file_name }}
                            </p>
                            <p class="mt-0.5 truncate text-[0.65rem] text-ink-400">
                                {{ $calculation['technology'] ?? $item->technology }} &middot;
                                {{ $calculation['material_source'] ?? $item->material }} &middot;
                                {{ $calculation['quantity'] ?? $item->quantity }} unit &middot; {{ $item->printer_name }}
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">Harga Penawaran</p>
                            <p class="font-display text-base font-bold {{ $item->awaitsPricing() ? 'text-amber-700' : 'text-brand-700' }}">
                                {{ $item->awaitsPricing() ? 'Menunggu Perhitungan' : $rupiah($calculation['selling_price']) }}
                            </p>
                            <p class="mt-0.5 text-[0.65rem] font-semibold text-ink-400">
                                Lihat Detail Perhitungan
                                <span class="inline-block transition-transform group-open:rotate-180">&#9660;</span>
                            </p>
                        </div>
                    </summary>

                    <div class="border-t border-ink-100 bg-ink-50/40">

                        {{-- Parameter yang benar-benar dipakai, supaya admin dapat
                             memeriksa satu nilai tanpa membaca seluruh tabel. --}}
                        <dl class="grid gap-x-6 gap-y-3 px-5 py-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($entry['parameters'] as $label => $value)
                                <div class="min-w-0">
                                    <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">{{ $label }}</dt>
                                    <dd class="mt-0.5 break-words text-xs font-semibold text-ink-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        @if (empty($entry['rows']))
                            {{-- SLA Industries: harganya satu angka dari kuotasi
                                 vendor, tanpa komponen material/mesin/risk. Yang
                                 merincinya adalah Form Perhitungan pada kartu
                                 modelnya, bukan tabel ini. --}}
                            <p class="border-t border-ink-100 bg-white px-5 py-4 text-xs leading-relaxed text-ink-500">
                                Harga model {{ $calculation['technology'] }} ini ditetapkan tim dari kuotasi vendor, bukan dihitung dari
                                material dan waktu mesin. Rincian lengkapnya
                                (Harga JLC, ongkir, DHL Beacukai, HPP, dan margin) ada pada
                                <a href="#model-{{ $item->id }}" class="font-semibold text-brand-600 hover:text-brand-800">Form Perhitungan {{ $calculation['technology'] }}</a>
                                di kartu model ini.
                            </p>
                        @else
                        <div class="overflow-x-auto border-t border-ink-100 bg-white">
                            <table class="w-full min-w-[520px] text-left text-sm">
                                <caption class="px-5 pt-4 text-left text-[0.6rem] font-bold uppercase tracking-[0.12em] text-ink-500">
                                    Detail Harga: {{ $item->file_name }}
                                </caption>
                                <thead>
                                    <tr class="border-b border-ink-100 text-[0.6rem] uppercase tracking-[0.14em] text-ink-400">
                                        <th scope="col" class="px-5 py-3 font-bold">Komponen</th>
                                        <th scope="col" class="px-5 py-3 font-bold">Rumus / Dasar Perhitungan</th>
                                        <th scope="col" class="px-5 py-3 text-right font-bold">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-100">
                                    @foreach ($entry['rows'] as $row)
                                        <tr @class(['bg-brand-50/50' => $row['highlight'] ?? false])>
                                            <td @class(['px-5 py-2.5 text-xs', 'font-bold text-ink-900' => $row['highlight'] ?? false, 'font-semibold text-ink-700' => empty($row['highlight'])])>{{ $row['label'] }}</td>
                                            <td class="px-5 py-2.5 text-[0.7rem] text-ink-500">{{ $row['formula'] }}</td>
                                            <td @class(['px-5 py-2.5 text-right text-xs', 'font-bold text-brand-700' => $row['highlight'] ?? false, 'text-ink-700' => empty($row['highlight'])])>{{ $rupiah($row['value']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        @if ($calculation['formula_missing'] ?? false)
                            <p class="border-t border-ink-100 bg-white px-5 py-3 text-[0.65rem] font-semibold text-amber-700">
                                Parameter {{ $calculation['technology'] }} belum diisi di Price List, jadi risk dan profit terhitung nol.
                            </p>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>

        {{-- Subtotal per teknologi hanya berguna saat penawarannya memang
             memakai lebih dari satu teknologi. --}}
        @if ($groups->count() > 1)
            <div class="mt-5 overflow-hidden rounded-2xl border border-ink-100 bg-white">
                <div class="border-b border-ink-100 bg-ink-50/80 px-5 py-3">
                    <p class="text-[0.6rem] font-bold uppercase tracking-[0.12em] text-ink-500">Subtotal per Teknologi</p>
                </div>
                <table class="w-full text-left text-sm">
                    <tbody class="divide-y divide-ink-100">
                        @foreach ($groups as $group)
                            <tr>
                                <th scope="row" class="px-5 py-3 text-xs font-bold text-ink-800">{{ $group['technology'] }}</th>
                                <td class="px-5 py-3 text-[0.7rem] text-ink-500">
                                    {{ $group['entries']->count() }} model &middot; {{ $group['totals']['quantity'] }} unit
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-semibold text-ink-800">{{ $rupiah($group['totals']['selling_price']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- ===================== TOTAL PENAWARAN ===================== --}}
        <div class="mt-5 rounded-2xl border border-brand-200 bg-brand-50/60 p-5">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-brand-700/70">Total Penawaran</p>
                    <p class="mt-1 font-display text-2xl font-bold {{ $awaitsPricing ? 'text-amber-700' : 'text-brand-700' }}">
                        {{ $awaitsPricing ? 'Menunggu Perhitungan' : $rupiah($sellingPrice['selling_price']) }}
                    </p>
                    <p class="mt-1 text-[0.65rem] text-ink-500">
                        Penjumlahan Harga Jual {{ $models->count() }} model &middot; {{ $sellingPrice['totals']['quantity'] }} unit
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-[0.6rem] font-semibold uppercase tracking-[0.12em] text-ink-400">Tercatat pada Penawaran</p>
                    <p class="mt-1 font-display text-lg font-bold text-ink-900">
                        {{ $awaitsPricing ? 'Belum ada' : $rupiah($sellingPrice['quotation_total']) }}
                    </p>
                    @if ($awaitsPricing)
                        {{-- Tidak ada yang dapat dibandingkan: harga penawarannya
                             memang belum ditetapkan, bukan berbeda. --}}
                        <p class="mt-1 text-[0.65rem] font-semibold text-amber-700">
                            Menunggu Form Perhitungan Kalkulator Manual diisi.
                        </p>
                    @elseif (abs($sellingPrice['difference']) < 0.01)
                        <p class="mt-1 text-[0.65rem] font-semibold text-emerald-700">&check; Cocok dengan rincian di atas</p>
                    @else
                        <p class="mt-1 text-[0.65rem] font-semibold text-amber-700">
                            Selisih {{ $sellingPrice['difference'] > 0 ? '+' : '' }}{{ $rupiah($sellingPrice['difference']) }}
                        </p>
                    @endif
                </div>
            </div>

            <p class="mt-4 border-t border-brand-200/70 pt-3 text-[0.65rem] leading-relaxed text-ink-500">
                Harga ditetapkan saat pelanggan mengirim permintaan dan perhitungannya ikut tersimpan, jadi membuka halaman ini
                tidak menghitung ulang apa pun. Parameter yang dipakai dikelola di
                <a href="{{ route('superadmin.price-list.harga') }}" class="font-semibold text-brand-600 underline">Price List</a>;
                mengubahnya hanya memengaruhi penawaran berikutnya.
            </p>
        </div>
    @endif
</div>
