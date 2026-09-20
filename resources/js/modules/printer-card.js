import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { STLLoader } from 'three/addons/loaders/STLLoader.js';
import { OBJLoader } from 'three/addons/loaders/OBJLoader.js';
import { ThreeMFLoader } from 'three/addons/loaders/3MFLoader.js';

import { analyzeGeometry, buildChecks, CHECK_STATUS } from './mesh-analysis';
import {
    estimate as calculateEstimate,
    formatCount,
    formatCurrency,
    formatLeadTime,
    formatNumber,
    formatPercent,
} from './print-estimator';
import { materialLabel } from './model-spec';
import { extensionOf, FORMAT_NAMES } from './model-formats';
import { estimateSupport, isSupportRequired, supportNote } from './support-estimator';
import { buildSupport, disposeSupport } from './support-builder';
import BuildPlate, { checkBuildVolume, createExceedMarkers, disposeExceedMarkers } from './build-plate';
import { clearPaint, paintOverhang, paintThickness, VIEW_MODES } from './mesh-paint';

const BRAND = 0x95271d;
const FALLBACK_COLOR = '#B8452F';

const SCALE_MIN = 10;
const SCALE_MAX = 400;

const show = (el, display = 'flex') => el && (el.style.display = display);
const hide = (el) => el && (el.style.display = 'none');

/**
 * Satu mesin printer beserta satu model di atasnya.
 *
 * Konsepnya: **1 printer = 1 build plate = 1 objek**. Setiap card memegang
 * scene, kamera, kontrol orbit, build plate, model, support, pengaturan
 * produksi, hasil analisis, dan estimasinya sendiri — tidak ada satu pun yang
 * dibagi dengan card lain. Mengubah material atau skala di satu card sama
 * sekali tidak menyentuh mesin lain.
 *
 * Kanvasnya digambar oleh SharedRenderer supaya sepuluh card tetap memakai
 * satu context WebGL.
 */
export default class PrinterCard {
    /**
     * @param {object} options
     * @param {HTMLElement} options.root elemen card hasil kloning template
     * @param {File} options.file berkas model
     * @param {ArrayBuffer} options.buffer isi berkas
     * @param {object} options.config printingConfig dari server
     * @param {import('./shared-renderer').default} options.renderer
     * @param {(card: PrinterCard) => void} options.onChange dipanggil setiap angka estimasi berubah
     * @param {(card: PrinterCard) => void} options.onRemove
     * @param {object} [options.state] keadaan tersimpan dari snapshot(), dipakai
     *        halaman viewer untuk melanjutkan pengaturan yang sudah dipilih
     */
    constructor({ root, file, buffer, bufferFormat, config, renderer, onChange, onRemove, state }) {
        this.root = root;
        this.file = file;
        this.config = config;
        this.renderer = renderer;
        this.onChange = onChange ?? (() => {});
        this.onRemove = onRemove ?? (() => {});

        // Format yang ditampilkan mengikuti berkas asli pengguna; format isi
        // buffer bisa berbeda untuk berkas CAD yang sudah ditesselasi.
        this.format = extensionOf(file.name).toUpperCase();
        this.bufferFormat = bufferFormat ?? null;

        // --- pengaturan milik mesin ini sendiri ---
        this.printerKey = config.defaultPrinter ?? Object.keys(config.printers ?? {})[0] ?? 'ender3';
        this.customVolume = { ...(config.printers?.[config.customPrinterKey]?.buildVolume ?? { x: 300, y: 300, z: 300 }) };

        this.displayMode = 'solid';
        this.viewMode = VIEW_MODES.MATERIAL;
        this.autoRotate = false;
        this.objectRotateMode = false;
        this.supportVisible = true;

        this.orientation = { x: 0, y: 0, z: 0, mirror: { x: 1, y: 1, z: 1 } };
        this.settings = this.defaultSettings();

        // Pengaturan yang sudah dipilih pengguna dipulihkan sebelum scene
        // dibangun, sehingga model langsung tampil pada skala, warna, dan
        // orientasi terakhirnya — bukan sempat berkedip di keadaan awal.
        this.restore(state);

        this.metrics = null;
        this.dimensions = null;
        this.boundingBox = null;
        this.worldBox = new THREE.Box3();
        this.analysis = null;
        this.estimate = null;
        this.fit = null;
        this.painted = null;
        this.support = null;
        this.supportStats = null;
        this.supportGroup = null;
        this.exceedGroup = null;

        this.cacheElements();
        this.initScene();
        this.buildModel(buffer);

        this.bindPrinter();
        this.bindToolbar();
        this.bindOrientation();
        this.bindScale();
        this.bindProduction();
        this.bindInfill();
        this.bindHollow();
        this.bindColors();
        this.bindFinishing();

        this.renderPrinter();
        this.syncControls();
        this.runAnalysis();
        this.refreshEstimate();
        this.frameBuildPlate();
    }

    /* ------------------------------------------------------------ elemen */

    cacheElements() {
        const q = (selector) => this.root.querySelector(selector);

        this.canvas = q('[data-card-canvas]');
        this.titleEl = q('[data-card-title]');
        this.fileNameEl = q('[data-card-file]');

        this.printerSelect = q('[data-printer-select]');
        this.printerNote = q('[data-printer-note]');
        this.printerCustomPanel = q('[data-printer-custom]');
        this.printerCustomInputs = this.root.querySelectorAll('[data-printer-custom-axis]');

        this.buildWarning = q('[data-build-warning]');
        this.buildWarningDetail = q('[data-build-warning-detail]');
        this.buildUsageBar = q('[data-build-usage-bar]');

        this.analysisList = q('[data-analysis-list]');
        this.analysisLegend = q('[data-analysis-legend]');

        this.technologySelect = q('[data-technology-select]');
        this.materialSelect = q('[data-material-select]');
        this.technologyDescription = q('[data-technology-description]');
        this.quantityInput = q('[data-quantity-input]');
        this.supportCheckbox = q('[data-support-toggle]');
        this.supportNoteEl = q('[data-support-note]');
        this.resolutionInputs = this.root.querySelectorAll('[data-resolution-option]');
        this.resolutionNotice = q('[data-resolution-notice]');

        this.scaleInput = q('[data-scale-input]');
        this.scaleSlider = q('[data-scale-slider]');
        this.infillDensityInputs = this.root.querySelectorAll('[data-infill-density]');
        this.infillPatternInputs = this.root.querySelectorAll('[data-infill-pattern]');
        this.infillNote = q('[data-infill-note]');

        this.hollowField = q('[data-hollow-field]');
        this.hollowToggle = q('[data-hollow-toggle]');
        this.hollowSettings = q('[data-hollow-settings]');
        this.hollowWall = q('[data-hollow-wall]');
        this.hollowDrain = q('[data-hollow-drain]');
        this.hollowPosition = q('[data-hollow-position]');

        this.colorSwatches = this.root.querySelectorAll('[data-material-color]');
        this.finishingSelect = q('[data-finishing-select]');
        this.finishingNote = q('[data-finishing-note]');
    }

    /**
     * Pulihkan keadaan hasil snapshot().
     *
     * Nilai yang tidak dikenal diabaikan begitu saja: data ini datang dari
     * penyimpanan browser yang bisa saja dibuat versi halaman sebelumnya.
     */
    restore(state) {
        if (!state || typeof state !== 'object') {
            return;
        }

        if (state.printer && this.config.printers?.[state.printer]) {
            this.printerKey = state.printer;
        }

        if (state.customVolume) {
            this.customVolume = { ...this.customVolume, ...state.customVolume };
        }

        if (state.settings) {
            this.settings = {
                ...this.settings,
                ...state.settings,
                hollow: { ...this.settings.hollow, ...(state.settings.hollow ?? {}) },
            };
        }

        if (state.orientation) {
            this.orientation = {
                ...this.orientation,
                ...state.orientation,
                mirror: { ...this.orientation.mirror, ...(state.orientation.mirror ?? {}) },
            };
        }

        if (state.viewMode) {
            this.viewMode = state.viewMode;
        }

        if (state.displayMode) {
            this.displayMode = state.displayMode;
        }
    }

