/**
 * Inti viewer pratinjau 3D yang ringan — hanya menampilkan, tidak menganalisis.
 *
 * Dipakai dua halaman:
 *   - viewer publik /3d-models/{id}/viewer (berkas dari penyimpanan browser);
 *   - viewer Admin/Superadmin pada detail penawaran (berkas dari server).
 *
 * Kontrolnya OrbitControls bawaan three.js:
 *   mouse  — seret kiri memutar, scroll zoom, seret kanan menggeser (pan);
 *   sentuh — satu jari memutar, dua jari mencubit untuk zoom dan menggeser.
 *
 * STL, OBJ, dan 3MF dibaca langsung. STEP/STP ditesselasi lebih dulu oleh
 * OpenCascade (./model-formats.js), kecuali pemanggil sudah memberikan hasil
 * tesselasinya sendiri.
 */
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { STLLoader } from 'three/addons/loaders/STLLoader.js';
import { OBJLoader } from 'three/addons/loaders/OBJLoader.js';
import { ThreeMFLoader } from 'three/addons/loaders/3MFLoader.js';
import { extensionOf, needsTessellation, tessellateStep } from './model-formats';

const MODEL_COLOR = 0x2f6fed;

/** Arah kamera tiap preset sudut pandang (Z ke atas seperti di slicer). */
const VIEWS = {
    iso: new THREE.Vector3(1, 0.8, 1).normalize(),
    front: new THREE.Vector3(0, 0, 1),
    side: new THREE.Vector3(1, 0, 0),
    top: new THREE.Vector3(0, 1, 0.0001).normalize(),
};

export function webglAvailable() {
    try {
        const canvas = document.createElement('canvas');

        return Boolean(window.WebGLRenderingContext && (canvas.getContext('webgl2') || canvas.getContext('webgl')));
    } catch {
        return false;
    }
}

/**
 * Bangun objek three.js dari isi berkas.
 *
 * @param {ArrayBuffer} buffer
 * @param {string} name       nama berkas, untuk menentukan formatnya
 * @param {string|null} format paksa format tertentu (mis. 'stl' untuk hasil tesselasi STEP)
 */
export async function loadObject(buffer, name, format = null) {
    let extension = format ?? extensionOf(name);

    if (!format && needsTessellation(name)) {
        buffer = await tessellateStep(buffer);
        extension = 'stl';
    }

    const material = new THREE.MeshStandardMaterial({
        color: MODEL_COLOR,
        metalness: 0.1,
        roughness: 0.55,
        side: THREE.DoubleSide,
    });

    let object;

    switch (extension) {
        case 'stl': {
            const geometry = new STLLoader().parse(buffer);

            if (!geometry.getAttribute('position')?.count) {
                throw new Error('Geometri STL kosong.');
            }

            geometry.computeVertexNormals();
            object = new THREE.Mesh(geometry, material);
            break;
        }
        case 'obj':
            object = new OBJLoader().parse(new TextDecoder().decode(buffer));
            break;
        case '3mf':
            object = new ThreeMFLoader().parse(buffer);
            break;
        default:
            throw new Error(`Format ${String(extension).toUpperCase()} tidak didukung viewer.`);
    }

    let meshes = 0;

    object.traverse((child) => {
        if (child.isMesh && child.geometry?.getAttribute('position')?.count) {
            if (!child.geometry.getAttribute('normal')) {
                child.geometry.computeVertexNormals();
            }

            child.material = material;
            meshes++;
        }
    });

    if (meshes === 0) {
        throw new Error('Berkas tidak memuat geometri yang dapat digambar.');
    }

    return object;
}

export function countTriangles(object) {
    let total = 0;

    object.traverse((child) => {
        if (!child.isMesh) {
            return;
        }

        const geometry = child.geometry;
        total += geometry.index ? geometry.index.count / 3 : geometry.getAttribute('position').count / 3;
    });

    return Math.round(total);
}

