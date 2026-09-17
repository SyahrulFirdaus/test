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
                'resources/js/model-detail.js',
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
