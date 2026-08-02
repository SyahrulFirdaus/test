import * as THREE from 'three';

/**
 * Pembentuk visualisasi support structure.
 *
 * Pendekatannya menyerupai konsep Ultimaker Cura, disederhanakan agar dapat
 * berjalan seketika di browser:
 *
 *  1. Setiap muka mesh diperiksa arah normalnya pada orientasi yang sedang
 *     dipilih. Muka yang menggantung lebih dari `overhangAngleDeg` dari bidang
 *     tegak ditandai sebagai area yang butuh penyangga.
 *  2. Titik-titik sampel muka tersebut dikelompokkan ke dalam kisi pada bidang
 *     meja cetak. Tiap sel menyimpan titik overhang paling rendah — titik itulah
 *     yang perlu ditopang lebih dulu.
 *  3. Dari setiap titik tersebut ditembakkan sinar ke bawah. Bila mengenai
 *     permukaan model, pilar tumbuh dari permukaan itu; bila tidak, pilar tumbuh
 *     dari meja cetak. Perilaku ini sama seperti support Cura yang dapat berdiri
 *     di atas model.
 *  4. Seluruh pilar digambar sebagai satu InstancedMesh, ditambah pelat dasar
 *     tipis, lalu dikembalikan dalam satu Group tersendiri agar terpisah penuh
 *     dari mesh model utama.
 *
 * Volume support yang dihasilkan ikut dihitung dari geometri yang benar-benar
 * dibentuk, sehingga angka estimasi berat sesuai dengan apa yang terlihat.
 */

const DEFAULTS = {
    overhangAngleDeg: 45,
    gridSizeMm: 4,
    maxCellsPerAxis: 44,
    pillarShrink: 0.62,
    minPillarHeightMm: 0.8,
    plateToleranceMm: 0.4,
    baseHeightMm: 0.8,
    baseExpand: 1.3,
    color: '#5AD4DE',
    opacity: 0.52,
    infill: 0.3,
};

/**
 * Gabungkan opsi dengan nilai default.
 *
 * Sengaja tidak memakai spread biasa: nilai `undefined`, `null`, atau angka
 * tidak hingga akan menimpa default dan merambat menjadi NaN di seluruh
 * perhitungan tanpa gejala yang jelas.
 */
function withDefaults(options = {}) {
    const cfg = { ...DEFAULTS };

    Object.entries(options).forEach(([key, value]) => {
        if (value === undefined || value === null) {
            return;
        }

        if (typeof DEFAULTS[key] === 'number') {
            const numeric = Number(value);

            if (Number.isFinite(numeric)) {
                cfg[key] = numeric;
            }

            return;
        }

        cfg[key] = value;
    });

    return cfg;
}

export function createSupportMaterial(options = {}) {
    const cfg = withDefaults(options);

    return new THREE.MeshStandardMaterial({
        color: new THREE.Color(cfg.color),
        transparent: true,
        opacity: cfg.opacity,
        roughness: 0.35,
        metalness: 0.05,
        // Support tidak boleh menutupi model utama, jadi tidak menulis depth
        // buffer — model tetap terlihat menembus support.
        depthWrite: false,
        side: THREE.DoubleSide,
    });
}

/**
 * @param {THREE.Object3D} model model yang sudah diorientasikan & didudukkan di meja
 * @param {object} options parameter dari config/printing.php
 * @returns {{group: THREE.Group, pillarCount: number, grossVolumeCm3: number,
 *            materialVolumeCm3: number, overhangFaces: number, cellSizeMm: number}}
 */
