/**
 * Perilaku dashboard admin & pelanggan.
 *
 * Yang ditangani di sini:
 *  1. sidebar yang dapat dibuka-tutup pada layar kecil;
 *  2. panel notifikasi di balik ikon lonceng;
 *  3. penarikan berkala notifikasi baru beserta popupnya;
 *  4. hitung mundur batas waktu pembayaran;
 *  5. tombol salin (mis. nomor rekening);
 *  6. pengalih mode terang/gelap;
 *  7. penghapusan massal pada tabel Price List;
 *  8. expand detail mesin pada tabel Machine Cost;
 *  9. perhitungan realtime Rumus Harga SLA Industries.
 *
 * Notifikasi ditarik berkala (polling) alih-alih lewat WebSocket supaya
 * pemberitahuan terasa langsung tanpa menuntut server tambahan. Endpointnya
 * hanya mengembalikan notifikasi yang belum dibaca milik akun yang sedang
 * masuk.
 */

import initRupiahInputs from './modules/rupiah-input';
import initSlaIndustriesFormulas from './modules/sla-industries-formula';

const POLL_INTERVAL_MS = 20000;

initThemeToggle();
initSidebar();
initNotifications();
initPaymentCountdown();
initCopyButtons();
initBulkDelete();
initAutoSubmit();
initMachineDetails();
// Input rupiah dipasang lebih dulu: rumus di bawah membaca nilai mentahnya.
initRupiahInputs();
initSlaIndustriesFormulas();

/**
 * Mode terang/gelap.
 *
 * Temanya sendiri sudah dipasang skrip inline di <head> sebelum halaman
 * digambar — di sini hanya perpindahannya yang diurus, beserta penyelarasan
 * rupa tombolnya dengan keadaan yang sedang berlaku.
 *
 * Pilihan disimpan di localStorage, jadi melekat pada perangkat, bukan pada
 * akun. Selama pengguna belum pernah memilih sendiri, temanya mengikuti
 * setelan sistem operasinya dan ikut berubah bila setelan itu berubah.
 */
function initThemeToggle() {
    const toggle = document.querySelector('[data-theme-toggle]');

    if (!toggle) {
        return;
    }

    const root = document.documentElement;
    const media = window.matchMedia('(prefers-color-scheme: dark)');

    const isDark = () => root.getAttribute('data-theme') === 'dark';

    const render = () => {
        const dark = isDark();
        const label = dark ? 'Kembali ke mode terang' : 'Aktifkan mode gelap';

        toggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);

        // Ikon menunjukkan MODE YANG SEDANG BERLAKU, bukan tujuan kliknya —
        // pengguna melihat bulan saat gelap, matahari saat terang.
        toggle.querySelector('[data-theme-icon="light"]')?.classList.toggle('hidden', dark);
        toggle.querySelector('[data-theme-icon="dark"]')?.classList.toggle('hidden', !dark);
    };

    const apply = (dark) => {
        if (dark) {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }

        render();
    };

    toggle.addEventListener('click', () => {
        const dark = !isDark();

        apply(dark);

        try {
            localStorage.setItem('nusama-theme', dark ? 'dark' : 'light');
        } catch (error) {
            // Penyimpanan diblokir: temanya tetap berubah untuk halaman ini,
            // hanya tidak diingat saat halaman berikutnya dibuka.
        }
    });

    // Mengikuti setelan sistem selama pengguna belum memilih sendiri.
    media.addEventListener('change', (event) => {
        try {
            if (localStorage.getItem('nusama-theme')) {
                return;
            }
        } catch (error) {
            return;
        }

        apply(event.matches);
    });

    render();
}

/**
 * Sidebar dashboard.
 *
 *  - Di bawah md tersembunyi dan dibuka lewat tombol menu; di tablet tampil
 *    icon-only dan tombol yang sama membukanya penuh (`.is-open`).
 *  - Di desktop dapat diciutkan menjadi icon-only; pilihannya disimpan di
 *    localStorage dan dipulihkan skrip inline di <head> sebelum digambar.
 *  - Grup accordion (Price List): hanya satu yang terbuka pada satu waktu.
 */
