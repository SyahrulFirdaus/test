import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // Entry terpisah agar Three.js hanya dimuat di halaman yang memang
                // menggambar model 3D.
                'resources/js/model-viewer.js',
                // Halaman 3D Viewer satu model (/3d-models/{id}/viewer).
                'resources/js/model-preview.js',
                // Pratinjau 3D model penawaran di dashboard Admin/Superadmin.
                'resources/js/admin-model-viewer.js',
                // Animasi 3D pada panel brand halaman Masuk.
                'resources/js/auth-scene.js',
                // Sidebar, ikon lonceng, dan popup notifikasi dashboard.
                'resources/js/dashboard.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Bundle Three.js memang besar, tetapi hanya dimuat di halaman Cek Barang.
        chunkSizeWarningLimit: 700,
    },
});
