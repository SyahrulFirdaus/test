@extends('layouts.dashboard')

@php
    $isEdit = $material->exists;

    /*
     * SLA Industries tidak menjual materialnya per gram — partnya dipesan ke
     * vendor luar dan harganya ditetapkan tim per penawaran. Kolom Harga Beli
     * dan Harga Jual karena itu tidak ditampilkan sama sekali, bukan sekadar
     * dibiarkan kosong: kolom harga yang terlihat tapi tidak berpengaruh justru
     * membingungkan.
     */
    $showsMaterialPrice = ! $technology->isSlaIndustries();
    $technologyLabel = $technology->isSlaIndustries() ? $technology->name : $technology->code;

    /*
     * Material SLA, MJF, dan SLM menentukan sendiri metode harganya (lihat
     * App\Support\PricingMethod): Kalkulator Otomatis (rumus Harga Jual FDM,
     * perlu Harga Beli/Harga Jual) atau Kalkulator Manual (ditetapkan tim per
     * penawaran). Material baru belum memilih apa pun — Superadmin wajib
     * menentukannya.
     */
    $choosesPricing = \App\Support\PricingMethod::appliesTo($technology->code);
    $pricingMethods = \App\Models\PrintMaterial::PRICING_METHODS;
    $pricingMethod = old('pricing_method', $material->exists ? $material->pricing_method : null);
    $isAutomatic = $pricingMethod === \App\Models\PrintMaterial::PRICING_AUTOMATIC;
    $isManual = $pricingMethod === \App\Models\PrintMaterial::PRICING_MANUAL;
    $percent = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp

@section('title', $isEdit ? 'Ubah Material' : 'Tambah Material')

