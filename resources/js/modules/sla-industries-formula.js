/**
 * Perhitungan realtime Rumus Harga SLA Industries.
 *
 * Aritmetikanya SENGAJA identik dengan App\Support\SlaIndustries::compute(),
 * termasuk pembulatan ke atas ke rupiah penuh pada tiap langkah — kalau tidak,
 * angka yang dilihat admin saat mengetik akan bergeser sendiri begitu form
 * disimpan dan server menghitung ulang.
 *
 * Kurs "Dollar Hari Ini" TIDAK diketik admin: nilainya datang dari endpoint
 * kurs, yang membaca simpanan server yang sama dengan yang dipakai saat
 * perhitungan disimpan. Karena keduanya satu sumber, angka di layar tidak
 * mungkin berbeda dari angka yang tersimpan.
 *
 * Yang ditulis di sini hanya urutan perhitungannya. Tidak ada satu pun nilai
 * kurs maupun harga yang ditanam di dalam kode.
 */

const MIN_MARGIN = 30;
const MAX_MARGIN = 50;

const rupiah = (value) => Math.ceil(value);

/** "Rp17.690" — tanpa spasi, sama persis dengan penata angka di sisi PHP. */
const idr = (value) => `Rp${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value)}`;

const usd = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * Rumus Harga SLA Industries.
 *
 * @param {{usdRate:number, jlcPriceUsd:number, jlcShippingUsd:number, customsIdr:number, marginPercent:number}} input
 */
export function computeSlaIndustries(input) {
    const usdRate = Math.max(0, Number(input.usdRate) || 0);
    const jlcPriceUsd = Math.max(0, Number(input.jlcPriceUsd) || 0);
    const jlcShippingUsd = Math.max(0, Number(input.jlcShippingUsd) || 0);
    const customsIdr = rupiah(Math.max(0, Number(input.customsIdr) || 0));
    const marginPercent = Number(input.marginPercent) || 0;

    const jlcPriceIdr = rupiah(jlcPriceUsd * usdRate);
    const jlcShippingIdr = rupiah(jlcShippingUsd * usdRate);

    const totalJlcUsd = Math.round((jlcPriceUsd + jlcShippingUsd) * 100) / 100;
    const totalJlcIdr = jlcPriceIdr + jlcShippingIdr;

    const hpp = totalJlcIdr + customsIdr;
    const profit = rupiah(hpp * (marginPercent / 100));

    return {
        usdRate,
        jlcPriceIdr,
        jlcShippingIdr,
        totalJlcUsd,
        totalJlcIdr,
        customsIdr,
        marginPercent,
        hpp,
        profit,
        finalPrice: hpp + profit,
    };
}

/**
 * Hidupkan setiap form rumus yang ada di halaman.
 *
 * Satu halaman boleh memuat beberapa form sekaligus — Detail Penawaran punya
 * satu per model SLA Industries — jadi tiap form dipasangi sendiri dan hanya
 * membaca elemen di dalam dirinya.
 */
export default function initSlaIndustriesFormulas(root = document) {
    root.querySelectorAll('[data-sla-formula]').forEach(bind);
}

function bind(form) {
    const field = (name) => form.querySelector(`[data-sla-input="${name}"]`);
    const output = (name) => form.querySelector(`[data-sla-output="${name}"]`);

    const inputs = {
        usdRate: field('usd_rate'),
        jlcPriceUsd: field('jlc_price_usd'),
        jlcShippingUsd: field('jlc_shipping_usd'),
        customsIdr: field('customs_idr'),
        marginPercent: field('margin_percent'),
    };

    const marginError = form.querySelector('[data-sla-margin-error]');
    const submit = form.querySelector('[data-sla-submit]');
    const rateOutput = output('usd_rate_idr');
    const rateStatus = form.querySelector('[data-sla-rate-status]');
    const rateRetry = form.querySelector('[data-sla-rate-retry]');

    /** Kurs belum ada sama sekali — bukan nol, melainkan tidak diketahui. */
    const rateMissing = () => inputs.usdRate === null || inputs.usdRate.value === '';

    const render = () => {
        const missing = rateMissing();

        const values = computeSlaIndustries({
            usdRate: inputs.usdRate?.value,
            jlcPriceUsd: inputs.jlcPriceUsd?.value,
            jlcShippingUsd: inputs.jlcShippingUsd?.value,
            customsIdr: inputs.customsIdr?.value,
            marginPercent: inputs.marginPercent?.value,
        });

        // Tanpa kurs, seluruh kolom rupiah tidak punya arti. Ditulis "-" alih-alih
        // Rp0, yang akan terbaca sebagai harga yang sudah pasti — dan gratis.
        const money = (value) => (missing ? '-' : idr(value));

        setText(rateOutput, missing ? 'Tidak tersedia' : idr(values.usdRate));
        setText(output('jlc_price_idr'), money(values.jlcPriceIdr));
        setText(output('jlc_shipping_idr'), money(values.jlcShippingIdr));
        setText(output('total_jlc_usd'), usd.format(values.totalJlcUsd));
        setText(output('total_jlc_idr'), money(values.totalJlcIdr));
        setText(output('hpp'), money(values.hpp));
        setText(output('profit'), money(values.profit));
        setText(output('final_price'), money(values.finalPrice));
        setText(output('margin_percent'), `${formatMargin(values.marginPercent)}%`);

        // Margin di luar 30%–50% ditolak server; diberitahukan di sini supaya
        // tidak perlu menunggu kiriman gagal untuk mengetahuinya.
        const margin = Number(inputs.marginPercent?.value);
        const marginValid =
            !inputs.marginPercent ||
            (Number.isFinite(margin) && margin >= MIN_MARGIN && margin <= MAX_MARGIN);

        if (marginError) {
            marginError.style.display = marginValid ? 'none' : 'block';
        }

        if (submit) {
            submit.disabled = !marginValid || missing;
        }
    };

    Object.values(inputs).forEach((input) => {
        input?.addEventListener('input', render);
        input?.addEventListener('change', render);
    });

    render();
    bindRate(form, { inputs, rateOutput, rateStatus, rateRetry, render });
}

