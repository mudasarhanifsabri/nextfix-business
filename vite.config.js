import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/ultimate.css', 'resources/css/customer.css', 'resources/css/booking-evidence.css', 'resources/css/quote-flow.css', 'resources/css/worker.css', 'resources/css/worker-flow.css', 'resources/css/worker-media.css', 'resources/css/worker-actions.css', 'resources/css/worker-completion.css', 'resources/css/notifications.css', 'resources/css/units.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
