/**
 * Estimasi berat, waktu, dan harga cetak di sisi browser.
 *
 * Rumus di sini sengaja dibuat identik dengan App\Services\PrintEstimator
 * supaya angka yang dilihat pengguna sama persis dengan yang dihitung ulang
 * dan disimpan server saat permintaan penawaran dikirim.
 *
 * Urutannya: skala → volume material (infill atau cangkang hollow) → support →
 * waktu (resolusi, pola infill, kecepatan printer) → Harga Jual.
 *
 * Harganya sendiri bukan milik berkas ini: penetapannya ada di ./selling-price.js,
 * cermin dari App\Services\SellingPriceEstimator.
 */

import { sellingPrice } from './selling-price.js';

const currencyFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const numberFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });

/** Label komponen Harga Jual, dipakai tabel rincian. */
export const COST_COMPONENTS = [
    ['material_cost', 'Material'],
    ['machine_operational_cost', 'Operasional Mesin'],
    ['hpp', 'HPP'],
    ['risk_cost', 'Risk Cost'],
    ['packaging', 'Packaging'],
    ['overtime', 'Overtime'],
    ['subtotal', 'Subtotal'],
    ['profit', 'Profit'],
    ['basic_fee', 'Basic Fee'],
];

/**
 * @param {object} technology entri config teknologi terpilih
 * @param {{density:number, pricePerGram:number}} material material terpilih
 * @param {number} geometryVolumeCm3 volume geometri asli (belum diskalakan)
 * @param {number} quantity jumlah cetak
 * @param {{enabled?:boolean, volumeCm3?:number, weightG?:number}} support
 * @param {{timeMultiplier?:number, materialMultiplier?:number}|null} resolution
 * @param {{
 *   scale?:number, surfaceAreaCm2?:number,
 *   infillDensity?:number, infillPattern?:string, patterns?:object,
 *   hollow?:{enabled?:boolean, wallThicknessMm?:number, drainDiameterMm?:number, drainHoles?:number},
 *   printer?:{speedFactor?:number, rateFactor?:number},
 *   finishing?:string, finishings?:object,
 *   cost?:object
 * }} options
 */
