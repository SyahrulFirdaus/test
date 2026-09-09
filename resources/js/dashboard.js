/**
 * Perilaku dashboard admin & pelanggan.
 *
 * Yang ditangani di sini:
 *  1. sidebar yang dapat dibuka-tutup pada layar kecil;
 *  2. panel notifikasi di balik ikon lonceng;
 *  3. penarikan berkala notifikasi baru beserta popupnya;
 *  4. hitung mundur batas waktu pembayaran;
 *  5. tombol salin (mis. nomor rekening).
 *
 * Notifikasi ditarik berkala (polling) alih-alih lewat WebSocket supaya
 * pemberitahuan terasa langsung tanpa menuntut server tambahan. Endpointnya
 * hanya mengembalikan notifikasi yang belum dibaca milik akun yang sedang
 * masuk.
 */

const POLL_INTERVAL_MS = 20000;

initSidebar();
initNotifications();
initPaymentCountdown();
initCopyButtons();

/** Sidebar tersembunyi di layar kecil dan dibuka lewat tombol menu. */
function initSidebar() {
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    if (!toggle || !sidebar) {
        return;
    }

    const setOpen = (open) => {
        sidebar.classList.toggle('-translate-x-full', !open);
        backdrop?.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        setOpen(sidebar.classList.contains('-translate-x-full'));
    });

    backdrop?.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
        }
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

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function escapeAttribute(value) {
    return escapeHtml(value).replace(/"/g, '&quot;');
}
