import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/ultimate.css', 'resources/css/customer.css', 'resources/css/quote-flow.css', 'resources/css/worker.css', 'resources/css/worker-flow.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
