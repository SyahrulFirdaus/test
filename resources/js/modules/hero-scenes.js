/**
 * Objek 3D untuk hero halaman Services, Technologies, dan About.
 *
 * Bingkainya sama dengan hero halaman utama (components/scene-3d: gradien
 * maroon, lantai grid, kubus rangka) — yang berbeda hanya objek Three.js di
 * dalamnya, supaya tiap halaman punya ceritanya sendiri:
 *
 *   services      — pemindaian 3D: laser menyapu vas di atas turntable,
 *                   point cloud terbentuk, lalu menjadi model padat.
 *   technologies  — carousel empat spesimen: FDM (lapisan), SLA (resin
 *                   bening), MJF (gear nylon), SLM (lattice logam).
 *   about         — globe jaringan: titik-titik terhubung busur dengan
 *                   denyut cahaya, plus part cetak yang mengorbit.
 *
 * Halaman utama tetap memakai ./print-showcase.js (part sedang dicetak).
 *
 * Seperti print-showcase, modul ini dimuat lewat dynamic import dari
 * auth-scene.js, hanya bila kanvasnya tampil dan WebGL tersedia.
 */
import * as THREE from 'three';

const BRAND = 0x95271d;
const GLOW = 0xff7a52;
const REFERENCE_ASPECT = 1.15;

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------------ dasar --- */

/**
 * Kerangka bersama: renderer, kamera yang mengikuti kursor sedikit, jeda saat
 * tab tersembunyi, dan komposisi bingkai (shiftX/shiftY/scale) yang sama
 * persis dengan PrintScene.
 */
class HeroScene {
    /** Tinggi kamera (satuan scene) pada skala 1. */
    cameraHeight = 3.1;

    /** Pose diam untuk pengguna yang mematikan animasi. */
    stillTime = 6;

    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.shiftX = options.shiftX || 0;
        this.shiftY = options.shiftY || 0;
        this.scale = options.scale || 1;
        this.clock = new THREE.Clock();
        this.elapsed = 0;
        this.running = true;

        this.pointer = new THREE.Vector2(0, 0);
        this.pointerTarget = new THREE.Vector2(0, 0);

        this.renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.35;

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);

        this.world = new THREE.Group();
        this.scene.add(this.world);

        this.buildLights();
        this.build();
        this.resize();

        this.onResize = () => this.resize();
        window.addEventListener('resize', this.onResize);

        this.onPointerMove = (event) => {
            this.pointerTarget.set(
                (event.clientX / window.innerWidth) * 2 - 1,
                (event.clientY / window.innerHeight) * 2 - 1
            );
        };
        window.addEventListener('pointermove', this.onPointerMove);

        this.onVisibility = () => {
            this.running = !document.hidden;
            this.clock.getDelta();
        };
        document.addEventListener('visibilitychange', this.onVisibility);

        if (reducedMotion) {
            this.update(0, this.stillTime);
            this.renderer.render(this.scene, this.camera);
        } else {
            this.renderer.setAnimationLoop(() => this.tick());
        }
    }

    buildLights() {
        this.scene.add(new THREE.HemisphereLight(0xffd9cf, 0x2a0906, 1.1));

        const key = new THREE.DirectionalLight(0xffffff, 2.2);
        key.position.set(3.5, 6, 4.5);
        this.scene.add(key);

        const fill = new THREE.DirectionalLight(0xffe6de, 0.65);
        fill.position.set(-4, 1.5, -2.5);
        this.scene.add(fill);

        const rim = new THREE.DirectionalLight(GLOW, 1.3);
        rim.position.set(-2, -1.5, 3);
        this.scene.add(rim);
    }

    /** Diisi tiap scene. */
    build() {}

    /** Diisi tiap scene. */
    update() {}

    tick() {
        // `paused` dipasang auth-scene.js saat kanvas keluar dari layar.
        if (!this.running || this.paused) {
            this.clock.getDelta();

            return;
        }

        const delta = Math.min(this.clock.getDelta(), 0.05);
        this.elapsed += delta;

        this.update(delta, this.elapsed);

        this.pointer.lerp(this.pointerTarget, 0.05);
        this.placeCamera();

        this.renderer.render(this.scene, this.camera);
    }

    placeCamera() {
        const d = this.frameScale;
        const distance = 8.2 * d;
        const viewHeight = 2 * distance * Math.tan((this.camera.fov * Math.PI) / 360);
        const viewWidth = viewHeight * this.camera.aspect;

        const targetX = -this.shiftX * viewWidth;
        const targetY = -this.shiftY * viewHeight;

        this.camera.position.set(
            targetX + this.pointer.x * 1.5,
            targetY + (this.cameraHeight - this.pointer.y * 1.1) * d,
            distance
        );
        this.camera.lookAt(targetX, targetY, 0);
    }

    resize() {
        const width = this.canvas.clientWidth || 1;
        const height = this.canvas.clientHeight || 1;
        const aspect = width / height;

        this.renderer.setSize(width, height, false);
        this.camera.aspect = aspect;
        this.camera.updateProjectionMatrix();

        this.frameScale = this.scale * Math.max(1, REFERENCE_ASPECT / aspect);
        this.placeCamera();

        if (reducedMotion) {
            this.renderer.render(this.scene, this.camera);
        }
    }
}