export function estimate(technology, material, geometryVolumeCm3, quantity = 1, support = null, resolution = null, options = {}) {
    if (!technology || !material) {
        return null;
    }

    const qty = Math.max(1, Number(quantity) || 1);

    // --- 1. skala -----------------------------------------------------------
    const scale = clamp(options.scale, 1, 0.05, 10);
    const solidVolume = Math.max(0, Number(geometryVolumeCm3) || 0) * scale ** 3;
    const surfaceArea = Math.max(0, Number(options.surfaceAreaCm2) || 0) * scale ** 2;

    // Resolusi memengaruhi jumlah lapisan yang harus dicetak, jadi waktu ikut
    // berubah; volume bahan hanya bergeser sedikit.
    const timeMultiplier = Number(resolution?.timeMultiplier ?? 1) || 1;
    const materialMultiplier = Number(resolution?.materialMultiplier ?? 1) || 1;

    // --- 2. volume material -------------------------------------------------
    const density = clamp(options.infillDensity, Number(technology.defaultInfill ?? 1), 0, 1);
    const pattern = options.patterns?.[options.infillPattern] ?? null;
    const hollow = resolveHollow(technology, options.hollow, solidVolume, surfaceArea);

    let fillFactor;
    let materialVolume;

    if (hollow.enabled) {
        // Part yang dikosongkan hanya menyisakan cangkang, jadi infill tidak
        // lagi berperan — volumenya ditentukan tebal dinding.
        fillFactor = solidVolume > 0 ? hollow.volumeCm3 / solidVolume : 0;
        materialVolume = hollow.volumeCm3 * materialMultiplier;
    } else {
        fillFactor = fillFactorFor(technology, density, pattern);
        materialVolume = solidVolume * fillFactor * materialMultiplier;
    }

    const weight = materialVolume * material.density;

    // --- 3. support ---------------------------------------------------------
    const supportVolume = Math.max(0, Number(support?.volumeCm3 ?? 0));
    const supportWeight = Math.max(0, Number(support?.weightG ?? 0));
    const supportEnabled = Boolean(support?.enabled);

    const totalVolume = materialVolume + supportVolume;
    const totalWeight = weight + supportWeight;

    // --- 4. waktu -----------------------------------------------------------
    const speedFactor = Math.max(0.1, Number(options.printer?.speedFactor ?? 1) || 1);
    const patternTime = 1 + ((Number(pattern?.timeMultiplier ?? 1) || 1) - 1) * density;
    const throughput = technology.throughput * speedFactor;

    const unitHours = throughput > 0
        ? (totalVolume / throughput) * timeMultiplier * (hollow.enabled ? 1 : patternTime)
        : 0;
    const totalHours = technology.setupHours + unitHours * qty;

    // --- 5. finishing -------------------------------------------------------
    // Pengerjaan setelah cetak tidak memakai mesin, jadi waktunya berdiri
    // sendiri dan tidak ikut dikalikan tarif mesin.
    const finishing = options.finishings?.[options.finishing] ?? null;
    const finishingHours = Math.max(0, Number(finishing?.hoursPerUnit ?? 0) || 0) * qty;

    // --- 6. harga -----------------------------------------------------------
    // Harga penawaran ditetapkan rumus Harga Jual Price List, bukan rincian
    // biaya lama — lihat ./selling-price.js dan App\Services\SellingPriceEstimator.
    const totalMinutes = Math.max(1, Math.round((totalHours + finishingHours) * 60));
    const breakdown = sellingPrice({
        technology: technology.code,
        // Metode harga ditentukan per material (SLA: Kalkulator Otomatis/Manual).
        manualPricing: material.manualPricing === true || technology.manualPricing === true,
        materialPricePerGram: material.pricePerGram,
        printerKey: options.printerKey ?? null,
        quantity: qty,
        totalWeightG: totalWeight,
        minutes: totalMinutes,
        dimensions: options.dimensions ?? null,
        pricing: options.pricing ?? {},
        cost: options.cost ?? {},

        // Finishing dan kecepatan produksi menambah komponen harganya sendiri
        // di atas harga printing — lihat App\Services\SellingPriceEstimator.
        finishing: options.finishing ?? null,
        finishingOption: finishing,
        productionSpeed: options.productionSpeed ?? null,
        leadTime: options.leadTime ?? leadTimeConfig,
    });

    return {
        scale,
        modelVolumeCm3: solidVolume,
        surfaceAreaCm2: surfaceArea,

        infillDensity: density,
        fillFactor,

        hollowEnabled: hollow.enabled,
        hollowWallThicknessMm: hollow.wallThicknessMm,
        hollowSavedCm3: hollow.enabled ? solidVolume - hollow.volumeCm3 : 0,

        materialVolumeCm3: materialVolume,
        weightG: weight,

        supportEnabled,
        supportVolumeCm3: supportVolume,
        supportWeightG: supportWeight,

        totalMaterialVolumeCm3: totalVolume,
        totalWeightG: totalWeight,

        finishing: options.finishing ?? null,
        finishingMinutes: Math.round(finishingHours * 60),

        largestDimensionMm: breakdown.largest_dimension_mm,
        basicFee: breakdown.basic_fee,
        basicFeeLabel: breakdown.basic_fee_label,

        unitMinutes: Math.max(1, Math.round(unitHours * 60)),
        // Waktu total mencakup pengerjaan finishing setelah part dicetak.
        totalMinutes,

        // Harga Estimasi yang dilihat pelanggan adalah Harga Jual itu sendiri,
        // bukan angka lain yang dihitung terpisah.
        //
        // Bernilai null pada teknologi yang harganya ditetapkan tim; seluruh
        // penampilnya lewat formatCurrency(), yang menulis "Menunggu
        // Perhitungan" untuk nilai seperti itu.
        totalCost: breakdown.selling_price,
        manualPricing: breakdown.manual_pricing === true,
        breakdown,
    };
}

/**
 * Bagian volume part yang benar-benar terisi material.
 * shellRatio + (1 - shellRatio) x kepadatan x pengali_pola
 */
function fillFactorFor(technology, density, pattern) {
    const shell = clamp(technology.shellRatio, 1, 0, 1);
    const multiplier = Number(pattern?.materialMultiplier ?? 1) || 1;

    return Math.min(1, shell + (1 - shell) * density * multiplier);
}

/**
 * Perkiraan volume part yang dikosongkan.
 *
 * Cangkang setebal `t` pada permukaan seluas `A` menyisakan material sebanyak
 * A x t, dikurangi lubang pembuangan yang dibor menembusnya. Hasilnya tidak
 * pernah melebihi volume padat aslinya.
 */
function resolveHollow(technology, options, solidVolumeCm3, surfaceAreaCm2) {
    const enabled = Boolean(options?.enabled)
        && Boolean(technology.allowsHollow)
        && surfaceAreaCm2 > 0
        && solidVolumeCm3 > 0;

    const thickness = clamp(options?.wallThicknessMm, 2, 0.1, 20);

    if (!enabled) {
        return { enabled: false, volumeCm3: solidVolumeCm3, wallThicknessMm: null };
    }

    // 1 cm2 x 1 mm = 0,1 cm3, jadi hasilnya dibagi 10.
    const shell = (surfaceAreaCm2 * thickness) / 10;

    const holes = Math.max(0, Number(options?.drainHoles ?? 2) || 0);
    const diameter = clamp(options?.drainDiameterMm, 3.5, 0.1, 50);
    const drainVolume = (holes * Math.PI * (diameter / 2) ** 2 * thickness) / 1000;

    return {
        enabled: true,
        volumeCm3: Math.max(0, Math.min(solidVolumeCm3, shell - drainVolume)),
        wallThicknessMm: thickness,
    };
}


