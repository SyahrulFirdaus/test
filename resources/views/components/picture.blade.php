{{--
    Gambar dengan versi WebP bila tersedia (berkas .webp di samping aslinya),
    dengan berkas asli sebagai cadangan untuk browser lama. <picture> memakai
    `display: contents` sehingga tata letak sama persis dengan <img> biasa.

    Pemakaian: <x-picture src="images/photos/machine-01.jpg" alt="…" class="…" />
--}}
@props(['src'])

@php
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);
    $hasWebp = $webp !== $src && is_file(public_path($webp));
@endphp

@if ($hasWebp)
    <picture class="contents">
        <source type="image/webp" srcset="{{ asset($webp) }}">
        <img src="{{ asset($src) }}" {{ $attributes }}>
    </picture>
@else
    <img src="{{ asset($src) }}" {{ $attributes }}>
@endif
