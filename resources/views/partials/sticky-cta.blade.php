{{--
    Bilah CTA sticky di ponsel (< 768 px): "WhatsApp" dan "Minta Penawaran".

    - WhatsApp memakai teks otomatis halaman ini (App\Support\WhatsAppLink).
    - "Minta Penawaran" menuju formulir di halaman itu bila ada
      (@section('quote-href'), mis. #form-kontak); selain itu ke 3D Models.
    - Tidak menutupi konten: <body> diberi ruang bawah setinggi bilah, dan
      bilah menyingkir selama tombol kirim formulir terlihat atau kolom isian
      sedang diketik (keyboard ponsel terbuka).
    Di layar lebar tetap memakai tombol melayang partials/consult-cta.
--}}
@php
    $quoteHref = trim($__env->yieldContent('quote-href')) ?: route('models');
@endphp

<div class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-100 bg-white/95 px-3 pt-2 shadow-[0_-10px_30px_-18px_rgba(15,11,10,0.45)] backdrop-blur transition-transform duration-300 md:hidden"
     style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom));"
     data-sticky-cta>
    <div class="mx-auto grid max-w-md grid-cols-2 gap-2">
        <a href="{{ wa_link() }}"
           @if ($company->whatsapp_link) target="_blank" rel="noopener noreferrer" @endif
           data-cta="sticky"
           data-cta-action="whatsapp"
           data-wa-link
           class="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-emerald-600 bg-emerald-600 px-4 text-sm font-bold text-white transition-colors hover:bg-emerald-700">
            <x-icons.whatsapp class="h-4 w-4" />
            WhatsApp
        </a>
        <a href="{{ $quoteHref }}"
           data-cta="sticky"
           data-cta-action="quote"
           class="inline-flex h-12 items-center justify-center rounded-full bg-brand-600 px-4 text-sm font-bold text-white transition-colors hover:bg-brand-700">
            Minta Penawaran
        </a>
    </div>
</div>

<script>
    (function () {
        var bar = document.querySelector('[data-sticky-cta]');

        if (!bar) {
            return;
        }

        var hiddenBySubmit = false;
        var hiddenByTyping = false;

        function update() {
            var hide = hiddenBySubmit || hiddenByTyping;
            bar.style.transform = hide ? 'translateY(110%)' : '';
            bar.setAttribute('aria-hidden', hide ? 'true' : 'false');
            bar.querySelectorAll('a').forEach(function (link) {
                link.tabIndex = hide ? -1 : 0;
            });
        }

        // Tombol kirim formulir di area konten tidak pernah tertutup bilah.
        if ('IntersectionObserver' in window) {
            var visible = new Set();
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        visible.add(entry.target);
                    } else {
                        visible.delete(entry.target);
                    }
                });
                hiddenBySubmit = visible.size > 0;
                update();
            });

            document.querySelectorAll('main form [type="submit"]').forEach(function (button) {
                observer.observe(button);
            });
        }

        document.addEventListener('focusin', function (event) {
            if (event.target.matches('main input:not([type="file"]):not([type="checkbox"]):not([type="radio"]), main textarea, main select')) {
                hiddenByTyping = true;
                update();
            }
        });

        document.addEventListener('focusout', function () {
            setTimeout(function () {
                var active = document.activeElement;
                hiddenByTyping = !!(active && active.matches && active.matches('main input, main textarea, main select'));
                update();
            }, 50);
        });
    })();
</script>
