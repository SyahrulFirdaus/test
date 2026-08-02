/**
 * Estimasi support structure di sisi browser.
 *
 * Rumusnya sengaja dibuat identik dengan App\Services\SupportEstimator supaya
 * angka yang dilihat pengguna sama dengan yang dihitung ulang dan disimpan
 * server saat permintaan penawaran dikirim.
 *
 * Untuk saat ini volume support hanya disimulasikan sebagai proporsi volume
 * model, disesuaikan kelangsingan part. Tahap berikutnya — deteksi otomatis
 * kebutuhan support dan analisis sudut overhang dari mesh — cukup mengganti
 * isi estimateSupport() tanpa menyentuh rumus biaya dan waktu.
 */

const FALLBACK = { infill: 0.3, heightInfluence: 0.25, maxAspect: 3, defaultType: 'normal', types: {} };

/**
 * @param {object} technology entri config teknologi terpilih
 * @param {{density:number}} material material terpilih
 * @param {number} modelVolumeCm3 volume model
 * @param {{x:number,y:number,z:number}|null} dimensions dimensi pada orientasi sekarang
 * @param {{enabled?:boolean, type?:string, config?:object}} options
 */
export function estimateSupport(technology, material, modelVolumeCm3, dimensions, options = {}) {
    const cfg = { ...FALLBACK, ...(options.config ?? {}) };
    const required = isSupportRequired(technology);
    const enabled = Boolean(options.enabled) && required;

    const type = options.type ?? cfg.defaultType;
    const typeMultiplier = Number(cfg.types?.[type]?.multiplier ?? 1);
    const aspect = aspectMultiplier(dimensions, cfg);

    if (!enabled) {
        return {
            enabled: false,
            required,
            measured: false,
            grossVolumeCm3: 0,
            volumeCm3: 0,
            weightG: 0,
            aspectMultiplier: aspect,
        };
    }

    // Bila support sudah benar-benar dibentuk di viewer, volumenya diukur dari
    // geometri itu — jauh lebih tepat daripada rumus simulasi, dan membuat angka
    // estimasi konsisten dengan apa yang terlihat pengguna.
    //
    // Nol yang terukur tetap dihormati: bila pada orientasi ini memang tidak ada
    // overhang yang perlu ditopang, beratnya nol — bukan hasil rumus perkiraan.
    const measuredVolume = Number(options.measuredVolumeCm3 ?? NaN);
    const measured = Number.isFinite(measuredVolume) && measuredVolume >= 0;

    const factor = Number(technology?.supportFactor ?? 0);
    const grossVolume = measured
        ? measuredVolume / Number(cfg.infill)
        : Math.max(0, modelVolumeCm3) * factor * typeMultiplier * aspect;
    const volume = measured ? measuredVolume : grossVolume * Number(cfg.infill);

    return {
        enabled: true,
        required,
        measured,
        grossVolumeCm3: grossVolume,
        volumeCm3: volume,
        weightG: volume * Math.max(0, Number(material?.density ?? 0)),
        aspectMultiplier: aspect,
    };
}

export function isSupportRequired(technology) {
    return Number(technology?.supportFactor ?? 0) > 0;
}

export function supportNote(technology) {
    return isSupportRequired(technology) ? null : (technology?.supportNote ?? null);
}

/**
 * Part tinggi dan langsing butuh lebih banyak penopang daripada part pendek
 * dan lebar, jadi rasio tinggi terhadap tapak ikut diperhitungkan.
 */
function aspectMultiplier(dimensions, cfg) {
    const height = Number(dimensions?.y ?? 0);
    const footprint = Math.max(Number(dimensions?.x ?? 0), Number(dimensions?.z ?? 0));

    if (height <= 0 || footprint <= 0) {
        return 1;
    }

    return 1 + Math.min(height / footprint, Number(cfg.maxAspect)) * Number(cfg.heightInfluence);
}
