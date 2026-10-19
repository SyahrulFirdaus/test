/**
 * Entry point terpisah untuk halaman "3D Models".
 * Three.js hanya diunduh di halaman ini, bukan pada seluruh website.
 *
 * Supaya halaman cepat tampil di ponsel, workspace (beserta Three.js) tidak
 * lagi dimuat saat halaman dibuka, melainkan ketika memang dibutuhkan:
 * - masih ada model tersimpan dari kunjungan sebelumnya (perlu ditampilkan), atau
 * - pengguna mulai berinteraksi: menyentuh/mengarahkan ke area unggah,
 *   memfokuskan input berkas, menyeret berkas, atau menyentuh layar.
 * Berkas yang dipilih/di-drop sebelum workspace siap ditampung lalu diproses
 * begitu siap, sehingga alur unggah tetap sama bagi pengguna.
 */
import { modelStore } from './modules/model-store';

const root = document.querySelector('[data-model-viewer]');

if (root) {
    const unsupported = root.querySelector('[data-viewer-unsupported]');
    const input = root.querySelector('[data-file-input]');
    const dropzone = root.querySelector('[data-dropzone]');

    // Beberapa perangkat/browser lama tidak menyediakan konteks WebGL.
    const webglAvailable = (() => {
        try {
            const canvas = document.createElement('canvas');

            return Boolean(
                window.WebGLRenderingContext &&
                (canvas.getContext('webgl2') || canvas.getContext('webgl'))
            );
        } catch {
            return false;
        }
    })();

    // Tanpa WebGL halaman tetap berfungsi penuh: unggah, analisis, estimasi,
    // dan permintaan penawaran berjalan seperti biasa. Yang ditiadakan hanya
    // pratinjau 3D, disertai pemberitahuan singkat untuk pengguna.
    if (!webglAvailable) {
        if (unsupported) {
            unsupported.style.display = 'block';
        }

        root.querySelectorAll('[data-viewer-hint]').forEach((hint) => {
            hint.style.display = 'none';
        });
    }

    let workspace = null;
    let loading = null;
    let pendingFiles = [];

    const start = () => {
        loading ??= Promise.all([
            import('./modules/model-workspace'),
            import('./modules/quotation-form'),
        ]).then(([{ default: ModelWorkspace }, { default: initQuotationForm }]) => {
            workspace = new ModelWorkspace(root, { webgl: webglAvailable });
            initQuotationForm(workspace, root);

            // Berkas yang sudah dipilih sebelum workspace siap.
            if (pendingFiles.length) {
                workspace.handleFiles(pendingFiles);
                pendingFiles = [];
            }
        });

        return loading;
    };

    // Penampung sementara selama workspace belum siap. Setelah siap,
    // workspace memasang pendengarnya sendiri dan penampung ini diam.
    const hold = (files) => {
        if (workspace || !files?.length) {
            return;
        }

        pendingFiles = [...files];
        start();
    };

    input?.addEventListener('change', () => hold(input.files));

    dropzone?.addEventListener('drop', (event) => {
        if (!workspace) {
            event.preventDefault();
            hold(event.dataTransfer?.files);
        }
    });

    // Mencegah browser membuka berkas yang di-drop selama workspace dimuat.
    ['dragover', 'drop'].forEach((type) => {
        window.addEventListener(type, (event) => {
            if (!workspace) {
                event.preventDefault();
            }
        });
    });

    // Interaksi pertama memicu pemuatan; pengguna biasanya masih memilih
    // berkas di dialog saat workspace selesai dimuat.
    const intents = [
        [dropzone, 'pointerenter'],
        [dropzone, 'pointerdown'],
        [input, 'focus'],
        [window, 'dragenter'],
        [window, 'touchstart'],
        [window, 'keydown'],
    ];

    intents.forEach(([target, type]) => target?.addEventListener(type, start, { once: true, passive: true }));

    // Model dari kunjungan sebelumnya langsung ditampilkan seperti biasa.
    modelStore.count()
        .then((count) => {
            if (count > 0) {
                start();
            }
        })
        .catch(() => start());
}
