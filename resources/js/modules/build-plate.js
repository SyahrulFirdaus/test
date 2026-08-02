import * as THREE from 'three';

/**
 * Virtual Build Plate.
 *
 * Menggambar area cetak mesin yang dipilih persis seperti tampilan Ultimaker
 * Cura: meja cetak beserta kisinya, kotak batas volume cetak, sumbu X/Y/Z,
 * dan penanda titik origin.
 *
 * Konvensi sumbu mengikuti build_volume pada config/printing.php —
 * `x` lebar meja, `y` kedalaman meja, `z` tinggi maksimum — lalu dipetakan ke
 * ruang Three.js yang sumbu tegaknya Y:
 *
 *     printer.x -> three.x   (lebar)
 *     printer.y -> three.z   (kedalaman)
 *     printer.z -> three.y   (tinggi)
 *
 * Titik origin (0,0,0) berada di tengah meja, sama seperti mesin yang
 * memusatkan koordinatnya, sehingga model yang belum diatur posisinya berdiri
 * tepat di tengah area cetak.
 */

const PLATE_COLOR = 0xf1ebe8;
const GRID_MINOR = 0xd8cdc8;
const GRID_MAJOR = 0xb9a9a3;
const VOLUME_COLOR = 0x95271d;
const AXIS_COLORS = { x: 0xc0392b, y: 0x2f7d3a, z: 0x2f5fb8 };

/** Jarak antar garis kisi, dinaikkan otomatis pada meja yang besar. */
function gridStep(span) {
    const candidates = [10, 20, 25, 50];

    return candidates.find((step) => span / step <= 30) ?? 100;
}

export default class BuildPlate {
    /**
     * @param {THREE.Scene} scene
     * @param {{x:number,y:number,z:number}} volume ukuran build volume printer
     */
    constructor(scene, volume) {
        this.scene = scene;
        this.group = new THREE.Group();
        this.group.name = 'build-plate';
        this.scene.add(this.group);

        this.gridVisible = true;
        this.axesVisible = false;
        this.volume = null;

        this.setVolume(volume);
    }

    /** Ukuran meja dalam ruang Three.js: lebar (x), kedalaman (z), tinggi (y). */
    get size() {
        return {
            width: this.volume.x,
            depth: this.volume.y,
            height: this.volume.z,
        };
    }

    /** Batas area cetak pada bidang meja, dipakai untuk menata & memvalidasi model. */
    get bounds() {
        const { width, depth, height } = this.size;

        return {
            minX: -width / 2,
            maxX: width / 2,
            minZ: -depth / 2,
            maxZ: depth / 2,
            maxY: height,
        };
    }

    /** Gambar ulang seluruh build plate untuk ukuran printer yang baru. */
    setVolume(volume) {
        const next = {
            x: Math.max(1, Number(volume?.x) || 1),
            y: Math.max(1, Number(volume?.y) || 1),
            z: Math.max(1, Number(volume?.z) || 1),
        };

        if (this.volume && next.x === this.volume.x && next.y === this.volume.y && next.z === this.volume.z) {
            return;
        }

        this.volume = next;
        this.rebuild();
    }

    rebuild() {
        this.disposeChildren();

        const { width, depth, height } = this.size;

        this.buildPlateSurface(width, depth);
        this.buildGrid(width, depth);
        this.buildVolumeBox(width, depth, height);
        this.buildAxes(width, depth, height);
        this.buildOrigin(width, depth);

        this.setGridVisible(this.gridVisible);
        this.setAxesVisible(this.axesVisible);
    }

    /** Permukaan meja cetak. */
    buildPlateSurface(width, depth) {
        const geometry = new THREE.PlaneGeometry(width, depth);
        geometry.rotateX(-Math.PI / 2);

        this.plate = new THREE.Mesh(
            geometry,
            new THREE.MeshStandardMaterial({
                color: PLATE_COLOR,
                roughness: 0.95,
                metalness: 0.0,
                // Meja digambar sedikit di bawah nol supaya tidak beradu
                // (z-fighting) dengan alas model yang duduk tepat di y = 0.
                side: THREE.DoubleSide,
            })
        );
        this.plate.position.y = -0.05;
        this.plate.name = 'build-plate-surface';
        this.group.add(this.plate);
    }

