/**
 * Pelengkap `scroll-behavior: smooth` pada CSS:
 * - memindahkan fokus ke target anchor agar tetap ramah keyboard & screen reader
 * - mengatur tampil/sembunyi tombol kembali ke atas
 */
export default function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const id = link.getAttribute('href');

            if (!id || id === '#') {
                return;
            }

            const target = document.querySelector(id);

            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            history.replaceState(null, '', id);

            // Beri fokus tanpa memicu lompatan scroll bawaan browser.
            target.setAttribute('tabindex', '-1');
            target.focus({ preventScroll: true });
        });
    });

    const toTop = document.querySelector('[data-scroll-top]');

    if (!toTop) {
        return;
    }

    const toggleVisibility = () => {
        const visible = window.scrollY > 600;
        toTop.classList.toggle('opacity-0', !visible);
        toTop.classList.toggle('pointer-events-none', !visible);
        toTop.classList.toggle('translate-y-3', !visible);
    };

    toggleVisibility();
    window.addEventListener('scroll', toggleVisibility, { passive: true });

    toTop.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}
