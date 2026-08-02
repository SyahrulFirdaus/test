/**
 * Analisis kelayakan cetak sebuah mesh.
 *
 * Seluruh pemeriksaan dilakukan langsung pada buffer geometri Three.js:
 *
 * - Vertex disatukan (welding) berdasarkan posisi yang dibulatkan, karena
 *   file STL menyimpan tiap segitiga secara terpisah sehingga tanpa welding
 *   setiap tepi akan selalu terlihat sebagai lubang.
 * - Dari daftar tepi hasil welding didapat: tepi batas (lubang), tepi
 *   non-manifold (dipakai lebih dari dua muka), dan arah penelusuran yang
 *   tidak konsisten (indikasi normal terbalik).
 * - Volume dihitung sebagai jumlah bertanda volume tetrahedron tiap muka
 *   terhadap titik asal; nilainya hanya sahih bila mesh tertutup.
 */

const STATUS_PASS = 'pass';
const STATUS_WARN = 'warn';
const STATUS_FAIL = 'fail';
const STATUS_SKIP = 'skip';

export const OVERALL_READY = 'ready';
export const OVERALL_WARNING = 'warning';
export const OVERALL_NOT_PRINTABLE = 'not_printable';

/**
 * Kumpulkan seluruh mesh pada sebuah object3D.
 */
function collectMeshes(object) {
    const meshes = [];

    object.traverse((child) => {
        if (child.isMesh && child.geometry?.getAttribute('position')?.count) {
            meshes.push(child);
        }
    });

    return meshes;
}

/**
 * Hitung metrik geometri: volume, luas permukaan, dan topologi tepi.
 */
/**
 * @param {object} object object3D yang dianalisis
 * @param {{maxTrianglesForTopology?: number, applyWorldMatrix?: boolean}} options
 *   applyWorldMatrix sengaja default false: metrik volume dan topologi harus
 *   bebas dari orientasi yang dipilih pengguna, sebab memutar atau membalik
 *   model tidak mengubah geometrinya.
 */
