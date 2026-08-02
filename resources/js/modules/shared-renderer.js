import * as THREE from 'three';

/**
 * Satu WebGLRenderer yang dipakai bersama oleh banyak viewer.
 *
 * Sejak satu model dicetak pada satu mesin, halaman dapat menampilkan sepuluh
 * viewer sekaligus. Membuat sepuluh WebGLRenderer berarti sepuluh context WebGL,
 * sedangkan browser hanya mengizinkan belasan context sebelum mulai mencabut
 * yang paling lama — viewer paling atas akan mendadak kosong.
 *
 * Karena itu dipakai pola "multiple elements" dari Three.js: satu renderer
 * tersembunyi menggambar setiap scene bergantian, lalu hasilnya disalin ke
 * kanvas 2D milik masing-masing card. Satu context untuk berapa pun viewer.
 *
 * Agar tetap ringan, hanya viewer yang benar-benar terlihat di layar dan
 * memang perlu digambar ulang yang dirender pada setiap frame.
 */
export default class SharedRenderer {
    constructor() {
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setPixelRatio(1); // ukuran diatur sendiri dalam piksel perangkat
        this.renderer.setScissorTest(true);
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.05;
        this.renderer.setSize(1, 1, false);

        this.pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
        this.width = 1;
        this.height = 1;

        /** @type {Set<object>} viewer yang terdaftar */
        this.views = new Set();

        // Viewer yang tergulung keluar layar tidak perlu digambar sama sekali.
        this.observer = 'IntersectionObserver' in window
            ? new IntersectionObserver(
                (entries) => entries.forEach((entry) => {
                    const view = [...this.views].find((candidate) => candidate.canvas === entry.target);

                    if (view) {
                        view.visible = entry.isIntersecting;
                        view.needsRender = view.needsRender || entry.isIntersecting;
                    }
                }),
                { rootMargin: '200px' }
            )
            : null;

        this.renderer.setAnimationLoop(() => this.tick());
    }

    /**
     * Daftarkan satu viewer.
     *
     * @param {{canvas: HTMLCanvasElement, scene: THREE.Scene, camera: THREE.Camera,
     *          controls?: object, isAnimating?: () => boolean}} view
     */
    register(view) {
        view.context = view.canvas.getContext('2d');
        view.visible = true;
        view.needsRender = true;

        this.views.add(view);
        this.observer?.observe(view.canvas);

        return view;
    }

    unregister(view) {
        if (!view) {
            return;
        }

        this.observer?.unobserve(view.canvas);
        this.views.delete(view);
    }

    /** Tandai satu viewer perlu digambar ulang pada frame berikutnya. */
    invalidate(view) {
        if (view) {
            view.needsRender = true;
        }
    }

    invalidateAll() {
        this.views.forEach((view) => {
            view.needsRender = true;
        });
    }

    /**
     * Pastikan buffer renderer cukup besar untuk viewer terbesar.
     * Ukurannya hanya ditambah, tidak pernah dikecilkan, agar buffer tidak
     * dialokasikan ulang setiap kali jendela berubah sedikit.
     */
    ensureSize(width, height) {
        if (width <= this.width && height <= this.height) {
            return;
        }

        this.width = Math.max(this.width, width);
        this.height = Math.max(this.height, height);
        this.renderer.setSize(this.width, this.height, false);
    }

    tick() {
        this.views.forEach((view) => {
            if (!view.visible || !view.canvas.isConnected) {
                return;
            }

            // OrbitControls dengan damping perlu terus diperbarui selama
            // gerakannya belum berhenti; update() memberi tahu bila kameranya
            // benar-benar berubah.
            const moved = view.controls?.update() === true;
            const animating = view.isAnimating?.() === true;

            if (!moved && !animating && !view.needsRender) {
                return;
            }

            this.draw(view);
            view.needsRender = false;
        });
    }

    /** Gambar satu viewer lalu salin hasilnya ke kanvas card. */
    draw(view) {
        const rect = view.canvas.getBoundingClientRect();
        const width = Math.max(1, Math.floor(rect.width * this.pixelRatio));
        const height = Math.max(1, Math.floor(rect.height * this.pixelRatio));

        if (width <= 1 || height <= 1) {
            return;
        }

        // Kanvas tujuan mengikuti ukuran tampilannya, dalam piksel perangkat.
        if (view.canvas.width !== width || view.canvas.height !== height) {
            view.canvas.width = width;
            view.canvas.height = height;
        }

        this.ensureSize(width, height);

        if (view.camera.isPerspectiveCamera) {
            const aspect = width / height;

            if (view.camera.aspect !== aspect) {
                view.camera.aspect = aspect;
                view.camera.updateProjectionMatrix();
            }
        }

        // Sumbu Y WebGL dihitung dari bawah, jadi viewport digeser ke atas agar
        // hasilnya menempati sudut kiri-atas buffer dan mudah disalin.
        const top = this.height - height;

        this.renderer.setViewport(0, top, width, height);
        this.renderer.setScissor(0, top, width, height);
        this.renderer.render(view.scene, view.camera);

        view.context.clearRect(0, 0, width, height);
        view.context.drawImage(this.renderer.domElement, 0, 0, width, height, 0, 0, width, height);
    }

    dispose() {
        this.renderer.setAnimationLoop(null);
        this.observer?.disconnect();
        this.views.clear();
        this.renderer.dispose();
    }
}