    /**
     * Keadaan mesin ini dalam bentuk yang dapat disimpan.
     *
     * Hanya berisi data biasa (tanpa objek Three.js maupun elemen DOM) supaya
     * dapat dititipkan ke IndexedDB dan dipulihkan di tab lain.
     */
    snapshot() {
        return {
            printer: this.printerKey,
            customVolume: { ...this.customVolume },
            viewMode: this.viewMode,
            displayMode: this.displayMode,
            orientation: {
                x: this.orientation.x,
                y: this.orientation.y,
                z: this.orientation.z,
                mirror: { ...this.orientation.mirror },
            },
            settings: {
                ...this.settings,
                hollow: { ...this.settings.hollow },
            },
        };
    }

    /** Pengaturan awal mesin ini. */
    defaultSettings() {
        const technologies = Object.keys(this.config.technologies ?? {});
        const code = technologies[0] ?? 'FDM';
        const technology = this.config.technologies?.[code];

        return {
            technology: code,
            material: technology?.materials?.[0]?.name ?? '',
            quantity: 1,
            resolution: this.config.defaultResolution ?? '0.25',
            support: Boolean(this.config.support?.defaultEnabled),
            scale: 1,
            infillDensity: Number(technology?.defaultInfill ?? 1),
            infillPattern: this.config.infill?.defaultPattern ?? 'grid',
            color: this.config.materialColors?.default ?? 'merah',
            finishing: this.config.finishing?.default ?? 'none',
            hollow: {
                enabled: false,
                wallThicknessMm: Number(this.config.hollow?.wallThickness?.default ?? 2),
                drainDiameterMm: Number(this.config.hollow?.drainDiameter?.default ?? 3.5),
                drainPosition: this.config.hollow?.defaultDrainPosition ?? 'bottom',
            },
        };
    }

    /* ------------------------------------------------------------- scene */

    initScene() {
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xf8f5f4);

        this.camera = new THREE.PerspectiveCamera(45, 1, 0.1, 5000);
        this.camera.position.set(120, 90, 160);

        this.controls = new OrbitControls(this.camera, this.canvas);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.08;
        this.controls.rotateSpeed = 0.85;
        this.controls.panSpeed = 0.8;
        this.controls.zoomSpeed = 0.9;
        this.controls.screenSpacePanning = true;
        this.controls.minDistance = 1;
        this.controls.maxDistance = 4000;

        this.scene.add(new THREE.HemisphereLight(0xffffff, 0xd9cfcb, 1.15));

        const key = new THREE.DirectionalLight(0xffffff, 2.1);
        key.position.set(1, 1.6, 1.2);
        this.scene.add(key);

        const fill = new THREE.DirectionalLight(0xffe6de, 0.7);
        fill.position.set(-1.4, 0.5, -0.8);
        this.scene.add(fill);

        const rim = new THREE.DirectionalLight(BRAND, 1.1);
        rim.position.set(-0.6, -1, 1.4);
        this.scene.add(rim);

        // Build plate milik mesin ini, seukuran area cetaknya sendiri.
        this.plate = new BuildPlate(this.scene, this.buildVolume());

        this.view = this.renderer.register({
            canvas: this.canvas,
            scene: this.scene,
            camera: this.camera,
            controls: this.controls,
            isAnimating: () => this.autoRotate,
        });

