/**
 * Carousel video pada hero halaman utama.
 *
 * Empat slide berganti otomatis. Videonya dimuat malas: hanya slide yang
 * sedang tampil yang benar-benar diputar, sehingga halaman tetap ringan dan
 * kuota pengunjung tidak habis untuk video yang tidak dilihat.
 *
 * Setiap slide selalu memasang gambar poster di belakang videonya. Bila berkas
 * videonya belum tersedia atau gagal dimuat, posternya yang tampil — hero tidak
 * pernah kosong.
 *
 * Perpindahan otomatis berhenti saat pengguna mengarahkan kursor, memakai
 * keyboard di dalamnya, atau mengaktifkan "reduce motion" di sistemnya.
 */

const INTERVAL_MS = 6500;

export default function initHeroCarousel() {
    const root = document.querySelector('[data-hero-carousel]');

    if (!root) {
        return;
    }

    const slides = [...root.querySelectorAll('[data-hero-slide]')];
    const dots = [...root.querySelectorAll('[data-hero-dot]')];

    if (slides.length === 0) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let index = 0;
    let timer = null;
    let paused = false;

    /** Putar video slide aktif, hentikan yang lain agar tidak berjalan diam-diam. */
    const syncVideos = () => {
        slides.forEach((slide, position) => {
            const video = slide.querySelector('[data-hero-video]');

            if (!video) {
                return;
            }

            if (position !== index) {
                video.pause();
                video.classList.add('opacity-0');

                return;
            }

            // Sumber baru dimuat saat slidenya benar-benar dibutuhkan.
            if (video.preload === 'none') {
                video.preload = 'auto';
                video.load();
            }

            const play = video.play();

            // Autoplay dapat ditolak browser; posternya tetap tampil, jadi tidak
            // ada yang perlu ditangani selain membiarkannya.
            play?.then(() => video.classList.remove('opacity-0')).catch(() => {});
        });
    };

    const caption = document.querySelector('[data-hero-caption]');

    const show = (next) => {
        index = (next + slides.length) % slides.length;

        slides.forEach((slide, position) => {
            const active = position === index;

            slide.classList.toggle('opacity-0', !active);
            slide.toggleAttribute('aria-hidden', !active);
        });

        dots.forEach((dot, position) => {
            dot.setAttribute('aria-selected', String(position === index));
        });

        // Keterangan di bawah deskripsi ikut menyebut slide yang sedang tampil.
        const active = dots[index];

        if (caption && active?.dataset.heroLabel) {
            caption.textContent = `${active.dataset.heroLabel} — ${active.dataset.heroText ?? ''}`.trim();
        }

        syncVideos();
    };

    const stop = () => {
        clearInterval(timer);
        timer = null;
    };

    const start = () => {
        if (reduceMotion || paused || timer) {
            return;
        }

        timer = setInterval(() => show(index + 1), INTERVAL_MS);
    };

    /** Perpindahan manual selalu mengulang hitungan mundurnya dari awal. */
    const goTo = (next) => {
        show(next);
        stop();
        start();
    };

    root.querySelector('[data-hero-prev]')?.addEventListener('click', () => goTo(index - 1));
    root.querySelector('[data-hero-next]')?.addEventListener('click', () => goTo(index + 1));

    dots.forEach((dot, position) => {
        dot.addEventListener('click', () => goTo(position));
    });

    // Berhenti selama pengunjung sedang memperhatikan satu slide.
    ['mouseenter', 'focusin'].forEach((type) => {
        root.addEventListener(type, () => {
            paused = true;
            stop();
        });
    });

    ['mouseleave', 'focusout'].forEach((type) => {
        root.addEventListener(type, () => {
            paused = false;
            start();
        });
    });

    // Tab yang tidak terlihat tidak perlu memutar video sama sekali.
    document.addEventListener('visibilitychange', () => {
        document.hidden ? stop() : start();
    });

    show(0);
    start();
}
