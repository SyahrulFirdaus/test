{{--
    Google Fonts tanpa memblokir render: stylesheet dimuat lebih dulu sebagai
    preload lalu diaktifkan saat selesai diunduh, sehingga teks langsung tampil
    dengan font cadangan dan berganti ke font asli (display=swap). Tanpa
    JavaScript, <noscript> memuatnya seperti biasa.
--}}
@php($fontsUrl = 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="{{ $fontsUrl }}" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="{{ $fontsUrl }}"></noscript>
