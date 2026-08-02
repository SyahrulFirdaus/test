import * as THREE from 'three';

/**
 * Pewarnaan model untuk mode analisis di viewer.
 *
 * Dua mode tersedia, keduanya menyerupai tampilan Ultimaker Cura:
 *
 *  - **Overhang** — sudut tiap muka diukur dari bidang tegak. Dinding tegak
 *    bernilai 0 derajat, langit-langit mendatar 90 derajat. Muka yang landai
 *    diberi hijau, yang mulai menggantung kuning, dan yang wajib ditopang
 *    merah.
 *  - **Wall Thickness** — dari setiap muka ditembakkan sinar ke dalam model;
 *    jarak sampai permukaan seberang adalah tebal dindingnya. Dinding yang
 *    lebih tipis dari batas teknologi diberi merah.
 *
 * Keduanya murni visual: warna ditulis sebagai atribut `color` pada geometri
 * sehingga dapat dilepas kembali tanpa menyentuh posisi maupun bentuk mesh.
 * Pengukuran dilakukan di ruang dunia, jadi skala model ikut diperhitungkan.
 */

export const VIEW_MODES = {
    MATERIAL: 'material',
    OVERHANG: 'overhang',
    THICKNESS: 'thickness',
};

/** Kumpulkan seluruh mesh beserta matriks dunianya. */
function collectMeshes(object) {
    const meshes = [];

    object.updateMatrixWorld(true);
    object.traverse((child) => {
        if (child.isMesh && child.geometry?.getAttribute('position')?.count) {
            meshes.push(child);
        }
    });

    return meshes;
}

/**
 * Siapkan atribut warna per vertex, dibuat sekali lalu dipakai ulang.
 * Nilai awalnya putih supaya muka yang belum sempat diwarnai tidak menghitam.
 */
function ensureColorAttribute(geometry) {
    const count = geometry.getAttribute('position').count;
    let attribute = geometry.getAttribute('color');

    if (!attribute || attribute.count !== count) {
        attribute = new THREE.BufferAttribute(new Float32Array(count * 3).fill(1), 3);
        geometry.setAttribute('color', attribute);
    }

    return attribute;
}

/**
 * Baca segitiga demi segitiga dalam ruang dunia.
 *
 * Normal dihitung dari perkalian silang sisi segitiga. Model yang dicerminkan
 * memiliki determinan matriks negatif sehingga urutan sudutnya terbalik —
 * normalnya dibalik agar tetap menghadap ke luar.
 */
function forEachWorldTriangle(mesh, callback) {
    const geometry = mesh.geometry;
    const position = geometry.getAttribute('position');
    const index = geometry.index;
    const count = index ? index.count : position.count;
    const matrix = mesh.matrixWorld;
    const flip = matrix.determinant() < 0;

    const a = new THREE.Vector3();
    const b = new THREE.Vector3();
    const c = new THREE.Vector3();
    const ab = new THREE.Vector3();
    const ac = new THREE.Vector3();
    const normal = new THREE.Vector3();

    for (let i = 0; i < count; i += 3) {
        const i0 = index ? index.getX(i) : i;
        const i1 = index ? index.getX(i + 1) : i + 1;
        const i2 = index ? index.getX(i + 2) : i + 2;

        a.fromBufferAttribute(position, i0).applyMatrix4(matrix);
        b.fromBufferAttribute(position, i1).applyMatrix4(matrix);
        c.fromBufferAttribute(position, i2).applyMatrix4(matrix);

        ab.subVectors(b, a);
        ac.subVectors(c, a);
        normal.crossVectors(ab, ac);

        const length = normal.length();

        if (length < 1e-12) {
            continue; // segitiga degenerate, tidak punya arah hadap
        }

        normal.divideScalar(length);

        if (flip) {
            normal.negate();
        }

        callback(a, b, c, normal, [i0, i1, i2]);
    }
}

/**
 * Tulis satu warna ke tiga vertex sebuah segitiga.
 *
 * Pada geometri terindeks satu vertex dapat dipakai beberapa muka; yang
 * ditulis adalah warna paling parah agar area bermasalah tidak tersamarkan
 * oleh muka tetangganya yang aman.
 */
function writeTriangleColor(attribute, indices, color, severity, severities) {
    indices.forEach((vertexIndex) => {
        if (severities[vertexIndex] !== undefined && severities[vertexIndex] >= severity) {
            return;
        }

        severities[vertexIndex] = severity;
        attribute.setXYZ(vertexIndex, color.r, color.g, color.b);
    });
}

