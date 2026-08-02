/**
 * Penyimpanan model 3D di dalam browser.
 *
 * Halaman 3D Models hanya menampilkan daftar model, sedangkan viewer 3D-nya
 * dibuka pada tab tersendiri. Tab baru tidak dapat mewarisi objek `File` dari
 * tab sebelumnya, jadi berkasnya dititipkan di IndexedDB — masih di perangkat
 * pengguna, masih tidak pernah menyentuh server sampai penawaran benar-benar
 * dikirim.
 *
 * IndexedDB dipilih karena sanggup menyimpan Blob berukuran besar apa adanya
 * (localStorage hanya menerima teks dan berbatas beberapa MB) dan datanya
 * dibagikan ke seluruh tab pada origin yang sama.
 *
 * Perubahan yang terjadi di satu tab disiarkan lewat BroadcastChannel supaya
 * daftar di tab lain ikut menyesuaikan tanpa perlu dimuat ulang.
 */

const DB_NAME = 'nusama3d';
const DB_VERSION = 1;
const STORE = 'models';
const CHANNEL = 'nusama3d-models';

let dbPromise = null;

function openDatabase() {
    if (dbPromise) {
        return dbPromise;
    }

    dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains(STORE)) {
                const store = db.createObjectStore(STORE, { keyPath: 'id' });
                // Urutan unggah dipertahankan sebagai nomor printer pada daftar.
                store.createIndex('position', 'position');
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error ?? new Error('IndexedDB tidak dapat dibuka'));
    });

    return dbPromise;
}

/** Jalankan satu transaksi lalu tunggu sampai benar-benar tersimpan. */
async function transaction(mode, handler) {
    const db = await openDatabase();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const store = tx.objectStore(STORE);
        let result;

        try {
            result = handler(store);
        } catch (error) {
            reject(error);

            return;
        }

        tx.oncomplete = () => resolve(result);
        tx.onerror = () => reject(tx.error ?? new Error('Transaksi IndexedDB gagal'));
        tx.onabort = () => reject(tx.error ?? new Error('Transaksi IndexedDB dibatalkan'));
    });
}

/** Bungkus IDBRequest menjadi Promise. */
function wrap(request) {
    return new Promise((resolve, reject) => {
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export const modelStore = {
    /** Seluruh model, urut sesuai urutan unggah. */
    async all() {
        const db = await openDatabase();

        const records = await wrap(db.transaction(STORE, 'readonly').objectStore(STORE).getAll());

        return records.sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
    },

    async find(id) {
        const db = await openDatabase();

        return wrap(db.transaction(STORE, 'readonly').objectStore(STORE).get(id));
    },

    async count() {
        const db = await openDatabase();

        return wrap(db.transaction(STORE, 'readonly').objectStore(STORE).count());
    },

    async put(record) {
        await transaction('readwrite', (store) => store.put(record));

        return record;
    },

    async remove(id) {
        await transaction('readwrite', (store) => store.delete(id));
    },

    async clear() {
        await transaction('readwrite', (store) => store.clear());
    },

    /** Rapatkan kembali nomor urut setelah ada model yang dihapus. */
    async reorder() {
        const records = await this.all();

        await transaction('readwrite', (store) => {
            records.forEach((record, index) => {
                store.put({ ...record, position: index + 1 });
            });
        });

        return records.map((record, index) => ({ ...record, position: index + 1 }));
    },
};

/**
 * Kabar perubahan antar tab.
 *
 * `postMessage` hanya sampai ke tab lain, tidak kembali ke pengirimnya — persis
 * yang dibutuhkan, karena tab pengirim sudah memperbarui tampilannya sendiri.
 */
export function createModelChannel(onMessage) {
    if (typeof BroadcastChannel === 'undefined') {
        return { post: () => {}, close: () => {} };
    }

    const channel = new BroadcastChannel(CHANNEL);

    if (onMessage) {
        channel.addEventListener('message', (event) => onMessage(event.data));
    }

    return {
        post: (message) => channel.postMessage(message),
        close: () => channel.close(),
    };
}

/** Id acak untuk satu model; cukup unik untuk dipakai sebagai kunci dan di URL. */
export function createModelId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `m-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}
