import SharedRenderer from './shared-renderer';
import PrinterCard from './printer-card';
import { createModelChannel, createModelId, modelStore } from './model-store';
import { toRecord } from './model-record';
import {
    extensionOf,
    isSupported,
    needsTessellation,
    SUPPORTED_EXTENSIONS,
    SUPPORTED_LABEL,
    tessellateStep,
} from './model-formats';
import {
    allowsHollow,
    allowsSupport,
    applySpecification,
    colorOptions,
    finishingOptions,
    materialCatalog,
    materialInfo,
    specificationOf,
    supportNoteFor,
    technologyOptions,
    validateModelSize,
} from './model-spec';
import {
    configureLeadTime,
    formatCount,
    formatCurrency,
    formatLeadTime,
    formatNumber,
} from './print-estimator';

const MAX_FILE_SIZE = 60 * 1024 * 1024; // 60 MB
const DEFAULT_MAX_MODELS = 25;

const show = (el, display = 'flex') => el && (el.style.display = display);
const hide = (el) => el && (el.style.display = 'none');

/** Ukuran ditulis "250 × 250 × 300 mm"; pecahannya dibulatkan satu desimal. */
const sizeFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
const sizeText = (size) =>
    size
        ? `${sizeFormatter.format(size.x)} × ${sizeFormatter.format(size.y)} × ${sizeFormatter.format(size.z)} mm`
        : '-';

/**
 * Halaman "3D Models" — daftar model yang diunggah.
 *
 * Berbeda dari sebelumnya, halaman ini tidak lagi menampilkan viewer 3D. Setiap
 * berkas yang diunggah dibaca dan dianalisis di luar layar: card mesin dikloning
 * ke tempat tersembunyi, dipakai sekali untuk mengukur geometri, menjalankan
 * analisis kelayakan, menghitung estimasi, dan memotret modelnya sebagai
 * thumbnail — lalu langsung dibuang.
 *
 * Hasilnya disimpan di IndexedDB dan ditampilkan sebagai kartu ringkas. Viewer
 * 3D beserta seluruh fitur analisis dan simulasinya dibuka di tab tersendiri
 * lewat tombol "Lihat 3D", sehingga halaman ini tetap ringan meski memuat
 * puluhan model.
 *
 * Seperti sebelumnya, berkas tidak pernah dikirim ke server sampai pengguna
 * benar-benar menekan "Minta Penawaran".
 */
export default class ModelWorkspace {
    constructor(root) {
        this.root = root;

        this.dropzone = root.querySelector('[data-dropzone]');
        this.input = root.querySelector('[data-file-input]');
        this.loadingEl = root.querySelector('[data-viewer-loading]');
        this.loadingLabel = root.querySelector('[data-viewer-loading-label]');
        this.errorEl = root.querySelector('[data-viewer-error]');
        this.errorMessage = root.querySelector('[data-viewer-error-message]');
        this.errorList = root.querySelector('[data-viewer-error-list]');

        this.template = root.querySelector('[data-printer-template]');
        this.headlessHost = root.querySelector('[data-headless-host]');
        this.listHost = root.querySelector('[data-model-list]');
        this.emptyState = root.querySelector('[data-printers-empty]');
        this.countEl = root.querySelector('[data-printer-count]');
        this.clearAllButton = root.querySelector('[data-action="clear-all"]');

        this.summaryPanel = root.querySelector('[data-quote-summary]');
        this.summaryRows = root.querySelector('[data-quote-rows]');
        this.summaryPlaceholder = root.querySelector('[data-quote-summary-placeholder]');
        this.quotationButton = root.querySelector('[data-open-quotation]');

        this.config = this.readConfig();
        this.maxModels = Number(this.config.limits?.maxModels) || DEFAULT_MAX_MODELS;
        this.viewerUrl = this.config.viewerUrl ?? '/3d-models/viewer';

        // Seluruh angka biaya hanya ditampilkan kepada pengguna yang sudah masuk.
        // Pengunjung tanpa akun tetap dapat mengunggah, meninjau, dan mengatur
        // spesifikasi modelnya — hanya harganya yang ditahan.
        this.showsPrice = this.config.auth?.check === true;

        this.renderer = new SharedRenderer();

        /** @type {Array<object>} catatan model yang tersimpan di browser */
        this.records = [];

        // Pengaturan yang diubah pada tab viewer langsung tercermin di sini.
        this.channel = createModelChannel((message) => this.onRemoteChange(message));

        this.bindUpload();
        this.bindList();
        this.clearAllButton?.addEventListener('click', () => this.clearAll());
        root.querySelector('[data-error-dismiss]')?.addEventListener('click', () => this.hideError());

        this.load();
    }

    readConfig() {
        const el = this.root.querySelector('[data-printing-config]');

        try {
            const config = JSON.parse(el?.textContent ?? '{}');

            // Tingkatan lead time dipasang sekali di sini agar seluruh modul
            // yang menampilkan estimasi memakai rentang hari yang sama.
            configureLeadTime(config.leadTime);

            return config;
        } catch {
            return { technologies: {}, limits: {} };
        }
    }

