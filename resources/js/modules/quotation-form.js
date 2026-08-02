import { formatCurrency, formatDuration, formatNumber } from './print-estimator';

/**
 * Form permintaan penawaran pada halaman 3D Models.
 *
 * Berbeda dengan pratinjau yang sepenuhnya berjalan di browser, langkah ini
 * memang mengunggah berkas model ke server bersama hasil analisis, pilihan
 * teknologi/material, dan estimasi — supaya tim produksi dapat menindaklanjuti
 * permintaan lewat dashboard admin.
 *
 * Seluruh model yang sedang ditinjau dikirim sekaligus sebagai satu permintaan,
 * sehingga pelanggan hanya menerima satu Nomor Tracking untuk semuanya.
 */
export default function initQuotationForm(viewer, root) {
    const modal = root.querySelector('[data-quotation-modal]');
    const form = root.querySelector('[data-quotation-form]');
    const openButton = root.querySelector('[data-open-quotation]');

    if (!modal || !form || !openButton) {
        return;
    }

    const dialog = modal.querySelector('[data-quotation-dialog]');
    const summary = modal.querySelector('[data-quotation-summary]');
    const successPanel = modal.querySelector('[data-quotation-success]');
    const referenceEl = modal.querySelector('[data-quotation-reference]');
    const successNote = modal.querySelector('[data-quotation-success-note]');
    const submitButton = form.querySelector('[data-quotation-submit]');
    const submitLabel = form.querySelector('[data-quotation-submit-label]');
    const spinner = form.querySelector('[data-quotation-spinner]');
    const alertBox = form.querySelector('[data-quotation-alert]');

    const badges = {
        ready: ['🟢 Ready', 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        warning: ['🟡 Perlu perbaikan', 'border-amber-200 bg-amber-50 text-amber-700'],
        not_printable: ['🔴 Not Printable', 'border-brand-200 bg-brand-50 text-brand-700'],
    };

    // Pratinjau dan seluruh simulasi terbuka untuk siapa saja; hanya pengiriman
    // penawaran yang menuntut akun. Bila belum masuk, tombol "Minta Penawaran"
    // memunculkan ajakan login alih-alih formulirnya.
    const loginModal = root.querySelector('[data-login-modal]');
    const isAuthenticated = viewer.config?.auth?.check !== false;

    let lastFocused = null;

    const setLoginOpen = (open) => {
        if (!loginModal) {
            return;
        }

        loginModal.style.display = open ? 'flex' : 'none';
        document.body.style.overflow = open ? 'hidden' : '';
    };

    loginModal?.querySelectorAll('[data-login-close]').forEach((el) => {
        el.addEventListener('click', () => setLoginOpen(false));
    });

    loginModal?.addEventListener('mousedown', (event) => {
        if (!loginModal.querySelector('[data-login-dialog]').contains(event.target)) {
            setLoginOpen(false);
        }
    });

    const setOpen = (open) => {
        modal.style.display = open ? 'flex' : 'none';
        document.body.style.overflow = open ? 'hidden' : '';

        if (open) {
            lastFocused = document.activeElement;
            form.querySelector('input[name="name"]')?.focus();
        } else {
            lastFocused?.focus?.();
        }
    };

    /** Daftar seluruh model yang akan ikut terkirim, beserta totalnya. */
    const renderSummary = (payload) => {
        const rows = payload.items
            .map((item, index) => {
                const [label, classes] = badges[item.analysis_status] ?? ['—', 'border-ink-200 bg-ink-50 text-ink-600'];

                return `
                    <li class="flex flex-wrap items-start justify-between gap-3 border-b border-ink-100 py-3 last:border-0">
                        <div class="min-w-0">
                            <p class="flex items-center gap-2">
                                <span class="font-mono text-[0.65rem] text-ink-400">Printer ${index + 1}</span>
                                <span class="truncate text-sm font-bold text-ink-900" title="${escapeAttribute(item.file.name)}">${escapeHtml(item.file.name)}</span>
                            </p>
                            <p class="mt-1 pl-5 text-xs text-ink-500">
                                ${escapeHtml(item.printer_name)} &middot; ${escapeHtml(item.technology)} &middot;
                                ${escapeHtml(item.material)} &middot; ${item.quantity} pcs
                                ${item.scale_percent !== 100 ? ` &middot; skala ${item.scale_percent}%` : ''}
                                ${item.hollow_enabled ? ' &middot; hollow' : ''}
                            </p>
                            <p class="mt-0.5 pl-5 text-[0.7rem] text-ink-400">
                                Warna ${escapeHtml(colorLabel(viewer.config, item.material_color))} &middot;
                                Finishing ${escapeHtml(finishingLabel(viewer.config, item.finishing))}
                            </p>
                            ${item.fits_build_volume
                                ? ''
                                : '<p class="mt-1 pl-5 text-[0.7rem] font-semibold text-brand-700">⚠ Melewati area cetak mesin yang dipilih</p>'}
                        </div>
                        <div class="text-right">
                            <p class="font-display text-sm font-bold text-brand-700">${formatCurrency(item.estimate.totalCost)}</p>
                            <span class="mt-1 inline-block rounded-full border px-2 py-0.5 text-[0.6rem] font-bold ${classes}">${label}</span>
                        </div>
                    </li>
                `;
            })
            .join('');

        summary.innerHTML = `
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink-100 pb-3">
                <div>
                    <p class="font-display text-sm font-bold text-ink-900">Ringkasan Permintaan</p>
                    <p class="mt-0.5 text-[0.65rem] text-ink-400">Satu printer untuk setiap model</p>
                </div>
                <span class="rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">
                    ${payload.items.length} printer
                </span>
            </div>

            <ul class="mt-1">${rows}</ul>

            <dl class="mt-3 grid gap-3 border-t border-ink-100 pt-3 sm:grid-cols-3">
                ${[
                    ['Total Berat', `${formatNumber(payload.totals.weightG, 1)} gram`],
                    ['Total Waktu', formatDuration(payload.totals.minutes)],
                    ['Total Biaya', formatCurrency(payload.totals.cost)],
                ]
                    .map(
                        ([label, value]) => `
                            <div>
                                <dt class="text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-ink-400">${label}</dt>
                                <dd class="mt-1 text-sm font-bold text-ink-800">${value}</dd>
                            </div>
                        `
                    )
                    .join('')}
            </dl>
        `;
    };

    const clearFieldErrors = () => {
        form.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
            el.style.display = 'none';
        });
        alertBox.style.display = 'none';
    };

    /**
     * Tampilkan pesan validasi di bawah isiannya masing-masing.
     *
     * Kesalahan pada salah satu model datang dengan kunci `items.0.model`, jadi
     * seluruhnya dikumpulkan pada satu baris dengan menyebut nomor modelnya
     * supaya pengguna tahu file mana yang perlu diperbaiki.
     */
    const showFieldErrors = (errors, payload) => {
        const itemMessages = [];

        Object.entries(errors).forEach(([field, messages]) => {
            const match = field.match(/^items\.(\d+)\./);

            if (match || field === 'items') {
                const name = payload?.items?.[Number(match?.[1])]?.file?.name;
                itemMessages.push(name ? `${name}: ${messages[0]}` : messages[0]);

                return;
            }

            const target = form.querySelector(`[data-error-for="${field}"]`);

            if (target) {
                target.textContent = messages[0];
                target.style.display = 'block';
            }
        });

        const itemTarget = form.querySelector('[data-error-for="items"]');

        if (itemTarget && itemMessages.length) {
            itemTarget.textContent = itemMessages.join(' · ');
            itemTarget.style.display = 'block';
        }
    };

    const showAlert = (message) => {
        alertBox.textContent = message;
        alertBox.style.display = 'block';
    };

    const setSubmitting = (submitting) => {
        submitButton.disabled = submitting;
        submitLabel.textContent = submitting ? 'Mengirim…' : 'Kirim Permintaan';
        spinner.style.display = submitting ? 'inline-block' : 'none';
    };

    openButton.addEventListener('click', () => {
        if (!isAuthenticated) {
            setLoginOpen(true);
            return;
        }

        const payload = viewer.submissionPayload();

        if (!payload) {
            return;
        }

        clearFieldErrors();
        successPanel.style.display = 'none';
        form.style.display = 'block';
        renderSummary(payload);
        setOpen(true);
    });

    modal.querySelectorAll('[data-quotation-close]').forEach((el) => {
        el.addEventListener('click', () => setOpen(false));
    });

    modal.addEventListener('mousedown', (event) => {
        if (!dialog.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (loginModal?.style.display === 'flex') {
            setLoginOpen(false);
        }

        if (modal.style.display === 'flex') {
            setOpen(false);
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFieldErrors();

        const payload = viewer.submissionPayload();

        if (!payload) {
            showAlert('Belum ada model yang dimuat. Unggah minimal satu file terlebih dahulu sebelum mengirim permintaan.');
            return;
        }

        // Batas unggah server (php.ini) bisa lebih kecil dari batas pratinjau,
        // jadi dicegat di sini agar pesannya jelas, bukan berakhir error 413.
        const oversized = payload.items.filter(
            (item) => viewer.config?.limits?.uploadMaxBytes && item.file.size > viewer.config.limits.uploadMaxBytes
        );

        if (oversized.length) {
            const maxMb = (viewer.config.limits.uploadMaxBytes / 1024 / 1024).toFixed(1);
            showAlert(
                `File ${oversized.map((item) => item.file.name).join(', ')} melebihi batas unggah server (${maxMb} MB per file). ` +
                    'Model tetap dapat Anda tinjau di viewer — untuk penawaran, kirimkan filenya langsung melalui email atau WhatsApp kami.'
            );
            return;
        }

        // Seluruh model dikirim dalam satu POST, jadi ukuran gabungannya juga
        // dibatasi post_max_size.
        const totalBytes = payload.items.reduce((carry, item) => carry + item.file.size, 0);
        const maxTotal = viewer.config?.limits?.uploadMaxTotalBytes;

        if (maxTotal && totalBytes > maxTotal) {
            showAlert(
                `Ukuran seluruh file ${(totalBytes / 1024 / 1024).toFixed(1)} MB melebihi batas satu permintaan ` +
                    `(${(maxTotal / 1024 / 1024).toFixed(1)} MB). Kurangi jumlah model lalu kirim sisanya sebagai permintaan terpisah.`
            );
            return;
        }

        const body = new FormData(form);

        payload.items.forEach((item, index) => {
            const field = (name) => `items[${index}][${name}]`;

            body.append(field('model'), item.file, item.file.name);
            body.append(field('technology'), item.technology);
            body.append(field('material'), item.material);

            // Setiap model dicetak pada mesinnya sendiri.
            body.append(field('printer'), item.printer);
            ['x', 'y', 'z'].forEach((axis) => {
                body.append(`items[${index}][build_volume][${axis}]`, String(item.build_volume[axis]));
            });
            body.append(field('quantity'), String(item.quantity));
            body.append(field('model_volume_cm3'), String(item.model_volume_cm3));
            body.append(field('resolution'), item.resolution ?? '');
            body.append(field('support_enabled'), item.support_enabled ? '1' : '0');

            if (item.support_volume_cm3 !== null && item.support_volume_cm3 !== undefined) {
                body.append(field('support_volume_cm3'), String(item.support_volume_cm3));
            }

            // Simulasi yang dijalankan di browser; server menghitung ulang
            // estimasinya dari nilai-nilai ini.
            body.append(field('scale_percent'), String(item.scale_percent));
            body.append(field('infill_density'), String(item.infill_density));
            body.append(field('infill_pattern'), item.infill_pattern);
            body.append(field('material_color'), item.material_color);
            body.append(field('finishing'), item.finishing ?? 'none');
            body.append(field('fits_build_volume'), item.fits_build_volume ? '1' : '0');

            body.append(field('hollow_enabled'), item.hollow_enabled ? '1' : '0');

            if (item.hollow_enabled) {
                body.append(field('hollow_wall_thickness_mm'), String(item.hollow_wall_thickness_mm));
                body.append(field('hollow_drain_diameter_mm'), String(item.hollow_drain_diameter_mm));
                body.append(field('hollow_drain_position'), item.hollow_drain_position);
            }

            body.append(field('analysis_status'), item.analysis_status);
            body.append(field('analysis'), JSON.stringify(item.analysis));
            body.append(field('model_stats'), JSON.stringify(item.model_stats));
        });

        setSubmitting(true);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            });

            // Sesi habis atau belum masuk: arahkan kembali ke ajakan login
            // supaya isian tidak hilang begitu saja tanpa penjelasan.
            if (response.status === 401 || response.status === 419) {
                setOpen(false);
                setLoginOpen(true);
                return;
            }

            if (response.status === 422) {
                const data = await response.json();
                showFieldErrors(data.errors ?? {}, payload);
                showAlert('Periksa kembali isian yang ditandai di bawah ini.');
                return;
            }

            if (!response.ok) {
                showAlert('Permintaan gagal dikirim. Silakan coba lagi beberapa saat lagi atau hubungi kami langsung.');
                return;
            }

            const data = await response.json();

            referenceEl.textContent = data.tracking_number ?? '-';

            if (successNote) {
                const count = data.model_count ?? payload.items.length;
                successNote.textContent = count > 1
                    ? `${count} model Anda tercatat dalam satu permintaan dengan nomor tracking di bawah ini.`
                    : 'Permintaan Anda tercatat dengan nomor tracking di bawah ini.';
            }

            // Tautan unduh PDF dan halaman tracking datang dari server, sehingga
            // nomor tracking tidak perlu disusun ulang di sisi klien.
            const documentLink = modal.querySelector('[data-quotation-document]');
            const trackingLink = modal.querySelector('[data-quotation-tracking]');

            if (documentLink && data.document_url) {
                documentLink.href = data.document_url;
            }

            if (trackingLink && data.tracking_url) {
                trackingLink.href = data.tracking_url;
            }

            form.style.display = 'none';
            successPanel.style.display = 'block';
            form.reset();
        } catch (error) {
            console.error(error);
            showAlert('Koneksi ke server terputus. Periksa jaringan Anda lalu coba lagi.');
        } finally {
            setSubmitting(false);
        }
    });
}

/** Label warna material sesuai config, mis. "Bening". */
function colorLabel(config, key) {
    return config?.materialColors?.options?.[key]?.label ?? '—';
}

/** Label finishing sesuai config, mis. "Polishing". */
function finishingLabel(config, key) {
    return config?.finishing?.options?.[key]?.label ?? 'Tanpa Finishing';
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/"/g, '&quot;');
}
