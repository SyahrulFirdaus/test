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

    import('./modules/print-showcase')
        .then(({ default: PrintScene }) => new PrintScene(canvas, options))
        .catch(() => {
            // Gagal memuat scene bukan kesalahan fatal: panel brand tetap
            // memakai latar gradien dan grid seperti halaman auth lainnya.
            canvas.remove();
        });
}

const canvas = document.querySelector('[data-auth-scene]');

if (canvas && webglAvailable()) {
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
