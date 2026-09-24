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
    applySpecification,
    colorOptions,
    finishingOptions,
    materialCatalog,
    materialInfo,
    materialLabel,
    specificationOf,
    technologyOptions,
    validateModelSize,
} from './model-spec';
import {
    configureLeadTime,
    EXPRESS_SPEED,
    expressAvailable,
    expressUnavailableReason,
    formatCount,
    formatCurrency,
    formatLeadTime,
    isManualEstimate,
    formatNumber,
    PENDING_PRICE_NOTE,
    STANDARD_SPEED,
    sumPrices,
} from './print-estimator';

const MAX_FILE_SIZE = 60 * 1024 * 1024; // 60 MB
const DEFAULT_MAX_MODELS = 25;

const show = (el, display = 'flex') => el && (el.style.display = display);
const hide = (el) => el && (el.style.display = 'none');

/**
 * Ukuran yang layak ditulis: ketiga sisinya ada dan lebih besar dari nol.
 *
 * Server sudah menyaringnya lewat App\Support\MaterialCatalog::size(), tetapi
 * penjaga yang sama diulang di sini supaya tidak ada jalan mana pun — model
 * lama di localStorage, payload yang di-cache — yang berakhir sebagai
 * "0 × 0 × 0 mm" di layar.
 */
const isSize = (size) =>
    !!size && ['x', 'y', 'z'].every((axis) => Number.isFinite(Number(size[axis])) && Number(size[axis]) > 0);

