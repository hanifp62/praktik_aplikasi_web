import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import inertia from '@inertiajs/vite';
import react from '@vitejs/plugin-react';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { fileURLToPath } from 'node:url';

/*
 * Satu pintu masuk: app.tsx (Inertia + React). resources/js/dasar.js memegang service
 * worker, tema, dan pemuat MapLibre serta perekam jejak yang dimuat malas. Blade yang
 * tersisa (halaman publik, galat, luring) hanya memuat app.css.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        inertia(),
        react(),
        wayfinder(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        rolldownOptions: {
            output: {
                // Komentar lisensi /*! ... */ ikut ke bundel produksi (HANDOVER bagian 0
                // butir 17): path ikon Phosphor (MIT) ditanam di kode dan MapLibre
                // (BSD-3-Clause) membawa pemberitahuannya sendiri; keduanya wajib ikut di
                // setiap salinan. Tanpa opsi ini minifier Vite 8 membuangnya. Komentar
                // anotasi dan JSDoc tetap dibuang.
                comments: { legal: true, annotation: false, jsdoc: false },
            },
        },
    },
});