/**
 * Pasang scene pratinjau di dalam `stage`.
 *
 * @returns {{
 *   object: THREE.Object3D, size: THREE.Vector3,
 *   setView(name: string): void, reset(): void, zoom(factor: number): void,
 *   rotateObject(axis: 'x'|'y'|'z', degrees?: number): void, resetObject(): void,
 *   orientation(): {x: number, y: number, z: number, dimensions: THREE.Vector3},
 *   setAutoRotate(on: boolean): void, setWireframe(on: boolean): void
 * }}
 */
export function createPreview(stage, object) {
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(stage.clientWidth, stage.clientHeight);
    renderer.domElement.style.display = 'block';
    stage.appendChild(renderer.domElement);

    const scene = new THREE.Scene();

    // Pusat object dipindah ke titik asal "holder", sehingga putaran object
    // selalu berporos pada pusatnya sendiri. Kotak batas diukur dalam
    // koordinat berkas aslinya (Z ke atas seperti di slicer).
    object.updateMatrixWorld(true);
    const box = new THREE.Box3().setFromObject(object);
    const center = box.getCenter(new THREE.Vector3());
    object.position.sub(center);

    const holder = new THREE.Group();
    holder.add(object);
    scene.add(holder);

    // Berkas memakai Z ke atas, three.js memakai Y ke atas.
    const zUp = new THREE.Quaternion().setFromAxisAngle(new THREE.Vector3(1, 0, 0), -Math.PI / 2);
    // Putaran yang dipilih pengguna, ditumpuk dalam sumbu dunia.
    const userRotation = new THREE.Quaternion();
    const turns = { x: 0, y: 0, z: 0 };

    /** Sumbu slicer (X kanan, Y belakang, Z atas) → sumbu dunia three.js. */
    const AXES = {
        x: new THREE.Vector3(1, 0, 0),
        y: new THREE.Vector3(0, 0, -1),
        z: new THREE.Vector3(0, 1, 0),
    };

    const size = box.getSize(new THREE.Vector3());
    const radius = Math.max(size.length() / 2, 1);

    /** Ukuran object pada orientasi sekarang, dalam sumbu slicer. */
    const dimensions = new THREE.Vector3();

    /**
     * Pasang orientasi lalu dudukkan object di tengah lantai: alasnya tepat di
     * bidang grid, sisi-sisinya berpusat pada sumbu vertikal.
     */
    const place = () => {
        holder.quaternion.copy(userRotation).multiply(zUp);
        holder.position.set(0, 0, 0);
        holder.updateMatrixWorld(true);

        const world = new THREE.Box3().setFromObject(object);
        const middle = world.getCenter(new THREE.Vector3());
        holder.position.set(-middle.x, -world.min.y, -middle.z);

        const extent = world.getSize(new THREE.Vector3());
        dimensions.set(extent.x, extent.z, extent.y);
    };

    place();

    scene.add(new THREE.GridHelper(radius * 4, 20, 0xc9d2e3, 0xe4e9f2));

    scene.add(new THREE.HemisphereLight(0xffffff, 0x8899aa, 1.6));
    const key = new THREE.DirectionalLight(0xffffff, 1.8);
    key.position.set(radius * 2, radius * 3, radius * 2);
    scene.add(key);
    const fill = new THREE.DirectionalLight(0xffffff, 0.6);
    fill.position.set(-radius * 2, radius, -radius * 2);
    scene.add(fill);

    const camera = new THREE.PerspectiveCamera(40, stage.clientWidth / Math.max(stage.clientHeight, 1), radius / 100, radius * 100);

    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.enablePan = true;
    controls.screenSpacePanning = true;
    controls.minDistance = radius * 0.2;
    controls.maxDistance = radius * 20;
    controls.autoRotateSpeed = 1.6;
    // Sentuh: satu jari memutar, dua jari zoom + geser (bawaan OrbitControls).
    controls.touches = { ONE: THREE.TOUCH.ROTATE, TWO: THREE.TOUCH.DOLLY_PAN };

    // Titik bidik: tengah tinggi object pada orientasi sekarang.
    const target = new THREE.Vector3(0, dimensions.z / 2, 0);

    /**
     * Jarak kamera agar seluruh object muat di bingkai. Sudut pandang yang
     * dipakai adalah yang tersempit antara vertikal dan horizontal — pada
     * canvas potret (ponsel) yang horizontal lebih sempit, jadi kamera mundur.
     */
    const fitDistance = () => {
        const vertical = THREE.MathUtils.degToRad(camera.fov);
        const horizontal = 2 * Math.atan(Math.tan(vertical / 2) * camera.aspect);

        // radius adalah bola pembatas object; sedikit ruang tepi supaya tidak mepet.
        return (radius * 1.05) / Math.sin(Math.min(vertical, horizontal) / 2);
    };

    const setView = (name) => {
        // Sisa putaran (inersia damping) dihabiskan lebih dulu — tanpa damping,
        // update() memakainya sekaligus lalu mengosongkannya — baru kamera
        // dipasang pada sudut preset.
        controls.enableDamping = false;
        controls.update();

        controls.target.copy(target);
        camera.position.copy(target).addScaledVector(VIEWS[name] ?? VIEWS.iso, fitDistance());
        camera.lookAt(target);
        controls.update();
        controls.enableDamping = true;
    };

    setView('iso');

    const resize = () => {
        const width = stage.clientWidth;
        const height = stage.clientHeight;

        if (!width || !height) {
            return;
        }

        renderer.setSize(width, height);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
    };

    new ResizeObserver(resize).observe(stage);

    renderer.setAnimationLoop(() => {
        controls.update();
        renderer.render(scene, camera);
    });

    /** Geser titik bidik ke tengah object yang baru, kamera ikut bergeser. */
    const retarget = () => {
        const next = new THREE.Vector3(0, dimensions.z / 2, 0);
        const shift = next.clone().sub(target);

        target.copy(next);
        controls.target.add(shift);
        camera.position.add(shift);
        controls.update();
    };

    return {
        object,
        // Ukuran berkas asli (sumbu berkas), tidak berubah saat object diputar.
        size,
        setView,

        /**
         * Putar object 90° (atau `degrees`) pada sumbu slicer X, Y, atau Z.
         * Hanya tampilan — berkas aslinya tidak berubah.
         */
        rotateObject(axis, degrees = 90) {
            if (!AXES[axis]) {
                return;
            }

            userRotation.premultiply(
                new THREE.Quaternion().setFromAxisAngle(AXES[axis], THREE.MathUtils.degToRad(degrees))
            );
            turns[axis] = (((turns[axis] + degrees) % 360) + 360) % 360;

            place();
            retarget();
        },

        /** Kembalikan object ke orientasi berkas aslinya. */
        resetObject() {
            userRotation.identity();
            turns.x = turns.y = turns.z = 0;

            place();
            retarget();
        },

        /** Sudut putaran per sumbu (0/90/180/270) dan ukuran pada orientasi sekarang. */
        orientation() {
            return { ...turns, dimensions: dimensions.clone() };
        },

        /** Kembali ke sudut pandang awal, jarak awal, dan tanpa geseran. */
        reset() {
            setView('iso');
        },

        /** factor < 1 mendekat, > 1 menjauh; tetap di dalam batas jarak kontrol. */
        zoom(factor) {
            const offset = camera.position.clone().sub(controls.target);
            const length = THREE.MathUtils.clamp(offset.length() * factor, controls.minDistance, controls.maxDistance);

            camera.position.copy(controls.target).add(offset.setLength(length));
            controls.update();
        },

        setAutoRotate(on) {
            controls.autoRotate = on;
        },

        setWireframe(on) {
            object.traverse((child) => {
                if (child.isMesh) {
                    child.material.wireframe = on;
                }
            });
        },
    };
}
