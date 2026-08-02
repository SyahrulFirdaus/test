import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { STLLoader } from 'three/addons/loaders/STLLoader.js';
import { OBJLoader } from 'three/addons/loaders/OBJLoader.js';

import { analyzeGeometry, buildChecks, CHECK_STATUS } from './mesh-analysis';
import {
    COST_COMPONENTS,
    estimate as calculateEstimate,
    formatCount,
    formatCurrency,
    formatDuration,
    formatNumber,
    formatPercent,
} from './print-estimator';
import { estimateSupport, isSupportRequired, supportNote } from './support-estimator';
import { buildSupport, disposeSupport } from './support-builder';
import BuildPlate, { checkBuildVolume, createExceedMarkers, disposeExceedMarkers } from './build-plate';
import { arrangeOnPlate, findOverlaps } from './arrange';
import { clearPaint, paintOverhang, paintThickness, VIEW_MODES } from './mesh-paint';

const SUPPORTED_EXTENSIONS = ['stl', 'obj'];
const MAX_FILE_SIZE = 60 * 1024 * 1024; // 60 MB
const DEFAULT_MAX_MODELS = 10;

const BRAND = 0x95271d;
const FALLBACK_COLOR = '#B8452F';

const SCALE_MIN = 10;
const SCALE_MAX = 400;

// Visibilitas diatur lewat inline style, bukan kelas utility, supaya tidak
// bergantung pada urutan kemunculan `hidden`/`flex` di dalam CSS hasil build.
const show = (el, display = 'flex') => el && (el.style.display = display);
const hide = (el) => el && (el.style.display = 'none');

/** Presentasi status analisis, dipakai badge, daftar model, dan ringkasan. */
const ANALYSIS_PRESENTATION = {
    ready: {
        emoji: '🟢',
        label: 'Ready to Print',
        summary: 'Model lolos seluruh pemeriksaan dasar dan siap masuk antrean produksi.',
        classes: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    },
    warning: {
        emoji: '🟡',
        label: 'Need Improvement',
        summary: 'Model masih dapat dicetak, namun ada catatan yang sebaiknya diperbaiki lebih dulu agar hasilnya optimal.',
        classes: 'border-amber-200 bg-amber-50 text-amber-800',
    },
    not_printable: {
        emoji: '🔴',
        label: 'Not Printable',
        summary: 'Ditemukan masalah yang membuat model belum dapat diproses slicer. Perbaiki dulu sebelum dicetak.',
        classes: 'border-brand-200 bg-brand-50 text-brand-800',
    },
};

/**
 * Pre-Print Analyzer untuk banyak model sekaligus.
 *
 * Seluruh berkas dibaca di browser memakai FileReader — tidak ada yang dikirim
 * ke server saat pratinjau, sehingga model tampil seketika tanpa memuat ulang
 * halaman. Berkas baru diunggah ketika pengguna benar-benar mengirim permintaan
 * penawaran, dan seluruh model dikirim bersama dalam satu permintaan.
 *
 * Semua model berdiri bersamaan di atas Virtual Build Plate seukuran mesin yang
 * dipilih. Setiap model tetap berdiri sendiri: geometri, orientasi, skala,
 * hasil analisis, pengaturan printing, dan estimasinya tersimpan pada objek
 * itemnya masing-masing. Panel di halaman selalu menampilkan model yang sedang
 * dipilih, dan perubahan yang dilakukan di sana hanya menyentuh model tersebut.
 *
 * Seluruh simulasi — skala, infill, hollow, overhang, wall thickness, sampai
 * rincian biaya — dihitung di sini tanpa satu pun permintaan ke server.
 */
export default class ModelViewer {
    constructor(root) {
        this.root = root;

        // --- elemen upload & status ---
        this.canvasHost = root.querySelector('[data-viewer-canvas]');
        this.dropzone = root.querySelector('[data-dropzone]');
        this.input = root.querySelector('[data-file-input]');
        this.loadingEl = root.querySelector('[data-viewer-loading]');
        this.loadingLabel = root.querySelector('[data-viewer-loading-label]');
        this.errorEl = root.querySelector('[data-viewer-error]');
        this.errorMessage = root.querySelector('[data-viewer-error-message]');
        this.errorList = root.querySelector('[data-viewer-error-list]');
        this.emptyState = root.querySelector('[data-viewer-empty]');
        this.stage = root.querySelector('[data-viewer-stage]');
        this.infoEl = root.querySelector('[data-viewer-info]');
        this.placeholderEl = root.querySelector('[data-viewer-placeholder]');
        this.fileNameEl = root.querySelector('[data-file-name]');
        this.viewerPositionEl = root.querySelector('[data-viewer-position]');
        this.fullscreenTarget = root.querySelector('[data-viewer-fullscreen-target]');

        // --- daftar model & ringkasan penawaran ---
        this.listPanel = root.querySelector('[data-model-list-panel]');
        this.listEl = root.querySelector('[data-model-list]');
        this.listCountEl = root.querySelector('[data-model-count]');
        this.activeLabels = root.querySelectorAll('[data-active-model]');
        this.summaryPanel = root.querySelector('[data-quote-summary]');
        this.summaryRows = root.querySelector('[data-quote-rows]');
        this.summaryPlaceholder = root.querySelector('[data-quote-summary-placeholder]');

        // --- printer & build plate ---
        this.printerSelect = root.querySelector('[data-printer-select]');
        this.printerNote = root.querySelector('[data-printer-note]');
        this.printerCustomPanel = root.querySelector('[data-printer-custom]');
        this.printerCustomInputs = root.querySelectorAll('[data-printer-custom-axis]');
        this.buildWarning = root.querySelector('[data-build-warning]');
        this.buildWarningDetail = root.querySelector('[data-build-warning-detail]');
        this.buildUsageBar = root.querySelector('[data-build-usage-bar]');

        // --- panel analisis & produksi ---
        this.analysisPanel = root.querySelector('[data-analysis-panel]');
        this.analysisPlaceholder = root.querySelector('[data-analysis-placeholder]');
        this.analysisBadge = root.querySelector('[data-analysis-badge]');
        this.analysisSummary = root.querySelector('[data-analysis-summary]');
        this.analysisList = root.querySelector('[data-analysis-list]');
        this.analysisLegend = root.querySelector('[data-analysis-legend]');

        this.technologySelect = root.querySelector('[data-technology-select]');
        this.materialSelect = root.querySelector('[data-material-select]');
        this.technologyDescription = root.querySelector('[data-technology-description]');
        this.orientationPanel = root.querySelector('[data-orientation-panel]');
        this.orientationPlaceholder = root.querySelector('[data-orientation-placeholder]');
        this.quantityInput = root.querySelector('[data-quantity-input]');
        this.supportCheckbox = root.querySelector('[data-support-toggle]');
        this.supportNoteEl = root.querySelector('[data-support-note]');
        this.supportRow = root.querySelector('[data-support-row]');
        this.resolutionInputs = root.querySelectorAll('[data-resolution-option]');
        this.resolutionNotice = root.querySelector('[data-resolution-notice]');
        this.estimatePanel = root.querySelector('[data-estimate-panel]');
        this.estimatePlaceholder = root.querySelector('[data-estimate-placeholder]');
        this.quotationButton = root.querySelector('[data-open-quotation]');

        // --- skala, infill, hollow, warna ---
        this.scaleInput = root.querySelector('[data-scale-input]');
        this.scaleSlider = root.querySelector('[data-scale-slider]');
        this.infillDensityInputs = root.querySelectorAll('[data-infill-density]');
        this.infillPatternInputs = root.querySelectorAll('[data-infill-pattern]');
        this.infillNote = root.querySelector('[data-infill-note]');
        this.hollowField = root.querySelector('[data-hollow-field]');
        this.hollowToggle = root.querySelector('[data-hollow-toggle]');
        this.hollowSettings = root.querySelector('[data-hollow-settings]');
        this.hollowWall = root.querySelector('[data-hollow-wall]');
        this.hollowDrain = root.querySelector('[data-hollow-drain]');
        this.hollowPosition = root.querySelector('[data-hollow-position]');
        this.colorSwatches = root.querySelectorAll('[data-material-color]');

        // --- state ---
        this.config = this.readConfig();
        this.maxModels = Number(this.config.limits?.maxModels) || DEFAULT_MAX_MODELS;

        /** @type {Array<object>} seluruh model yang sedang ditinjau */
        this.items = [];
        this.activeId = null;
        this.nextId = 1;

        // Printer berlaku untuk seluruh penawaran: satu build plate, satu mesin.
        this.printerKey = this.config.defaultPrinter ?? Object.keys(this.config.printers ?? {})[0] ?? 'ender3';
        this.customVolume = { ...(this.config.printers?.[this.config.customPrinterKey]?.buildVolume ?? { x: 300, y: 300, z: 300 }) };

        // Preferensi tampilan berlaku untuk viewer, bukan untuk model tertentu.
        this.displayMode = 'solid';
        this.viewMode = VIEW_MODES.MATERIAL;
        this.autoRotate = false;
        this.objectRotateMode = false;
        this.supportVisible = true;

        this.initScene();
        this.bindUpload();
        this.bindToolbar();
        this.bindOrientation();
        this.bindObjectDrag();
        this.bindPicking();
        this.bindPrinter();
        this.bindProduction();
        this.bindScale();
        this.bindInfill();
        this.bindHollow();
        this.bindColors();
        this.bindList();

        this.renderPrinter();
        this.renderBuildInfo();
    }

    /* ------------------------------------------------------- model aktif */

    /** Model yang sedang ditampilkan di viewer, atau null bila belum ada. */
    get item() {
        return this.items.find((item) => item.id === this.activeId) ?? null;
    }

    /** Model yang siap dikirim: sudah punya geometri sekaligus estimasi. */
    readyItems() {
        return this.items.filter((item) => item.metrics && item.estimate);
    }

    findItem(id) {
        return this.items.find((item) => item.id === Number(id)) ?? null;
    }

    readConfig() {
        const el = this.root.querySelector('[data-printing-config]');

        try {
            return JSON.parse(el?.textContent ?? '{}');
        } catch {
            return { technologies: {}, limits: {} };
        }
    }

    /* --------------------------------------------------------------- scene */

    initScene() {
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0xf8f5f4);

        const { clientWidth: width, clientHeight: height } = this.canvasHost;