export function buildSupport(model, options = {}) {
    const cfg = withDefaults(options);
    const group = new THREE.Group();
    group.name = 'support-structure';

    const empty = {
        group,
        pillarCount: 0,
        grossVolumeCm3: 0,
        materialVolumeCm3: 0,
        overhangFaces: 0,
        cellSizeMm: cfg.gridSizeMm,
    };

    const meshes = [];
    model.updateMatrixWorld(true);
    model.traverse((child) => {
        if (child.isMesh && child.geometry?.getAttribute('position')?.count) {
            meshes.push(child);
        }
    });

    if (meshes.length === 0) {
        return empty;
    }

    // `precise` penting di sini: bounding box konservatif membuat posisi meja
    // cetak terbaca lebih rendah dari yang sebenarnya, sehingga muncul pilar
    // pendek palsu di bawah model yang sudah diputar.
    const box = new THREE.Box3().setFromObject(model, true);
    const size = box.getSize(new THREE.Vector3());

    if (size.x <= 0 || size.z <= 0) {
        return empty;
    }

    // Ukuran sel menyesuaikan model supaya jumlah pilar tetap terkendali.
    const span = Math.max(size.x, size.z);
    const cell = Math.max(cfg.gridSizeMm, span / cfg.maxCellsPerAxis);

    const threshold = Math.cos((cfg.overhangAngleDeg * Math.PI) / 180);
    const cells = new Map();
    let overhangFaces = 0;

    const a = new THREE.Vector3();
    const b = new THREE.Vector3();
    const c = new THREE.Vector3();
    const ab = new THREE.Vector3();
    const ac = new THREE.Vector3();
    const normal = new THREE.Vector3();

    meshes.forEach((mesh) => {
        const position = mesh.geometry.getAttribute('position');
        const index = mesh.geometry.index;
        const count = index ? index.count : position.count;
        const matrix = mesh.matrixWorld;

        for (let i = 0; i < count; i += 3) {
            const i0 = index ? index.getX(i) : i;
            const i1 = index ? index.getX(i + 1) : i + 1;
            const i2 = index ? index.getX(i + 2) : i + 2;

            a.fromBufferAttribute(position, i0).applyMatrix4(matrix);
            b.fromBufferAttribute(position, i1).applyMatrix4(matrix);
            c.fromBufferAttribute(position, i2).applyMatrix4(matrix);

            ab.subVectors(b, a);
            ac.subVectors(c, a);
            normal.crossVectors(ab, ac).normalize();

            // Muka makin mendatar menghadap bawah => -normal.y makin mendekati 1.
            if (-normal.y <= threshold) {
                continue;
            }

            overhangFaces += 1;

            // Segitiga dirasterisasi ke kisi: setiap sel yang titik pusatnya
            // berada di dalam proyeksi segitiga ikut terisi, dengan ketinggian
            // hasil interpolasi. Sampel titik saja tidak memadai — satu bidang
            // overhang datar bisa hanya terdiri dari dua segitiga besar.
            rasterizeTriangle(a, b, c, box.min, cell, (cx, cz, y) => {
                const key = cx * 100000 + cz;
                const existing = cells.get(key);

                if (existing === undefined || y < existing.y) {
                    cells.set(key, { cx, cz, y });
                }
            });
        }
    });

    if (cells.size === 0) {
        return { ...empty, overhangFaces, cellSizeMm: cell };
    }

    // --- tumbuhkan pilar dari meja atau dari permukaan model di bawahnya ---
    const raycaster = new THREE.Raycaster();
    const down = new THREE.Vector3(0, -1, 0);
    const origin = new THREE.Vector3();
    const pillars = [];
    let grossVolume = 0;

    cells.forEach(({ cx, cz, y }) => {
        // Overhang yang sudah menempel meja tidak perlu ditopang.
        if (y - box.min.y <= cfg.plateToleranceMm) {
            return;
        }

        const centerX = box.min.x + (cx + 0.5) * cell;
        const centerZ = box.min.z + (cz + 0.5) * cell;

        origin.set(centerX, y - 0.05, centerZ);
        raycaster.set(origin, down);
        raycaster.far = y - box.min.y;

        const hits = raycaster.intersectObjects(meshes, false);
        const baseY = hits.length > 0 ? hits[0].point.y : box.min.y;
        const height = y - baseY;

        if (height < cfg.minPillarHeightMm) {
            return;
        }

        const width = cell * cfg.pillarShrink;
        pillars.push({ x: centerX, z: centerZ, baseY, height, width });
        grossVolume += width * width * height;
    });

    if (pillars.length === 0) {
        return { ...empty, overhangFaces, cellSizeMm: cell };
    }

    const material = createSupportMaterial(cfg);

    // Pilar: satu geometri kubus, di-instance ulang dengan matriks masing-masing.
    const pillarGeometry = new THREE.BoxGeometry(1, 1, 1);
    const pillarMesh = new THREE.InstancedMesh(pillarGeometry, material, pillars.length);
    pillarMesh.name = 'support-pillars';

    // Pelat dasar tipis hanya untuk pilar yang benar-benar berdiri di meja.
    const grounded = pillars.filter((p) => p.baseY - box.min.y < 0.01);
    const baseGeometry = new THREE.BoxGeometry(1, 1, 1);
    const baseMesh = grounded.length > 0
        ? new THREE.InstancedMesh(baseGeometry, material, grounded.length)
        : null;

    if (baseMesh) {
        baseMesh.name = 'support-base';
    }

    const matrix = new THREE.Matrix4();
    const position = new THREE.Vector3();
    const scale = new THREE.Vector3();
    const quaternion = new THREE.Quaternion();

    pillars.forEach((pillar, i) => {
        position.set(pillar.x, pillar.baseY + pillar.height / 2, pillar.z);
        scale.set(pillar.width, pillar.height, pillar.width);
        pillarMesh.setMatrixAt(i, matrix.compose(position, quaternion, scale));
    });

    pillarMesh.instanceMatrix.needsUpdate = true;
    group.add(pillarMesh);

    if (baseMesh) {
        const baseWidth = cell * cfg.pillarShrink * cfg.baseExpand;

        grounded.forEach((pillar, i) => {
            position.set(pillar.x, box.min.y + cfg.baseHeightMm / 2, pillar.z);
            scale.set(baseWidth, cfg.baseHeightMm, baseWidth);
            baseMesh.setMatrixAt(i, matrix.compose(position, quaternion, scale));
            grossVolume += baseWidth * baseWidth * cfg.baseHeightMm;
        });

        baseMesh.instanceMatrix.needsUpdate = true;
        group.add(baseMesh);
    }

    // Support dicetak renggang, jadi volume bahannya hanya sebagian volume kasar.
    const grossVolumeCm3 = grossVolume / 1000;

    return {
        group,
        pillarCount: pillars.length,
        grossVolumeCm3,
        materialVolumeCm3: grossVolumeCm3 * cfg.infill,
        overhangFaces,
        cellSizeMm: cell,
    };
}

