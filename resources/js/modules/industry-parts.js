import * as THREE from 'three';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';

/**
 * Model 3D prosedural untuk galeri "Contoh Hasilnya" di halaman Industri.
 *
 * Setiap fungsi membangun satu part contoh dari primitif Three.js (extrude,
 * lathe, tube, rounded box). Ukurannya bebas: industry-gallery.js menormalkan
 * hasilnya agar pas di kartu. Kunci objek ini dipakai sebagai `model` di
 * config/industries.php.
 */

/* ----------------------------------------------------------- material --- */

/**
 * Garis layer hasil cetak: permukaan diberi alur halus searah sumbu Y dunia,
 * seperti part FDM/SLA sungguhan. Alurnya memudar otomatis ketika terlalu
 * rapat di layar (part kecil/jauh) agar tidak muncul pola moire.
 */
function withLayerLines(material, { period = 0.022, strength = 0.22 } = {}) {
    material.onBeforeCompile = (shader) => {
        shader.uniforms.layerPeriod = { value: period };
        shader.uniforms.layerStrength = { value: strength };

        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', `#include <common>
                varying vec3 vLayerWorld;`)
            .replace('#include <project_vertex>', `#include <project_vertex>
                vLayerWorld = (modelMatrix * vec4(transformed, 1.0)).xyz;`);

        shader.fragmentShader = shader.fragmentShader
            .replace('#include <common>', `#include <common>
                varying vec3 vLayerWorld;
                uniform float layerPeriod;
                uniform float layerStrength;`)
            .replace('#include <normal_fragment_maps>', `#include <normal_fragment_maps>
                {
                    float phase = vLayerWorld.y / layerPeriod;
                    float density = fwidth(phase);
                    float fade = 1.0 - smoothstep(0.18, 0.45, density);
                    vec3 upView = normalize((viewMatrix * vec4(0.0, 1.0, 0.0, 0.0)).xyz);
                    float ridge = sin(phase * 6.2831853);
                    normal = normalize(normal + upView * ridge * layerStrength * fade);
                }`);
    };

    material.customProgramCacheKey = () => `layer-lines-${period}-${strength}`;

    return material;
}

export function createMaterials() {
    // Nylon / resin putih hasil cetak: doff dengan sedikit kilap.
    const printed = {
        color: 0xe9e2dd,
        roughness: 0.62,
        metalness: 0,
        clearcoat: 0.1,
        clearcoatRoughness: 0.75,
        sheen: 0.3,
        sheenRoughness: 0.8,
    };

    return {
        part: withLayerLines(new THREE.MeshPhysicalMaterial(printed)),
        // Sama dengan `part`, untuk cangkang tipis yang terlihat dari dalam.
        partDouble: withLayerLines(new THREE.MeshPhysicalMaterial({ ...printed, side: THREE.DoubleSide })),
        // Logam hasil SLM setelah finishing.
        metal: new THREE.MeshPhysicalMaterial({
            color: 0xd9d6d4,
            roughness: 0.3,
            metalness: 1,
        }),
        // Aksen gelap: kaca layar, lensa, ban, celah ventilasi.
        dark: new THREE.MeshPhysicalMaterial({
            color: 0x2a2523,
            roughness: 0.35,
            metalness: 0.1,
            clearcoat: 0.6,
        }),
    };
}

let M = null;

/* ------------------------------------------------------------ helpers --- */

function mesh(geometry, material = M.part) {
    const object = new THREE.Mesh(geometry, material);
    object.castShadow = true;
    object.receiveShadow = true;

    return object;
}

function at(object, x = 0, y = 0, z = 0, rx = 0, ry = 0, rz = 0) {
    object.position.set(x, y, z);
    object.rotation.set(rx, ry, rz);

    return object;
}

function scaled(object, x, y, z) {
    object.scale.set(x, y, z);

    return object;
}

function group(...children) {
    const result = new THREE.Group();
    children.flat().forEach((child) => result.add(child));

    return result;
}

const box = (w, h, d, r = 0.04) => new RoundedBoxGeometry(w, h, d, 4, Math.min(r, w / 2, h / 2, d / 2));
const cyl = (rt, rb, h, seg = 48) => new THREE.CylinderGeometry(rt, rb, h, seg);
const sphere = (r) => new THREE.SphereGeometry(r, 48, 32);
const torus = (r, t, arc = Math.PI * 2) => new THREE.TorusGeometry(r, t, 24, 72, arc);

function lathe(points, segments = 72) {
    return new THREE.LatheGeometry(points.map(([x, y]) => new THREE.Vector2(x, y)), segments);
}

function tube(points, radius, segments = 80) {
    const curve = new THREE.CatmullRomCurve3(points.map((p) => new THREE.Vector3(...p)));

    return new THREE.TubeGeometry(curve, segments, radius, 32, false);
}

function rrect(w, h, r, path = new THREE.Shape(), cx = 0, cy = 0) {
    const x = cx - w / 2;
    const y = cy - h / 2;

    path.moveTo(x + r, y);
    path.lineTo(x + w - r, y);
    path.quadraticCurveTo(x + w, y, x + w, y + r);
    path.lineTo(x + w, y + h - r);
    path.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    path.lineTo(x + r, y + h);
    path.quadraticCurveTo(x, y + h, x, y + h - r);
    path.lineTo(x, y + r);
    path.quadraticCurveTo(x, y, x + r, y);

    return path;
}

const hole = (x, y, r) => new THREE.Path().absarc(x, y, r, 0, Math.PI * 2, true);

function circleShape(r) {
    return new THREE.Shape().absarc(0, 0, r, 0, Math.PI * 2, false);
}

function ringShape(outer, inner) {
    const shape = circleShape(outer);
    shape.holes.push(hole(0, 0, inner));

    return shape;
}

/** Extrude yang berpusat di z = 0. */
function extrude(shape, depth, bevel = 0.025) {
    const geometry = new THREE.ExtrudeGeometry(shape, {
        depth,
        bevelEnabled: bevel > 0,
        bevelSize: bevel,
        bevelThickness: bevel,
        bevelSegments: 3,
        curveSegments: 40,
    });
    geometry.translate(0, 0, -depth / 2);

    return geometry;
}

/** Extrude yang direbahkan: bentuk 2D di bidang XZ, tebal ke arah +Y. */
function plate(shape, thickness, bevel = 0.025) {
    const geometry = extrude(shape, thickness, bevel);
    geometry.rotateX(-Math.PI / 2);
    geometry.translate(0, thickness / 2, 0);

    return geometry;
}

function gearShape(teeth, outer, root, bore = 0) {
    const shape = new THREE.Shape();
    const step = (Math.PI * 2) / teeth;

    for (let i = 0; i < teeth; i++) {
        const a = i * step;
        [[root, a], [outer, a + step * 0.18], [outer, a + step * 0.42], [root, a + step * 0.6]].forEach(([r, t], j) => {
            const x = r * Math.cos(t);
            const y = r * Math.sin(t);
            i === 0 && j === 0 ? shape.moveTo(x, y) : shape.lineTo(x, y);
        });
    }

    shape.closePath();

    if (bore) {
        shape.holes.push(hole(0, 0, bore));
    }

    return shape;
}

/** Bilangan acak berbenih supaya bentuk acak selalu sama di setiap kunjungan. */
function seeded(seed) {
    let value = seed;

    return () => {
        value = (value * 16807) % 2147483647;

        return (value - 1) / 2147483646;
    };
}

/** Pipa berongga (dinding tebal) dari profil lathe. */
function hollowTube(outer, inner, height) {
    return lathe([[inner, 0], [outer, 0], [outer, height], [inner, height], [inner, 0]]);
}

/* ------------------------------------------------- otomotif & marine --- */

function elbow() {
    const R = 1.2;
    const points = [];

    for (let i = 0; i <= 16; i++) {
        const t = Math.PI - (i / 16) * (Math.PI / 2);
        points.push([R + R * Math.cos(t), R * Math.sin(t) + 0.12, 0]);
    }

    const ribs = [0.3, 0.55, 0.8].map((k) => {
        const t = Math.PI - k * (Math.PI / 2);
        const rib = mesh(torus(0.43, 0.035));
        rib.position.set(R + R * Math.cos(t), R * Math.sin(t) + 0.12, 0);
        rib.lookAt(rib.position.clone().add(new THREE.Vector3(-Math.sin(t), Math.cos(t), 0)));

        return rib;
    });

    return group(
        mesh(tube(points, 0.4)),
        at(mesh(lathe([[0.36, 0], [0.62, 0], [0.62, 0.1], [0.4, 0.16], [0.36, 0.16]])), 0, 0, 0),
        at(mesh(hollowTube(0.48, 0.36, 0.3)), R - 0.05, R + 0.12, 0, 0, 0, -Math.PI / 2),
        ribs,
    );
}

function clampBracket() {
    const base = rrect(1.8, 1.1, 0.12);
    base.holes.push(rrect(0.5, 0.16, 0.08, new THREE.Path(), -0.5, 0.25), rrect(0.5, 0.16, 0.08, new THREE.Path(), 0.5, 0.25));

    const clamp = ringShape(0.42, 0.26);

    return group(
        mesh(plate(base, 0.12)),
        at(mesh(box(0.7, 1.2, 0.36, 0.06)), 0, 0.72, -0.32),
        at(mesh(extrude(clamp, 0.36)), 0, 1.45, -0.32),
        at(mesh(box(0.12, 0.3, 0.36, 0.02), M.dark), 0, 1.86, -0.32),
        at(mesh(box(0.36, 0.14, 0.4, 0.04)), 0, 1.86, -0.32),
    );
}

function pushClip() {
    const fins = [0.55, 0.78, 1.01].map((y) => at(mesh(cyl(0.12, 0.3, 0.2)), 0, y, 0));

    return group(
        mesh(box(2.2, 0.18, 0.55, 0.08)),
        at(mesh(cyl(0.14, 0.14, 1.1)), 0, 0.6, 0),
        fins,
        at(mesh(new THREE.ConeGeometry(0.22, 0.35, 48)), 0, 1.3, 0),
    );
}

function boatFitting() {
    const base = rrect(1.9, 1.1, 0.4);
    [[-0.7, -0.35], [0.7, -0.35], [-0.7, 0.35], [0.7, 0.35]].forEach(([x, y]) => base.holes.push(hole(x, y, 0.08)));

    const arch = new THREE.CylinderGeometry(0.55, 0.55, 0.9, 48, 1, false, -Math.PI / 2, Math.PI);
    arch.rotateX(Math.PI / 2);
    arch.rotateZ(Math.PI / 2);

    return group(
        mesh(plate(base, 0.1)),
        at(mesh(arch), 0, 0.1, 0, 0, Math.PI / 2, 0),
        at(mesh(cyl(0.16, 0.16, 1.1)), 0.65, 0.35, 0, 0, 0, Math.PI / 2),
        [0.95, 1.1].map((x) => at(mesh(cyl(0.13, 0.2, 0.12)), x, 0.35, 0, 0, 0, Math.PI / 2)),
    );
}

function cleat() {
    const horn = new THREE.CapsuleGeometry(0.14, 1.6, 12, 32);
    horn.rotateZ(Math.PI / 2);

    return group(
        mesh(plate(rrect(1.4, 0.6, 0.28), 0.1)),
        at(mesh(cyl(0.16, 0.24, 0.4)), -0.35, 0.3, 0),
        at(mesh(cyl(0.16, 0.24, 0.4)), 0.35, 0.3, 0),
        at(mesh(horn), 0, 0.56, 0),
        at(mesh(hollowTube(0.24, 0.17, 1.1)), 1.1, 0.05, 0, 0, 0, -0.45),
        at(mesh(plate(rrect(0.7, 0.7, 0.15), 0.08)), 1.1, 0, 0),
    );
}

function ventGrille() {
    const frame = rrect(2.2, 1.4, 0.14);
    frame.holes.push(rrect(1.9, 1.1, 0.06, new THREE.Path()));

    const louvers = Array.from({ length: 7 }, (_, i) => at(mesh(box(1.92, 0.04, 0.2, 0.015)), 0, -0.45 + i * 0.15, 0, -0.55, 0, 0));

    return at(group(mesh(extrude(frame, 0.22)), louvers, at(mesh(box(0.4, 0.08, 0.1, 0.03)), 0, -0.62, 0.12)), 0, 0, 0, -0.25, 0, 0);
}

/* --------------------------------------------------- aerospace & UAV --- */

function droneFrame() {
    const arms = [45, 135, 225, 315].map((deg) => {
        const a = THREE.MathUtils.degToRad(deg);
        const x = Math.cos(a) * 1.05;
        const z = Math.sin(a) * 1.05;

        return group(
            at(mesh(box(1.4, 0.08, 0.18, 0.03)), x / 2, 0.12, z / 2, 0, -a, 0),
            at(mesh(cyl(0.2, 0.2, 0.18)), x, 0.2, z),
            at(mesh(cyl(0.12, 0.14, 0.12), M.dark), x, 0.35, z),
            at(mesh(box(1, 0.015, 0.08, 0.007), M.dark), x, 0.43, z, 0, a + 0.6, 0),
        );
    });

    return group(mesh(plate(rrect(0.8, 0.8, 0.18), 0.12)), arms, at(mesh(box(0.55, 0.2, 0.4, 0.06)), 0, 0.25, 0));
}

function cameraGimbal() {
    const lens = group(
        at(mesh(cyl(0.2, 0.22, 0.25)), 0, 0, 0.42, Math.PI / 2, 0, 0),
        at(mesh(cyl(0.15, 0.15, 0.02), M.dark), 0, 0, 0.55, Math.PI / 2, 0, 0),
    );

    return group(
        at(mesh(cyl(0.35, 0.35, 0.12)), 0, 1.6, 0),
        at(mesh(cyl(0.12, 0.12, 0.3)), 0, 1.4, 0),
        at(mesh(box(1.3, 0.14, 0.3, 0.06)), 0, 1.25, 0),
        at(mesh(box(0.14, 0.75, 0.3, 0.06)), -0.58, 0.9, 0),
        at(mesh(box(0.14, 0.75, 0.3, 0.06)), 0.58, 0.9, 0),
        at(group(mesh(box(0.9, 0.6, 0.7, 0.1)), lens), 0, 0.7, 0),
    );
}

function landingGear() {
    const skid = (x) => mesh(tube([[x, 0.2, -1.1], [x, 0.07, -0.95], [x, 0.07, 0.95], [x, 0.2, 1.1]], 0.06));
    const strut = (x, z) => mesh(tube([[x, 0.07, z], [x * 0.6, 0.6, z * 0.9], [x * 0.45, 0.9, z * 0.8]], 0.05));

    return group(
        skid(-0.75), skid(0.75),
        strut(-0.75, -0.5), strut(0.75, -0.5), strut(-0.75, 0.5), strut(0.75, 0.5),
        at(mesh(box(1, 0.1, 1.1, 0.05)), 0, 0.92, 0),
        at(mesh(box(0.6, 0.3, 0.7, 0.08)), 0, 1.12, 0),
    );
}

function latticeBracket() {
    const n = 3;
    const s = 0.5;
    const struts = [];

    for (let i = 0; i <= n; i++) {
        for (let j = 0; j <= n; j++) {
            const a = -0.75 + i * s;
            const b = -0.75 + j * s;
            struts.push(at(mesh(cyl(0.035, 0.035, 1.5, 12)), a, 0.85, b));
        }
    }

    for (let i = 0; i < n; i++) {
        for (let j = 0; j <= n; j++) {
            const a = -0.75 + i * s + s / 2;
            const b = -0.75 + j * s;
            const len = Math.hypot(s, 1.5 / n);
            const angle = Math.atan2(s, 1.5 / n);

            for (let k = 0; k < n; k++) {
                const y = 0.1 + k * (1.5 / n) + 1.5 / n / 2;
                const dir = (i + k) % 2 ? 1 : -1;
                struts.push(at(mesh(cyl(0.03, 0.03, len, 10)), a, y, b, 0, 0, angle * dir));
                struts.push(at(mesh(cyl(0.03, 0.03, len, 10)), b, y, a, angle * dir, 0, 0));
            }
        }
    }

    const top = rrect(1.8, 1.8, 0.15);
    top.holes.push(hole(0, 0, 0.25));

    return group(mesh(plate(rrect(1.8, 1.8, 0.15), 0.1)), at(mesh(plate(top, 0.1)), 0, 1.55, 0), struts);
}

function antennaDome() {
    const base = rrect(1.6, 1.6, 0.25);
    [[-0.6, -0.6], [0.6, -0.6], [-0.6, 0.6], [0.6, 0.6]].forEach(([x, y]) => base.holes.push(hole(x, y, 0.07)));

    const dome = [];

    for (let i = 0; i <= 20; i++) {
        const t = (i / 20) * (Math.PI / 2);
        dome.push([0.62 * Math.sin(t) + 0.001, 0.75 * Math.cos(t)]);
    }

    return group(
        mesh(plate(base, 0.12)),
        at(mesh(cyl(0.68, 0.7, 0.14)), 0, 0.19, 0),
        at(mesh(lathe(dome)), 0, 0.26, 0),
    );
}

/* ----------------------------------------------------- manufaktur ----- */

function gear() {
    const shape = gearShape(24, 1.1, 0.95, 0.22);
    [0, 1, 2, 3, 4].forEach((i) => {
        const a = (i / 5) * Math.PI * 2;
        shape.holes.push(hole(Math.cos(a) * 0.58, Math.sin(a) * 0.58, 0.17));
    });

    return group(
        at(mesh(extrude(shape, 0.3, 0.015)), 0, 0.6, 0, -0.35, 0.4, 0),
        at(mesh(hollowTube(0.36, 0.22, 0.5)), 0, 0.6, 0, -0.35 - Math.PI / 2, 0.4, 0),
    );
}

function gripper() {
    const finger = new THREE.Shape();
    finger.moveTo(0, 0);
    finger.lineTo(0.3, 0);
    finger.lineTo(0.3, 0.9);
    [0.95, 1.05, 1.15].forEach((y) => {
        finger.lineTo(0.2, y);
        finger.lineTo(0.3, y + 0.05);
    });
    finger.lineTo(0.3, 1.3);
    finger.lineTo(0.05, 1.3);
    finger.lineTo(0, 0);

    const left = at(mesh(extrude(finger, 0.4)), -0.55, 0.5, 0);
    const right = at(mesh(extrude(finger, 0.4)), 0.55, 0.5, 0, 0, Math.PI, 0);

    return group(
        at(mesh(cyl(0.45, 0.5, 0.25)), 0, 0.12, 0),
        at(mesh(box(1.5, 0.3, 0.6, 0.06)), 0, 0.38, 0),
        at(mesh(box(1.1, 0.06, 0.3, 0.03), M.dark), 0, 0.53, 0),
        left,
        right,
    );
}

function jig() {
    const base = rrect(2.2, 1.5, 0.08);
    [[-0.9, -0.6], [0.9, -0.6], [-0.9, 0.6], [0.9, 0.6]].forEach(([x, y]) => base.holes.push(hole(x, y, 0.06)));

    return group(
        mesh(plate(base, 0.14)),
        at(mesh(box(0.25, 0.35, 1.0, 0.03)), -0.7, 0.3, 0),
        at(mesh(box(1.1, 0.35, 0.25, 0.03)), 0, 0.3, -0.45),
        at(mesh(cyl(0.07, 0.07, 0.4)), 0.4, 0.3, 0.3),
        at(mesh(cyl(0.07, 0.07, 0.4)), -0.2, 0.3, 0.3),
        at(mesh(box(0.8, 0.12, 0.6, 0.05)), 0.15, 0.2, 0.05),
        at(mesh(box(0.15, 0.5, 0.15, 0.03)), 0.85, 0.38, 0.35),
        at(mesh(box(0.7, 0.1, 0.12, 0.04)), 0.55, 0.62, 0.35, 0, 0, 0.25),
    );
}

function gauge() {
    const knurl = cyl(0.18, 0.18, 1.0, 24);

    return at(group(
        mesh(knurl),
        at(mesh(cyl(0.2, 0.2, 0.08)), 0, 0.52, 0),
        at(mesh(cyl(0.2, 0.2, 0.08)), 0, -0.52, 0),
        at(mesh(cyl(0.13, 0.13, 0.55), M.metal), 0, 0.82, 0),
        at(mesh(cyl(0.15, 0.15, 0.3), M.metal), 0, -0.7, 0),
        at(mesh(box(0.22, 0.12, 0.6, 0.03)), 0.2, 0, 0, 0, 0, 0),
    ), 0, 0.3, 0, 0, 0, Math.PI / 2 - 0.15);
}

function roller() {
    const profile = [[0.22, -1], [0.55, -1]];

    for (let i = 0; i <= 8; i++) {
        const y = -0.9 + i * 0.225;
        profile.push([0.55, y - 0.05], [0.5, y], [0.55, y + 0.05]);
    }

    profile.push([0.55, 1], [0.22, 1]);

    return at(group(
        mesh(lathe(profile)),
        at(mesh(cyl(0.12, 0.12, 2.6), M.metal), 0, 0, 0),
        at(mesh(cyl(0.3, 0.3, 0.15)), 0, 1.15, 0),
        at(mesh(cyl(0.3, 0.3, 0.15)), 0, -1.15, 0),
    ), 0, 0.6, 0, 0, 0.5, Math.PI / 2);
}

function machineCover() {
    const shell = new THREE.CylinderGeometry(1, 1, 2, 64, 1, true, -Math.PI / 2, Math.PI);
    const inner = new THREE.CylinderGeometry(0.92, 0.92, 2, 64, 1, true, -Math.PI / 2, Math.PI);
    const slots = Array.from({ length: 5 }, (_, i) => at(mesh(box(0.06, 0.45, 0.02, 0.01), M.dark), -0.4 + i * 0.2, 0.86 + Math.abs(-0.4 + i * 0.2) * -0.2, 0.25, 0.3, 0, 0));
    const flange = (y) => at(mesh(extrude(halfRing(1.12, 0.92), 0.08)), 0, y, 0, Math.PI / 2, 0, 0);

    return group(
        at(mesh(shell), 0, 0, 0, 0, 0, Math.PI / 2),
        at(mesh(inner), 0, 0, 0, 0, 0, Math.PI / 2),
        at(group(flange(1), flange(-1)), 0, 0, 0, 0, 0, Math.PI / 2),
        at(group(slots), 0, 0, 0),
    );
}

function halfRing(outer, inner) {
    const shape = new THREE.Shape();
    shape.absarc(0, 0, outer, 0, Math.PI, false);
    shape.lineTo(-inner, 0);
    shape.absarc(0, 0, inner, Math.PI, 0, true);
    shape.lineTo(outer, 0);

    return shape;
}

/* ------------------------------------------------ kimia, energi, migas --- */

function impeller() {
    const blades = Array.from({ length: 8 }, (_, i) => {
        const blade = new THREE.Shape();
        blade.moveTo(0.25, 0);
        blade.quadraticCurveTo(0.7, 0.25, 1.05, 0.15);
        blade.lineTo(1.05, 0.08);
        blade.quadraticCurveTo(0.7, 0.15, 0.25, -0.08);
        blade.closePath();

        const geometry = extrude(blade, 0.45, 0.01);
        geometry.rotateX(-Math.PI / 2);

        return at(mesh(geometry, M.metal), 0, 0.33, 0, 0, (i / 8) * Math.PI * 2, 0);
    });

    return group(
        at(mesh(cyl(1.1, 1.1, 0.1), M.metal), 0, 0.05, 0),
        mesh(lathe([[0.001, 0.9], [0.18, 0.85], [0.28, 0.6], [0.3, 0.1], [0.001, 0.1]]), M.metal),
        blades,
    );
}

function nozzle() {
    return group(
        at(mesh(cyl(0.55, 0.55, 0.4, 6), M.metal), 0, 0.2, 0),
        mesh(lathe([[0.2, 0.4], [0.42, 0.4], [0.42, 0.75], [0.35, 0.8], [0.3, 1.5], [0.18, 1.85], [0.12, 1.85], [0.12, 0.4]]), M.metal),
        at(mesh(cyl(0.4, 0.4, 0.3, 48), M.metal), 0, -0.15, 0),
    );
}

function manifold() {
    const port = (x, y, z, rx, rz) => at(group(
        mesh(cyl(0.18, 0.18, 0.5), M.metal),
        at(mesh(cyl(0.26, 0.26, 0.18, 6), M.metal), 0, 0.2, 0),
        at(mesh(cyl(0.1, 0.1, 0.02), M.dark), 0, 0.3, 0),
    ), x, y, z, rx, 0, rz);

    return group(
        at(mesh(box(2, 0.8, 0.8, 0.08), M.metal), 0, 0.4, 0),
        [-0.6, 0, 0.6].map((x) => port(x, 1.0, 0, 0, 0)),
        port(-1.2, 0.4, 0, 0, Math.PI / 2),
        port(1.2, 0.4, 0, 0, -Math.PI / 2),
        port(0, 0.4, 0.6, Math.PI / 2, 0),
    );
}

function pipePlant() {
    const tank = (x, z, h) => group(
        at(mesh(new THREE.CapsuleGeometry(0.3, h, 12, 32)), x, 0.3 + h / 2 + 0.1, z),
        at(mesh(cyl(0.34, 0.34, 0.1)), x, 0.15, z),
    );

    return group(
        mesh(plate(rrect(2.6, 1.8, 0.1), 0.1)),
        tank(-0.8, -0.3, 0.9),
        tank(0.1, -0.4, 0.6),
        at(mesh(box(0.6, 0.5, 0.5, 0.05)), 0.85, 0.35, 0.35),
        mesh(tube([[-0.8, 0.9, -0.3], [-0.8, 1.35, 0.1], [0.1, 1.35, 0.1], [0.1, 0.9, -0.4]], 0.06)),
        mesh(tube([[0.1, 0.4, -0.4], [0.5, 0.4, 0], [0.85, 0.4, 0.35]], 0.06)),
        mesh(tube([[-0.8, 0.35, -0.3], [-0.8, 0.25, 0.5], [0.85, 0.25, 0.6]], 0.05)),
    );
}

function instrumentHousing() {
    return group(
        at(mesh(box(1.4, 1.4, 0.8, 0.15)), 0, 0.7, 0),
        at(mesh(cyl(0.48, 0.48, 0.12)), 0, 0.78, 0.42, Math.PI / 2, 0, 0),
        at(mesh(cyl(0.38, 0.38, 0.02), M.dark), 0, 0.78, 0.48, Math.PI / 2, 0, 0),
        at(mesh(box(0.04, 0.3, 0.02, 0.01)), 0.07, 0.86, 0.5, 0, 0, -0.6),
        at(mesh(cyl(0.12, 0.14, 0.3, 6)), 0, -0.05, 0),
        at(mesh(cyl(0.08, 0.08, 0.4)), 0, -0.25, 0),
        [-0.5, 0.5].map((x) => [0.2, 1.2].map((y) => at(mesh(cyl(0.05, 0.05, 0.04), M.metal), x, y, 0.41, Math.PI / 2, 0, 0))),
    );
}

function flange() {
    const shape = ringShape(1, 0.4);
    Array.from({ length: 8 }, (_, i) => (i / 8) * Math.PI * 2).forEach((a) => shape.holes.push(hole(Math.cos(a) * 0.75, Math.sin(a) * 0.75, 0.09)));

    return group(
        mesh(plate(shape, 0.2, 0.02), M.metal),
        mesh(lathe([[0.4, 0.2], [0.6, 0.2], [0.48, 0.6], [0.48, 1.0], [0.4, 1.0]]), M.metal),
    );
}

/* ------------------------------------------------ elektronik & IoT ---- */

function enclosure() {
    const vents = Array.from({ length: 6 }, (_, i) => at(mesh(box(0.06, 0.03, 0.6, 0.012), M.dark), -0.35 + i * 0.14, 0.86, 0));

    return group(
        at(mesh(box(1.6, 0.6, 1.1, 0.12)), 0, 0.3, 0),
        at(mesh(box(1.62, 0.25, 1.12, 0.1)), 0, 0.73, 0),
        vents,
        at(mesh(cyl(0.05, 0.05, 0.02), M.dark), 0.55, 0.86, -0.3),
        at(mesh(box(0.3, 0.12, 0.05, 0.02), M.dark), -0.4, 0.3, 0.55),
        at(mesh(cyl(0.08, 0.08, 0.05), M.dark), 0.5, 0.3, 0.55, Math.PI / 2, 0, 0),
    );
}

function handheld() {
    const buttons = [-0.18, 0.18].map((x) => at(mesh(cyl(0.08, 0.08, 0.05)), x, 0.26, 0.55));

    return at(group(
        mesh(box(0.85, 0.35, 1.7, 0.16)),
        at(mesh(box(0.6, 0.02, 0.7, 0.04), M.dark), 0, 0.18, -0.25),
        buttons.map((b) => {
            b.position.y = 0.18;
            b.position.z = 0.45;

            return b;
        }),
        at(mesh(cyl(0.12, 0.12, 0.04)), 0, 0.18, 0.2),
    ), 0, 0.3, 0, 0.35, 0.4, 0);
}

function pcbHolder() {
    const standoffs = [[-0.7, -0.45], [0.7, -0.45], [-0.7, 0.45], [0.7, 0.45]].map(([x, z]) => at(mesh(cyl(0.09, 0.11, 0.35, 6)), x, 0.27, z));

    return group(
        mesh(plate(rrect(1.9, 1.3, 0.12), 0.1)),
        standoffs,
        at(mesh(box(1.7, 0.05, 1.15, 0.01), M.dark), 0, 0.47, 0),
        at(mesh(box(0.45, 0.08, 0.45, 0.02)), -0.3, 0.53, 0),
        at(mesh(box(0.25, 0.06, 0.15, 0.01)), 0.4, 0.52, 0.25),
        at(mesh(box(0.25, 0.06, 0.15, 0.01)), 0.4, 0.52, -0.2),
        at(mesh(box(0.4, 0.18, 0.2, 0.02)), 0.55, 0.58, -0.45),
    );
}

function knobPanel() {
    const knobs = [-0.55, 0, 0.55].map((x) => at(group(
        mesh(cyl(0.18, 0.2, 0.22, 24)),
        at(mesh(box(0.03, 0.03, 0.12, 0.01), M.dark), 0, 0.12, -0.08),
    ), x, 0.75, 0.17, Math.PI / 2, 0, 0));

    return group(
        at(mesh(box(2, 1.2, 0.12, 0.06)), 0, 0.75, 0),
        at(mesh(box(2, 0.12, 0.6, 0.04)), 0, 0.06, 0.24),
        knobs,
        [-0.5, -0.17, 0.17, 0.5].map((x) => at(mesh(box(0.22, 0.12, 0.08, 0.03)), x, 0.38, 0.08)),
        at(mesh(box(0.8, 0.22, 0.02, 0.02), M.dark), 0, 1.12, 0.07),
    );
}

function watch() {
    // Strap berbentuk cincin oval, case jam duduk di atasnya.
    return group(
        scaled(at(mesh(hollowTube(0.62, 0.55, 0.5)), -0.25, 0.64, 0, 0, 0, -Math.PI / 2), 1, 1, 1.2),
        at(mesh(box(0.8, 0.26, 0.9, 0.16)), 0, 1.3, 0),
        at(mesh(box(0.62, 0.02, 0.72, 0.12), M.dark), 0, 1.44, 0),
        at(mesh(cyl(0.06, 0.06, 0.1)), 0.43, 1.3, 0.12, 0, 0, Math.PI / 2),
    );
}

function cableClip() {
    const clip = () => {
        const shape = new THREE.Shape();
        shape.absarc(0, 0.25, 0.25, -Math.PI * 0.2, Math.PI * 1.2, false);
        shape.absarc(0, 0.25, 0.17, Math.PI * 1.2, -Math.PI * 0.2, true);
        shape.closePath();

        return mesh(extrude(shape, 0.3, 0.01));
    };

    return group(
        mesh(plate(rrect(2.4, 0.5, 0.15), 0.08)),
        [-0.8, 0, 0.8].map((x) => at(clip(), x, 0.06, 0, 0, 0, 0)),
        [-0.8, 0, 0.8].map((x) => at(mesh(cyl(0.12, 0.12, 1.6, 32), M.dark), x, 0.31, 0, Math.PI / 2, 0, 0)),
    );
}

/* ------------------------------------------------- arsitektur -------- */

function building() {
    const tower = (x, z, w, d, floors) => group(
        at(mesh(box(w, floors * 0.18, d, 0.01)), x, (floors * 0.18) / 2 + 0.08, z),
        Array.from({ length: floors }, (_, i) => at(mesh(box(w + 0.06, 0.025, d + 0.06, 0.005)), x, 0.08 + (i + 1) * 0.18, z)),
    );

    return group(
        mesh(plate(rrect(2.4, 1.8, 0.04), 0.08, 0.01)),
        tower(-0.45, -0.2, 0.8, 0.8, 9),
        tower(0.5, 0.05, 0.7, 1.1, 5),
        tower(-0.35, 0.55, 1.2, 0.4, 2),
    );
}

function masterplan() {
    const rand = seeded(7);
    const blocks = [];

    for (let i = -2; i <= 2; i++) {
        for (let j = -2; j <= 2; j++) {
            if (i === 0 && j === 0) {
                continue;
            }

            const h = 0.1 + rand() * (Math.abs(i) + Math.abs(j) < 2 ? 0.9 : 0.4);
            blocks.push(at(mesh(box(0.3, h, 0.3, 0.01)), i * 0.45, 0.08 + h / 2, j * 0.45));
        }
    }

    return group(
        mesh(plate(rrect(2.6, 2.6, 0.04), 0.08, 0.01)),
        blocks,
        at(mesh(cyl(0.18, 0.18, 0.04)), 0, 0.1, 0),
    );
}

function terrain() {
    const size = 2.4;
    const geometry = new THREE.PlaneGeometry(size, size, 96, 96);
    geometry.rotateX(-Math.PI / 2);
    const pos = geometry.attributes.position;

    for (let i = 0; i < pos.count; i++) {
        const x = pos.getX(i);
        const z = pos.getZ(i);
        let h = 0.85 * Math.exp(-((x + 0.3) ** 2 + (z - 0.2) ** 2) * 1.6)
            + 0.5 * Math.exp(-((x - 0.7) ** 2 + (z + 0.5) ** 2) * 3)
            + 0.05 * Math.sin(x * 4) * Math.cos(z * 3);
        h = Math.round(h / 0.06) * 0.06; // kontur bertingkat seperti maket
        pos.setY(i, 0.2 + Math.max(h, 0));
    }

    geometry.computeVertexNormals();

    return group(mesh(geometry), at(mesh(box(size, 0.2, size, 0.01)), 0, 0.1, 0));
}

function parametricPanel() {
    const cells = [];

    for (let i = 0; i < 7; i++) {
        for (let j = 0; j < 5; j++) {
            const h = 0.06 + 0.22 * (0.5 + 0.5 * Math.sin(i * 0.9 + j * 0.7));
            const pyramid = new THREE.ConeGeometry(0.2, h, 4);
            pyramid.rotateY(Math.PI / 4);
            cells.push(at(mesh(pyramid), -0.84 + i * 0.28, 0.1 + h / 2, -0.56 + j * 0.28));
        }
    }

    return at(group(mesh(box(2.1, 0.1, 1.5, 0.02)), cells), 0, 0.6, 0, 1.05, 0, 0);
}

function twistedShell({ radius = 0.7, height = 1.6, lobes = 9, twist = 1.4, open = true, taper = 0.25 } = {}) {
    const geometry = new THREE.CylinderGeometry(radius, radius, height, 180, 60, open);
    const pos = geometry.attributes.position;

    for (let i = 0; i < pos.count; i++) {
        const x = pos.getX(i);
        const y = pos.getY(i);
        const z = pos.getZ(i);
        const t = (y + height / 2) / height;
        const a = Math.atan2(z, x);
        const r = Math.hypot(x, z);

        if (r < 1e-4) {
            continue;
        }

        const profile = 1 - taper * Math.sin(t * Math.PI) + 0.25 * t * t;
        const k = profile * (1 + 0.08 * Math.cos(lobes * a + twist * t * Math.PI * 2));
        pos.setX(i, (x / r) * radius * k);
        pos.setZ(i, (z / r) * radius * k);
    }

    geometry.computeVertexNormals();
    geometry.translate(0, height / 2, 0);

    return geometry;
}

function lampshade() {
    const material = M.partDouble;

    return group(
        mesh(twistedShell({ radius: 0.75, height: 1.3, lobes: 14, twist: 1, taper: -0.1 }), material),
        at(mesh(cyl(0.1, 0.1, 0.5)), 0, 1.5, 0),
    );
}

function ornament() {
    const star = new THREE.Shape();

    for (let i = 0; i < 16; i++) {
        const r = i % 2 ? 0.35 : 0.55;
        const a = (i / 16) * Math.PI * 2;
        i ? star.lineTo(Math.cos(a) * r, Math.sin(a) * r) : star.moveTo(r, 0);
    }

    const geometry = new THREE.ExtrudeGeometry(star, { depth: 2, steps: 80, bevelEnabled: false });
    const pos = geometry.attributes.position;

    for (let i = 0; i < pos.count; i++) {
        const z = pos.getZ(i);
        const angle = z * 0.9;
        const scale = 1 - 0.18 * Math.sin((z / 2) * Math.PI);
        const x = pos.getX(i) * scale;
        const y = pos.getY(i) * scale;
        pos.setX(i, x * Math.cos(angle) - y * Math.sin(angle));
        pos.setY(i, x * Math.sin(angle) + y * Math.cos(angle));
    }

    geometry.computeVertexNormals();
    geometry.rotateX(-Math.PI / 2);

    return group(mesh(cyl(0.7, 0.75, 0.15)), at(mesh(geometry), 0, 0.07, 0), at(mesh(cyl(0.7, 0.65, 0.15)), 0, 2.12, 0));
}

/* --------------------------------------------------- produk & furnitur --- */

function speaker() {
    return group(
        mesh(lathe([[0.001, 0], [0.75, 0], [0.85, 0.1], [0.88, 0.5], [0.8, 0.62], [0.001, 0.64]])),
        at(mesh(cyl(0.66, 0.66, 0.02), M.dark), 0, 0.64, 0),
        at(mesh(torus(0.3, 0.02)), 0, 0.66, 0, Math.PI / 2, 0, 0),
        at(mesh(cyl(0.05, 0.05, 0.03)), 0, 0.66, 0),
    );
}

function connectorJoint() {
    const arm = (rx, rz) => at(group(mesh(hollowTube(0.3, 0.22, 0.75))), 0, 0, 0, rx, 0, rz);

    return at(group(
        mesh(sphere(0.36)),
        arm(0, 0),
        arm(0, -Math.PI / 2),
        arm(Math.PI / 2, 0),
    ), 0, 0.5, 0, 0, 0.6, 0);
}

function handle() {
    return group(
        [-0.8, 0.8].map((x) => at(mesh(cyl(0.16, 0.2, 0.15)), x, 0.07, 0)),
        mesh(tube([[-0.8, 0.1, 0], [-0.75, 0.45, 0], [-0.45, 0.62, 0], [0.45, 0.62, 0], [0.75, 0.45, 0], [0.8, 0.1, 0]], 0.1)),
        at(mesh(plate(rrect(2.1, 0.5, 0.2), 0.05)), 0, -0.05, 0),
    );
}

function vase() {
    return group(
        mesh(twistedShell({ radius: 0.55, height: 1.9, lobes: 6, twist: 0.8, open: false, taper: -0.35 })),
    );
}

function planter() {
    return group(
        mesh(lathe([[0.001, 0], [0.55, 0], [0.75, 0.9], [0.8, 0.95], [0.66, 0.95], [0.5, 0.15], [0.001, 0.15]], 6)),
        at(mesh(cyl(0.68, 0.68, 0.03, 6), M.dark), 0, 0.85, 0),
    );
}

/* ------------------------------------------------------ mainan ------- */

function figure() {
    return group(
        [-0.22, 0.22].map((x) => at(mesh(box(0.28, 0.6, 0.32, 0.08)), x, 0.3, 0)),
        at(mesh(box(0.85, 0.75, 0.5, 0.12)), 0, 0.98, 0),
        [-0.55, 0.55].map((x) => at(mesh(new THREE.CapsuleGeometry(0.12, 0.45, 8, 24)), x, 0.95, 0, 0, 0, x > 0 ? 0.3 : -0.3)),
        at(mesh(box(0.7, 0.55, 0.55, 0.15)), 0, 1.7, 0),
        [-0.15, 0.15].map((x) => at(mesh(sphere(0.07), M.dark), x, 1.75, 0.27)),
        at(mesh(cyl(0.025, 0.025, 0.3)), 0, 2.1, 0),
        at(mesh(sphere(0.07)), 0, 2.27, 0),
        at(mesh(box(0.3, 0.2, 0.04, 0.03), M.dark), 0, 1.05, 0.26),
    );
}

function toyCar() {
    const wheel = (x, z) => at(group(mesh(cyl(0.3, 0.3, 0.22), M.dark), at(mesh(cyl(0.15, 0.15, 0.24)), 0, 0, 0)), x, 0.3, z, Math.PI / 2, 0, 0);

    return group(
        at(mesh(box(2, 0.45, 1, 0.18)), 0, 0.5, 0),
        at(mesh(box(1.05, 0.45, 0.85, 0.2)), -0.1, 0.9, 0),
        at(mesh(box(0.95, 0.3, 0.87, 0.15), M.dark), -0.1, 0.92, 0),
        wheel(-0.62, 0.5), wheel(0.62, 0.5), wheel(-0.62, -0.5), wheel(0.62, -0.5),
    );
}

function diorama() {
    const tree = (x, z, s) => group(
        at(mesh(cyl(0.04, 0.05, 0.25 * s)), x, 0.2 + 0.12 * s, z),
        at(mesh(new THREE.ConeGeometry(0.22 * s, 0.6 * s, 24)), x, 0.3 + 0.5 * s, z),
    );
    const roof = new THREE.CylinderGeometry(0.42, 0.42, 0.8, 3);
    roof.rotateX(Math.PI / 2);

    return group(
        mesh(lathe([[0.001, 0], [1.2, 0], [1.25, 0.08], [1.2, 0.2], [0.001, 0.2]])),
        at(mesh(box(0.7, 0.45, 0.55, 0.03)), -0.2, 0.42, 0),
        at(mesh(roof), -0.2, 0.84, 0, 0, Math.PI / 2, Math.PI / 2 * 0),
        at(mesh(box(0.15, 0.25, 0.02, 0.01), M.dark), -0.2, 0.33, 0.28),
        tree(0.6, -0.3, 1.1), tree(0.75, 0.3, 0.8), tree(0.25, 0.6, 0.7),
        at(mesh(sphere(0.12)), -0.75, 0.24, 0.5),
    );
}

function podium() {
    return group(
        at(mesh(box(0.8, 0.9, 0.8, 0.04)), 0, 0.45, 0),
        at(mesh(box(0.8, 0.6, 0.8, 0.04)), -0.82, 0.3, 0),
        at(mesh(box(0.8, 0.4, 0.8, 0.04)), 0.82, 0.2, 0),
        at(mesh(sphere(0.22)), 0, 1.12, 0),
        at(mesh(box(0.35, 0.35, 0.35, 0.05)), -0.82, 0.78, 0, 0, 0.5, 0),
        at(mesh(new THREE.ConeGeometry(0.2, 0.4, 32)), 0.82, 0.6, 0),
    );
}

function retailStand() {
    return group(
        mesh(plate(rrect(1.6, 1, 0.1), 0.08)),
        at(mesh(box(1.5, 1.1, 0.05, 0.03)), 0, 0.62, -0.35, -0.2, 0, 0),
        at(mesh(box(1.2, 0.5, 0.02, 0.02), M.dark), 0, 0.8, -0.31, -0.2, 0, 0),
        at(mesh(box(0.08, 0.08, 1, 0.02)), 0, 0.12, 0.05),
        at(mesh(box(0.45, 0.6, 0.3, 0.05)), 0.35, 0.38, 0.2),
        at(mesh(cyl(0.18, 0.18, 0.5)), -0.35, 0.33, 0.25),
    );
}

function mascot() {
    return group(
        scaled(at(mesh(sphere(0.55)), 0, 0.6, 0), 1, 1.05, 0.9),
        at(mesh(sphere(0.48)), 0, 1.45, 0),
        [-0.36, 0.36].map((x) => at(mesh(sphere(0.16)), x, 1.82, 0)),
        at(mesh(sphere(0.17)), 0, 1.36, 0.4),
        at(mesh(sphere(0.06), M.dark), 0, 1.42, 0.55),
        [-0.17, 0.17].map((x) => at(mesh(sphere(0.055), M.dark), x, 1.55, 0.42)),
        [-0.55, 0.55].map((x) => at(mesh(sphere(0.2)), x, 0.75, 0.1)),
        [-0.28, 0.28].map((x) => at(mesh(sphere(0.22)), x, 0.18, 0.2)),
    );
}

/* ------------------------------------------------------ medis -------- */

function bone() {
    return at(group(
        mesh(cyl(0.13, 0.16, 1.8, 32)),
        at(mesh(sphere(0.28)), 0.12, 1.0, 0),
        at(mesh(sphere(0.2)), -0.15, 0.92, 0),
        at(mesh(sphere(0.24)), 0.13, -0.95, 0),
        at(mesh(sphere(0.24)), -0.15, -0.95, 0),
    ), 0, 0.35, 0, 0.2, 0.3, Math.PI / 2 - 0.1);
}

function teeth() {
    const arch = new THREE.Shape();
    arch.absarc(0, 0, 1.05, Math.PI * 1.05, -Math.PI * 0.05, true);
    arch.absarc(0, 0, 0.55, -Math.PI * 0.05, Math.PI * 1.05, false);
    arch.closePath();

    const teethMeshes = Array.from({ length: 14 }, (_, i) => {
        const a = Math.PI * 1.02 - (i / 13) * Math.PI * 1.04;
        const size = i < 3 || i > 10 ? 0.24 : i < 5 || i > 8 ? 0.18 : 0.15;

        return at(mesh(box(size, 0.32, size * 1.1, 0.07)), Math.cos(a) * 0.8, 0.36, -Math.sin(a) * 0.8, 0, a, 0);
    });

    return group(mesh(plate(arch, 0.22, 0.06)), teethMeshes);
}

function surgicalGuide() {
    const arch = new THREE.Shape();
    arch.absarc(0, 0, 1.05, Math.PI * 1.05, -Math.PI * 0.05, true);
    arch.absarc(0, 0, 0.6, -Math.PI * 0.05, Math.PI * 1.05, false);
    arch.closePath();

    const sleeves = [0.25, 0.5, 0.75].map((k) => {
        const a = Math.PI * (1 - k);

        return at(mesh(hollowTube(0.1, 0.06, 0.25), M.metal), Math.cos(a) * 0.82, 0.12, -Math.sin(a) * 0.82);
    });

    return group(mesh(plate(arch, 0.14, 0.05)), sleeves);
}

function splint() {
    const material = M.partDouble;
    const geometry = new THREE.CylinderGeometry(0.45, 0.6, 2, 64, 1, true, -Math.PI * 0.65, Math.PI * 1.3);

    return group(at(mesh(geometry, material), 0, 0.55, 0, Math.PI / 2, 0, 0));
}

function spine() {
    return at(group(Array.from({ length: 5 }, (_, i) => group(
        at(mesh(cyl(0.38, 0.4, 0.26)), 0, i * 0.38, 0),
        at(mesh(cyl(0.34, 0.34, 0.08), M.dark), 0, i * 0.38 + 0.18, 0),
        at(mesh(box(0.14, 0.18, 0.5, 0.05)), 0, i * 0.38, -0.5, -0.4, 0, 0),
        at(mesh(box(0.7, 0.1, 0.12, 0.04)), 0, i * 0.38, -0.25),
    ))), 0, 0.15, 0, 0, 0.8, 0);
}

/* ------------------------------------------------ pendidikan & riset --- */

function robotArm() {
    return group(
        mesh(cyl(0.5, 0.55, 0.3)),
        at(mesh(cyl(0.3, 0.3, 0.35)), 0, 0.45, 0),
        at(mesh(box(0.3, 1.0, 0.3, 0.08)), 0.15, 1.0, 0, 0, 0, -0.3),
        at(mesh(cyl(0.2, 0.2, 0.4)), 0.32, 1.5, 0, Math.PI / 2, 0, 0),
        at(mesh(box(0.9, 0.24, 0.24, 0.08)), 0.7, 1.62, 0, 0, 0, -0.25),
        at(mesh(cyl(0.15, 0.15, 0.3)), 1.15, 1.5, 0, Math.PI / 2, 0, 0),
        at(mesh(box(0.08, 0.3, 0.08, 0.02)), 1.12, 1.25, 0.08),
        at(mesh(box(0.08, 0.3, 0.08, 0.02)), 1.12, 1.25, -0.08),
    );
}

function robotChassis() {
    const wheel = (x, z) => at(group(mesh(cyl(0.32, 0.32, 0.22), M.dark), at(mesh(cyl(0.2, 0.2, 0.24)), 0, 0, 0)), x, 0.32, z, Math.PI / 2, 0, 0);

    return group(
        at(mesh(box(1.6, 0.15, 1.0, 0.05)), 0, 0.4, 0),
        wheel(-0.55, 0.65), wheel(0.55, 0.65), wheel(-0.55, -0.65), wheel(0.55, -0.65),
        at(mesh(box(0.7, 0.3, 0.6, 0.06)), -0.2, 0.62, 0),
        at(mesh(box(0.2, 0.15, 0.5, 0.04)), 0.75, 0.55, 0),
        [-0.15, 0.15].map((z) => at(mesh(cyl(0.06, 0.06, 0.08), M.dark), 0.86, 0.55, z, 0, 0, Math.PI / 2)),
    );
}

function wheelRim() {
    const spokes = Array.from({ length: 5 }, (_, i) => {
        const a = (i / 5) * Math.PI * 2;

        return at(mesh(box(0.14, 0.6, 0.08, 0.03)), Math.sin(a) * 0.48, Math.cos(a) * 0.48, 0, 0, 0, -a);
    });

    return at(group(
        mesh(torus(0.95, 0.18), M.dark),
        mesh(torus(0.78, 0.05)),
        spokes,
        at(mesh(cyl(0.22, 0.22, 0.2)), 0, 0, 0, Math.PI / 2, 0, 0),
    ), 0, 1.13, 0, 0, 0.5, 0);
}

function gearTrain() {
    return group(
        mesh(plate(rrect(2.6, 1.6, 0.1), 0.1)),
        at(mesh(extrude(gearShape(20, 0.6, 0.52, 0.08), 0.18, 0.01)), -0.45, 0.55, 0, -Math.PI / 2, 0, 0),
        at(mesh(extrude(gearShape(12, 0.38, 0.31, 0.06), 0.18, 0.01)), 0.5, 0.55, 0.05, -Math.PI / 2, 0, 0.15),
        at(mesh(extrude(gearShape(9, 0.28, 0.22, 0.05), 0.18, 0.01)), 0.95, 0.55, -0.45, -Math.PI / 2, 0, 0),
        [[-0.45, 0], [0.5, 0.05], [0.95, -0.45]].map(([x, z]) => at(mesh(cyl(0.06, 0.06, 0.65), M.metal), x, 0.4, z)),
        [[-0.45, 0], [0.5, 0.05], [0.95, -0.45]].map(([x, z]) => at(mesh(cyl(0.12, 0.14, 0.35)), x, 0.27, z)),
    );
}

function tubeRack() {
    const top = rrect(2.2, 0.8, 0.06);
    const positions = [-0.75, -0.45, -0.15, 0.15, 0.45, 0.75];
    positions.forEach((x) => top.holes.push(hole(x, 0, 0.11)));

    return group(
        mesh(plate(rrect(2.2, 0.8, 0.06), 0.08)),
        at(mesh(plate(top, 0.08)), 0, 0.6, 0),
        [-1.05, 1.05].map((x) => at(mesh(box(0.08, 0.6, 0.8, 0.02)), x, 0.34, 0)),
        positions.filter((_, i) => i % 2 === 0 || i === 3).map((x, i) => at(mesh(new THREE.CapsuleGeometry(0.09, 0.9 - i * 0.05, 8, 24)), x, 0.65, 0)),
    );
}

function molecule() {
    const atoms = [
        [0, 0.9, 0, 0.3], [0.7, 1.4, 0.2, 0.22], [-0.7, 1.35, -0.1, 0.22],
        [0.1, 0.2, 0.5, 0.22], [0.6, 0.45, -0.5, 0.18], [-0.6, 0.4, -0.4, 0.18], [1.2, 1.9, 0.3, 0.15],
    ];
    const bonds = [[0, 1], [0, 2], [0, 3], [0, 4], [0, 5], [1, 6]];

    return group(
        atoms.map(([x, y, z, r], i) => at(mesh(sphere(r), i === 0 ? M.dark : M.part), x, y, z)),
        bonds.map(([a, b]) => mesh(tube([atoms[a].slice(0, 3), atoms[b].slice(0, 3)], 0.05, 4))),
    );
}

/* -------------------------------------------- seni, fashion, perhiasan --- */

function sculpture() {
    return group(
        at(mesh(new THREE.TorusKnotGeometry(0.6, 0.2, 220, 32, 2, 3)), 0, 1.15, 0, 0.3, 0, 0),
        at(mesh(box(0.7, 0.35, 0.7, 0.04)), 0, 0.17, 0),
        at(mesh(cyl(0.06, 0.06, 0.25)), 0, 0.42, 0),
    );
}

function eyewear() {
    const lensShape = rrect(0.9, 0.6, 0.25);
    lensShape.holes.push(rrect(0.75, 0.45, 0.18, new THREE.Path()));

    return at(group(
        at(mesh(extrude(lensShape, 0.08)), -0.55, 0, 0),
        at(mesh(extrude(lensShape, 0.08)), 0.55, 0, 0),
        at(mesh(box(0.75, 0.45, 0.01, 0.005), M.dark), -0.55, 0, 0),
        at(mesh(box(0.75, 0.45, 0.01, 0.005), M.dark), 0.55, 0, 0),
        mesh(tube([[-0.12, 0.12, 0], [0, 0.18, 0], [0.12, 0.12, 0]], 0.035)),
        [-1, 1].map((s) => mesh(tube([[s * 1.0, 0.15, 0], [s * 1.02, 0.15, -0.8], [s * 1.0, 0.0, -1.1]], 0.035))),
    ), 0, 0.5, 0, 0, 0.5, 0);
}

function helmet() {
    const shell = new THREE.SphereGeometry(0.9, 64, 32, 0, Math.PI * 2, 0, Math.PI * 0.6);
    const visor = new THREE.SphereGeometry(0.93, 64, 32, -Math.PI * 0.3 + Math.PI / 2, Math.PI * 0.6, Math.PI * 0.32, Math.PI * 0.22);
    const material = M.partDouble;

    return group(
        at(mesh(shell, material), 0, 0.5, 0),
        at(mesh(visor, M.dark), 0, 0.5, 0),
        [-0.88, 0.88].map((x) => at(mesh(cyl(0.18, 0.18, 0.1)), x, 0.4, 0, 0, 0, Math.PI / 2)),
        at(mesh(box(0.12, 0.25, 0.9, 0.05)), 0, 1.38, -0.1),
    );
}

function ring() {
    const prongs = Array.from({ length: 4 }, (_, i) => {
        const a = (i / 4) * Math.PI * 2 + Math.PI / 4;

        return at(mesh(cyl(0.025, 0.035, 0.3), M.metal), Math.cos(a) * 0.16, 1.62, Math.sin(a) * 0.16);
    });

    return group(
        at(mesh(torus(0.62, 0.09), M.metal), 0, 0.85, 0, 0, 0.4, 0),
        at(mesh(cyl(0.18, 0.12, 0.2), M.metal), 0, 1.5, 0),
        at(mesh(new THREE.OctahedronGeometry(0.24, 0), M.part), 0, 1.75, 0, 0, 0.4, 0),
        prongs,
    );
}

function keychain() {
    const tag = rrect(1.2, 1.6, 0.3);
    tag.holes.push(hole(0, 0.55, 0.12));

    const star = new THREE.Shape();

    for (let i = 0; i < 10; i++) {
        const r = i % 2 ? 0.16 : 0.38;
        const a = (i / 10) * Math.PI * 2 + Math.PI / 2;
        i ? star.lineTo(Math.cos(a) * r, Math.sin(a) * r) : star.moveTo(Math.cos(a) * r, Math.sin(a) * r);
    }

    return at(group(
        mesh(extrude(tag, 0.12)),
        at(mesh(extrude(star, 0.08, 0.015)), 0, -0.15, 0.08),
        at(mesh(torus(0.28, 0.03), M.metal), 0, 0.8, 0, 0, Math.PI / 2, 0),
    ), 0, 0.85, 0, -0.25, 0.4, 0);
}

function trophy() {
    return group(
        at(mesh(box(0.9, 0.3, 0.9, 0.04)), 0, 0.15, 0),
        mesh(lathe([[0.001, 0.3], [0.3, 0.3], [0.25, 0.38], [0.08, 0.45], [0.07, 0.9], [0.15, 1.0], [0.5, 1.15], [0.6, 1.7], [0.55, 1.72], [0.45, 1.25], [0.001, 1.15]])),
        [-1, 1].map((s) => at(mesh(torus(0.22, 0.04, Math.PI)), s * 0.58, 1.42, 0, 0, 0, -s * Math.PI / 2)),
    );
}

/* -------------------------------------------------------- registry --- */

const BUILDERS = {
    elbow, clampBracket, pushClip, boatFitting, cleat, ventGrille,
    droneFrame, cameraGimbal, landingGear, latticeBracket, antennaDome,
    gear, gripper, jig, gauge, roller, machineCover,
    impeller, nozzle, manifold, pipePlant, instrumentHousing, flange,
    enclosure, handheld, pcbHolder, knobPanel, watch, cableClip,
    building, masterplan, terrain, parametricPanel, lampshade, ornament,
    speaker, connectorJoint, handle, vase, planter,
    figure, toyCar, diorama, podium, retailStand, mascot,
    bone, teeth, surgicalGuide, splint, spine,
    robotArm, robotChassis, wheelRim, gearTrain, tubeRack, molecule,
    sculpture, eyewear, helmet, ring, keychain, trophy,
};

/**
 * Bangun satu part. Kunci yang tidak dikenal memakai roda gigi agar kartu
 * tidak pernah kosong.
 */
export function buildPart(key, materials) {
    M = materials;
    const build = BUILDERS[key] || gear;

    return build();
}
