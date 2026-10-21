import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    // Windows bind mounts don't forward file events into Docker, so poll for changes
    server: {
        watch: {
            usePolling: true,
            interval: 1000,
            ignored: ['**/vendor/**', '**/storage/**', '**/node_modules/**', '**/public/**', '**/.git/**'],
        },
    },
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
