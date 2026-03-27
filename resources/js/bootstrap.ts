import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel.
 */

// This part tells TypeScript that 'Pusher' and 'Echo' exist on the window
declare global {
    interface Window {
        axios: typeof axios;
        Pusher: typeof Pusher;
        Echo: Echo<any>; // Added <any> here
    }
}

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY as string,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER as string,
    forceTLS: true
});