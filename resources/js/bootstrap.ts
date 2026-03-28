import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Explicitly assign Pusher to the window so the library is globally available
(window as any).Pusher = Pusher;

/**
 * Echo initialization with a safety check
 */
const apiKey = import.meta.env.VITE_PUSHER_APP_KEY;
const apiCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER;

if (apiKey && apiCluster) {
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: apiKey,
        cluster: apiCluster,
        forceTLS: true
    });
    console.log('Echo has been initialized successfully.');
} else {
    console.error('Vite env variables are missing! Check your .env file.');
}

declare global {
    interface Window {
        axios: typeof axios;
        Pusher: typeof Pusher;
        Echo: Echo<any>;
    }
}