/** Batas aman agar satu segitiga raksasa tidak membekukan browser. */
const MAX_CELLS_PER_TRIANGLE = 40000;

/**
 * Rasterisasi proyeksi segitiga ke kisi bidang meja.
 *
 * Setiap sel yang titik pusatnya berada di dalam segitiga dilaporkan beserta
 * ketinggian hasil interpolasi barisentrik, sehingga bidang overhang datar
 * terisi merata berapa pun jumlah segitiganya.
 */
function rasterizeTriangle(a, b, c, origin, cell, report) {
    const minX = Math.min(a.x, b.x, c.x);
    const maxX = Math.max(a.x, b.x, c.x);
    const minZ = Math.min(a.z, b.z, c.z);
    const maxZ = Math.max(a.z, b.z, c.z);

    const cx0 = Math.floor((minX - origin.x) / cell);
    const cx1 = Math.floor((maxX - origin.x) / cell);
    const cz0 = Math.floor((minZ - origin.z) / cell);
    const cz1 = Math.floor((maxZ - origin.z) / cell);

    // Denominator koordinat barisentrik pada bidang XZ. Mendekati nol berarti
    // segitiga tegak lurus meja — proyeksinya berupa garis, tidak perlu support.
    const denom = (b.z - c.z) * (a.x - c.x) + (c.x - b.x) * (a.z - c.z);

    if (Math.abs(denom) < 1e-9) {
        return;
    }

    const cellCount = (cx1 - cx0 + 1) * (cz1 - cz0 + 1);
    let reported = false;

    if (cellCount <= MAX_CELLS_PER_TRIANGLE) {
        for (let cx = cx0; cx <= cx1; cx += 1) {
            for (let cz = cz0; cz <= cz1; cz += 1) {
                const px = origin.x + (cx + 0.5) * cell;
                const pz = origin.z + (cz + 0.5) * cell;

                const w1 = ((b.z - c.z) * (px - c.x) + (c.x - b.x) * (pz - c.z)) / denom;
                const w2 = ((c.z - a.z) * (px - c.x) + (a.x - c.x) * (pz - c.z)) / denom;
                const w3 = 1 - w1 - w2;

                if (w1 < -1e-6 || w2 < -1e-6 || w3 < -1e-6) {
                    continue;
                }

                report(cx, cz, w1 * a.y + w2 * b.y + w3 * c.y);
                reported = true;
            }
        }
    }

    // Segitiga yang lebih kecil dari satu sel bisa saja tidak memuat satu pun
    // titik pusat sel; titik beratnya tetap dilaporkan agar tidak terlewat.
    if (!reported) {
        const gx = (a.x + b.x + c.x) / 3;
        const gz = (a.z + b.z + c.z) / 3;
        const gy = (a.y + b.y + c.y) / 3;

        report(
            Math.floor((gx - origin.x) / cell),
            Math.floor((gz - origin.z) / cell),
            gy
        );
    }
}

/** Bebaskan seluruh sumber daya GPU milik grup support. */
export function disposeSupport(group) {
    if (!group) {
        return;
    }

    group.traverse((child) => {
        if (child.isMesh || child.isInstancedMesh) {
            child.geometry?.dispose();

            if (Array.isArray(child.material)) {
                child.material.forEach((m) => m.dispose());
            } else {
                child.material?.dispose();
            }
        }
    });

    group.removeFromParent();
}