export function analyzeGeometry(object, { maxTrianglesForTopology = 400000, applyWorldMatrix = false } = {}) {
    const meshes = collectMeshes(object);

    let triangles = 0;
    let vertices = 0;

    meshes.forEach((mesh) => {
        const position = mesh.geometry.getAttribute('position');
        vertices += position.count;
        triangles += mesh.geometry.index ? mesh.geometry.index.count / 3 : position.count / 3;
    });

    triangles = Math.round(triangles);

    const metrics = {
        triangles,
        vertices,
        signedVolumeMm3: 0,
        volumeMm3: 0,
        surfaceAreaMm2: 0,
        degenerateFaces: 0,
        weldedVertices: 0,
        boundaryEdges: 0,
        nonManifoldEdges: 0,
        inconsistentEdges: 0,
        holeCount: 0,
        topologyAnalyzed: triangles > 0 && triangles <= maxTrianglesForTopology,
        isWatertight: false,
    };

    if (triangles === 0) {
        return metrics;
    }

    // --- volume, luas permukaan, dan muka degenerate (selalu dihitung) ---
    const ax = [0, 0, 0];
    const bx = [0, 0, 0];
    const cx = [0, 0, 0];

    const forEachTriangle = (callback) => {
        meshes.forEach((mesh) => {
            const geometry = mesh.geometry;
            const position = geometry.getAttribute('position');
            const index = geometry.index;
            const count = index ? index.count : position.count;
            const matrix = mesh.matrixWorld;
            const useMatrix = applyWorldMatrix;

            for (let i = 0; i < count; i += 3) {
                const i0 = index ? index.getX(i) : i;
                const i1 = index ? index.getX(i + 1) : i + 1;
                const i2 = index ? index.getX(i + 2) : i + 2;

                readVertex(position, i0, ax, matrix, useMatrix);
                readVertex(position, i1, bx, matrix, useMatrix);
                readVertex(position, i2, cx, matrix, useMatrix);

                callback(ax, bx, cx, i0, i1, i2, mesh);
            }
        });
    };

    forEachTriangle((a, b, c) => {
        // Volume tetrahedron bertanda: (a . (b x c)) / 6
        metrics.signedVolumeMm3 +=
            (a[0] * (b[1] * c[2] - b[2] * c[1]) -
                a[1] * (b[0] * c[2] - b[2] * c[0]) +
                a[2] * (b[0] * c[1] - b[1] * c[0])) /
            6;

        const ux = b[0] - a[0];
        const uy = b[1] - a[1];
        const uz = b[2] - a[2];
        const vx = c[0] - a[0];
        const vy = c[1] - a[1];
        const vz = c[2] - a[2];

        const nx = uy * vz - uz * vy;
        const ny = uz * vx - ux * vz;
        const nz = ux * vy - uy * vx;

        const area = Math.sqrt(nx * nx + ny * ny + nz * nz) / 2;
        metrics.surfaceAreaMm2 += area;

        if (area < 1e-10) {
            metrics.degenerateFaces += 1;
        }
    });

    metrics.volumeMm3 = Math.abs(metrics.signedVolumeMm3);

    if (!metrics.topologyAnalyzed) {
        return metrics;
    }

    // --- welding + pemeriksaan topologi tepi ---
    const weld = new Map();
    const precision = 1e4; // pembulatan ke 0,0001 satuan file
    const key = (v) =>
        `${Math.round(v[0] * precision)},${Math.round(v[1] * precision)},${Math.round(v[2] * precision)}`;

    const weldIndex = (v) => {
        const k = key(v);
        let id = weld.get(k);

        if (id === undefined) {
            id = weld.size;
            weld.set(k, id);
        }

        return id;
    };

    const triangleIndices = new Int32Array(triangles * 3);
    let cursor = 0;

    forEachTriangle((a, b, c) => {
        triangleIndices[cursor++] = weldIndex(a);
        triangleIndices[cursor++] = weldIndex(b);
        triangleIndices[cursor++] = weldIndex(c);
    });

    metrics.weldedVertices = weld.size;

    const stride = weld.size + 1;
    // edgeKey unik per pasangan (min,max); nilai menyimpan jumlah pemakaian
    // dan selisih arah untuk mendeteksi winding yang tidak konsisten.
    const edgeUse = new Map();

    const addEdge = (from, to) => {
        if (from === to) {
            return; // tepi degenerate, diabaikan
        }

        const min = Math.min(from, to);
        const max = Math.max(from, to);
        const edgeKey = min * stride + max;
        const forward = from === min ? 1 : -1;

        const existing = edgeUse.get(edgeKey);

        if (existing === undefined) {
            edgeUse.set(edgeKey, { count: 1, direction: forward, a: min, b: max });
        } else {
            existing.count += 1;
            existing.direction += forward;
        }
    };

    for (let i = 0; i < triangleIndices.length; i += 3) {
        const a = triangleIndices[i];
        const b = triangleIndices[i + 1];
        const c = triangleIndices[i + 2];

        addEdge(a, b);
        addEdge(b, c);
        addEdge(c, a);
    }

    const boundary = [];

    edgeUse.forEach((edge) => {
        if (edge.count === 1) {
            metrics.boundaryEdges += 1;
            boundary.push(edge);
        } else if (edge.count > 2) {
            metrics.nonManifoldEdges += 1;
        } else if (edge.direction !== 0) {
            // Dipakai dua kali tetapi arahnya sama → orientasi muka berlawanan.
            metrics.inconsistentEdges += 1;
        }
    });

    metrics.holeCount = countHoleLoops(boundary);
    metrics.isWatertight = metrics.boundaryEdges === 0 && metrics.nonManifoldEdges === 0;

    return metrics;
}

