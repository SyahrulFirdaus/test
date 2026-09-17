{{--
    Latar 3D bernuansa merah maroon: grid, cahaya, lantai grid berperspektif,
    kubus rangka yang berputar, dan animasi mesin cetak (Three.js).

    Dipakai halaman masuk (layouts/login) dan hero halaman utama, supaya
    keduanya berbagi tampilan yang sama persis. Pemakainya wajib memuat
    resources/js/auth-scene.js, yang menyalakan kanvas `data-auth-scene`.

    Props:
      position  kelas penempatan, mis. "fixed inset-0" atau "absolute inset-0"
      shiftX    geser mesin ke kiri (negatif) / kanan (positif) dari tengah
      shiftY    geser mesin ke bawah (negatif) / atas (positif)
      scale     besar mesin
--}}
@props([
    'position' => 'absolute inset-0',
    'shiftX' => 0,
    'shiftY' => 0,
    'scale' => 1,
])

<div {{ $attributes->merge(['class' => 'login-scene overflow-hidden bg-gradient-to-br from-brand-800 via-brand-700 to-brand-950 '.$position]) }} aria-hidden="true">
    <div class="blueprint-grid-dark absolute inset-0 opacity-60"></div>

    {{-- Cahaya lembut memberi kedalaman. --}}
    <div class="login-scene-glow"></div>

    {{-- Lantai grid berperspektif yang bergerak menjauh. --}}
    <div class="login-scene-horizon">
        <div class="login-scene-floor"></div>
    </div>

    {{-- Kubus rangka berputar di tepi layar. --}}
    @foreach (['login-cube-a', 'login-cube-b', 'login-cube-c'] as $cube)
        <div class="login-cube {{ $cube }}">
            <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
    @endforeach

    {{-- Animasi mesin cetak, hanya mulai lebar md supaya ponsel tidak ikut
         mengunduh Three.js. --}}
    <canvas data-auth-scene data-scene-shift-x="{{ $shiftX }}" data-scene-shift-y="{{ $shiftY }}" data-scene-scale="{{ $scale }}"
            class="pointer-events-none absolute inset-0 hidden h-full w-full opacity-70 md:block"></canvas>

    {{-- Vignette: tepi lebih gelap, konten di tengah tetap mudah dibaca. --}}
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_35%,rgb(46_10_7/0.65)_100%)]"></div>

    {{ $slot }}
</div>
