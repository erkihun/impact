import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    // An IPv4 host keeps the dev server origin expressible in the CSP
    // (CSP host-sources cannot name IPv6 literals such as [::1]).
    server: {
        host: '127.0.0.1',
    },
    plugins: [
        laravel({
            ssr: 'resources/js/ssr.jsx',
            input: [
                'resources/css/app.css',
                'resources/css/public-glass.css',
                'resources/js/app.js',
                'resources/js/inertia.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],
});
