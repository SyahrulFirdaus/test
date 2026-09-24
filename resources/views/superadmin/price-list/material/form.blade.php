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

    /*
     * Baris daftar warna: kiriman yang ditolak validasi lebih dulu (supaya yang
     * sudah diketik tidak hilang), baru warna yang tersimpan. Material baru
     * dibukakan satu baris kosong agar tidak perlu menekan Tambah Color dulu.
     */
    $colorRows = collect(old('colors', $material->exists
        ? $material->colors->map(fn ($color) => ['key' => $color->key, 'name' => $color->name, 'hex' => $color->hex])->all()
        : []))
        ->filter(fn ($row) => is_array($row))
        ->map(fn ($row) => [
            // Kunci warna yang sudah ada ikut dikirim kembali supaya mengganti
            // namanya TIDAK membuat penawaran lama kehilangan warnanya.
            'key' => (string) ($row['key'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'hex' => (string) ($row['hex'] ?? ''),
        ])
        ->values()
        ->all();

    if ($colorRows === [] && ! $material->exists) {
        $colorRows = [['key' => '', 'name' => '', 'hex' => '#FFFFFF']];
    }

    /*
     * Kelebihan, kekurangan, dan daftar finishing tinggal di `technical_spec` —
     * tempat yang sejak awal dibaca halaman spesifikasi, jadi tidak ada kolom
     * kedua untuk data yang sama. Keduanya disunting sebagai teks bertingkat,
     * satu baris satu butir.
     */
    $spec = (array) ($material->technical_spec ?? []);
    $advantages = old('advantages', implode("\n", (array) ($spec['pros'] ?? [])));
    $disadvantages = old('disadvantages', implode("\n", (array) ($spec['cons'] ?? [])));

    $finishingOptions = \App\Support\Finishing::all();
    $selectedFinishings = (array) old('finishings', (array) ($spec['finishings'] ?? []));
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

            {{-- ===== Color ===== --}}
            <fieldset class="sm:col-span-2 rounded-xl border border-ink-100 p-4 sm:p-5">
                <legend class="px-1 font-display text-sm font-bold text-ink-900">Color</legend>
                <p class="text-xs text-ink-400">
                    Warna yang ditawarkan material ini. Tiap material punya daftarnya sendiri &mdash; inilah yang dilihat
                    pelanggan setelah memilih {{ $technologyLabel }} &rarr; material ini pada Edit Specification.
                </p>

                @error('colors') <p class="field-error">{{ $message }}</p> @enderror

                <div class="mt-3 space-y-2.5" data-color-list>
                    @foreach ($colorRows as $i => $row)
                        <div class="flex items-start gap-2" data-color-row>
                            <input type="hidden" name="colors[{{ $i }}][key]" value="{{ $row['key'] }}">

                            <input type="color" aria-label="Pilih warna" data-color-picker
                                   value="{{ $row['hex'] ?: '#FFFFFF' }}"
                                   class="h-[42px] w-12 shrink-0 cursor-pointer rounded-xl border border-ink-200 bg-white p-1">

                            <div class="min-w-0 flex-1">
                                <input type="text" name="colors[{{ $i }}][name]" maxlength="60" data-color-name
                                       value="{{ $row['name'] }}" class="field-input" placeholder="Nama Color, mis. Putih">
                                @error('colors.'.$i.'.name') <p class="field-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="w-36 shrink-0">
                                <input type="text" name="colors[{{ $i }}][hex]" maxlength="7" data-color-hex
                                       value="{{ $row['hex'] }}" class="field-input font-mono uppercase" placeholder="#FFFFFF">
                                @error('colors.'.$i.'.hex') <p class="field-error">{{ $message }}</p> @enderror
                            </div>

                            <button type="button" data-color-remove
                                    class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-ink-200 text-ink-400 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600"
                                    aria-label="Hapus warna ini">&times;</button>
                        </div>
                    @endforeach
                </div>

                <button type="button" data-color-add class="btn-outline mt-3 px-4 py-2 text-xs">+ Tambah Color</button>

                <p class="mt-2.5 text-xs text-ink-400" data-color-empty-note @unless ($colorRows === []) hidden @endunless>
                    Belum ada warna. Material tanpa warna menerima seluruh warna yang dikenal sistem.
                </p>
            </fieldset>

            {{-- Baris kosong untuk tombol Tambah Color. `__INDEX__` diganti
                 nomor urut baru saat barisnya disalin. --}}
            <template data-color-template>
                <div class="flex items-start gap-2" data-color-row>
                    <input type="color" aria-label="Pilih warna" data-color-picker value="#FFFFFF"
                           class="h-[42px] w-12 shrink-0 cursor-pointer rounded-xl border border-ink-200 bg-white p-1">

                    <div class="min-w-0 flex-1">
                        <input type="text" name="colors[__INDEX__][name]" maxlength="60" data-color-name
                               class="field-input" placeholder="Nama Color, mis. Putih">
                    </div>

                    <div class="w-36 shrink-0">
                        <input type="text" name="colors[__INDEX__][hex]" maxlength="7" data-color-hex
                               value="#FFFFFF" class="field-input font-mono uppercase" placeholder="#FFFFFF">
                    </div>

                    <button type="button" data-color-remove
                            class="mt-1 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-ink-200 text-ink-400 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-600"
                            aria-label="Hapus warna ini">&times;</button>
                </div>
            </template>

            {{-- ===== Finishing ===== --}}
            <fieldset class="sm:col-span-2 rounded-xl border border-ink-100 p-4 sm:p-5">
                <legend class="px-1 font-display text-sm font-bold text-ink-900">Finishing</legend>
                <p class="text-xs text-ink-400">
                    Finishing yang ditawarkan material ini. Biarkan kosong bila seluruhnya boleh dipilih.
                    Harganya dihitung Rumus Harga Otomatis, bukan diatur per material.
                </p>

                <div class="mt-3 space-y-2">
                    @foreach ($finishingOptions as $key => $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-ink-200 bg-white p-3 transition-colors has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                            <input type="checkbox" name="finishings[]" value="{{ $key }}"
                                   class="mt-0.5 h-4 w-4 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-600"
                                   @checked(in_array($key, $selectedFinishings, true))>
                            <span>
                                <span class="block text-sm font-semibold text-ink-900">
                                    {{ $option['label'] }}
                                    @if (! empty($option['manual']))
                                        <span class="ml-1 rounded-full bg-ink-100 px-2 py-0.5 text-[0.65rem] font-bold text-ink-600">Kuotasi manual</span>
                                    @elseif (($option['percent'] ?? 0) > 0)
                                        <span class="ml-1 text-[0.7rem] font-normal text-ink-400">
                                            MAX({{ (int) $option['percent'] }}% × Harga Printing, Rp{{ number_format((float) ($option['min_price'] ?? 0), 0, ',', '.') }})
                                        </span>
                                    @else
                                        <span class="ml-1 text-[0.7rem] font-normal text-ink-400">Rp0</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block text-[0.7rem] leading-relaxed text-ink-500">{{ $option['description'] ?? '' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('finishings') <p class="field-error">{{ $message }}</p> @enderror
                @error('finishings.*') <p class="field-error">{{ $message }}</p> @enderror
            </fieldset>

            {{-- ===== Kelebihan & Kekurangan ===== --}}
            <div>
                <label for="advantages" class="field-label">Kelebihan <span class="text-ink-300">(satu per baris)</span></label>
                <textarea id="advantages" name="advantages" rows="5" class="field-input"
                          placeholder="Mudah dicetak&#10;Hasil permukaan cukup baik&#10;Cocok untuk prototype">{{ $advantages }}</textarea>
                <p class="mt-1.5 text-xs text-ink-400">Tampil pada Edit Specification saat pelanggan memilih material ini.</p>
                @error('advantages') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="disadvantages" class="field-label">Kekurangan <span class="text-ink-300">(satu per baris)</span></label>
                <textarea id="disadvantages" name="disadvantages" rows="5" class="field-input"
                          placeholder="Ketahanan terhadap panas terbatas&#10;Tidak cocok untuk temperatur tinggi">{{ $disadvantages }}</textarea>
                <p class="mt-1.5 text-xs text-ink-400">Ditulis apa adanya; pelanggan membacanya sebagai pertimbangan.</p>
                @error('disadvantages') <p class="field-error">{{ $message }}</p> @enderror
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
                                Harga tidak dihitung dari berat model. Pelanggan melihat &ldquo;Harga Perlu Dicek Terlebih Dahulu&rdquo;, lalu
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
                <div class="sm:col-span-2 {{ $isAutomatic ? '' : 'hidden' }}" data-pricing-panel="automatic">
                    <label for="purchase_price" class="field-label">Harga Beli (Rp)</label>
                    <x-rupiah-input name="purchase_price" :value="old('purchase_price', $material->purchase_price)"
                                    :step="1000" :max="9999999999" align="left" nullable />
                    <p class="mt-1.5 text-xs text-ink-400">Harga satu kemasan material. Harga per gram dihitung otomatis darinya.</p>
                    @error('purchase_price') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="{{ $isAutomatic ? '' : 'hidden' }}" data-pricing-panel="automatic">
                    <label for="sale_price" class="field-label">Harga Jual per Gram (Rp)</label>
                    <x-rupiah-input name="sale_price" :value="old('sale_price', $material->sale_price)"
                                    :step="100" :max="9999999999" align="left" nullable />
                    <p class="mt-1.5 text-xs text-ink-400">Dibulatkan ke atas kelipatan seratus; itulah harga material per gram pada rumus.</p>
                    @error('sale_price') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="{{ $isAutomatic ? '' : 'hidden' }}" data-pricing-panel="automatic">
                    @include('superadmin.price-list.material.partials.sale-per-10-gram', ['salePrice' => old('sale_price', $material->sale_price)])
                </div>
            @elseif ($showsMaterialPrice)
                <div class="sm:col-span-2">
                    <label for="purchase_price" class="field-label">Harga Beli (Rp)</label>
                    <x-rupiah-input name="purchase_price" :value="old('purchase_price', $material->purchase_price)"
                                    :step="1000" :max="9999999999" align="left" nullable />
                    <p class="mt-1.5 text-xs text-ink-400">Harga satu spool/botol. Harga per gram dihitung otomatis darinya.</p>
                    @error('purchase_price') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="sale_price" class="field-label">Harga Jual per Gram (Rp)</label>
                    <x-rupiah-input name="sale_price" :value="old('sale_price', $material->sale_price)"
                                    :step="100" :max="9999999999" align="left" nullable />
                    <p class="mt-1.5 text-xs text-ink-400">Dibulatkan ke atas kelipatan seratus; itulah harga per gram yang dikutip ke pelanggan.</p>
                    @error('sale_price') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    @include('superadmin.price-list.material.partials.sale-per-10-gram', ['salePrice' => old('sale_price', $material->sale_price)])
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

@push('scripts')
    <script>
        // Daftar warna material: tambah, hapus, dan pemilih warna tiap baris.
        (() => {
            const list = document.querySelector('[data-color-list]');
            const template = document.querySelector('[data-color-template]');
            const addButton = document.querySelector('[data-color-add]');
            const emptyNote = document.querySelector('[data-color-empty-note]');

            if (!list || !template || !addButton) return;

            // Nomor baris baru tidak pernah dipakai ulang. Server menerima
            // indeks apa pun — daftarnya dirapikan ulang saat disimpan — jadi
            // baris yang dihapus tidak perlu membuat sisanya dinomori ulang.
            let nextIndex = list.querySelectorAll('[data-color-row]').length;

            const syncEmptyNote = () => {
                if (emptyNote) {
                    emptyNote.hidden = list.querySelector('[data-color-row]') !== null;
                }
            };

            addButton.addEventListener('click', () => {
                const row = template.content.firstElementChild.cloneNode(true);

                row.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace('__INDEX__', String(nextIndex));
                });

                nextIndex += 1;
                list.append(row);
                syncEmptyNote();
                row.querySelector('[data-color-name]')?.focus();
            });

            list.addEventListener('click', (event) => {
                if (!event.target.closest('[data-color-remove]')) return;

                event.target.closest('[data-color-row]')?.remove();
                syncEmptyNote();
            });

            // Didengarkan di tingkat daftar supaya baris yang baru ditambahkan
            // ikut berlaku tanpa dipasangi penangan sendiri.
            list.addEventListener('input', (event) => {
                const row = event.target.closest('[data-color-row]');
                const picker = row?.querySelector('[data-color-picker]');
                const hex = row?.querySelector('[data-color-hex]');

                if (!picker || !hex) return;

                if (event.target === picker) {
                    hex.value = picker.value.toUpperCase();
                } else if (event.target === hex && /^#[0-9A-Fa-f]{6}$/.test(hex.value.trim())) {
                    picker.value = hex.value.trim();
                }
            });

            syncEmptyNote();
        })();
    </script>
@endpush

@if ($showsMaterialPrice)
    @push('scripts')
        <script>
            // Harga Jual per 10 Gram mengikuti Harga Jual per Gram secara langsung.
            // Rumus memakai harga per gram yang dibulatkan ke atas kelipatan Rp100,
            // jadi pembulatan yang sama dipakai di sini sebelum dikali 10.
            (() => {
                const source = document.querySelector('input[type="hidden"][name="sale_price"]');
                const target = document.querySelector('[data-sale-per-10-gram]');

                if (!source || !target) return;

                const rupiah = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

                const sync = () => {
                    if (source.value === '') {
                        target.value = '';

                        return;
                    }

                    const perGram = Math.ceil((Number(source.value) || 0) / 100) * 100;
                    target.value = `Rp ${rupiah.format(perGram * 10)}`;
                };

                source.addEventListener('input', sync);
                sync();
            })();
        </script>
    @endpush
@endif

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

                    // Kewajiban mengisi Harga Beli/Jual saat Kalkulator Otomatis
                    // diperiksa server (StorePrintMaterialRequest), karena nilai
                    // yang terkirim ada di input tersembunyi komponen rupiah.
                };

                form.querySelectorAll('input[name="pricing_method"]').forEach((radio) => radio.addEventListener('change', sync));
                sync();
            })();
        </script>
    @endpush
@endif
