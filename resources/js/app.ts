import '../css/app.css';
import './bootstrap';
import { DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // --- GLOBAL REAL-TIME LISTENER START ---
        // This runs once when the application starts
        window.Echo.channel('monti-stream')
            .listen('.TextileUpdate', (e: { message: string }) => {
                console.log('Global Event Received:', e.message);
                alert('Monti Textile Notification: ' + e.message);
            });
        // --- GLOBAL REAL-TIME LISTENER END ---

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});