/**
 * Halaman detail Industri.
 *
 * 1. Galeri "Contoh Hasilnya": setiap kartu memuat <canvas data-industry-part>
 *    yang digambari part 3D prosedural (modules/industry-parts.js). Seluruh
 *    kartu memakai satu WebGLRenderer bersama (modules/shared-renderer.js)
 *    sehingga puluhan kartu tetap hanya memakai satu context WebGL.
 * 2. Viewer: kartu yang diklik membuka <dialog data-industry-viewer> berisi
 *    model yang bisa diputar dan di-zoom (OrbitControls).
 * 3. Daftar isi sticky: tautan bagian yang sedang dibaca ditandai aktif.
 * 4. Hero: <canvas data-industry-hero> menampilkan part khas industri yang
 *    berputar pelan di sisi kanan jumbotron (layar besar saja).
 *
 * Setiap kanvas/tombol dapat membawa data-industry-file: URL file STL/OBJ asli
 * (app/Support/IndustryModel.php). Bila ada, file itulah yang dirender;
 * bila gagal dimuat atau tidak ada, dipakai model buatan program.
 *
 * Three.js baru diunduh ketika galerinya mendekati layar atau viewer dibuka;
 * tanpa WebGL kartu tetap menampilkan latar gradien dan judulnya.
 */

function webglAvailable() {
    try {
        const probe = document.createElement('canvas');

        return Boolean(window.WebGLRenderingContext && (probe.getContext('webgl2') || probe.getContext('webgl')));
    } catch {
        return false;
    }
}

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ------------------------------------------------------------ engine --- */

let enginePromise = null;

/** Muat Three.js beserta modul part sekali saja untuk galeri dan viewer. */
function loadEngine() {
    enginePromise ??= Promise.all([
        import('three'),
        import('three/addons/environments/RoomEnvironment.js'),
        import('three/addons/controls/OrbitControls.js'),
        import('./modules/shared-renderer'),
        import('./modules/industry-parts'),
    ]).then(([THREE, { RoomEnvironment }, { OrbitControls }, { default: SharedRenderer }, parts]) => ({
        THREE,
        RoomEnvironment,
        OrbitControls,
        SharedRenderer,
        buildPart: parts.buildPart,
        materials: parts.createMaterials(),
    }));

    return enginePromise;
}

function createEnvironment(engine, renderer) {
    const pmrem = new engine.THREE.PMREMGenerator(renderer);
    const texture = pmrem.fromScene(new engine.RoomEnvironment(), 0.04).texture;
    pmrem.dispose();

    return texture;
}

/* -------------------------------------------------- file 3D asli --- */

const fileCache = new Map();

/**
 * Muat geometri dari file STL/OBJ sekali per URL. Normal permukaannya
 * dihitung ulang dengan sudut lipatan (crease) supaya bidang lengkung
 * tampak mulus sementara tepi tajam tetap tegas, seperti part sungguhan.
 */