function readVertex(position, index, target, matrix, useMatrix) {
    target[0] = position.getX(index);
    target[1] = position.getY(index);
    target[2] = position.getZ(index);

    if (useMatrix) {
        const [x, y, z] = target;
        const e = matrix.elements;
        const w = e[3] * x + e[7] * y + e[11] * z + e[15] || 1;

        target[0] = (e[0] * x + e[4] * y + e[8] * z + e[12]) / w;
        target[1] = (e[1] * x + e[5] * y + e[9] * z + e[13]) / w;
        target[2] = (e[2] * x + e[6] * y + e[10] * z + e[14]) / w;
    }
}

/**
 * Hitung berapa lubang terpisah yang terbentuk dari kumpulan tepi batas.
 */
function countHoleLoops(boundaryEdges) {
    if (boundaryEdges.length === 0) {
        return 0;
    }

    const adjacency = new Map();

    const link = (from, to) => {
        if (!adjacency.has(from)) {
            adjacency.set(from, []);
        }

        adjacency.get(from).push(to);
    };

    boundaryEdges.forEach((edge) => {
        link(edge.a, edge.b);
        link(edge.b, edge.a);
    });

    const visited = new Set();
    let loops = 0;

    adjacency.forEach((_, start) => {
        if (visited.has(start)) {
            return;
        }

        loops += 1;

        const stack = [start];
        visited.add(start);

        while (stack.length) {
            const current = stack.pop();

            (adjacency.get(current) ?? []).forEach((next) => {
                if (!visited.has(next)) {
                    visited.add(next);
                    stack.push(next);
                }
            });
        }
    });

    return loops;
}

/**
 * Susun daftar pemeriksaan beserta status keseluruhan.
 *
 * @param {object} metrics hasil analyzeGeometry()
 * @param {{x:number,y:number,z:number}} dimensions ukuran bounding box model
 * @param {object|null} technology konfigurasi teknologi terpilih
 * @param {{minDimension:number, warnDimension:number}} limits
 */
