{{--
    Banner persetujuan cookie + Google Consent Mode v2.

    - Muncul pada kunjungan pertama dan tetap tampil sampai pengunjung memilih.
    - Default semua penyimpanan iklan & analitik "denied" (lihat
      partials/google-tag); pilihan pengunjung diteruskan lewat
      gtag('consent', 'update', …) dan event dataLayer `consent_update`.
    - Pilihan disimpan di cookie `nusama3d_consent` selama 180 hari dan dapat
      diubah atau ditarik kapan saja lewat tautan "Pengaturan Cookie"
      ([data-cookie-settings]) — sesuai prinsip persetujuan UU PDP: jelas,
      spesifik per tujuan, tidak dicentang lebih dulu, dan dapat ditarik.
    - Teks dua bahasa: Indonesia, atau Inggris bila pengunjung memilih EN.

    Script-nya disertakan di sini (bukan di app.js) agar bekerja juga pada
    layout halaman masuk/daftar yang tidak memuat app.js. Tanpa JavaScript
    banner tetap tersembunyi (tanpa JavaScript tag Google juga tidak berjalan).
--}}
@php
    $texts = [
        'id' => [
            'title' => 'Kami menghargai privasi Anda',
            'body' => 'Kami memakai cookie esensial agar situs berfungsi (sesi login, keamanan, pilihan bahasa). Dengan persetujuan Anda, kami juga memakai cookie analitik dan iklan (Google) untuk mengukur kunjungan dan efektivitas iklan. Anda dapat menerima, menolak, atau memilih sendiri, dan mengubahnya kapan saja.',
            'more' => 'Kebijakan Privasi',
            'and' => 'dan',
            'terms' => 'Syarat & Ketentuan',
            'accept' => 'Terima Semua',
            'reject' => 'Tolak',
            'customize' => 'Atur Pilihan',
            'save' => 'Simpan Pilihan',
            'essential' => 'Esensial',
            'essential_desc' => 'Diperlukan agar situs berfungsi. Selalu aktif.',
            'analytics' => 'Analitik',
            'analytics_desc' => 'Membantu kami memahami cara situs digunakan.',
            'ads' => 'Iklan & Pemasaran',
            'ads_desc' => 'Mengukur konversi iklan Google dan menampilkan iklan yang relevan (termasuk data yang Anda kirim lewat formulir, dalam bentuk ter-hash).',
            'always' => 'Selalu aktif',
        ],
        'en' => [
            'title' => 'We value your privacy',
            'body' => 'We use essential cookies to make this site work (login session, security, language choice). With your consent, we also use analytics and advertising cookies (Google) to measure visits and ad performance. You can accept, reject, or choose for yourself, and change it at any time.',
            'more' => 'Privacy Policy',
            'and' => 'and',
            'terms' => 'Terms & Conditions',
            'accept' => 'Accept All',
            'reject' => 'Reject',
            'customize' => 'Customize',
            'save' => 'Save Choices',
            'essential' => 'Essential',
            'essential_desc' => 'Required for the site to work. Always on.',
            'analytics' => 'Analytics',
            'analytics_desc' => 'Helps us understand how the site is used.',
            'ads' => 'Advertising & Marketing',
            'ads_desc' => 'Measures Google ad conversions and shows relevant ads (including data you submit through forms, in hashed form).',
            'always' => 'Always on',
        ],
    ];
    $t = $texts['id'];
@endphp

<div class="notranslate fixed inset-x-0 bottom-0 z-50 hidden md:inset-x-auto md:bottom-6 md:left-6 md:max-w-md"
     translate="no"
     role="dialog"
     aria-modal="false"
     aria-labelledby="cookie-consent-title"
     data-cookie-banner
     data-texts="{{ json_encode($texts, JSON_UNESCAPED_UNICODE) }}">
    <div class="max-h-[85vh] overflow-y-auto rounded-t-3xl border border-ink-100 bg-white p-5 shadow-[0_-12px_40px_-14px_rgba(15,11,10,0.45)] sm:p-6 md:rounded-2xl md:shadow-[0_18px_40px_-14px_rgba(15,11,10,0.45)]">
        <p id="cookie-consent-title" class="font-display text-base font-bold text-ink-900" data-t="title">{{ $t['title'] }}</p>
        <p class="mt-2 text-xs leading-relaxed text-ink-500">
            <span data-t="body">{{ $t['body'] }}</span>
            <a href="{{ route('legal.privacy') }}#cookie" class="font-semibold text-brand-600 underline decoration-brand-200 underline-offset-2 hover:text-brand-700" data-t="more">{{ $t['more'] }}</a>
            <span data-t="and">{{ $t['and'] }}</span>
            <a href="{{ route('legal.terms') }}" class="font-semibold text-brand-600 underline decoration-brand-200 underline-offset-2 hover:text-brand-700" data-t="terms">{{ $t['terms'] }}</a>.
        </p>

        {{-- Pilihan per tujuan; tidak ada yang dicentang lebih dulu. --}}
        <div class="mt-4 hidden space-y-3" data-cookie-preferences>
            <div class="flex items-start justify-between gap-4 rounded-xl border border-ink-100 bg-ink-50/60 p-3">
                <div>
                    <p class="text-sm font-semibold text-ink-900" data-t="essential">{{ $t['essential'] }}</p>
                    <p class="text-xs text-ink-500" data-t="essential_desc">{{ $t['essential_desc'] }}</p>
                </div>
                <span class="shrink-0 text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-ink-400" data-t="always">{{ $t['always'] }}</span>
            </div>

            @foreach (['analytics', 'ads'] as $purpose)
                <label class="flex cursor-pointer items-start justify-between gap-4 rounded-xl border border-ink-100 p-3">
                    <span>
                        <span class="block text-sm font-semibold text-ink-900" data-t="{{ $purpose }}">{{ $t[$purpose] }}</span>
                        <span class="block text-xs text-ink-500" data-t="{{ $purpose }}_desc">{{ $t[$purpose.'_desc'] }}</span>
                    </span>
                    <input type="checkbox" class="mt-1 h-5 w-5 shrink-0 rounded border-ink-300 text-brand-600 focus:ring-brand-600" data-consent="{{ $purpose }}">
                </label>
            @endforeach
        </div>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <button type="button" class="btn-primary flex-1 px-5 py-2.5 text-xs" data-cookie-accept data-t="accept">{{ $t['accept'] }}</button>
            <button type="button" class="btn-outline flex-1 px-5 py-2.5 text-xs" data-cookie-reject data-t="reject">{{ $t['reject'] }}</button>
            <button type="button" class="px-3 py-2.5 text-xs font-semibold text-ink-600 underline underline-offset-2 hover:text-brand-600" data-cookie-customize data-t="customize">{{ $t['customize'] }}</button>
            <button type="button" class="btn-primary hidden flex-1 px-5 py-2.5 text-xs" data-cookie-save data-t="save">{{ $t['save'] }}</button>
        </div>
    </div>