/** Alas bundar gelap dengan cincin menyala, dipakai sebagai panggung. */
function stage(radius, { grid = true } = {}) {
    const group = new THREE.Group();

    const slab = new THREE.Mesh(
        new THREE.CylinderGeometry(radius, radius * 1.04, 0.2, 72),
        new THREE.MeshStandardMaterial({ color: 0x1c0806, roughness: 0.5, metalness: 0.4, transparent: true, opacity: 0.92 })
    );
    slab.position.y = -0.1;
    group.add(slab);

    const ring = new THREE.Mesh(
        new THREE.TorusGeometry(radius * 1.02, 0.018, 8, 96),
        new THREE.MeshBasicMaterial({ color: GLOW, transparent: true, opacity: 0.6 })
    );
    ring.rotation.x = Math.PI / 2;
    ring.position.y = 0.005;
    group.add(ring);

    if (grid) {
        const polar = new THREE.PolarGridHelper(radius, 12, 5, 64, GLOW, 0xffffff);
        polar.material.transparent = true;
        polar.material.opacity = 0.14;
        polar.position.y = 0.006;
        group.add(polar);
    }

    return group;
}

const smooth = (t) => t * t * (3 - 2 * t);
const clamp01 = (t) => Math.min(1, Math.max(0, t));

/* --------------------------------------------------------------- services --- */

/**
 * Pemindaian 3D.
 *
 * Siklus: laser menyapu dari bawah ke atas dan meninggalkan point cloud →
 * point cloud berubah menjadi model padat → jeda → memudar, ulang.
 */
class ScanScene extends HeroScene {
    cameraHeight = 2.6;

    stillTime = 9.5;

    static HEIGHT = 3.2;

    static SCAN = 6.5;

    static SOLIDIFY = 1.6;

    static HOLD = 2.4;

    static FADE = 1;

    /** Jari-jari profil vas pada ketinggian y. */
    static radiusAt(y) {
        const t = y / ScanScene.HEIGHT;

        return 0.5 + 0.42 * Math.sin(t * Math.PI * 1.15 + 0.35) + 0.18 * Math.sin(t * Math.PI * 3.2) * (1 - t);
    }

