import { estimate as calculateEstimate } from './print-estimator';
import { estimateSupport } from './support-estimator';

/**
 * Perubahan spesifikasi satu model dari halaman daftar.
 *
 * Mengubah teknologi, material, warna, finishing, atau jumlah tidak menyentuh
 * geometri sama sekali — yang berubah hanya angka. Karena itu perhitungannya
 * dijalankan langsung dari `record.inputs` (volume geometri, luas permukaan,
 * dimensi, dan volume support terukur) tanpa perlu membaca dan mem-parse berkas
 * 3D-nya lagi. Hasilnya terasa seketika, bahkan untuk model berukuran besar.
 *
 * Rumus yang dipakai sama persis dengan yang dijalankan viewer 3D maupun server,
 * jadi angka pada daftar, viewer, dan penawaran tidak pernah berbeda.
 */

/** Selisih yang masih dianggap sama saat membandingkan ukuran, dalam mm. */
const SIZE_TOLERANCE_MM = 0.01;

/** Teknologi yang tersedia beserta labelnya, mis. "FDM (Plastic)". */
export function technologyOptions(config) {
    return Object.entries(config.technologies ?? {}).map(([code, technology]) => ({
        code,
        label: technology.label ?? code,
        name: technology.name,
        description: technology.description ?? null,
    }));
}

/** Material yang tersedia untuk satu teknologi. */
export function materialOptions(config, technology) {
    return (config.technologies?.[technology]?.materials ?? []).map((material) => material.name);
}

/** Katalog lengkap satu teknologi: material beserta keterangan dan batas ukurannya. */
export function materialCatalog(config, technology) {
    return config.technologies?.[technology]?.materials ?? [];
}

/** Keterangan satu material dari katalog, atau null bila tidak dikenal. */
export function materialInfo(config, technology, material) {
    return materialCatalog(config, technology).find((item) => item.name === material) ?? null;
}

/**
 * Nama material yang dilihat pelanggan, mis. "PLA+".
 *
 * `name` tetap nama katalog beserta brand-nya — itulah yang dikirim ke server
 * dan menentukan harga. Yang ditampilkan hanya `label`, disiapkan server lewat
 * App\Support\MaterialCatalog. Material tanpa label ditampilkan apa adanya.
 */
export function materialLabel(material) {
    return material?.label ?? material?.name ?? '-';
}

/** Nama tampilan sebuah material menurut katalog teknologinya. */
export function materialLabelFor(config, technology, material) {
    return materialLabel(materialInfo(config, technology, material) ?? { name: material });
}

/** Tiga sisi diurutkan dari yang terpanjang, supaya perbandingan tidak bergantung orientasi. */
function sidesOf(size) {
    return [Number(size?.x ?? 0), Number(size?.y ?? 0), Number(size?.z ?? 0)].sort((a, b) => b - a);
}

/**
 * Batas yang benar-benar dapat dibandingkan: ketiga sisinya ada dan positif.
 *
 * Batas maksimum material diturunkan dari mesinnya, jadi material yang belum
 * ditentukan mesinnya tidak memilikinya. Itu bukan pelanggaran — model apa pun
 * tetap boleh lewat. Tanpa penjaga ini, batas kosong terbaca sebagai 0 mm dan
 * setiap model akan ditolak.
 */
function isLimit(size) {
    return !!size && ['x', 'y', 'z'].every((axis) => Number.isFinite(Number(size[axis])) && Number(size[axis]) > 0);
}

/**
 * Periksa ukuran model terhadap batas material yang dipilih.
 *
 * Perbandingan dilakukan sisi-terpanjang-lawan-sisi-terpanjang sehingga model
 * yang sebenarnya muat setelah diputar tidak ikut ditolak. Batas minimum boleh
 * dipenuhi lewat `minSize` maupun `minSizeSlender` — yang kedua mengakomodasi
 * part memanjang seperti batang atau pin.
 *
 * @returns {null|{type: 'min'|'max', limit: {x: number, y: number, z: number}}}
 */
export function validateModelSize(dimensions, material) {
    if (!dimensions || !material) {
        return null;
    }

    const model = sidesOf(dimensions);

    if (isLimit(material.maxSize)) {
        const max = sidesOf(material.maxSize);

        if (model.some((side, index) => side > max[index] + SIZE_TOLERANCE_MM)) {
            return { type: 'max', limit: material.maxSize };
        }
    }

    const minimums = [material.minSize, material.minSizeSlender].filter(isLimit);

    if (minimums.length) {
        const fits = minimums.some((minimum) => {
            const min = sidesOf(minimum);

            return model.every((side, index) => side + SIZE_TOLERANCE_MM >= min[index]);
        });

        if (!fits) {
            return { type: 'min', limit: material.minSize ?? minimums[0] };
        }
    }

    return null;
}

/**
 * Warna yang tersedia untuk satu material.
 * Resin bening hanya tersedia bening, part logam hanya warna aslinya.
 */
