import * as THREE from 'three';

/**
 * Gambar pratinjau (thumbnail) yang benar-benar dibuat dari model 3D pengguna.
 *
 * Bukan ikon file dan bukan gambar bawaan: objek yang sama persis dengan yang
 * tampil di viewer difoto sekali dari sudut tiga perempat, lalu hasilnya
 * disimpan sebagai data URL. Model kursi menghasilkan gambar kursi, gear
 * menghasilkan gear.
 *
 * Renderer khusus thumbnail dibuat satu kali dan dipakai ulang untuk semua
 * model — satu context WebGL tambahan, tidak peduli berapa banyak file yang
 * diunggah.
 */

const WIDTH = 640;
const HEIGHT = 480;
const BACKGROUND = 0xf3eeec;

let renderer = null;

function getRenderer() {
    if (renderer) {
        return renderer;
    }

    renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
    renderer.setPixelRatio(1);
    renderer.setSize(WIDTH, HEIGHT, false);
    renderer.setClearColor(BACKGROUND, 1);
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;

    return renderer;
}

/**
 * Ambil gambar satu objek 3D.
 *
 * Objeknya dipinjam sebentar ke scene khusus thumbnail lalu dikembalikan ke
 * induknya semula, sehingga viewer yang sedang berjalan tidak terganggu.
 *
 * @param {THREE.Object3D} object model yang akan difoto
 * @returns {string} data URL PNG, atau string kosong bila gagal
 */
export function renderThumbnail(object) {
    if (!object) {
        return '';
    }

    const parent = object.parent;
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(BACKGROUND);

    scene.add(new THREE.HemisphereLight(0xffffff, 0xd9cfcb, 1.2));

    const key = new THREE.DirectionalLight(0xffffff, 2.0);
    key.position.set(1, 1.5, 1.2);
    scene.add(key);

    const fill = new THREE.DirectionalLight(0xffe6de, 0.65);
    fill.position.set(-1.4, 0.6, -0.9);
    scene.add(fill);

    try {
        scene.add(object);

        const box = new THREE.Box3().setFromObject(object);

        if (box.isEmpty()) {
            return '';
        }

        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        const radius = Math.max(size.length() / 2, 0.001);

        const camera = new THREE.PerspectiveCamera(38, WIDTH / HEIGHT, 0.1, radius * 100);
        // Sudut tiga perempat dari atas — bentuk objek paling mudah dikenali.
        const direction = new THREE.Vector3(1, 0.72, 1).normalize();
        const distance = radius / Math.sin((camera.fov * Math.PI) / 360) * 1.12;

        camera.position.copy(center).addScaledVector(direction, distance);
        camera.lookAt(center);
        camera.updateProjectionMatrix();

        const engine = getRenderer();
        engine.render(scene, camera);

        return engine.domElement.toDataURL('image/png');
    } catch (error) {
        console.error(error);

        return '';
    } finally {
        // Kembalikan objek ke tempat asalnya apa pun yang terjadi.
        parent ? parent.add(object) : scene.remove(object);
    }
}
