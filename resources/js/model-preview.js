/**
 * Halaman 3D Viewer publik — /3d-models/{id}/viewer.
 *
 * Hanya pratinjau: object 3D, informasi dasar (nama, format, dimensi,
 * volume), dan kontrol viewer. Tidak ada spesifikasi cetak, berat, maupun
 * harga di halaman ini.
 *
 * Berkasnya diambil dari penyimpanan browser pengunjung (IndexedDB) memakai id
 * pada URL — berkas yang sama yang diunggah di halaman 3D Models. Tidak ada
 * yang diunduh dari maupun dikirim ke server, dan tidak ada salinan berkas
 * yang dibuat. Id yang tidak ada di browser ini menampilkan pesan "tidak
 * ditemukan"; model milik pengunjung lain memang tidak pernah dapat terbaca
 * karena tidak pernah meninggalkan perangkat mereka.
 */
import { createPreview, loadObject, webglAvailable } from './modules/mesh-preview';
import { modelStore } from './modules/model-store';

const root = document.querySelector('[data-model-preview]');

if (root) {
    boot(root).catch((error) => {
        console.error(error);
        showState(root, 'error', error?.message);
    });
}

async function boot(container) {
    const id = container.dataset.modelId;

    if (!webglAvailable()) {
        showState(container, 'error', 'Browser ini tidak mendukung WebGL.');

        return;
    }

    let record = null;

    try {
        record = id ? await modelStore.find(id) : null;
    } catch (error) {
        console.error(error);
    }

    if (!record?.blob) {
        setText(container, 'name', 'Model tidak ditemukan');
        showState(container, 'missing');

        return;
    }

    describe(container, record);

    // Berkas CAD (STEP/STP) sudah ditesselasi saat diunggah; hasilnya yang
    // digambar, jadi pustaka CAD tidak perlu dimuat ulang di sini.
    const source = record.mesh ?? record.blob;
    const format = record.mesh ? record.meshFormat ?? 'stl' : null;

    let object;

    try {
        object = await loadObject(await source.arrayBuffer(), record.name, format);
    } catch (error) {
        console.error(error);
        showState(container, 'error', error?.message);

        return;
    }

    const viewer = createPreview(container.querySelector('[data-viewer-canvas]'), object);

    // Dimensi mengikuti orientasi yang sedang tampil. Skalanya diambil dari
    // daftar model (yang sudah memperhitungkan skala pilihan pengguna) dengan
    // membandingkan sisi terpanjang — sisi terpanjang tidak berubah oleh putaran.
    const listed = record.summary?.dimensions;
    const scale = listed
        ? Math.max(listed.x, listed.y, listed.z) / Math.max(viewer.size.x, viewer.size.y, viewer.size.z) || 1
        : 1;

    const refreshOrientation = () => {
        const { x, y, z, dimensions } = viewer.orientation();

        setText(container, 'dimensions', formatDimensions(dimensions.clone().multiplyScalar(scale)));

        container.querySelectorAll('[data-rotation-readout]').forEach((el) => {
            el.textContent = `X ${x}° · Y ${y}° · Z ${z}°`;
        });
    };

    refreshOrientation();

    container.querySelector('[data-viewer-loading]')?.remove();
    wireControls(container, viewer, refreshOrientation);
}

/* ---------------------------------------------------------- informasi --- */

const number = (value, digits) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: digits }).format(Number(value) || 0);

function formatDimensions(size) {
    return `${number(size.x, 1)} × ${number(size.y, 1)} × ${number(size.z, 1)} mm`;
}

function describe(container, record) {
    const summary = record.summary ?? {};

    setText(container, 'name', record.name);
    setText(container, 'format', String(record.format ?? '').toUpperCase() || '-');
    setText(container, 'dimensions', summary.dimensions ? formatDimensions(summary.dimensions) : '-');
    setText(container, 'volume', summary.volumeCm3 ? `${number(summary.volumeCm3, 2)} cm³` : '-');

    document.title = `${record.name} · 3D Viewer`;
}

/** Nama model tampil di judul halaman dan di panel informasi. */
function setText(container, key, value) {
    document.querySelectorAll(`[data-model-info="${key}"]`).forEach((el) => {
        el.textContent = value;
    });
}

/* ------------------------------------------------------------- kontrol --- */

function wireControls(container, viewer, refreshOrientation) {
    // Rotasi object: 90° per klik pada sumbu X, Y, atau Z (hanya tampilan).
    container.querySelectorAll('[data-rotate-object]').forEach((button) => {
        button.disabled = false;

        button.addEventListener('click', () => {
            viewer.rotateObject(button.dataset.rotateObject, 90);
            refreshOrientation();
        });
    });

    const buttons = container.querySelectorAll('[data-viewer-action]');

    buttons.forEach((button) => {
        button.disabled = false;

        button.addEventListener('click', () => {
            switch (button.dataset.viewerAction) {
                case 'rotate': {
                    const on = button.getAttribute('aria-pressed') !== 'true';
                    viewer.setAutoRotate(on);
                    button.setAttribute('aria-pressed', String(on));
                    break;
                }
                case 'zoom-in':
                    viewer.zoom(0.8);
                    break;
                case 'zoom-out':
                    viewer.zoom(1.25);
                    break;
                case 'reset':
                    // Orientasi object dikembalikan dulu, baru sudut pandang
                    // dipasang ulang terhadap bentuk aslinya.
                    viewer.resetObject();
                    viewer.reset();
                    refreshOrientation();
                    container.querySelector('[data-viewer-action="rotate"]')?.setAttribute('aria-pressed', 'false');
                    viewer.setAutoRotate(false);
                    break;
            }
        });
    });
}

/* ------------------------------------------------------------- keadaan --- */

/**
 * Tampilkan keadaan pengganti viewer.
 *
 * @param {'missing'|'error'} state
 */
function showState(container, state, detail = '') {
    container.querySelector('[data-viewer-loading]')?.remove();

    const panel = container.querySelector(`[data-viewer-state="${state}"]`);

    if (panel) {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
    }

    const detailEl = panel?.querySelector('[data-viewer-state-detail]');

    if (detailEl && detail) {
        detailEl.textContent = detail;
    }

    container.querySelector('[data-viewer-hint]')?.classList.add('hidden');

    // Tanpa model, kontrol dan informasinya tidak berarti apa-apa.
    if (state === 'missing') {
        container.querySelector('[data-model-info-panel]')?.classList.add('hidden');
    }
}