        this.controls.addEventListener('change', () => this.renderer.invalidate(this.view));
    }

    /** Minta viewer digambar ulang pada frame berikutnya. */
    invalidate() {
        this.controls.autoRotate = this.autoRotate;
        this.controls.autoRotateSpeed = 1.6;
        this.renderer.invalidate(this.view);
    }

    /* ------------------------------------------------------------- model */

    buildModel(buffer) {
        const color = this.colorHex(this.settings.color);

        // `bufferFormat` dipakai berkas CAD: yang tersimpan tetap STEP aslinya,
        // sedangkan yang digambar adalah hasil tesselasinya dalam bentuk STL.
        const extension = this.bufferFormat ?? extensionOf(this.file.name);

        if (extension === 'obj') {
            this.object = this.buildFromObj(buffer, color);
        } else if (extension === '3mf') {
            this.object = this.buildFrom3mf(buffer, color);
        } else {
            this.object = this.buildFromStl(buffer, color);
        }

        // Geometri dipusatkan lalu dibungkus pivot supaya rotasi dan skala
        // selalu terjadi di sekitar pusat modelnya sendiri.
        const box = new THREE.Box3().setFromObject(this.object);
        const center = box.getCenter(new THREE.Vector3());
        this.object.position.sub(center);

        this.pivot = new THREE.Group();
        this.pivot.add(this.object);
        this.scene.add(this.pivot);

        this.applyOrientation({ refresh: false });
    }

    buildFromStl(buffer, color) {
        const geometry = new STLLoader().parse(buffer);

        if (!geometry.getAttribute('position') || geometry.getAttribute('position').count === 0) {
            throw new Error('Geometri STL kosong');
        }

        if (!geometry.getAttribute('normal')) {
            geometry.computeVertexNormals();
        }

        return new THREE.Mesh(geometry, this.createMaterial(color));
    }

    buildFromObj(buffer, color) {
        const text = new TextDecoder().decode(buffer);

        return this.dressGroup(new OBJLoader().parse(text), color, 'OBJ');
    }

    /**
     * 3MF membawa satuan, transformasi, dan susunan objeknya sendiri, jadi
     * loader bawaan three.js dipakai apa adanya lalu hasilnya diseragamkan
     * seperti OBJ — satu grup berisi mesh dengan material milik card ini.
     */
    buildFrom3mf(buffer, color) {
        return this.dressGroup(new ThreeMFLoader().parse(buffer), color, '3MF');
    }

    /** Samakan material seluruh mesh di dalam grup, sekaligus pastikan isinya ada. */
    dressGroup(object, color, label) {
        let hasGeometry = false;

        object.traverse((child) => {
            if (!child.isMesh) {
                return;
            }

            if (child.geometry?.getAttribute('position')?.count) {
                hasGeometry = true;
            }

            if (!child.geometry.getAttribute('normal')) {
                child.geometry.computeVertexNormals();
            }

            child.material = this.createMaterial(color);
        });

        if (!hasGeometry) {
            throw new Error(`Tidak ada mesh pada file ${label}`);
        }

        return object;
    }

    createMaterial(color) {
        const transparent = this.displayMode === 'transparent';

        return new THREE.MeshStandardMaterial({
            color: new THREE.Color(color ?? FALLBACK_COLOR),
            roughness: 0.52,
            metalness: 0.18,
            wireframe: this.displayMode === 'wireframe',
            transparent,
            opacity: transparent ? 0.38 : 1,
            depthWrite: !transparent,
            side: THREE.DoubleSide,
        });
    }

    colorHex(key) {
        return this.config.materialColors?.options?.[key]?.hex ?? FALLBACK_COLOR;
    }

    /* ----------------------------------------------------------- printer */

    printerConfig() {
        return this.config.printers?.[this.printerKey]
            ?? { name: 'Printer', buildVolume: { x: 220, y: 220, z: 250 }, speedFactor: 1, rateFactor: 1 };
    }

    isCustomPrinter() {
        return Boolean(this.printerConfig().custom);
    }

    /** Ukuran area cetak yang berlaku, termasuk isian Custom. */
    buildVolume() {
        const printer = this.printerConfig();

        if (!printer.custom) {
            return { ...printer.buildVolume };
        }

        const limits = this.config.customPrinterLimits ?? { min: 50, max: 1000 };
        const clamp = (value, fallback) => {
            const numeric = Number(value);

            return Number.isFinite(numeric) ? Math.min(limits.max, Math.max(limits.min, numeric)) : fallback;
        };

        return {
            x: clamp(this.customVolume.x, printer.buildVolume.x),
            y: clamp(this.customVolume.y, printer.buildVolume.y),
            z: clamp(this.customVolume.z, printer.buildVolume.z),
        };
    }

    bindPrinter() {
        this.printerSelect?.addEventListener('change', () => {
            this.printerKey = this.printerSelect.value;
            this.renderPrinter();
            this.applyPrinter();
        });

        this.printerCustomInputs.forEach((input) => {
            input.addEventListener('input', () => {
                this.customVolume[input.dataset.printerCustomAxis] = input.value;
                this.applyPrinter();
            });
        });
    }

    renderPrinter() {
        const printer = this.printerConfig();

        if (this.printerSelect) {
            this.printerSelect.value = this.printerKey;
        }

        if (this.printerNote) {
            this.printerNote.textContent = printer.note ?? '';
        }

        this.isCustomPrinter() ? show(this.printerCustomPanel, 'block') : hide(this.printerCustomPanel);
    }

    /** Ganti mesin: build plate, validasi ukuran, waktu, dan biaya ikut berubah. */
    applyPrinter() {
        this.plate.setVolume(this.buildVolume());
        this.applyOrientation();
        this.frameBuildPlate();
    }

    /* --------------------------------------------------------- orientasi */

    /**
     * Terapkan rotasi, pencerminan, dan skala, lalu dudukkan model tepat di
     * tengah build plate mesin ini.
     *
     * Karena satu plate hanya berisi satu objek, modelnya selalu berdiri di
     * titik origin — tidak perlu penataan posisi sama sekali.
     */
    applyOrientation({ refresh = true, precise = true } = {}) {
        if (!this.pivot) {
            return;
        }

        const { x, y, z, mirror } = this.orientation;
        const scale = Math.max(0.01, Number(this.settings.scale) || 1);
        const toRad = (deg) => (deg * Math.PI) / 180;

        this.pivot.position.set(0, 0, 0);
        this.pivot.rotation.set(toRad(x), toRad(y), toRad(z));
        this.pivot.scale.set(mirror.x * scale, mirror.y * scale, mirror.z * scale);
        this.pivot.updateMatrixWorld(true);

        // Mode cepat hanya dipakai selama drag berlangsung: hasilnya
        // melebih-lebihkan pada objek yang diputar sehingga modelnya akan
        // mengambang, dan selalu diperbaiki begitu drag selesai.
        const box = new THREE.Box3().setFromObject(this.pivot, precise);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());

        this.pivot.position.x -= center.x;
        this.pivot.position.z -= center.z;
        this.pivot.position.y -= box.min.y;
        this.pivot.updateMatrixWorld(true);

        this.dimensions = { x: size.x, y: size.y, z: size.z };
        this.boundingBox = {
            min: { x: -size.x / 2, y: 0, z: -size.z / 2 },
            max: { x: size.x / 2, y: size.y, z: size.z / 2 },
        };
        this.worldBox.setFromCenterAndSize(new THREE.Vector3(0, size.y / 2, 0), size);

        this.refreshFit();
        this.invalidate();

        if (!refresh) {
            return;
        }

        this.repaint();
        this.refreshAnalysis();
        this.refreshEstimate();
        this.renderStats();
        this.renderOrientationInputs();
    }

    bindOrientation() {
        this.root.querySelectorAll('[data-rotate-step]').forEach((button) => {
            button.addEventListener('click', () => {
                this.orientation[button.dataset.axis] = normalizeAngle(
                    this.orientation[button.dataset.axis] + Number(button.dataset.rotateStep)
                );
                this.applyOrientation();
            });
        });

        this.root.querySelectorAll('[data-rotate-slider]').forEach((slider) => {
            slider.addEventListener('input', () => {
                this.orientation[slider.dataset.rotateSlider] = normalizeAngle(Number(slider.value) || 0);
                this.applyOrientation();
            });
        });

        this.root.querySelectorAll('[data-mirror]').forEach((button) => {
            button.addEventListener('click', () => {
                this.orientation.mirror[button.dataset.mirror] *= -1;
                this.applyOrientation();
            });
        });

        this.root.querySelector('[data-action="reset-orientation"]')?.addEventListener('click', () => {
            this.orientation = { x: 0, y: 0, z: 0, mirror: { x: 1, y: 1, z: 1 } };
            this.applyOrientation();
        });

        this.objectRotateToggle = this.root.querySelector('[data-action="rotate-object-mode"]');

        this.objectRotateToggle?.addEventListener('click', () => {
            this.objectRotateMode = !this.objectRotateMode;
            this.objectRotateToggle.setAttribute('aria-pressed', String(this.objectRotateMode));
            this.controls.enableRotate = !this.objectRotateMode;
            this.canvas.style.cursor = this.objectRotateMode ? 'grab' : '';
        });

        this.bindObjectDrag();
    }

    /** Drag pada kanvas untuk memutar objeknya, bukan kameranya. */
    bindObjectDrag() {
        let dragging = false;
        let lastX = 0;
        let lastY = 0;

        this.canvas.addEventListener('pointerdown', (event) => {
            if (!this.objectRotateMode || event.button !== 0) {
                return;
            }

            dragging = true;
            lastX = event.clientX;
            lastY = event.clientY;
            this.canvas.style.cursor = 'grabbing';
            this.canvas.setPointerCapture(event.pointerId);
        });

        this.canvas.addEventListener('pointermove', (event) => {
            if (!dragging) {
                return;
            }

            this.orientation.y = normalizeAngle(this.orientation.y + (event.clientX - lastX) * 0.6);
            this.orientation.x = normalizeAngle(this.orientation.x + (event.clientY - lastY) * 0.6);
            lastX = event.clientX;
            lastY = event.clientY;

            this.applyOrientation({ refresh: false, precise: false });
            this.renderOrientationInputs();
        });

        const endDrag = (event) => {
            if (!dragging) {
                return;
            }

            dragging = false;
            this.canvas.style.cursor = this.objectRotateMode ? 'grab' : '';

            try {
                this.canvas.releasePointerCapture(event.pointerId);
            } catch {
                // pointer sudah lepas
            }

            this.applyOrientation();
        };

        this.canvas.addEventListener('pointerup', endDrag);
        this.canvas.addEventListener('pointercancel', endDrag);
    }

    renderOrientationInputs() {
        ['x', 'y', 'z'].forEach((axis) => {
            const slider = this.root.querySelector(`[data-rotate-slider="${axis}"]`);
            const value = this.root.querySelector(`[data-rotate-value="${axis}"]`);
            const mirrorButton = this.root.querySelector(`[data-mirror="${axis}"]`);

            if (slider) {
                slider.value = String(Math.round(this.orientation[axis]));
            }

            if (value) {
                value.textContent = `${Math.round(this.orientation[axis])}°`;
            }

            if (mirrorButton) {
                mirrorButton.setAttribute('aria-pressed', String(this.orientation.mirror[axis] === -1));
            }
        });
    }

    /* -------------------------------------------------------- build plate */

    /** Periksa model terhadap batas area cetak mesin ini. */
    refreshFit() {
        disposeExceedMarkers(this.exceedGroup);
        this.exceedGroup = null;

        this.fit = checkBuildVolume(this.worldBox, this.plate.bounds);

        if (this.fit.exceeds) {
            this.exceedGroup = createExceedMarkers(this.fit.regions);
            this.scene.add(this.exceedGroup);
        }

        this.plate.setExceeded(this.fit.exceeds);
        this.renderBuildInfo();
    }

    renderBuildInfo() {
        const volume = this.plate.size;
        const capacity = volume.width * volume.depth * volume.height;

        this.setText('[data-build="printer"]', `${formatNumber(volume.width, 0)} × ${formatNumber(volume.depth, 0)} × ${formatNumber(volume.height, 0)} mm`);
        this.setText(
            '[data-build="model"]',
            this.dimensions
                ? `${formatNumber(this.dimensions.x, 0)} × ${formatNumber(this.dimensions.z, 0)} × ${formatNumber(this.dimensions.y, 0)} mm`
                : '-'
        );

        const used = this.dimensions ? this.dimensions.x * this.dimensions.y * this.dimensions.z : 0;
        const ratio = capacity > 0 ? used / capacity : 0;

        this.setText('[data-build="usage"]', formatPercent(Math.min(ratio, 9.99), ratio < 0.1 ? 1 : 0));

        if (this.buildUsageBar) {
            this.buildUsageBar.style.width = `${Math.min(100, ratio * 100)}%`;
            this.buildUsageBar.classList.toggle('bg-brand-600', ratio <= 1);
            this.buildUsageBar.classList.toggle('bg-red-600', ratio > 1);
        }

        if (!this.buildWarning) {
            return;
        }

        if (!this.fit?.exceeds) {
            hide(this.buildWarning);

            return;
        }

        if (this.buildWarningDetail) {
            this.buildWarningDetail.textContent =
                `${this.file.name} melewati area cetak ${formatNumber(volume.width, 0)} × ` +
                `${formatNumber(volume.depth, 0)} × ${formatNumber(volume.height, 0)} mm.`;
        }

        show(this.buildWarning, 'block');
    }

    /* -------------------------------------------------------------- skala */

    bindScale() {
        const apply = (percent) => this.setScale(percent);

        this.scaleInput?.addEventListener('input', () => apply(this.scaleInput.value));
        this.scaleSlider?.addEventListener('input', () => apply(this.scaleSlider.value));

        this.root.querySelectorAll('[data-scale-step]').forEach((button) => {
            button.addEventListener('click', () => {
                apply(Math.round(this.settings.scale * 100) + Number(button.dataset.scaleStep));
            });
        });

        this.root.querySelectorAll('[data-scale-preset]').forEach((button) => {
            button.addEventListener('click', () => apply(button.dataset.scalePreset));
        });
    }

    setScale(percent) {
        const value = Math.min(SCALE_MAX, Math.max(SCALE_MIN, Math.round(Number(percent) || 100)));

        this.settings.scale = value / 100;
        this.renderScaleInputs();
        this.applyOrientation();
    }

    renderScaleInputs() {
        const percent = Math.round(this.settings.scale * 100);

        if (this.scaleInput && document.activeElement !== this.scaleInput) {
            this.scaleInput.value = String(percent);
        }

        if (this.scaleSlider) {
            this.scaleSlider.value = String(percent);
        }

        this.root.querySelectorAll('[data-scale-preset]').forEach((button) => {
            button.setAttribute('aria-pressed', String(Number(button.dataset.scalePreset) === percent));
        });
    }

    /* ------------------------------------------------------------- infill */

    bindInfill() {
        this.infillDensityInputs.forEach((input) => {
            input.addEventListener('change', () => {
                this.settings.infillDensity = Number(input.value);
                this.refreshEstimate();
            });
        });

        this.infillPatternInputs.forEach((input) => {
            input.addEventListener('change', () => {
                this.settings.infillPattern = input.value;
                this.refreshEstimate();
            });
        });
    }

    renderInfillInputs() {
        const density = Number(this.settings.infillDensity);

        this.infillDensityInputs.forEach((input) => {
            input.checked = Math.abs(Number(input.value) - density) < 1e-6;
        });

        this.infillPatternInputs.forEach((input) => {
            input.checked = input.value === this.settings.infillPattern;
        });

        const note = this.technology()?.infillNote ?? null;

        if (this.infillNote) {
            this.infillNote.textContent = note ?? '';
            note ? show(this.infillNote, 'block') : hide(this.infillNote);
        }
    }

    /* ------------------------------------------------------------- hollow */

    bindHollow() {
        this.hollowToggle?.addEventListener('change', () => {
            this.settings.hollow.enabled = this.hollowToggle.checked;
            this.renderHollowInputs();
            this.refreshEstimate();
        });

        [
            [this.hollowWall, 'wallThicknessMm'],
            [this.hollowDrain, 'drainDiameterMm'],
        ].forEach(([input, key]) => {
            input?.addEventListener('input', () => {
                this.settings.hollow[key] = Number(input.value);
                this.renderHollowInputs();
                this.refreshEstimate();
            });
        });

        this.hollowPosition?.addEventListener('change', () => {
            this.settings.hollow.drainPosition = this.hollowPosition.value;
            this.renderHollowInputs();
            this.refreshEstimate();
        });
    }

    syncHollowAvailability() {
        const allowed = Boolean(this.technology()?.allowsHollow);

        allowed ? show(this.hollowField, 'block') : hide(this.hollowField);

        if (!allowed) {
            this.settings.hollow.enabled = false;
        }
    }

    renderHollowInputs() {
        const hollow = this.settings.hollow;

        if (!this.hollowToggle) {
            return;
        }

        this.hollowToggle.checked = Boolean(hollow.enabled);
        hollow.enabled ? show(this.hollowSettings, 'block') : hide(this.hollowSettings);

        if (this.hollowWall) {
            this.hollowWall.value = String(hollow.wallThicknessMm);
        }

        if (this.hollowDrain) {
            this.hollowDrain.value = String(hollow.drainDiameterMm);
        }

        if (this.hollowPosition) {
            this.hollowPosition.value = hollow.drainPosition;
        }

        this.setText('[data-hollow-value="wall"]', `${formatNumber(hollow.wallThicknessMm, 1)} mm`);
        this.setText('[data-hollow-value="drain"]', `${formatNumber(hollow.drainDiameterMm, 1)} mm`);
        this.setText(
            '[data-hollow-position-note]',
            this.hollowPosition?.selectedOptions?.[0]?.dataset.description ?? ''
        );

        this.renderHollowSaving();
    }

    renderHollowSaving() {
        const el = this.root.querySelector('[data-hollow-saving]');

        if (!el) {
            return;
        }

        if (!this.estimate?.hollowEnabled) {
            el.textContent = 'Aktifkan untuk melihat berapa banyak resin yang dihemat.';

            return;
        }

        const ratio = this.estimate.modelVolumeCm3 > 0 ? this.estimate.hollowSavedCm3 / this.estimate.modelVolumeCm3 : 0;

        el.textContent =
            `Menghemat ${formatNumber(this.estimate.hollowSavedCm3, 2)} cm³ resin (${formatPercent(ratio, 0)} dari volume padat) ` +
            `pada dinding ${formatNumber(this.estimate.hollowWallThicknessMm ?? 0, 1)} mm.`;
    }

    /* -------------------------------------------------------------- warna */

    bindColors() {
        this.colorSwatches.forEach((swatch) => {
            swatch.addEventListener('click', () => {
                this.settings.color = swatch.dataset.materialColor;
                this.renderColorInputs();
                this.applyAppearance();
                this.onChange(this);
            });
        });
    }

    /**
     * Warna yang benar-benar tersedia untuk material yang sedang dipilih.
     * Resin bening hanya tersedia bening, part logam hanya warna aslinya.
     */
    availableColors() {
        const allowed = this.material()?.colors ?? [];

        return allowed.length ? allowed : Object.keys(this.config.materialColors?.options ?? {});
    }

    renderColorInputs() {
        const available = this.availableColors();

        // Pilihan yang tidak tersedia disembunyikan, bukan sekadar dinonaktifkan,
        // supaya deretan swatch tetap ringkas.
        this.colorSwatches.forEach((swatch) => {
            const key = swatch.dataset.materialColor;
            const offered = available.includes(key);

            swatch.style.display = offered ? '' : 'none';
            swatch.setAttribute('aria-pressed', String(offered && key === this.settings.color));
        });
    }

    /** Pastikan warna yang dipilih memang tersedia pada materialnya. */
    syncColorAvailability() {
        const available = this.availableColors();

        if (!available.includes(this.settings.color)) {
            const preferred = this.config.materialColors?.default;

            this.settings.color = available.includes(preferred) ? preferred : available[0];
            this.applyAppearance();
        }

        this.renderColorInputs();
    }

    /* ---------------------------------------------------------- finishing */

    bindFinishing() {
        this.finishingSelect?.addEventListener('change', () => {
            this.settings.finishing = this.finishingSelect.value;
            this.renderFinishingNote();
            this.refreshEstimate();
        });
    }

    renderFinishingInputs() {
        if (this.finishingSelect) {
            this.finishingSelect.value = this.settings.finishing;
        }

        this.renderFinishingNote();
    }

    renderFinishingNote() {
        if (!this.finishingNote) {
            return;
        }

        const option = this.config.finishing?.options?.[this.settings.finishing];

        this.finishingNote.textContent = option?.description
            ?? 'Pengerjaan setelah part selesai dicetak.';
    }

    /* --------------------------------------------------- mode tampilan */

    bindToolbar() {
        this.root.querySelector('[data-action="reset"]')?.addEventListener('click', () => this.frameBuildPlate());
        this.root.querySelector('[data-action="focus"]')?.addEventListener('click', () => this.focusModel());

        this.root.querySelector('[data-action="rotate"]')?.addEventListener('click', (event) => {
            this.autoRotate = !this.autoRotate;
            event.currentTarget.setAttribute('aria-pressed', String(this.autoRotate));
            this.invalidate();
        });

        this.root.querySelector('[data-action="grid"]')?.addEventListener('click', (event) => {
            this.plate.setGridVisible(!this.plate.gridVisible);
            event.currentTarget.setAttribute('aria-pressed', String(this.plate.gridVisible));
            this.invalidate();
        });

        this.root.querySelector('[data-action="axis"]')?.addEventListener('click', (event) => {
            this.plate.setAxesVisible(!this.plate.axesVisible);
            event.currentTarget.setAttribute('aria-pressed', String(this.plate.axesVisible));
            this.invalidate();
        });

        this.root.querySelector('[data-action="support-visibility"]')?.addEventListener('click', (event) => {
            this.supportVisible = !this.supportVisible;
            event.currentTarget.setAttribute('aria-pressed', String(this.supportVisible));

            if (this.supportGroup) {
                this.supportGroup.visible = this.supportVisible;
            }

            this.invalidate();
        });

        this.root.querySelector('[data-action="remove"]')?.addEventListener('click', () => this.onRemove(this));

        this.root.querySelectorAll('[data-mode]').forEach((button) => {
            button.addEventListener('click', () => this.setDisplayMode(button.dataset.mode));
        });

        this.root.querySelectorAll('[data-view-mode]').forEach((button) => {
            button.addEventListener('click', () => this.setViewMode(button.dataset.viewMode));
        });

        this.root.querySelectorAll('[data-view]').forEach((button) => {
            button.addEventListener('click', () => this.applyViewPreset(button.dataset.view));
        });
    }

    setDisplayMode(mode) {
        this.displayMode = mode;

        this.root.querySelectorAll('[data-mode]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.mode === mode));
        });

        this.applyAppearance();
    }

    setViewMode(mode) {
        if (this.viewMode === mode) {
            return;
        }

        this.viewMode = mode;

        this.root.querySelectorAll('[data-view-mode]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.viewMode === mode));
        });

        this.repaint();
    }

    /** Warnai ulang model sesuai mode analisis yang aktif pada mesin ini. */
    repaint() {
        if (!this.object) {
            return;
        }

        clearPaint(this.object);
        this.painted = null;

        const analysis = this.config.analysis ?? {};

        if (this.viewMode === VIEW_MODES.OVERHANG) {
            this.painted = {
                mode: VIEW_MODES.OVERHANG,
                ...paintOverhang(this.object, {
                    safeDeg: analysis.overhang?.safeDeg,
                    warnDeg: analysis.overhang?.warnDeg,
                    colors: analysis.overhang?.colors,
                }),
            };
        } else if (this.viewMode === VIEW_MODES.THICKNESS) {
            const maxTriangles = Number(analysis.wallThickness?.maxTriangles ?? 250000);

            if ((this.metrics?.triangles ?? 0) > maxTriangles) {
                this.painted = { mode: VIEW_MODES.THICKNESS, skipped: true };
            } else {
                const minimum = this.minWallThickness();

                this.painted = {
                    mode: VIEW_MODES.THICKNESS,
                    minWallMm: minimum,
                    ...paintThickness(this.object, {
                        minMm: minimum,
                        colors: analysis.wallThickness?.colors,
                        maxSamples: analysis.wallThickness?.maxSamples,
                    }),
                };
            }
        }

        this.applyAppearance();
        this.renderAnalysisLegend();
    }

    minWallThickness() {
        return Number(this.technology()?.minWallThicknessMm ?? 1);
    }

    applyAppearance() {
        if (!this.object) {
            return;
        }

        const painted = Boolean(this.painted) && !this.painted.skipped;
        const transparent = this.displayMode === 'transparent';
        const color = painted ? '#FFFFFF' : this.colorHex(this.settings.color);

        this.object.traverse((child) => {
            if (!child.isMesh) {
                return;
            }

            child.material.color.set(color);
            child.material.wireframe = this.displayMode === 'wireframe';
            child.material.transparent = transparent;
            child.material.opacity = transparent ? 0.38 : 1;
            child.material.depthWrite = !transparent;
            child.material.needsUpdate = true;
        });

        this.invalidate();
    }

    renderAnalysisLegend() {
        if (!this.analysisLegend) {
            return;
        }

        if (this.viewMode === VIEW_MODES.MATERIAL) {
            hide(this.analysisLegend);

            return;
        }

        const analysis = this.config.analysis ?? {};
        const title = this.analysisLegend.querySelector('[data-analysis-legend-title]');
        const list = this.analysisLegend.querySelector('[data-analysis-legend-items]');
        const note = this.analysisLegend.querySelector('[data-analysis-legend-note]');

        let heading;
        let entries;
        let footnote = '';

        if (this.viewMode === VIEW_MODES.OVERHANG) {
            const safe = analysis.overhang?.safeDeg ?? 45;
            const warn = analysis.overhang?.warnDeg ?? 60;
            const colors = analysis.overhang?.colors ?? {};

            heading = 'Overhang Analysis';
            entries = [
                [colors.safe ?? '#3FA45B', `Aman: di bawah ${safe}°`],
                [colors.warn ?? '#E0A82E', `Mungkin perlu support: ${safe}° sampai ${warn}°`],
                [colors.critical ?? '#C0392B', `Wajib support: di atas ${warn}°`],
            ];
            footnote = 'Sudut diukur dari bidang tegak: dinding tegak 0°, langit-langit mendatar 90°.';
        } else {
            const colors = analysis.wallThickness?.colors ?? {};
            const minimum = this.minWallThickness();

            heading = 'Wall Thickness Analysis';
            entries = [
                [colors.safe ?? '#3FA45B', `Aman: ${formatNumber(minimum, 1)} mm ke atas`],
                [colors.thin ?? '#C0392B', `Terlalu tipis: di bawah ${formatNumber(minimum, 1)} mm`],
            ];

            if (this.painted?.skipped) {
                footnote = 'Model ini terlalu rapat untuk diukur seketika, jadi pewarnaannya dilewati.';
            } else if (this.painted?.sampled) {
                footnote = `Diukur dari ${formatCount(this.painted.measured)} titik sampel; dinding tertipis ${formatNumber(this.painted.minMm, 2)} mm.`;
            } else if (this.painted?.minMm !== undefined) {
                footnote = `Dinding tertipis yang terukur ${formatNumber(this.painted.minMm, 2)} mm.`;
            }
        }

        if (title) {
            title.textContent = heading;
        }

        if (list) {
            list.innerHTML = entries
                .map(
                    ([color, label]) => `
                        <li class="flex items-center gap-2 text-[0.65rem] font-semibold text-white">
                            <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-sm" style="background-color: ${escapeAttribute(color)}"></span>
                            ${escapeHtml(label)}
                        </li>
                    `
                )
                .join('');
        }

        if (note) {
            note.textContent = footnote;
        }

        show(this.analysisLegend, 'block');
    }

    /* ------------------------------------------------------------ kamera */

    frameBuildPlate() {
        const { width, depth, height } = this.plate.size;
        const size = new THREE.Vector3(width, height, depth);

        this.homeTarget = new THREE.Vector3(0, height / 4, 0);
        this.homeDirection = new THREE.Vector3(0.85, 0.62, 1).normalize();
        this.frameRadius = Math.max(size.length() / 2, 0.5);
        this.frameDistance = (this.frameRadius / Math.sin((this.camera.fov * Math.PI) / 360)) * 1.15;

        this.camera.near = Math.max(this.frameDistance / 1000, 0.01);
        this.camera.far = this.frameDistance * 100;

        this.applyViewDirection(this.homeDirection);
    }

    focusModel() {
        if (!this.dimensions) {
            return;
        }

        const size = new THREE.Vector3(this.dimensions.x, this.dimensions.y, this.dimensions.z);

        this.homeTarget = new THREE.Vector3(0, size.y / 2, 0);
        this.frameRadius = Math.max(size.length() / 2, 0.5);
        this.frameDistance = (this.frameRadius / Math.sin((this.camera.fov * Math.PI) / 360)) * 1.25;

        this.camera.near = Math.max(this.frameDistance / 1000, 0.01);
        this.camera.far = Math.max(this.frameDistance * 100, this.plate.size.width * 20);

        this.applyViewDirection(this.homeDirection ?? new THREE.Vector3(0.85, 0.62, 1).normalize());
    }

    applyViewDirection(direction) {
        if (!this.homeTarget || !direction) {
            return;
        }

        this.homeDirection = direction.clone().normalize();

        this.camera.position.copy(this.homeDirection).multiplyScalar(this.frameDistance).add(this.homeTarget);
        this.camera.updateProjectionMatrix();
        this.controls.target.copy(this.homeTarget);
        this.controls.update();
        this.invalidate();
    }

    applyViewPreset(preset) {
        const directions = {
            front: [0, 0, 1],
            back: [0, 0, -1],
            right: [1, 0, 0],
            left: [-1, 0, 0],
            top: [0.0001, 1, 0.0001],
            bottom: [0.0001, -1, 0.0001],
            isometric: [0.85, 0.62, 1],
        };

        const direction = directions[preset];

        if (direction) {
            this.applyViewDirection(new THREE.Vector3(...direction));
        }
    }

    /* ---------------------------------------------------------- produksi */

    bindProduction() {
        this.populateTechnologies();

        this.technologySelect?.addEventListener('change', () => {
            this.settings.technology = this.technologySelect.value;
            this.populateMaterials();
            this.settings.material = this.materialSelect.value;
            this.settings.infillDensity = Number(this.technology()?.defaultInfill ?? 1);

            this.renderTechnologyDescription();
            this.syncSupportAvailability();
            this.syncHollowAvailability();
            this.syncColorAvailability();
            this.renderInfillInputs();
            this.renderHollowInputs();
            this.renderResolutionNotice();
            this.refreshAnalysis();
            this.refreshEstimate();
            this.repaint();
        });

        this.materialSelect?.addEventListener('change', () => {
            this.settings.material = this.materialSelect.value;
            // Warna mengikuti material: pilihan yang tidak tersedia diganti
            // dengan warna terdekat yang memang ada.
            this.syncColorAvailability();
            this.refreshEstimate();
        });

        this.quantityInput?.addEventListener('input', () => {
            this.settings.quantity = Math.max(1, parseInt(this.quantityInput.value, 10) || 1);
            this.refreshEstimate();
        });

        this.supportCheckbox?.addEventListener('change', () => {
            this.settings.support = Boolean(this.supportCheckbox.checked && !this.supportCheckbox.disabled);
            this.refreshEstimate();
        });

        this.resolutionInputs.forEach((input) => {
            input.addEventListener('change', () => {
                this.settings.resolution = input.value;
                this.renderResolutionNotice();
                this.refreshEstimate();
            });
        });
    }

    /** Kembalikan seluruh kontrol ke pengaturan mesin ini. */
    syncControls() {
        if (this.titleEl) {
            this.titleEl.textContent = `Printer ${this.position ?? 1}`;
        }

        if (this.fileNameEl) {
            this.fileNameEl.textContent = this.file.name;
        }

        if (this.technologySelect) {
            this.technologySelect.value = this.settings.technology;
            this.populateMaterials();
            this.materialSelect.value = this.settings.material;
        }

        if (this.quantityInput) {
            this.quantityInput.value = String(this.settings.quantity);
        }

        this.resolutionInputs.forEach((input) => {
            input.checked = input.value === this.settings.resolution;
        });

        if (this.supportCheckbox) {
            this.supportCheckbox.checked = Boolean(this.settings.support);
        }

        this.renderScaleInputs();
        this.renderInfillInputs();
        this.syncColorAvailability();
        this.renderFinishingInputs();
        this.renderTechnologyDescription();
        this.syncSupportAvailability();
        this.syncHollowAvailability();
        this.renderHollowInputs();
        this.renderResolutionNotice();
        this.renderOrientationInputs();
    }

    populateTechnologies() {
        if (!this.technologySelect) {
            return;
        }

        this.technologySelect.innerHTML = Object.keys(this.config.technologies ?? {})
            .map((code) => `<option value="${code}">${code} (${escapeHtml(this.config.technologies[code].name)})</option>`)
            .join('');

        this.populateMaterials();
    }

    populateMaterials() {
        if (!this.materialSelect) {
            return;
        }

        // Nilai option tetap nama katalog — itulah yang dikirim ke server dan
        // menentukan harga; yang dibaca pelanggan hanya nama jenis bahannya.
        this.materialSelect.innerHTML = (this.technology()?.materials ?? [])
            .map((material) => `<option value="${escapeHtml(material.name)}">${escapeHtml(materialLabel(material))}</option>`)
            .join('');
    }

    renderTechnologyDescription() {
        const technology = this.technology();

        if (!this.technologyDescription || !technology) {
            return;
        }

        const build = technology.buildVolume;

        this.technologyDescription.innerHTML = `
            <p class="text-sm leading-relaxed text-ink-600">${escapeHtml(technology.description)}</p>
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">
                Kapasitas teknologi ${build.x} × ${build.y} × ${build.z} mm
            </p>
        `;
    }

    syncSupportAvailability() {
        if (!this.supportCheckbox) {
            return;
        }

        const technology = this.technology();
        const required = isSupportRequired(technology);

        this.supportCheckbox.disabled = !required;

        if (!required) {
            this.supportCheckbox.checked = false;
            this.settings.support = false;
        }

        this.supportCheckbox.closest('[data-support-field]')?.classList.toggle('opacity-50', !required);

        if (this.supportNoteEl) {
            const note = supportNote(technology);
            this.supportNoteEl.textContent = note ?? '';
            note ? show(this.supportNoteEl, 'block') : hide(this.supportNoteEl);
        }
    }

    technology() {
        const technology = this.config.technologies?.[this.settings.technology];

        return technology ? { ...technology, code: this.settings.technology } : null;
    }

    material() {
        return this.technology()?.materials?.find((entry) => entry.name === this.settings.material) ?? null;
    }

    resolution() {
        return this.config.resolutions?.[this.settings.resolution] ?? null;
    }

    renderResolutionNotice() {
        if (!this.resolutionNotice) {
            return;
        }

        const technology = this.technology();
        const range = technology?.layerHeightRange;
        const resolution = this.resolution();

        if (!range || !resolution) {
            hide(this.resolutionNotice);

            return;
        }

        const height = Number(resolution.layerHeight);
        const min = Number(range.min);
        const max = Number(range.max);

        if (height >= min && height <= max) {
            hide(this.resolutionNotice);

            return;
        }

        const mm = (value) => formatNumber(value, (value * 100) % 1 === 0 ? 2 : 3);
        const rangeText = min === max ? `tetap ${mm(min)} mm` : `${mm(min)} – ${mm(max)} mm`;

        this.resolutionNotice.textContent =
            `Tebal lapisan ${technology.code} ${rangeText}. Pilihan ini di luar rentang tersebut, ` +
            'tim kami akan menyesuaikannya saat produksi.';

        show(this.resolutionNotice, 'block');
    }

    /* ---------------------------------------------------------- support */

    rebuildSupportVisual() {
        this.detachSupport();

        if (!this.object || !this.supportEnabled()) {
            this.supportStats = null;
            this.renderSupportStats();

            return;
        }

        const visual = this.config.support?.visual ?? {};

        const result = buildSupport(this.pivot, {
            overhangAngleDeg: visual.overhangAngleDeg,
            gridSizeMm: visual.gridSizeMm,
            maxCellsPerAxis: visual.maxCellsPerAxis,
            pillarShrink: visual.pillarShrink,
            minPillarHeightMm: visual.minPillarHeightMm,
            plateToleranceMm: visual.plateToleranceMm,
            baseHeightMm: visual.baseHeightMm,
            baseExpand: visual.baseExpand,
            color: visual.color,
            opacity: visual.opacity,
            infill: this.config.support?.infill,
        });

        this.supportGroup = result.group;
        this.supportGroup.visible = this.supportVisible;
        this.supportStats = result;
        this.scene.add(this.supportGroup);

        this.renderSupportStats();
    }

    detachSupport() {
        if (!this.supportGroup) {
            return;
        }

        this.scene.remove(this.supportGroup);
        disposeSupport(this.supportGroup);
        this.supportGroup = null;
    }

    supportEnabled() {
        return Boolean(this.settings.support) && isSupportRequired(this.technology());
    }

    renderSupportStats() {
        const wrapper = this.root.querySelector('[data-support-stats]');
        const legend = this.root.querySelector('[data-support-legend]');
        const emptyNote = this.root.querySelector('[data-support-empty]');
        const toggle = this.root.querySelector('[data-action="support-visibility"]');

        if (!wrapper) {
            return;
        }

        const stats = this.supportStats;

        if (!stats || stats.pillarCount === 0) {
            hide(wrapper);
            hide(legend);
            stats ? show(emptyNote, 'block') : hide(emptyNote);

            if (toggle) {
                toggle.disabled = true;
            }

            return;
        }

        hide(emptyNote);
        show(legend, 'block');

        this.setText('[data-support-stat="support-pillars"]', formatCount(stats.pillarCount));
        this.setText('[data-support-stat="support-overhang"]', formatCount(stats.overhangFaces));
        this.setText('[data-support-stat="support-grid"]', `${formatNumber(stats.cellSizeMm, 1)} mm`);
        show(wrapper, 'block');

        if (toggle) {
            toggle.disabled = false;
            toggle.setAttribute('aria-pressed', String(this.supportVisible));
        }
    }

    /* ---------------------------------------------------------- analisis */

    runAnalysis() {
        this.metrics = analyzeGeometry(this.object, {
            maxTrianglesForTopology: this.config.limits?.maxTrianglesFullAnalysis ?? 400000,
        });

        this.refreshAnalysis();
        this.renderStats();
    }

    refreshAnalysis() {
        if (!this.metrics || !this.dimensions) {
            return;
        }

        const technology = this.technology();
        const volume = this.plate.size;

        this.analysis = buildChecks(
            this.metrics,
            this.dimensions,
            technology
                ? {
                    ...technology,
                    // Batas yang berlaku adalah mesin card ini, bukan kapasitas
                    // teoretis teknologinya.
                    code: this.printerConfig().name,
                    buildVolume: { x: volume.width, y: volume.depth, z: volume.height },
                }
                : null,
            {
                minDimension: this.config.limits?.minDimensionMm ?? 2,
                warnDimension: this.config.limits?.warnDimensionMm ?? 5,
            }
        );

        this.renderAnalysis();
    }

    renderAnalysis() {
        if (!this.analysisList || !this.analysis) {
            return;
        }

        const presentation = {
            [CHECK_STATUS.PASS]: { dot: 'bg-emerald-500', chip: 'bg-emerald-50 text-emerald-700 border-emerald-200', label: 'Aman' },
            [CHECK_STATUS.WARN]: { dot: 'bg-amber-500', chip: 'bg-amber-50 text-amber-700 border-amber-200', label: 'Perlu perbaikan' },
            [CHECK_STATUS.FAIL]: { dot: 'bg-brand-600', chip: 'bg-brand-50 text-brand-700 border-brand-200', label: 'Bermasalah' },
            [CHECK_STATUS.SKIP]: { dot: 'bg-ink-300', chip: 'bg-ink-50 text-ink-500 border-ink-200', label: 'Dilewati' },
        };

        this.analysisList.innerHTML = this.analysis.checks
            .map((check) => {
                const style = presentation[check.status] ?? presentation[CHECK_STATUS.SKIP];

                return `
                    <li class="flex gap-3 rounded-xl border border-ink-100 bg-white p-4">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full ${style.dot}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-sm font-bold text-ink-900">${escapeHtml(check.label)}</p>
                                <span class="rounded-full border px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-[0.1em] ${style.chip}">${style.label}</span>
                            </div>
                            <p class="mt-1.5 text-xs leading-relaxed text-ink-500">${escapeHtml(check.message)}</p>
                        </div>
                    </li>
                `;
            })
            .join('');

    }

    /* --------------------------------------------------- informasi model */

    renderStats() {
        if (!this.metrics || !this.dimensions) {
            return;
        }

        const { x, y, z } = this.dimensions;
        const scale = this.settings.scale;
        const volumeCm3 = (this.metrics.volumeMm3 / 1000) * scale ** 3;

        this.setStat('name', this.file.name);
        this.setStat('format', `${this.format} (${FORMAT_NAMES[this.format] ?? 'Model 3D'})`);
        this.setStat('size', formatBytes(this.file.size));
        this.setStat('vertices', formatCount(this.metrics.vertices));
        this.setStat('triangles', formatCount(this.metrics.triangles));
        this.setStat(
            'dimensions',
            `${formatNumber(x, 2)} × ${formatNumber(y, 2)} × ${formatNumber(z, 2)} mm` +
                (scale !== 1 ? ` (skala ${Math.round(scale * 100)}%)` : '')
        );
        this.setStat(
            'bounding-box',
            `min (${formatNumber(this.boundingBox.min.x, 1)}, ${formatNumber(this.boundingBox.min.y, 1)}, ${formatNumber(this.boundingBox.min.z, 1)}) sampai ` +
                `max (${formatNumber(this.boundingBox.max.x, 1)}, ${formatNumber(this.boundingBox.max.y, 1)}, ${formatNumber(this.boundingBox.max.z, 1)}) mm`
        );
        this.setStat('surface-area', `${formatNumber((this.metrics.surfaceAreaMm2 / 100) * scale ** 2, 2)} cm²`);

        const volumeNote = this.root.querySelector('[data-volume-note]');

        if (this.metrics.topologyAnalyzed && !this.metrics.isWatertight) {
            this.setStat('volume', `± ${formatNumber(volumeCm3, 3)} cm³`);
            show(volumeNote, 'block');
        } else {
            this.setStat('volume', `${formatNumber(volumeCm3, 3)} cm³`);
            hide(volumeNote);
        }
    }

    /* ----------------------------------------------------------- estimasi */

    /**
     * Hitung ulang estimasi mesin ini dari pengaturannya sendiri.
     * Seluruhnya berjalan di browser — tidak ada permintaan ke server.
     */
    refreshEstimate() {
        const technology = this.technology();
        const material = this.material();

        if (!this.metrics || !technology || !material) {
            return;
        }

        const geometryVolumeCm3 = this.metrics.volumeMm3 / 1000;
        const scale = this.settings.scale;

        // Support digambar lebih dulu supaya volumenya terukur dari geometri
        // yang benar-benar dibentuk — geometri itu sudah ikut terskalakan.
        this.rebuildSupportVisual();

        this.support = estimateSupport(technology, material, geometryVolumeCm3 * scale ** 3, this.dimensions, {
            enabled: this.supportEnabled(),
            config: this.config.support,
            measuredVolumeCm3: this.supportStats?.materialVolumeCm3,
        });

        this.estimate = calculateEstimate(
            technology,
            material,
            geometryVolumeCm3,
            this.settings.quantity,
            this.support,
            this.resolution(),
            {
                scale,
                surfaceAreaCm2: this.metrics.surfaceAreaMm2 / 100,
                // Sudah terskalakan sejak diukur di viewer; dipakai Basic Fee.
                dimensions: this.dimensions,
                infillDensity: this.settings.infillDensity,
                infillPattern: this.settings.infillPattern,
                patterns: this.config.infill?.patterns,
                hollow: { ...this.settings.hollow, drainHoles: this.config.hollow?.drainCount ?? 2 },
                printer: this.printerConfig(),
                printerKey: this.printerKey,
                finishing: this.settings.finishing,
                finishings: this.config.finishing?.options,
                cost: this.config.cost,
                pricing: this.config.pricing,
            }
        );

        this.renderEstimate();
        this.onChange(this);
    }

    renderEstimate() {
        if (!this.estimate) {
            return;
        }

        const technology = this.technology();
        const resolution = this.resolution();
        const result = this.estimate;

        // Ringkasan estimasi sengaja dibuat ringkas: mesin, resolusi, dan infill
        // tetap dapat diatur pada panelnya masing-masing, tetapi tidak lagi ikut
        // ditampilkan di sini. Rincian biaya per komponen juga ditiadakan —
        // pelanggan cukup melihat satu angka Estimasi Harga.
        this.setEstimate('technology', `${technology.code} (${technology.name})`);
        // Material lama yang tidak lagi ditawarkan tetap ditampilkan apa adanya.
        this.setEstimate('material', materialLabel(this.material() ?? { name: this.settings.material }));
        this.setEstimate('quality', resolution?.quality ?? '-');
        this.setEstimate('volume', `${formatNumber(result.totalMaterialVolumeCm3, 2)} cm³`);
        // Berat tidak pernah ditulis ke DOM halaman pelanggan. Angkanya tetap
        // ada di `result` karena menjadi dasar perhitungan harga dan tetap
        // dikirim ke server saat penawaran dibuat.
        this.setEstimate('time', formatLeadTime(result.totalMinutes, result.manualPricing));
        this.setEstimate('cost', formatCurrency(result.totalCost));
        this.setEstimate('quantity', `${this.settings.quantity} pcs`);
        this.setEstimate('support', this.supportEnabled() ? 'Ya' : 'Tidak');

        this.setText('[data-infill-fill]', result.hollowEnabled
            ? 'Hollow'
            : formatPercent(result.fillFactor, result.fillFactor < 0.1 ? 1 : 0));

        this.renderHollowSaving();
        this.renderScaleResults();
        this.renderStats();
    }

    renderScaleResults() {
        if (!this.dimensions || !this.estimate) {
            return;
        }

        const { x, y, z } = this.dimensions;

        this.setText('[data-scale-result="dimensions"]', `${formatNumber(x, 1)} × ${formatNumber(z, 1)} × ${formatNumber(y, 1)} mm`);
        this.setText('[data-scale-result="volume"]', `${formatNumber(this.estimate.modelVolumeCm3, 2)} cm³`);
        this.setText('[data-scale-result="time"]', formatLeadTime(this.estimate.totalMinutes, this.estimate.manualPricing));
    }

    /* ------------------------------------------------------------- utils */

    /** Nomor urut mesin, dipakai judul card. */
    setPosition(position) {
        this.position = position;

        if (this.titleEl) {
            this.titleEl.textContent = `Printer ${position}`;
        }
    }

    isReady() {
        return Boolean(this.metrics && this.estimate);
    }

    setText(selector, value) {
        const el = this.root.querySelector(selector);

        if (el) {
            el.textContent = value;
        }
    }

    setStat(name, value) {
        this.setText(`[data-stat="${name}"]`, value);
    }

    setEstimate(name, value) {
        this.setText(`[data-estimate="${name}"]`, value);
    }

    /** Data model ini untuk dikirim bersama permintaan penawaran. */
    payload() {
        const volume = this.plate.size;

        return {
            file: this.file,
            format: this.format,
            printer: this.printerKey,
            printer_name: this.printerConfig().name,
            build_volume: { x: volume.width, y: volume.depth, z: volume.height },

            technology: this.settings.technology,
            material: this.settings.material,
            quantity: this.settings.quantity,
            resolution: this.settings.resolution,
            support_enabled: this.supportEnabled(),
            support_volume_cm3: this.support?.volumeCm3 ?? null,
            model_volume_cm3: this.metrics.volumeMm3 / 1000,

            scale_percent: Math.round(this.settings.scale * 100),
            infill_density: this.settings.infillDensity,
            infill_pattern: this.settings.infillPattern,
            material_color: this.settings.color,
            finishing: this.settings.finishing,
            hollow_enabled: this.settings.hollow.enabled,
            hollow_wall_thickness_mm: this.settings.hollow.wallThicknessMm,
            hollow_drain_diameter_mm: this.settings.hollow.drainDiameterMm,
            hollow_drain_position: this.settings.hollow.drainPosition,
            fits_build_volume: !this.fit?.exceeds,

            analysis_status: this.analysis?.status ?? 'warning',
            analysis: this.analysis?.checks ?? [],
            estimate: {
                totalWeightG: this.estimate.totalWeightG,
                totalMinutes: this.estimate.totalMinutes,
                totalCost: this.estimate.totalCost,
                manualPricing: this.estimate.manualPricing,
                breakdown: this.estimate.breakdown,
            },
            model_stats: {
                vertices: this.metrics.vertices,
                triangles: this.metrics.triangles,
                dimensions: this.dimensions,
                bounding_box: this.boundingBox,
                volume_cm3: this.metrics.volumeMm3 / 1000,
                surface_area_cm2: this.metrics.surfaceAreaMm2 / 100,
                scaled_volume_cm3: this.estimate.modelVolumeCm3,
                watertight: this.metrics.isWatertight,
                holes: this.metrics.holeCount,
                non_manifold_edges: this.metrics.nonManifoldEdges,
                inconsistent_edges: this.metrics.inconsistentEdges,
                topology_analyzed: this.metrics.topologyAnalyzed,
                printer: { key: this.printerKey, name: this.printerConfig().name, build_volume: { x: volume.width, y: volume.depth, z: volume.height } },
                orientation: {
                    rotation_deg: {
                        x: Math.round(this.orientation.x),
                        y: Math.round(this.orientation.y),
                        z: Math.round(this.orientation.z),
                    },
                    mirrored: Object.entries(this.orientation.mirror)
                        .filter(([, value]) => value === -1)
                        .map(([axis]) => axis.toUpperCase()),
                },
            },
        };
    }

    /** Lepaskan seluruh sumber daya mesin ini. */
    dispose() {
        this.renderer.unregister(this.view);
        this.controls.dispose();
        this.detachSupport();
        disposeExceedMarkers(this.exceedGroup);
        this.plate.dispose();

        this.object?.traverse((child) => {
            if (child.isMesh) {
                child.geometry.dispose();

                if (Array.isArray(child.material)) {
                    child.material.forEach((material) => material.dispose());
                } else {
                    child.material.dispose();
                }
            }
        });

        this.scene.remove(this.pivot);
        this.root.remove();
    }
}

function normalizeAngle(degrees) {
    return ((degrees % 360) + 360) % 360;
}

function formatBytes(bytes) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit += 1;
    }

    return `${value.toFixed(value < 10 ? 1 : 0)} ${units[unit]}`;
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/'/g, '&#39;');
}
