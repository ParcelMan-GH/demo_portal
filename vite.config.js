import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/pages/api-tester.css',
                'resources/css/pages/vendor-portal.css',
                'resources/css/pages/warehouse-portal.css',
                'resources/js/app.js',
                'resources/js/admin/app.js',
                'resources/js/warehouse/app.js',
                'resources/js/admin/modules/auth/login.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
