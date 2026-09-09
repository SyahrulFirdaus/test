@php
    /**
     * Logo NUSAMA3D.
     *
     * Bila berkas logo resmi tersedia di public/images (logo.svg, logo.png,
     * atau logo.webp), berkas itulah yang dipakai di seluruh website. Selama
     * belum ada, marka di bawah ini yang tampil: cincin heksagon dengan pita
     * diagonal dan dua segitiga yang ujungnya menunjuk ke pita.
     *
     * Cukup simpan berkas logonya dengan salah satu nama di atas — navbar,
     * footer, halaman masuk, dan dashboard langsung ikut berganti tanpa
     * perubahan kode. Ukuran dan posisinya tetap sama sehingga tata letak
     * halaman tidak bergeser.
     */
    $logoFile = collect(['logo.svg', 'logo.png', 'logo.webp'])
        ->first(fn (string $file) => is_file(public_path('images/'.$file)));

    $label = $company->name ?? config('app.name');
@endphp

@if ($logoFile)
    <img src="{{ asset('images/'.$logoFile) }}"
         alt="{{ $label }}"
         {{ $attributes->merge(['class' => 'h-10 w-10 shrink-0 rounded-full object-cover']) }}>
@else
    <svg {{ $attributes->merge(['class' => 'h-10 w-10']) }} viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{{ $label }}">
        <circle cx="50" cy="50" r="50" fill="#A50505" />

        {{-- Marka diperkecil sedikit agar tidak menempel tepi cakram. --}}
        <g transform="translate(50 49.7) scale(.86) translate(-50 -49.7)">
            <path d="M50 6.5 87 28.2v43L50 92.9 13 71.2v-43Z"
                  fill="none" stroke="#ffffff" stroke-width="2.9" />

            {{-- Pita diagonal --}}
            <path fill="#ffffff" d="M20.45 29.9 79.55 56.74V69.5L20.45 42.66Z" />

            {{-- Segitiga kanan atas & kiri bawah, ujungnya menunjuk ke pita --}}
            <path fill="#ffffff" d="M79.55 32.02v19.94L64.37 42Z" />
            <path fill="#ffffff" d="M20.45 67.38V47.44L35.63 57.4Z" />
        </g>
    </svg>
@endif
