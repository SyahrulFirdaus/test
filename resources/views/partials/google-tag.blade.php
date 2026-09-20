{{--
    Google tag (gtag.js) — Google Ads.

    ID-nya dibaca dari config/services.php (`GOOGLE_ADS_ID` di .env), bukan
    ditulis langsung di sini, supaya lingkungan pengembangan dan pengujian dapat
    mematikannya cukup dengan mengosongkan nilainya — tanpa itu, setiap kali
    halaman dibuka di localhost angkanya ikut terkirim ke akun iklan.

    Dipasang pada halaman yang dilihat PENGUNJUNG. Dashboard pengelola sengaja
    tidak ikut: lalu lintas admin dan superadmin bukan calon pelanggan, dan
    menghitungnya hanya mengaburkan data konversi.
--}}
@if (filled($googleAdsId = config('services.google_ads.id')))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAdsId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ $googleAdsId }}');
    </script>
@endif
