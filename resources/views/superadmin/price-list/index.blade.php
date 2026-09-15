@extends('layouts.dashboard')

@section('title', 'Price List')

@section('content')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Price List</h2>
            <p class="mt-2 text-sm text-ink-500">
                Harga material FDM/SLA, packaging, dan mesin — sumber data Calculator/Quotation. Kolom
                <span class="font-semibold text-ink-700">Harga/10 gram</span> pada FDM &amp; SLA yang dipakai
                sebagai harga material pelanggan.
            </p>
        </div>
    </div>

    {{-- Navigasi tab.

         Tab material dibangkitkan dari daftar teknologi di basis data, jadi
         menambah teknologi lewat tab "Teknologi" langsung memunculkan tabnya
         sendiri — tanpa menyentuh berkas ini. Empat tab terakhir tetap.

         JS-nya ada di resources/js/dashboard.js (initPriceListTabs). --}}
    <div class="mt-8 flex gap-1 overflow-x-auto border-b border-ink-200" role="tablist" data-price-list-tabs>
        @foreach ($technologies as $technology)
            <button type="button" role="tab"
                    aria-selected="{{ $activeTab === $technology->tabKey() ? 'true' : 'false' }}"
                    data-price-list-tab="{{ $technology->tabKey() }}" class="price-list-tab">
                Material {{ $technology->code }}
            </button>
        @endforeach

        @foreach (['packaging' => 'Packaging', 'machine-cost' => 'Machine Cost', 'harga' => 'Harga', 'teknologi' => 'Teknologi'] as $key => $label)
            <button type="button" role="tab"
                    aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                    data-price-list-tab="{{ $key }}" class="price-list-tab">{{ $label }}</button>
        @endforeach
    </div>

    {{-- ================= MATERIAL PER TEKNOLOGI ================= --}}
    @foreach ($technologies as $technology)
        @php $tab = $technology->tabKey(); @endphp

        <section class="mt-6 {{ $activeTab === $tab ? '' : 'hidden' }}" data-price-list-panel="{{ $tab }}">
            <form method="GET" class="mb-4 flex flex-wrap gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="min-w-[240px] flex-1">
                    <label for="{{ $tab }}_q" class="field-label">Cari Material {{ $technology->code }}</label>
                    <input type="search" id="{{ $tab }}_q" name="{{ $tab }}_q"
                           value="{{ $filters[$tab.'_q'] ?? '' }}"
                           placeholder="Nama material, brand, atau remark" class="field-input">
                </div>
                <div class="flex items-end"><button type="submit" class="btn-outline px-6 py-3">Cari</button></div>
            </form>

            @include('superadmin.price-list.partials.material-table', [
                'technology' => $technology,
                'materials' => $materials[$technology->code],
                'numbers' => $materialNumbers[$technology->code],
                'rupiah' => $rupiah,
            ])
        </section>
    @endforeach

    {{-- ================= Packaging ================= --}}
    <section class="mt-6 {{ $activeTab === 'packaging' ? '' : 'hidden' }}" data-price-list-panel="packaging">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h3 class="font-display text-lg font-bold text-ink-900">Packaging</h3>
            <a href="{{ route('superadmin.price-list.packaging.create') }}" class="btn-primary">Tambah Packaging</a>
        </div>

        <form method="GET" class="mt-4 flex flex-wrap gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
            <input type="hidden" name="tab" value="packaging">
            <input type="hidden" name="fdm_q" value="{{ $filters['fdm_q'] }}">
            <input type="hidden" name="sla_q" value="{{ $filters['sla_q'] }}">
            <input type="hidden" name="machine_q" value="{{ $filters['machine_q'] }}">
            <div class="min-w-[240px] flex-1">
                <label for="packaging_q" class="field-label">Cari Packaging</label>
                <input type="search" id="packaging_q" name="packaging_q" value="{{ $filters['packaging_q'] }}" placeholder="Item, ukuran, atau dimensi" class="field-input">
            </div>
            <div class="flex items-end"><button type="submit" class="btn-outline px-6 py-3">Cari</button></div>
        </form>

        <div class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-4 py-4 font-bold">No</th>
                            <th scope="col" class="px-4 py-4 font-bold">Item</th>
                            <th scope="col" class="px-4 py-4 font-bold">Ukuran</th>
                            <th scope="col" class="px-4 py-4 font-bold">Dimensi</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Harga</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($packaging as $index => $item)
                            <tr class="transition-colors hover:bg-brand-50/40">
                                <td class="px-4 py-3 text-ink-500">{{ $packaging->firstItem() + $index }}</td>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $item->item }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $item->ukuran ?: '-' }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $item->dimensi ?: '-' }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-ink-800">{{ $item->formatted_price }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('superadmin.price-list.packaging.edit', $item) }}" class="viewer-tool">Ubah</a>
                                        <form method="POST" action="{{ route('superadmin.price-list.packaging.destroy', $item) }}"
                                              onsubmit="return confirm('Hapus packaging {{ $item->item }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-ink-400">Belum ada data packaging.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($packaging->hasPages())
            <div class="mt-4">{{ $packaging->links() }}</div>
        @endif
    </section>

    {{-- ================= Machine Cost ================= --}}
    <section class="mt-6 {{ $activeTab === 'machine-cost' ? '' : 'hidden' }}" data-price-list-panel="machine-cost">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h3 class="font-display text-lg font-bold text-ink-900">Machine Cost</h3>
            <a href="{{ route('superadmin.price-list.machine-cost.create') }}" class="btn-primary">Tambah Mesin</a>
        </div>

        <form method="GET" class="mt-4 flex flex-wrap gap-3 rounded-2xl border border-ink-100 bg-white p-4 shadow-card">
            <input type="hidden" name="tab" value="machine-cost">
            <input type="hidden" name="fdm_q" value="{{ $filters['fdm_q'] }}">
            <input type="hidden" name="sla_q" value="{{ $filters['sla_q'] }}">
            <input type="hidden" name="packaging_q" value="{{ $filters['packaging_q'] }}">
            <div class="min-w-[240px] flex-1">
                <label for="machine_q" class="field-label">Cari Mesin</label>
                <input type="search" id="machine_q" name="machine_q" value="{{ $filters['machine_q'] }}" placeholder="Nama mesin" class="field-input">
            </div>
            <div class="flex items-end"><button type="submit" class="btn-outline px-6 py-3">Cari</button></div>
        </form>

        <div class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-4 py-4 font-bold">No</th>
                            <th scope="col" class="px-4 py-4 font-bold">Mesin</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Watt (KWH)</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Harga Listrik</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Depresiasi</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Listrik/Hour</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Machine Cost</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Pembulatan</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    {{-- Mesin dikelompokkan di bawah teknologinya, dan tiap
                         baris punya tombol + yang membuka spesifikasi fisiknya
                         pada baris tersembunyi tepat di bawahnya. Kolom harga
                         yang sudah ada tidak berubah sedikit pun.

                         Isi detailnya datang dari accessor `spec_rows` pada
                         App\Models\MachineCost — tidak ada angka yang ditulis
                         di berkas ini. JS-nya: initMachineDetails() di
                         resources/js/dashboard.js. --}}
                    @php $nomor = $machineCosts->firstItem(); @endphp

                    <tbody class="divide-y divide-ink-100">
                        @forelse ($machineCosts->getCollection()->groupBy(fn ($machine) => $machine->technology?->code ?? '') as $rows)
                            @php $technology = $rows->first()->technology; @endphp

                            <tr class="bg-ink-50/70">
                                <th colspan="9" scope="colgroup" class="px-4 py-2.5 text-left">
                                    <span class="text-[0.7rem] font-bold uppercase tracking-[0.14em] text-ink-700">
                                        {{ $technology?->code ?? 'Tanpa Teknologi' }}
                                    </span>
                                    <span class="ml-2 text-[0.65rem] font-medium normal-case tracking-normal text-ink-400">
                                        {{ $technology?->name ?? 'Belum ditentukan teknologinya' }}
                                        &middot; {{ $rows->count() }} mesin
                                    </span>
                                </th>
                            </tr>

                            @foreach ($rows as $machine)
                                <tr class="transition-colors hover:bg-brand-50/40">
                                    <td class="px-4 py-3 text-ink-500">{{ $nomor++ }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    data-machine-toggle="{{ $machine->id }}"
                                                    aria-expanded="false"
                                                    aria-controls="machine-detail-{{ $machine->id }}"
                                                    aria-label="Buka detail mesin {{ $machine->mesin }}"
                                                    title="Lihat detail mesin"
                                                    class="machine-toggle">
                                                <span data-machine-toggle-icon aria-hidden="true">+</span>
                                            </button>
                                            <span class="font-semibold text-ink-900">{{ $machine->mesin }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ number_format((float) $machine->watt_kwh, 3, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($machine->harga_listrik) }}</td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($machine->depresiasi) }}</td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($machine->electricity_per_hour) }}</td>
                                    <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($machine->machine_cost) }}</td>
                                    <td class="px-4 py-3 text-right font-semibold text-brand-700">{{ $rupiah($machine->rounded_machine_cost) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('superadmin.price-list.machine-cost.edit', $machine) }}" class="viewer-tool">Ubah</a>
                                            <form method="POST" action="{{ route('superadmin.price-list.machine-cost.destroy', $machine) }}"
                                                  onsubmit="return confirm('Hapus mesin {{ $machine->mesin }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <tr id="machine-detail-{{ $machine->id }}" data-machine-detail="{{ $machine->id }}" class="hidden">
                                    <td colspan="9" class="bg-ink-50/50 px-4 py-4">
                                        <div class="max-w-md rounded-xl border border-ink-100 bg-white p-4">
                                            <table class="w-full text-left text-sm">
                                                <caption class="sr-only">Detail mesin {{ $machine->mesin }}</caption>
                                                <thead>
                                                    <tr class="border-b border-ink-100 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                                                        <th scope="col" class="py-2 font-bold">Bagian</th>
                                                        <th scope="col" class="py-2 text-right font-bold">Ukuran</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-ink-100">
                                                    @foreach ($machine->spec_rows as $spec)
                                                        <tr>
                                                            <th scope="row" class="py-2 font-medium text-ink-600">{{ $spec['label'] }}</th>
                                                            <td class="py-2 text-right font-semibold text-ink-900">{{ $spec['value'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>

                                            @unless ($machine->hasSpecs())
                                                <p class="mt-3 border-t border-ink-100 pt-3 text-xs text-ink-400">
                                                    Detail mesin belum diisi.
                                                    <a href="{{ route('superadmin.price-list.machine-cost.edit', $machine) }}"
                                                       class="font-semibold text-brand-600 hover:text-brand-800">Lengkapi sekarang</a>.
                                                </p>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="9" class="px-4 py-12 text-center text-ink-400">Belum ada data Machine Cost.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($machineCosts->hasPages())
            <div class="mt-4">{{ $machineCosts->links() }}</div>
        @endif
    </section>

    {{-- ================= Harga (rumus Harga Jual, referensi saja) ================= --}}
    <section class="mt-6 {{ $activeTab === 'harga' ? '' : 'hidden' }}" data-price-list-panel="harga">
        <div>
            <h3 class="font-display text-lg font-bold text-ink-900">Rumus Harga Jual</h3>
            <p class="mt-1.5 text-sm text-ink-500">
                Simulasi rumus &amp; parameter penentuan Harga Jual per teknologi cetak. Murni referensi
                admin — <span class="font-semibold text-ink-700">tidak memengaruhi Calculator maupun Quotation</span>.
            </p>
        </div>

        {{-- Sub-tab teknologi: satu rumus tampil per waktu, JS-nya sama
             persis dengan tab utama (initFormulaTabs di dashboard.js). --}}
        <div class="mt-5 flex gap-1 overflow-x-auto border-b border-ink-200" role="tablist" data-formula-tabs>
            @foreach (\App\Models\PricingFormula::technologies() as $tech)
                <button type="button" role="tab" aria-selected="{{ $tech === 'FDM' ? 'true' : 'false' }}"
                        data-formula-tab="{{ $tech }}" class="price-list-tab">{{ $tech }}</button>
            @endforeach
        </div>

        @foreach (\App\Models\PricingFormula::technologies() as $tech)
            @php
                $formula = $formulas[$tech] ?? null;
                $isSubmitted = old('technology') === $tech;
                $val = fn (string $field) => $isSubmitted ? old($field) : ($formula->{$field} ?? '');
            @endphp
            <div class="mt-6 grid gap-6 lg:grid-cols-2 {{ $tech === 'FDM' ? '' : 'hidden' }}" data-formula-panel="{{ $tech }}">
                {{-- Rincian rumus: dihitung otomatis dari parameter di samping. --}}
                <div class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                    <div class="border-b border-ink-100 px-5 py-4">
                        <h4 class="font-display text-base font-bold text-ink-900">Rincian Harga Jual — {{ $tech }}</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[420px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                                    <th scope="col" class="px-5 py-3 font-bold">Komponen</th>
                                    <th scope="col" class="px-5 py-3 font-bold">Rumus / Parameter</th>
                                    <th scope="col" class="px-5 py-3 text-right font-bold">Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                @if ($formula)
                                    @foreach ($formula->breakdown() as $row)
                                        <tr @class(['bg-brand-50/50' => $row['highlight'] ?? false])>
                                            <td @class(['px-5 py-3', 'font-bold text-ink-900' => $row['highlight'] ?? false, 'font-semibold text-ink-800' => empty($row['highlight'])])>{{ $row['label'] }}</td>
                                            <td class="px-5 py-3 text-xs text-ink-500">{{ $row['formula'] }}</td>
                                            <td @class(['px-5 py-3 text-right', 'font-bold text-brand-700' => $row['highlight'] ?? false, 'text-ink-700' => empty($row['highlight'])])>{{ $rupiah($row['value']) }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr><td colspan="3" class="px-5 py-8 text-center text-ink-400">Rumus {{ $tech }} belum tersedia.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Parameter: admin ubah di sini, rincian di samping ikut berubah setelah disimpan. --}}
                @if ($formula)
                    <form method="POST" action="{{ route('superadmin.price-list.harga.update', $tech) }}"
                          class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="technology" value="{{ $tech }}">

                        <h4 class="font-display text-base font-bold text-ink-900">Parameter {{ $tech }}</h4>
                        <p class="mt-1 text-xs text-ink-500">Ubah nilai lalu simpan — rincian di samping langsung mengikuti.</p>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="machine_time_hours_{{ $tech }}" class="field-label">Machine Time (jam)</label>
                                <input type="number" step="0.01" min="0" id="machine_time_hours_{{ $tech }}" name="machine_time_hours" value="{{ $val('machine_time_hours') }}" class="field-input">
                                @if ($isSubmitted) @error('machine_time_hours') <p class="field-error">{{ $message }}</p> @enderror @endif
                            </div>
                            <div>
                                <label for="machine_cost_{{ $tech }}" class="field-label">Machine Cost (Rp/jam)</label>
                                <input type="number" step="0.01" min="0" id="machine_cost_{{ $tech }}" name="machine_cost" value="{{ $val('machine_cost') }}" class="field-input">
                                @if ($isSubmitted) @error('machine_cost') <p class="field-error">{{ $message }}</p> @enderror @endif
                            </div>
                            <div>
                                <label for="material_qty_g_{{ $tech }}" class="field-label">Jumlah Material (gram)</label>
                                <input type="number" step="0.01" min="0" id="material_qty_g_{{ $tech }}" name="material_qty_g" value="{{ $val('material_qty_g') }}" class="field-input">
                                @if ($isSubmitted) @error('material_qty_g') <p class="field-error">{{ $message }}</p> @enderror @endif
                            </div>
                            <div>
                                <label for="material_price_per_g_{{ $tech }}" class="field-label">Harga Material (Rp/gram)</label>
                                <input type="number" step="0.01" min="0" id="material_price_per_g_{{ $tech }}" name="material_price_per_g" value="{{ $val('material_price_per_g') }}" class="field-input">
                                @if ($isSubmitted) @error('material_price_per_g') <p class="field-error">{{ $message }}</p> @enderror @endif
                            </div>
                            <div>
                                <label for="risk_percent_{{ $tech }}" class="field-label">Risiko Gagal Print (%)</label>
                                <input type="number" step="0.01" min="0" max="100" id="risk_percent_{{ $tech }}" name="risk_percent" value="{{ $val('risk_percent') }}" class="field-input">
                                @if ($isSubmitted) @error('risk_percent') <p class="field-error">{{ $message }}</p> @enderror @endif
                            </div>
                            <div>
                                <label for="packaging_cost_{{ $tech }}" class="field-label">Packaging (Rp)</label>
                                <input type="number" step="0.01" min="0" id="packaging_cost_{{ $tech }}" name="packaging_cost" value="{{ $val('packaging_cost') }}" class="field-input">
                                @if ($isSubmitted) @error('packaging_cost') <p class="field-error">{{ $message }}</p> @enderror @endif
                            </div>
                            <div>
                                <label for="overtime_cost_{{ $tech }}" class="field-label">Overtime (Rp)</label>
                                <input type="number" step="0.01" min="0" id="overtime_cost_{{ $tech }}" name="overtime_cost" value="{{ $val('overtime_cost') }}" class="field-input">
                                @if ($isSubmitted) @error('overtime_cost') <p class="field-error">{{ $message }}</p> @enderror @endif
                            </div>
                            <div>
                                <label for="profit_percent_{{ $tech }}" class="field-label">Profit (%)</label>
                                <input type="number" step="0.01" min="0" max="100" id="profit_percent_{{ $tech }}" name="profit_percent" value="{{ $val('profit_percent') }}" class="field-input">
                                @if ($isSubmitted) @error('profit_percent') <p class="field-error">{{ $message }}</p> @enderror @endif
                            </div>

                            {{-- Basic Fee tidak diisi sebagai rupiah: yang diisi
                                 ukuran objectnya, tarifnya mengikuti tingkatan di
                                 config/printing.php. --}}
                            <div class="sm:col-span-2">
                                <label for="object_size_mm_{{ $tech }}" class="field-label">Ukuran 3D Object — sisi terpanjang (mm)</label>
                                <input type="number" step="0.01" min="0" max="10000" id="object_size_mm_{{ $tech }}" name="object_size_mm" value="{{ $val('object_size_mm') }}" class="field-input">
                                @if ($isSubmitted) @error('object_size_mm') <p class="field-error">{{ $message }}</p> @enderror @endif
                                <p class="mt-1.5 text-[0.65rem] leading-relaxed text-ink-500">
                                    Menentukan Basic Fee:
                                    @foreach (\App\Support\BasicFee::tiers() as $tier)
                                        <span class="font-semibold text-ink-700">{{ $tier['label'] }}</span>
                                        @if (isset($tier['below_mm'])) (&lt; {{ $tier['below_mm'] }} mm)
                                        @elseif (isset($tier['up_to_mm'])) (s.d. {{ $tier['up_to_mm'] }} mm)
                                        @else (di atasnya) @endif
                                        Rp{{ number_format((float) $tier['fee'], 0, ',', '.') }}{{ $loop->last ? '.' : ' · ' }}
                                    @endforeach
                                    Pada penawaran sungguhan ukuran ini dibaca otomatis dari model 3D pelanggan.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="btn-primary">Simpan Rumus {{ $tech }}</button>
                        </div>
                    </form>
                @endif
            </div>
        @endforeach
    </section>

    {{-- ================= TEKNOLOGI ================= --}}
    <section class="mt-6 {{ $activeTab === 'teknologi' ? '' : 'hidden' }}" data-price-list-panel="teknologi">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h3 class="font-display text-lg font-bold text-ink-900">Teknologi Cetak</h3>
                <p class="mt-1.5 text-sm text-ink-500">
                    Menambah teknologi di sini langsung memunculkan tab materialnya sendiri di halaman ini,
                    pilihan Technology pada Edit Specification, dan baris parameternya pada tab Harga.
                </p>
            </div>

            <a href="{{ route('superadmin.price-list.technologies.create') }}" class="btn-primary">Tambah Teknologi</a>
        </div>

        <div class="mt-4 overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                            <th scope="col" class="px-4 py-4 font-bold">Kode</th>
                            <th scope="col" class="px-4 py-4 font-bold">Nama</th>
                            <th scope="col" class="px-4 py-4 font-bold">Keluarga</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Material</th>
                            <th scope="col" class="px-4 py-4 font-bold">Area Cetak</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Tarif Mesin</th>
                            <th scope="col" class="px-4 py-4 font-bold">Hollow</th>
                            <th scope="col" class="px-4 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-ink-100">
                        @forelse ($technologies as $technology)
                            <tr class="transition-colors hover:bg-brand-50/40">
                                <td class="px-4 py-3 font-mono font-bold text-brand-700">{{ $technology->code }}</td>
                                <td class="px-4 py-3 font-semibold text-ink-900">{{ $technology->name }}</td>
                                <td class="px-4 py-3 text-ink-600">{{ $technology->family ?: '-' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($technology->materials_count > 0)
                                        <span class="font-semibold text-ink-800">{{ $technology->materials_count }}</span>
                                    @else
                                        {{-- Tanpa material, teknologinya tidak dapat dipilih pelanggan. --}}
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em] text-amber-800">
                                            Belum ada
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-600">
                                    {{ $technology->build_volume_x }} × {{ $technology->build_volume_y }} × {{ $technology->build_volume_z }} mm
                                </td>
                                <td class="px-4 py-3 text-right text-ink-700">{{ $rupiah($technology->machine_rate_per_hour) }}/jam</td>
                                <td class="px-4 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.1em]',
                                        'bg-emerald-100 text-emerald-800' => $technology->allows_hollow,
                                        'bg-ink-100 text-ink-500' => ! $technology->allows_hollow,
                                    ])>
                                        {{ $technology->allows_hollow ? 'Ya' : 'Tidak' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('superadmin.price-list.technologies.edit', $technology) }}" class="viewer-tool">Ubah</a>

                                        @if ($technology->isInUse())
                                            {{-- Penawaran lama menunjuk teknologinya lewat kode, jadi
                                                 tombolnya dimatikan — penjaganya juga ada di controller. --}}
                                            <span class="viewer-tool cursor-not-allowed opacity-50"
                                                  title="Sudah dipakai penawaran, tidak dapat dihapus">Hapus</span>
                                        @else
                                            <form method="POST" action="{{ route('superadmin.price-list.technologies.destroy', $technology) }}"
                                                  onsubmit="return confirm('Hapus teknologi {{ $technology->code }} beserta {{ $technology->materials_count }} materialnya? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="viewer-tool text-brand-600">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-ink-400">
                                    Belum ada teknologi.
                                    <a href="{{ route('superadmin.price-list.technologies.create') }}" class="font-semibold text-brand-600 hover:text-brand-700">Tambahkan sekarang</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
