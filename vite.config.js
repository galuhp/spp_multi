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
                'resources/css/proses-kelas.css',
                'resources/css/tagihan-siswa.css',
                'resources/css/riwayat-pembayaran-siswa.css',
                'resources/css/manajemen-role.css',
                'resources/css/pembayaran-massal.css',
                'resources/css/laporan-pembayaran.css',
                'resources/css/pembayaran-form.css',
                'resources/css/sidebar.css',
                'resources/css/entri-pembayaran-kasir.css',
                'resources/js/app.js',
            ],
            refresh: true,
            fonts: [
                bunny('DM Sans', {
                    weights: [400, 500, 600, 700],
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
