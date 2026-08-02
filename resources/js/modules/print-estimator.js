/**
 * Estimasi berat, waktu, dan biaya cetak di sisi browser.
 *
 * Rumus di sini sengaja dibuat identik dengan App\Services\PrintEstimator
 * supaya angka yang dilihat pengguna sama persis dengan yang dihitung ulang
 * dan disimpan server saat permintaan penawaran dikirim.
 *
 * Urutannya: skala → volume material (infill atau cangkang hollow) → support →
 * waktu (resolusi, pola infill, kecepatan printer) → rincian biaya.
 */

const currencyFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const numberFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });

/** Label komponen biaya, dipakai tabel rincian. */
export const COST_COMPONENTS = [
    ['material', 'Material'],
    ['machine_time', 'Waktu Printing'],
    ['support', 'Support Structure'],
    ['finishing', 'Finishing'],
    ['quality_control', 'Quality Control'],
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

    // --- 6. biaya -----------------------------------------------------------
    const machineRate = technology.machineRate * Math.max(0.1, Number(options.printer?.rateFactor ?? 1) || 1);
    const breakdown = costBreakdown(technology, material, options.cost ?? {}, {
        quantity: qty,
        modelWeightG: weight,
        supportWeightG: supportWeight,
        supportEnabled,
        totalHours,
        machineRate,
        surfaceAreaCm2: surfaceArea,
        finishingMultiplier: Math.max(0, Number(finishing?.costMultiplier ?? 1) || 0),
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

        unitMinutes: Math.max(1, Math.round(unitHours * 60)),
        // Waktu total mencakup pengerjaan finishing setelah part dicetak.
        totalMinutes: Math.max(1, Math.round((totalHours + finishingHours) * 60)),
        unitCost: roundCost(totalWeight * material.pricePerGram + unitHours * machineRate, options.cost),
        totalCost: breakdown.total,
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

/**
 * Rincian biaya menjadi lima komponen.
 *
 * Totalnya dihitung dari penjumlahan komponen — bukan sebaliknya — supaya
 * tabel rincian selalu berjumlah persis sama dengan total penawaran.
 */
function costBreakdown(technology, material, cost, context) {
    const qty = context.quantity;

    const materialCost = context.modelWeightG * material.pricePerGram * qty;

    // Biaya setup mesin ikut komponen waktu: sama-sama okupansi mesin.
    const timeCost = technology.setupFee + context.totalHours * context.machineRate;

    const supportCost = context.supportEnabled
        ? context.supportWeightG * material.pricePerGram * qty + Number(cost.supportRemovalFee ?? 0) * qty
        : 0;

    // Pembersihan dasar berlaku untuk setiap part; pilihan finishing tambahan
    // mengalikannya sesuai `finishing.options` di config.
    const finishingMinimum = Number(cost.finishing?.minimum ?? 0);
    const baseFinishing = context.surfaceAreaCm2 > 0
        ? Math.max(finishingMinimum, context.surfaceAreaCm2 * Number(cost.finishing?.ratePerCm2 ?? 0)) * qty
        : finishingMinimum * qty;

    const finishingCost = baseFinishing * Number(context.finishingMultiplier ?? 1);

    const subtotal = materialCost + timeCost + supportCost + finishingCost;

    const qualityCost = Math.max(
        Number(cost.qualityControl?.minimum ?? 0),
        subtotal * Number(cost.qualityControl?.percent ?? 0)
    );

    const components = {
        material: roundCost(materialCost, cost),
        machine_time: roundCost(timeCost, cost),
        support: roundCost(supportCost, cost),
        finishing: roundCost(finishingCost, cost),
        quality_control: roundCost(qualityCost, cost),
    };

    return {
        ...components,
        total: Object.values(components).reduce((sum, value) => sum + value, 0),
    };
}

/** Dibulatkan ke atas pada kelipatan yang diatur di config, sama seperti di server. */
function roundCost(value, cost = {}) {
    const step = Number(cost.rounding ?? 500) || 0;

    return step > 0 ? Math.ceil(value / step) * step : value;
}

function clamp(value, fallback, min, max) {
    const numeric = Number(value);

    return Number.isFinite(numeric) ? Math.min(max, Math.max(min, numeric)) : fallback;
}

export function formatCurrency(value) {
    return currencyFormatter.format(Math.round(value));
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