export function colorOptions(config, technology, material) {
    const entry = (config.technologies?.[technology]?.materials ?? []).find((item) => item.name === material);
    const allowed = entry?.colors ?? [];
    const all = config.materialColors?.options ?? {};

    const keys = allowed.length ? allowed.filter((key) => key in all) : Object.keys(all);

    return keys.map((key) => ({ key, ...all[key] }));
}

/**
 * Pilihan finishing untuk satu material.
 *
 * Daftarnya milik material — diisi Superadmin pada form Tambah/Ubah Material —
 * sehingga resin bening boleh tidak menawarkan painting sama sekali. Material
 * yang daftarnya masih kosong menerima seluruh pilihan.
 */
export function finishingOptions(config, technology, material) {
    const all = config.finishing?.options ?? {};
    const entry = (config.technologies?.[technology]?.materials ?? []).find((item) => item.name === material);
    const allowed = (entry?.finishings ?? []).filter((key) => key in all);
    const keys = allowed.length ? allowed : Object.keys(all);

    return keys.map((key) => ({ key, ...all[key] }));
}

/** Finishing yang menuntut pelanggan memilih warna cat. */
export function finishingNeedsColor(config, finishing) {
    return Boolean(config.finishing?.options?.[finishing]?.needsColor);
}

/**
 * Bahan mentah estimasi milik satu model.
 *
 * Model yang tersimpan sebelum `inputs` ada tetap dapat dihitung ulang: seluruh
 * angka yang dibutuhkan juga tercatat di dalam payload penawarannya.
 */
export function resolveInputs(record) {
    const inputs = record.inputs ?? {};
    const payload = record.payload ?? {};
    const stats = payload.model_stats ?? {};

    const geometryVolumeCm3 = Number(
        inputs.geometryVolumeCm3 ?? stats.volume_cm3 ?? payload.model_volume_cm3 ?? 0
    );

    const surfaceAreaCm2 = Number(inputs.surfaceAreaCm2 ?? stats.surface_area_cm2 ?? 0);

    const dimensions = inputs.dimensions ?? stats.dimensions ?? null;

    const measured = inputs.measuredSupportVolumeCm3 ?? payload.support_volume_cm3 ?? null;

    return {
        geometryVolumeCm3: Number.isFinite(geometryVolumeCm3) ? geometryVolumeCm3 : 0,
        surfaceAreaCm2: Number.isFinite(surfaceAreaCm2) ? surfaceAreaCm2 : 0,
        dimensions,
        measuredSupportVolumeCm3: measured === null ? null : Number(measured),
    };
}

/** Spesifikasi yang sedang berlaku pada satu model. */
export function specificationOf(record) {
    const settings = record.state?.settings ?? {};

    return {
        technology: settings.technology ?? record.summary?.technology,
        material: settings.material ?? record.summary?.material,
        color: settings.color ?? record.summary?.color,
        finishing: settings.finishing ?? record.summary?.finishing ?? 'none',
        quantity: Math.max(1, Number(settings.quantity ?? record.summary?.quantity ?? 1)),
        support: Boolean(settings.support),
        hollow: {
            enabled: Boolean(settings.hollow?.enabled),
            wallThicknessMm: Number(settings.hollow?.wallThicknessMm ?? 2),
            drainDiameterMm: Number(settings.hollow?.drainDiameterMm ?? 3.5),
            drainPosition: settings.hollow?.drainPosition ?? 'bottom',
        },
    };
}

/**
 * Apakah teknologi ini benar-benar membutuhkan support (MJF tidak).
 *
 * Sejak pilihannya dihapus dari Edit Specification, inilah yang menentukan
 * support menyala atau tidak — bukan lagi pelanggan. Aturan yang sama
 * diberlakukan server di App\Services\PrintEstimator::estimate().
 */
export function allowsSupport(config, technology) {
    return Number(config.technologies?.[technology]?.supportFactor ?? 0) > 0;
}

/**
 * Terapkan spesifikasi baru pada satu model.
 *
 * Mengembalikan salinan record dengan payload, ringkasan, dan pengaturannya
 * sudah diperbarui — record aslinya tidak diubah. Model lain sama sekali tidak
 * tersentuh karena perhitungannya hanya memakai data milik record ini.
 *
 * @returns {object|null} record baru, atau null bila spesifikasinya tidak dikenal
 */