/**
 * Warnai model menurut sudut overhang tiap muka.
 *
 * @returns {{safe:number, warn:number, critical:number, faces:number}}
 */
export function paintOverhang(object, options = {}) {
    const safeDeg = Number(options.safeDeg ?? 45);
    const warnDeg = Number(options.warnDeg ?? 60);

    const palette = {
        safe: new THREE.Color(options.colors?.safe ?? '#3FA45B'),
        warn: new THREE.Color(options.colors?.warn ?? '#E0A82E'),
        critical: new THREE.Color(options.colors?.critical ?? '#C0392B'),
    };

    // Ambang dibandingkan langsung pada komponen tegak normal, jadi tidak perlu
    // memanggil asin() untuk setiap muka.
    const safeThreshold = Math.sin((safeDeg * Math.PI) / 180);
    const warnThreshold = Math.sin((warnDeg * Math.PI) / 180);

    const tally = { safe: 0, warn: 0, critical: 0, faces: 0 };

    collectMeshes(object).forEach((mesh) => {
        const attribute = ensureColorAttribute(mesh.geometry);
        const severities = {};

        forEachWorldTriangle(mesh, (a, b, c, normal, indices) => {
            const downward = Math.max(0, -normal.y);

            let bucket = 'safe';
            let severity = 0;

            if (downward >= warnThreshold) {
                bucket = 'critical';
                severity = 2;
            } else if (downward >= safeThreshold) {
                bucket = 'warn';
                severity = 1;
            }

            tally[bucket] += 1;
            tally.faces += 1;

            writeTriangleColor(attribute, indices, palette[bucket], severity, severities);
        });

        attribute.needsUpdate = true;
        applyVertexColors(mesh, true);
    });

    return tally;
}

/**
 * Warnai model menurut tebal dindingnya.
 *
 * @returns {{minMm:number, thinFaces:number, faces:number, sampled:boolean, measured:number}}
 */
export function paintThickness(object, options = {}) {
    const minMm = Math.max(0.01, Number(options.minMm ?? 1));
    const maxSamples = Math.max(200, Number(options.maxSamples ?? 20000));

    const palette = {
        safe: new THREE.Color(options.colors?.safe ?? '#3FA45B'),
        thin: new THREE.Color(options.colors?.thin ?? '#C0392B'),
    };

    const meshes = collectMeshes(object);
    const grid = TriangleGrid.fromMeshes(meshes);

    const summary = { minMm: Infinity, thinFaces: 0, faces: 0, sampled: false, measured: 0 };

    if (!grid) {
        return { ...summary, minMm: 0 };
    }

    // Model yang sangat rapat tidak diukur satu per satu; muka di antara titik
    // sampel memakai hasil pengukuran terakhir.
    const stride = Math.max(1, Math.ceil(grid.triangleCount / maxSamples));
    summary.sampled = stride > 1;

    const origin = new THREE.Vector3();
    const direction = new THREE.Vector3();

    meshes.forEach((mesh) => {
        const attribute = ensureColorAttribute(mesh.geometry);
        const severities = {};

        let face = 0;
        let lastThickness = grid.diagonal;

        forEachWorldTriangle(mesh, (a, b, c, normal, indices) => {
            if (face % stride === 0) {
                origin.copy(a).add(b).add(c).divideScalar(3);
                direction.copy(normal).negate();

                // Titik asal digeser sedikit ke dalam agar sinarnya tidak
                // langsung mengenai muka tempat ia berangkat.
                origin.addScaledVector(direction, grid.epsilon);

                const hit = grid.raycast(origin, direction, grid.diagonal);
                lastThickness = hit === null ? grid.diagonal : hit + grid.epsilon;
                summary.measured += 1;
            }

            face += 1;
            summary.faces += 1;
            summary.minMm = Math.min(summary.minMm, lastThickness);

            const thin = lastThickness < minMm;

            if (thin) {
                summary.thinFaces += 1;
            }

            writeTriangleColor(attribute, indices, thin ? palette.thin : palette.safe, thin ? 1 : 0, severities);
        });

        attribute.needsUpdate = true;
        applyVertexColors(mesh, true);
    });

    if (!Number.isFinite(summary.minMm)) {
        summary.minMm = 0;
    }

    return summary;
}

