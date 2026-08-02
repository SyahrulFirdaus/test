import SharedRenderer from './shared-renderer';
import PrinterCard from './printer-card';
import { createModelChannel, createModelId, modelStore } from './model-store';
import { toRecord } from './model-record';
import {
    allowsHollow,
    allowsSupport,
    applySpecification,
    colorOptions,
    finishingOptions,
    materialOptions,
    specificationOf,
    supportNoteFor,
    technologyOptions,
} from './model-spec';
import {
    formatCount,
    formatCurrency,
    formatDuration,
    formatNumber,
} from './print-estimator';

const SUPPORTED_EXTENSIONS = ['stl', 'obj'];
const MAX_FILE_SIZE = 60 * 1024 * 1024; // 60 MB
const DEFAULT_MAX_MODELS = 25;

const show = (el, display = 'flex') => el && (el.style.display = display);
const hide = (el) => el && (el.style.display = 'none');

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
            return JSON.parse(el?.textContent ?? '{}');
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

                this.showLoading(`Menganalisis ${file.name}${progress}…`);
                // Beri kesempatan browser menggambar indikator loading sebelum
                // parsing yang berat mengunci thread utama.
                await new Promise((resolve) => setTimeout(resolve, 24));

                const record = this.buildRecord(file, buffer, this.records.length + 1);

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
        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

        if (!SUPPORTED_EXTENSIONS.includes(extension)) {
            return `Format “.${extension || 'tanpa ekstensi'}” belum didukung. Viewer ini hanya dapat membaca file .stl dan .obj.`;
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

    /* -------------------------------------------------------------- proses */

    /**
     * Bangun satu card di luar layar, ambil hasilnya, lalu buang cardnya.
     *
     * Card yang sama persis dengan yang dipakai halaman viewer, jadi angka pada
     * daftar ini dan angka pada viewer tidak pernah berbeda.
     */
    buildRecord(file, buffer, position) {
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
                buffer,
                config: this.config,
                renderer: this.renderer,
            });

            return {
                ...toRecord(card, { id, position, name: file.name, size: file.size }),
                blob: new Blob([buffer], { type: file.type || 'application/octet-stream' }),
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

        /** @type {object|null} model yang sedang disunting */
        this.editing = null;

        /** @type {object} spesifikasi sementara sebelum disimpan */
        this.draft = null;

        // Pilihan teknologi dan finishing tetap sama untuk semua model, jadi
        // cukup diisi sekali.
        this.specTechnology.innerHTML = technologyOptions(this.config)
            .map((option) => `<option value="${escapeAttribute(option.code)}">${escapeHtml(option.label)}</option>`)
            .join('');

        this.specFinishing.innerHTML = finishingOptions(this.config)
            .map((option) => `<option value="${escapeAttribute(option.key)}">${escapeHtml(option.label)}</option>`)
            .join('');

        this.specTechnology.addEventListener('change', () => {
            this.draft.technology = this.specTechnology.value;
            // Material, warna, support, dan hollow mengikuti teknologi barunya.
            this.draft.material = materialOptions(this.config, this.draft.technology)[0] ?? this.draft.material;
            this.renderSpecMaterials();
            this.renderSpecColors();
            this.renderSpecExtras();
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

        this.specMaterial.addEventListener('change', () => {
            this.draft.material = this.specMaterial.value;
            this.renderSpecColors();
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

        this.specFinishing.addEventListener('change', () => {
            this.draft.finishing = this.specFinishing.value;
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
            if (event.key === 'Escape' && this.specModal.style.display === 'flex') {
                this.closeSpecModal();
            }
        });

        this.specForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (this.editing) {
                this.updateSpecification(this.editing, { ...this.draft });
            }

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
        this.specTechnology.value = this.draft.technology;
        this.specFinishing.value = this.draft.finishing;
        this.specQuantity.value = String(this.draft.quantity);

        this.renderSpecMaterials();
        this.renderSpecColors();
        this.renderSpecFinishingNote();
        this.renderSpecExtras();
        this.previewSpec();

        this.specModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        this.specTechnology.focus();
    }

    closeSpecModal() {
        if (!this.specModal) {
            return;
        }

        this.specModal.style.display = 'none';
        document.body.style.overflow = '';
        this.editing = null;
    }

    renderSpecMaterials() {
        const materials = materialOptions(this.config, this.draft.technology);

        if (!materials.includes(this.draft.material)) {
            this.draft.material = materials[0] ?? this.draft.material;
        }

        this.specMaterial.innerHTML = materials
            .map((name) => `<option value="${escapeAttribute(name)}">${escapeHtml(name)}</option>`)
            .join('');

        this.specMaterial.value = this.draft.material;
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
            ['weight', 'time', 'cost'].forEach((key) => set(key, '—'));

            return;
        }

        set('weight', `${formatNumber(summary.weightG, 1)} gram`);
        set('time', formatDuration(summary.minutes));
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
            : '—';

        // Spesifikasi dibaca lewat specificationOf() supaya model yang tersimpan
        // sebelum fitur ini ada tetap menampilkan warna dan finishingnya.
        const spec = specificationOf(record);
        const colors = this.config.materialColors?.options ?? {};
        const finishings = this.config.finishing?.options ?? {};
        const colorLabel = colors[spec.color]?.label ?? '—';
        const colorHex = colors[spec.color]?.hex ?? '#8A817C';
        const finishingLabel = finishings[spec.finishing]?.label ?? 'Tanpa Finishing';

        const rows = [
            ['Dimensi', dimensions],
            ['Volume', `${formatNumber(summary.volumeCm3 ?? 0, 2)} cm³`],
            ['Berat', `${formatNumber(summary.weightG ?? 0, 1)} gram`],
        ];

        // Ringkasan spesifikasi: pengguna dapat membacanya tanpa perlu membuka
        // kembali Edit Specification.
        const specs = [
            ['Teknologi', escapeHtml(spec.technology ?? '')],
            ['Material', escapeHtml(spec.material ?? '')],
            [
                'Warna',
                `<span class="inline-flex items-center gap-1.5">
                    <span class="inline-block h-3 w-3 shrink-0 rounded-full border border-ink-200" style="background: ${escapeAttribute(colorHex)}"></span>
                    ${escapeHtml(colorLabel)}
                </span>`,
            ],
            ['Finishing', escapeHtml(finishingLabel)],
        ];

        return `
            <article class="card overflow-hidden">
                <a href="${escapeAttribute(url)}"
                   target="_blank"
                   rel="noopener"
                   class="group relative block aspect-[4/3] overflow-hidden bg-ink-100"
                   title="Buka viewer 3D ${escapeAttribute(record.name)}">
                    ${record.thumbnail
                        ? `<img src="${escapeAttribute(record.thumbnail)}" alt="Pratinjau model ${escapeAttribute(record.name)}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.04]" loading="lazy">`
                        : '<span class="flex h-full w-full items-center justify-center text-sm text-ink-400">Pratinjau tidak tersedia</span>'}

                    <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-2 bg-ink-950/70 py-2.5 text-xs font-bold text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                        Buka Viewer 3D
                    </span>
                </a>

                <div class="flex flex-1 flex-col p-5">
                    <p class="flex items-center gap-2">
                        <span class="font-mono text-[0.65rem] text-ink-400">#${record.position}</span>
                        <span class="truncate font-display text-sm font-bold text-ink-900" title="${escapeAttribute(record.name)}">${escapeHtml(record.name)}</span>
                    </p>
                    <p class="mt-0.5 text-[0.65rem] uppercase tracking-[0.12em] text-ink-400">
                        ${escapeHtml(record.format)} · ${formatBytes(record.size)}
                    </p>

                    <dl class="mt-4 space-y-1.5 text-xs">
                        ${rows
                            .map(
                                ([label, value]) => `
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="text-ink-400">${label}</dt>
                                        <dd class="text-right font-semibold text-ink-800">${value}</dd>
                                    </div>
                                `
                            )
                            .join('')}
                    </dl>

                    <div class="mt-4 rounded-xl bg-ink-50/80 p-3">
                        <p class="text-[0.6rem] font-bold uppercase tracking-[0.14em] text-ink-400">Spesifikasi</p>
                        <dl class="mt-2 space-y-1 text-xs">
                            ${specs
                                .map(
                                    ([label, value]) => `
                                        <div class="flex items-start justify-between gap-3">
                                            <dt class="text-ink-400">${label}</dt>
                                            <dd class="text-right font-semibold text-ink-800">${value}</dd>
                                        </div>
                                    `
                                )
                                .join('')}
                        </dl>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <label class="text-xs font-semibold text-ink-600" for="qty-${escapeAttribute(record.id)}">Quantity</label>

                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    class="qty-step"
                                    data-model="${escapeAttribute(record.id)}"
                                    data-qty-step="-1"
                                    ${spec.quantity <= 1 ? 'disabled' : ''}
                                    aria-label="Kurangi jumlah ${escapeAttribute(record.name)}">−</button>

                            <input type="number"
                                   id="qty-${escapeAttribute(record.id)}"
                                   class="qty-input"
                                   value="${spec.quantity}"
                                   min="1"
                                   max="10000"
                                   step="1"
                                   inputmode="numeric"
                                   data-qty-input
                                   data-model="${escapeAttribute(record.id)}">

                            <button type="button"
                                    class="qty-step"
                                    data-model="${escapeAttribute(record.id)}"
                                    data-qty-step="1"
                                    aria-label="Tambah jumlah ${escapeAttribute(record.name)}">+</button>

                            <span class="ml-1 text-xs text-ink-400">pcs</span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-end justify-between gap-3 border-t border-ink-100 pt-4">
                        <div>
                            <p class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">Estimasi Harga</p>
                            ${this.showsPrice
                                ? `<p class="mt-0.5 font-display text-base font-bold text-brand-700">${formatCurrency(summary.cost ?? 0)}</p>`
                                : '<p class="mt-0.5 text-xs font-semibold text-ink-400">Login untuk melihat harga</p>'}
                        </div>
                        <p class="text-right text-[0.65rem] text-ink-400">${formatDuration(summary.minutes ?? 0)}</p>
                    </div>

                    ${summary.fits === false
                        ? '<p class="mt-3 rounded-lg bg-brand-50 px-3 py-2 text-[0.7rem] font-semibold text-brand-700">⚠ Melewati area cetak mesin yang dipilih</p>'
                        : ''}

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" class="btn-outline px-4 py-2.5 text-xs" data-edit-spec="${escapeAttribute(record.id)}">
                            Edit Specification
                        </button>
                        <a href="${escapeAttribute(url)}" target="_blank" rel="noopener" class="btn-primary px-4 py-2.5 text-xs">
                            Lihat 3D
                        </a>
                    </div>

                    <button type="button"
                            class="mt-2 text-xs font-semibold text-ink-400 transition-colors hover:text-brand-600"
                            data-remove-model="${escapeAttribute(record.id)}">
                        Hapus model ini
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

                return `
                    <tr>
                        <td class="py-3 pr-3 font-mono text-xs text-ink-400">${index + 1}</td>
                        <td class="max-w-[200px] px-3 py-3">
                            <p class="truncate font-semibold text-ink-900" title="${escapeAttribute(record.name)}">${escapeHtml(record.name)}</p>
                            <p class="text-[0.65rem] uppercase tracking-[0.1em] text-ink-400">
                                ${escapeHtml(record.format)}${scale !== 100 ? ` &middot; skala ${scale}%` : ''}
                            </p>
                        </td>
                        <td class="px-3 py-3">
                            <p class="font-semibold text-ink-800">${escapeHtml(record.payload.printer_name ?? '')}</p>
                            <p class="text-xs text-ink-400">${escapeHtml(summary.technology ?? '')} &middot; ${escapeHtml(summary.material ?? '')}</p>
                        </td>
                        <td class="px-3 py-3 text-right text-ink-700">${formatCount(summary.quantity ?? 1)} unit</td>
                        <td class="px-3 py-3 text-right text-ink-700">${formatNumber(summary.weightG ?? 0, 1)} gr</td>
                        <td class="px-3 py-3 text-right text-ink-700">${formatDuration(estimate.totalMinutes ?? 0)}</td>
                        ${this.showsPrice
                            ? `<td class="py-3 pl-3 text-right font-display font-bold text-brand-700">${formatCurrency(estimate.totalCost ?? 0)}</td>`
                            : ''}
                    </tr>
                `;
            })
            .join('');

        const totals = this.totals();

        this.setTotal('printers', `${formatCount(ready.length)} printer`);
        this.setTotal('models', `${formatCount(ready.length)} model`);
        this.setTotal('weight', `${formatNumber(totals.weightG, 1)} gram`);
        this.setTotal('time', formatDuration(totals.minutes));
        this.setTotal('longest', formatDuration(totals.longestMinutes));
        this.setTotal('cost', this.showsPrice ? formatCurrency(totals.cost) : '—');

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
