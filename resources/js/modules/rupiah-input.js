/**
 * Input nominal Rupiah — pasangan dari resources/views/components/rupiah-input.blade.php.
 *
 * Yang diketik pengguna dibersihkan menjadi angka saja, lalu ditampilkan
 * kembali sebagai "Rp 1.500.000". Angka murninya ditulis ke input tersembunyi
 * dan diumumkan lewat event `input`/`change`, sehingga apa pun yang membaca
 * nilai itu (kalkulasi rumus, pengiriman form) tidak pernah melihat format.
 */

const formatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

/** "Rp 1.500.000"; kosong ditampilkan "Rp 0". */
export function formatRupiah(value) {
    return `Rp ${formatter.format(Math.max(0, Math.round(Number(value) || 0)))}`;
}

/** Hanya digit yang dianggap; karakter lain diabaikan. */
export function parseRupiah(text) {
    const digits = String(text ?? '').replace(/\D/g, '');

    return digits === '' ? 0 : Number(digits);
}

export default function initRupiahInputs(root = document) {
    root.querySelectorAll('[data-rupiah-input]').forEach(bind);
}

function bind(wrapper) {
    const display = wrapper.querySelector('[data-rupiah-display]');
    const hidden = wrapper.querySelector('[data-rupiah-value]');

    if (!display || !hidden || wrapper.dataset.rupiahBound) {
        return;
    }

    wrapper.dataset.rupiahBound = 'true';

    const step = Math.max(1, Number(wrapper.dataset.step) || 1);
    const max = Number(wrapper.dataset.max) || Number.MAX_SAFE_INTEGER;
    const clamp = (value) => Math.min(max, Math.max(0, value));

    // `data-nullable`: kolom yang dikosongkan tetap kosong (tidak menjadi
    // "Rp 0"), supaya validasi wajib-isi di server tetap berlaku.
    const nullable = wrapper.hasAttribute('data-nullable');

    const clear = () => {
        display.value = '';

        if (hidden.value !== '') {
            hidden.value = '';
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const commit = (value, { keepCaret = false } = {}) => {
        const next = clamp(value);

        // Posisi kursor dihitung dari jumlah DIGIT di kanannya, supaya titik
        // ribuan yang bertambah/berkurang tidak membuat kursor melompat.
        const digitsRight = keepCaret
            ? display.value.slice(display.selectionEnd ?? display.value.length).replace(/\D/g, '').length
            : 0;

        display.value = formatRupiah(next);

        if (keepCaret && document.activeElement === display) {
            let position = display.value.length;
            let seen = 0;

            while (position > 0 && seen < digitsRight) {
                position -= 1;

                if (/\d/.test(display.value[position])) {
                    seen += 1;
                }
            }

            // Kursor tidak boleh masuk ke dalam awalan "Rp ".
            position = Math.max(3, position);
            display.setSelectionRange(position, position);
        }

        if (String(next) !== hidden.value) {
            hidden.value = String(next);
            hidden.dispatchEvent(new Event('input', { bubbles: true }));
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    display.addEventListener('input', () => {
        if (nullable && display.value.replace(/\D/g, '') === '') {
            clear();

            return;
        }

        commit(parseRupiah(display.value), { keepCaret: true });
    });

    display.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            event.preventDefault();
            commit(Number(hidden.value) + (event.key === 'ArrowUp' ? step : -step));
        }
    });

    // Kursor yang jatuh di dalam awalan "Rp " dipindah ke akhir, supaya
    // pengguna tidak mengetik di depannya. Pilihan teks (mis. seluruh isi yang
    // terpilih saat masuk lewat Tab atau Ctrl+A) dibiarkan utuh.
    display.addEventListener('focus', () => {
        requestAnimationFrame(() => {
            const start = display.selectionStart;

            if (start !== null && start === display.selectionEnd && start < 3) {
                display.setSelectionRange(display.value.length, display.value.length);
            }
        });
    });

    wrapper.querySelectorAll('[data-rupiah-step]').forEach((button) => {
        button.addEventListener('click', () => {
            commit(Number(hidden.value) + Number(button.dataset.rupiahStep) * step);
            display.focus();
        });
    });

    if (nullable && hidden.value === '') {
        clear();
    } else {
        commit(Number(hidden.value));
    }
}
