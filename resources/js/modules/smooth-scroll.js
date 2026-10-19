/**
 * Pelengkap `scroll-behavior: smooth` pada CSS:
 * - memindahkan fokus ke target anchor agar tetap ramah keyboard & screen reader
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

    // Tautan yang datang dari halaman lain membawa anchor di URL-nya — misalnya
    // tombol "Learn More" pada Edit Specification yang menuju material tertentu
    // di halaman panduan. Lompatan bawaan browser terjadi sebelum AOS selesai
    // menata halaman sehingga posisinya meleset, jadi diulang sekali setelah
    // seluruh isi halaman siap.
    const scrollToHash = () => {
        if (! window.location.hash) {
            return;
        }

        let target = null;

        try {
            target = document.querySelector(window.location.hash);
        } catch {
            return;
        }

        target?.scrollIntoView({ block: 'start' });
    };

    window.addEventListener('load', () => window.setTimeout(scrollToHash, 150));
}