        this.camera = new THREE.PerspectiveCamera(45, (width || 1) / (height || 1), 0.1, 5000);
        this.camera.position.set(120, 90, 160);

        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: false });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setSize(width, height);
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.05;
        this.canvasHost.appendChild(this.renderer.domElement);

        this.controls = new OrbitControls(this.camera, this.renderer.domElement);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.08;
        this.controls.rotateSpeed = 0.85;
        this.controls.panSpeed = 0.8;
        this.controls.zoomSpeed = 0.9;
        this.controls.screenSpacePanning = true;
        this.controls.minDistance = 1;
        this.controls.maxDistance = 4000;

        // Pencahayaan tiga titik supaya kontur part tetap terbaca dari segala sudut.
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

        // Build plate menggantikan grid biasa: ukurannya mengikuti mesin yang dipilih.
        this.plate = new BuildPlate(this.scene, this.buildVolume());

        // Kotak penanda model yang sedang dipilih.
        this.selectionBox = new THREE.Box3Helper(new THREE.Box3(), BRAND);
        this.selectionBox.visible = false;
        this.scene.add(this.selectionBox);

        this.frameBuildPlate();
        this.observeResize();
        this.renderer.setAnimationLoop(() => this.tick());
    }

    tick() {
        this.controls.autoRotate = this.autoRotate;
        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }

    observeResize() {
        const resize = () => {
            const width = this.canvasHost.clientWidth;
            const height = this.canvasHost.clientHeight;

            if (width === 0 || height === 0) {
                return;
            }

            this.camera.aspect = width / height;
            this.camera.updateProjectionMatrix();
            this.renderer.setSize(width, height);
        };

        if ('ResizeObserver' in window) {
            new ResizeObserver(resize).observe(this.canvasHost);
        } else {
            window.addEventListener('resize', resize);
        }

        resize();
    }

    /* -------------------------------------------------------------- printer */

    /** Entri config printer yang sedang dipilih. */
    printerConfig() {
        return this.config.printers?.[this.printerKey] ?? { name: 'Printer', buildVolume: { x: 220, y: 220, z: 250 }, speedFactor: 1, rateFactor: 1 };
    }

    isCustomPrinter() {
        return Boolean(this.printerConfig().custom);
    }

    /** Ukuran area cetak yang benar-benar berlaku, termasuk isian Custom. */
    buildVolume() {
        const printer = this.printerConfig();

        if (!printer.custom) {
            return { ...printer.buildVolume };
        }

        const limits = this.config.customPrinterLimits ?? { min: 50, max: 1000 };
        const clamp = (value, fallback) => {
            const numeric = Number(value);

            return Number.isFinite(numeric)
                ? Math.min(limits.max, Math.max(limits.min, numeric))
                : fallback;
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
            this.applyPrinter({ reframe: true });
        });

        this.printerCustomInputs.forEach((input) => {
            input.addEventListener('input', () => {
                this.customVolume[input.dataset.printerCustomAxis] = input.value;
                this.applyPrinter({ reframe: true });
            });
        });
    }

    /** Tampilkan keterangan mesin dan buka isian ukuran bila pilihannya Custom. */
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

    /**
     * Terapkan ukuran mesin ke build plate, susunan model, dan seluruh estimasi.
     *
     * Mengganti mesin mengubah area cetak sekaligus laju dan tarifnya, jadi
     * validasi ukuran maupun angka estimasi tiap model dihitung ulang.
     */
    applyPrinter({ reframe = false } = {}) {
        this.plate.setVolume(this.buildVolume());
        this.arrangeItems();

        this.items.forEach((item) => this.refreshEstimate(item, { render: false }));

        if (reframe) {
            this.frameBuildPlate();
        }

        this.refreshAnalysis();
        this.renderEstimate();
        this.renderBuildInfo();
        this.renderList();
        this.renderQuoteSummary();
    }

    /* -------------------------------------------------------------- upload */

    bindUpload() {
        this.input.addEventListener('change', () => {
            if (this.input.files?.length) {
                this.handleFiles([...this.input.files]);
            }
        });

        ['dragenter', 'dragover'].forEach((type) => {
            this.dropzone.addEventListener(type, (event) => {
                event.preventDefault();
                this.dropzone.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach((type) => {
            this.dropzone.addEventListener(type, (event) => {
                event.preventDefault();

                // dragleave juga terpicu saat kursor melintasi elemen anak.
                if (type === 'dragleave' && this.dropzone.contains(event.relatedTarget)) {
                    return;
                }

                this.dropzone.classList.remove('is-dragging');
            });
        });

        // Beberapa berkas dapat dijatuhkan sekaligus.
        this.dropzone.addEventListener('drop', (event) => {
            const files = [...(event.dataTransfer?.files ?? [])];

            if (files.length) {
                this.handleFiles(files);
            }
        });

        // Seluruh halaman menolak drop di luar dropzone agar browser tidak
        // membuka file tersebut dan meninggalkan halaman.
        ['dragover', 'drop'].forEach((type) => {
            window.addEventListener(type, (event) => {
                if (!this.dropzone.contains(event.target)) {
                    event.preventDefault();
                }
            });
        });
    }

    /**
     * Proses sekumpulan berkas sekaligus.
     *
     * Berkas yang bermasalah dilaporkan satu per satu beserta namanya, sedangkan
     * berkas lain yang valid tetap dimuat — satu file rusak tidak membatalkan
     * seluruh unggahan.
     */
    async handleFiles(fileList) {
        this.hideError();

        const problems = [];
        const accepted = [];

        fileList.forEach((file) => {
            const problem = this.validateFile(file);

            problem ? problems.push({ name: file.name, message: problem }) : accepted.push(file);
        });

        // Sisa kapasitas dihitung setelah validasi format, supaya file yang
        // memang ditolak tidak ikut memakan jatah.
        const room = Math.max(0, this.maxModels - this.items.length);

        accepted.slice(room).forEach((file) => {
            problems.push({
                name: file.name,
                message: `Batas ${this.maxModels} model per permintaan sudah tercapai. Hapus salah satu model lebih dulu.`,
            });
        });

        const queue = accepted.slice(0, room);
        let firstAdded = null;

        for (const [index, file] of queue.entries()) {
            const progress = queue.length > 1 ? ` (${index + 1}/${queue.length})` : '';

            try {
                this.showLoading(`Membaca ${file.name}${progress}…`);

                const buffer = await this.readFile(file);

                this.showLoading(`Memproses geometri ${file.name}${progress}…`);
                // Beri kesempatan browser menggambar indikator loading sebelum
                // parsing yang berat mengunci thread utama. Sengaja memakai
                // setTimeout, bukan requestAnimationFrame, agar tetap berjalan
                // saat halaman berada di tab latar belakang.
                await new Promise((resolve) => setTimeout(resolve, 24));

                const item = this.createItem(file, buffer);

                this.items.push(item);
                this.scene.add(item.pivot);

                this.showLoading(`Menganalisis ${file.name}${progress}…`);
                await new Promise((resolve) => setTimeout(resolve, 24));

                this.runAnalysis(item);
                firstAdded ??= item;
            } catch (error) {
                console.error(error);
                problems.push({
                    name: file.name,
                    message: 'Isi file tidak sesuai dengan format yang dinyatakan, atau file rusak/tidak lengkap. Coba ekspor ulang model dari software CAD Anda.',
                });
            }
        }

        // Susunan dan estimasi dihitung setelah semuanya masuk, supaya model
        // yang diunggah bersamaan langsung berdiri berdampingan di meja.
        if (queue.length) {
            this.arrangeItems();
            this.items.forEach((item) => this.refreshEstimate(item, { render: false }));
        }

        this.hideLoading();

        // Input dikosongkan supaya memilih berkas yang sama lagi tetap memicu `change`.
        this.input.value = '';

        if (firstAdded) {
            this.activate(firstAdded.id);
            this.frameBuildPlate();
        } else {
            this.renderList();
            this.renderQuoteSummary();
            this.renderBuildInfo();
        }

        if (problems.length) {
            this.showFileProblems(problems);
        }
    }

    /** Alasan penolakan berkas, atau null bila berkasnya dapat diproses. */
    validateFile(file) {
        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

        if (!SUPPORTED_EXTENSIONS.includes(extension)) {
            return `Format “.${extension || 'tanpa ekstensi'}” belum didukung. Viewer ini hanya dapat membaca file .stl dan .obj.`;
        }

        if (file.size === 0) {
            return 'Ukuran file terbaca 0 byte. Kemungkinan file gagal tersalin atau rusak saat diunduh.';
        }

        if (file.size > MAX_FILE_SIZE) {
            return `Ukuran ${this.formatBytes(file.size)} melebihi batas pratinjau ${this.formatBytes(MAX_FILE_SIZE)}.`;
        }

        return null;
    }

    readFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(reader.error ?? new Error('Gagal membaca file'));
            reader.readAsArrayBuffer(file);
        });
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
        const object = new OBJLoader().parse(text);

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

            // Material dari file .mtl tidak ikut diunggah, jadi seluruh mesh
            // memakai material seragam sesuai warna yang dipilih pengguna.
            child.material = this.createMaterial(color);
        });

        if (!hasGeometry) {
            throw new Error('Tidak ada mesh pada file OBJ');
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

    /** Warna material pilihan pengguna dalam bentuk hex. */
    colorHex(key) {
        return this.config.materialColors?.options?.[key]?.hex ?? FALLBACK_COLOR;
    }

    /* --------------------------------------------------------------- model */

    /**
     * Bentuk satu item model lengkap dengan pivot dan pengaturan awalnya.
     *
     * Model baru mewarisi pilihan produksi yang sedang aktif di panel supaya
     * mengunggah beberapa part sejenis tidak perlu diatur ulang satu per satu.
     */
    createItem(file, buffer) {
        const extension = file.name.split('.').pop().toLowerCase();
        const settings = this.defaultSettings();
        const color = this.colorHex(settings.color);

        const object = extension === 'stl' ? this.buildFromStl(buffer, color) : this.buildFromObj(buffer, color);

        // Geometri digeser agar pusat bounding box berada di titik asal, lalu
        // dibungkus pivot. Rotasi dan skala diterapkan pada pivot sehingga model
        // selalu berubah di sekitar pusatnya sendiri.
        const box = new THREE.Box3().setFromObject(object);
        const center = box.getCenter(new THREE.Vector3());
        object.position.sub(center);

        const pivot = new THREE.Group();
        pivot.add(object);

        const item = {
            id: this.nextId++,
            file,
            format: extension.toUpperCase(),
            object,
            pivot,

            metrics: null,
            dimensions: null,
            boundingBox: null,
            worldBox: new THREE.Box3(),
            analysis: null,
            estimate: null,
            fit: null,
            painted: null,

            orientation: { x: 0, y: 0, z: 0, mirror: { x: 1, y: 1, z: 1 } },
            placement: { x: 0, z: 0 },

            settings,

            support: null,
            supportStats: null,
            supportGroup: null,
            exceedGroup: null,
        };

        // Menghitung dimensi dan bounding box awal serta mendudukkan model di plate.
        this.applyOrientation(item, { refresh: false });

        return item;
    }

    /** Pengaturan awal model baru, mengikuti pilihan yang sedang aktif di panel. */
    defaultSettings() {
        const technology = this.selectedTechnology();

        return {
            technology: this.technologySelect?.value ?? '',
            material: this.materialSelect?.value ?? '',
            quantity: this.quantity(),
            resolution: this.selectedResolutionKey(),
            support: Boolean(this.supportCheckbox?.checked),
            scale: 1,
            infillDensity: Number(technology?.defaultInfill ?? 1),
            infillPattern: this.config.infill?.defaultPattern ?? 'grid',
            color: this.config.materialColors?.default ?? 'merah',
            hollow: {
                enabled: false,
                wallThicknessMm: Number(this.config.hollow?.wallThickness?.default ?? 2),
                drainDiameterMm: Number(this.config.hollow?.drainDiameter?.default ?? 3.5),
                drainPosition: this.config.hollow?.defaultDrainPosition ?? 'bottom',
            },
        };
    }

    /**
     * Tampilkan satu model sebagai model aktif.
     *
     * Seluruh model tetap berdiri di build plate; yang berubah hanyalah model
     * mana yang sedang disorot dan diikuti oleh panel pengaturan.
     */
    activate(id) {
        const next = this.findItem(id);

        if (!next) {
            return;
        }

        this.activeId = next.id;

        this.syncControlsFromItem(next);
        this.setActionsEnabled(true);

        hide(this.emptyState);
        hide(this.placeholderEl);
        show(this.stage, 'block');
        show(this.infoEl, 'block');
        show(this.orientationPanel, 'block');

        this.renderActiveItem();
    }

    /** Gambar ulang seluruh panel yang mengikuti model aktif. */
    renderActiveItem() {
        this.renderStats();
        this.renderAnalysis();
        this.renderEstimate();
        this.renderOrientationInputs();
        this.renderSupportStats();
        this.renderActiveLabels();
        this.renderSelection();
        this.renderBuildInfo();
        this.renderList();
        this.renderQuoteSummary();
    }

    /** Kembalikan nilai seluruh kontrol ke pengaturan milik model tersebut. */
    syncControlsFromItem(item) {
        if (this.technologySelect && item.settings.technology) {
            this.technologySelect.value = item.settings.technology;
            this.populateMaterials();
        }

        if (this.materialSelect && item.settings.material) {
            this.materialSelect.value = item.settings.material;
        }

        if (this.quantityInput) {
            this.quantityInput.value = String(item.settings.quantity);
        }

        this.resolutionInputs.forEach((input) => {
            input.checked = input.value === item.settings.resolution;
        });

        if (this.supportCheckbox) {
            this.supportCheckbox.checked = Boolean(item.settings.support);
        }

        this.renderScaleInputs(item);
        this.renderInfillInputs(item);
        this.renderHollowInputs(item);
        this.renderColorInputs(item);

        this.renderTechnologyDescription();
        this.syncSupportAvailability();
        this.syncHollowAvailability();
        this.renderResolutionNotice();
    }

    /** Nama model aktif, ditampilkan sebagai penanda di tiap panel pengaturan. */
    renderActiveLabels() {
        const item = this.item;
        const position = item ? this.items.indexOf(item) + 1 : 0;

        if (this.fileNameEl) {
            this.fileNameEl.textContent = item ? item.file.name : 'Viewer 3D';
        }

        if (this.viewerPositionEl) {
            this.viewerPositionEl.textContent = item
                ? `Model ${position} dari ${this.items.length}`
                : 'Pratinjau interaktif';
        }

        this.activeLabels.forEach((label) => {
            if (!item) {
                hide(label);

                return;
            }

            label.textContent = `Model ${position}: ${item.file.name}`;
            show(label, 'block');
        });
    }

    /** Kotak sorot di sekeliling model yang sedang dipilih. */
    renderSelection() {
        const item = this.item;

        if (!item || this.items.length === 0) {
            this.selectionBox.visible = false;

            return;
        }

        this.selectionBox.box.copy(item.worldBox);
        this.selectionBox.visible = this.items.length > 1;
    }

    /* ------------------------------------------------------- build plate */

    /**
     * Tata ulang seluruh model di atas build plate.
     *
     * Dipanggil setiap kali daftar model, ukuran mesin, orientasi, atau skala
     * berubah — semuanya mengubah tapak yang harus ditampung meja.
     */
    arrangeItems() {
        const footprints = this.items.map((item) => ({
            id: item.id,
            footprint: { x: item.dimensions?.x ?? 1, z: item.dimensions?.z ?? 1 },
        }));

        const placements = arrangeOnPlate(footprints, {
            width: this.plate.size.width,
            depth: this.plate.size.depth,
        });

        this.items.forEach((item) => {
            const placement = placements.get(item.id);

            if (placement) {
                this.moveItemTo(item, placement);
            }
        });

        // Jaring pengaman: bila suatu saat penataannya menyisakan tumpukan,
        // modelnya ditandai alih-alih diam-diam berbagi satu petak.
        const overlapping = findOverlaps(
            this.items.map((item) => ({
                id: item.id,
                placement: item.placement,
                footprint: { x: item.dimensions?.x ?? 1, z: item.dimensions?.z ?? 1 },
            }))
        );

        this.items.forEach((item) => {
            item.overlapping = overlapping.has(item.id);
        });

        this.renderSlots();
        this.refreshFit();
    }

    /**
     * Garis tapak yang menandai petak milik tiap model di atas meja.
     *
     * Hanya digambar saat ada lebih dari satu model — di situlah pengguna perlu
     * melihat bahwa setiap objek punya tempatnya sendiri dan tidak berbagi
     * petak dengan objek lain.
     */
    renderSlots() {
        this.disposeSlots();

        if (this.items.length < 2) {
            return;
        }

        this.slotGroup = new THREE.Group();
        this.slotGroup.name = 'model-slots';

        const material = new THREE.LineBasicMaterial({ color: 0x95271d, transparent: true, opacity: 0.35 });

        this.items.forEach((item) => {
            if (!item.dimensions) {
                return;
            }

            const halfX = item.dimensions.x / 2;
            const halfZ = item.dimensions.z / 2;
            // Sedikit di atas meja supaya garisnya tidak beradu dengan kisi.
            const y = 0.2;

            const geometry = new THREE.BufferGeometry();
            geometry.setAttribute('position', new THREE.Float32BufferAttribute([
                -halfX, y, -halfZ, halfX, y, -halfZ,
                halfX, y, -halfZ, halfX, y, halfZ,
                halfX, y, halfZ, -halfX, y, halfZ,
                -halfX, y, halfZ, -halfX, y, -halfZ,
            ], 3));

            const outline = new THREE.LineSegments(geometry, material);
            outline.position.set(item.placement.x, 0, item.placement.z);
            this.slotGroup.add(outline);
        });

        this.scene.add(this.slotGroup);
    }

    disposeSlots() {
        if (!this.slotGroup) {
            return;
        }

        this.slotGroup.traverse((child) => {
            child.geometry?.dispose();
            child.material?.dispose();
        });

        this.scene.remove(this.slotGroup);
        this.slotGroup = null;
    }

    /**
     * Geser satu model ke petaknya di atas build plate.
     *
     * Berpindah tempat hanya menggeser pivot mendatar, jadi tidak perlu
     * menghitung ulang bounding box sama sekali — bentuk dan tingginya tidak
     * berubah. Ini sekaligus mencegah dimensi model tergerus mode cepat setiap
     * kali meja ditata ulang.
     */
    moveItemTo(item, placement) {
        if (!item?.pivot || !item.dimensions) {
            return;
        }

        const previous = item.placement ?? { x: 0, z: 0 };

        item.pivot.position.x += placement.x - previous.x;
        item.pivot.position.z += placement.z - previous.z;
        item.pivot.updateMatrixWorld(true);

        item.placement = placement;
        this.syncWorldBox(item);
    }

    /** Selaraskan kotak dunia model dengan dimensi dan posisinya sekarang. */
    syncWorldBox(item) {
        item.worldBox.setFromCenterAndSize(
            new THREE.Vector3(item.placement.x, item.dimensions.y / 2, item.placement.z),
            new THREE.Vector3(item.dimensions.x, item.dimensions.y, item.dimensions.z)
        );
    }

    /**
     * Periksa setiap model terhadap batas area cetak.
     *
     * Bagian yang melewati batas digambar sebagai kotak merah transparan,
     * sehingga area yang bermasalah langsung terlihat — bukan sekadar
     * disebutkan pada pesan peringatan.
     */
    refreshFit() {
        const bounds = this.plate.bounds;
        let exceeded = false;

        this.items.forEach((item) => {
            disposeExceedMarkers(item.exceedGroup);
            item.exceedGroup = null;

            item.fit = checkBuildVolume(item.worldBox, bounds);

            if (!item.fit.exceeds) {
                return;
            }

            exceeded = true;
            item.exceedGroup = createExceedMarkers(item.fit.regions);
            this.scene.add(item.exceedGroup);
        });

        this.plate.setExceeded(exceeded);
        this.renderBuildWarning();
    }

    /** Peringatan model yang melewati area cetak beserta daftar namanya. */
    renderBuildWarning() {
        if (!this.buildWarning) {
            return;
        }

        const offenders = this.items.filter((item) => item.fit?.exceeds);

        if (offenders.length === 0) {
            hide(this.buildWarning);

            return;
        }

        const volume = this.plate.size;
        const names = offenders.map((item) => item.file.name).join(', ');

        if (this.buildWarningDetail) {
            this.buildWarningDetail.textContent =
                `${offenders.length === 1 ? 'Model' : `${offenders.length} model`} ${names} melewati area cetak ` +
                `${formatNumber(volume.width, 0)} × ${formatNumber(volume.depth, 0)} × ${formatNumber(volume.height, 0)} mm.`;
        }

        show(this.buildWarning, 'block');
    }

    /** Panel Informasi Build Volume: ukuran mesin, ukuran model, dan pemakaiannya. */
    renderBuildInfo() {
        const volume = this.plate.size;
        const capacity = volume.width * volume.depth * volume.height;

        this.setBuild('printer', `${formatNumber(volume.width, 0)} × ${formatNumber(volume.depth, 0)} × ${formatNumber(volume.height, 0)} mm`);

        const item = this.item;

        this.setBuild(
            'model',
            item?.dimensions
                ? `${formatNumber(item.dimensions.x, 0)} × ${formatNumber(item.dimensions.z, 0)} × ${formatNumber(item.dimensions.y, 0)} mm`
                : '—'
        );

        // Pemakaian dihitung dari kotak pembatas seluruh model yang berdiri di
        // meja, karena itulah ruang yang benar-benar tidak dapat dipakai lagi.
        const used = this.items.reduce((total, model) => {
            const size = model.dimensions;

            return total + (size ? size.x * size.y * size.z : 0);
        }, 0);

        const ratio = capacity > 0 ? used / capacity : 0;

        this.setBuild('usage', this.items.length === 0 ? '—' : formatPercent(Math.min(ratio, 9.99), ratio < 0.1 ? 1 : 0));

        if (this.buildUsageBar) {
            this.buildUsageBar.style.width = `${Math.min(100, ratio * 100)}%`;
            this.buildUsageBar.classList.toggle('bg-brand-600', ratio <= 1);
            this.buildUsageBar.classList.toggle('bg-red-600', ratio > 1);
        }
    }

    setBuild(name, value) {
        const el = this.root.querySelector(`[data-build="${name}"]`);

        if (el) {
            el.textContent = value;
        }
    }

    /* ------------------------------------------------- kontrol orientasi */

    bindOrientation() {
        this.root.querySelectorAll('[data-rotate-step]').forEach((button) => {
            button.addEventListener('click', () => {
                this.rotateBy(button.dataset.axis, Number(button.dataset.rotateStep));
            });
        });

        this.root.querySelectorAll('[data-rotate-slider]').forEach((slider) => {
            // `input` untuk umpan balik langsung saat digeser.
            slider.addEventListener('input', () => {
                this.setAxisRotation(slider.dataset.rotateSlider, slider.value);
            });
        });

        this.root.querySelectorAll('[data-mirror]').forEach((button) => {
            button.addEventListener('click', () => this.toggleMirror(button.dataset.mirror));
        });

        this.root.querySelector('[data-action="reset-orientation"]')
            ?.addEventListener('click', () => this.resetOrientation());

        this.objectRotateToggle = this.root.querySelector('[data-action="rotate-object-mode"]');

        this.objectRotateToggle?.addEventListener('click', () => {
            this.objectRotateMode = !this.objectRotateMode;
            this.objectRotateToggle.setAttribute('aria-pressed', String(this.objectRotateMode));
            // Saat mode putar objek aktif, drag kiri tidak lagi memutar kamera.
            this.controls.enableRotate = !this.objectRotateMode;
            this.canvasHost.style.cursor = this.objectRotateMode ? 'grab' : '';
        });
    }

    /**
     * Drag langsung pada kanvas untuk memutar objek, bukan kamera.
     * Analisis hanya dihitung ulang saat drag selesai supaya tetap ringan.
     */
    bindObjectDrag() {
        const canvas = this.renderer.domElement;
        let dragging = false;
        let lastX = 0;
        let lastY = 0;

        canvas.addEventListener('pointerdown', (event) => {
            if (!this.objectRotateMode || !this.item?.pivot || event.button !== 0) {
                return;
            }

            dragging = true;
            lastX = event.clientX;
            lastY = event.clientY;
            this.canvasHost.style.cursor = 'grabbing';
            canvas.setPointerCapture(event.pointerId);
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!dragging || !this.item) {
                return;
            }

            const deltaX = event.clientX - lastX;
            const deltaY = event.clientY - lastY;
            lastX = event.clientX;
            lastY = event.clientY;

            this.item.orientation.y = normalizeAngle(this.item.orientation.y + deltaX * 0.6);
            this.item.orientation.x = normalizeAngle(this.item.orientation.x + deltaY * 0.6);
            // Mode cepat: gerakannya tetap mulus, dan dimensinya diperbaiki
            // begitu drag selesai.
            this.applyOrientation(this.item, { refresh: false, arrange: false, precise: false });
            this.renderOrientationInputs();
        });

        const endDrag = (event) => {
            if (!dragging) {
                return;
            }

            dragging = false;
            this.canvasHost.style.cursor = this.objectRotateMode ? 'grab' : '';

            try {
                canvas.releasePointerCapture(event.pointerId);
            } catch {
                // pointer sudah lepas, tidak perlu ditangani
            }

            // Dimensi & analisis baru dihitung sekali di akhir gerakan.
            this.applyOrientation(this.item);
        };

        canvas.addEventListener('pointerup', endDrag);
        canvas.addEventListener('pointercancel', endDrag);
    }

    /**
     * Pilih model dengan mengkliknya langsung di build plate.
     *
     * Klik dibedakan dari drag kamera lewat jarak geser pointer, supaya memutar
     * pandangan tidak ikut mengganti model yang sedang dipilih.
     */
    bindPicking() {
        const canvas = this.renderer.domElement;
        const raycaster = new THREE.Raycaster();
        const pointer = new THREE.Vector2();
        let startX = 0;
        let startY = 0;

        canvas.addEventListener('pointerdown', (event) => {
            startX = event.clientX;
            startY = event.clientY;
        });

        canvas.addEventListener('pointerup', (event) => {
            if (this.objectRotateMode || event.button !== 0 || this.items.length < 2) {
                return;
            }

            if (Math.hypot(event.clientX - startX, event.clientY - startY) > 4) {
                return; // pengguna sedang memutar kamera, bukan memilih model
            }

            const rect = canvas.getBoundingClientRect();
            pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

            raycaster.setFromCamera(pointer, this.camera);

            const targets = this.items.map((item) => item.object).filter(Boolean);
            const hit = raycaster.intersectObjects(targets, true)[0];

            if (!hit) {
                return;
            }

            const picked = this.items.find((item) => {
                let node = hit.object;

                while (node) {
                    if (node === item.object) {
                        return true;
                    }

                    node = node.parent;
                }

                return false;
            });

            if (picked && picked.id !== this.activeId) {
                this.activate(picked.id);
            }
        });
    }

    /**
     * Terapkan rotasi, pencerminan, dan skala pilihan pengguna pada satu model,
     * lalu dudukkan model tepat di atas build plate pada posisinya.
     *
     * Dimensi dan pemeriksaan area cetak memang harus ikut berubah: part yang
     * direbahkan punya tinggi dan lebar yang berbeda, dan itulah yang menentukan
     * apakah part masih muat di mesin.
     */
    applyOrientation(item = this.item, { refresh = true, arrange = true, precise = true } = {}) {
        if (!item?.pivot) {
            return;
        }

        const { x, y, z, mirror } = item.orientation;
        const scale = Math.max(0.01, Number(item.settings.scale) || 1);
        const toRad = (deg) => (deg * Math.PI) / 180;

        item.pivot.position.set(0, 0, 0);
        item.pivot.rotation.set(toRad(x), toRad(y), toRad(z));
        item.pivot.scale.set(mirror.x * scale, mirror.y * scale, mirror.z * scale);
        item.pivot.updateMatrixWorld(true);

        // Setelah diputar, bounding box berubah — geser mendatar ke petaknya di
        // atas meja lalu turunkan sampai sisi terbawah menyentuh build plate.
        //
        // `precise` mentransformasi setiap vertex. Mode cepat hanya mengambil 8
        // sudut bounding box geometri sehingga hasilnya melebih-lebihkan pada
        // objek yang diputar — modelnya akan mengambang dan tapaknya terbaca
        // lebih besar dari yang sebenarnya. Karena itu mode cepat hanya dipakai
        // selama drag berlangsung, dan hasilnya selalu diperbaiki begitu drag
        // selesai.
        const box = new THREE.Box3().setFromObject(item.pivot, precise);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        const placement = item.placement ?? { x: 0, z: 0 };

        item.pivot.position.x += placement.x - center.x;
        item.pivot.position.z += placement.z - center.z;
        item.pivot.position.y -= box.min.y;
        item.pivot.updateMatrixWorld(true);

        item.dimensions = { x: size.x, y: size.y, z: size.z };

        // Bounding box pada panel informasi menggambarkan modelnya sendiri, jadi
        // tetap berpusat di titik nol — bukan posisinya di atas meja.
        item.boundingBox = {
            min: { x: -size.x / 2, y: 0, z: -size.z / 2 },
            max: { x: size.x / 2, y: size.y, z: size.z / 2 },
        };

        item.placement = placement;
        this.syncWorldBox(item);

        if (!refresh) {
            return;
        }

        if (arrange) {
            // Tapak model berubah, jadi susunan di meja disusun ulang.
            this.arrangeItems();
        } else {
            this.refreshFit();
        }

        this.repaintItem(item);
        this.refreshAnalysis(item);
        // Support tumbuh dari meja, jadi harus dibentuk ulang setiap kali
        // orientasi berubah. Sengaja hanya di sini, bukan saat drag
        // berlangsung, supaya gerakannya tetap mulus.
        this.refreshEstimate(item);

        if (item.id === this.activeId) {
            this.renderStats();
            this.renderOrientationInputs();
            this.renderSelection();
        }

        this.renderBuildInfo();
    }

    /** Putar bertahap, dipakai tombol −90°/+90° dan −15°/+15°. */
    rotateBy(axis, degrees) {
        const item = this.item;

        if (!item) {
            return;
        }

        item.orientation[axis] = normalizeAngle(item.orientation[axis] + degrees);
        this.applyOrientation(item);
    }

    setAxisRotation(axis, degrees) {
        const item = this.item;

        if (!item) {
            return;
        }

        item.orientation[axis] = normalizeAngle(Number(degrees) || 0);
        this.applyOrientation(item);
    }

    /** Balik (mirror) model pada satu sumbu. */
    toggleMirror(axis) {
        const item = this.item;

        if (!item) {
            return;
        }

        item.orientation.mirror[axis] *= -1;
        this.applyOrientation(item);
    }

    resetOrientation() {
        const item = this.item;

        if (!item) {
            return;
        }

        item.orientation = { x: 0, y: 0, z: 0, mirror: { x: 1, y: 1, z: 1 } };
        this.applyOrientation(item);
    }

    /** Sinkronkan slider, angka derajat, dan status tombol balik dengan state. */
    renderOrientationInputs() {
        const item = this.item;

        if (!this.orientationPanel || !item) {
            return;
        }

        ['x', 'y', 'z'].forEach((axis) => {
            const slider = this.root.querySelector(`[data-rotate-slider="${axis}"]`);
            const value = this.root.querySelector(`[data-rotate-value="${axis}"]`);
            const mirrorButton = this.root.querySelector(`[data-mirror="${axis}"]`);

            if (slider) {
                slider.value = String(Math.round(item.orientation[axis]));
            }

            if (value) {
                value.textContent = `${Math.round(item.orientation[axis])}°`;
            }

            if (mirrorButton) {
                mirrorButton.setAttribute('aria-pressed', String(item.orientation.mirror[axis] === -1));
            }
        });
    }

    setActionsEnabled(enabled) {
        this.root.querySelectorAll('[data-viewer-action]').forEach((el) => {
            el.disabled = !enabled;
        });
    }

    /* ------------------------------------------------------------- skala */

    bindScale() {
        const apply = (percent) => this.setScale(percent);

        this.scaleInput?.addEventListener('input', () => apply(this.scaleInput.value));
        this.scaleSlider?.addEventListener('input', () => apply(this.scaleSlider.value));

        this.root.querySelectorAll('[data-scale-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const current = Math.round((this.item?.settings.scale ?? 1) * 100);

                apply(current + Number(button.dataset.scaleStep));
            });
        });

        this.root.querySelectorAll('[data-scale-preset]').forEach((button) => {
            button.addEventListener('click', () => apply(button.dataset.scalePreset));
        });
    }

    /**
     * Ubah skala model aktif.
     *
     * Volume berubah pangkat tiga terhadap skala, jadi berat, waktu, dan biaya
     * ikut dihitung ulang — sama seperti dimensinya di viewer.
     */
    setScale(percent) {
        const item = this.item;

        if (!item) {
            return;
        }

        const value = Math.min(SCALE_MAX, Math.max(SCALE_MIN, Math.round(Number(percent) || 100)));

        item.settings.scale = value / 100;

        this.renderScaleInputs(item);
        this.applyOrientation(item);
    }

    renderScaleInputs(item) {
        const percent = Math.round((item?.settings.scale ?? 1) * 100);

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

    /** Ringkasan dampak skala: dimensi, volume, berat, dan waktu setelah diskalakan. */
    renderScaleResults() {
        const item = this.item;
        const set = (name, value) => {
            const el = this.root.querySelector(`[data-scale-result="${name}"]`);

            if (el) {
                el.textContent = value;
            }
        };

        if (!item?.dimensions || !item.estimate) {
            ['dimensions', 'volume', 'weight', 'time'].forEach((name) => set(name, '—'));

            return;
        }

        const { x, y, z } = item.dimensions;

        set('dimensions', `${formatNumber(x, 1)} × ${formatNumber(z, 1)} × ${formatNumber(y, 1)} mm`);
        set('volume', `${formatNumber(item.estimate.modelVolumeCm3, 2)} cm³`);
        set('weight', `${formatNumber(item.estimate.totalWeightG, 1)} gram`);
        set('time', formatDuration(item.estimate.totalMinutes));
    }

    /* ------------------------------------------------------------ infill */

    bindInfill() {
        this.infillDensityInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!this.item) {
                    return;
                }

                this.item.settings.infillDensity = Number(input.value);
                this.refreshEstimate(this.item);
            });
        });

        this.infillPatternInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (!this.item) {
                    return;
                }

                this.item.settings.infillPattern = input.value;
                this.refreshEstimate(this.item);
            });
        });
    }

    renderInfillInputs(item) {
        const density = Number(item?.settings.infillDensity ?? 1);

        this.infillDensityInputs.forEach((input) => {
            input.checked = Math.abs(Number(input.value) - density) < 1e-6;
        });

        this.infillPatternInputs.forEach((input) => {
            input.checked = input.value === item?.settings.infillPattern;
        });

        // Pada teknologi yang mengeras padat, kepadatan infill tidak berpengaruh
        // sama sekali — alasannya dijelaskan alih-alih dibiarkan tampak rusak.
        const note = this.technologyOf(item)?.infillNote ?? null;

        if (this.infillNote) {
            this.infillNote.textContent = note ?? '';
            note ? show(this.infillNote, 'block') : hide(this.infillNote);
        }
    }

    /* ------------------------------------------------------------ hollow */

    bindHollow() {
        this.hollowToggle?.addEventListener('change', () => {
            if (!this.item) {
                return;
            }

            this.item.settings.hollow.enabled = this.hollowToggle.checked;
            this.renderHollowInputs(this.item);
            this.refreshEstimate(this.item);
        });

        this.hollowWall?.addEventListener('input', () => {
            if (!this.item) {
                return;
            }

            this.item.settings.hollow.wallThicknessMm = Number(this.hollowWall.value);
            this.renderHollowInputs(this.item);
            this.refreshEstimate(this.item);
        });

        this.hollowDrain?.addEventListener('input', () => {
            if (!this.item) {
                return;
            }

            this.item.settings.hollow.drainDiameterMm = Number(this.hollowDrain.value);
            this.renderHollowInputs(this.item);
            this.refreshEstimate(this.item);
        });

        this.hollowPosition?.addEventListener('change', () => {
            if (!this.item) {
                return;
            }

            this.item.settings.hollow.drainPosition = this.hollowPosition.value;
            this.renderHollowInputs(this.item);
            this.refreshEstimate(this.item);
        });
    }

    /** Hollow Model hanya tersedia pada teknologi yang mendukungnya (SLA). */
    syncHollowAvailability() {
        const allowed = Boolean(this.technologyOf(this.item)?.allowsHollow);

        allowed ? show(this.hollowField, 'block') : hide(this.hollowField);

        if (!allowed && this.item) {
            this.item.settings.hollow.enabled = false;
        }
    }

    renderHollowInputs(item) {
        const hollow = item?.settings.hollow;

        if (!hollow || !this.hollowToggle) {
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

        this.setHollowValue('wall', `${formatNumber(hollow.wallThicknessMm, 1)} mm`);
        this.setHollowValue('drain', `${formatNumber(hollow.drainDiameterMm, 1)} mm`);

        const note = this.root.querySelector('[data-hollow-position-note]');

        if (note) {
            note.textContent = this.hollowPosition?.selectedOptions?.[0]?.dataset.description
                ?? this.hollowPositionDescription(hollow.drainPosition);
        }

        this.renderHollowSaving(item);
    }

    hollowPositionDescription(position) {
        return {
            bottom: 'Paling tersembunyi setelah dicetak dan paling mudah dibersihkan.',
            side: 'Dipakai bila dasar model menjadi permukaan tampak.',
            top: 'Untuk model yang dicetak terbalik agar resin mengalir keluar sendiri.',
        }[position] ?? '';
    }

    /** Berapa banyak resin yang dihemat oleh pengaturan hollow saat ini. */
    renderHollowSaving(item) {
        const el = this.root.querySelector('[data-hollow-saving]');

        if (!el) {
            return;
        }

        const estimate = item?.estimate;

        if (!estimate?.hollowEnabled) {
            el.textContent = 'Aktifkan untuk melihat berapa banyak resin yang dihemat.';

            return;
        }

        const ratio = estimate.modelVolumeCm3 > 0 ? estimate.hollowSavedCm3 / estimate.modelVolumeCm3 : 0;

        el.textContent =
            `Menghemat ${formatNumber(estimate.hollowSavedCm3, 2)} cm³ resin (${formatPercent(ratio, 0)} dari volume padat) ` +
            `pada dinding ${formatNumber(estimate.hollowWallThicknessMm ?? 0, 1)} mm.`;
    }

    setHollowValue(name, value) {
        const el = this.root.querySelector(`[data-hollow-value="${name}"]`);

        if (el) {
            el.textContent = value;
        }
    }

    /* ------------------------------------------------------------- warna */

    bindColors() {
        this.colorSwatches.forEach((swatch) => {
            swatch.addEventListener('click', () => {
                const item = this.item;

                if (!item) {
                    return;
                }

                item.settings.color = swatch.dataset.materialColor;
                this.renderColorInputs(item);
                this.applyAppearance(item);
                this.renderList();
            });
        });
    }

    renderColorInputs(item) {
        this.colorSwatches.forEach((swatch) => {
            swatch.setAttribute('aria-pressed', String(swatch.dataset.materialColor === item?.settings.color));
        });
    }

    /* ------------------------------------------------- mode analisis visual */

    /**
     * Terapkan mode tampilan analisis pada seluruh model.
     *
     * Pengukuran wall thickness menembakkan ribuan sinar ke dalam mesh, jadi
     * indikator loading ditampilkan lebih dulu agar halaman tidak terlihat
     * membeku pada model yang rapat.
     */
    async setViewMode(mode) {
        if (this.viewMode === mode) {
            return;
        }

        this.viewMode = mode;

        this.root.querySelectorAll('[data-view-mode]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.viewMode === mode));
        });

        if (mode === VIEW_MODES.THICKNESS && this.items.length) {
            this.showLoading('Mengukur ketebalan dinding…');
            await new Promise((resolve) => setTimeout(resolve, 24));
        }

        this.items.forEach((item) => this.repaintItem(item));

        this.hideLoading();
        this.renderAnalysisLegend();
    }

    /** Warnai ulang satu model sesuai mode analisis yang sedang aktif. */
    repaintItem(item) {
        if (!item?.object) {
            return;
        }

        clearPaint(item.object);
        item.painted = null;

        const analysis = this.config.analysis ?? {};

        if (this.viewMode === VIEW_MODES.OVERHANG) {
            item.painted = {
                mode: VIEW_MODES.OVERHANG,
                ...paintOverhang(item.object, {
                    safeDeg: analysis.overhang?.safeDeg,
                    warnDeg: analysis.overhang?.warnDeg,
                    colors: analysis.overhang?.colors,
                }),
            };
        } else if (this.viewMode === VIEW_MODES.THICKNESS) {
            const maxTriangles = Number(analysis.wallThickness?.maxTriangles ?? 250000);

            if ((item.metrics?.triangles ?? 0) > maxTriangles) {
                // Model terlalu rapat untuk diukur seketika; pewarnaannya
                // dilewati dan alasannya disampaikan lewat legenda.
                item.painted = { mode: VIEW_MODES.THICKNESS, skipped: true };
            } else {
                item.painted = {
                    mode: VIEW_MODES.THICKNESS,
                    minWallMm: this.minWallThickness(item),
                    ...paintThickness(item.object, {
                        minMm: this.minWallThickness(item),
                        colors: analysis.wallThickness?.colors,
                        maxSamples: analysis.wallThickness?.maxSamples,
                    }),
                };
            }
        }

        this.applyAppearance(item);
    }

    minWallThickness(item) {
        return Number(this.technologyOf(item)?.minWallThicknessMm ?? 1);
    }

    /**
     * Terapkan warna material, mode tampilan, dan hasil pewarnaan analisis.
     *
     * Saat mode analisis aktif, warna material dinetralkan menjadi putih agar
     * warna hijau/kuning/merah hasil analisis tidak tercampur.
     */
    applyAppearance(item) {
        if (!item?.object) {
            return;
        }

        const painted = Boolean(item.painted) && !item.painted.skipped;
        const transparent = this.displayMode === 'transparent';
        const color = painted ? '#FFFFFF' : this.colorHex(item.settings.color);

        item.object.traverse((child) => {
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
    }

    /** Legenda arti warna pada mode analisis yang sedang aktif. */
    renderAnalysisLegend() {
        if (!this.analysisLegend) {
            return;
        }

        const title = this.analysisLegend.querySelector('[data-analysis-legend-title]');
        const list = this.analysisLegend.querySelector('[data-analysis-legend-items]');
        const note = this.analysisLegend.querySelector('[data-analysis-legend-note]');

        if (this.viewMode === VIEW_MODES.MATERIAL || this.items.length === 0) {
            hide(this.analysisLegend);

            return;
        }

        const analysis = this.config.analysis ?? {};
        const item = this.item;

        let heading;
        let entries;
        let footnote = '';

        if (this.viewMode === VIEW_MODES.OVERHANG) {
            const safe = analysis.overhang?.safeDeg ?? 45;
            const warn = analysis.overhang?.warnDeg ?? 60;
            const colors = analysis.overhang?.colors ?? {};

            heading = 'Overhang Analysis';
            entries = [
                [colors.safe ?? '#3FA45B', `Aman — di bawah ${safe}°`],
                [colors.warn ?? '#E0A82E', `Mungkin perlu support — ${safe}°–${warn}°`],
                [colors.critical ?? '#C0392B', `Wajib support — di atas ${warn}°`],
            ];
            footnote = 'Sudut diukur dari bidang tegak: dinding tegak 0°, langit-langit mendatar 90°.';
        } else {
            const colors = analysis.wallThickness?.colors ?? {};
            const minimum = item ? this.minWallThickness(item) : 1;

            heading = 'Wall Thickness Analysis';
            entries = [
                [colors.safe ?? '#3FA45B', `Aman — ${formatNumber(minimum, 1)} mm ke atas`],
                [colors.thin ?? '#C0392B', `Terlalu tipis — di bawah ${formatNumber(minimum, 1)} mm`],
            ];

            if (item?.painted?.skipped) {
                footnote = 'Model ini terlalu rapat untuk diukur seketika, jadi pewarnaannya dilewati.';
            } else if (item?.painted?.sampled) {
                footnote = `Diukur dari ${formatCount(item.painted.measured)} titik sampel; dinding tertipis ${formatNumber(item.painted.minMm, 2)} mm.`;
            } else if (item?.painted?.minMm !== undefined) {
                footnote = `Dinding tertipis yang terukur ${formatNumber(item.painted.minMm, 2)} mm.`;
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

    /* ------------------------------------------------------------ analisis */

    runAnalysis(item) {
        if (!item?.object) {
            return;
        }

        // applyWorldMatrix dibiarkan false: volume dan topologi tidak boleh
        // berubah hanya karena pengguna memutar, membalik, atau menskalakan model.
        item.metrics = analyzeGeometry(item.object, {
            maxTrianglesForTopology: this.config.limits?.maxTrianglesFullAnalysis ?? 400000,
        });

        this.refreshAnalysis(item);
    }

    /**
     * Analisis kelayakan bergantung pada teknologi dan area cetak mesin, jadi
     * disusun ulang setiap kali salah satunya berubah.
     */
    refreshAnalysis(item = this.item) {
        if (item === null) {
            this.items.forEach((model) => this.refreshAnalysis(model));

            return;
        }

        if (!item?.metrics || !item.dimensions) {
            return;
        }

        const technology = this.technologyOf(item);
        const volume = this.plate.size;

        item.analysis = buildChecks(
            item.metrics,
            item.dimensions,
            technology
                ? {
                    ...technology,
                    // Batas yang berlaku adalah mesin yang dipilih, bukan
                    // kapasitas teoretis teknologinya.
                    code: `${this.printerConfig().name}`,
                    buildVolume: { x: volume.width, y: volume.depth, z: volume.height },
                }
                : null,
            {
                minDimension: this.config.limits?.minDimensionMm ?? 2,
                warnDimension: this.config.limits?.warnDimensionMm ?? 5,
            }
        );

        if (item.id === this.activeId) {
            this.renderAnalysis();
        }
    }

    renderAnalysis() {
        const item = this.item;

        if (!this.analysisList || !item?.analysis) {
            return;
        }

        const presentation = {
            [CHECK_STATUS.PASS]: { dot: 'bg-emerald-500', chip: 'bg-emerald-50 text-emerald-700 border-emerald-200', label: 'Aman' },
            [CHECK_STATUS.WARN]: { dot: 'bg-amber-500', chip: 'bg-amber-50 text-amber-700 border-amber-200', label: 'Perlu perbaikan' },
            [CHECK_STATUS.FAIL]: { dot: 'bg-brand-600', chip: 'bg-brand-50 text-brand-700 border-brand-200', label: 'Bermasalah' },
            [CHECK_STATUS.SKIP]: { dot: 'bg-ink-300', chip: 'bg-ink-50 text-ink-500 border-ink-200', label: 'Dilewati' },
        };

        this.analysisList.innerHTML = item.analysis.checks
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

        const overall = ANALYSIS_PRESENTATION[item.analysis.status] ?? ANALYSIS_PRESENTATION.warning;

        if (this.analysisBadge) {
            this.analysisBadge.className =
                `inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-bold ${overall.classes}`;
            this.analysisBadge.textContent = `${overall.emoji} ${overall.label}`;
        }

        if (this.analysisSummary) {
            this.analysisSummary.textContent = overall.summary;
        }

        hide(this.analysisPlaceholder);
        show(this.analysisPanel, 'block');
    }

    /* ---------------------------------------------------- informasi model */

    renderStats() {
        const item = this.item;

        if (!item?.metrics) {
            return;
        }

        const { x, y, z } = item.dimensions;
        const scale = item.settings.scale;
        const volumeCm3 = (item.metrics.volumeMm3 / 1000) * scale ** 3;

        this.setStat('name', item.file.name);
        this.setStat('format', `${item.format} (${item.format === 'STL' ? 'Stereolithography' : 'Wavefront'})`);
        this.setStat('size', this.formatBytes(item.file.size));
        this.setStat('vertices', formatCount(item.metrics.vertices));
        this.setStat('triangles', formatCount(item.metrics.triangles));
        this.setStat(
            'dimensions',
            `${formatNumber(x, 2)} × ${formatNumber(y, 2)} × ${formatNumber(z, 2)} mm` +
                (scale !== 1 ? ` (skala ${Math.round(scale * 100)}%)` : '')
        );
        this.setStat(
            'bounding-box',
            `min (${formatNumber(item.boundingBox.min.x, 1)}, ${formatNumber(item.boundingBox.min.y, 1)}, ${formatNumber(item.boundingBox.min.z, 1)}) — ` +
                `max (${formatNumber(item.boundingBox.max.x, 1)}, ${formatNumber(item.boundingBox.max.y, 1)}, ${formatNumber(item.boundingBox.max.z, 1)}) mm`
        );
        this.setStat('surface-area', `${formatNumber((item.metrics.surfaceAreaMm2 / 100) * scale ** 2, 2)} cm²`);

        const volumeNote = this.root.querySelector('[data-volume-note]');

        if (item.metrics.topologyAnalyzed && !item.metrics.isWatertight) {
            this.setStat('volume', `± ${formatNumber(volumeCm3, 3)} cm³`);
            show(volumeNote, 'block');
        } else {
            this.setStat('volume', `${formatNumber(volumeCm3, 3)} cm³`);
            hide(volumeNote);
        }
    }

    setStat(name, value) {
        const el = this.root.querySelector(`[data-stat="${name}"]`);

        if (el) {
            el.textContent = value;
        }
    }

    /* ------------------------------------------------------ daftar model */

    bindList() {
        // Daftar digambar ulang setiap ada perubahan, jadi penanganannya
        // didelegasikan ke wadahnya agar tidak perlu memasang listener berulang.
        this.listEl?.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-model]');

            if (remove) {
                event.stopPropagation();
                this.removeItem(Number(remove.dataset.removeModel));

                return;
            }

            const select = event.target.closest('[data-select-model]');

            if (select) {
                this.activate(Number(select.dataset.selectModel));
            }
        });

        this.root.querySelector('[data-action="clear-all"]')?.addEventListener('click', () => this.clearAll());
    }

    renderList() {
        if (!this.listEl) {
            return;
        }

        if (this.items.length === 0) {
            this.listEl.innerHTML = '';
            hide(this.listPanel);

            return;
        }

        show(this.listPanel, 'block');

        if (this.listCountEl) {
            this.listCountEl.textContent = `(${this.items.length}/${this.maxModels})`;
        }

        this.listEl.innerHTML = this.items
            .map((item, index) => {
                const active = item.id === this.activeId;
                const status = ANALYSIS_PRESENTATION[item.analysis?.status] ?? ANALYSIS_PRESENTATION.warning;
                const weight = item.estimate ? `${formatNumber(item.estimate.totalWeightG, 1)} gram` : '—';
                const cost = item.estimate ? formatCurrency(item.estimate.totalCost) : '—';
                const scale = Math.round(item.settings.scale * 100);

                return `
                    <li>
                        <div class="relative h-full">
                            <button type="button"
                                    class="model-card ${active ? 'is-active' : ''}"
                                    data-select-model="${item.id}"
                                    aria-pressed="${active}">
                                <span class="flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full ${active ? 'bg-brand-600 text-white' : 'bg-ink-100 text-ink-500'} text-[0.65rem] font-bold">${index + 1}</span>
                                    <span class="min-w-0 flex-1 truncate pr-6 font-display text-sm font-bold text-ink-900" title="${escapeAttribute(item.file.name)}">${escapeHtml(item.file.name)}</span>
                                    <span class="inline-block h-3.5 w-3.5 shrink-0 rounded-full border border-ink-200" style="background-color: ${escapeAttribute(this.colorHex(item.settings.color))}"></span>
                                </span>

                                <span class="mt-2 block text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-ink-400">
                                    ${item.format} &middot; ${escapeHtml(item.settings.technology)} ${escapeHtml(item.settings.material)}${scale !== 100 ? ` &middot; ${scale}%` : ''}
                                </span>

                                <span class="mt-3 flex items-end justify-between gap-2 border-t border-ink-100 pt-3">
                                    <span>
                                        <span class="block text-[0.6rem] font-semibold uppercase tracking-[0.1em] text-ink-400">Berat</span>
                                        <span class="block text-xs font-bold text-ink-800">${weight}</span>
                                    </span>
                                    <span class="text-right">
                                        <span class="block text-[0.6rem] font-semibold uppercase tracking-[0.1em] text-ink-400">Estimasi</span>
                                        <span class="block font-display text-sm font-bold text-brand-700">${cost}</span>
                                    </span>
                                </span>

                                <span class="mt-3 flex flex-wrap gap-1.5">
                                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full border px-2.5 py-1 text-[0.6rem] font-bold ${status.classes}">
                                        ${status.emoji} ${status.label}
                                    </span>
                                    ${item.fit?.exceeds
                                        ? '<span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-2.5 py-1 text-[0.6rem] font-bold text-brand-700">⚠ Di luar area cetak</span>'
                                        : ''}
                                    ${item.overlapping
                                        ? '<span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[0.6rem] font-bold text-amber-700">⚠ Bertindih model lain</span>'
                                        : ''}
                                </span>
                            </button>

                            <button type="button"
                                    class="model-card-remove"
                                    data-remove-model="${item.id}"
                                    aria-label="Hapus model ${escapeAttribute(item.file.name)}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4" aria-hidden="true">
                                    <path d="M18 6 6 18M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </li>
                `;
            })
            .join('');
    }

    /** Hapus satu model dari daftar beserta geometri dan supportnya. */
    removeItem(id) {
        const index = this.items.findIndex((item) => item.id === Number(id));

        if (index === -1) {
            return;
        }

        const [item] = this.items.splice(index, 1);
        const wasActive = item.id === this.activeId;

        this.disposeItem(item);

        if (this.items.length === 0) {
            this.activeId = null;
            this.resetEmptyState();

            return;
        }

        this.arrangeItems();
        this.items.forEach((model) => this.refreshEstimate(model, { render: false }));

        if (!wasActive) {
            this.renderActiveItem();

            return;
        }

        // Setelah menghapus, model berikutnya di daftar yang ditampilkan —
        // atau model sebelumnya bila yang dihapus berada di urutan terakhir.
        this.activeId = null;
        this.activate((this.items[index] ?? this.items[index - 1]).id);
    }

    clearAll() {
        this.items.forEach((item) => this.disposeItem(item));
        this.items = [];
        this.activeId = null;
        this.resetEmptyState();
    }

    /** Lepaskan geometri, material, support, dan penanda satu model dari memori. */
    disposeItem(item) {
        this.detachSupport(item);
        disposeExceedMarkers(item.exceedGroup);
        item.exceedGroup = null;

        item.object.traverse((child) => {
            if (child.isMesh) {
                child.geometry.dispose();

                if (Array.isArray(child.material)) {
                    child.material.forEach((material) => material.dispose());
                } else {
                    child.material.dispose();
                }
            }
        });

        this.scene.remove(item.pivot);
        item.pivot = null;
        item.object = null;
    }

    /** Kembalikan halaman ke keadaan sebelum ada model yang dimuat. */
    resetEmptyState() {
        this.input.value = '';
        this.objectRotateMode = false;
        this.objectRotateToggle?.setAttribute('aria-pressed', 'false');
        this.controls.enableRotate = true;
        this.canvasHost.style.cursor = '';
        this.selectionBox.visible = false;
        this.plate.setExceeded(false);
        this.disposeSlots();

        hide(this.stage);
        hide(this.infoEl);
        hide(this.analysisPanel);
        hide(this.estimatePanel);
        hide(this.orientationPanel);
        hide(this.buildWarning);
        hide(this.analysisLegend);
        show(this.emptyState, 'flex');
        show(this.placeholderEl, 'block');
        show(this.analysisPlaceholder, 'block');
        show(this.estimatePlaceholder, 'block');
        show(this.orientationPlaceholder, 'block');

        if (this.analysisBadge) {
            this.analysisBadge.className =
                'inline-flex items-center gap-2 rounded-full border border-ink-200 bg-ink-50 px-4 py-2 text-sm font-bold text-ink-500';
            this.analysisBadge.textContent = 'Menunggu model';
        }

        this.setActionsEnabled(false);
        this.renderActiveLabels();
        this.renderSupportStats();
        this.renderScaleResults();
        this.renderList();
        this.renderQuoteSummary();
        this.renderBuildInfo();
        this.frameBuildPlate();
    }

    /* ------------------------------------------------- ringkasan penawaran */

    /**
     * Tabel seluruh model yang akan dicetak beserta totalnya.
     *
     * Inilah yang benar-benar dikirim saat pengguna menekan "Minta Penawaran":
     * satu permintaan berisi semua baris di tabel ini.
     */
    renderQuoteSummary() {
        if (!this.summaryRows) {
            return;
        }

        const ready = this.readyItems();

        if (ready.length === 0) {
            this.summaryRows.innerHTML = '';
            hide(this.summaryPanel);
            show(this.summaryPlaceholder, 'block');
            this.updateQuotationButton();

            return;
        }

        this.summaryRows.innerHTML = ready
            .map((item, index) => {
                const active = item.id === this.activeId;
                const scale = Math.round(item.settings.scale * 100);

                return `
                    <tr class="${active ? 'bg-brand-50/50' : ''}">
                        <td class="py-3 pr-3 font-mono text-xs text-ink-400">${index + 1}</td>
                        <td class="max-w-[220px] px-3 py-3">
                            <p class="truncate font-semibold text-ink-900" title="${escapeAttribute(item.file.name)}">${escapeHtml(item.file.name)}</p>
                            <p class="text-[0.65rem] uppercase tracking-[0.1em] text-ink-400">
                                ${item.format} &middot; ${this.formatBytes(item.file.size)}${scale !== 100 ? ` &middot; skala ${scale}%` : ''}
                            </p>
                        </td>
                        <td class="px-3 py-3">
                            <p class="font-semibold text-ink-800">${escapeHtml(item.settings.technology)}</p>
                            <p class="text-xs text-ink-400">${escapeHtml(item.settings.material)}</p>
                        </td>
                        <td class="px-3 py-3 text-right text-ink-700">${formatCount(item.settings.quantity)} unit</td>
                        <td class="px-3 py-3 text-right text-ink-700">${formatNumber(item.estimate.totalWeightG * item.settings.quantity, 1)} gr</td>
                        <td class="px-3 py-3 text-right text-ink-700">${formatDuration(item.estimate.totalMinutes)}</td>
                        <td class="py-3 pl-3 text-right font-display font-bold text-brand-700">${formatCurrency(item.estimate.totalCost)}</td>
                    </tr>
                `;
            })
            .join('');

        const totals = this.totals();

        this.setTotal('models', `${formatCount(ready.length)} model`);
        this.setTotal('weight', `${formatNumber(totals.weightG, 1)} gram`);
        this.setTotal('time', formatDuration(totals.minutes));
        this.setTotal('cost', formatCurrency(totals.cost));

        this.renderTotalBreakdown(totals.breakdown);

        hide(this.summaryPlaceholder);
        show(this.summaryPanel, 'block');
        this.updateQuotationButton();
    }

    /**
     * Total seluruh model.
     *
     * Waktu dijumlahkan dengan asumsi model dicetak berurutan pada satu mesin —
     * cara baca yang sama dipakai server saat menyimpan permintaan.
     */
    totals() {
        const breakdown = { material: 0, machine_time: 0, support: 0, finishing: 0, quality_control: 0, total: 0 };

        const summary = this.readyItems().reduce(
            (carry, item) => {
                COST_COMPONENTS.forEach(([key]) => {
                    breakdown[key] += item.estimate.breakdown?.[key] ?? 0;
                });

                breakdown.total += item.estimate.breakdown?.total ?? 0;

                return {
                    weightG: carry.weightG + item.estimate.totalWeightG * item.settings.quantity,
                    minutes: carry.minutes + item.estimate.totalMinutes,
                    cost: carry.cost + item.estimate.totalCost,
                };
            },
            { weightG: 0, minutes: 0, cost: 0 }
        );

        return { ...summary, breakdown };
    }

    /** Rincian biaya gabungan seluruh model pada ringkasan penawaran. */
    renderTotalBreakdown(breakdown) {
        COST_COMPONENTS.forEach(([key]) => {
            const el = this.root.querySelector(`[data-total-cost="${key}"]`);

            if (el) {
                el.textContent = formatCurrency(breakdown[key] ?? 0);
            }
        });

        const total = this.root.querySelector('[data-total-cost="total"]');

        if (total) {
            total.textContent = formatCurrency(breakdown.total ?? 0);
        }
    }

    setTotal(name, value) {
        const el = this.root.querySelector(`[data-total="${name}"]`);

        if (el) {
            el.textContent = value;
        }
    }

    /* ------------------------------------------------------------- toolbar */

    bindToolbar() {
        this.root.querySelector('[data-action="reset"]')?.addEventListener('click', () => this.frameBuildPlate());
        this.root.querySelector('[data-action="focus"]')?.addEventListener('click', () => this.focusActiveItem());

        this.root.querySelector('[data-action="arrange"]')?.addEventListener('click', () => {
            this.arrangeItems();
            this.renderActiveItem();
        });

        this.root.querySelector('[data-action="rotate"]')?.addEventListener('click', (event) => {
            this.autoRotate = !this.autoRotate;
            this.controls.autoRotateSpeed = 1.6;
            event.currentTarget.setAttribute('aria-pressed', String(this.autoRotate));
        });

        this.root.querySelector('[data-action="grid"]')?.addEventListener('click', (event) => {
            this.plate.setGridVisible(!this.plate.gridVisible);
            event.currentTarget.setAttribute('aria-pressed', String(this.plate.gridVisible));
        });

        this.root.querySelector('[data-action="axis"]')?.addEventListener('click', (event) => {
            this.plate.setAxesVisible(!this.plate.axesVisible);
            event.currentTarget.setAttribute('aria-pressed', String(this.plate.axesVisible));
        });

        this.root.querySelector('[data-action="fullscreen"]')?.addEventListener('click', () => this.toggleFullscreen());

        // Sembunyikan/tampilkan support tanpa mengubah perhitungan estimasi.
        this.root.querySelector('[data-action="support-visibility"]')?.addEventListener('click', (event) => {
            this.supportVisible = ! this.supportVisible;
            event.currentTarget.setAttribute('aria-pressed', String(this.supportVisible));

            this.items.forEach((item) => {
                if (item.supportGroup) {
                    item.supportGroup.visible = this.supportVisible;
                }
            });
        });

        document.addEventListener('fullscreenchange', () => {
            const active = document.fullscreenElement === this.fullscreenTarget;
            this.root.querySelector('[data-action="fullscreen"]')?.setAttribute('aria-pressed', String(active));
            // Ukuran kanvas berubah drastis saat masuk/keluar fullscreen.
            setTimeout(() => this.renderer.setSize(this.canvasHost.clientWidth, this.canvasHost.clientHeight), 60);
        });

        this.root.querySelectorAll('[data-mode]').forEach((button) => {
            button.addEventListener('click', () => this.setDisplayMode(button.dataset.mode));
        });

        this.root.querySelectorAll('[data-view-mode]').forEach((button) => {
            button.addEventListener('click', () => this.setViewMode(button.dataset.viewMode));
        });

        this.root.querySelectorAll('[data-view]').forEach((button) => {
            button.addEventListener('click', () => this.applyViewPreset(button.dataset.view));
        });

        // Tombol hapus pada toolbar menghapus model yang sedang ditampilkan.
        this.root.querySelector('[data-action="clear"]')?.addEventListener('click', () => {
            if (this.activeId !== null) {
                this.removeItem(this.activeId);
            }
        });

        this.root.querySelector('[data-error-dismiss]')?.addEventListener('click', () => this.hideError());
    }

    setDisplayMode(mode) {
        this.displayMode = mode;

        this.root.querySelectorAll('[data-mode]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.mode === mode));
        });

        this.items.forEach((item) => this.applyAppearance(item));
    }

    /* ------------------------------------------------------------- kamera */

    /** Bingkai seluruh build plate, sama seperti tampilan awal Cura. */
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

    /** Dekatkan kamera ke model yang sedang dipilih. */
    focusActiveItem() {
        const item = this.item;

        if (!item?.dimensions) {
            return;
        }

        const size = new THREE.Vector3(item.dimensions.x, item.dimensions.y, item.dimensions.z);

        this.homeTarget = new THREE.Vector3(item.placement.x, size.y / 2, item.placement.z);
        this.frameRadius = Math.max(size.length() / 2, 0.5);
        this.frameDistance = (this.frameRadius / Math.sin((this.camera.fov * Math.PI) / 360)) * 1.25;

        this.camera.near = Math.max(this.frameDistance / 1000, 0.01);
        this.camera.far = Math.max(this.frameDistance * 100, this.plate.size.width * 20);

        this.applyViewDirection(this.homeDirection ?? new THREE.Vector3(0.85, 0.62, 1).normalize());
    }

    resetView() {
        this.frameBuildPlate();
    }

    /** Pindahkan kamera ke arah tertentu dengan jarak framing yang sama. */
    applyViewDirection(direction) {
        if (!this.homeTarget || !direction) {
            return;
        }

        this.homeDirection = direction.clone().normalize();

        this.camera.position
            .copy(this.homeDirection)
            .multiplyScalar(this.frameDistance)
            .add(this.homeTarget);

        this.camera.updateProjectionMatrix();
        this.controls.target.copy(this.homeTarget);
        this.controls.update();
    }

    applyViewPreset(preset) {
        const directions = {
            front: [0, 0, 1],
            back: [0, 0, -1],
            right: [1, 0, 0],
            left: [-1, 0, 0],
            // Sedikit digeser agar OrbitControls tidak terkunci di kutub.
            top: [0.0001, 1, 0.0001],
            bottom: [0.0001, -1, 0.0001],
            isometric: [0.85, 0.62, 1],
        };

        const direction = directions[preset];

        if (direction) {
            this.applyViewDirection(new THREE.Vector3(...direction));
        }
    }

    async toggleFullscreen() {
        const target = this.fullscreenTarget ?? this.canvasHost;

        try {
            if (document.fullscreenElement) {
                await document.exitFullscreen();
            } else {
                await target.requestFullscreen();
            }
        } catch (error) {
            console.warn('Fullscreen ditolak browser:', error);
        }
    }

    /* --------------------------------------------- teknologi & estimasi */

    bindProduction() {
        if (!this.technologySelect || !this.materialSelect) {
            return;
        }

        this.populateTechnologies();

        this.technologySelect.addEventListener('change', () => {
            this.populateMaterials();
            this.renderTechnologyDescription();
            this.syncSupportAvailability();
            this.captureSettings();

            // Teknologi baru punya kepadatan bawaannya sendiri, jadi infill
            // disetel ulang kecuali pengguna memang sudah memilih sendiri.
            if (this.item) {
                this.item.settings.infillDensity = Number(this.selectedTechnology()?.defaultInfill ?? 1);
                this.renderInfillInputs(this.item);
            }

            this.syncHollowAvailability();
            this.renderHollowInputs(this.item);
            this.renderResolutionNotice();
            this.refreshAnalysis();
            this.refreshEstimate();
            this.repaintItem(this.item);
        });

        this.materialSelect.addEventListener('change', () => {
            this.captureSettings();
            this.refreshEstimate();
        });

        this.quantityInput?.addEventListener('input', () => {
            this.captureSettings();
            this.refreshEstimate();
        });

        // Support dihitung ulang seketika, tanpa memuat ulang halaman.
        this.supportCheckbox?.addEventListener('change', () => {
            this.captureSettings();
            this.refreshEstimate();
        });

        // Resolusi memakai radio: hanya satu pilihan aktif dalam satu waktu.
        this.resolutionInputs.forEach((input) => {
            input.addEventListener('change', () => {
                this.captureSettings();
                this.refreshEstimate();
            });
        });

        this.renderTechnologyDescription();
        this.syncSupportAvailability();
        this.updateQuotationButton();
    }

    /**
     * Simpan nilai kontrol ke model yang sedang dipilih.
     *
     * Panel pengaturan hanya satu, tetapi nilainya milik model aktif — inilah
     * yang membuat mengubah material satu model tidak menyentuh model lain.
     */
    captureSettings() {
        const item = this.item;

        if (!item) {
            return;
        }

        item.settings = {
            ...item.settings,
            technology: this.technologySelect?.value ?? item.settings.technology,
            material: this.materialSelect?.value ?? item.settings.material,
            quantity: this.quantity(),
            resolution: this.selectedResolutionKey(),
            support: Boolean(this.supportCheckbox?.checked && !this.supportCheckbox.disabled),
        };
    }

    /**
     * Sesuaikan ketersediaan checkbox support dengan teknologi terpilih.
     * MJF tidak memerlukan support sama sekali, jadi opsinya dinonaktifkan
     * beserta penjelasannya alih-alih diam-diam menghasilkan 0 gram.
     */
    syncSupportAvailability() {
        if (!this.supportCheckbox) {
            return;
        }

        const technology = this.selectedTechnology();
        const required = isSupportRequired(technology);

        this.supportCheckbox.disabled = ! required;

        if (! required) {
            this.supportCheckbox.checked = false;
        }

        const wrapper = this.supportCheckbox.closest('[data-support-field]');
        wrapper?.classList.toggle('opacity-50', ! required);

        if (this.supportNoteEl) {
            const note = supportNote(technology);
            this.supportNoteEl.textContent = note ?? '';
            note ? show(this.supportNoteEl, 'block') : hide(this.supportNoteEl);
        }
    }

    /* -------------------------------------------------------- resolusi */

    selectedResolutionKey() {
        const checked = [...this.resolutionInputs].find((input) => input.checked);

        return checked?.value ?? this.config.defaultResolution ?? '0.25';
    }

    resolutionOf(item) {
        return this.config.resolutions?.[item?.settings?.resolution] ?? null;
    }

    selectedResolution() {
        return this.config.resolutions?.[this.selectedResolutionKey()] ?? null;
    }

    /**
     * Beri tahu bila resolusi terpilih berada di luar rentang lapisan yang
     * benar-benar tersedia pada teknologi terpilih — MJF misalnya bekerja pada
     * tebal lapisan tetap 0,08 mm.
     */
    renderResolutionNotice() {
        if (! this.resolutionNotice) {
            return;
        }

        const technology = this.selectedTechnology();
        const range = technology?.layerHeightRange;
        const resolution = this.selectedResolution();

        if (! range || ! resolution) {
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

        // Dua desimal, kecuali angkanya memang butuh tiga (mis. 0,025 mm).
        const mm = (value) => formatNumber(value, (value * 100) % 1 === 0 ? 2 : 3);

        const rangeText = min === max
            ? `tetap ${mm(min)} mm`
            : `${mm(min)} – ${mm(max)} mm`;

        this.resolutionNotice.textContent =
            `Tebal lapisan ${technology.code} ${rangeText}. Pilihan ini di luar rentang tersebut, ` +
            'tim kami akan menyesuaikannya saat produksi.';

        show(this.resolutionNotice, 'block');
    }

    /* -------------------------------------------- visualisasi support */

    /**
     * Bentuk ulang visualisasi support satu model sesuai orientasinya saat ini.
     *
     * Support selalu tumbuh tegak dari meja cetak, jadi grupnya menjadi anak
     * scene — bukan anak pivot model — dan dibentuk ulang setiap kali orientasi,
     * skala, atau posisinya di atas plate berubah.
     */
    rebuildSupportVisual(item) {
        this.detachSupport(item);

        if (! item?.object || ! this.supportEnabledFor(item)) {
            item.supportStats = null;

            if (item?.id === this.activeId) {
                this.renderSupportStats();
            }

            return;
        }

        const visual = this.config.support?.visual ?? {};

        const result = buildSupport(item.pivot, {
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

        item.supportGroup = result.group;
        item.supportGroup.visible = this.supportVisible;
        item.supportStats = result;
        this.scene.add(item.supportGroup);

        if (item.id === this.activeId) {
            this.renderSupportStats();
        }
    }

    /** Lepaskan support satu model dari scene lalu bebaskan memorinya. */
    detachSupport(item) {
        if (!item?.supportGroup) {
            return;
        }

        this.scene.remove(item.supportGroup);
        disposeSupport(item.supportGroup);
        item.supportGroup = null;
    }

    supportEnabledFor(item) {
        return Boolean(item?.settings.support) && isSupportRequired(this.technologyOf(item));
    }

    renderSupportStats() {
        const wrapper = this.root.querySelector('[data-support-stats]');
        const legend = this.root.querySelector('[data-support-legend]');

        if (! wrapper) {
            return;
        }

        const stats = this.item?.supportStats ?? null;
        const emptyNote = this.root.querySelector('[data-support-empty]');
        const toggle = this.root.querySelector('[data-action="support-visibility"]');

        if (! stats) {
            hide(wrapper);
            hide(legend);
            hide(emptyNote);

            if (toggle) {
                toggle.disabled = true;
            }

            return;
        }

        // Support berhasil dihitung tetapi memang tidak ada yang perlu ditopang.
        if (stats.pillarCount === 0) {
            hide(wrapper);
            hide(legend);
            show(emptyNote, 'block');

            if (toggle) {
                toggle.disabled = true;
            }

            return;
        }

        hide(emptyNote);
        show(legend, 'block');

        this.setStatText('support-pillars', formatCount(stats.pillarCount));
        this.setStatText('support-overhang', formatCount(stats.overhangFaces));
        this.setStatText('support-grid', `${formatNumber(stats.cellSizeMm, 1)} mm`);
        show(wrapper, 'block');

        if (toggle) {
            toggle.disabled = false;
            toggle.setAttribute('aria-pressed', String(this.supportVisible));
        }
    }

    setStatText(name, value) {
        const el = this.root.querySelector(`[data-support-stat="${name}"]`);

        if (el) {
            el.textContent = value;
        }
    }

    populateTechnologies() {
        const codes = Object.keys(this.config.technologies ?? {});

        this.technologySelect.innerHTML = codes
            .map((code) => `<option value="${code}">${code} — ${escapeHtml(this.config.technologies[code].name)}</option>`)
            .join('');

        this.populateMaterials();
    }

    populateMaterials() {
        const technology = this.selectedTechnology();

        this.materialSelect.innerHTML = (technology?.materials ?? [])
            .map((material) => `<option value="${escapeHtml(material.name)}">${escapeHtml(material.name)}</option>`)
            .join('');
    }

    renderTechnologyDescription() {
        const technology = this.selectedTechnology();

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

    selectedTechnology() {
        return this.technologyByCode(this.technologySelect?.value);
    }

    technologyByCode(code) {
        const technology = this.config.technologies?.[code];

        return technology ? { ...technology, code } : null;
    }

    /** Teknologi milik satu model, dibaca dari pengaturannya sendiri. */
    technologyOf(item) {
        return this.technologyByCode(item?.settings?.technology);
    }

    materialOf(item) {
        const technology = this.technologyOf(item);

        return technology?.materials?.find((material) => material.name === item?.settings?.material) ?? null;
    }

    quantity() {
        return Math.max(1, parseInt(this.quantityInput?.value ?? '1', 10) || 1);
    }

    /**
     * Hitung ulang estimasi satu model dari pengaturannya sendiri.
     *
     * Perhitungan tidak membaca nilai kontrol di halaman, melainkan
     * `item.settings`, sehingga model yang sedang tidak ditampilkan pun tetap
     * punya estimasi yang benar. Seluruhnya berjalan di browser — tidak ada
     * satu pun permintaan ke server.
     */
    refreshEstimate(item = this.item, { render = true } = {}) {
        if (!item) {
            this.updateQuotationButton();

            return;
        }

        const technology = this.technologyOf(item);
        const material = this.materialOf(item);

        if (!item.metrics || !technology || !material) {
            this.updateQuotationButton();

            return;
        }

        const geometryVolumeCm3 = item.metrics.volumeMm3 / 1000;
        const scale = item.settings.scale;

        // Support digambar lebih dulu, supaya volumenya dapat diukur langsung
        // dari geometri yang benar-benar dibentuk — geometri itu sudah ikut
        // terskalakan, jadi volumenya tidak perlu dikalikan lagi.
        this.rebuildSupportVisual(item);

        item.support = estimateSupport(technology, material, geometryVolumeCm3 * scale ** 3, item.dimensions, {
            enabled: this.supportEnabledFor(item),
            config: this.config.support,
            measuredVolumeCm3: item.supportStats?.materialVolumeCm3,
        });

        item.estimate = calculateEstimate(
            technology,
            material,
            geometryVolumeCm3,
            item.settings.quantity,
            item.support,
            this.resolutionOf(item),
            {
                scale,
                surfaceAreaCm2: item.metrics.surfaceAreaMm2 / 100,
                infillDensity: item.settings.infillDensity,
                infillPattern: item.settings.infillPattern,
                patterns: this.config.infill?.patterns,
                hollow: {
                    ...item.settings.hollow,
                    drainHoles: this.config.hollow?.drainCount ?? 2,
                },
                printer: this.printerConfig(),
                cost: this.config.cost,
            }
        );

        if (!render) {
            return;
        }

        if (item.id === this.activeId) {
            this.renderEstimate();
        }

        this.renderList();
        this.renderQuoteSummary();
    }

    /** Tampilkan estimasi model aktif pada panel Estimasi Printing. */
    renderEstimate() {
        const item = this.item;

        if (!item?.estimate) {
            return;
        }

        const technology = this.technologyOf(item);
        const resolution = this.resolutionOf(item);
        const result = item.estimate;

        this.setEstimate('technology', `${technology.code} — ${technology.name}`);
        this.setEstimate('material', item.settings.material);
        this.setEstimate('resolution', resolution
            ? `${formatNumber(resolution.layerHeight, 2)} mm (${resolution.name})`
            : '—');
        this.setEstimate('quality', resolution?.quality ?? '—');
        this.renderResolutionNotice();
        this.setEstimate('volume', `${formatNumber(result.totalMaterialVolumeCm3, 2)} cm³`);
        this.setEstimate('weight', `${formatNumber(result.weightG, 1)} gram`);
        this.setEstimate('support-weight', `${formatNumber(result.supportWeightG, 1)} gram`);
        this.setEstimate('total-weight', `${formatNumber(result.totalWeightG, 1)} gram`);
        this.setEstimate('time', formatDuration(result.totalMinutes));
        this.setEstimate('cost', formatCurrency(result.totalCost));
        this.setEstimate('quantity', `${item.settings.quantity} unit`);

        // Baris berat support hanya relevan bila supportnya aktif dan terbentuk.
        this.supportRow?.classList.toggle('opacity-40', result.supportWeightG <= 0);

        // Rincian biaya diperbarui bersamaan dengan totalnya.
        COST_COMPONENTS.forEach(([key]) => {
            this.setCost(key, formatCurrency(result.breakdown?.[key] ?? 0));
        });

        this.setCost('total', formatCurrency(result.breakdown?.total ?? result.totalCost));

        const fillEl = this.root.querySelector('[data-infill-fill]');

        if (fillEl) {
            fillEl.textContent = result.hollowEnabled
                ? 'Hollow'
                : formatPercent(result.fillFactor, result.fillFactor < 0.1 ? 1 : 0);
        }

        this.renderHollowSaving(item);
        this.renderScaleResults();

        hide(this.estimatePlaceholder);
        show(this.estimatePanel, 'block');
        this.updateQuotationButton();
    }

    setEstimate(name, value) {
        const el = this.root.querySelector(`[data-estimate="${name}"]`);

        if (el) {
            el.textContent = value;
        }
    }

    setCost(name, value) {
        const el = this.root.querySelector(`[data-cost="${name}"]`);

        if (el) {
            el.textContent = value;
        }
    }

    updateQuotationButton() {
        if (!this.quotationButton) {
            return;
        }

        this.quotationButton.disabled = this.readyItems().length === 0;
    }

    /**
     * Seluruh model yang dikirim bersama dalam satu permintaan penawaran.
     *
     * @returns {{items: Array<object>, printer: object, totals: object}|null}
     */
    submissionPayload() {
        const ready = this.readyItems();

        if (ready.length === 0) {
            return null;
        }

        const volume = this.plate.size;

        return {
            printer: {
                key: this.printerKey,
                name: this.printerConfig().name,
                build_volume: { x: volume.width, y: volume.depth, z: volume.height },
            },
            items: ready.map((item) => this.itemPayload(item)),
            totals: this.totals(),
        };
    }

    /** Ringkasan satu model, dikirim sebagai satu baris `items[]`. */
    itemPayload(item) {
        const scale = item.settings.scale;

        return {
            file: item.file,
            format: item.format,
            technology: item.settings.technology,
            material: item.settings.material,
            quantity: item.settings.quantity,
            resolution: item.settings.resolution,
            support_enabled: this.supportEnabledFor(item),
            support_volume_cm3: item.support?.volumeCm3 ?? null,
            model_volume_cm3: item.metrics.volumeMm3 / 1000,

            scale_percent: Math.round(scale * 100),
            infill_density: item.settings.infillDensity,
            infill_pattern: item.settings.infillPattern,
            material_color: item.settings.color,
            hollow_enabled: item.settings.hollow.enabled,
            hollow_wall_thickness_mm: item.settings.hollow.wallThicknessMm,
            hollow_drain_diameter_mm: item.settings.hollow.drainDiameterMm,
            hollow_drain_position: item.settings.hollow.drainPosition,
            fits_build_volume: !item.fit?.exceeds,

            analysis_status: item.analysis?.status ?? 'warning',
            analysis: item.analysis?.checks ?? [],
            estimate: {
                totalWeightG: item.estimate.totalWeightG,
                totalMinutes: item.estimate.totalMinutes,
                totalCost: item.estimate.totalCost,
                breakdown: item.estimate.breakdown,
            },
            model_stats: {
                vertices: item.metrics.vertices,
                triangles: item.metrics.triangles,
                dimensions: item.dimensions,
                bounding_box: item.boundingBox,
                // Volume dan luas permukaan dilaporkan pada geometri aslinya;
                // server menerapkan skalanya sendiri agar hasilnya konsisten.
                volume_cm3: item.metrics.volumeMm3 / 1000,
                surface_area_cm2: item.metrics.surfaceAreaMm2 / 100,
                scaled_volume_cm3: item.estimate.modelVolumeCm3,
                watertight: item.metrics.isWatertight,
                holes: item.metrics.holeCount,
                non_manifold_edges: item.metrics.nonManifoldEdges,
                inconsistent_edges: item.metrics.inconsistentEdges,
                topology_analyzed: item.metrics.topologyAnalyzed,
                orientation: {
                    rotation_deg: {
                        x: Math.round(item.orientation.x),
                        y: Math.round(item.orientation.y),
                        z: Math.round(item.orientation.z),
                    },
                    mirrored: Object.entries(item.orientation.mirror)
                        .filter(([, value]) => value === -1)
                        .map(([axis]) => axis.toUpperCase()),
                },
            },
        };
    }

    /* ---------------------------------------------------------------- ui */

    showLoading(label) {
        this.loadingLabel.textContent = label;
        show(this.loadingEl);
    }

    hideLoading() {
        hide(this.loadingEl);
    }

    /**
     * Laporkan berkas yang gagal diproses satu per satu.
     *
     * Berkas lain yang valid sudah terlanjur dimuat, jadi pesannya menyebut nama
     * file yang bermasalah alih-alih membatalkan seluruh unggahan.
     */
    showFileProblems(problems) {
        const title = this.errorEl.querySelector('[data-viewer-error-title]');

        if (title) {
            title.textContent = problems.length > 1
                ? `${problems.length} file tidak dapat diproses`
                : 'Satu file tidak dapat diproses';
        }

        this.errorMessage.textContent = this.items.length > 0
            ? 'File berikut dilewati. Model lain yang valid tetap dimuat dan dapat Anda tinjau.'
            : 'File berikut dilewati.';

        if (this.errorList) {
            this.errorList.innerHTML = problems
                .map(
                    (problem) => `
                        <li class="rounded-xl border border-brand-200 bg-white/70 px-4 py-3">
                            <p class="break-all text-sm font-bold text-brand-900">${escapeHtml(problem.name)}</p>
                            <p class="mt-1 text-xs leading-relaxed text-brand-800">${escapeHtml(problem.message)}</p>
                        </li>
                    `
                )
                .join('');

            show(this.errorList, 'block');
        }

        show(this.errorEl, 'block');
        this.errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    hideError() {
        hide(this.errorEl);
        hide(this.errorList);
    }

    formatBytes(bytes) {
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
}

/** Jaga sudut tetap dalam rentang 0–359 derajat. */
function normalizeAngle(degrees) {
    return ((degrees % 360) + 360) % 360;
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