function loadFileGeometries(engine, url) {
    if (!fileCache.has(url)) {
        fileCache.set(url, (async () => {
            const { THREE } = engine;
            const extension = url.split(/[?#]/)[0].split('.').pop().toLowerCase();
            const { toCreasedNormals } = await import('three/addons/utils/BufferGeometryUtils.js');
            const geometries = [];

            if (extension === 'stl') {
                const { STLLoader } = await import('three/addons/loaders/STLLoader.js');
                const geometry = await new STLLoader().loadAsync(url);
                // File STL dari CAD umumnya memakai sumbu Z ke atas.
                geometry.rotateX(-Math.PI / 2);
                geometries.push(geometry);
            } else if (extension === 'obj') {
                const { OBJLoader } = await import('three/addons/loaders/OBJLoader.js');
                const object = await new OBJLoader().loadAsync(url);
                object.updateMatrixWorld(true);
                object.traverse((child) => {
                    if (child.isMesh) {
                        geometries.push(child.geometry.clone().applyMatrix4(child.matrixWorld));
                    }
                });
            } else {
                throw new Error(`Format file 3D tidak didukung: ${extension}`);
            }

            return geometries.map((geometry) => {
                ['normal', 'uv', 'color'].forEach((name) => geometry.deleteAttribute(name));

                return toCreasedNormals(geometry, THREE.MathUtils.degToRad(35));
            });
        })());
    }

    return fileCache.get(url);
}

/**
 * Part untuk satu kanvas: file asli bila ada, selain itu model buatan
 * program. Kegagalan memuat file tidak pernah membuat kartu kosong.
 */
async function resolvePart(engine, key, file) {
    if (file) {
        try {
            const geometries = await loadFileGeometries(engine, file);
            const group = new engine.THREE.Group();

            geometries.forEach((geometry) => {
                const mesh = new engine.THREE.Mesh(geometry, engine.materials.part);
                mesh.castShadow = true;
                mesh.receiveShadow = true;
                group.add(mesh);
            });

            if (group.children.length) {
                return group;
            }
        } catch (error) {
            console.warn('[industry-gallery] file 3D gagal dimuat, memakai model bawaan:', file, error);
        }
    }

    return engine.buildPart(key, engine.materials);
}

/**
 * Scene studio untuk satu part: part dinormalkan (dimensi terbesar 2.2,
 * berdiri di lantai), lantai penerima bayangan, dan tiga lampu.
 */
function createStage(engine, part, environment) {
    const { THREE } = engine;
    const scene = new THREE.Scene();
    scene.environment = environment;
    scene.environmentIntensity = 0.45;

    const bounds = new THREE.Box3().setFromObject(part);
    const size = bounds.getSize(new THREE.Vector3());
    part.scale.setScalar(2.2 / Math.max(size.x, size.y, size.z));
    bounds.setFromObject(part);
    const center = bounds.getCenter(new THREE.Vector3());
    part.position.sub(new THREE.Vector3(center.x, bounds.min.y, center.z));

    // Pivot terpisah supaya rotasi berporos di tengah part.
    const pivot = new THREE.Group();
    pivot.add(part);
    pivot.rotation.y = -0.45;
    scene.add(pivot);

    const height = bounds.max.y - bounds.min.y;
    const radius = bounds.getBoundingSphere(new THREE.Sphere()).radius;

    // Lantai yang hanya menerima bayangan; latar merahnya dari CSS.
    const floor = new THREE.Mesh(new THREE.PlaneGeometry(12, 12), new THREE.ShadowMaterial({ opacity: 0.4 }));
    floor.rotation.x = -Math.PI / 2;
    floor.receiveShadow = true;
    scene.add(floor);

    scene.add(new THREE.HemisphereLight(0xfff1ec, 0x5a1a12, 0.35));

    const keyLight = new THREE.DirectionalLight(0xffffff, 2.6);
    keyLight.position.set(2.5, 5, 3);
    keyLight.castShadow = true;
    keyLight.shadow.mapSize.set(1024, 1024);
    keyLight.shadow.radius = 6;
    keyLight.shadow.bias = -0.0005;
    Object.assign(keyLight.shadow.camera, { left: -3, right: 3, top: 3, bottom: -3, near: 0.5, far: 15 });
    scene.add(keyLight);

    const rim = new THREE.DirectionalLight(0xffc9bd, 1.2);
    rim.position.set(-3, 2.5, -3);
    scene.add(rim);

    return { scene, pivot, height, radius };
}

/* ----------------------------------------------------------- galeri --- */

async function startGallery(canvases) {
    const engine = await loadEngine();
    const { THREE } = engine;

    const shared = new engine.SharedRenderer();
    shared.renderer.shadowMap.enabled = true;
    shared.renderer.shadowMap.type = THREE.PCFShadowMap;
    const environment = createEnvironment(engine, shared.renderer);

    // Window resize dipasang sekali; kanvas yang memuat file asli menyusul
    // setelah filenya selesai diunduh.
    window.addEventListener('resize', () => shared.invalidateAll(), { passive: true });

    await Promise.all(canvases.map(async (canvas) => {
        const part = await resolvePart(engine, canvas.dataset.industryPart, canvas.dataset.industryFile);
        const { scene, pivot, height, radius } = createStage(engine, part, environment);

        // Kamera 3/4 dari atas; titik bidiknya diturunkan sedikit agar part
        // tampil di bagian atas kartu, di atas judulnya. Part jangkung butuh
        // jarak lebih supaya tidak menabrak judul.
        // data-industry-framing="center": part di tengah kartu (kartu tanpa
        // judul di atas gambar, mis. ringkasan di halaman /industri).
        const centered = canvas.dataset.industryFraming === 'center';
        const camera = new THREE.PerspectiveCamera(26, 4 / 3, 0.1, 50);
        const fit = Math.max(radius, height * 0.85);
        const distance = (fit / Math.sin(THREE.MathUtils.degToRad(13))) * (centered ? 1.2 : 1.12);
        const target = new THREE.Vector3(0, height / 2 - (centered ? 0 : fit * 0.32), 0);
        camera.position.copy(target).add(new THREE.Vector3(0, Math.sin(0.42), Math.cos(0.42)).multiplyScalar(distance));
        camera.lookAt(target);

        let hovered = false;
        let last = 0;
        const card = canvas.closest('[data-industry-card]') || canvas;

        if (!reduceMotion) {
            card.addEventListener('pointerenter', () => {
                hovered = true;
                last = performance.now();
            });
            card.addEventListener('pointerleave', () => {
                hovered = false;
            });
        }

        shared.register({
            canvas,
            scene,
            camera,
            isAnimating: () => {
                if (!hovered) {
                    return false;
                }

                const now = performance.now();
                pivot.rotation.y += Math.min(now - last, 50) * 0.0006;
                last = now;

                return true;
            },
        });

        canvas.classList.add('is-ready');
    }));
}

/* ----------------------------------------------------------- viewer --- */

function initViewer() {
    const dialog = document.querySelector('[data-industry-viewer]');
    const triggers = document.querySelectorAll('[data-industry-open]');

    if (!dialog || !triggers.length || typeof dialog.showModal !== 'function') {
        return;
    }

    const canvas = dialog.querySelector('[data-industry-viewer-canvas]');
    const title = dialog.querySelector('[data-industry-viewer-title]');
    // Renderer dan environment dibuat sekali lalu dipakai ulang; membuat
    // WebGLRenderer baru di kanvas yang sama setelah dispose() menghasilkan
    // kanvas kosong. Yang diganti setiap kali dibuka hanya scene & kontrolnya.
    let viewer = null;
    let session = null;
    let openToken = 0;

    const stop = () => {
        if (!session) {
            return;
        }

        viewer.renderer.setAnimationLoop(null);
        session.controls.dispose();
        session.resizeObserver.disconnect();
        session = null;
    };

    const open = async (key, label, file) => {
        const token = ++openToken;
        title.textContent = label;

        if (!dialog.open) {
            dialog.showModal();
        }

        const engine = await loadEngine();
        const { THREE } = engine;

        // Viewer sudah ditutup, atau model lain dibuka, sebelum Three.js
        // selesai dimuat.
        if (!dialog.open || token !== openToken) {
            return;
        }

        stop();

        if (!viewer) {
            const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
            renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
            renderer.toneMapping = THREE.ACESFilmicToneMapping;
            renderer.toneMappingExposure = 1.05;
            renderer.shadowMap.enabled = true;
            renderer.shadowMap.type = THREE.PCFShadowMap;

            viewer = { renderer, environment: createEnvironment(engine, renderer) };
        }

        const { renderer } = viewer;
        const part = await resolvePart(engine, key, file);

        if (!dialog.open || token !== openToken) {
            return;
        }

        const { scene, height, radius } = createStage(engine, part, viewer.environment);

        const camera = new THREE.PerspectiveCamera(30, 1, 0.1, 60);
        const distance = (Math.max(radius, height * 0.7) / Math.sin(THREE.MathUtils.degToRad(15))) * 1.1;
        const target = new THREE.Vector3(0, height / 2, 0);
        camera.position.copy(target).add(new THREE.Vector3(0, Math.sin(0.4), Math.cos(0.4)).multiplyScalar(distance));

        const controls = new engine.OrbitControls(camera, canvas);
        controls.target.copy(target);
        controls.enableDamping = true;
        controls.enablePan = false;
        controls.minDistance = distance * 0.45;
        controls.maxDistance = distance * 1.8;
        controls.maxPolarAngle = Math.PI / 2 - 0.05;
        controls.autoRotate = !reduceMotion;
        controls.autoRotateSpeed = 1.2;
        controls.addEventListener('start', () => {
            controls.autoRotate = false;
        });
        controls.update();

        const resize = () => {
            const { clientWidth: width, clientHeight: heightPx } = canvas;

            if (!width || !heightPx) {
                return;
            }

            renderer.setSize(width, heightPx, false);
            camera.aspect = width / heightPx;
            camera.updateProjectionMatrix();
        };

        const resizeObserver = new ResizeObserver(resize);
        resizeObserver.observe(canvas);
        resize();

        renderer.setAnimationLoop(() => {
            controls.update();
            renderer.render(scene, camera);
        });

        session = { controls, resizeObserver };
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            open(trigger.dataset.industryOpen, trigger.dataset.industryTitle || '', trigger.dataset.industryFile).catch((error) => {
                console.warn('[industry-viewer]', error);
                dialog.close();
            });
        });
    });

    dialog.querySelector('[data-industry-viewer-close]')?.addEventListener('click', () => dialog.close());

    // Klik pada backdrop (di luar panel) menutup viewer.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    // Event close dijalankan belakangan; bila viewer sudah dibuka lagi untuk
    // model lain, sesi barunya jangan ikut dihentikan.
    dialog.addEventListener('close', () => {
        if (!dialog.open) {
            stop();
        }
    });
}

