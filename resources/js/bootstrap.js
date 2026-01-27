import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

/**
 * WebSocket Configuration for Reverb
 * 
 * DEVELOPMENT: Always use the current browser hostname
 * PRODUCTION: Use sistemamundoindustrial.online
 */
const hostname = window.location.hostname;
const port = window.location.port;
const protocol = window.location.protocol;

// Only treat as production if explicitly accessing the production domain
const isProduction = hostname === 'sistemamundoindustrial.online';

// WebSocket host and port configuration
let wsHost = hostname;
let wsPort = 8080;
let wsScheme = 'http';

if (isProduction) {
    wsHost = 'sistemamundoindustrial.online';
    wsPort = 443;
    wsScheme = 'https';
}

// Echo configuration
const echoConfig = {
    broadcaster: 'reverb',
    key: 'mundo-industrial-key', // fallback key
    wsHost: wsHost,
    wsPort: wsPort,
    wssPort: wsPort,
    forceTLS: wsScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
};

// Debug logging
if (!isProduction) {
    console.log('[Reverb] Connecting to development server:', {
        wsHost,
        wsPort,
        wsScheme,
        url: `${wsScheme}://${wsHost}:${wsPort}`
    });
}

window.Echo = new Echo(echoConfig);

// Verificar conexión
window.Echo.connector.pusher.connection.bind('connected', () => {
    // Conectado
});

window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('WebSocket error:', err);
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    console.warn('WebSocket disconnected');
});