export function applySpecification(record, config, spec) {
    const technology = config.technologies?.[spec.technology];

    if (!technology) {
        return null;
    }

    const material = (technology.materials ?? []).find((item) => item.name === spec.material);

    if (!material) {
        return null;
    }

    const settings = record.state?.settings ?? {};
    const inputs = resolveInputs(record);
    const quantity = Math.max(1, Number(spec.quantity) || 1);

    // Warna disesuaikan dengan materialnya; bila yang dipilih tidak tersedia,
    // warna pertama yang memang ada yang dipakai.
    const colors = colorOptions(config, spec.technology, spec.material).map((color) => color.key);
    const color = colors.includes(spec.color) ? spec.color : (colors[0] ?? spec.color);

    // Finishing disesuaikan dengan materialnya: yang dipilih harus benar-benar
    // ditawarkan material itu, kalau tidak jatuh ke pilihan pertama yang ada.
    const finishings = finishingOptions(config, spec.technology, spec.material).map((option) => option.key);
    const finishing = finishings.includes(spec.finishing)
        ? spec.finishing
        : (finishings.includes(config.finishing?.default) ? config.finishing.default : (finishings[0] ?? 'none'));

    const scale = Number(settings.scale ?? 1) || 1;
    const geometryVolumeCm3 = inputs.geometryVolumeCm3;

    // Infill milik teknologi sebelumnya tidak boleh terbawa: SLA/MJF/SLM
    // memakai 100%, sehingga model yang dipindah ke FDM akan dihitung padat
    // dan harganya melonjak. Sama seperti viewer, berganti teknologi berarti
    // kembali ke infill bawaan teknologi barunya.
    const infillDensity = spec.technology === settings.technology && settings.infillDensity !== undefined
        ? settings.infillDensity
        : Number(technology.defaultInfill ?? 1);

    // Support tidak lagi dipilih pelanggan: teknologinya yang menentukan, dan
    // MJF tetap tanpa support karena part-nya tertopang serbuk. Hollow Model
    // sudah tidak ditawarkan sama sekali, jadi part selalu dihitung padat.
    // Keduanya ditetapkan sama persis di App\Services\Pricing\PricingInput,
    // yang menghitung ulang harga saat penawarannya disimpan.
    const supportEnabled = allowsSupport(config, spec.technology);
    const hollow = {
        ...(settings.hollow ?? {}),
        ...(spec.hollow ?? {}),
        enabled: false,
    };

    // Berat support ikut berubah bila materialnya berganti — volumenya tetap,
    // densitasnya yang berbeda.
    const support = estimateSupport(
        technology,
        material,
        geometryVolumeCm3 * scale ** 3,
        inputs.dimensions ?? null,
        {
            enabled: supportEnabled,
            config: config.support,
            measuredVolumeCm3: inputs.measuredSupportVolumeCm3 ?? undefined,
        }
    );

    const estimate = calculateEstimate(
        technology,
        material,
        geometryVolumeCm3,
        quantity,
        support,
        config.resolutions?.[settings.resolution] ?? null,
        {
            scale,
            surfaceAreaCm2: Number(inputs.surfaceAreaCm2 ?? 0),
            // Sudah terskalakan sejak diukur di viewer; dipakai Basic Fee.
            dimensions: inputs.dimensions ?? null,
            infillDensity,
            infillPattern: settings.infillPattern,
            patterns: config.infill?.patterns,
            hollow: { ...hollow, drainHoles: config.hollow?.drainCount ?? 2 },
            printer: config.printers?.[record.state?.printer] ?? null,
            printerKey: record.state?.printer ?? null,
            finishing,
            finishings: config.finishing?.options,

            // Kecepatan pengerjaan milik PESANAN, bukan satu model, jadi
            // dititipkan pada config yang dipakai bersama seluruh model —
            // mengubahnya lalu menghitung ulang membuat tambahan Express
            // masuk ke setiap model sekaligus, sama seperti di server.
            productionSpeed: config.productionSpeed ?? 'standard',
            leadTime: config.leadTime,

            cost: config.cost,
            pricing: config.pricing,
        }
    );

    if (!estimate) {
        return null;
    }

    return {
        ...record,

        state: {
            ...record.state,
            settings: {
                ...settings,
                technology: spec.technology,
                material: spec.material,
                color,
                finishing,
                quantity,
                support: supportEnabled,
                hollow,
                infillDensity,
            },
        },

        payload: {
            ...record.payload,
            technology: spec.technology,
            material: spec.material,
            material_color: color,
            finishing,
            quantity,
            support_enabled: support.enabled,
            support_volume_cm3: support.enabled ? support.volumeCm3 : null,
            hollow_enabled: estimate.hollowEnabled,
            hollow_wall_thickness_mm: estimate.hollowWallThicknessMm,
            estimate: {
                totalWeightG: estimate.totalWeightG,
                totalMinutes: estimate.totalMinutes,
                totalCost: estimate.totalCost,
                manualPricing: estimate.manualPricing,
                breakdown: estimate.breakdown,
            },
        },

        summary: {
            ...record.summary,
            volumeCm3: estimate.modelVolumeCm3,
            weightG: estimate.totalWeightG * quantity,
            minutes: estimate.totalMinutes,
            cost: estimate.totalCost,
            manualPricing: estimate.manualPricing,
            technology: spec.technology,
            material: spec.material,
            color,
            finishing,
            quantity,
            support: support.enabled,
            hollow: estimate.hollowEnabled,
        },
    };
}