/** Ukuran ditulis "250 × 250 × 300 mm"; pecahannya dibulatkan satu desimal. */
const sizeFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
const sizeText = (size) =>
    isSize(size)
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
 * Hasilnya disimpan di IndexedDB dan ditampilkan sebagai kartu ringkas. Tombol
 * "Lihat 3D" berpindah (di tab yang sama) ke halaman pratinjau
 * /3d-models/{id}/viewer, yang membaca berkasnya dari IndexedDB yang sama —
 * sehingga halaman ini tetap ringan meski memuat puluhan model.
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
        this.productionOptions = root.querySelector('[data-production-options]');
        this.productionNote = root.querySelector('[data-production-note]');
        this.quotationButton = root.querySelector('[data-open-quotation]');

        this.config = this.readConfig();
        this.maxModels = Number(this.config.limits?.maxModels) || DEFAULT_MAX_MODELS;
        // Pola alamat viewer, mis. "/3d-models/MODELID/viewer"; MODELID diganti id model.
        this.viewerUrl = this.config.viewerUrl ?? '/3d-models/MODELID/viewer';

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

            // Aturan lead time dipasang sekali di sini agar seluruh modul yang
            // menampilkan estimasi memakai rentang hari yang sama.
            configureLeadTime(config.leadTime);

            // Kecepatan pengerjaan pesanan. Dititipkan pada config karena
            // dipakai bersama seluruh model — lihat setProductionSpeed().
            config.productionSpeed = STANDARD_SPEED;

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
            const duplicate = event.target.closest('[data-duplicate-model]');

            if (duplicate) {
                this.duplicateModel(duplicate.dataset.duplicateModel);

                return;
            }

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

    /**
     * Gandakan satu object beserta seluruh spesifikasinya.
     *
     * Berkas 3D-nya TIDAK dibaca ulang: hasil analisis, thumbnail, dan Blob-nya
     * disalin apa adanya, jadi menggandakan model 9 MB pun seketika dan tidak
     * menjalankan parsing yang berat itu untuk kedua kalinya.
     *
     * Salinannya berdiri sendiri — id barunya sendiri, dan isinya disalin dalam
     * sekali `structuredClone` supaya mengubah spesifikasi salah satunya tidak
     * ikut menggeser yang lain. Namanya sengaja dibiarkan sama: berkasnya
     * memang berkas yang sama, hanya baris penawarannya yang bertambah.
     *
     * Posisi setengah menempatkannya tepat di bawah aslinya; `reorder()`
     * merapatkan kembali nomornya menjadi bilangan bulat berurutan.
     */
    async duplicateModel(id) {
        const record = this.records.find((item) => item.id === id);

        if (!record) {
            return;
        }

        if (this.records.length >= this.maxModels) {
            this.showFileProblems([
                {
                    name: record.name,
                    message: `Batas ${this.maxModels} model per permintaan sudah tercapai. Hapus salah satu model lebih dulu.`,
                },
            ]);

            return;
        }

        const copy = {
            ...structuredClone(record),
            id: createModelId(),
            position: record.position + 0.5,
            createdAt: Date.now(),
        };

        try {
            await modelStore.put(copy);
            this.records = await modelStore.reorder();
        } catch (error) {
            console.error(error);

            return;
        }

        this.renderList();
        this.renderSummary();
        this.channel.post({ type: 'refreshed' });
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
            // Material dan warna mengikuti teknologi barunya.
            this.draft.material = materialCatalog(this.config, this.draft.technology)[0]?.name ?? this.draft.material;
            this.renderSpecTechnologies();
            this.renderSpecMaterials();
            this.renderSpecColors();
            this.renderSpecInfo();
            this.previewSpec();
        });

        // Pilihan Production digambar ulang setiap ringkasan diperbarui, jadi
        // penangannya dipasang di tingkat wadahnya.
        this.productionOptions?.addEventListener('change', (event) => {
            const option = event.target.closest('[data-production-option]');

            if (option) {
                this.setProductionSpeed(option.value);
            }
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

            if (!option || option.disabled) {
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
                        ${
                            // Keterangan di bawah label adalah nama panjang
                            // teknologinya. Pada teknologi yang memang dipanggil
                            // dengan namanya - SLA Industries - keduanya sama,
                            // dan mengulangnya hanya jadi bising.
                            option.label.startsWith(option.name)
                                ? ''
                                : `<span class="spec-option-note">${escapeHtml(option.name)}</span>`
                        }
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
                        <span class="spec-option-title">${escapeHtml(materialLabel(material))}</span>
                        ${
                            // Batas ukuran hanya dimiliki material yang sudah
                            // terhubung ke sebuah mesin. Tanpa mesin tidak ada
                            // angka yang benar untuk ditulis, jadi keterangannya
                            // ditiadakan — bukan diisi "Maks. -".
                            isSize(material.maxSize)
                                ? `<span class="spec-option-note">Maks. ${escapeHtml(sizeText(material.maxSize))}</span>`
                                : ''
                        }
                    </button>
                `
            )
            .join('');
    }

    /**
     * Surface finish; daftarnya milik material yang sedang dipilih.
     *
     * Finishing yang tidak lagi ditawarkan material itu dibatalkan otomatis.
     * Custom Finishing tidak berharga otomatis — keterangannya ikut ditulis
     * supaya pelanggan tahu harganya menunggu kuotasi.
     */
    renderSpecFinishings() {
        const options = finishingOptions(this.config, this.draft.technology, this.draft.material);
        const keys = options.map((option) => option.key);

        if (!keys.includes(this.draft.finishing)) {
            this.draft.finishing = keys.includes(this.config.finishing?.default)
                ? this.config.finishing.default
                : (keys[0] ?? 'none');
        }

        this.specFinishing.innerHTML = options
            .map((option) => {
                return `
                    <button type="button"
                            class="spec-option"
                            data-spec-finishing-option="${escapeAttribute(option.key)}"
                            aria-pressed="${option.key === this.draft.finishing}"
                            ${option.note ? `title="${escapeAttribute(option.note)}"` : ''}>
                        <span class="spec-option-title">${escapeHtml(option.label)}</span>
                    </button>
                `;
            })
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
        // Berat tidak ikut ditulis: pelanggan tidak melihat angka berat di mana pun.

        setText('[data-spec-material-name]', material ? materialLabel(material) : '-');
        setText('[data-spec-material-description]', material?.description ?? '');
        // Batas ukuran: barisnya hanya digambar bila angkanya ada. Ukuran
        // maksimum berasal dari mesin material, jadi material tanpa mesin
        // memang tidak punya — dan barisnya ditiadakan, bukan diisi "-".
        const setLimit = (rowSelector, valueSelector, value) => {
            const row = this.specModal.querySelector(rowSelector);

            // Dikosongkan saat tidak ada angkanya, supaya batas material
            // sebelumnya tidak tertinggal di dalam baris yang disembunyikan.
            setText(valueSelector, value ?? '');

            if (row) {
                row.style.display = value ? 'flex' : 'none';
            }

            return !!value;
        };

        const hasMax = setLimit(
            '[data-spec-material-max-row]',
            '[data-spec-material-max]',
            isSize(material?.maxSize) ? sizeText(material.maxSize) : null
        );

        const hasMin = setLimit(
            '[data-spec-material-min-row]',
            '[data-spec-material-min]',
            isSize(material?.minSize)
                ? isSize(material?.minSizeSlender)
                    ? `${sizeText(material.minSize)} / ${sizeText(material.minSizeSlender)}`
                    : sizeText(material.minSize)
                : null
        );

        // Tanpa satu pun batas, pembatas dan jarak di atasnya ikut hilang agar
        // tidak menyisakan garis kosong menggantung.
        const limits = this.specModal.querySelector('[data-spec-material-limits]');

        if (limits) {
            limits.style.display = hasMax || hasMin ? 'block' : 'none';
        }

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

        const list = (attribute, items, prefix) => {
            const el = this.specModal.querySelector(`[${attribute}]`);
            const block = this.specModal.querySelector(`[${attribute}-block]`);
            const rows = (items ?? []).filter((item) => String(item ?? '').trim() !== '');

            if (el) {
                el.innerHTML = rows
                    .map((item) => `<li class="text-xs leading-relaxed text-ink-600">${prefix} ${escapeHtml(item)}</li>`)
                    .join('');
            }

            // Material yang belum diberi keterangan tidak menampilkan judulnya
            // sama sekali, bukan judul di atas daftar kosong.
            if (block) {
                block.style.display = rows.length ? 'block' : 'none';
            }
        };

        list('data-spec-material-pros', material?.pros, '+');
        list('data-spec-material-cons', material?.cons, '−');

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

        // Warna menentukan apakah Painting boleh dipilih, jadi finishing ikut disegarkan.
        this.renderSpecFinishings();
        this.renderSpecFinishingNote();
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

        const pendingNote = this.specModal.querySelector('[data-spec-pending-note]');

        const showPendingNote = (pending) => {
            if (pendingNote) {
                pendingNote.textContent = pending ? PENDING_PRICE_NOTE : '';
                pendingNote.style.display = pending ? 'block' : 'none';
            }
        };

        if (!summary) {
            ['time', 'cost'].forEach((key) => set(key, '-'));
            showPendingNote(false);

            return;
        }

        set('time', formatLeadTime(this.config.productionSpeed, isManualEstimate(preview.payload.estimate)));
        set('cost', this.showsPrice ? formatCurrency(summary.cost) : 'Login dulu');

        // Harga yang belum dapat ditentukan sendiri oleh sistem — material
        // Kalkulator Manual atau Custom Finishing — dijelaskan, bukan sekadar
        // ditulis sebagai judul tanpa keterangan.
        showPendingNote(this.showsPrice && !Number.isFinite(Number(summary.cost)));
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
        // Berpindah ke halaman viewer di tab yang sama — bukan tab baru.
        const url = this.viewerUrl.replace('MODELID', encodeURIComponent(record.id));
        const dimensions = summary.dimensions
            ? `${formatNumber(summary.dimensions.x, 1)} × ${formatNumber(summary.dimensions.y, 1)} × ${formatNumber(summary.dimensions.z, 1)} mm`
            : '-';

        // Spesifikasi dibaca lewat specificationOf() supaya model yang tersimpan
        // sebelum fitur ini ada tetap memiliki jumlah yang sah. Rinciannya
        // sendiri tidak lagi ditampilkan di kartu — cukup lewat Edit Specification.
        const spec = specificationOf(record);

        // Berat sengaja tidak ikut: angka berat tidak ditampilkan kepada
        // pelanggan di halaman mana pun.
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
                            ? `<span class="block font-display text-sm font-bold text-brand-700">${formatCurrency(summary.cost)}</span>`
                            : '<span class="block text-[0.65rem] font-semibold text-ink-400">Login untuk harga</span>'}
                        <span class="block text-[0.6rem] text-ink-400">${formatLeadTime(this.config.productionSpeed, isManualEstimate(record.payload.estimate))}</span>
                    </span>
                </div>

                ${summary.fits === false
                    ? '<p class="border-t border-brand-100 bg-brand-50 px-3 py-1.5 text-[0.65rem] font-semibold text-brand-700">⚠ Melewati area cetak mesin yang dipilih</p>'
                    : ''}

                <div class="flex items-center gap-2 border-t border-ink-100 px-3 py-2">
                    <a href="${escapeAttribute(url)}"
                       class="btn-outline shrink-0 px-3 py-1.5 text-[0.7rem]"
                       title="Lihat 3D ${escapeAttribute(record.name)}"
                       data-view-model="${escapeAttribute(record.id)}">
                        Lihat 3D
                    </a>

                    <button type="button" class="btn-outline flex-1 px-3 py-1.5 text-[0.7rem]" data-edit-spec="${escapeAttribute(record.id)}">
                        Edit Specification
                    </button>

                    <button type="button"
                            class="shrink-0 rounded-lg p-1.5 text-ink-300 transition-colors hover:bg-brand-50 hover:text-brand-600"
                            title="Duplikat model ini"
                            aria-label="Duplikat ${escapeAttribute(record.name)}"
                            data-duplicate-model="${escapeAttribute(record.id)}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                            <rect x="9" y="9" width="12" height="12" rx="2" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                        </svg>
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
     * `minutes` adalah penjumlahan waktu proses seluruh object — inilah yang
     * menentukan lead time penawaran. Object terlama tidak lagi dicatat
     * terpisah: lead time ditentukan keseluruhan pesanan, bukan satu object.
     */
    totals() {
        const records = this.readyRecords();

        const summary = records.reduce(
            (carry, record) => {
                const estimate = record.payload.estimate ?? {};

                return {
                    weightG: carry.weightG + (record.summary.weightG ?? 0),
                    minutes: carry.minutes + (estimate.totalMinutes ?? 0),
                    // Satu model berharga Rumus Harga Manual sudah menentukan
                    // lead time seluruh penawaran.
                    manualPricing: carry.manualPricing || isManualEstimate(estimate),
                };
            },
            { weightG: 0, minutes: 0, manualPricing: false }
        );

        // Harga dijumlahkan terpisah: satu model yang harganya belum ditetapkan
        // membuat totalnya belum ada sama sekali, bukan sekadar lebih kecil.
        return {
            ...summary,
            cost: sumPrices(records.map((record) => record.payload.estimate?.totalCost)),
        };
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
                        <td class="${this.showsPrice ? 'px-2' : 'pl-2'} py-3 text-right text-ink-700">${formatLeadTime(this.config.productionSpeed, isManualEstimate(estimate))}</td>
                        ${this.showsPrice
                            ? `<td class="py-3 pl-2 text-right font-display font-bold text-brand-700">${formatCurrency(estimate.totalCost)}</td>`
                            : ''}
                    </tr>
                `;
            })
            .join('');

        // Express yang syaratnya sudah gugur diturunkan lebih dulu, supaya
        // angka di bawah adalah angka yang benar-benar berlaku.
        this.enforceProductionAvailability();

        const totals = this.totals();

        this.setTotal('models', `${formatCount(ready.length)} model`);

        // Lead time mengikuti KECEPATAN yang dipilih pelanggan, bukan lagi
        // disimpulkan dari jam mesin — lihat App\Support\LeadTime.
        this.setTotal('time', formatLeadTime(this.config.productionSpeed, totals.manualPricing));
        this.setTotal('cost', this.showsPrice ? formatCurrency(totals.cost) : '-');

        this.renderProductionChoice();

        hide(this.summaryPlaceholder);
        show(this.summaryPanel, 'block');
        this.updateQuotationButton();
    }

    /**
     * Kecepatan pengerjaan pesanan: Standard atau Express.
     *
     * Pilihannya milik pesanan, bukan satu model, jadi disimpan pada config yang
     * dipakai bersama lalu SELURUH model dihitung ulang — dengan begitu tambahan
     * Express masuk ke setiap model, sama seperti yang dilakukan
     * App\Services\SellingPriceEstimator saat penawarannya disimpan.
     */
    async setProductionSpeed(speed) {
        if (speed === this.config.productionSpeed) {
            return;
        }

        this.config.productionSpeed = speed;
        this.reestimateAll();

        this.renderList();
        this.renderSummary();

        try {
            await Promise.all(this.records.map((record) => modelStore.put(record)));
        } catch (error) {
            console.error(error);
        }
    }

    /** Hitung ulang seluruh model dengan config yang berlaku sekarang. */
    reestimateAll() {
        this.records = this.records.map(
            (record) => applySpecification(record, this.config, specificationOf(record)) ?? record
        );
    }

    /**
     * Express yang tidak lagi memenuhi syarat diturunkan ke Standard.
     *
     * Pelanggan dapat memilih Express lalu menambah part atau memperbesar
     * modelnya; begitu salah satu syaratnya gugur, pesanannya tidak boleh tetap
     * berharga Express. Dipanggil sebelum ringkasan digambar, jadi angka yang
     * tampil selalu angka yang benar-benar berlaku.
     */
    enforceProductionAvailability() {
        if (this.config.productionSpeed !== EXPRESS_SPEED) {
            return;
        }

        const totals = this.totals();

        if (!expressAvailable(this.readyRecords().length, totals.minutes, totals.manualPricing)) {
            this.config.productionSpeed = STANDARD_SPEED;
            this.reestimateAll();
        }
    }

    /** Pilihan Production beserta keterangannya bila Express tidak tersedia. */
    renderProductionChoice() {
        if (!this.productionOptions) {
            return;
        }

        const totals = this.totals();
        const available = expressAvailable(this.readyRecords().length, totals.minutes, totals.manualPricing);
        const selected = this.config.productionSpeed ?? STANDARD_SPEED;

        const choices = available ? [STANDARD_SPEED, EXPRESS_SPEED] : [STANDARD_SPEED];

        this.productionOptions.innerHTML = choices
            .map((key) => {
                const isExpress = key === EXPRESS_SPEED;

                return `
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition-colors ${
                        key === selected ? 'border-brand-500 bg-brand-50' : 'border-ink-200 hover:bg-ink-50'
                    }">
                        <input type="radio" name="production_speed" value="${key}" class="h-4 w-4 accent-brand-600"
                               ${key === selected ? 'checked' : ''} data-production-option>
                        <span class="text-sm font-bold text-ink-900">${formatLeadTime(key, totals.manualPricing)}${isExpress ? ' ⚡' : ''}</span>
                    </label>
                `;
            })
            .join('');

        if (this.productionNote) {
            const reason = available
                ? ''
                : expressUnavailableReason(this.readyRecords().length, totals.minutes, totals.manualPricing);

            this.productionNote.textContent = reason;
            this.productionNote.style.display = reason ? 'block' : 'none';
        }
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

            // Kecepatan pengerjaan berlaku untuk seluruh pesanan; server
            // memeriksa ulang syaratnya sebelum harga ditetapkan.
            productionSpeed: this.config.productionSpeed ?? STANDARD_SPEED,
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