/* ------------------------------------------------------- daftar isi --- */

function initToc() {
    const links = [...document.querySelectorAll('[data-toc-link]')];

    if (!links.length || !('IntersectionObserver' in window)) {
        return;
    }

    const sections = links
        .map((link) => document.getElementById(link.dataset.tocLink))
        .filter(Boolean);

    const setActive = (id) => {
        links.forEach((link) => {
            link.classList.toggle('is-active', link.dataset.tocLink === id);
        });
    };

    // Bagian yang aktif adalah bagian terakhir yang bagian atasnya sudah
    // melewati sepertiga atas layar.
    const update = () => {
        const line = window.innerHeight / 3;
        let current = sections[0]?.id;

        sections.forEach((section) => {
            if (section.getBoundingClientRect().top <= line) {
                current = section.id;
            }
        });

        setActive(current);
    };

    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(() => {
                update();
                ticking = false;
            });
        }
    }, { passive: true });

    update();
}

/* ------------------------------------------------------------- hero --- */

/**
 * Part khas industri di sisi kanan hero. Kanvasnya disembunyikan di layar
 * kecil; selama lebarnya nol, Three.js tidak dimuat sama sekali.
 */
function initHero() {
    const canvas = document.querySelector('canvas[data-industry-hero]');

    if (!canvas) {
        return;
    }

    let started = false;

    const start = async () => {
        started = true;

        const engine = await loadEngine();
        const { THREE } = engine;

        const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.1;
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFShadowMap;

        const part = await resolvePart(engine, canvas.dataset.industryHero, canvas.dataset.industryFile);
        const { scene, pivot, height, radius } = createStage(engine, part, createEnvironment(engine, renderer));

        // Cahaya merah dari belakang supaya part menyatu dengan nuansa brand
        // di atas latar hero yang gelap.
        const glow = new THREE.DirectionalLight(0xc74d38, 3);
        glow.position.set(-2.5, 1.5, -3.5);
        scene.add(glow);

        const camera = new THREE.PerspectiveCamera(28, 1, 0.1, 60);
        const target = new THREE.Vector3(0, height / 2, 0);
        const halfFov = THREE.MathUtils.degToRad(14);

        // Jarak kamera memenuhi batas tinggi DAN lebar kanvas, supaya part
        // yang lebar (mis. dari file STL asli) tidak terpotong di kanvas tegak.
        const placeCamera = (aspect) => {
            const vertical = Math.max(radius, height * 0.75) / Math.sin(halfFov);
            const horizontal = radius / Math.sin(Math.atan(Math.tan(halfFov) * aspect));
            const distance = Math.max(vertical * 1.15, horizontal * 0.98);
            camera.position.copy(target).add(new THREE.Vector3(0, Math.sin(0.32), Math.cos(0.32)).multiplyScalar(distance));
            camera.lookAt(target);
        };

        placeCamera(1);

        const resize = () => {
            const { clientWidth: width, clientHeight: heightPx } = canvas;

            if (!width || !heightPx) {
                return;
            }

            renderer.setSize(width, heightPx, false);
            camera.aspect = width / heightPx;
            camera.updateProjectionMatrix();
            placeCamera(camera.aspect);
        };

        new ResizeObserver(resize).observe(canvas);
        resize();

        // Hanya digambar selama hero terlihat di layar.
        let visible = true;

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(([entry]) => {
                visible = entry.isIntersecting;
            }).observe(canvas);
        }

        const clock = new THREE.Clock();
        const baseY = pivot.position.y;

        renderer.setAnimationLoop(() => {
            const delta = clock.getDelta();

            if (!visible) {
                return;
            }

            if (!reduceMotion) {
                pivot.rotation.y += delta * 0.35;
                pivot.position.y = baseY + Math.sin(clock.elapsedTime * 1.2) * 0.04;
            }

            renderer.render(scene, camera);
        });

        canvas.classList.add('is-ready');
    };

    const tryStart = () => {
        if (!started && canvas.clientWidth > 0) {
            start().catch((error) => console.warn('[industry-hero]', error));
        }
    };

    tryStart();

    // Jendela sempit: tunggu sampai kanvasnya benar-benar tampil.
    if (!started && 'ResizeObserver' in window) {
        const observer = new ResizeObserver(() => {
            tryStart();

            if (started) {
                observer.disconnect();
            }
        });

        observer.observe(canvas);
    }
}

/* ------------------------------------------------------------- boot --- */

initToc();

if (webglAvailable()) {
    initHero();
    initViewer();

    const canvases = [...document.querySelectorAll('canvas[data-industry-part]')];

    if (canvases.length) {
        const run = () => startGallery(canvases).catch((error) => console.warn('[industry-gallery]', error));

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    observer.disconnect();
                    run();
                }
            }, { rootMargin: '400px' });

            canvases.forEach((canvas) => observer.observe(canvas));
        } else {
            run();
        }
    }
} else {
    // Tanpa WebGL kartu tetap tampil, hanya saja tidak bisa diputar.
    document.querySelectorAll('[data-industry-open]').forEach((trigger) => {
        trigger.remove();
    });
}
