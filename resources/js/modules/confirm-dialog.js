/**
 * Modal konfirmasi yang dipakai bersama seluruh halaman.
 *
 * Menggantikan `window.confirm()`: rupanya mengikuti tema aplikasi (termasuk
 * mode gelap), teksnya berbahasa Indonesia seutuhnya, dan tombolnya dapat
 * diberi nama sesuai aksinya — "Ya, Batalkan", "Ya, Logout", dan seterusnya.
 *
 * Ada dua cara memakainya:
 *
 *  1. Deklaratif — pasang `data-confirm` pada <form>. Pengirimannya ditahan
 *     sampai pengguna menekan tombol setuju. Tanpa JavaScript formulirnya tetap
 *     terkirim seperti biasa, jadi tidak ada aksi yang hilang.
 *
 *  2. Lewat `confirmAction()` yang mengembalikan Promise, untuk aksi yang
 *     pesannya dihitung dulu — mis. jumlah penawaran yang sedang terpilih.
 *
 * Markupnya: resources/views/components/confirm-dialog.blade.php, dipasang
 * sekali per layout.
 */

/** Penanda pada <form> yang konfirmasinya sudah dijawab, supaya tidak berulang. */
const PASSED = 'confirmPassed';

let dialog = null;
let elements = null;
let pending = null;
let lastFocused = null;

function collect() {
    if (elements || !dialog) {
        return elements;
    }

    elements = {
        panel: dialog.querySelector('[data-confirm-panel]'),
        title: dialog.querySelector('[data-confirm-title]'),
        message: dialog.querySelector('[data-confirm-message]'),
        accept: dialog.querySelector('[data-confirm-accept]'),
        cancel: dialog.querySelector('[data-confirm-cancel]'),
    };

    return elements;
}

/** Tutup modal dan jawab Promise yang sedang menunggu. */
function settle(confirmed) {
    if (!dialog) {
        return;
    }

    dialog.classList.add('hidden');
    dialog.classList.remove('flex');

    const resolve = pending;
    pending = null;

    lastFocused?.focus?.();
    lastFocused = null;

    resolve?.(confirmed);
}

/**
 * Tanyakan sesuatu, lalu jawab `true` bila pengguna menyetujuinya.
 *
 * Tanpa markup modal di halaman ini — mis. layout yang belum memasangnya —
 * pertanyaannya jatuh kembali ke `window.confirm()` supaya aksinya tetap dapat
 * dijalankan alih-alih diam tanpa penjelasan.
 *
 * @param {{title?: string, message?: string, accept?: string, cancel?: string}} options
 * @returns {Promise<boolean>}
 */
export function confirmAction(options = {}) {
    const { title, message, accept, cancel } = options;

    if (!dialog) {
        return Promise.resolve(window.confirm(message || title || 'Lanjutkan?'));
    }

    // Pertanyaan yang datang saat modal masih terbuka membatalkan yang lama,
    // bukan menumpuk di belakangnya.
    if (pending) {
        settle(false);
    }

    const parts = collect();

    parts.title.textContent = title || 'Konfirmasi';
    parts.message.textContent = message || '';
    parts.message.classList.toggle('hidden', !message);
    parts.accept.textContent = accept || 'Ya, Lanjutkan';
    parts.cancel.textContent = cancel || 'Batal';

    lastFocused = document.activeElement;

    dialog.classList.remove('hidden');
    dialog.classList.add('flex');

    // Fokus jatuh ke "Batal": menekan Enter tanpa membaca tidak menjalankan
    // aksinya.
    parts.cancel.focus();

    return new Promise((resolve) => {
        pending = resolve;
    });
}

/** Isi modal yang dibaca dari atribut `data-confirm-*` sebuah elemen. */
export function confirmOptionsOf(element) {
    return {
        title: element.dataset.confirmTitle,
        message: element.dataset.confirm || element.dataset.confirmMessage,
        accept: element.dataset.confirmAccept,
        cancel: element.dataset.confirmCancel,
    };
}

export default function initConfirmDialog() {
    dialog = document.querySelector('[data-confirm-dialog]');

    if (dialog) {
        const parts = collect();

        parts.accept.addEventListener('click', () => settle(true));
        parts.cancel.addEventListener('click', () => settle(false));

        // Klik di luar panel dan tombol Escape sama-sama berarti "batal".
        dialog.addEventListener('click', (event) => {
            if (!parts.panel.contains(event.target)) {
                settle(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && pending) {
                settle(false);
            }
        });
    }

    // Formulir bertanda `data-confirm`: pengirimannya ditahan sampai dijawab.
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || form.dataset.confirm === undefined) {
            return;
        }

        if (form.dataset[PASSED] === 'true') {
            delete form.dataset[PASSED];

            return;
        }

        event.preventDefault();

        confirmAction(confirmOptionsOf(form)).then((confirmed) => {
            if (!confirmed) {
                return;
            }

            form.dataset[PASSED] = 'true';

            // requestSubmit() menjalankan validasi bawaan dan memicu ulang
            // event ini — penanda di atas yang meloloskannya.
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    });
}
