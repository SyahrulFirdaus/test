{{--
    Form Rumus Harga SLA (Kalkulator Manual).

    Dipakai DUA tempat dengan tampilan yang sama persis:

      1. Price List → tab SLA — mengatur nilai BAWAAN yang mengisi
         form perhitungan tiap model saat Admin membukanya pertama kali;
      2. Detail Penawaran → tiap model SLA — kuotasi sungguhan yang
         menetapkan harga model itu.

    Ditulis sekali supaya rumus dan urutan komponennya tidak pernah berbeda
    antara keduanya.

    Diharapkan:
      $action          alamat tujuan form
      $values          model SlaIndustriesFormula / SlaIndustriesQuote yang mengisi
      $submitLabel     tulisan tombol simpan
      $uid             awalan id elemen, supaya beberapa form dalam satu halaman
                       tidak saling merebut label
      $showProductName tampilkan isian Nama Produk/Model (hanya pada penawaran)
      $productName     nilai bawaan Nama Produk
      $readonly        tampilkan angkanya saja, tanpa isian — untuk peran yang
                       tidak berhak mengubah
      $usdRate         keadaan kurs USD/IDR (bentuk App\Services\UsdRate::current()).
                       Selalu kurs YANG BERLAKU SEKARANG: formulir ini untuk
                       menghitung, dan menghitung memakai kurs hari ini. Kurs
                       yang dipakai harga yang SUDAH ditetapkan tersimpan pada
                       kuotasinya sendiri dan ditampilkan terpisah.
      $rateEndpoint    alamat penyegaran kurs; null mematikan penyegaran
--}}
@php
    use App\Support\SlaIndustries;

    $showProductName ??= false;
    $productName ??= null;
    $readonly ??= false;
    $submitLabel ??= 'Simpan Perhitungan';

    $rupiah = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
    $dollar = fn ($value) => '$'.number_format((float) $value, 2, '.', ',');

    $rateEndpoint ??= null;

    /*
     * Kurs tidak lagi diketik: nilainya datang dari App\Services\UsdRate.
     * `rate` bernilai null hanya bila penyedianya gagal DAN sistem belum pernah
     * menyimpan kurs sama sekali. Dalam keadaan itu formulirnya tidak boleh
     * menghitung apa pun — menghitung dengan kurs nol menghasilkan harga
     * gratis, yang jauh lebih berbahaya daripada menolak menghitung.
     */
    $rateValue = $usdRate['rate'] ?? null;
    $rateMissing = $rateValue === null;
    $rateStale = (bool) ($usdRate['stale'] ?? false);

    // Stempel waktu penyedia datang dalam UTC; yang membacanya ada di Jakarta.
    $waktu = fn (?string $iso) => blank($iso)
        ? null
        : \Illuminate\Support\Carbon::parse($iso)->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i').' WIB';

    // Nilai yang sedang berlaku; old() menang supaya isian tidak hilang saat
    // validasi server menolak kiriman.
    $val = fn (string $field, $fallback = null) => old($field, $fallback ?? $values->{$field});

    $computed = $values->computed();
@endphp