function clamp(value, fallback, min, max) {
    const numeric = Number(value);

    return Number.isFinite(numeric) ? Math.min(max, Math.max(min, numeric)) : fallback;
}

/**
 * Keterangan pengganti harga yang memang belum ada.
 *
 * Dipakai teknologi yang harganya ditetapkan tim setelah penawaran masuk
 * (SLA Industries). Sengaja BUKAN "Rp0": angka nol terbaca sebagai harga yang
 * sudah pasti, dan gratis.
 */
export const PENDING_PRICE_LABEL = 'Harga Perlu Dicek Terlebih Dahulu';

/** Keterangan lengkapnya, untuk tempat yang cukup memuat satu kalimat penjelas. */
export const PENDING_PRICE_NOTE =
    'Desain Anda perlu diperiksa terlebih dahulu sebelum harga dapat ditentukan. '
    + 'Tim NUSAMA3D akan memberikan penawaran harga setelah dilakukan pengecekan.';

/**
 * Rupiah siap tampil.
 *
 * Nilai null/undefined — dan NaN yang lahir dari penjumlahan yang memuat null —
 * berarti harganya belum ada, bukan nol, jadi yang keluar keterangannya.
 */
export function formatCurrency(value) {
    if (value === null || value === undefined || !Number.isFinite(Number(value))) {
        return PENDING_PRICE_LABEL;
    }

    return currencyFormatter.format(Math.round(value));
}

/**
 * Jumlahkan harga beberapa model.
 *
 * Satu harga yang belum ada membuat TOTALNYA belum ada juga — menjumlahkan
 * sisanya akan menghasilkan angka yang terbaca sebagai harga penuh padahal
 * belum lengkap.
 *
 * @param {Array<number|null|undefined>} values
 * @returns {number|null}
 */
export function sumPrices(values) {
    let total = 0;

    for (const value of values) {
        if (value === null || value === undefined || !Number.isFinite(Number(value))) {
            return null;
        }

        total += Number(value);
    }

    return total;
}

export function formatNumber(value, digits = 2) {
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    }).format(value);
}

export function formatCount(value) {
    return numberFormatter.format(value);
}

export function formatPercent(value, digits = 0) {
    return `${formatNumber(value * 100, digits)}%`;
}

/**
 * Kecepatan pengerjaan beserta rentang hari kerjanya.
 *
 * Kecepatannya DIPILIH pelanggan, tidak lagi disimpulkan dari jam mesin.
 * Aturannya dikirim server lewat `printingConfig.leadTime`
 * (App\Support\LeadTime) agar pilihan yang tampil di browser tidak pernah
 * berbeda dengan yang diperiksa ulang server; nilai di bawah hanya cadangan
 * bila konfigurasinya belum sempat dimuat.
 */
let leadTimeConfig = {
    unit: 'Hari Kerja',
    default: 'standard',
    standard: { key: 'standard', name: 'Standard', minDays: 5, maxDays: 7 },
    express: {
        key: 'express',
        name: 'Express',
        minDays: 1,
        maxDays: 2,
        maxParts: 1,
        // 18 jam = 1.080 menit.
        belowMinutes: 1080,
        surchargePercent: 25,
    },
    // Pekerjaan berharga Rumus Harga Manual menunggu kuotasi vendor lebih
    // dahulu, jadi rentangnya tetap dan Express tidak berlaku.
    manual: { name: 'Standard', minDays: 5, maxDays: 7 },
    unavailableNote: 'Express is unavailable for this order. Please select Standard Production (5–7 working days).',
};

export const STANDARD_SPEED = 'standard';

export const EXPRESS_SPEED = 'express';

export function configureLeadTime(config) {
    if (config?.standard && config?.express) {
        leadTimeConfig = { ...leadTimeConfig, ...config };
    }
}

/**
 * Alasan Express tidak tersedia, dalam kalimat yang menjelaskan.
 *
 * Pelanggan perlu tahu MENGAPA pilihannya tinggal Standard, bukan sekadar
 * diberi tahu bahwa Express tidak ada. Syaratnya dua dan keduanya bisa menjadi
 * penyebabnya sekaligus, jadi alasannya disusun dari yang benar-benar berlaku.
 */
