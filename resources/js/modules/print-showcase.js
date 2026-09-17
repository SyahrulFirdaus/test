/**
 * Animasi "part sedang dicetak" untuk panel brand halaman Masuk.
 *
 * Dimuat lewat dynamic import dari `auth-scene.js` supaya bundel Three.js
 * hanya diunduh saat panelnya memang tampil (layar lebar, WebGL tersedia).
 */
import * as THREE from 'three';

const BRAND = 0x95271d;
const GLOW = 0xff7a52;

/* Tinggi satu "lapisan" cetak dalam satuan scene. Pertumbuhan model dibulatkan
   ke kelipatan ini supaya tepiannya melangkah seperti hasil FDM, bukan naik
   mulus seperti wipe biasa. */
const LAYER = 0.04;

/* Perbandingan lebar-tinggi kanvas yang dianggap "pas". Lebih sempit dari ini,
   kamera dimundurkan supaya mesin tidak terpotong sisi kiri-kanannya. */
const REFERENCE_ASPECT = 1.15;

/* Durasi satu siklus (detik): naik mencetak, jeda memamerkan hasil, lalu surut. */
const PRINT_SECONDS = 9;
const HOLD_SECONDS = 2.6;
const RESET_SECONDS = 0.9;

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export default class PrintScene {
    /**
     * @param {HTMLCanvasElement} canvas
     * @param {{shiftX?: number, shiftY?: number, scale?: number}} [options]
     *        `shiftX`/`shiftY` menggeser mesin dari pusat bingkai dalam pecahan
     *        lebar/tinggi tampak (+x ke kanan, +y ke atas), sementara `scale`
     *        mengatur seberapa jauh kamera mundur. Ukurannya sengaja relatif
     *        supaya komposisi tetap sama pada kanvas sempit maupun selebar layar.
     */
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.shiftX = options.shiftX || 0;
        this.shiftY = options.shiftY || 0;
        this.scale = options.scale || 1;
        this.clock = new THREE.Clock();
        this.elapsed = 0;
        this.running = true;

        // Arah pandang mengikuti kursor sedikit saja; nilainya disimpan sebagai
        // target lalu dikejar perlahan agar gerakannya tidak patah-patah.
        this.pointer = new THREE.Vector2(0, 0);
        this.pointerTarget = new THREE.Vector2(0, 0);

        this.renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.35;
        // Bidang potong dipakai untuk memperlihatkan hanya bagian yang sudah tercetak.
        this.renderer.localClippingEnabled = true;

        this.scene = new THREE.Scene();

        this.camera = new THREE.PerspectiveCamera(38, 1, 0.1, 100);
        this.camera.position.set(0, 3.1, 8.2);

        // Seluruh isi mesin dikelompokkan dalam satu grup di titik asal;
        // penempatannya di dalam bingkai diatur lewat arah bidik kamera.
        this.world = new THREE.Group();
        this.scene.add(this.world);

        this.buildLights();
        this.buildPart();
        this.buildPlate();
        this.buildNozzle();

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

        // Tab yang tersembunyi tidak perlu dianimasikan sama sekali.
        this.onVisibility = () => {
            this.running = !document.hidden;
            this.clock.getDelta();
        };
        document.addEventListener('visibilitychange', this.onVisibility);

        if (reducedMotion) {
            // Tanpa animasi: tampilkan part yang sudah jadi, satu frame saja.
            this.apply(1);
            this.renderer.render(this.scene, this.camera);
        } else {
            this.renderer.setAnimationLoop(() => this.tick());
        }
    }

    /** Pencahayaan tiga titik, senada dengan viewer di halaman model. */
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

        // Cahaya kecil yang ikut naik bersama garis cetak, memberi kesan panas
        // pada lapisan yang baru keluar dari nozzle.
        this.hotLight = new THREE.PointLight(GLOW, 6, 7, 2);
        this.scene.add(this.hotLight);
    }

    /** Meja cetak: pelat gelap dengan grid teknik di permukaannya. */
    buildPlate() {
        this.plate = new THREE.Group();
        // Permukaan plate tepat menyentuh dasar part, jadi lapisan pertama benar-benar menempel.
        this.plate.position.y = this.minY;
        this.world.add(this.plate);

        const slab = new THREE.Mesh(
            new THREE.BoxGeometry(7.2, 0.22, 7.2),
            new THREE.MeshStandardMaterial({
                color: 0x1c0806,
                roughness: 0.55,
                metalness: 0.35,
                transparent: true,
                opacity: 0.92,
            })
        );
        slab.position.y = -0.11;
        this.plate.add(slab);

        const grid = new THREE.GridHelper(7.2, 18, GLOW, 0xffffff);
        grid.material.transparent = true;
        grid.material.opacity = 0.16;
        grid.position.y = 0.005;
        this.plate.add(grid);

        const edge = new THREE.Mesh(
            new THREE.BoxGeometry(7.24, 0.03, 7.24),
            new THREE.MeshBasicMaterial({ color: GLOW, transparent: true, opacity: 0.35 })
        );
        edge.position.y = 0.012;
        this.plate.add(edge);
    }

    /**
     * Part yang dicetak.
     *
     * Dua mesh memakai geometri yang sama: satu versi padat yang dipotong di
     * bawah garis cetak, dan satu rangka kawat untuk sisa di atasnya — seolah
     * pratinjau model yang belum terwujud.
     */
    buildPart() {
        this.part = new THREE.Group();
        this.world.add(this.part);

        const geometry = new THREE.TorusKnotGeometry(1.25, 0.4, 220, 36);
        geometry.computeBoundingBox();

        this.minY = geometry.boundingBox.min.y;
        this.maxY = geometry.boundingBox.max.y;

        // Bidang bernormal -Y: yang disisakan adalah titik dengan y <= konstanta.
        this.printedPlane = new THREE.Plane(new THREE.Vector3(0, -1, 0), 0);
        // Kebalikannya, untuk rangka kawat di atas garis cetak.
        this.pendingPlane = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);

        this.solid = new THREE.Mesh(
            geometry,
            new THREE.MeshStandardMaterial({
                color: BRAND,
                roughness: 0.3,
                metalness: 0.32,
                emissive: 0x4a120c,
                clippingPlanes: [this.printedPlane],
                // Tanpa DoubleSide, permukaan hasil potongan tampak berlubang.
                side: THREE.DoubleSide,
            })
        );
        this.part.add(this.solid);

        this.ghost = new THREE.Mesh(
            geometry,
            new THREE.MeshBasicMaterial({
                color: 0xffffff,
                wireframe: true,
                transparent: true,
                opacity: 0.1,
                clippingPlanes: [this.pendingPlane],
            })
        );
        this.part.add(this.ghost);

        // Bidang sapuan: cakram tipis menyala setinggi lapisan yang sedang dicetak.
        this.sweep = new THREE.Mesh(
            new THREE.CircleGeometry(2.1, 64),
            new THREE.MeshBasicMaterial({
                color: GLOW,
                transparent: true,
                opacity: 0.09,
                side: THREE.DoubleSide,
                depthWrite: false,
            })
        );
        this.sweep.rotation.x = -Math.PI / 2;
        this.part.add(this.sweep);

        this.sweepRing = new THREE.Mesh(
            new THREE.RingGeometry(2.06, 2.12, 64),
            new THREE.MeshBasicMaterial({
                color: GLOW,
                transparent: true,
                opacity: 0.55,
                side: THREE.DoubleSide,
                depthWrite: false,
            })
        );
        this.sweepRing.rotation.x = -Math.PI / 2;
        this.part.add(this.sweepRing);
    }

    /** Nozzle yang mengitari part pada ketinggian lapisan yang sedang dicetak. */
    buildNozzle() {
        this.nozzle = new THREE.Group();
        this.world.add(this.nozzle);

        const body = new THREE.Mesh(
            new THREE.CylinderGeometry(0.16, 0.05, 0.42, 20),
            new THREE.MeshStandardMaterial({ color: 0xe8ded9, roughness: 0.3, metalness: 0.8 })
        );
        body.position.y = 0.21;
        this.nozzle.add(body);

        const tip = new THREE.Mesh(
            new THREE.SphereGeometry(0.05, 16, 12),
            new THREE.MeshBasicMaterial({ color: GLOW })
        );
        this.nozzle.add(tip);
    }

    /**
     * Terapkan kemajuan cetak.
     *
     * @param {number} progress 0 = plate kosong, 1 = part selesai.
     * @returns {number} ketinggian garis cetak setelah dibulatkan ke lapisan.
     */
    apply(progress) {
        const span = this.maxY - this.minY;
        const raw = this.minY + span * progress;
        // Dibulatkan ke kelipatan lapisan supaya tepinya melangkah seperti FDM.
        const height = Math.floor(raw / LAYER) * LAYER;

        // Bidang potong bekerja di ruang dunia, jadi pergeseran grup mesin
        // harus ikut diperhitungkan.
        const worldHeight = height + this.world.position.y;

        this.printedPlane.constant = worldHeight;
        this.pendingPlane.constant = -worldHeight;

        this.sweep.position.y = height;
        this.sweepRing.position.y = height;

        const printing = progress > 0.001 && progress < 0.999;

        this.sweep.visible = printing;
        this.sweepRing.visible = printing;
        this.nozzle.visible = printing;
        this.hotLight.intensity = printing ? 6 : 0;

        return height;
    }

    tick() {
        if (!this.running) {
            this.clock.getDelta();

            return;
        }

        const delta = Math.min(this.clock.getDelta(), 0.05);
        this.elapsed += delta;

        const cycle = PRINT_SECONDS + HOLD_SECONDS + RESET_SECONDS;
        const t = this.elapsed % cycle;

        let progress;

        if (t < PRINT_SECONDS) {
            progress = t / PRINT_SECONDS;
        } else if (t < PRINT_SECONDS + HOLD_SECONDS) {
            progress = 1;
        } else {
            // Surut cepat, lalu siklus berikutnya mulai lagi dari plate kosong.
            progress = 1 - (t - PRINT_SECONDS - HOLD_SECONDS) / RESET_SECONDS;
        }

        const height = this.apply(progress);

        // Part berputar pelan agar seluruh siluetnya sempat terlihat. Hanya
        // sumbu Y yang diputar: memiringkannya akan membuat bidang potong tidak
        // lagi sejajar lapisan, dan ilusi mencetak langsung hilang.
        this.part.rotation.y += delta * 0.32;

        // Nozzle mengitari part mengikuti ketinggian lapisan berjalan.
        const orbit = this.elapsed * 2.4;
        const radius = 1.75;

        this.nozzle.position.set(
            Math.cos(orbit) * radius,
            height + 0.06,
            Math.sin(orbit) * radius
        );
        this.hotLight.position.set(
            this.world.position.x + this.nozzle.position.x,
            this.world.position.y + height + 0.3,
            this.world.position.z + this.nozzle.position.z
        );

        // Kamera mengikuti kursor sedikit — cukup untuk memberi kesan ruang.
        this.pointer.lerp(this.pointerTarget, 0.05);
        this.placeCamera();

        this.renderer.render(this.scene, this.camera);
    }

    /**
     * Letakkan kamera: sudut pandangnya selalu sama, yang berubah hanya jarak
     * (`frameScale`), titik bidik (`shiftX`/`shiftY`), dan geseran kecil
     * mengikuti kursor.
     */
    placeCamera() {
        const d = this.frameScale;
        const distance = 8.2 * d;

        // Ukuran bidang yang tertangkap kamera pada jarak tersebut. Dipakai agar
        // pergeseran mesin bisa dinyatakan sebagai pecahan bingkai, bukan satuan
        // scene yang artinya berubah-ubah mengikuti lebar kanvas.
        const viewHeight = 2 * distance * Math.tan((this.camera.fov * Math.PI) / 360);
        const viewWidth = viewHeight * this.camera.aspect;

        // Mesin digeser ke satu sisi dengan cara membidik titik di sisi
        // seberangnya — karena itu tandanya dibalik.
        const targetX = -this.shiftX * viewWidth;
        const targetY = -this.shiftY * viewHeight;

        this.camera.position.set(
            targetX + this.pointer.x * 1.5,
            targetY + (3.1 - this.pointer.y * 1.1) * d,
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

        /* Kanvas bisa jauh lebih sempit daripada tingginya. Karena field of view
           dihitung terhadap tinggi, mesin akan meluber keluar sisi kiri-kanan
           bila kamera tidak dimundurkan pada kanvas yang sempit. */
        this.frameScale = this.scale * Math.max(1, REFERENCE_ASPECT / aspect);

        this.placeCamera();

        if (reducedMotion) {
            this.renderer.render(this.scene, this.camera);
        }
    }
}