<form method="POST" action="{{ $action }}"
      data-sla-formula
      @if ($rateEndpoint && ! $readonly)
          data-sla-rate-endpoint="{{ $rateEndpoint }}"
          data-sla-rate-refresh="{{ (int) ($usdRate['refresh_seconds'] ?? 0) }}"
      @endif
      class="space-y-5">
    @csrf
    @method('PATCH')

    @if ($showProductName)
        <div>
            <label for="{{ $uid }}-product-name" class="field-label">Nama Produk/Model</label>
            <input type="text" id="{{ $uid }}-product-name" name="product_name" maxlength="150"
                   value="{{ $val('product_name', $productName) }}"
                   class="field-input" placeholder="mis. Impeller" @disabled($readonly)>
            <p class="mt-1.5 text-xs text-ink-400">
                Kosongkan untuk memakai nama berkas modelnya.
            </p>
            @error('product_name') <p class="field-error">{{ $message }}</p> @enderror
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-ink-100 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink-100 bg-ink-50/80 text-[0.65rem] uppercase tracking-[0.14em] text-ink-500">
                        <th scope="col" class="px-4 py-3 font-bold">Komponen</th>
                        <th scope="col" class="px-4 py-3 text-right font-bold">Dollar</th>
                        <th scope="col" class="px-4 py-3 text-right font-bold">Rupiah</th>
                        <th scope="col" class="px-4 py-3 font-bold">Remark</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-ink-100">
                    {{-- Kurs: satu-satunya baris yang sisi dollarnya tetap $1,
                         dan satu-satunya yang TIDAK diketik Admin. Nilainya
                         diambil sistem dari penyedia kurs; isian tersembunyi di
                         bawah hanya membawa angka yang benar-benar terlihat di
                         layar, supaya yang tersimpan sama dengan yang dilihat. --}}
                    <tr data-sla-rate-row>
                        <th scope="row" class="px-4 py-3 text-left font-semibold text-ink-900">
                            Dollar Hari Ini
                            <span class="mt-1 block text-[0.6rem] font-bold uppercase tracking-[0.12em] text-brand-600">
                                Otomatis
                            </span>
                        </th>
                        <td class="px-4 py-3 text-right font-mono text-ink-500">$1.00</td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-mono text-base font-bold {{ $rateMissing ? 'text-brand-700' : 'text-ink-900' }}"
                                  data-sla-output="usd_rate_idr">
                                {{ $rateMissing ? 'Tidak tersedia' : $rupiah($rateValue) }}
                            </span>

                            {{-- Angka inilah yang dikirim. Tidak dapat disunting
                                 dari layar, dan server memeriksanya ulang. --}}
                            <input type="hidden" name="usd_rate" value="{{ $rateValue }}" data-sla-input="usd_rate">

                            @error('usd_rate') <p class="field-error text-right">{{ $message }}</p> @enderror
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-400">
                            <span class="block" data-sla-rate-status>
                                @if ($rateMissing)
                                    <span class="font-semibold text-brand-700">Tidak dapat mengambil kurs terbaru.</span>
                                    Perhitungan ditahan sampai kursnya berhasil diambil.
                                @else
                                    @if ($rateStale)
                                        <span class="block font-semibold text-amber-700">
                                            Kurs terbaru gagal diperbarui. Memakai kurs terakhir yang tersimpan.
                                        </span>
                                    @endif

                                    @if (filled($usdRate['source'] ?? null))
                                        <span class="block">Sumber: <span class="font-semibold text-ink-600">{{ $usdRate['source'] }}</span></span>
                                    @endif

                                    @if ($waktu($usdRate['published_at'] ?? null))
                                        <span class="block">Terakhir diperbarui: {{ $waktu($usdRate['published_at']) }}</span>
                                    @elseif ($waktu($usdRate['fetched_at'] ?? null))
                                        <span class="block">Diambil: {{ $waktu($usdRate['fetched_at']) }}</span>
                                    @endif
                                @endif
                            </span>

                            @if ($rateEndpoint && ! $readonly)
                                <button type="button"
                                        class="viewer-tool mt-2 {{ $rateMissing || $rateStale ? '' : 'hidden' }}"
                                        data-sla-rate-retry>Coba Lagi</button>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <th scope="row" class="px-4 py-3 text-left font-semibold text-ink-900">Harga JLC</th>
                        <td class="px-4 py-3 text-right">
                            @if ($readonly)
                                <span class="font-mono text-ink-700">{{ $dollar($computed['jlc_price_usd']) }}</span>
                            @else
                                <input type="number" step="0.01" min="0" max="99999999" required
                                       id="{{ $uid }}-jlc-price" name="jlc_price_usd" value="{{ $val('jlc_price_usd') }}"
                                       class="field-input text-right font-mono" data-sla-input="jlc_price_usd"
                                       aria-label="Harga JLC dalam dollar">
                            @endif
                            @error('jlc_price_usd') <p class="field-error text-right">{{ $message }}</p> @enderror
                        </td>
                        {{-- Rupiah tidak diketik: selalu turunan dari dollar × kurs. --}}
                        <td class="px-4 py-3 text-right font-mono font-semibold text-ink-800" data-sla-output="jlc_price_idr">
                            {{ $rupiah($computed['jlc_price_idr']) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-400">Isi harga dari JLC di kotak kuning.</td>
                    </tr>

                    <tr>
                        <th scope="row" class="px-4 py-3 text-left font-semibold text-ink-900">Ongkir JLC</th>
                        <td class="px-4 py-3 text-right">
                            @if ($readonly)
                                <span class="font-mono text-ink-700">{{ $dollar($computed['jlc_shipping_usd']) }}</span>
                            @else
                                <input type="number" step="0.01" min="0" max="99999999" required
                                       id="{{ $uid }}-jlc-shipping" name="jlc_shipping_usd" value="{{ $val('jlc_shipping_usd') }}"
                                       class="field-input text-right font-mono" data-sla-input="jlc_shipping_usd"
                                       aria-label="Ongkir JLC dalam dollar">
                            @endif
                            @error('jlc_shipping_usd') <p class="field-error text-right">{{ $message }}</p> @enderror
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-ink-800" data-sla-output="jlc_shipping_idr">
                            {{ $rupiah($computed['jlc_shipping_idr']) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-400">Isi harga ongkir dari JLC di kotak kuning.</td>
                    </tr>

                    <tr class="bg-ink-50/60">
                        <th scope="row" class="px-4 py-3 text-left font-semibold text-ink-900">Total Bayar ke JLC</th>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-ink-800" data-sla-output="total_jlc_usd">
                            {{ $dollar($computed['total_jlc_usd']) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-ink-800" data-sla-output="total_jlc_idr">
                            {{ $rupiah($computed['total_jlc_idr']) }}
                        </td>
                        <td class="px-4 py-3 text-xs font-semibold text-ink-500">Otomatis &middot; Harga JLC + Ongkir JLC</td>
                    </tr>

                    <tr>
                        <th scope="row" class="px-4 py-3 text-left font-semibold text-ink-900">DHL Beacukai (Pajak)</th>
                        <td class="px-4 py-3 text-right text-ink-300">-</td>
                        <td class="px-4 py-3 text-right">
                            @if ($readonly)
                                <span class="font-mono font-semibold text-ink-800">{{ $rupiah($computed['customs_idr']) }}</span>
                            @else
                                {{-- Nominal rupiah: tampil "Rp 1.500.000", terkirim sebagai angka murni. --}}
                                <x-rupiah-input name="customs_idr" :id="$uid.'-customs'" :value="$val('customs_idr')"
                                                label="DHL Beacukai dalam rupiah" data-sla-input="customs_idr" />
                            @endif
                            @error('customs_idr') <p class="field-error text-right">{{ $message }}</p> @enderror
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-400">
                            Hitung melalui
                            <a href="{{ SlaIndustries::CUSTOMS_CALCULATOR_URL }}" target="_blank" rel="noopener noreferrer"
                               class="font-semibold text-brand-600 hover:text-brand-800">Kalkulator Pabean Bea Cukai</a>,
                            lalu ketik hasilnya di sini.
                        </td>
                    </tr>

                    <tr>
                        <th scope="row" class="px-4 py-3 text-left font-semibold text-ink-900">Margin Profit</th>
                        <td class="px-4 py-3 text-right text-ink-300">-</td>
                        <td class="px-4 py-3 text-right">
                            @if ($readonly)
                                <span class="font-mono font-semibold text-ink-800" data-sla-output="margin_percent">
                                    {{ rtrim(rtrim(number_format((float) $computed['margin_percent'], 2, ',', '.'), '0'), ',') }}%
                                </span>
                            @else
                                <div class="flex items-center justify-end gap-2">
                                    <input type="number" step="0.01"
                                           min="{{ SlaIndustries::MIN_MARGIN }}" max="{{ SlaIndustries::MAX_MARGIN }}" required
                                           id="{{ $uid }}-margin" name="margin_percent" value="{{ $val('margin_percent') }}"
                                           class="field-input w-28 text-right font-mono" data-sla-input="margin_percent"
                                           aria-label="Margin profit dalam persen">
                                    <span class="text-sm font-semibold text-ink-500">%</span>
                                </div>
                                <p class="field-error text-right" style="display: none" data-sla-margin-error>
                                    {{ SlaIndustries::marginMessage() }}
                                </p>
                            @endif
                            @error('margin_percent') <p class="field-error text-right">{{ $message }}</p> @enderror
                        </td>
                        <td class="px-4 py-3 text-xs text-ink-400">
                            Isi margin profit {{ SlaIndustries::MIN_MARGIN }}%–{{ SlaIndustries::MAX_MARGIN }}%.
                        </td>
                    </tr>

                    <tr class="bg-ink-50/60">
                        <th scope="row" class="px-4 py-3 text-left font-semibold text-ink-900">HPP</th>
                        <td class="px-4 py-3 text-right text-ink-300">-</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-ink-800" data-sla-output="hpp">
                            {{ $rupiah($computed['hpp']) }}
                        </td>
                        <td class="px-4 py-3 text-xs font-semibold text-ink-500">Otomatis &middot; Total Bayar ke JLC + DHL Beacukai</td>
                    </tr>

                    <tr class="bg-ink-50/60">
                        <th scope="row" class="px-4 py-3 text-left font-semibold text-ink-900">Profit</th>
                        <td class="px-4 py-3 text-right text-ink-300">-</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-ink-800" data-sla-output="profit">
                            {{ $rupiah($computed['profit']) }}
                        </td>
                        <td class="px-4 py-3 text-xs font-semibold text-ink-500">Otomatis &middot; HPP × Margin Profit</td>
                    </tr>

                    <tr class="bg-brand-50">
                        <th scope="row" class="px-4 py-4 text-left font-display text-base font-bold text-ink-900">Final Price</th>
                        <td class="px-4 py-4 text-right text-ink-300">-</td>
                        <td class="px-4 py-4 text-right font-display text-base font-bold text-brand-700" data-sla-output="final_price">
                            {{ $rupiah($computed['final_price']) }}
                        </td>
                        <td class="px-4 py-4 text-xs font-semibold text-brand-800">Otomatis &middot; HPP + Profit</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @unless ($readonly)
        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="btn-primary px-6 py-2.5" data-sla-submit @disabled($rateMissing)>{{ $submitLabel }}</button>
            <p class="text-xs text-ink-400">
                @if ($rateMissing)
                    Kurs USD/IDR belum tersedia, jadi harga belum dapat ditetapkan.
                @else
                    Angka pada kolom Rupiah dihitung ulang server saat disimpan.
                @endif
            </p>
        </div>
    @endunless
</form>