    build() {
        this.world.position.y = -1.3;

        this.world.add(stage(2.3));

        this.turntable = new THREE.Group();
        this.world.add(this.turntable);

        // Vas dari profil putar; sisi dalam ikut dirender supaya bibir atasnya tidak berlubang.
        const profile = [];
        for (let i = 0; i <= 48; i++) {
            const y = (i / 48) * ScanScene.HEIGHT;
            profile.push(new THREE.Vector2(ScanScene.radiusAt(y), y));
        }
        const geometry = new THREE.LatheGeometry(profile, 72);

        this.solidMaterial = new THREE.MeshStandardMaterial({
            color: BRAND,
            roughness: 0.35,
            metalness: 0.3,
            emissive: 0x4a120c,
            side: THREE.DoubleSide,
            transparent: true,
            opacity: 0,
        });
        this.solid = new THREE.Mesh(geometry, this.solidMaterial);
        this.turntable.add(this.solid);

        this.ghost = new THREE.Mesh(
            geometry,
            new THREE.MeshBasicMaterial({ color: 0xffffff, wireframe: true, transparent: true, opacity: 0.06 })
        );
        this.turntable.add(this.ghost);

        // Point cloud: sampel acak di permukaan, diurutkan dari bawah supaya
        // cukup menaikkan drawRange untuk "memindai" lapis demi lapis.
        const count = 5200;
        const points = [];
        for (let i = 0; i < count; i++) {
            const y = Math.random() * ScanScene.HEIGHT;
            const a = Math.random() * Math.PI * 2;
            const r = ScanScene.radiusAt(y) * (1 + (Math.random() - 0.5) * 0.03);
            points.push([Math.cos(a) * r, y, Math.sin(a) * r]);
        }
        points.sort((p, q) => p[1] - q[1]);
        this.cloudHeights = points.map((p) => p[1]);

        const cloudGeometry = new THREE.BufferGeometry();
        cloudGeometry.setAttribute('position', new THREE.Float32BufferAttribute(points.flat(), 3));
        cloudGeometry.setDrawRange(0, 0);

        this.cloudMaterial = new THREE.PointsMaterial({
            color: GLOW,
            size: 0.035,
            transparent: true,
            opacity: 0.9,
            depthWrite: false,
            blending: THREE.AdditiveBlending,
        });
        this.cloud = new THREE.Points(cloudGeometry, this.cloudMaterial);
        this.turntable.add(this.cloud);

        // Kepala pemindai di rel tegak, di sisi kanan.
        this.scanner = new THREE.Group();
        this.scanner.position.set(2.7, 0, 0.6);
        this.world.add(this.scanner);

        const rail = new THREE.Mesh(
            new THREE.CylinderGeometry(0.035, 0.035, ScanScene.HEIGHT + 0.8, 12),
            new THREE.MeshStandardMaterial({ color: 0xcfc6c2, metalness: 0.85, roughness: 0.3 })
        );
        rail.position.y = (ScanScene.HEIGHT + 0.8) / 2;
        this.scanner.add(rail);

        this.head = new THREE.Mesh(
            new THREE.BoxGeometry(0.34, 0.2, 0.28),
            new THREE.MeshStandardMaterial({ color: 0xe8ded9, metalness: 0.7, roughness: 0.3 })
        );
        this.scanner.add(this.head);

        this.lens = new THREE.Mesh(
            new THREE.SphereGeometry(0.05, 16, 12),
            new THREE.MeshBasicMaterial({ color: GLOW })
        );
        this.lens.position.x = -0.18;
        this.head.add(this.lens);

        // Lembar laser: segitiga dari lensa ke kedua tepi objek.
        const sheet = new THREE.BufferGeometry();
        sheet.setAttribute('position', new THREE.BufferAttribute(new Float32Array(9), 3));
        this.sheet = new THREE.Mesh(
            sheet,
            new THREE.MeshBasicMaterial({
                color: GLOW,
                transparent: true,
                opacity: 0.16,
                side: THREE.DoubleSide,
                depthWrite: false,
                blending: THREE.AdditiveBlending,
            })
        );
        this.world.add(this.sheet);

        // Garis laser yang melingkari objek pada ketinggian pindai.
        this.scanRing = new THREE.Mesh(
            new THREE.TorusGeometry(1, 0.012, 6, 96),
            new THREE.MeshBasicMaterial({ color: GLOW, transparent: true, opacity: 0.95 })
        );
        this.scanRing.rotation.x = Math.PI / 2;
        this.world.add(this.scanRing);

        this.laserLight = new THREE.PointLight(GLOW, 5, 6, 2);
        this.world.add(this.laserLight);
    }

    update(delta, elapsed) {
        const { SCAN, SOLIDIFY, HOLD, FADE, HEIGHT } = ScanScene;
        const cycle = SCAN + SOLIDIFY + HOLD + FADE;
        const t = elapsed % cycle;

        const scan = clamp01(t / SCAN);
        const solidify = clamp01((t - SCAN) / SOLIDIFY);
        const fade = clamp01((t - SCAN - SOLIDIFY - HOLD) / FADE);
        const scanning = t < SCAN;

        this.turntable.rotation.y = elapsed * 0.45;

        // Laser naik perlahan; point cloud menyusul di bawahnya.
        const height = smooth(scan) * HEIGHT;
        const visible = this.cloudHeights.findIndex((y) => y > height);
        this.cloud.geometry.setDrawRange(0, visible === -1 ? this.cloudHeights.length : visible);
        this.cloudMaterial.opacity = 0.9 * (1 - smooth(solidify)) * (1 - fade);

        this.solidMaterial.opacity = smooth(solidify) * (1 - fade);
        this.solid.visible = this.solidMaterial.opacity > 0.01;
        this.ghost.material.opacity = 0.06 * (1 - smooth(solidify) * 0.7);

        const headY = scanning ? height : HEIGHT * (1 - smooth(clamp01((t - SCAN) / (SOLIDIFY + HOLD + FADE))));
        this.head.position.y = headY;

        const radius = ScanScene.radiusAt(Math.min(height, HEIGHT));
        this.scanRing.visible = scanning;
        this.sheet.visible = scanning;
        this.laserLight.intensity = scanning ? 5 : 0;

        if (scanning) {
            this.scanRing.position.y = height;
            this.scanRing.scale.setScalar(radius);

            const lens = new THREE.Vector3();
            this.lens.getWorldPosition(lens);
            this.world.worldToLocal(lens);

            // Dua titik singgung kira-kira di sisi depan & belakang objek.
            const position = this.sheet.geometry.getAttribute('position');
            position.setXYZ(0, lens.x, lens.y, lens.z);
            position.setXYZ(1, 0, height, radius);
            position.setXYZ(2, 0, height, -radius);
            position.needsUpdate = true;
            this.sheet.geometry.computeBoundingSphere();

            this.laserLight.position.set(radius + 0.3, height, 0.4);
        }
    }
}

