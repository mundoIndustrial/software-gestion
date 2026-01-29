import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Importar Pusher JS para Reverb
import Pusher from 'pusher-js';
window.Pusher = Pusher;

import Echo from 'laravel-echo';

/**
 * Configuración Reverb - Con Pusher JS importado
 * Pusher JS ahora está disponible globalmente para Laravel Echo
 */
const hostname = window.location.hostname;
const isProduction = hostname === 'sistemamundoindustrial.online';

// Usar IP específica para desarrollo
const wsHost = isProduction ? 'sistemamundoindustrial.online' : '192.168.0.173';
const wsPort = isProduction ? 443 : 8080;
const forceTLS = isProduction;

try {
    // WebSockets desactivados - Usar solo polling fallback
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost,
        wsPort,
        wssPort: wsPort,
        forceTLS,
        enabledTransports: [], // Desactivar WebSockets - usar solo polling
        disableStats: true,
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        },
        wsErrorMessage: 'WebSocket connection failed',
    });

    if (!isProduction) {
        console.log('[Reverb] Echo conectado exitosamente', {
            wsHost,
            wsPort,
            key: import.meta.env.VITE_REVERB_APP_KEY,
        });
    }
} catch (error) {
    console.error('[Reverb] Error inicializando Echo:', error);
    // Echo no disponible, las funciones real-time no funcionarán
}
