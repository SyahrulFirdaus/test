/**
 * Animasi angka statistik saat elemen pertama kali masuk viewport.
 * Elemen menggunakan atribut data-counter="<angka tujuan>".
 */
export default function initCounters({ animate = true } = {}) {
    const counters = document.querySelectorAll('[data-counter]');

    if (counters.length === 0) {
        return;
    }

    const format = (value) => new Intl.NumberFormat('id-ID').format(Math.round(value));

    if (!animate || !('IntersectionObserver' in window)) {
        counters.forEach((el) => {
            el.textContent = format(Number(el.dataset.counter) || 0);
        });

        return;
    }

    const run = (el) => {
        const target = Number(el.dataset.counter) || 0;
        const duration = 1400;
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            // easeOutExpo — cepat di awal lalu melambat halus di akhir.
            const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);

            el.textContent = format(target * eased);

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    run(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.4 }
    );

    counters.forEach((el) => {
        el.textContent = '0';
        observer.observe(el);
    });
}