/* ----------------------------------------------------------- technologies --- */

/** Carousel empat spesimen teknologi cetak. */
class TechnologyScene extends HeroScene {
    cameraHeight = 2.9;

    build() {
        this.world.position.y = -0.9;

        this.world.add(stage(3.1));

        this.carousel = new THREE.Group();
        this.world.add(this.carousel);

        this.specimens = [this.fdm(), this.sla(), this.mjf(), this.slm()];

        this.specimens.forEach((specimen, index) => {
            const angle = (index / this.specimens.length) * Math.PI * 2;
            const holder = new THREE.Group();
            holder.position.set(Math.cos(angle) * 2.15, 0, Math.sin(angle) * 2.15);

            // Tiang cahaya tipis dari panggung ke spesimen.
            const beam = new THREE.Mesh(
                new THREE.CylinderGeometry(0.42, 0.55, 0.9, 32, 1, true),
                new THREE.MeshBasicMaterial({
                    color: GLOW, transparent: true, opacity: 0.07, side: THREE.DoubleSide,
                    depthWrite: false, blending: THREE.AdditiveBlending,
                })
            );
            beam.position.y = 0.45;
            holder.add(beam);

            const pad = new THREE.Mesh(
                new THREE.TorusGeometry(0.5, 0.015, 6, 48),
                new THREE.MeshBasicMaterial({ color: GLOW, transparent: true, opacity: 0.7 })
            );
            pad.rotation.x = Math.PI / 2;
            pad.position.y = 0.02;
            holder.add(pad);

            specimen.position.y = 1.25;
            holder.add(specimen);

            holder.userData.phase = index * 1.7;
            this.carousel.add(holder);
        });

        // Inti di tengah: kristal kecil berputar, penanda "satu standar kualitas".
        this.core = new THREE.Mesh(
            new THREE.OctahedronGeometry(0.32, 0),
            new THREE.MeshStandardMaterial({ color: GLOW, emissive: GLOW, emissiveIntensity: 0.6, roughness: 0.3, metalness: 0.2 })
        );
        this.core.position.y = 0.9;
        this.world.add(this.core);

        this.coreLight = new THREE.PointLight(GLOW, 4, 5, 2);
        this.coreLight.position.y = 1;
        this.world.add(this.coreLight);
    }

    /** FDM: tumpukan lapisan berkontur seperti hasil ekstrusi. */
    fdm() {
        const group = new THREE.Group();
        const material = new THREE.MeshStandardMaterial({ color: BRAND, roughness: 0.55, metalness: 0.15, emissive: 0x3a0e09 });
        const layers = 20;

        for (let i = 0; i < layers; i++) {
            const t = i / (layers - 1);
            const radius = 0.34 + 0.16 * Math.sin(t * Math.PI) + 0.05 * Math.sin(t * Math.PI * 4);
            const layer = new THREE.Mesh(new THREE.CylinderGeometry(radius, radius, 0.045, 40), material);
            layer.position.y = (t - 0.5) * 1.05;
            group.add(layer);
        }

        return group;
    }

    /** SLA: kristal resin bening yang mengilap. */
    sla() {
        const group = new THREE.Group();
        const geometry = new THREE.IcosahedronGeometry(0.55, 0);

        group.add(new THREE.Mesh(
            geometry,
            new THREE.MeshPhysicalMaterial({
                color: 0xffb3a1, roughness: 0.05, metalness: 0, clearcoat: 1, clearcoatRoughness: 0.05,
                transparent: true, opacity: 0.55, side: THREE.DoubleSide, depthWrite: false,
            })
        ));

        group.add(new THREE.LineSegments(
            new THREE.EdgesGeometry(geometry),
            new THREE.LineBasicMaterial({ color: 0xffe2da, transparent: true, opacity: 0.85 })
        ));

        return group;
    }

    /** MJF: gear nylon abu gelap bertekstur kasar. */
    mjf() {
        const teeth = 12;
        const outer = 0.58;
        const inner = 0.47;
        const shape = new THREE.Shape();

        for (let i = 0; i < teeth * 4; i++) {
            const angle = (i / (teeth * 4)) * Math.PI * 2;
            const radius = i % 4 < 2 ? outer : inner;
            const x = Math.cos(angle) * radius;
            const y = Math.sin(angle) * radius;

            if (i === 0) {
                shape.moveTo(x, y);
            } else {
                shape.lineTo(x, y);
            }
        }
        shape.closePath();

        const hole = new THREE.Path();
        hole.absarc(0, 0, 0.16, 0, Math.PI * 2, true);
        shape.holes.push(hole);

        const geometry = new THREE.ExtrudeGeometry(shape, { depth: 0.22, bevelEnabled: true, bevelSize: 0.015, bevelThickness: 0.015, bevelSegments: 1 });
        geometry.center();

        const gear = new THREE.Mesh(geometry, new THREE.MeshStandardMaterial({ color: 0x3b3438, roughness: 0.95, metalness: 0.05 }));
        gear.rotation.x = -0.35;

        const group = new THREE.Group();
        group.add(gear);

        return group;
    }

