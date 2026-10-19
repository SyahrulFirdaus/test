{{--
    Google Tag Manager (noscript) — tepat setelah <body>, pasangan snippet
    <head> di partials/google-tag. Tidak dimuat bila GTM_ID kosong/tidak sah.
--}}
@if ($gtmId = \App\Support\GoogleTagManager::id())
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ rawurlencode($gtmId) }}" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
@endif
