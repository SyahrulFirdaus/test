{{--
    Objek 3D dekoratif di panel kiri halaman Daftar: simpul torus berhalo
    partikel (varian `register` pada resources/js/modules/hero-scenes.js),
    berbeda dari objek 3D halaman lain.

    Panel kirinya hanya tampil mulai lebar lg (lihat layouts/auth), jadi di
    ponsel formulir langsung terlihat penuh dan Three.js tidak ikut diunduh.
    Tidak bisa diklik dan tidak menyentuh formulir sama sekali.
--}}
{{-- Digeser ke atas: judul dan poin panel berada di tengah-bawah, jadi
     objeknya mengisi ruang antara logo dan judul, tidak menimpa teks. --}}
<x-scene-3d variant="register" position="absolute inset-0" shift-x="0" shift-y="0.3" scale="1.6" />

@push('scripts')
    @vite('resources/js/auth-scene.js')
@endpush
