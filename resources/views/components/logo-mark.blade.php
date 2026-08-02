{{--
    Logo NUSAMA3D — hexagon ring dengan pita diagonal di dalam lingkaran tipis.
    Digambar putih di atas lingkaran merah brand, sesuai logo aslinya, sehingga
    tetap terbaca di latar terang maupun gelap tanpa perlu varian terpisah.
--}}
<svg {{ $attributes->merge(['class' => 'h-10 w-10']) }} viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $company->name ?? config('app.name') }}">
    <defs>
        <clipPath id="nusamaHex">
            <path d="M50 12 83 31 83 69 50 88 17 69 17 31Z" />
        </clipPath>
    </defs>

    <circle cx="50" cy="50" r="50" fill="#95271D" />

    {{-- Cincin hexagon --}}
    <path fill-rule="evenodd" clip-rule="evenodd" fill="#ffffff"
          d="M50 12 83 31 83 69 50 88 17 69 17 31Z M50 20.5 25 35.2 25 64.8 50 79.5 75 64.8 75 35.2Z" />

    {{-- Pita diagonal, dipotong mengikuti bentuk hexagon --}}
    <g clip-path="url(#nusamaHex)">
        <path fill="#ffffff" d="M21.7 22.8 87.7 60.8 78.3 77.2 12.3 39.2Z" />
    </g>

    {{-- Lingkaran tipis pembatas --}}
    <circle cx="50" cy="50" r="45.5" fill="none" stroke="#ffffff" stroke-width="1.8" stroke-opacity=".9" />
</svg>
