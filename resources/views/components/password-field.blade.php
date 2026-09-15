@props([
    'id' => 'password',
    'name' => 'password',
    'autocomplete' => 'current-password',
    'placeholder' => null,
    'required' => false,

    // Jarak atas dipegang pembungkusnya, bukan inputnya: tombol mata
    // diposisikan terhadap pembungkus, jadi margin pada input akan
    // menggesernya ke atas. Pemanggil menetralkan margin bawaan kelas
    // inputnya dengan `mt-0`.
    'wrapperClass' => 'mt-2',
])

{{--
    Field kata sandi beserta tombol lihat/sembunyikan.

    Tombolnya hanya menukar atribut `type` input antara `password` dan `text`.
    Nilai yang diketik, nama field, dan formulir yang dikirim tidak tersentuh
    sama sekali — proses autentikasi berjalan persis seperti sebelumnya.

    Dibuat sebagai komponen supaya Login User dan Login Admin memakai perilaku
    yang sama meski kelas inputnya berbeda: kelas halaman diteruskan lewat
    atribut biasa, komponen hanya menambahkan ruang di kanan untuk tombolnya.
--}}

<div class="relative {{ $wrapperClass }}">
    <input type="password"
           id="{{ $id }}"
           name="{{ $name }}"
           autocomplete="{{ $autocomplete }}"
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @required($required)
           {{ $attributes->merge(['class' => 'pr-12']) }}>

    <button type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-ink-400 transition-colors hover:text-brand-600 focus:text-brand-600 focus:outline-none"
            data-password-toggle
            aria-controls="{{ $id }}"
            aria-pressed="false"
            aria-label="Tampilkan kata sandi"
            title="Tampilkan kata sandi">
        <x-icons.eye class="h-5 w-5" data-password-icon="show" />
        <x-icons.eye-off class="hidden h-5 w-5" data-password-icon="hide" />
    </button>
</div>

@once
    {{-- Satu listener untuk seluruh field kata sandi di halaman ini. Halaman
         login tidak memuat bundel JavaScript apa pun, jadi perilakunya ikut
         bersama komponennya dan hanya dicetak sekali per halaman. --}}
    <script>
        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('[data-password-toggle]');

            if (!toggle) {
                return;
            }

            var input = document.getElementById(toggle.getAttribute('aria-controls'));

            if (!input) {
                return;
            }

            var reveal = input.type === 'password';

            input.type = reveal ? 'text' : 'password';

            var label = reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
            toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);

            var show = toggle.querySelector('[data-password-icon="show"]');
            var hide = toggle.querySelector('[data-password-icon="hide"]');

            if (show && hide) {
                show.classList.toggle('hidden', reveal);
                hide.classList.toggle('hidden', !reveal);
            }

            // Kursor dikembalikan ke akhir teks: mengganti `type` membuat
            // sebagian browser memindahkannya ke awal.
            if (input === document.activeElement) {
                var end = input.value.length;
                input.setSelectionRange(end, end);
            }
        });
    </script>
@endonce
