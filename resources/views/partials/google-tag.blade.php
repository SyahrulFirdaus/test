{{--
    Google tag (gtag.js) — Google Ads, Google Tag Manager (opsional), Consent
    Mode v2, dan event dataLayer situs.

    ID-nya dibaca dari config/services.php (`GOOGLE_ADS_ID` dan `GTM_ID` di
    .env), bukan ditulis langsung di sini, supaya lingkungan pengembangan dan
    pengujian dapat mematikannya cukup dengan mengosongkan nilainya — tanpa
    itu, setiap kali halaman dibuka di localhost angkanya ikut terkirim ke
    akun iklan.

    Dipasang pada halaman yang dilihat PENGUNJUNG. Dashboard pengelola sengaja
    tidak ikut: lalu lintas admin dan superadmin bukan calon pelanggan, dan
    menghitungnya hanya mengaburkan data konversi.

    Urutannya penting:
      1. Consent Mode v2 — default "denied" untuk ad_storage, analytics_storage,
         ad_user_data, ad_personalization, lalu langsung diperbarui bila
         pengunjung sudah pernah memilih (cookie `nusama3d_consent`, diatur
         banner di partials/cookie-banner). Harus sebelum tag mana pun.
      2. Event yang dititipkan server lewat flash `datalayer` (mis. sign_up).
      3. GTM (bila GTM_ID diisi) dan gtag.js Google Ads.
      4. Pelacak klik tel:, mailto:, dan WhatsApp (phone_click, email_click,
         whatsapp_click) dengan cta_location dari atribut data-cta terdekat.
--}}
@php
    $googleAdsId = config('services.google_ads.id');
    $gtmId = \App\Support\GoogleTagManager::id();
    $flashedEvents = session('datalayer');
    $waNumber = preg_replace('/\D/', '', (string) $company?->whatsapp_link);
@endphp

<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}

    // Consent Mode v2: semua penyimpanan iklan & analitik ditolak sampai
    // pengunjung menyetujuinya lewat banner cookie.
    gtag('consent', 'default', {
        ad_storage: 'denied',
        analytics_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        functionality_storage: 'granted',
        security_storage: 'granted',
        wait_for_update: 500
    });
    gtag('set', 'ads_data_redaction', true);
    gtag('set', 'url_passthrough', true);

    (function () {
        var match = document.cookie.match(/(?:^|;\s*)nusama3d_consent=([^;]+)/);

        if (!match) {
            return;
        }

        try {
            var choice = JSON.parse(decodeURIComponent(match[1]));
            var ads = choice.ads ? 'granted' : 'denied';

            gtag('consent', 'update', {
                ad_storage: ads,
                ad_user_data: ads,
                ad_personalization: ads,
                analytics_storage: choice.analytics ? 'granted' : 'denied'
            });
        } catch (error) {}
    })();

    // Kirim event ke dataLayer (GTM). Data `user_data` (email & nomor HP untuk
    // Enhanced Conversions) juga diteruskan ke Google tag, yang men-hash-nya
    // sebelum dikirim — tidak pernah ditaruh di URL.
    window.nusamaTrack = function (event, params) {
        params = params || {};

        if (params.user_data) {
            gtag('set', 'user_data', params.user_data);
        }

        window.dataLayer.push(Object.assign({ event: event }, params));
    };

    @if (is_array($flashedEvents))
        @foreach ($flashedEvents as $flashed)
            @if (is_array($flashed) && filled($flashed['event'] ?? null))
                window.nusamaTrack(@json($flashed['event']), @json(\Illuminate\Support\Arr::except($flashed, 'event')));
            @endif
        @endforeach
    @endif
</script>

@if (filled($gtmId))
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer',@json($gtmId));</script>
    <!-- End Google Tag Manager -->
@endif

@if (filled($googleAdsId))
    <!-- Google tag (gtag.js) -->
    {{-- Berkas gtag.js (±160 KB) baru diunduh setelah halaman tampil — pada
         interaksi pertama atau 4 detik setelah halaman termuat — agar tidak
         memperlambat tampilan awal di ponsel. Antreannya sudah disiapkan di
         atas, jadi tidak ada event yang hilang. --}}
    <script>
        gtag('js', new Date());

        gtag('config', '{{ $googleAdsId }}', { allow_enhanced_conversions: true });

        (function () {
            var loaded = false;

            function loadGtag() {
                if (loaded) {
                    return;
                }

                loaded = true;
                var script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtag/js?id={{ $googleAdsId }}';
                document.head.appendChild(script);
            }

            ['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (type) {
                window.addEventListener(type, loadGtag, { once: true, passive: true });
            });

            window.addEventListener('load', function () {
                setTimeout(loadGtag, 4000);
            });
        })();
    </script>
@endif

<script>
    // Klik tel:, mailto:, dan WhatsApp perusahaan → phone_click, email_click,
    // whatsapp_click. cta_location dibaca dari atribut data-cta terdekat
    // (header / menu / footer / sticky / floating / content); nilai lain
    // (mis. "hero") dicatat sebagai cta_id dengan lokasi "content".
    (function () {
        var waPrefix = 'https://wa.me/' + @json($waNumber);
        var locations = ['header', 'menu', 'footer', 'sticky', 'floating', 'content'];

        function locate(link) {
            var holder = link.closest('[data-cta]');
            var value = holder ? holder.getAttribute('data-cta') : '';

            if (locations.indexOf(value) !== -1) {
                return { cta_location: value };
            }

            if (value) {
                return { cta_location: 'content', cta_id: value };
            }

            return { cta_location: link.closest('header') ? 'header' : (link.closest('footer') ? 'footer' : 'content') };
        }

        document.addEventListener('click', function (event) {
            var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;

            if (!link) {
                return;
            }

            var href = link.getAttribute('href') || '';
            var name = null;

            if (/^tel:/i.test(href)) {
                name = 'phone_click';
            } else if (/^mailto:/i.test(href)) {
                name = 'email_click';
            } else if (waPrefix.length > 'https://wa.me/'.length && href.indexOf(waPrefix) === 0) {
                name = 'whatsapp_click';
            }

            if (name) {
                window.nusamaTrack(name, Object.assign(locate(link), { page_path: window.location.pathname }));
            }
        }, true);
    })();
</script>
