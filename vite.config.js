import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const devHost = process.env.VITE_DEV_HOST || '127.0.0.1';
const hmrHost = process.env.VITE_HMR_HOST || devHost;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: devHost === '127.0.0.1' ? '127.0.0.1' : '0.0.0.0',
        hmr: {
            host: '192.168.1.23' // Replace this with your computer's actual local IP
        },
    },
});