/* ------------------------------------------------------------ kurs USD --- */

/**
 * Ambil kurs saat form dibuka, lalu segarkan berkala.
 *
 * Jedanya ditentukan SERVER lewat `data-sla-rate-refresh`, bukan ditetapkan di
 * sini: sumber kurs harian tidak berubah sepanjang hari, jadi menariknya tiap
 * beberapa menit hanya menghabiskan kuota penyedia. Penyegaran rapat baru
 * berarti pada kurs pasar intraday.
 *
 * Form kuotasi yang sudah tersimpan tidak membawa endpoint sama sekali —
 * kursnya beku dan tidak boleh ikut bergerak.
 */
function bindRate(form, ui) {
    const endpoint = form.dataset.slaRateEndpoint;

    if (!endpoint) {
        return;
    }

    const refreshSeconds = Number(form.dataset.slaRateRefresh) || 0;
    let fetching = false;

    const apply = (data) => {
        const rate = data?.rate ?? null;

        if (ui.inputs.usdRate) {
            ui.inputs.usdRate.value = rate === null ? '' : rate;
        }

        if (ui.rateStatus) {
            ui.rateStatus.innerHTML = statusHtml(data);
        }

        // Tombol "Coba Lagi" hanya muncul ketika memang ada yang perlu diulang.
        ui.rateRetry?.classList.toggle('hidden', !(rate === null || data?.stale));

        ui.render();
    };

    const load = async (force = false) => {
        if (fetching) {
            return;
        }

        fetching = true;

        if (force && ui.rateStatus) {
            ui.rateStatus.textContent = 'Mengambil kurs USD/IDR…';
        }

        try {
            const url = new URL(endpoint, window.location.origin);

            if (force) {
                url.searchParams.set('force', '1');
            }

            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            apply(await response.json());
        } catch (error) {
            // Jaringan browser yang putus tidak boleh menghapus kurs yang sudah
            // terpasang: yang sedang disusun admin tetap dapat diselesaikan.
            console.error(error);

            if (ui.rateStatus) {
                ui.rateStatus.innerHTML =
                    '<span class="font-semibold text-amber-700">Gagal menghubungi server untuk memperbarui kurs.</span>';
            }

            ui.rateRetry?.classList.remove('hidden');
        } finally {
            fetching = false;
        }
    };

    ui.rateRetry?.addEventListener('click', () => load(true));

    load();

    if (refreshSeconds > 0) {
        setInterval(() => load(), refreshSeconds * 1000);
    }
}

/** Keterangan sumber & waktu di bawah kurs, sepadan dengan yang dirender Blade. */
function statusHtml(data) {
    if (!data || data.rate === null || data.rate === undefined) {
        return (
            '<span class="font-semibold text-brand-700">Tidak dapat mengambil kurs terbaru.</span> ' +
            'Perhitungan ditahan sampai kursnya berhasil diambil.'
        );
    }

    const lines = [];

    if (data.stale) {
        lines.push(
            '<span class="block font-semibold text-amber-700">' +
                'Kurs terbaru gagal diperbarui. Memakai kurs terakhir yang tersimpan.' +
                '</span>'
        );
    }

    if (data.source) {
        lines.push(`<span class="block">Sumber: <span class="font-semibold text-ink-600">${escapeHtml(data.source)}</span></span>`);
    }

    const published = waktu(data.published_at);
    const fetched = waktu(data.fetched_at);

    if (published) {
        lines.push(`<span class="block">Terakhir diperbarui: ${published}</span>`);
    } else if (fetched) {
        lines.push(`<span class="block">Diambil: ${fetched}</span>`);
    }

    return lines.join('');
}

/** Stempel waktu penyedia datang dalam UTC; yang membacanya ada di Jakarta. */
function waktu(iso) {
    if (!iso) {
        return null;
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    // Disusun per bagian, bukan dengan satu format tanggal-waktu sekaligus:
    // locale id-ID menyisipkan kata "pukul" di tengahnya, sedangkan sisi PHP
    // menulis "16 September 2026, 07:02 WIB". Keduanya harus sama persis.
    const opsi = { timeZone: 'Asia/Jakarta' };
    const tanggal = new Intl.DateTimeFormat('id-ID', {
        ...opsi,
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
    const jam = new Intl.DateTimeFormat('id-ID', {
        ...opsi,
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).format(date);

    return `${tanggal}, ${jam.replace('.', ':')} WIB`;
}

function escapeHtml(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function setText(el, value) {
    if (el) {
        el.textContent = value;
    }
}

/** Margin ditulis tanpa desimal bila memang bulat, mis. "50%" bukan "50,00%". */
function formatMargin(value) {
    return Number.isInteger(value) ? String(value) : String(Math.round(value * 100) / 100).replace('.', ',');
}