@section('content')
    <a href="{{ \App\Support\PriceListPage::technologyUrl($technology) }}"
       class="inline-flex items-center gap-2 text-sm font-semibold text-ink-500 transition-colors hover:text-brand-600">
        &larr; Kembali ke {{ $technology->tabLabel() }}
    </a>

    <h2 class="mt-5 font-display text-2xl font-bold tracking-tight text-ink-900">
        {{ $isEdit ? 'Ubah Material '.$technologyLabel : 'Tambah Material '.$technologyLabel }}
    </h2>
    <p class="mt-1.5 text-sm text-ink-500">
        Nama yang diisi di sini langsung menjadi pilihan Material pada Edit Specification untuk teknologi
        <span class="font-semibold text-ink-700">{{ $technologyLabel }}</span>.
        @if ($showsMaterialPrice)
            Harganya dipakai perhitungan penawaran berikutnya.
        @endif
    </p>

    <form method="POST"
          action="{{ $isEdit
              ? route('superadmin.price-list.materials.update', [$technology, $material])
              : route('superadmin.price-list.materials.store', $technology) }}"
          class="mt-6 max-w-2xl rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-7">
        @csrf
        @if ($isEdit) @method('PATCH') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="material" class="field-label">Nama Material</label>
                <input type="text" id="material" name="material" maxlength="120" required
                       value="{{ old('material', $material->material) }}" class="field-input"
                       placeholder="mis. PLA+">
                <p class="mt-1.5 text-xs text-ink-400">Inilah yang dibaca pelanggan. Tulis nama jenis bahannya, bukan brand.</p>
                @error('material') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="brand" class="field-label">Brand</label>
                <input type="text" id="brand" name="brand" maxlength="60" required
                       value="{{ old('brand', $material->brand) }}" class="field-input"
                       placeholder="mis. ESUN">
                <p class="mt-1.5 text-xs text-ink-400">Keterangan internal; tidak ditampilkan kepada pelanggan.</p>
                @error('brand') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            {{-- Pilihan mesin datang dari Price List → Machine Cost, bukan
                 daftar yang ditulis di sini. Dikelompokkan per teknologi agar
                 mesin yang relevan mudah ditemukan, namun SELURUH mesin tetap
                 dapat dipilih. --}}
            <div class="sm:col-span-2">
                <label for="machine_cost_id" class="field-label">Nama Mesin</label>
                <select id="machine_cost_id" name="machine_cost_id" class="field-input">
                    <option value="">Tanpa mesin</option>

                    @foreach ($machines->groupBy(fn ($machine) => $machine->technology?->code ?? 'Tanpa Teknologi') as $group => $rows)
                        <optgroup label="{{ $group }}">
                            @foreach ($rows as $machine)
                                <option value="{{ $machine->id }}"
                                        @selected((string) old('machine_cost_id', $material->machine_cost_id) === (string) $machine->id)>
                                    {{ $machine->mesin }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-ink-400">
                    Diambil dari <a href="{{ route('superadmin.price-list.machine-cost.index') }}"
                                    class="font-semibold text-brand-600 hover:text-brand-800">Machine Cost</a>;
                    material dikelompokkan di bawah mesin ini pada Price List.
                    @if ($machines->isEmpty())
                        <span class="font-semibold text-brand-700">Belum ada mesin terdaftar.</span>
                    @endif
                </p>
                @error('machine_cost_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @if ($choosesPricing)
                {{-- ===== Menentukan Harga ===== --}}
                <fieldset class="sm:col-span-2 rounded-xl border border-ink-100 p-4 sm:p-5" data-pricing-method-field>
                    <legend class="px-1 font-display text-sm font-bold text-ink-900">Menentukan Harga</legend>
                    <p class="text-xs text-ink-400">Pilih cara harga material ini ditetapkan bagi pelanggan.</p>

                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($pricingMethods as $value => $label)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-ink-200 bg-white p-3 transition-colors has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="radio" name="pricing_method" value="{{ $value }}" required
                                       class="mt-0.5 h-4 w-4 accent-brand-600"
                                       @checked($pricingMethod === $value)>
                                <span>
                                    <span class="block text-sm font-semibold text-ink-900">{{ $label }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-500">
                                        {{ $value === \App\Models\PrintMaterial::PRICING_AUTOMATIC
                                            ? 'Harga langsung dihitung sistem dengan Rumus Harga Otomatis.'
                                            : 'Harga ditetapkan Admin/Superadmin setelah penawaran masuk.' }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('pricing_method') <p class="field-error">{{ $message }}</p> @enderror

                    {{-- Preview Kalkulator Otomatis: rumus Harga Jual FDM yang
                         sudah ada (App\Services\SellingPriceEstimator), beserta
                         parameter FDM yang sedang berlaku. Hanya informasi. --}}
                    <div class="mt-4 {{ $isAutomatic ? '' : 'hidden' }}" data-pricing-panel="automatic">
                        <div class="rounded-xl border border-ink-100 bg-ink-50/70 p-4">
                            <p class="text-xs font-semibold text-ink-700">Preview Rumus: Kalkulator Otomatis</p>
                            <dl class="mt-3 grid gap-3 font-mono text-xs text-ink-700 sm:grid-cols-2">
                                @foreach ([
                                    'Material' => 'Berat Model × Harga Material',
                                    'Machine Operation' => 'Machine Time × Machine Cost',
                                    'HPP' => 'Material + Machine Operation',
                                    'Risk Cost' => 'HPP × Risk %'.($automaticFormula ? ' ('.$percent($automaticFormula->risk_percent).'%)' : ''),
                                    'Subtotal' => 'HPP + Risk Cost + Packaging + Overtime',
                                    'Profit' => 'Subtotal × Profit %'.($automaticFormula ? ' ('.$percent($automaticFormula->profit_percent).'%)' : ''),
                                    'Harga Jual' => 'Subtotal + Profit + Basic Fee',
                                ] as $term => $formula)
                                    <div>
                                        <dt class="font-sans font-semibold text-ink-900">{{ $term }}</dt>
                                        <dd class="mt-0.5">= {{ $formula }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                            <p class="mt-3 text-[0.7rem] leading-relaxed text-ink-500">
                                Harga Material dari Harga Jual di bawah (per gram). Machine Time dari estimasi waktu cetak model,
                                Machine Cost dari mesin yang dipilih pelanggan (Price List &rarr; Machine Cost), Packaging dari kardus
                                terkecil yang memuat model, dan Basic Fee menurut ukuran object. Risk %, Profit %, dan Overtime memakai
                                parameter <a href="{{ route('superadmin.price-list.harga') }}" class="font-semibold text-brand-600 hover:text-brand-800">Rumus Harga Otomatis</a>@if ($automaticFormula)
                                    (Overtime Rp{{ number_format((float) $automaticFormula->overtime_cost, 0, ',', '.') }}).
                                @else
                                    <span class="font-semibold text-brand-700">(belum ada).</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Kalkulator Manual: rumus manual yang sudah dipakai SLA. --}}
                    <div class="mt-4 {{ $isManual ? '' : 'hidden' }}" data-pricing-panel="manual">
                        <div class="rounded-xl border border-ink-100 bg-ink-50/70 p-4">
                            <p class="text-xs font-semibold text-ink-700">Preview Rumus: Kalkulator Manual</p>
                            <p class="mt-1 text-xs leading-relaxed text-ink-500">
                                Harga tidak dihitung dari berat model. Pelanggan melihat &ldquo;Harga sedang dihitung oleh tim kami&rdquo;, lalu
                                harganya ditetapkan tim per model lewat
                                <span class="font-semibold text-ink-700">Form Perhitungan Kalkulator Manual</span> pada Detail Penawaran.
                            </p>
                            <dl class="mt-3 grid gap-3 font-mono text-xs text-ink-700 sm:grid-cols-2">
                                @foreach ([
                                    'Total Bayar ke JLC' => 'Harga JLC + Ongkir JLC',
                                    'HPP' => 'Total Bayar ke JLC + DHL Beacukai',
                                    'Profit' => 'HPP × Margin Profit',
                                    'Final Price' => 'HPP + Profit',
                                ] as $term => $formula)
                                    <div>
                                        <dt class="font-sans font-semibold text-ink-900">{{ $term }}</dt>
                                        <dd class="mt-0.5">= {{ $formula }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                </fieldset>

                {{-- Harga material hanya dipakai Kalkulator Otomatis. Saat Manual
                     dipilih kolomnya hanya disembunyikan, supaya nilai yang sudah
                     diisi tidak hilang. --}}
                <div class="{{ $isAutomatic ? '' : 'hidden' }}" data-pricing-panel="automatic">
                    <label for="purchase_price" class="field-label">Harga Beli (Rp)</label>
                    <input type="number" step="1" min="0" max="9999999999" id="purchase_price" name="purchase_price"
                           value="{{ old('purchase_price', $material->purchase_price) }}" class="field-input"
                           data-pricing-required @required($isAutomatic)>
                    <p class="mt-1.5 text-xs text-ink-400">Harga satu kemasan material. Harga per gram dihitung otomatis darinya.</p>
                    @error('purchase_price') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="{{ $isAutomatic ? '' : 'hidden' }}" data-pricing-panel="automatic">
                    <label for="sale_price" class="field-label">Harga Jual (Rp)</label>
                    <input type="number" step="1" min="0" max="9999999999" id="sale_price" name="sale_price"
                           value="{{ old('sale_price', $material->sale_price) }}" class="field-input"
                           data-pricing-required @required($isAutomatic)>
                    <p class="mt-1.5 text-xs text-ink-400">Dibulatkan ke atas kelipatan seratus; itulah harga material per gram pada rumus.</p>
                    @error('sale_price') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @elseif ($showsMaterialPrice)
                <div>
                    <label for="purchase_price" class="field-label">Harga Beli (Rp)</label>
                    <input type="number" step="1" min="0" max="9999999999" required id="purchase_price" name="purchase_price"
                           value="{{ old('purchase_price', $material->purchase_price) }}" class="field-input">
                    <p class="mt-1.5 text-xs text-ink-400">Harga satu spool/botol. Harga per gram dihitung otomatis darinya.</p>
                    @error('purchase_price') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sale_price" class="field-label">Harga Jual (Rp)</label>
                    <input type="number" step="1" min="0" max="9999999999" required id="sale_price" name="sale_price"
                           value="{{ old('sale_price', $material->sale_price) }}" class="field-input">
                    <p class="mt-1.5 text-xs text-ink-400">Dibulatkan ke atas kelipatan seratus; itulah harga per gram yang dikutip ke pelanggan.</p>
                    @error('sale_price') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="sm:col-span-2 rounded-xl border border-ink-100 bg-ink-50/70 p-4">
                    <p class="text-xs font-semibold text-ink-700">Material {{ $technology->name }} tidak punya harga per gram.</p>
                    <p class="mt-1 text-xs leading-relaxed text-ink-500">
                        Part {{ $technology->name }} dipesan ke vendor, jadi harganya ditetapkan tim per penawaran lewat
                        <span class="font-semibold text-ink-700">Rumus Harga {{ $technology->name }}</span> pada Detail Penawaran,
                        bukan dihitung dari berat model.
                    </p>
                </div>
            @endif

            <div class="sm:col-span-2">
                <label for="remark" class="field-label">Remark <span class="text-ink-300">(opsional)</span></label>
                <input type="text" id="remark" name="remark" maxlength="120"
                       value="{{ old('remark', $material->remark) }}" class="field-input"
                       placeholder="mis. Standard Material">
                @error('remark') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-3 border-t border-ink-100 pt-6">
            <button type="submit" class="btn-primary px-6 py-2.5">
                {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Material' }}
            </button>
            <a href="{{ \App\Support\PriceListPage::technologyUrl($technology) }}" class="btn-outline">Batal</a>
        </div>
    </form>
@endsection

@if ($choosesPricing)
    @push('scripts')
        <script>
            // Menentukan Harga: tampilkan preview & kolom harga sesuai pilihan.
            (() => {
                const field = document.querySelector('[data-pricing-method-field]');
                const form = field?.closest('form');

                if (!form) return;

                const sync = () => {
                    const method = form.querySelector('input[name="pricing_method"]:checked')?.value ?? null;

                    form.querySelectorAll('[data-pricing-panel]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.pricingPanel !== method);
                    });

                    form.querySelectorAll('[data-pricing-required]').forEach((input) => {
                        input.required = method === 'automatic';
                    });
                };

                form.querySelectorAll('input[name="pricing_method"]').forEach((radio) => radio.addEventListener('change', sync));
                sync();
            })();
        </script>
    @endpush
@endif