export function buildChecks(metrics, dimensions, technology, limits) {
    const checks = [];
    const number = new Intl.NumberFormat('id-ID');

    // 1. Mesh tertutup ------------------------------------------------------
    if (!metrics.topologyAnalyzed) {
        checks.push({
            id: 'watertight',
            label: 'Mesh tertutup (watertight)',
            status: STATUS_SKIP,
            message: `Model memiliki ${number.format(metrics.triangles)} segitiga sehingga analisis topologi dilewati agar browser tetap responsif. Tim kami akan memeriksanya secara manual.`,
        });
    } else if (metrics.isWatertight) {
        checks.push({
            id: 'watertight',
            label: 'Mesh tertutup (watertight)',
            status: STATUS_PASS,
            message: 'Seluruh tepi tersambung rapat. Model membentuk volume padat yang siap di-slice.',
        });
    } else {
        checks.push({
            id: 'watertight',
            label: 'Mesh tertutup (watertight)',
            status: STATUS_FAIL,
            message: 'Mesh belum tertutup sempurna sehingga slicer tidak dapat menentukan bagian dalam dan luar part.',
        });
    }

    // 2. Lubang pada mesh ---------------------------------------------------
    if (!metrics.topologyAnalyzed) {
        checks.push({
            id: 'holes',
            label: 'Lubang pada permukaan',
            status: STATUS_SKIP,
            message: 'Tidak diperiksa karena jumlah segitiga melebihi batas analisis otomatis.',
        });
    } else if (metrics.boundaryEdges === 0) {
        checks.push({
            id: 'holes',
            label: 'Lubang pada permukaan',
            status: STATUS_PASS,
            message: 'Tidak ditemukan tepi terbuka pada permukaan model.',
        });
    } else {
        checks.push({
            id: 'holes',
            label: 'Lubang pada permukaan',
            status: STATUS_FAIL,
            message: `Ditemukan ${number.format(metrics.holeCount)} lubang (${number.format(metrics.boundaryEdges)} tepi terbuka). Tutup lubang tersebut di software CAD atau perbaiki dengan mesh repair sebelum dicetak.`,
        });
    }

    // 3. Non-manifold edge --------------------------------------------------
    if (!metrics.topologyAnalyzed) {
        checks.push({
            id: 'non-manifold',
            label: 'Non-manifold edge',
            status: STATUS_SKIP,
            message: 'Tidak diperiksa karena jumlah segitiga melebihi batas analisis otomatis.',
        });
    } else if (metrics.nonManifoldEdges === 0) {
        checks.push({
            id: 'non-manifold',
            label: 'Non-manifold edge',
            status: STATUS_PASS,
            message: 'Setiap tepi dipakai tepat oleh dua muka — geometri bersih.',
        });
    } else {
        checks.push({
            id: 'non-manifold',
            label: 'Non-manifold edge',
            status: STATUS_FAIL,
            message: `Ditemukan ${number.format(metrics.nonManifoldEdges)} tepi yang dipakai lebih dari dua muka. Biasanya muncul akibat permukaan bertumpuk atau body yang belum di-merge.`,
        });
    }

    // 4. Normal muka terbalik ----------------------------------------------
    if (!metrics.topologyAnalyzed) {
        checks.push({
            id: 'normals',
            label: 'Arah normal muka',
            status: STATUS_SKIP,
            message: 'Tidak diperiksa karena jumlah segitiga melebihi batas analisis otomatis.',
        });
    } else if (metrics.inconsistentEdges === 0 && metrics.signedVolumeMm3 >= 0) {
        checks.push({
            id: 'normals',
            label: 'Arah normal muka',
            status: STATUS_PASS,
            message: 'Orientasi seluruh muka konsisten menghadap ke luar.',
        });
    } else if (metrics.inconsistentEdges > 0) {
        checks.push({
            id: 'normals',
            label: 'Arah normal muka',
            status: STATUS_WARN,
            message: `Ditemukan ${number.format(metrics.inconsistentEdges)} tepi dengan orientasi muka berlawanan. Sebagian normal kemungkinan terbalik — umumnya masih dapat diperbaiki otomatis oleh slicer, namun sebaiknya dibetulkan lebih dulu.`,
        });
    } else {
        checks.push({
            id: 'normals',
            label: 'Arah normal muka',
            status: STATUS_WARN,
            message: 'Seluruh normal tampak menghadap ke dalam. Model kemungkinan ter-invert; balik arah normalnya sebelum dicetak.',
        });
    }

    // 5. Ukuran model -------------------------------------------------------
    const minDimension = Math.min(dimensions.x, dimensions.y, dimensions.z);
    const maxDimension = Math.max(dimensions.x, dimensions.y, dimensions.z);

    if (minDimension < limits.minDimension) {
        checks.push({
            id: 'size',
            label: 'Ukuran model',
            status: STATUS_FAIL,
            message: `Sisi terkecil hanya ${minDimension.toFixed(2)} mm, di bawah batas minimum ${limits.minDimension} mm. Perbesar model atau pastikan satuan file sudah benar.`,
        });
    } else if (minDimension < limits.warnDimension) {
        checks.push({
            id: 'size',
            label: 'Ukuran model',
            status: STATUS_WARN,
            message: `Sisi terkecil ${minDimension.toFixed(2)} mm tergolong sangat tipis. Fitur setipis ini rawan patah pada FDM — pertimbangkan SLA untuk hasil lebih baik.`,
        });
    } else {
        checks.push({
            id: 'size',
            label: 'Ukuran model',
            status: STATUS_PASS,
            message: `Dimensi terbesar ${maxDimension.toFixed(1)} mm dan terkecil ${minDimension.toFixed(1)} mm berada pada rentang yang wajar.`,
        });
    }

    // 6. Muat pada area cetak ----------------------------------------------
    // Diperiksa mengikuti orientasi yang sedang dipilih pengguna: tinggi model
    // (sumbu Y viewer) dibandingkan tinggi mesin, sedangkan tapak model
    // dibandingkan bidang meja — boleh ditukar karena memutar model pada sumbu
    // tegak tidak mengubah apa pun bagi proses cetak.
    if (technology) {
        const build = technology.buildVolume;
        const height = dimensions.y;
        const footprint = [dimensions.x, dimensions.z];
        const bed = [build.x, build.y];

        const heightFits = height <= build.z;
        const footprintFits =
            (footprint[0] <= bed[0] && footprint[1] <= bed[1]) ||
            (footprint[0] <= bed[1] && footprint[1] <= bed[0]);
        const fitsAsOriented = heightFits && footprintFits;

        // Apakah masih ada orientasi lain yang memungkinkan?
        const sorted = [dimensions.x, dimensions.y, dimensions.z].sort((a, b) => b - a);
        const capacity = [build.x, build.y, build.z].sort((a, b) => b - a);
        const fitsSomehow = sorted.every((value, i) => value <= capacity[i]);

        const usage = Math.max(
            height / build.z,
            Math.min(
                Math.max(footprint[0] / bed[0], footprint[1] / bed[1]),
                Math.max(footprint[0] / bed[1], footprint[1] / bed[0])
            )
        ) * 100;

        const sizeText = `${dimensions.x.toFixed(0)} × ${dimensions.z.toFixed(0)} mm tapak, tinggi ${height.toFixed(0)} mm`;
        const buildText = `${build.x} × ${build.y} × ${build.z} mm`;

        if (!fitsAsOriented && fitsSomehow) {
            checks.push({
                id: 'build-volume',
                label: `Area cetak ${technology.code}`,
                status: STATUS_FAIL,
                message: `Pada orientasi sekarang (${sizeText}) model tidak muat di area cetak ${buildText}, ${!heightFits ? 'karena terlalu tinggi' : 'karena tapaknya terlalu lebar'}. Model ini masih muat bila diputar — coba ubah orientasinya pada panel Orientasi Model.`,
            });
        } else if (!fitsAsOriented) {
            checks.push({
                id: 'build-volume',
                label: `Area cetak ${technology.code}`,
                status: STATUS_FAIL,
                message: `Model ${sizeText} melebihi area cetak ${buildText} pada orientasi apa pun. Pilih teknologi lain, perkecil skala, atau pecah model menjadi beberapa bagian.`,
            });
        } else if (usage > 90) {
            checks.push({
                id: 'build-volume',
                label: `Area cetak ${technology.code}`,
                status: STATUS_WARN,
                message: `Model memakai ${usage.toFixed(0)}% area cetak ${buildText} pada orientasi sekarang. Masih muat, tetapi ruang untuk support sangat terbatas.`,
            });
        } else {
            checks.push({
                id: 'build-volume',
                label: `Area cetak ${technology.code}`,
                status: STATUS_PASS,
                message: `Pada orientasi sekarang model muat di area cetak ${buildText} (memakai ${usage.toFixed(0)}%).`,
            });
        }
    }

    // 7. Muka degenerate (informasi tambahan) -------------------------------
    if (metrics.degenerateFaces > 0) {
        checks.push({
            id: 'degenerate',
            label: 'Muka degenerate',
            status: STATUS_WARN,
            message: `${number.format(metrics.degenerateFaces)} segitiga memiliki luas nol. Biasanya sisa proses ekspor dan dapat dibersihkan otomatis oleh slicer.`,
        });
    }

    const hasFail = checks.some((check) => check.status === STATUS_FAIL);
    const hasWarn = checks.some((check) => check.status === STATUS_WARN || check.status === STATUS_SKIP);

    return {
        checks,
        status: hasFail ? OVERALL_NOT_PRINTABLE : hasWarn ? OVERALL_WARNING : OVERALL_READY,
    };
}

export const CHECK_STATUS = {
    PASS: STATUS_PASS,
    WARN: STATUS_WARN,
    FAIL: STATUS_FAIL,
    SKIP: STATUS_SKIP,
};
