/**
 * Viewer 3D pada dashboard Admin/Superadmin — pratinjau satu model penawaran.
 *
 * Berkasnya diambil apa adanya dari route unduh model (hak aksesnya sama
 * dengan mengunduh), lalu hanya digambar di browser: tidak ada yang diubah,
 * disalin, maupun dikirim kembali ke server.
 *
 * Pemuat berkas dan scene-nya dipakai bersama viewer publik — lihat
 * ./modules/mesh-preview.js.
 */
import { countTriangles, createPreview, loadObject, webglAvailable } from './modules/mesh-preview';

const root = document.querySelector('[data-admin-model-viewer]');

if (root) {
    boot(root);
}

async function boot(container) {
    const stage = container.querySelector('[data-viewer-canvas]');
    const url = container.dataset.fileUrl;
    const name = container.dataset.fileName ?? '';

    if (!url) {
        fail(container, 'Berkas model tidak ditemukan di penyimpanan.');

        return;
    }

    if (!webglAvailable()) {
        fail(container, 'Browser ini tidak mendukung WebGL.');

        return;
    }

    let object;

    try {
        const response = await fetch(url, { credentials: 'same-origin' });

        if (!response.ok) {
            throw new Error(`Berkas tidak dapat diambil (HTTP ${response.status}).`);
        }

        object = await loadObject(await response.arrayBuffer(), name);
    } catch (error) {
        console.error(error);
        fail(container, error?.message ?? '');

        return;
    }

    const viewer = createPreview(stage, object);

    container.querySelector('[data-viewer-loading]')?.remove();
    describe(container, viewer.size, countTriangles(object));
    wireControls(container, viewer);
}

/* --------------------------------------------------------------- kontrol --- */

function wireControls(container, viewer) {
    container.querySelectorAll('[data-view]').forEach((button) => {
        button.disabled = false;
        button.addEventListener('click', () => viewer.setView(button.dataset.view));
    });

    const wireframe = container.querySelector('[data-wireframe]');

    if (wireframe) {
        wireframe.disabled = false;
        wireframe.addEventListener('click', () => {
            const on = wireframe.getAttribute('aria-pressed') !== 'true';

            viewer.setWireframe(on);
            wireframe.setAttribute('aria-pressed', String(on));
        });
    }

    const fullscreen = container.querySelector('[data-fullscreen]');

    if (fullscreen && document.fullscreenEnabled) {
        fullscreen.disabled = false;
        fullscreen.addEventListener('click', () => {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                container.requestFullscreen?.();
            }
        });
    }
}

function describe(container, size, triangles) {
    const number = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
    const dimensions = container.querySelector('[data-viewer-dimensions]');
    const count = container.querySelector('[data-viewer-triangles]');

    if (dimensions) {
        dimensions.textContent = `Dimensi berkas: ${number.format(size.x)} × ${number.format(size.y)} × ${number.format(size.z)} mm`;
    }

    if (count) {
        count.textContent = `Segitiga: ${new Intl.NumberFormat('id-ID').format(triangles)}`;
    }
}

function fail(container, detail) {
    container.querySelector('[data-viewer-loading]')?.remove();

    const error = container.querySelector('[data-viewer-error]');

    if (error) {
        error.classList.remove('hidden');
        error.classList.add('flex');
    }

    const detailEl = container.querySelector('[data-viewer-error-detail]');

    if (detailEl && detail) {
        detailEl.textContent = detail;
    }
}
