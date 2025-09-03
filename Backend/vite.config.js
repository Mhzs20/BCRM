import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
                {
                    src: 'node_modules/remixicon/fonts',
                    dest: ''
                }
            ]
        })
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,      
        hmr: {
            host: '127.0.0.1',
        },
    },
});