    /** SLM: struktur lattice logam — batang dan simpul. */
    slm() {
        const group = new THREE.Group();
        const metal = new THREE.MeshStandardMaterial({ color: 0xe3dcd8, metalness: 1, roughness: 0.22 });
        const base = new THREE.OctahedronGeometry(0.62, 0);
        const edges = new THREE.EdgesGeometry(base).getAttribute('position');
        const up = new THREE.Vector3(0, 1, 0);

        for (let i = 0; i < edges.count; i += 2) {
            const a = new THREE.Vector3().fromBufferAttribute(edges, i);
            const b = new THREE.Vector3().fromBufferAttribute(edges, i + 1);
            const length = a.distanceTo(b);

            const strut = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, length, 10), metal);
            strut.position.copy(a).add(b).multiplyScalar(0.5);
            strut.quaternion.setFromUnitVectors(up, b.clone().sub(a).normalize());
            group.add(strut);
        }

        // Batang dalam dari tiap simpul ke pusat, seperti sel lattice.
        const vertices = base.getAttribute('position');
        const seen = new Set();

        for (let i = 0; i < vertices.count; i++) {
            const v = new THREE.Vector3().fromBufferAttribute(vertices, i);
            const key = v.toArray().map((n) => n.toFixed(3)).join(',');

            if (seen.has(key)) {
                continue;
            }
            seen.add(key);

            const node = new THREE.Mesh(new THREE.SphereGeometry(0.06, 14, 10), metal);
            node.position.copy(v);
            group.add(node);

            const spoke = new THREE.Mesh(new THREE.CylinderGeometry(0.018, 0.018, v.length(), 8), metal);
            spoke.position.copy(v).multiplyScalar(0.5);
            spoke.quaternion.setFromUnitVectors(up, v.clone().normalize());
            group.add(spoke);
        }

        return group;
    }

    update(delta, elapsed) {
        this.carousel.rotation.y = elapsed * 0.22;

        this.carousel.children.forEach((holder, index) => {
            const specimen = this.specimens[index];
            specimen.position.y = 1.25 + Math.sin(elapsed * 1.3 + holder.userData.phase) * 0.09;
            specimen.rotation.y = elapsed * 0.7 + holder.userData.phase;
        });

        this.core.rotation.y = elapsed * 1.1;
        this.core.rotation.x = Math.sin(elapsed * 0.6) * 0.4;
        this.core.position.y = 0.9 + Math.sin(elapsed * 1.6) * 0.06;
    }
}

/* ------------------------------------------------------------------ about --- */

/** Globe jaringan: pelanggan, mitra, dan tim yang saling terhubung. */
class NetworkScene extends HeroScene {
    cameraHeight = 1.4;

    static RADIUS = 1.85;