    /**
     * Kisi meja: garis rapat sebagai penanda skala, garis tebal tiap lima
     * langkah, dan dua garis tengah yang menandai sumbu meja.
     */
    buildGrid(width, depth) {
        const step = gridStep(Math.max(width, depth));
        const minor = [];
        const major = [];

        const halfWidth = width / 2;
        const halfDepth = depth / 2;

        const push = (target, ax, az, bx, bz) => target.push(ax, 0, az, bx, 0, bz);

        for (let offset = 0; offset <= halfWidth + 1e-6; offset += step) {
            const bucket = Math.round(offset / step) % 5 === 0 ? major : minor;

            push(bucket, offset, -halfDepth, offset, halfDepth);

            if (offset > 0) {
                push(bucket, -offset, -halfDepth, -offset, halfDepth);
            }
        }

        for (let offset = 0; offset <= halfDepth + 1e-6; offset += step) {
            const bucket = Math.round(offset / step) % 5 === 0 ? major : minor;

            push(bucket, -halfWidth, offset, halfWidth, offset);

            if (offset > 0) {
                push(bucket, -halfWidth, -offset, halfWidth, -offset);
            }
        }

        this.gridLines = [
            this.createLines(minor, GRID_MINOR, 0.5),
            this.createLines(major, GRID_MAJOR, 0.85),
        ].filter(Boolean);

        this.gridLines.forEach((line) => this.group.add(line));
    }

    /** Kotak transparan yang menandai batas volume cetak mesin. */
    buildVolumeBox(width, depth, height) {
        const geometry = new THREE.BoxGeometry(width, height, depth);
        geometry.translate(0, height / 2, 0);

        this.volumeBox = new THREE.LineSegments(
            new THREE.EdgesGeometry(geometry),
            new THREE.LineBasicMaterial({ color: VOLUME_COLOR, transparent: true, opacity: 0.55 })
        );
        this.volumeBox.name = 'build-volume-box';
        this.group.add(this.volumeBox);

        geometry.dispose();
    }

    /**
     * Sumbu X, Y, dan Z yang tumbuh dari titik origin.
     * Warnanya mengikuti konvensi umum software CAD: X merah, Y hijau (tegak),
     * Z biru (kedalaman meja).
     */
    buildAxes(width, depth, height) {
        const length = {
            x: width / 2,
            y: Math.min(height, Math.max(width, depth) / 2),
            z: depth / 2,
        };

        this.axes = new THREE.Group();
        this.axes.name = 'build-plate-axes';

        const segments = [
            [AXIS_COLORS.x, [0, 0, 0, length.x, 0, 0]],
            [AXIS_COLORS.y, [0, 0, 0, 0, length.y, 0]],
            [AXIS_COLORS.z, [0, 0, 0, 0, 0, length.z]],
        ];

        segments.forEach(([color, points]) => {
            const line = this.createLines(points, color, 1);

            if (line) {
                this.axes.add(line);
            }
        });

        this.group.add(this.axes);
    }

    /** Penanda titik origin (0,0,0) di tengah meja. */
    buildOrigin(width, depth) {
        const radius = Math.max(1.2, Math.min(width, depth) / 90);

        this.origin = new THREE.Mesh(
            new THREE.SphereGeometry(radius, 20, 14),
            new THREE.MeshStandardMaterial({ color: VOLUME_COLOR, roughness: 0.4, metalness: 0.1 })
        );
        this.origin.name = 'build-plate-origin';
        this.origin.position.set(0, 0, 0);
        this.group.add(this.origin);
    }

    createLines(points, color, opacity) {
        if (points.length === 0) {
            return null;
        }

        const geometry = new THREE.BufferGeometry();
        geometry.setAttribute('position', new THREE.Float32BufferAttribute(points, 3));

        return new THREE.LineSegments(
            geometry,
            new THREE.LineBasicMaterial({ color, transparent: true, opacity })
        );
    }

    setGridVisible(visible) {
        this.gridVisible = visible;
        this.plate.visible = visible;
        this.gridLines.forEach((line) => {
            line.visible = visible;
        });
    }

    setAxesVisible(visible) {
        this.axesVisible = visible;
        this.axes.visible = visible;
        this.origin.visible = visible;
    }

    /**
     * Tandai bahwa ada model yang keluar area cetak.
     * Kotak volume berubah merah agar batasnya langsung terbaca.
     */
    setExceeded(exceeded) {
        this.volumeBox.material.color.set(exceeded ? 0xc0392b : VOLUME_COLOR);
        this.volumeBox.material.opacity = exceeded ? 0.9 : 0.55;
    }

    disposeChildren() {
        [...this.group.children].forEach((child) => {
            child.traverse?.((node) => {
                node.geometry?.dispose();

                if (Array.isArray(node.material)) {
                    node.material.forEach((material) => material.dispose());
                } else {
                    node.material?.dispose();
                }
            });

            this.group.remove(child);
        });
    }