/** Kembalikan model ke pewarnaan material biasa. */
export function clearPaint(object) {
    collectMeshes(object).forEach((mesh) => {
        mesh.geometry.deleteAttribute('color');
        applyVertexColors(mesh, false);
    });
}

function applyVertexColors(mesh, enabled) {
    const materials = Array.isArray(mesh.material) ? mesh.material : [mesh.material];

    materials.forEach((material) => {
        if (!material) {
            return;
        }

        material.vertexColors = enabled;
        material.needsUpdate = true;
    });
}

/**
 * Kisi seragam untuk mempercepat penembakan sinar.
 *
 * Tanpa akselerasi, satu sinar harus diuji terhadap seluruh segitiga model —
 * ribuan sinar pada model puluhan ribu muka akan membekukan browser. Di sini
 * segitiga dimasukkan lebih dulu ke dalam sel-sel kisi, lalu sinar hanya
 * diuji terhadap segitiga di sel yang benar-benar dilewatinya.
 *
 * Sinar diperpanjang bertahap: potongan pendek diuji lebih dulu, dan begitu
 * ada perpotongan di dalam potongan itu, itulah perpotongan terdekat. Karena
 * dinding umumnya tipis, sebagian besar sinar selesai pada percobaan pertama.
 */
class TriangleGrid {
    static fromMeshes(meshes) {
        const positions = [];
        const box = new THREE.Box3();
        const point = new THREE.Vector3();

        meshes.forEach((mesh) => {
            forEachWorldTriangle(mesh, (a, b, c) => {
                positions.push(a.x, a.y, a.z, b.x, b.y, b.z, c.x, c.y, c.z);
                box.expandByPoint(point.copy(a));
                box.expandByPoint(point.copy(b));
                box.expandByPoint(point.copy(c));
            });
        });

        return positions.length > 0 ? new TriangleGrid(new Float32Array(positions), box) : null;
    }

    constructor(vertices, box) {
        this.vertices = vertices;
        this.triangleCount = vertices.length / 9;

        const size = box.getSize(new THREE.Vector3());
        this.min = box.min.clone();
        this.diagonal = Math.max(size.length(), 1e-3);
        this.epsilon = Math.max(this.diagonal * 1e-5, 1e-4);

        // Sekitar dua sel per akar pangkat tiga jumlah segitiga menjaga isi tiap
        // sel tetap sedikit tanpa membuat kisinya terlalu besar.
        const resolution = Math.min(64, Math.max(6, Math.round(Math.cbrt(this.triangleCount) * 2)));

        this.cellSize = {
            x: Math.max(size.x / resolution, this.diagonal / 512),
            y: Math.max(size.y / resolution, this.diagonal / 512),
            z: Math.max(size.z / resolution, this.diagonal / 512),
        };

        this.resolution = resolution;
        this.cells = new Map();

        this.build();
    }

    cellIndex(value, axis) {
        const index = Math.floor((value - this.min[axis]) / this.cellSize[axis]);

        return Math.min(this.resolution, Math.max(0, index));
    }

    key(ix, iy, iz) {
        const span = this.resolution + 1;

        return (ix * span + iy) * span + iz;
    }

    build() {
        for (let t = 0; t < this.triangleCount; t += 1) {
            const offset = t * 9;

            let minX = Infinity;
            let minY = Infinity;
            let minZ = Infinity;
            let maxX = -Infinity;
            let maxY = -Infinity;
            let maxZ = -Infinity;

            for (let v = 0; v < 3; v += 1) {
                const x = this.vertices[offset + v * 3];
                const y = this.vertices[offset + v * 3 + 1];
                const z = this.vertices[offset + v * 3 + 2];

                minX = Math.min(minX, x);
                maxX = Math.max(maxX, x);
                minY = Math.min(minY, y);
                maxY = Math.max(maxY, y);
                minZ = Math.min(minZ, z);
                maxZ = Math.max(maxZ, z);
            }

            const x0 = this.cellIndex(minX, 'x');
            const x1 = this.cellIndex(maxX, 'x');
            const y0 = this.cellIndex(minY, 'y');
            const y1 = this.cellIndex(maxY, 'y');
            const z0 = this.cellIndex(minZ, 'z');
            const z1 = this.cellIndex(maxZ, 'z');

            for (let ix = x0; ix <= x1; ix += 1) {
                for (let iy = y0; iy <= y1; iy += 1) {
                    for (let iz = z0; iz <= z1; iz += 1) {
                        const key = this.key(ix, iy, iz);
                        const bucket = this.cells.get(key);

                        if (bucket === undefined) {
                            this.cells.set(key, [t]);
                        } else {
                            bucket.push(t);
                        }
                    }
                }
            }
        }
    }