    build() {
        const R = NetworkScene.RADIUS;

        this.world.position.y = 0.2;

        this.globe = new THREE.Group();
        this.world.add(this.globe);

        this.globe.add(new THREE.Mesh(
            new THREE.SphereGeometry(R * 0.985, 48, 32),
            new THREE.MeshStandardMaterial({ color: 0x2a0906, roughness: 0.6, metalness: 0.2, transparent: true, opacity: 0.55 })
        ));

        this.globe.add(new THREE.LineSegments(
            new THREE.WireframeGeometry(new THREE.IcosahedronGeometry(R, 3)),
            new THREE.LineBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.08 })
        ));

        // Simpul tersebar merata (spiral Fibonacci).
        const count = 34;
        const golden = Math.PI * (3 - Math.sqrt(5));
        this.nodes = [];

        for (let i = 0; i < count; i++) {
            const y = 1 - (i / (count - 1)) * 2;
            const r = Math.sqrt(1 - y * y);
            const theta = golden * i;
            const position = new THREE.Vector3(Math.cos(theta) * r, y, Math.sin(theta) * r).multiplyScalar(R);

            const node = new THREE.Mesh(
                new THREE.SphereGeometry(i % 5 === 0 ? 0.07 : 0.045, 12, 8),
                new THREE.MeshBasicMaterial({ color: i % 5 === 0 ? 0xffffff : GLOW })
            );
            node.position.copy(position);
            node.userData.phase = i * 0.9;
            this.globe.add(node);
            this.nodes.push(node);
        }

        // Busur antar-simpul terdekat, melengkung keluar permukaan.
        this.pulses = [];
        const arcMaterial = new THREE.LineBasicMaterial({ color: GLOW, transparent: true, opacity: 0.35 });

        this.nodes.forEach((node, i) => {
            const neighbours = this.nodes
                .map((other, j) => ({ j, distance: node.position.distanceTo(other.position) }))
                .filter(({ j }) => j > i)
                .sort((a, b) => a.distance - b.distance)
                .slice(0, 2);

            neighbours.forEach(({ j }, k) => {
                const a = node.position;
                const b = this.nodes[j].position;
                const mid = a.clone().add(b).multiplyScalar(0.5);
                mid.setLength(R * (1.18 + a.distanceTo(b) * 0.12));

                const curve = new THREE.QuadraticBezierCurve3(a.clone(), mid, b.clone());
                this.globe.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints(curve.getPoints(32)), arcMaterial));

                if ((i + k) % 2 === 0) {
                    const pulse = new THREE.Mesh(
                        new THREE.SphereGeometry(0.035, 8, 6),
                        new THREE.MeshBasicMaterial({ color: 0xffd2c4 })
                    );
                    pulse.userData = { curve, speed: 0.25 + ((i * 7 + k) % 5) * 0.06, offset: (i * 0.37) % 1 };
                    this.globe.add(pulse);
                    this.pulses.push(pulse);
                }
            });
        });

        // Cincin orbit miring dengan part cetak kecil yang mengitarinya.
        this.orbit = new THREE.Group();
        this.orbit.rotation.set(0.9, 0, -0.35);
        this.world.add(this.orbit);

        this.orbit.add(new THREE.Mesh(
            new THREE.TorusGeometry(R * 1.45, 0.012, 6, 160),
            new THREE.MeshBasicMaterial({ color: GLOW, transparent: true, opacity: 0.45 })
        ));

        this.satellite = new THREE.Mesh(
            new THREE.BoxGeometry(0.26, 0.26, 0.26),
            new THREE.MeshStandardMaterial({ color: BRAND, roughness: 0.3, metalness: 0.35, emissive: 0x4a120c })
        );
        this.satellite.add(new THREE.LineSegments(
            new THREE.EdgesGeometry(this.satellite.geometry),
            new THREE.LineBasicMaterial({ color: GLOW })
        ));
        this.orbit.add(this.satellite);

        this.halo = new THREE.Mesh(
            new THREE.RingGeometry(R * 1.02, R * 1.1, 96),
            new THREE.MeshBasicMaterial({
                color: GLOW, transparent: true, opacity: 0.12, side: THREE.DoubleSide,
                depthWrite: false, blending: THREE.AdditiveBlending,
            })
        );
        this.world.add(this.halo);
    }

    update(delta, elapsed) {
        const R = NetworkScene.RADIUS;

        this.globe.rotation.y = elapsed * 0.12;
        this.globe.rotation.x = 0.25 + Math.sin(elapsed * 0.2) * 0.05;

        this.nodes.forEach((node) => {
            node.scale.setScalar(1 + Math.max(0, Math.sin(elapsed * 1.8 + node.userData.phase)) * 0.6);
        });

        this.pulses.forEach((pulse) => {
            const { curve, speed, offset } = pulse.userData;
            curve.getPoint((elapsed * speed + offset) % 1, pulse.position);
        });

        const angle = elapsed * 0.5;
        this.satellite.position.set(Math.cos(angle) * R * 1.45, Math.sin(angle) * R * 1.45, 0);
        this.satellite.rotation.set(elapsed * 0.9, elapsed * 0.7, 0);

        // Halo selalu menghadap kamera, seperti atmosfer tipis.
        this.halo.quaternion.copy(this.camera.quaternion);
    }
}

/* --------------------------------------------------------------- analyzer --- */

/**
 * Pre-Print Analyzer (bagian "Cek kelayakan cetak" di halaman utama).
 *
 * Sebuah bracket mesin diperiksa: bidang pindai tegak menyapu dari kiri ke
 * kanan, dan bagian yang sudah dilewati berganti warna menjadi peta analisis —
 * hijau aman, kuning untuk permukaan menggantung (overhang). Kotak batas dan
 * penanda ukuran menyala di sekelilingnya.
 */
class AnalyzeScene extends HeroScene {
    cameraHeight = 2.2;

    stillTime = 5;

    static SWEEP = 4.2;

    static HOLD = 2.8;

    static RESET = 1.2;