function initSidebar() {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    const collapse = document.querySelector('[data-sidebar-collapse]');
    const root = document.documentElement;

    if (!sidebar) {
        return;
    }

    const desktop = window.matchMedia('(min-width: 64rem)');

    const isOpen = () => sidebar.classList.contains('is-open');

    const setOpen = (open) => {
        sidebar.classList.toggle('-translate-x-full', !open);
        sidebar.classList.toggle('is-open', open);
        backdrop?.classList.toggle('hidden', !open);
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    // Sidebar sedang tampil sebagai deretan ikon saja.
    const isRail = () => {
        if (isOpen()) {
            return false;
        }

        return desktop.matches
            ? root.classList.contains('sidebar-collapsed')
            : window.matchMedia('(min-width: 48rem)').matches;
    };

    const renderCollapse = () => {
        const collapsed = root.classList.contains('sidebar-collapsed');
        const label = collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar';

        collapse?.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
        collapse?.setAttribute('aria-label', label);
        collapse?.setAttribute('title', label);
    };

    const setCollapsed = (collapsed) => {
        root.classList.toggle('sidebar-collapsed', collapsed);
        renderCollapse();

        try {
            localStorage.setItem('nusama-sidebar', collapsed ? 'collapsed' : 'expanded');
        } catch (error) {
            // Penyimpanan diblokir: pilihannya berlaku sampai halaman ditutup.
        }
    };

    toggle?.addEventListener('click', () => setOpen(!isOpen()));
    backdrop?.addEventListener('click', () => setOpen(false));
    collapse?.addEventListener('click', () => setCollapsed(!root.classList.contains('sidebar-collapsed')));
    document.addEventListener('sidebar:close', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
    });

    renderCollapse();

    // --- grup accordion ------------------------------------------------------
    const groups = sidebar.querySelectorAll('[data-sidebar-group]');

    const openGroup = (group, open) => {
        group.querySelector('[data-sidebar-group-list]')?.classList.toggle('hidden', !open);
        group.querySelector('[data-sidebar-group-toggle]')?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const showOnly = (target) => {
        groups.forEach((group) => openGroup(group, group === target));
    };

    groups.forEach((group) => {
        const button = group.querySelector('[data-sidebar-group-toggle]');

        button?.addEventListener('click', () => {
            // Dalam mode ikon, isi grup tidak punya tempat: lebarkan dulu.
            if (isRail()) {
                desktop.matches ? setCollapsed(false) : setOpen(true);
                showOnly(group);

                return;
            }

            const expanded = button.getAttribute('aria-expanded') === 'true';

            expanded ? openGroup(group, false) : showOnly(group);
        });

        group.addEventListener('sidebar:open', () => showOnly(group));
    });
}

function initNotifications() {
    const root = document.querySelector('[data-notification-bell]');

    if (!root) {
        return;
    }

    const button = root.querySelector('[data-bell-toggle]');
    const panel = root.querySelector('[data-bell-panel]');
    const list = root.querySelector('[data-bell-list]');
    const badge = root.querySelector('[data-bell-badge]');
    const toasts = document.querySelector('[data-notification-toasts]');
    const endpoint = root.dataset.notificationBell;

    // Notifikasi yang sudah tampil saat halaman dimuat tidak perlu muncul lagi
    // sebagai popup — hanya yang benar-benar baru datang.
    const seen = new Set(
        (root.dataset.seen ?? '')
            .split(',')
            .map((id) => id.trim())
            .filter(Boolean)
    );

    button?.addEventListener('click', () => {
        panel.classList.toggle('hidden');
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            panel?.classList.add('hidden');
        }
    });

    const render = (notifications) => {
        if (!list) {
            return;
        }

        if (!notifications.length) {
            list.innerHTML =
                '<p class="px-4 py-6 text-center text-sm text-ink-400">Tidak ada notifikasi baru.</p>';
            return;
        }

        list.innerHTML = notifications
            .map(
                (item) => `
                    <a href="${escapeAttribute(item.url ?? '#')}"
                       class="block border-b border-ink-100 px-4 py-3 transition-colors last:border-0 hover:bg-brand-50/50">
                        <p class="text-sm font-bold text-ink-900">${escapeHtml(item.title)}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-ink-500">${escapeHtml(item.message)}</p>
                        <p class="mt-1 text-[0.65rem] text-ink-400">${escapeHtml(item.created_at ?? '')}</p>
                    </a>
                `
            )
            .join('');
    };

    const toast = (item) => {
        if (!toasts) {
            return;
        }

        const element = document.createElement('div');
        element.className =
            'pointer-events-auto w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-ink-100 bg-white p-4 shadow-card-hover';
        element.innerHTML = `
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M18 8.5a6 6 0 0 0-12 0c0 5-2 6.5-2 6.5h16s-2-1.5-2-6.5Z" />
                        <path d="M13.7 19a2 2 0 0 1-3.4 0" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-ink-900">${escapeHtml(item.title)}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-ink-500">${escapeHtml(item.message)}</p>
                    ${
                        item.url
                            ? `<a href="${escapeAttribute(item.url)}" class="mt-2 inline-block text-xs font-semibold text-brand-600 hover:text-brand-700">Lihat detail →</a>`
                            : ''
                    }
                </div>
                <button type="button" class="rounded-lg p-1 text-ink-300 transition-colors hover:text-ink-600" aria-label="Tutup notifikasi">✕</button>
            </div>
        `;

        element.querySelector('button')?.addEventListener('click', () => element.remove());
        toasts.append(element);

        setTimeout(() => element.remove(), 12000);
    };

    const poll = async () => {
        try {
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (badge) {
                badge.textContent = data.unread_count;
                badge.classList.toggle('hidden', !data.unread_count);
            }

            render(data.notifications ?? []);

            (data.notifications ?? [])
                .filter((item) => !seen.has(item.id))
                .forEach((item) => {
                    seen.add(item.id);
                    toast(item);
                });
        } catch (error) {
            // Jaringan sedang terputus; percobaan berikutnya berjalan seperti biasa.
        }
    };

    setInterval(poll, POLL_INTERVAL_MS);
}

