@extends('superadmin.price-list.layout')

@section('title', 'Price List · Rumus Harga Otomatis')
@section('price-list-group', 'Harga')
@section('price-list-page', 'Rumus Harga Otomatis')

@section('price-list')
    @php $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.'); @endphp

    {{-- ================= Rumus Harga Otomatis ================= --}}
    <section>
        <div>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h3 class="font-display text-lg font-bold text-ink-900">Rumus Harga Jual</h3>
                    <p class="mt-1.5 text-sm text-ink-500">
                        Satu rumus penentuan Harga Jual yang berlaku untuk <span class="font-semibold text-ink-700">seluruh teknologi</span>
                        dengan Kalkulator Otomatis. Perubahan berlaku untuk penawaran berikutnya; penawaran yang sudah dibuat tidak berubah.
                    </p>
                </div>

                {{-- Biaya packaging bagian dari rumus ini, jadi halamannya dibuka dari sini. --}}
                <a href="{{ route('superadmin.price-list.packaging.index') }}" class="viewer-tool">Kelola Packaging</a>
            </div>
        </div>

        {{-- Satu rumus yang berlaku umum untuk seluruh teknologi. --}}
        @php
            $val = fn (string $field) => old($field, $formula->{$field} ?? '');
        @endphp

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            {{-- Rincian rumus: dihitung otomatis dari parameter di samping. --}}
            <div class="overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-card">
                <div class="border-b border-ink-100 px-5 py-4">
                    <h4 class="font-display text-base font-bold text-ink-900">Rincian Harga Jual</h4>
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
                                <tr><td colspan="3" class="px-5 py-8 text-center text-ink-400">Rumus Harga Otomatis belum tersedia.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Parameter: admin ubah di sini, rincian di samping ikut berubah setelah disimpan. --}}
            @if ($formula)
                <form method="POST" action="{{ route('superadmin.price-list.harga.update') }}"
                      class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card">
                    @csrf
                    @method('PATCH')

                    <h4 class="font-display text-base font-bold text-ink-900">Parameter Rumus</h4>
                    <p class="mt-1 text-xs text-ink-500">Ubah nilai lalu simpan, rincian di samping langsung mengikuti.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="machine_time_hours" class="field-label">Machine Time (jam)</label>
                            <input type="number" step="0.01" min="0" id="machine_time_hours" name="machine_time_hours" value="{{ $val('machine_time_hours') }}" class="field-input">
                            @error('machine_time_hours') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="machine_cost" class="field-label">Machine Cost (Rp/jam)</label>
                            {{-- Nominal rupiah: tampil "Rp 61.000", terkirim sebagai angka murni. --}}
                            <x-rupiah-input name="machine_cost" id="machine_cost" :value="$val('machine_cost')"
                                            :step="1000" align="left" wrapper-class="mt-2" />
                            @error('machine_cost') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="material_qty_g" class="field-label">Jumlah Material (gram)</label>
                            <input type="number" step="0.01" min="0" id="material_qty_g" name="material_qty_g" value="{{ $val('material_qty_g') }}" class="field-input">
                            @error('material_qty_g') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="material_price_per_g" class="field-label">Harga Material (Rp/gram)</label>
                            <x-rupiah-input name="material_price_per_g" id="material_price_per_g" :value="$val('material_price_per_g')"
                                            :step="10" align="left" wrapper-class="mt-2" />
                            @error('material_price_per_g') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="risk_percent" class="field-label">Risiko Gagal Print (%)</label>
                            <input type="number" step="0.01" min="0" max="100" id="risk_percent" name="risk_percent" value="{{ $val('risk_percent') }}" class="field-input">
                            @error('risk_percent') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="packaging_cost" class="field-label">Packaging (Rp)</label>
                            <x-rupiah-input name="packaging_cost" id="packaging_cost" :value="$val('packaging_cost')"
                                            :step="1000" align="left" wrapper-class="mt-2" />
                            @error('packaging_cost') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="overtime_cost" class="field-label">Overtime (Rp)</label>
                            <input type="number" step="0.01" min="0" id="overtime_cost" name="overtime_cost" value="{{ $val('overtime_cost') }}" class="field-input">
                            @error('overtime_cost') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="profit_percent" class="field-label">Profit (%)</label>
                            <input type="number" step="0.01" min="0" max="100" id="profit_percent" name="profit_percent" value="{{ $val('profit_percent') }}" class="field-input">
                            @error('profit_percent') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Basic Fee tidak diisi sebagai rupiah: yang diisi
                             ukuran objectnya, tarifnya mengikuti tingkatan di
                             config/printing.php. --}}
                        <div class="sm:col-span-2">
                            <label for="object_size_mm" class="field-label">Ukuran 3D Object, sisi terpanjang (mm)</label>
                            <input type="number" step="0.01" min="0" max="10000" id="object_size_mm" name="object_size_mm" value="{{ $val('object_size_mm') }}" class="field-input">
                            @error('object_size_mm') <p class="field-error">{{ $message }}</p> @enderror
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
                        <button type="submit" class="btn-primary">Simpan Rumus</button>
                    </div>
                </form>
            @endif
        </div>
    </section>
@endsection