    dispose() {
        this.disposeChildren();
        this.group.removeFromParent();
    }
}

/**
 * Periksa apakah sebuah model masih berada di dalam area cetak.
 *
 * Mengembalikan pelanggaran per sumbu sekaligus kotak-kotak bagian yang
 * berada di luar batas, sehingga bagian yang melewati mesin dapat digambar
 * merah alih-alih hanya diberi peringatan teks.
 *
 * @param {THREE.Box3} box bounding box model pada posisi & orientasi sekarang
 * @param {{minX:number,maxX:number,minZ:number,maxZ:number,maxY:number}} bounds
 */
export function checkBuildVolume(box, bounds) {
    const tolerance = 0.01;

    const violations = {
        left: box.min.x < bounds.minX - tolerance,
        right: box.max.x > bounds.maxX + tolerance,
        front: box.min.z < bounds.minZ - tolerance,
        back: box.max.z > bounds.maxZ + tolerance,
        top: box.max.y > bounds.maxY + tolerance,
    };

    const exceeds = Object.values(violations).some(Boolean);

    // Bagian model yang keluar batas, dipotong per sumbu. Tiap potongan berupa
    // kotak {center, size} siap digambar sebagai penanda merah.
    const regions = [];

    const push = (minX, maxX, minY, maxY, minZ, maxZ) => {
        const sizeX = maxX - minX;
        const sizeY = maxY - minY;
        const sizeZ = maxZ - minZ;

        if (sizeX > tolerance && sizeY > tolerance && sizeZ > tolerance) {
            regions.push({
                center: { x: (minX + maxX) / 2, y: (minY + maxY) / 2, z: (minZ + maxZ) / 2 },
                size: { x: sizeX, y: sizeY, z: sizeZ },
            });
        }
    };

    if (violations.top) {
        push(box.min.x, box.max.x, bounds.maxY, box.max.y, box.min.z, box.max.z);
    }

    // Sisi samping dipotong sampai batas tinggi saja supaya tidak bertumpuk
    // dengan potongan atas.
    const cappedTop = Math.min(box.max.y, bounds.maxY);

    if (violations.left) {
        push(box.min.x, Math.min(box.max.x, bounds.minX), box.min.y, cappedTop, box.min.z, box.max.z);
    }

    if (violations.right) {
        push(Math.max(box.min.x, bounds.maxX), box.max.x, box.min.y, cappedTop, box.min.z, box.max.z);
    }

    const cappedMinX = Math.max(box.min.x, bounds.minX);
    const cappedMaxX = Math.min(box.max.x, bounds.maxX);

    if (violations.front && cappedMaxX > cappedMinX) {
        push(cappedMinX, cappedMaxX, box.min.y, cappedTop, box.min.z, Math.min(box.max.z, bounds.minZ));
    }

    if (violations.back && cappedMaxX > cappedMinX) {
        push(cappedMinX, cappedMaxX, box.min.y, cappedTop, Math.max(box.min.z, bounds.maxZ), box.max.z);
    }

    return { exceeds, violations, regions };
}

/** Kotak merah transparan penanda bagian yang melewati batas area cetak. */
export function createExceedMarkers(regions) {
    const group = new THREE.Group();
    group.name = 'build-volume-exceeded';

    if (regions.length === 0) {
        return group;
    }

    const material = new THREE.MeshStandardMaterial({
        color: 0xc0392b,
        transparent: true,
        opacity: 0.28,
        roughness: 0.6,
        depthWrite: false,
        side: THREE.DoubleSide,
    });

    const edgeMaterial = new THREE.LineBasicMaterial({ color: 0xc0392b, transparent: true, opacity: 0.95 });

    regions.forEach((region) => {
        const geometry = new THREE.BoxGeometry(region.size.x, region.size.y, region.size.z);

        const mesh = new THREE.Mesh(geometry, material);
        mesh.position.set(region.center.x, region.center.y, region.center.z);
        group.add(mesh);

        const edges = new THREE.LineSegments(new THREE.EdgesGeometry(geometry), edgeMaterial);
        edges.position.copy(mesh.position);
        group.add(edges);
    });

    return group;
}

/** Bebaskan sumber daya GPU milik penanda pelanggaran area cetak. */
export function disposeExceedMarkers(group) {
    if (!group) {
        return;
    }

    group.traverse((child) => {
        child.geometry?.dispose();

        if (Array.isArray(child.material)) {
            child.material.forEach((material) => material.dispose());
        } else {
            child.material?.dispose();
        }
    });

    group.removeFromParent();
}