    build() {
        this.renderer.localClippingEnabled = true;
        this.world.position.y = -0.75;

        this.part = new THREE.Group();
        this.part.scale.setScalar(1.1);
        this.world.add(this.part);

        const geometry = this.bracketGeometry();
        geometry.computeBoundingBox();
        this.box = geometry.boundingBox.clone();

        // Bidang pindai tegak (sumbu X dunia). analyzed: sisi kiri (sudah
        // dianalisis), pending: sisi kanan (belum).
        this.analyzedPlane = new THREE.Plane(new THREE.Vector3(-1, 0, 0), 0);
        this.pendingPlane = new THREE.Plane(new THREE.Vector3(1, 0, 0), 0);

        this.part.add(new THREE.Mesh(
            geometry,
            new THREE.MeshStandardMaterial({
                vertexColors: true, roughness: 0.45, metalness: 0.1,
                clippingPlanes: [this.analyzedPlane], side: THREE.DoubleSide,
            })
        ));

        this.part.add(new THREE.Mesh(
            geometry,
            new THREE.MeshStandardMaterial({
                color: BRAND, roughness: 0.35, metalness: 0.3, emissive: 0x4a120c,
                clippingPlanes: [this.pendingPlane], side: THREE.DoubleSide,
            })
        ));

        // Kotak batas dan penanda sudut: "dimensi" hasil pengukuran.
        const size = this.box.getSize(new THREE.Vector3());
        const center = this.box.getCenter(new THREE.Vector3());
        const frame = new THREE.LineSegments(
            new THREE.EdgesGeometry(new THREE.BoxGeometry(size.x + 0.16, size.y + 0.16, size.z + 0.16)),
            new THREE.LineDashedMaterial({ color: GLOW, dashSize: 0.08, gapSize: 0.06, transparent: true, opacity: 0.55 })
        );
        frame.computeLineDistances();
        frame.position.copy(center);
        this.part.add(frame);

        const cornerMaterial = new THREE.MeshBasicMaterial({ color: 0xffd2c4 });
        for (const x of [-1, 1]) {
            for (const y of [-1, 1]) {
                for (const z of [-1, 1]) {
                    const corner = new THREE.Mesh(new THREE.SphereGeometry(0.035, 10, 8), cornerMaterial);
                    corner.position.set(
                        center.x + x * (size.x / 2 + 0.08),
                        center.y + y * (size.y / 2 + 0.08),
                        center.z + z * (size.z / 2 + 0.08)
                    );
                    this.part.add(corner);
                }
            }
        }

        // Bidang pindai yang terlihat: panel tipis menyala dengan tepi terang.
        const planeHeight = 3.8;
        const planeDepth = 3.8;
        this.scanPanel = new THREE.Mesh(
            new THREE.PlaneGeometry(planeDepth, planeHeight),
            new THREE.MeshBasicMaterial({
                color: GLOW, transparent: true, opacity: 0.08, side: THREE.DoubleSide,
                depthWrite: false, blending: THREE.AdditiveBlending,
            })
        );
        this.scanPanel.rotation.y = Math.PI / 2;
        this.scanPanel.add(new THREE.LineSegments(
            new THREE.EdgesGeometry(new THREE.PlaneGeometry(planeDepth, planeHeight)),
            new THREE.LineBasicMaterial({ color: GLOW, transparent: true, opacity: 0.7 })
        ));
        this.scene.add(this.scanPanel);

        this.scanLight = new THREE.PointLight(GLOW, 4, 5, 2);
        this.scene.add(this.scanLight);

        // Rentang sapuan di ruang dunia, sedikit lebih lebar dari part yang berputar.
        this.sweepFrom = -2.4;
        this.sweepTo = 2.4;
    }

