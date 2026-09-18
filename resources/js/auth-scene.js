/**
 * Entry point terpisah untuk halaman Masuk.
 *
 * Isinya sengaja ringan: latar halaman memuat animasi 3D, tetapi bundel
 * Three.js baru diunduh lewat dynamic import ketika kanvasnya memang tampil —
 * di layar kecil kanvas itu disembunyikan, jadi ponsel tidak ikut menanggung
 * ongkosnya. Kartu formulirnya datar dan tidak digerakkan sama sekali.
 */

/** Beberapa perangkat/browser lama tidak menyediakan konteks WebGL. */
function webglAvailable() {
    try {
        const probe = document.createElement('canvas');

        return Boolean(
            window.WebGLRenderingContext &&
            (probe.getContext('webgl2') || probe.getContext('webgl'))
        );
    } catch {
        return false;
    }
}

function startScene(canvas) {
    // Komposisi diatur dari markup: tiap halaman punya ruang kosong di tempat
    // yang berbeda, jadi posisi mesin ikut menyesuaikan.
    const options = {
        shiftX: Number(canvas.dataset.sceneShiftX || 0),
        shiftY: Number(canvas.dataset.sceneShiftY || 0),
        scale: Number(canvas.dataset.sceneScale || 1),
    };

    // Tanpa varian: part yang sedang dicetak (halaman utama & Masuk). Dengan
    // varian: objek hero milik halaman itu sendiri (lihat hero-scenes.js).
    const variant = canvas.dataset.sceneVariant;
    const load = variant
        ? import('./modules/hero-scenes').then(({ HERO_SCENES }) => {
            const Scene = HERO_SCENES[variant];

            if (!Scene) {
                throw new Error(`Varian scene "${variant}" tidak dikenal.`);
            }

            return new Scene(canvas, options);
        })
        : import('./modules/print-showcase').then(({ default: PrintScene }) => new PrintScene(canvas, options));

    load
        .then((scene) => pauseWhenOffscreen(canvas, scene))
        .catch(() => {
            // Gagal memuat scene bukan kesalahan fatal: panel brand tetap
            // memakai latar gradien dan grid seperti halaman auth lainnya.
            canvas.remove();
        });
}

/**
 * Satu halaman dapat memuat lebih dari satu kanvas 3D (hero dan bagian
 * analyzer di halaman utama). Yang sedang tidak terlihat di layar tidak perlu
 * digambar sama sekali.
 */
function pauseWhenOffscreen(canvas, scene) {
    if (!('IntersectionObserver' in window)) {
        return;
    }

    new IntersectionObserver(([entry]) => {
        scene.paused = !entry.isIntersecting;
        // Waktu yang lewat selama dijeda tidak ikut dihitung.
        scene.clock?.getDelta();
    }).observe(canvas);
}

function watch(canvas) {
    if (canvas.clientWidth > 0) {
        startScene(canvas);
    } else if ('ResizeObserver' in window) {
        // Jendela sempit: tunggu sampai panelnya benar-benar muncul.
        const observer = new ResizeObserver(() => {
            if (canvas.clientWidth > 0) {
                observer.disconnect();
                startScene(canvas);
            }
        });

        observer.observe(canvas);
    }
}

if (webglAvailable()) {
    document.querySelectorAll('[data-auth-scene]').forEach(watch);
}