    /** Muat kembali model yang masih tersimpan dari kunjungan sebelumnya. */
    async load() {
        try {
            this.records = await modelStore.all();
        } catch (error) {
            console.error(error);
            this.records = [];
        }

        this.renderList();
        this.renderSummary();
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

                if (type === 'dragleave' && this.dropzone.contains(event.relatedTarget)) {
                    return;
                }

                this.dropzone.classList.remove('is-dragging');
            });
        });

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
     * Baca setiap berkas, analisis, lalu simpan sebagai satu entri daftar.
     *
     * Berkas yang bermasalah dilaporkan satu per satu beserta namanya, sedangkan
     * berkas lain yang valid tetap diproses.
     */
    async handleFiles(fileList) {
        this.hideError();

        const problems = [];
        const accepted = [];

        fileList.forEach((file) => {
            const problem = this.validateFile(file);

            problem ? problems.push({ name: file.name, message: problem }) : accepted.push(file);
        });

        const room = Math.max(0, this.maxModels - this.records.length);

        accepted.slice(room).forEach((file) => {
            problems.push({
                name: file.name,
                message: `Batas ${this.maxModels} model per permintaan sudah tercapai. Hapus salah satu model lebih dulu.`,
            });
        });

        const queue = accepted.slice(0, room);

        for (const [index, file] of queue.entries()) {
            const progress = queue.length > 1 ? ` (${index + 1}/${queue.length})` : '';

            try {
                this.showLoading(`Membaca ${file.name}${progress}…`);

                const buffer = await this.readFile(file);

                // Berkas CAD (STEP/STP) berisi permukaan matematis, bukan
                // segitiga, jadi ditesselasi lebih dulu menjadi mesh. Berkas
                // aslinya tetap yang dikirim ke server.
                let geometry = buffer;
                let bufferFormat = null;

                if (needsTessellation(file.name)) {
                    this.showLoading(`Mengonversi geometri CAD ${file.name}${progress}…`);
                    await this.nextFrame();

                    geometry = await tessellateStep(buffer);
                    bufferFormat = 'stl';
                }

                this.showLoading(`Menganalisis ${file.name}${progress}…`);
                // Beri kesempatan browser menggambar indikator loading sebelum
                // parsing yang berat mengunci thread utama.
                await this.nextFrame();

                const record = this.buildRecord(file, buffer, this.records.length + 1, {
                    geometry,
                    bufferFormat,
                });

                await modelStore.put(record);
                this.records.push(record);
            } catch (error) {
                console.error(error);
                problems.push({
                    name: file.name,
                    message: 'Isi file tidak sesuai dengan format yang dinyatakan, atau file rusak/tidak lengkap. Coba ekspor ulang model dari software CAD Anda.',
                });
            }
        }

        this.hideLoading();

        // Input dikosongkan supaya memilih berkas yang sama lagi tetap memicu `change`.
        this.input.value = '';

        this.renderList();
        this.renderSummary();
        this.channel.post({ type: 'refreshed' });

        if (problems.length) {
            this.showFileProblems(problems);
        }
    }

    validateFile(file) {
        const extension = extensionOf(file.name);

        if (!isSupported(file.name)) {
            return `Format “.${extension || 'tanpa ekstensi'}” belum didukung. Viewer ini dapat membaca file ${SUPPORTED_LABEL}.`;
        }

        if (file.size === 0) {
            return 'Ukuran file terbaca 0 byte. Kemungkinan file gagal tersalin atau rusak saat diunduh.';
        }

        if (file.size > MAX_FILE_SIZE) {
            return `Ukuran ${formatBytes(file.size)} melebihi batas pratinjau ${formatBytes(MAX_FILE_SIZE)}.`;
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

    /** Lepaskan thread utama sejenak agar indikator loading sempat tergambar. */
    nextFrame() {
        return new Promise((resolve) => setTimeout(resolve, 24));
    }

    /* -------------------------------------------------------------- proses */

    /**
     * Bangun satu card di luar layar, ambil hasilnya, lalu buang cardnya.
     *
     * Card yang sama persis dengan yang dipakai halaman viewer, jadi angka pada
     * daftar ini dan angka pada viewer tidak pernah berbeda.
     */
    buildRecord(file, buffer, position, { geometry = null, bufferFormat = null } = {}) {
        const id = createModelId();
        const host = document.createElement('div');

        host.innerHTML = this.template.innerHTML.replaceAll('__CARD__', `tmp-${position}`);

        const element = host.firstElementChild;
        this.headlessHost.appendChild(element);

        let card = null;

        try {
            card = new PrinterCard({
                root: element,
                file,
                buffer: geometry ?? buffer,
                bufferFormat,
                config: this.config,
                renderer: this.renderer,
            });

            return {
                ...toRecord(card, { id, position, name: file.name, size: file.size }),
                blob: new Blob([buffer], { type: file.type || 'application/octet-stream' }),

                // Berkas CAD menyimpan hasil tesselasinya sekalian, supaya tab
                // viewer tidak perlu menjalankan konversi yang berat itu lagi.
                mesh: bufferFormat ? new Blob([geometry], { type: 'model/stl' }) : null,
                meshFormat: bufferFormat,

                createdAt: Date.now(),
            };
        } finally {
            // dispose() sekaligus melepas elemen cardnya dari halaman.
            card ? card.dispose() : element.remove();
        }
    }

    /* --------------------------------------------------------------- daftar */

    bindList() {
        this.listHost?.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-model]');

            if (remove) {
                this.removeModel(remove.dataset.removeModel);

                return;
            }

            const step = event.target.closest('[data-qty-step]');

            if (step) {
                this.stepQuantity(step.dataset.model, Number(step.dataset.qtyStep));

                return;
            }

            const edit = event.target.closest('[data-edit-spec]');

            if (edit) {
                this.openSpecModal(edit.dataset.editSpec);
            }
        });

        // Ketikan langsung pada kotak jumlah, mis. mengganti 1 menjadi 12.
        this.listHost?.addEventListener('change', (event) => {
            const input = event.target.closest('[data-qty-input]');

            if (input) {
                this.setQuantity(input.dataset.model, parseInt(input.value, 10));
            }
        });

        this.bindSpecModal();
    }

    /* ------------------------------------------------------------ quantity */

    /** Tombol + dan − pada kartu; jumlah tidak pernah turun di bawah 1 pcs. */
    stepQuantity(id, delta) {
        const record = this.records.find((item) => item.id === id);

        if (!record) {
            return;
        }

        this.setQuantity(id, specificationOf(record).quantity + delta);
    }

    setQuantity(id, quantity) {
        const record = this.records.find((item) => item.id === id);

        if (!record) {
            return;
        }

        const spec = specificationOf(record);
        const next = Math.min(10000, Math.max(1, Number.isFinite(quantity) ? quantity : spec.quantity));

        if (next === spec.quantity) {
            // Nilai tidak berubah, tetapi kotaknya mungkin sempat diisi teks
            // yang tidak sah — kembalikan tampilannya.
            this.renderList();

            return;
        }

        this.updateSpecification(record, { ...spec, quantity: next });
    }

    /**
     * Terapkan spesifikasi baru lalu perbarui tampilan seketika.
     *
     * Perhitungannya berjalan di browser dari data yang sudah tersimpan, jadi
     * harga, berat, waktu, dan ringkasan penawaran ikut berubah tanpa halaman
     * dimuat ulang dan tanpa satu pun permintaan ke server.
     */
    async updateSpecification(record, spec) {
        const updated = applySpecification(record, this.config, spec);

        if (!updated) {
            return;
        }

        const index = this.records.findIndex((item) => item.id === record.id);

        if (index !== -1) {
            this.records[index] = updated;
        }

        this.renderList();
        this.renderSummary();

        try {
            await modelStore.put(updated);
            this.channel.post({ type: 'updated', id: updated.id });
        } catch (error) {
            console.error(error);
        }
    }

    async removeModel(id) {
        try {
            await modelStore.remove(id);
            this.records = await modelStore.reorder();
        } catch (error) {
            console.error(error);

            return;
        }

        this.renderList();
        this.renderSummary();
        this.channel.post({ type: 'removed', id });
    }

    async clearAll() {
        try {
            await modelStore.clear();
        } catch (error) {
            console.error(error);
        }

        this.records = [];
        this.renderList();
        this.renderSummary();
        this.channel.post({ type: 'cleared' });
    }

    /** Perubahan dari tab viewer: muat ulang daftarnya. */
    async onRemoteChange(message) {
        if (!message || typeof message !== 'object') {
            return;
        }

        await this.load();
    }

    /* ------------------------------------------------- edit specification */

    bindSpecModal() {
        this.specModal = this.root.querySelector('[data-spec-modal]');

        if (!this.specModal) {
            return;
        }

        this.specForm = this.specModal.querySelector('[data-spec-form]');
        this.specFile = this.specModal.querySelector('[data-spec-file]');
        this.specTechnology = this.specModal.querySelector('[data-spec-technology]');
        this.specMaterial = this.specModal.querySelector('[data-spec-material]');
        this.specColors = this.specModal.querySelector('[data-spec-colors]');
        this.specFinishing = this.specModal.querySelector('[data-spec-finishing]');
        this.specFinishingNote = this.specModal.querySelector('[data-spec-finishing-note]');
        this.specQuantity = this.specModal.querySelector('[data-spec-quantity]');
        this.specSupport = this.specModal.querySelector('[data-spec-support]');
        this.specSupportField = this.specModal.querySelector('[data-spec-support-field]');
        this.specSupportNote = this.specModal.querySelector('[data-spec-support-note]');
        this.specHollow = this.specModal.querySelector('[data-spec-hollow]');
        this.specHollowField = this.specModal.querySelector('[data-spec-hollow-field]');
        this.specHollowSettings = this.specModal.querySelector('[data-spec-hollow-settings]');
        this.specHollowWall = this.specModal.querySelector('[data-spec-hollow-wall]');

        // Panel kiri: pratinjau model beserta keterangan material terpilih.
        this.specThumbnail = this.specModal.querySelector('[data-spec-thumbnail]');
        this.specThumbnailEmpty = this.specModal.querySelector('[data-spec-thumbnail-empty]');
        this.specLearnMore = this.specModal.querySelector('[data-spec-learn-more]');

        // Notice ukuran model, ditampilkan di atas modal ini saat penyimpanan
        // ditolak karena modelnya di luar batas material.
        this.bindSpecNotice();

        /** @type {object|null} model yang sedang disunting */
        this.editing = null;

        /** @type {object} spesifikasi sementara sebelum disimpan */
        this.draft = null;

        this.specTechnology.addEventListener('click', (event) => {
            const option = event.target.closest('[data-spec-technology-option]');

            if (!option) {
                return;
            }

            this.draft.technology = option.dataset.specTechnologyOption;
            // Material, warna, support, dan hollow mengikuti teknologi barunya.
            this.draft.material = materialCatalog(this.config, this.draft.technology)[0]?.name ?? this.draft.material;
            this.renderSpecTechnologies();
            this.renderSpecMaterials();
            this.renderSpecColors();
            this.renderSpecExtras();
            this.renderSpecInfo();
            this.previewSpec();
        });

        this.specSupport?.addEventListener('change', () => {
            this.draft.support = this.specSupport.checked;
            this.previewSpec();
        });

        this.specHollow?.addEventListener('change', () => {
            this.draft.hollow = { ...this.draft.hollow, enabled: this.specHollow.checked };
            this.renderSpecExtras();
            this.previewSpec();
        });

        this.specHollowWall?.addEventListener('input', () => {
            this.draft.hollow = {
                ...this.draft.hollow,
                wallThicknessMm: Number(this.specHollowWall.value) || this.draft.hollow.wallThicknessMm,
            };
            this.previewSpec();
        });

        this.specMaterial.addEventListener('click', (event) => {
            const option = event.target.closest('[data-spec-material-option]');

            if (!option) {
                return;
            }

            this.draft.material = option.dataset.specMaterialOption;
            this.renderSpecMaterials();
            this.renderSpecColors();
            this.renderSpecInfo();
            this.previewSpec();
        });

        this.specColors.addEventListener('click', (event) => {
            const swatch = event.target.closest('[data-spec-color]');

            if (!swatch) {
                return;
            }

            this.draft.color = swatch.dataset.specColor;
            this.renderSpecColors();
            this.previewSpec();
        });

        this.specFinishing.addEventListener('click', (event) => {
            const option = event.target.closest('[data-spec-finishing-option]');

            if (!option) {
                return;
            }

            this.draft.finishing = option.dataset.specFinishingOption;
            this.renderSpecFinishings();
            this.renderSpecFinishingNote();
            this.previewSpec();
        });

        this.specQuantity.addEventListener('input', () => {
            this.draft.quantity = Math.min(10000, Math.max(1, parseInt(this.specQuantity.value, 10) || 1));
            this.previewSpec();
        });

        this.specModal.querySelectorAll('[data-spec-qty-step]').forEach((button) => {
            button.addEventListener('click', () => {
                this.draft.quantity = Math.min(10000, Math.max(1, this.draft.quantity + Number(button.dataset.specQtyStep)));
                this.specQuantity.value = String(this.draft.quantity);
                this.previewSpec();
            });
        });

        this.specModal.querySelectorAll('[data-spec-close]').forEach((el) => {
            el.addEventListener('click', () => this.closeSpecModal());
        });

        this.specModal.addEventListener('mousedown', (event) => {
            if (!this.specModal.querySelector('[data-spec-dialog]').contains(event.target)) {
                this.closeSpecModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            // Notice ditutup lebih dulu; modal pengaturannya tetap terbuka.
            if (this.specNotice?.style.display === 'flex') {
                this.closeSpecNotice();
            } else if (this.specModal.style.display === 'flex') {
                this.closeSpecModal();
            }
        });

        this.specForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!this.editing) {
                this.closeSpecModal();

                return;
            }

            // Ukuran model diperiksa terhadap batas material yang dipilih.
            // Bila di luar batas, perubahannya tidak disimpan dan modal ini
            // tetap terbuka di belakang notice.
            const material = materialInfo(this.config, this.draft.technology, this.draft.material);
            const dimensions = this.editing.summary?.dimensions ?? null;
            const violation = validateModelSize(dimensions, material);

            if (violation) {
                this.showSpecNotice(violation, material, dimensions);

                return;
            }

            this.updateSpecification(this.editing, { ...this.draft });
            this.closeSpecModal();
        });
    }

    openSpecModal(id) {
        const record = this.records.find((item) => item.id === id);

        if (!record || !this.specModal) {
            return;
        }

        this.editing = record;
        this.draft = specificationOf(record);

        this.specFile.textContent = record.name;
        this.specQuantity.value = String(this.draft.quantity);

        this.renderSpecTechnologies();
        this.renderSpecMaterials();
        this.renderSpecColors();
        this.renderSpecFinishings();
        this.renderSpecFinishingNote();
        this.renderSpecExtras();
        this.renderSpecInfo();
        this.previewSpec();

        this.specModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        this.specTechnology.querySelector('[data-spec-technology-option]')?.focus();
    }

    closeSpecModal() {
        if (!this.specModal) {
            return;
        }

        this.closeSpecNotice();
        this.specModal.style.display = 'none';
        document.body.style.overflow = '';
        this.editing = null;
    }

    /** Pilihan teknologi, labelnya mengikuti katalog (mis. "FDM (Plastic)"). */
    renderSpecTechnologies() {
        this.specTechnology.innerHTML = technologyOptions(this.config)
            .map(
                (option) => `
                    <button type="button"
                            class="spec-option"
                            data-spec-technology-option="${escapeAttribute(option.code)}"
                            aria-pressed="${option.code === this.draft.technology}">
                        <span class="spec-option-title">${escapeHtml(option.label)}</span>
                        <span class="spec-option-note">${escapeHtml(option.name)}</span>
                    </button>
                `
            )
            .join('');
    }

    renderSpecMaterials() {
        const materials = materialCatalog(this.config, this.draft.technology);

        if (!materials.some((material) => material.name === this.draft.material)) {
            this.draft.material = materials[0]?.name ?? this.draft.material;
        }

        this.specMaterial.innerHTML = materials
            .map(
                (material) => `
                    <button type="button"
                            class="spec-option"
                            data-spec-material-option="${escapeAttribute(material.name)}"
                            aria-pressed="${material.name === this.draft.material}">
                        <span class="spec-option-title">${escapeHtml(material.name)}</span>
                        <span class="spec-option-note">Maks. ${escapeHtml(sizeText(material.maxSize))}</span>
                    </button>
                `
            )
            .join('');
    }

    /** Surface finish; daftar beserta keterangannya dibaca dari konfigurasi. */
    renderSpecFinishings() {
        this.specFinishing.innerHTML = finishingOptions(this.config)
            .map(
                (option) => `
                    <button type="button"
                            class="spec-option"
                            data-spec-finishing-option="${escapeAttribute(option.key)}"
                            aria-pressed="${option.key === this.draft.finishing}">
                        <span class="spec-option-title">${escapeHtml(option.label)}</span>
                    </button>
                `
            )
            .join('');
    }

    /**
     * Panel kiri: pratinjau model beserta keterangan material yang dipilih.
     *
     * Seluruh keterangannya berasal dari katalog yang sama dengan halaman
     * 3D Printing Guide, jadi tidak ada data yang ditulis dua kali.
     */
    renderSpecInfo() {
        const record = this.editing;

        if (!record) {
            return;
        }

        const summary = record.summary ?? {};
        const material = materialInfo(this.config, this.draft.technology, this.draft.material);

        if (this.specThumbnail) {
            if (record.thumbnail) {
                this.specThumbnail.src = record.thumbnail;
                this.specThumbnail.alt = `Pratinjau model ${record.name}`;
                this.specThumbnail.style.display = 'block';
                hide(this.specThumbnailEmpty);
            } else {
                this.specThumbnail.style.display = 'none';
                show(this.specThumbnailEmpty, 'flex');
            }
        }

        const setText = (selector, value) => {
            const el = this.specModal.querySelector(selector);

            if (el) {
                el.textContent = value;
            }
        };

        setText('[data-spec-model="dimensions"]', sizeText(summary.dimensions));
        setText('[data-spec-model="volume"]', `${formatNumber(summary.volumeCm3 ?? 0, 2)} cm³`);
        setText('[data-spec-model="weight"]', `${formatNumber(summary.weightG ?? 0, 1)} gram`);

        setText('[data-spec-material-name]', material?.name ?? '-');
        setText('[data-spec-material-description]', material?.description ?? '');
        setText('[data-spec-material-max]', sizeText(material?.maxSize));

        const minimum = sizeText(material?.minSize);
        setText(
            '[data-spec-material-min]',
            material?.minSizeSlender ? `${minimum} / ${sizeText(material.minSizeSlender)}` : minimum
        );

        const characteristics = this.specModal.querySelector('[data-spec-material-characteristics]');

        if (characteristics) {
            characteristics.innerHTML = Object.entries(material?.characteristics ?? {})
                .map(
                    ([label, value]) => `
                        <div class="spec-info-row">
                            <dt class="text-ink-400">${escapeHtml(label)}</dt>
                            <dd class="text-right font-semibold text-ink-700">${escapeHtml(String(value))}</dd>
                        </div>
                    `
                )
                .join('');
        }

        const list = (selector, items, prefix) => {
            const el = this.specModal.querySelector(selector);

            if (el) {
                el.innerHTML = (items ?? [])
                    .map((item) => `<li class="text-xs leading-relaxed text-ink-600">${prefix} ${escapeHtml(item)}</li>`)
                    .join('');
            }
        };

        list('[data-spec-material-pros]', material?.pros, '+');
        list('[data-spec-material-cons]', material?.cons, '−');

        // "Learn More" menuju bagian material tersebut di halaman panduan.
        if (this.specLearnMore && this.config.guideUrl) {
            this.specLearnMore.href = material?.slug
                ? `${this.config.guideUrl}#material-${material.slug}`
                : this.config.guideUrl;
        }
    }

    renderSpecColors() {
        const colors = colorOptions(this.config, this.draft.technology, this.draft.material);

        if (!colors.some((color) => color.key === this.draft.color)) {
            this.draft.color = colors[0]?.key ?? this.draft.color;
        }

        this.specColors.innerHTML = colors
            .map(
                (color) => `
                    <button type="button"
                            class="color-swatch"
                            style="--swatch: ${escapeAttribute(color.hex)}"
                            data-spec-color="${escapeAttribute(color.key)}"
                            aria-pressed="${color.key === this.draft.color}"
                            title="${escapeAttribute(color.label)}">
                        <span class="sr-only">${escapeHtml(color.label)}</span>
                    </button>
                `
            )
            .join('');
    }

    /* --------------------------------------------- notice ukuran model */

    bindSpecNotice() {
        this.specNotice = this.root.querySelector('[data-spec-notice]');

        if (!this.specNotice) {
            return;
        }

        this.specNotice.querySelector('[data-spec-notice-close]')?.addEventListener('click', () => {
            this.closeSpecNotice();
        });

        this.specNotice.addEventListener('mousedown', (event) => {
            if (!this.specNotice.querySelector('[data-spec-notice-dialog]').contains(event.target)) {
                this.closeSpecNotice();
            }
        });
    }

    /**
     * Jelaskan mengapa spesifikasi tidak dapat disimpan.
     *
     * Modal Edit Specification sengaja dibiarkan terbuka di belakang supaya
     * pengguna tinggal memilih material atau teknologi lain.
     */
    showSpecNotice(violation, material, dimensions) {
        if (!this.specNotice) {
            return;
        }

        const tooLarge = violation.type === 'max';

        const setText = (selector, value) => {
            const el = this.specNotice.querySelector(selector);

            if (el) {
                el.textContent = value;
            }
        };

        setText(
            '[data-spec-notice-message]',
            `Material "${material?.name ?? '-'}" tidak dapat digunakan untuk file "${this.editing?.name ?? ''}".`
        );
        setText('[data-spec-notice-model]', sizeText(dimensions));
        setText('[data-spec-notice-limit-label]', tooLarge ? 'Ukuran maksimum material' : 'Ukuran minimum material');
        setText('[data-spec-notice-limit]', sizeText(violation.limit));
        setText(
            '[data-spec-notice-hint]',
            tooLarge
                ? 'Silakan pilih teknologi atau material lain yang mendukung ukuran model tersebut.'
                : 'Silakan pilih material lain atau ubah ukuran model agar sesuai dengan spesifikasi material.'
        );

        this.specNotice.style.display = 'flex';
        this.specNotice.querySelector('[data-spec-notice-close]')?.focus();
    }

    closeSpecNotice() {
        if (this.specNotice) {
            this.specNotice.style.display = 'none';
        }
    }

    renderSpecFinishingNote() {
        if (this.specFinishingNote) {
            this.specFinishingNote.textContent = this.config.finishing?.options?.[this.draft.finishing]?.description ?? '';
        }
    }

    /**
     * Support structure & Hollow Model.
     *
     * Keduanya bergantung teknologi: MJF tidak memerlukan support karena part
     * tertopang serbuk, dan Hollow Model hanya tersedia untuk resin (SLA).
     */
    renderSpecExtras() {
        const technology = this.draft.technology;

        if (this.specSupport) {
            const available = allowsSupport(this.config, technology);

            if (!available) {
                this.draft.support = false;
            }

            this.specSupport.disabled = !available;
            this.specSupport.checked = Boolean(this.draft.support);
            this.specSupportField?.classList.toggle('opacity-50', !available);

            if (this.specSupportNote) {
                const note = supportNoteFor(this.config, technology);
                this.specSupportNote.textContent = note ?? '';
                this.specSupportNote.style.display = note ? 'block' : 'none';
            }
        }

        if (this.specHollow) {
            const available = allowsHollow(this.config, technology);

            if (!available) {
                this.draft.hollow = { ...this.draft.hollow, enabled: false };
            }

            this.specHollowField.style.display = available ? 'block' : 'none';
            this.specHollow.checked = Boolean(this.draft.hollow.enabled);

            if (this.specHollowSettings) {
                this.specHollowSettings.style.display = this.draft.hollow.enabled ? 'block' : 'none';
            }

            if (this.specHollowWall) {
                this.specHollowWall.value = String(this.draft.hollow.wallThicknessMm);
            }
        }
    }

    /** Angka pratinjau di dalam modal, dihitung sebelum perubahan disimpan. */
    previewSpec() {
        if (!this.editing) {
            return;
        }

        const preview = applySpecification(this.editing, this.config, this.draft);
        const summary = preview?.summary;

        const set = (key, value) => {
            const el = this.specModal.querySelector(`[data-spec-preview="${key}"]`);

            if (el) {
                el.textContent = value;
            }
        };

        if (!summary) {
            ['weight', 'time', 'cost'].forEach((key) => set(key, '-'));

            return;
        }

        set('weight', `${formatNumber(summary.weightG, 1)} gram`);
        set('time', formatLeadTime(summary.minutes));
        set('cost', this.showsPrice ? formatCurrency(summary.cost) : 'Login dulu');
    }

    renderList() {
        if (this.countEl) {
            this.countEl.textContent = `${this.records.length}/${this.maxModels}`;
        }

        this.records.length === 0 ? show(this.emptyState, 'block') : hide(this.emptyState);

        if (this.clearAllButton) {
            this.clearAllButton.disabled = this.records.length === 0;
        }

        if (!this.listHost) {
            return;
        }

        this.listHost.innerHTML = this.records.map((record) => this.cardMarkup(record)).join('');
    }

    /** Satu kartu ringkas: thumbnail asli model beserta informasi pentingnya. */
    cardMarkup(record) {
        const summary = record.summary ?? {};
        const url = `${this.viewerUrl}?model=${encodeURIComponent(record.id)}`;
        const dimensions = summary.dimensions
            ? `${formatNumber(summary.dimensions.x, 1)} × ${formatNumber(summary.dimensions.y, 1)} × ${formatNumber(summary.dimensions.z, 1)} mm`
            : '-';

        // Spesifikasi dibaca lewat specificationOf() supaya model yang tersimpan
        // sebelum fitur ini ada tetap memiliki jumlah yang sah. Rinciannya
        // sendiri tidak lagi ditampilkan di kartu — cukup lewat Edit Specification.
        const spec = specificationOf(record);

        // Berat sengaja tidak ikut: angkanya baru berarti setelah material
        // dipilih, jadi ditampilkan pada Edit Specification saja.
        const rows = [
            ['Dimensi', dimensions],
            ['Volume', `${formatNumber(summary.volumeCm3 ?? 0, 2)} cm³`],
        ];

        // Susunan mendatar: thumbnail kecil di kiri, seluruh keterangan di
        // kanan. Satu kartu jadi kira-kira sepertiga tinggi versi sebelumnya,
        // sehingga puluhan model tetap terbaca tanpa menggulir jauh.
        return `
            <article class="card overflow-hidden">
                <div class="flex gap-3 p-3">
                    <a href="${escapeAttribute(url)}"
                       target="_blank"
                       rel="noopener"
                       class="group relative block h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-ink-100"
                       title="Buka viewer 3D ${escapeAttribute(record.name)}">
                        ${record.thumbnail
                            ? `<img src="${escapeAttribute(record.thumbnail)}" alt="Pratinjau model ${escapeAttribute(record.name)}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.06]" loading="lazy">`
                            : '<span class="flex h-full w-full items-center justify-center px-1 text-center text-[0.6rem] leading-tight text-ink-400">Pratinjau tidak tersedia</span>'}

                        <span class="absolute inset-0 flex items-center justify-center bg-ink-950/65 text-[0.6rem] font-bold text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                            Lihat 3D
                        </span>
                    </a>

                    <div class="min-w-0 flex-1">
                        <p class="flex items-baseline gap-1.5">
                            <span class="font-mono text-[0.6rem] text-ink-400">#${record.position}</span>
                            <span class="truncate font-display text-xs font-bold text-ink-900" title="${escapeAttribute(record.name)}">${escapeHtml(record.name)}</span>
                        </p>
                        <p class="mt-0.5 text-[0.6rem] uppercase tracking-[0.12em] text-ink-400">
                            ${escapeHtml(record.format)} · ${formatBytes(record.size)}
                        </p>

                        <dl class="mt-1.5 space-y-0.5 text-[0.7rem]">
                            ${rows
                                .map(
                                    ([label, value]) => `
                                        <div class="flex items-baseline justify-between gap-2">
                                            <dt class="shrink-0 text-ink-400">${label}</dt>
                                            <dd class="truncate text-right font-semibold text-ink-800">${value}</dd>
                                        </div>
                                    `
                                )
                                .join('')}
                        </dl>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 border-t border-ink-100 px-3 py-2">
                    <label class="text-[0.7rem] font-semibold text-ink-600" for="qty-${escapeAttribute(record.id)}">Qty</label>

                    <div class="flex items-center gap-1">
                        <button type="button"
                                class="qty-step h-7 w-7 text-base"
                                data-model="${escapeAttribute(record.id)}"
                                data-qty-step="-1"
                                ${spec.quantity <= 1 ? 'disabled' : ''}
                                aria-label="Kurangi jumlah ${escapeAttribute(record.name)}">−</button>

                        <input type="number"
                               id="qty-${escapeAttribute(record.id)}"
                               class="qty-input h-7 w-12 text-xs"
                               value="${spec.quantity}"
                               min="1"
                               max="10000"
                               step="1"
                               inputmode="numeric"
                               data-qty-input
                               data-model="${escapeAttribute(record.id)}">

                        <button type="button"
                                class="qty-step h-7 w-7 text-base"
                                data-model="${escapeAttribute(record.id)}"
                                data-qty-step="1"
                                aria-label="Tambah jumlah ${escapeAttribute(record.name)}">+</button>
                    </div>

                    <span class="ml-auto text-right">
                        ${this.showsPrice
                            ? `<span class="block font-display text-sm font-bold text-brand-700">${formatCurrency(summary.cost ?? 0)}</span>`
                            : '<span class="block text-[0.65rem] font-semibold text-ink-400">Login untuk harga</span>'}
                        <span class="block text-[0.6rem] text-ink-400">${formatLeadTime(summary.minutes ?? 0)}</span>
                    </span>
                </div>

                ${summary.fits === false
                    ? '<p class="border-t border-brand-100 bg-brand-50 px-3 py-1.5 text-[0.65rem] font-semibold text-brand-700">⚠ Melewati area cetak mesin yang dipilih</p>'
                    : ''}

                <div class="flex items-center gap-2 border-t border-ink-100 px-3 py-2">
                    <button type="button" class="btn-outline flex-1 px-3 py-1.5 text-[0.7rem]" data-edit-spec="${escapeAttribute(record.id)}">
                        Edit Specification
                    </button>

                    <button type="button"
                            class="shrink-0 rounded-lg p-1.5 text-ink-300 transition-colors hover:bg-brand-50 hover:text-brand-600"
                            title="Hapus model ini"
                            aria-label="Hapus ${escapeAttribute(record.name)}"
                            data-remove-model="${escapeAttribute(record.id)}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                            <path d="M4 7h16" /><path d="M10 11v6" /><path d="M14 11v6" />
                            <path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12" />
                            <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
                        </svg>
                    </button>
                </div>
            </article>
        `;
    }

    /* ---------------------------------------------------------- ringkasan */

    readyRecords() {
        return this.records.filter((record) => record.payload && record.summary);
    }

    /**
     * Total seluruh mesin.
     *
     * Karena tiap model dicetak pada mesinnya sendiri, waktu tidak dijumlahkan
     * sebagai antrean satu mesin — yang ditampilkan adalah total jam mesin, dan
     * waktu selesai mengikuti mesin yang paling lama.
     */
    totals() {
        return this.readyRecords().reduce(
            (carry, record) => {
                const estimate = record.payload.estimate ?? {};

                return {
                    weightG: carry.weightG + (record.summary.weightG ?? 0),
                    minutes: carry.minutes + (estimate.totalMinutes ?? 0),
                    longestMinutes: Math.max(carry.longestMinutes, estimate.totalMinutes ?? 0),
                    cost: carry.cost + (estimate.totalCost ?? 0),
                };
            },
            { weightG: 0, minutes: 0, longestMinutes: 0, cost: 0 }
        );
    }

    renderSummary() {
        if (!this.summaryRows) {
            return;
        }

        const ready = this.readyRecords();

        if (ready.length === 0) {
            this.summaryRows.innerHTML = '';
            hide(this.summaryPanel);
            show(this.summaryPlaceholder, 'block');
            this.updateQuotationButton();

            return;
        }

        this.summaryRows.innerHTML = ready
            .map((record, index) => {
                const summary = record.summary;
                const estimate = record.payload.estimate ?? {};
                const scale = Math.round(record.payload.scale_percent ?? 100);

                // Mesin, material, dan berat tidak lagi ikut di ringkasan:
                // rinciannya sudah ada pada kartu model dan Edit Specification.
                return `
                    <tr>
                        <td class="max-w-[160px] py-3 pr-3">
                            <p class="truncate font-semibold text-ink-900" title="${escapeAttribute(record.name)}">
                                <span class="font-mono text-[0.65rem] text-ink-400">#${index + 1}</span>
                                ${escapeHtml(record.name)}
                            </p>
                            <p class="text-[0.65rem] uppercase tracking-[0.1em] text-ink-400">
                                ${escapeHtml(record.format)}${scale !== 100 ? ` &middot; skala ${scale}%` : ''}
                            </p>
                        </td>
                        <td class="px-2 py-3 text-right text-ink-700">${formatCount(summary.quantity ?? 1)}</td>
                        <td class="${this.showsPrice ? 'px-2' : 'pl-2'} py-3 text-right text-ink-700">${formatLeadTime(estimate.totalMinutes ?? 0)}</td>
                        ${this.showsPrice
                            ? `<td class="py-3 pl-2 text-right font-display font-bold text-brand-700">${formatCurrency(estimate.totalCost ?? 0)}</td>`
                            : ''}
                    </tr>
                `;
            })
            .join('');

        const totals = this.totals();

        this.setTotal('models', `${formatCount(ready.length)} model`);

        // Tiap model dicetak pada mesinnya sendiri sehingga pengerjaannya
        // berjalan bersamaan; lead time penawaran karena itu mengikuti mesin
        // yang paling lama, bukan penjumlahan seluruh jam mesin.
        this.setTotal('time', formatLeadTime(totals.longestMinutes));
        this.setTotal('cost', this.showsPrice ? formatCurrency(totals.cost) : '-');

        hide(this.summaryPlaceholder);
        show(this.summaryPanel, 'block');
        this.updateQuotationButton();
    }

    setTotal(name, value) {
        this.setText(`[data-total="${name}"]`, value);
    }

    setText(selector, value) {
        const el = this.root.querySelector(selector);

        if (el) {
            el.textContent = value;
        }
    }

    updateQuotationButton() {
        if (this.quotationButton) {
            this.quotationButton.disabled = this.readyRecords().length === 0;
        }
    }

    /* --------------------------------------------------------- pengiriman */

    /**
     * Seluruh model dikirim bersama dalam satu permintaan penawaran.
     *
     * Berkasnya diambil kembali dari IndexedDB dan dibungkus ulang menjadi File
     * supaya dapat ikut di dalam FormData, persis seperti saat baru diunggah.
     */
    submissionPayload() {
        const ready = this.readyRecords();

        if (ready.length === 0) {
            return null;
        }

        return {
            items: ready.map((record) => ({
                ...record.payload,
                file: new File([record.blob], record.name, {
                    type: record.blob?.type || 'application/octet-stream',
                }),
            })),
            totals: this.totals(),
        };
    }

    /* ---------------------------------------------------------------- ui */

    showLoading(label) {
        if (this.loadingLabel) {
            this.loadingLabel.textContent = label;
        }

        show(this.loadingEl, 'flex');
    }

    hideLoading() {
        hide(this.loadingEl);
    }

    showFileProblems(problems) {
        const title = this.errorEl.querySelector('[data-viewer-error-title]');

        if (title) {
            title.textContent = problems.length > 1
                ? `${problems.length} file tidak dapat diproses`
                : 'Satu file tidak dapat diproses';
        }

        this.errorMessage.textContent = this.records.length > 0
            ? 'File berikut dilewati. Model lain yang valid tetap masuk ke daftar.'
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
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/'/g, '&#39;');
}