</div>

<script>
    (function () {
        var COOKIE = 'nusama3d_consent';
        var MAX_AGE = 60 * 60 * 24 * 180;
        var banner = document.querySelector('[data-cookie-banner]');

        if (!banner) {
            return;
        }

        var preferences = banner.querySelector('[data-cookie-preferences]');
        var save = banner.querySelector('[data-cookie-save]');
        var customize = banner.querySelector('[data-cookie-customize]');
        var boxes = {
            analytics: banner.querySelector('[data-consent="analytics"]'),
            ads: banner.querySelector('[data-consent="ads"]')
        };

        // Bahasa mengikuti pilihan ID/EN pengunjung (cookie googtrans).
        try {
            var lang = (document.cookie.match(/(?:^|;\s*)googtrans=\/[a-z-]+\/([a-z-]+)/i) || [])[1];
            var texts = JSON.parse(banner.getAttribute('data-texts'));

            if (lang === 'en' && texts.en) {
                banner.querySelectorAll('[data-t]').forEach(function (el) {
                    el.textContent = texts.en[el.getAttribute('data-t')] || el.textContent;
                });
            }
        } catch (error) {}

        function read() {
            var match = document.cookie.match(new RegExp('(?:^|;\\s*)' + COOKIE + '=([^;]+)'));

            try {
                return match ? JSON.parse(decodeURIComponent(match[1])) : null;
            } catch (error) {
                return null;
            }
        }

        function apply(choice) {
            var ads = choice.ads ? 'granted' : 'denied';

            if (typeof window.gtag === 'function') {
                window.gtag('consent', 'update', {
                    ad_storage: ads,
                    ad_user_data: ads,
                    ad_personalization: ads,
                    analytics_storage: choice.analytics ? 'granted' : 'denied'
                });
            }

            (window.dataLayer = window.dataLayer || []).push({
                event: 'consent_update',
                consent_analytics: choice.analytics ? 'granted' : 'denied',
                consent_ads: ads
            });
        }

        function store(choice) {
            var value = { v: 1, analytics: !!choice.analytics, ads: !!choice.ads, ts: new Date().toISOString() };
            var secure = location.protocol === 'https:' ? '; Secure' : '';

            document.cookie = COOKIE + '=' + encodeURIComponent(JSON.stringify(value)) + '; max-age=' + MAX_AGE + '; path=/; SameSite=Lax' + secure;
            apply(value);
            close();
        }

        function showPreferences(show) {
            preferences.classList.toggle('hidden', !show);
            save.classList.toggle('hidden', !show);
            customize.classList.toggle('hidden', show);
        }

        function open(withPreferences) {
            var current = read();

            boxes.analytics.checked = !!(current && current.analytics);
            boxes.ads.checked = !!(current && current.ads);
            showPreferences(!!withPreferences);
            banner.classList.remove('hidden');
        }

        function close() {
            banner.classList.add('hidden');
        }

        banner.querySelector('[data-cookie-accept]').addEventListener('click', function () {
            store({ analytics: true, ads: true });
        });

        banner.querySelector('[data-cookie-reject]').addEventListener('click', function () {
            store({ analytics: false, ads: false });
        });

        customize.addEventListener('click', function () {
            showPreferences(true);
        });

        save.addEventListener('click', function () {
            store({ analytics: boxes.analytics.checked, ads: boxes.ads.checked });
        });

        // "Pengaturan Cookie" (footer) membuka kembali pilihan.
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest && event.target.closest('[data-cookie-settings]');

            if (trigger) {
                event.preventDefault();
                open(true);
            }
        });

        if (!read()) {
            open(false);
        }
    })();
</script>