/**
 * Hitung mundur batas waktu pembayaran, mis. "23 Jam 45 Menit 10 Detik".
 *
 * Yang menentukan penawaran kedaluwarsa tetap server: begitu hitungannya
 * habis, halaman dimuat ulang sekali agar status barunya datang dari sana,
 * bukan diputuskan di browser.
 */
function initPaymentCountdown() {
    const el = document.querySelector('[data-payment-countdown]');

    if (!el) {
        return;
    }

    const deadline = new Date(el.dataset.deadline ?? '').getTime();

    if (!Number.isFinite(deadline)) {
        return;
    }

    const expiredLabel = el.dataset.expiredLabel ?? 'Waktu pembayaran habis';
    let reloaded = false;

    const tick = () => {
        const remaining = Math.floor((deadline - Date.now()) / 1000);

        if (remaining <= 0) {
            el.textContent = expiredLabel;
            clearInterval(timer);

            if (!reloaded) {
                reloaded = true;
                setTimeout(() => window.location.reload(), 1500);
            }

            return;
        }

        const hours = Math.floor(remaining / 3600);
        const minutes = Math.floor((remaining % 3600) / 60);
        const seconds = remaining % 60;

        el.textContent = `${hours} Jam ${minutes} Menit ${seconds} Detik`;
    };

    const timer = setInterval(tick, 1000);
    tick();
}

/**
 * Tab halaman Price List: FDM/SLA/Packaging/Machine Cost tampil satu per
 * satu, tanpa reload. Tab aktif mengikuti query string `?tab=` bila ada
 * (mis. setelah submit pencarian salah satu tabel) supaya reload halaman
 * tidak mengembalikan pengguna ke tab FDM.
 */
/**
 * Expand detail mesin pada tabel Machine Cost (Price List).
 *
 * Isinya sudah tergambar sejak halaman dimuat sebagai baris <tr> tersembunyi
 * tepat di bawah mesinnya — tidak ada permintaan ke server saat dibuka, dan
 * detailnya tetap terbaca pembaca layar maupun pencarian dalam halaman.
 *
 * Tiap mesin berdiri sendiri: membuka satu tidak menutup yang lain.
 */
function initMachineDetails() {
    document.querySelectorAll('[data-machine-toggle]').forEach((toggle) => {
        const id = toggle.dataset.machineToggle;
        const detail = document.querySelector(`[data-machine-detail="${id}"]`);
        const icon = toggle.querySelector('[data-machine-toggle-icon]');
        const name = toggle.getAttribute('aria-label')?.replace(/^Buka detail mesin /, '') || 'mesin';

        if (!detail) {
            return;
        }

        toggle.addEventListener('click', () => {
            const open = detail.classList.toggle('hidden') === false;

            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', `${open ? 'Tutup' : 'Buka'} detail mesin ${name}`);

            if (icon) {
                // Tanda minus memakai U+2212, bukan hubung, supaya sejajar
                // dengan tanda tambah pada tombol yang sama.
                icon.textContent = open ? '\u2212' : '+';
            }
        });
    });
}

