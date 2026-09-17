{{--
    Input nominal Rupiah: tampil "Rp 1.500.000", terkirim sebagai angka murni.

    Kotak yang terlihat hanya tampilan — tidak punya `name`, jadi tidak ikut
    terkirim. Nilai yang dikirim ke server dan dibaca kalkulasi rumus ada di
    input tersembunyi di sebelahnya (`data-rupiah-value`), sehingga validasi
    maupun perhitungan di server tetap menerima angka biasa seperti "1500000".

    Tombol ▲▼ (dan tombol panah keyboard) menambah/mengurangi sebesar `step`.
    Perilakunya ada di resources/js/modules/rupiah-input.js.

    Atribut tambahan (mis. data-sla-input) dipasang pada input tersembunyi,
    karena di sanalah nilai mentahnya berada.
--}}
@props([
    'name',
    'value' => null,
    'id' => null,
    'step' => 1000,
    'max' => 999999999999,
    'label' => null,
    // Perataan angka: 'right' untuk kolom tabel, 'left' untuk form biasa.
    'align' => 'right',
    'wrapperClass' => '',
])

@php
    $raw = is_numeric($value) ? (int) round((float) $value) : 0;
    $raw = max(0, min((int) $max, $raw));
    $id ??= $name;
@endphp

<div class="rupiah-input {{ $wrapperClass }}" data-rupiah-input data-step="{{ $step }}" data-max="{{ $max }}">
    <input type="text"
           id="{{ $id }}"
           inputmode="numeric"
           autocomplete="off"
           placeholder="Rp 0"
           value="Rp {{ number_format($raw, 0, ',', '.') }}"
           class="field-input pr-10 font-mono {{ $align === 'left' ? 'text-left' : 'text-right' }}"
           @if ($label) aria-label="{{ $label }}" @endif
           data-rupiah-display>

    <input type="hidden" name="{{ $name }}" value="{{ $raw }}" {{ $attributes }} data-rupiah-value>

    <div class="rupiah-input-spinner" aria-hidden="true">
        <button type="button" tabindex="-1" class="rupiah-input-step" data-rupiah-step="1" title="Tambah {{ number_format((int) $step, 0, ',', '.') }}">
            <svg viewBox="0 0 10 6" class="h-2 w-2.5" fill="currentColor"><path d="M5 0 10 6H0z"/></svg>
        </button>
        <button type="button" tabindex="-1" class="rupiah-input-step" data-rupiah-step="-1" title="Kurangi {{ number_format((int) $step, 0, ',', '.') }}">
            <svg viewBox="0 0 10 6" class="h-2 w-2.5" fill="currentColor"><path d="M0 0h10L5 6z"/></svg>
        </button>
    </div>
</div>
