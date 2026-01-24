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
 * Detectar entorno (desarrollo vs producción)
 * En desarrollo: usa localhost:8080 (HTTP)
 * En producción: usa sistemamundoindustrial.online:443 (HTTPS)
 */
const isProduction = import.meta.env.MODE === 'production' || 
                     import.meta.env.VITE_ENV === 'production' ||
                     window.location.hostname !== 'localhost' && 
                     window.location.hostname !== '127.0.0.1';

// Valores por defecto según el entorno
const defaults = {
    dev: {
        host: 'localhost',
        port: 8080,
        scheme: 'http'
    },
    prod: {
        host: 'sistemamundoindustrial.online',
        port: 443,
        scheme: 'https'
    }
};

const env = isProduction ? defaults.prod : defaults.dev;

const echoConfig = {
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'dummy-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || env.host,
    wsPort: import.meta.env.VITE_REVERB_PORT || env.port,
    wssPort: import.meta.env.VITE_REVERB_PORT || env.port,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || env.scheme) === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
};

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
