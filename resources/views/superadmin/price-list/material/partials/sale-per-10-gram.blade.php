{{--
    Harga Jual per 10 Gram — hanya tampilan, tidak dapat diisi dan tidak
    ikut terkirim (disabled).

    Dihitung dari Harga Jual per Gram yang BENAR-BENAR dipakai rumus, yaitu
    setelah dibulatkan ke atas kelipatan Rp100 (App\Models\Concerns\HasMaterialPricing),
    lalu dikali 10. Nilainya diperbarui langsung oleh skrip di form material
    setiap kali Harga Jual per Gram diubah.

    Variabel: $salePrice — nilai Harga Jual per Gram saat halaman dibuka.
--}}
@php
    $perGram = is_numeric($salePrice) ? (int) (ceil(((float) $salePrice) / 100) * 100) : null;
@endphp

<label for="sale_price_per_10_gram" class="field-label">Harga Jual per 10 Gram (Rp)</label>
<input type="text"
       id="sale_price_per_10_gram"
       value="{{ $perGram === null ? '' : 'Rp '.number_format($perGram * 10, 0, ',', '.') }}"
       placeholder="Rp 0"
       class="field-input cursor-not-allowed bg-ink-50 font-mono text-ink-500"
       disabled
       data-sale-per-10-gram>
<p class="mt-1.5 text-xs text-ink-400">Otomatis: Harga Jual per Gram (setelah dibulatkan) &times; 10.</p>
