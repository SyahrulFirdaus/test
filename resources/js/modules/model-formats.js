/**
 * Format berkas 3D yang diterima Pre-Print Analyzer.
 *
 * Tiga di antaranya dibaca langsung oleh three.js (STL, OBJ, 3MF). STEP/STP
 * adalah format CAD B-rep — bukan mesh — sehingga perlu ditesselasi lebih dulu
 * oleh occt-import-js (OpenCascade versi WebAssembly) menjadi segitiga.
 *
 * Pustaka OCCT berukuran ~7,6 MB, jadi berkasnya tidak ikut di dalam bundel:
 * dimuat dari /vendor/occt hanya pada saat pengguna benar-benar mengunggah
 * berkas STEP, lalu hasil instansiasinya dipakai ulang selama halaman terbuka.
 */

/** Ekstensi yang boleh diunggah, urut seperti yang ditampilkan ke pengguna. */
export const SUPPORTED_EXTENSIONS = ['stl', 'stp', 'step', 'obj', '3mf'];

/** Label "File Types" pada area unggah. */
export const SUPPORTED_LABEL = 'STL, STP, STEP, OBJ, 3MF';

/** Format CAD yang harus ditesselasi lebih dulu sebelum dapat digambar. */
const CAD_EXTENSIONS = ['stp', 'step'];

/** Nama panjang tiap format, dipakai baris "Format" pada card. */
export const FORMAT_NAMES = {
    STL: 'Stereolithography',
    OBJ: 'Wavefront',
    STP: 'STEP CAD',
    STEP: 'STEP CAD',
    '3MF': '3D Manufacturing Format',
};

const OCCT_BASE = '/vendor/occt';

export function extensionOf(name) {
    return String(name ?? '').split('.').pop()?.toLowerCase() ?? '';
}

export function isSupported(name) {
    return SUPPORTED_EXTENSIONS.includes(extensionOf(name));
}

export function needsTessellation(name) {
    return CAD_EXTENSIONS.includes(extensionOf(name));
}

let occtPromise = null;

/** Muat OCCT sekali saja, dan hanya bila memang ada berkas STEP. */
function loadOcct() {
    if (occtPromise) {
        return occtPromise;
    }

    occtPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');

        script.src = `${OCCT_BASE}/occt-import-js.js`;
        script.async = true;
        script.onload = () => {
            if (typeof window.occtimportjs !== 'function') {
                reject(new Error('Pustaka STEP gagal dimuat.'));

                return;
            }

            window
                .occtimportjs({ locateFile: (file) => `${OCCT_BASE}/${file}` })
                .then(resolve)
                .catch(reject);
        };
        script.onerror = () => reject(new Error('Pustaka STEP tidak dapat diunduh.'));

        document.head.appendChild(script);
    }).catch((error) => {
        // Kegagalan tidak di-cache: percobaan unggah berikutnya boleh mencoba lagi.
        occtPromise = null;

        throw error;
    });

    return occtPromise;
}

/**
 * Ubah berkas STEP/STP menjadi STL biner.
 *
 * Dengan mengembalikan STL, seluruh alur setelah ini — pembacaan geometri,
 * analisis kelayakan, estimasi, thumbnail, sampai viewer 3D — berjalan persis
 * seperti berkas mesh biasa tanpa perlu jalur khusus di mana-mana.
 *
 * @param {ArrayBuffer} buffer isi berkas STEP
 * @returns {Promise<ArrayBuffer>} STL biner berisi seluruh solid di dalamnya
 */
export async function tessellateStep(buffer) {
    const occt = await loadOcct();
    const result = occt.ReadStepFile(new Uint8Array(buffer), null);

    if (!result?.success || !result.meshes?.length) {
        throw new Error('Isi berkas STEP tidak dapat dibaca.');
    }

    return meshesToStl(result.meshes);
}

/**
 * Gabungkan seluruh mesh hasil tesselasi menjadi satu STL biner.
 *
 * @param {Array<{attributes: {position: {array: number[]}}, index?: {array: number[]}}>} meshes
 */
function meshesToStl(meshes) {
    const triangles = [];

    for (const mesh of meshes) {
        const position = mesh.attributes?.position?.array;

        if (!position?.length) {
            continue;
        }

        // OCCT boleh mengembalikan mesh terindeks maupun tidak; keduanya
        // diratakan menjadi daftar segitiga agar penulisan STL-nya seragam.
        const index = mesh.index?.array ?? null;
        const count = index ? index.length : position.length / 3;

        for (let i = 0; i < count; i += 3) {
            const corners = [];

            for (let corner = 0; corner < 3; corner++) {
                const vertex = (index ? index[i + corner] : i + corner) * 3;

                corners.push([position[vertex], position[vertex + 1], position[vertex + 2]]);
            }

            triangles.push(corners);
        }
    }

    if (triangles.length === 0) {
        throw new Error('Berkas STEP tidak memuat solid yang dapat dicetak.');
    }

    const buffer = new ArrayBuffer(84 + triangles.length * 50);
    const view = new DataView(buffer);

    // 80 byte header dibiarkan kosong, lalu jumlah segitiga.
    view.setUint32(80, triangles.length, true);

    let offset = 84;

    for (const [a, b, c] of triangles) {
        // Normal dibiarkan nol: three.js menghitungnya sendiri dari urutan
        // verteks, dan analisis kelayakan juga memakai normal hasil hitungan.
        view.setFloat32(offset, 0, true);
        view.setFloat32(offset + 4, 0, true);
        view.setFloat32(offset + 8, 0, true);
        offset += 12;

        for (const [x, y, z] of [a, b, c]) {
            view.setFloat32(offset, x, true);
            view.setFloat32(offset + 4, y, true);
            view.setFloat32(offset + 8, z, true);
            offset += 12;
        }

        view.setUint16(offset, 0, true);
        offset += 2;
    }

    return buffer;
}
