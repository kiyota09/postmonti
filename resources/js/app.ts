import '../css/app.css';
import './bootstrap'; // Ensure Echo is initialized inside bootstrap.ts

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
// Use the ZiggyVue helper from the vendor folder to ensure it matches your Laravel routes
import { ZiggyVue } from '../../vendor/tightenco/ziggy'; 

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // --- GLOBAL REAL-TIME LISTENER ---
        // We check if Echo exists to prevent "undefined" crashes during the mount
        if (window.Echo) {
            window.Echo.channel('monti-stream')
                .listen('.TextileUpdate', (e: { message: string }) => {
                    console.log('Global Event Received:', e.message);
                    alert('Monti Textile Notification: ' + e.message);
                });
        }

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});