import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

//  CREAR STUB DE STORAGE POR SEGURIDAD (por si acaso no esté disponible)
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

//  Sistema para esperar a que Echo esté listo
window.echoReady = false;
window.echoReadyCallbacks = [];

/**
 * Esperar a que Echo esté completamente inicializado
 * Uso: window.waitForEcho(() => { callback code })
 */
window.waitForEcho = function(callback) {
    if (window.echoReady && window.Echo) {
        callback();
    } else {
        window.echoReadyCallbacks.push(callback);
    }
};

/**
 * Notificar que Echo está listo (llamado al final de inicializeEcho)
 */
window.notifyEchoReady = function() {
    window.echoReady = true;
    
    // Ejecutar todos los callbacks pendientes
    while (window.echoReadyCallbacks.length > 0) {
        const callback = window.echoReadyCallbacks.shift();
        try {
            callback();
        } catch (error) {
            console.error('[Echo]  Error ejecutando callback:', error);
        }
    }
};

/**
 * Inicializar Echo después de que todo esté cargado
 */
function initializeEcho() {
    //  Leer config desde meta tags inyectados por Laravel (dinámico, no compilado)
    const metaReverbHost = document.querySelector('meta[name="reverb-host"]')?.getAttribute('content');
    const metaReverbPort = document.querySelector('meta[name="reverb-port"]')?.getAttribute('content');
    
    // Fallback a variables de entorno compiladas (para compatibilidad)
    let wsHost = metaReverbHost || import.meta.env.VITE_REVERB_HOST || 'localhost';
    let wsPort = parseInt(metaReverbPort || import.meta.env.VITE_REVERB_PORT) || 8080;
    
    // Detectar si está en producción por el hostname
    const hostname = window.location.hostname;
    const isProduction = hostname !== 'localhost' && hostname !== '127.0.0.1' && hostname.includes('.');
    
    // En producción con dominio, usar HTTPS automáticamente
    const forceTLS = isProduction && wsPort === 443;

    try {
        // WebSockets habilitados para Reverb (Supervisor Pedidos en tiempo real)
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY || 'mundo-industrial-key',
            wsHost,
            wsPort,
            wssPort: wsPort,
            forceTLS,
            enabledTransports: ['ws', 'wss'], //  Habilitar WebSockets
            disableStats: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            },
            wsErrorMessage: 'WebSocket connection failed',
        });
        
        // Notificar que Echo está listo
        setTimeout(() => {
            window.notifyEchoReady();
        }, 100);
        
    } catch (error) {
        console.error('[Echo]  Error inicializando Echo:', error);
    }
}

// Inicializar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeEcho();
    });
} else {
    initializeEcho();
}
