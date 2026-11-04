import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/impor-siswa.css',
                'resources/css/demo-preline.css',
                'resources/css/demo-mary.css',
                'resources/css/demo-flux.css',
                'resources/css/demo-wireui.css',
                'resources/css/demo-wirekit.css',
                'resources/css/demo-sheaf.css',
                'resources/css/proses-kelas.css',
                'resources/css/tagihan-siswa.css',
                'resources/js/app.js',
                'resources/js/demo-preline.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