    /**
     * Jarak ke perpotongan terdekat, atau null bila sinar tidak mengenai apa pun.
     *
     * @param {THREE.Vector3} origin
     * @param {THREE.Vector3} direction sudah dinormalisasi
     * @param {number} maxDistance
     */
    raycast(origin, direction, maxDistance) {
        const step = Math.max(this.cellSize.x, this.cellSize.y, this.cellSize.z) * 2;
        let reach = Math.min(step, maxDistance);

        while (reach <= maxDistance) {
            const hit = this.raycastSegment(origin, direction, reach);

            if (hit !== null) {
                return hit;
            }

            if (reach >= maxDistance) {
                return null;
            }

            reach = Math.min(reach * 2, maxDistance);
        }

        return null;
    }

    /** Uji seluruh segitiga pada sel yang bersinggungan dengan satu potongan sinar. */
    raycastSegment(origin, direction, length) {
        const endX = origin.x + direction.x * length;
        const endY = origin.y + direction.y * length;
        const endZ = origin.z + direction.z * length;

        const x0 = this.cellIndex(Math.min(origin.x, endX), 'x');
        const x1 = this.cellIndex(Math.max(origin.x, endX), 'x');
        const y0 = this.cellIndex(Math.min(origin.y, endY), 'y');
        const y1 = this.cellIndex(Math.max(origin.y, endY), 'y');
        const z0 = this.cellIndex(Math.min(origin.z, endZ), 'z');
        const z1 = this.cellIndex(Math.max(origin.z, endZ), 'z');

        let nearest = null;
        const seen = new Set();

        for (let ix = x0; ix <= x1; ix += 1) {
            for (let iy = y0; iy <= y1; iy += 1) {
                for (let iz = z0; iz <= z1; iz += 1) {
                    const bucket = this.cells.get(this.key(ix, iy, iz));

                    if (bucket === undefined) {
                        continue;
                    }

                    for (let i = 0; i < bucket.length; i += 1) {
                        const triangle = bucket[i];

                        if (seen.has(triangle)) {
                            continue;
                        }

                        seen.add(triangle);

                        const distance = this.intersectTriangle(triangle, origin, direction);

                        if (distance !== null && distance <= length && (nearest === null || distance < nearest)) {
                            nearest = distance;
                        }
                    }
                }
            }
        }

        return nearest;
    }

    /** Möller–Trumbore, menerima perpotongan dari kedua sisi muka. */
    intersectTriangle(triangle, origin, direction) {
        const o = triangle * 9;
        const v = this.vertices;

        const e1x = v[o + 3] - v[o];
        const e1y = v[o + 4] - v[o + 1];
        const e1z = v[o + 5] - v[o + 2];

        const e2x = v[o + 6] - v[o];
        const e2y = v[o + 7] - v[o + 1];
        const e2z = v[o + 8] - v[o + 2];

        const px = direction.y * e2z - direction.z * e2y;
        const py = direction.z * e2x - direction.x * e2z;
        const pz = direction.x * e2y - direction.y * e2x;

        const det = e1x * px + e1y * py + e1z * pz;

        if (Math.abs(det) < 1e-12) {
            return null;
        }

        const inv = 1 / det;
        const tx = origin.x - v[o];
        const ty = origin.y - v[o + 1];
        const tz = origin.z - v[o + 2];

        const u = (tx * px + ty * py + tz * pz) * inv;

        if (u < -1e-6 || u > 1 + 1e-6) {
            return null;
        }

        const qx = ty * e1z - tz * e1y;
        const qy = tz * e1x - tx * e1z;
        const qz = tx * e1y - ty * e1x;

        const w = (direction.x * qx + direction.y * qy + direction.z * qz) * inv;

        if (w < -1e-6 || u + w > 1 + 1e-6) {
            return null;
        }

        const distance = (e2x * qx + e2y * qy + e2z * qz) * inv;

        return distance > 1e-9 ? distance : null;
    }
}