export function expressUnavailableReason(partCount, minutes, manualPricing = false) {
    const { standard, express, unit } = leadTimeConfig;
    const rentang = `${standard.name} (${standard.minDays}–${standard.maxDays} ${unit})`;

    if (manualPricing) {
        return `Material yang Anda pilih perlu dicek tim kami lebih dahulu, jadi pengerjaannya mengikuti ${rentang}.`;
    }

    const maxParts = Number(express.maxParts ?? 1);
    const batasMenit = Number(express.belowMinutes ?? 1080);
    const alasan = [];

    if (Number(partCount) > maxParts) {
        alasan.push(`pesanan Anda berisi ${partCount} model sedangkan Express hanya untuk ${maxParts} model`);
    }

    if (Math.max(0, Number(minutes) || 0) >= batasMenit) {
        alasan.push(
            `perkiraan waktu mesinnya ${formatDuration(minutes)} sedangkan Express hanya untuk pengerjaan di bawah ${batasMenit / 60} jam`
        );
    }

    return alasan.length ? `Pengerjaan mengikuti ${rentang} karena ${alasan.join(' dan ')}.` : '';
}

/**
 * Express tersedia untuk pesanan ini.
 *
 * KEDUA syaratnya harus terpenuhi sekaligus: pesanannya berisi tidak lebih dari
 * `maxParts` part DAN total waktu mesinnya DI BAWAH `belowMinutes` — 17 jam 59
 * menit masih boleh, 18 jam tepat tidak. Sama persis dengan
 * App\Support\LeadTime::expressAvailable().
 */
export function expressAvailable(partCount, minutes, manualPricing = false) {
    if (manualPricing || Number(partCount) < 1) {
        return false;
    }

    return Number(partCount) <= Number(leadTimeConfig.express.maxParts ?? 1)
        && Math.max(0, Number(minutes) || 0) < Number(leadTimeConfig.express.belowMinutes ?? 1080);
}

/** Kecepatan yang benar-benar berlaku; Express yang tak memenuhi syarat turun ke Standard. */
export function resolveSpeed(speed, partCount, minutes, manualPricing = false) {
    return speed === EXPRESS_SPEED && expressAvailable(partCount, minutes, manualPricing)
        ? EXPRESS_SPEED
        : STANDARD_SPEED;
}

/** Tambahan harga printing Express, dalam persen. */
export function expressSurchargePercent() {
    return Math.max(0, Number(leadTimeConfig.express.surchargePercent ?? 0) || 0);
}

function leadTimeTier(speed, manualPricing = false) {
    if (manualPricing) {
        return leadTimeConfig.manual;
    }

    return speed === EXPRESS_SPEED ? leadTimeConfig.express : leadTimeConfig.standard;
}

/**
 * Penanda Rumus Harga Manual pada estimasi yang sudah tersimpan.
 *
 * Record yang dibuat sebelum penanda ini ada hanya menyimpannya di dalam
 * rincian harga, jadi keduanya dibaca — model yang sudah lebih dulu ada di
 * browser pelanggan tetap memperoleh lead time yang benar.
 */
export function isManualEstimate(estimate) {
    return estimate?.manualPricing === true || estimate?.breakdown?.manual_pricing === true;
}

/** @returns {{min:number, max:number}} rentang hari kerja sebuah kecepatan */
export function leadTimeDays(speed, manualPricing = false) {
    const tier = leadTimeTier(speed, manualPricing);

    return { min: tier.minDays, max: tier.maxDays };
}

/**
 * Label siap tampil, mis. "Express (1–2 Hari Kerja)".
 *
 * Jam mesin tetap dipakai sebagai data perhitungan, tetapi yang ditampilkan
 * kepada pelanggan hanya nama kecepatan beserta rentang hari kerjanya.
 *
 * `manualPricing` menandai pekerjaan yang harganya dihitung dengan Rumus Harga
 * Manual; rentangnya tetap dan Express tidak berlaku.
 */
export function formatLeadTime(speed, manualPricing = false) {
    const tier = leadTimeTier(speed, manualPricing);
    const range = `${tier.minDays === tier.maxDays ? tier.minDays : `${tier.minDays}–${tier.maxDays}`} ${leadTimeConfig.unit}`;

    return tier.name ? `${tier.name} (${range})` : range;
}

export function formatDuration(minutes) {
    const total = Math.max(1, Math.round(minutes));
    const days = Math.floor(total / 1440);
    const hours = Math.floor((total % 1440) / 60);
    const mins = total % 60;

    const parts = [];

    if (days > 0) {
        parts.push(`${days} hari`);
    }

    if (hours > 0) {
        parts.push(`${hours} jam`);
    }

    if (mins > 0 || parts.length === 0) {
        parts.push(`${mins} menit`);
    }

    return parts.join(' ');
}
