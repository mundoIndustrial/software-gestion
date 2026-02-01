import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// 🛡️ CREAR STUB DE STORAGE POR SEGURIDAD (por si acaso no esté disponible)
if (typeof window.localStorage === 'undefined') {
    window.localStorage = {
        getItem: () => null,
        setItem: () => {},
        removeItem: () => {},
        clear: () => {},
        key: () => null,
        length: 0
    };
}
if (typeof window.sessionStorage === 'undefined') {
    window.sessionStorage = {
        getItem: () => null,
        setItem: () => {},
        removeItem: () => {},
        clear: () => {},
        key: () => null,
        length: 0
    };
}

// Importar Pusher JS SINCRÓNICAMENTE
import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Importar Echo SINCRÓNICAMENTE
import Echo from 'laravel-echo';

/**
 * Inicializar Echo después de que todo esté cargado
 */
function initializeEcho() {
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
    }
}

// Inicializar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeEcho);
} else {
    initializeEcho();
}
