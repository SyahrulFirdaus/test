/**
 * Halaman Viewer 3D — satu model, seluruh fitur analisis.
 *
 * Modelnya diambil dari IndexedDB memakai id pada query string, bukan diunduh
 * dari server: berkas 3D tetap berada di perangkat pengguna sampai penawaran
 * benar-benar dikirim.
 *
 * Setiap perubahan pengaturan disimpan kembali beserta thumbnail barunya, lalu
 * disiarkan ke tab lain sehingga daftar di halaman 3D Models ikut menyesuaikan
 * tanpa perlu dimuat ulang.
 */
import SharedRenderer from './modules/shared-renderer';
import PrinterCard from './modules/printer-card';
import { createModelChannel, modelStore } from './modules/model-store';
import { toRecord } from './modules/model-record';

const SAVE_DELAY_MS = 400;

const root = document.querySelector('[data-model-detail]');

if (root) {
    boot(root).catch((error) => {
        console.error(error);
        showMissing(root);
    });
}

async function boot(container) {
    const loading = container.querySelector('[data-detail-loading]');
    const cardHost = container.querySelector('[data-detail-card]');
    const unsupported = container.querySelector('[data-viewer-unsupported]');

    if (!webglAvailable()) {
        loading.style.display = 'none';

        if (unsupported) {
            unsupported.style.display = 'block';
        }

        return;
    }

    const id = new URLSearchParams(window.location.search).get('model');
    const record = id ? await modelStore.find(id) : null;

    if (!record?.blob) {
        showMissing(container);

        return;
    }

    const config = readConfig(container);
    const buffer = await record.blob.arrayBuffer();
    const file = new File([record.blob], record.name, {
        type: record.blob.type || 'application/octet-stream',
    });

    const channel = createModelChannel();
    const renderer = new SharedRenderer();

    let saveTimer = null;

    const persist = async (card) => {
        try {
            const updated = {
                ...toRecord(card, {
                    id: record.id,
                    position: record.position,
                    name: record.name,
                    size: record.size,
                }),
                blob: record.blob,
                createdAt: record.createdAt,
            };

            await modelStore.put(updated);
            channel.post({ type: 'updated', id: record.id });
        } catch (error) {
            console.error(error);
        }
    };

    // Card ditampilkan lebih dulu supaya kanvasnya sudah punya ukuran saat
    // viewer dibangun — model langsung terbingkai penuh, tanpa sempat kosong.
    cardHost.style.display = 'block';

    const card = new PrinterCard({
        root: cardHost.querySelector('article'),
        file,
        buffer,
        config,
        renderer,
        state: record.state,
        onChange: (instance) => {
            // Menggeser slider dapat memicu puluhan perubahan beruntun; simpan
            // sekali saja setelah pengguna berhenti mengubah.
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => persist(instance), SAVE_DELAY_MS);
        },
        onRemove: async () => {
            if (!window.confirm(`Hapus ${record.name} dari daftar penawaran?`)) {
                return;
            }

            await modelStore.remove(record.id);
            await modelStore.reorder();
            channel.post({ type: 'removed', id: record.id });

            window.location.href = container.dataset.backUrl ?? '/3d-models';
        },
    });

    card.setPosition(record.position ?? 1);

    loading.style.display = 'none';
    card.frameBuildPlate();

    setTitle(container, record);

    // Simpan sekali di awal: thumbnail dan estimasinya ikut mengikuti versi
    // perhitungan terbaru meski pengguna tidak mengubah apa pun.
    persist(card);
}

function readConfig(container) {
    const el = container.querySelector('[data-printing-config]');

    try {
        return JSON.parse(el?.textContent ?? '{}');
    } catch {
        return { technologies: {}, limits: {} };
    }
}

function setTitle(container, record) {
    const title = document.querySelector('[data-detail-title]');
    const subtitle = document.querySelector('[data-detail-subtitle]');

    if (title) {
        title.textContent = record.name;
    }

    if (subtitle) {
        subtitle.textContent = `Model #${record.position ?? 1} · ${record.format} · seluruh pengaturan di halaman ini tersimpan otomatis.`;
    }

    document.title = `${record.name} · Viewer 3D`;
}

function showMissing(container) {
    const loading = container.querySelector('[data-detail-loading]');
    const missing = container.querySelector('[data-detail-missing]');

    if (loading) {
        loading.style.display = 'none';
    }

    if (missing) {
        missing.style.display = 'block';
    }

    const title = document.querySelector('[data-detail-title]');

    if (title) {
        title.textContent = 'Model tidak ditemukan';
    }
}

function webglAvailable() {
    try {
        const canvas = document.createElement('canvas');

        return Boolean(
            window.WebGLRenderingContext &&
            (canvas.getContext('webgl2') || canvas.getContext('webgl'))
        );
    } catch {
        return false;
    }
}