/** Tombol salin sederhana; labelnya kembali semula setelah dua detik. */
function initCopyButtons() {
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const original = button.textContent;

            try {
                await navigator.clipboard.writeText(button.dataset.copy ?? '');
                button.textContent = button.dataset.copyDone ?? 'Tersalin!';
            } catch {
                // Clipboard ditolak browser — nomornya tetap terbaca di halaman.
                button.textContent = 'Salin manual';
            }

            setTimeout(() => {
                button.textContent = original;
            }, 2000);
        });
    });
}

/**
 * Penghapusan massal pada tabel Price List (Material FDM & SLA).
 *
 * Satu fungsi melayani kedua tab; yang membedakan hanya nilai atribut
 * `data-bulk-*`, jadi menambah tabel ketiga nanti cukup menyalin markupnya
 * tanpa menyentuh berkas ini.
 *
 * Kotak centang baris berada di dalam <table> sedangkan formulirnya di luar —
 * keduanya dijembatani atribut `form` pada HTML, bukan oleh JavaScript. Yang
 * dikerjakan di sini hanya tiga: centang-semua, menghitung yang terpilih, dan
 * menahan pengiriman sampai dikonfirmasi.
 */
function initBulkDelete() {
    document.querySelectorAll('[data-bulk-form]').forEach((form) => {
        const key = form.dataset.bulkForm;
        const noun = form.dataset.bulkNoun || 'baris';

        const scope = form.closest('[data-price-list-panel]') || document;
        const all = scope.querySelector(`[data-bulk-all="${key}"]`);
        const bar = scope.querySelector(`[data-bulk-bar="${key}"]`);
        const counter = scope.querySelector(`[data-bulk-count="${key}"]`);
        const clear = scope.querySelector(`[data-bulk-clear="${key}"]`);
        const items = () => Array.from(scope.querySelectorAll(`[data-bulk-item="${key}"]`));

        if (items().length === 0) {
            return;
        }

        const selected = () => items().filter((item) => item.checked);

        const render = () => {
            const count = selected().length;
            const total = items().length;

            if (counter) {
                counter.textContent = String(count);
            }

            bar?.classList.toggle('hidden', count === 0);
            bar?.classList.toggle('flex', count > 0);

            if (all) {
                all.checked = count > 0 && count === total;
                // Sebagian terpilih ditandai garis, bukan centang penuh.
                all.indeterminate = count > 0 && count < total;
            }

            // Baris terpilih diberi latar agar terlihat saat tabelnya panjang.
            items().forEach((item) => {
                item.closest('tr')?.classList.toggle('bg-brand-50/60', item.checked);
            });
        };

        all?.addEventListener('change', () => {
            items().forEach((item) => {
                item.checked = all.checked;
            });

            render();
        });

        scope.addEventListener('change', (event) => {
            if (event.target.matches(`[data-bulk-item="${key}"]`)) {
                render();
            }
        });

        clear?.addEventListener('click', () => {
            items().forEach((item) => {
                item.checked = false;
            });

            render();
        });

        form.addEventListener('submit', (event) => {
            const count = selected().length;

            if (count === 0) {
                event.preventDefault();

                return;
            }

            const confirmed = window.confirm(
                `Hapus ${count} ${noun} yang dipilih? Tindakan ini tidak dapat dibatalkan.`
            );

            if (!confirmed) {
                event.preventDefault();
            }
        });

        render();
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/"/g, '&quot;');
}

/**
 * Kirim formulir begitu kontrol bertanda `data-auto-submit` diubah, mis. switch
 * Status pada tabel Teknologi. Tanpa JavaScript formulirnya tetap sah, hanya
 * tidak terkirim otomatis.
 */
function initAutoSubmit() {
    document.addEventListener('change', (event) => {
        const control = event.target.closest('[data-auto-submit]');

        if (control?.form) {
            control.form.requestSubmit();
        }
    });
}