    /**
     * Bracket siku: pelat dasar berlubang dua, pelat tegak berlubang satu, dan
     * dua rusuk penguat. Warna tiap segitiga dari arah normalnya — muka yang
     * menghadap ke bawah (overhang) kuning, sisanya hijau.
     */
    bracketGeometry() {
        const plate = (width, height, holes) => {
            const shape = new THREE.Shape();
            const r = 0.12;
            shape.moveTo(-width / 2 + r, -height / 2);
            shape.lineTo(width / 2 - r, -height / 2);
            shape.quadraticCurveTo(width / 2, -height / 2, width / 2, -height / 2 + r);
            shape.lineTo(width / 2, height / 2 - r);
            shape.quadraticCurveTo(width / 2, height / 2, width / 2 - r, height / 2);
            shape.lineTo(-width / 2 + r, height / 2);
            shape.quadraticCurveTo(-width / 2, height / 2, -width / 2, height / 2 - r);
            shape.lineTo(-width / 2, -height / 2 + r);
            shape.quadraticCurveTo(-width / 2, -height / 2, -width / 2 + r, -height / 2);

            holes.forEach(([x, y, radius]) => {
                const hole = new THREE.Path();
                hole.absarc(x, y, radius, 0, Math.PI * 2, true);
                shape.holes.push(hole);
            });

            return new THREE.ExtrudeGeometry(shape, { depth: 0.22, bevelEnabled: true, bevelSize: 0.03, bevelThickness: 0.03, bevelSegments: 2, curveSegments: 24 });
        };

        // Pelat dasar mendatar.
        const base = plate(2.6, 1.5, [[-0.8, 0, 0.2], [0.8, 0, 0.2]]);
        base.rotateX(-Math.PI / 2);
        base.translate(0, 0, 0.75);

        // Pelat tegak di tepi belakang.
        const wall = plate(2.6, 1.4, [[0, 0.1, 0.28]]);
        wall.translate(0, 0.7, -0.75);

        // Rusuk segitiga penguat di kedua sisi.
        const rib = () => {
            const shape = new THREE.Shape();
            shape.moveTo(0, 0);
            shape.lineTo(0.8, 0);
            shape.lineTo(0, 0.8);
            shape.closePath();

            const geometry = new THREE.ExtrudeGeometry(shape, { depth: 0.14, bevelEnabled: false });
            geometry.rotateY(-Math.PI / 2);

            return geometry;
        };
        const ribLeft = rib();
        ribLeft.translate(-1.05, 0.22, -0.53);
        const ribRight = rib();
        ribRight.translate(1.19, 0.22, -0.53);

        const parts = [base, wall, ribLeft, ribRight].map((g) => g.toNonIndexed());
        const merged = mergeGeometries(parts);
        merged.computeVertexNormals();
        merged.center();

        // Peta analisis per segitiga.
        const normals = merged.getAttribute('normal');
        const colors = new Float32Array(normals.count * 3);
        // Sedikit diredam supaya tetap senada dengan latar maroon.
        const ok = new THREE.Color(0x5ebf98);
        const warn = new THREE.Color(0xf2b544);
        const color = new THREE.Color();

        for (let i = 0; i < normals.count; i += 3) {
            const ny = (normals.getY(i) + normals.getY(i + 1) + normals.getY(i + 2)) / 3;
            color.copy(ny < -0.5 ? warn : ok);

            for (let k = 0; k < 3; k++) {
                color.toArray(colors, (i + k) * 3);
            }
        }
        merged.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        return merged;
    }

    update(delta, elapsed) {
        const { SWEEP, HOLD, RESET } = AnalyzeScene;
        const t = elapsed % (SWEEP + HOLD + RESET);

        let progress;
        if (t < SWEEP) {
            progress = smooth(t / SWEEP);
        } else if (t < SWEEP + HOLD) {
            progress = 1;
        } else {
            progress = 1 - smooth((t - SWEEP - HOLD) / RESET);
        }

        this.part.rotation.y = -0.6 + Math.sin(elapsed * 0.35) * 0.5;
        this.part.rotation.x = 0.12;
        this.part.position.y = 0.9 + Math.sin(elapsed * 0.9) * 0.06;

        const x = THREE.MathUtils.lerp(this.sweepFrom, this.sweepTo, progress);
        const worldX = x + this.world.position.x;

        this.analyzedPlane.constant = worldX;
        this.pendingPlane.constant = -worldX;

        const sweeping = progress > 0.001 && progress < 0.999;
        this.scanPanel.visible = sweeping;
        this.scanPanel.position.set(worldX, this.world.position.y + 0.9, 0);
        this.scanLight.intensity = sweeping ? 4 : 0;
        this.scanLight.position.set(worldX, this.world.position.y + 1.4, 1.2);
    }
}

/**
 * Gabungkan beberapa BufferGeometry tak-terindeks yang atributnya sama
 * (position, normal, uv) menjadi satu.
 */
function mergeGeometries(geometries) {
    const merged = new THREE.BufferGeometry();

    for (const name of ['position', 'normal', 'uv']) {
        const attributes = geometries.map((g) => g.getAttribute(name));

        if (attributes.some((a) => !a)) {
            continue;
        }

        const itemSize = attributes[0].itemSize;
        const array = new Float32Array(attributes.reduce((sum, a) => sum + a.count * itemSize, 0));
        let offset = 0;

        attributes.forEach((a) => {
            array.set(a.array, offset);
            offset += a.array.length;
        });

        merged.setAttribute(name, new THREE.BufferAttribute(array, itemSize));
    }

    return merged;
}

/** Objek hero menurut nama variannya. */
export const HERO_SCENES = {
    services: ScanScene,
    technologies: TechnologyScene,
    about: NetworkScene,
    analyzer: AnalyzeScene,
};
