import { renderThumbnail } from './thumbnail';

/**
 * Bentuk baku satu model di dalam penyimpanan browser.
 *
 * Dipakai halaman daftar (saat berkas baru diunggah) maupun halaman viewer
 * (setiap kali pengaturannya berubah), sehingga daftar dan viewer tidak pernah
 * menampilkan angka yang berbeda untuk model yang sama.
 *
 * @param {import('./printer-card').default} card
 * @param {{id: string, position: number, name: string, size: number}} identity
 */
export function toRecord(card, { id, position, name, size }) {
    const payload = card.payload();
    const quantity = card.settings.quantity;

    // Objek File tidak ikut disimpan: berkasnya sudah tersimpan sebagai Blob.
    delete payload.file;

    return {
        id,
        position,
        name,
        size,
        format: card.format,
        thumbnail: renderThumbnail(card.pivot),
        state: card.snapshot(),
        payload,

        // Bahan mentah estimasi. Dengan angka-angka ini, mengubah spesifikasi
        // dari halaman daftar cukup menghitung ulang rumusnya — tidak perlu
        // membaca dan mem-parse berkas 3D-nya lagi.
        inputs: {
            geometryVolumeCm3: (card.metrics?.volumeMm3 ?? 0) / 1000,
            surfaceAreaCm2: (card.metrics?.surfaceAreaMm2 ?? 0) / 100,
            dimensions: card.dimensions ? { ...card.dimensions } : null,
            measuredSupportVolumeCm3: card.supportStats?.materialVolumeCm3 ?? null,
        },

        summary: {
            dimensions: card.dimensions ? { ...card.dimensions } : null,
            volumeCm3: card.estimate?.modelVolumeCm3 ?? 0,
            // Berat seluruh unit. Tidak pernah ditampilkan kepada pelanggan —
            // hanya dipakai sebagai dasar harga dan ikut dikirim ke server saat
            // penawaran dibuat, tempat admin memakainya untuk proses internal.
            weightG: (card.estimate?.totalWeightG ?? 0) * quantity,
            minutes: card.estimate?.totalMinutes ?? 0,
            // null berarti harganya memang belum ada (teknologi yang
            // ditetapkan tim), bukan nol — jangan dipaksa menjadi angka.
            cost: card.estimate?.totalCost ?? null,
            // Menentukan lead time: pekerjaan berharga Rumus Harga Manual
            // memakai rentang tetapnya sendiri, bukan tingkat menit mesin.
            manualPricing: card.estimate?.manualPricing === true,
            technology: card.settings.technology,
            material: card.settings.material,
            color: card.settings.color,
            finishing: card.settings.finishing,
            quantity,
            analysisStatus: card.analysis?.status ?? 'warning',
            fits: !card.fit?.exceeds,
        },
    };
}
