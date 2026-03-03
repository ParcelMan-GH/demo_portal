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
                'resources/css/pages/driver-portal.css',
                'resources/css/pages/warehouse-portal.css',
                'resources/js/app.js',
                'resources/js/admin/app.js',
                'resources/js/warehouse/app.js',
                'resources/js/admin/modules/auth/login.js',
                'resources/js/web/firebase-push.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        port: 5400,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
