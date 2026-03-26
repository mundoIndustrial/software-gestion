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



// Guardar el constructor Echo en una variable separada
window.EchoConstructor = Echo;

// Exportar Echo al scope global para que esté disponible en todas partes
window.Echo = Echo;



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
 * Callbacks pendientes a ejecutar cuando Echo esté listo
 */
window.echoReadyCallbacks = window.echoReadyCallbacks || [];
window.echoReady = window.echoReady || false;

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
            console.error('[Echo] Error ejecutando callback:', error);
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
    
    // Usar la misma IP/hostname de la página actual (evita problemas de red)
    const currentHost = window.location.hostname;
    
    // Fallback a variables de entorno compiladas (para compatibilidad)
    let wsHost = metaReverbHost || currentHost || import.meta.env.VITE_REVERB_HOST || 'localhost';
    let wsPort = parseInt(metaReverbPort || import.meta.env.VITE_REVERB_PORT) || 8080;
    
    // Detectar si está en producción por el hostname
    const hostname = window.location.hostname;
    const isProduction = hostname !== 'localhost' && hostname !== '127.0.0.1' && hostname.includes('.');
    
    // En producción, usar proxy de Nginx (puerto 443/80) en lugar de conexión directa
    // En desarrollo, usar conexión directa al puerto de Reverb
    let useProxy = isProduction;
    let wsPortFinal = useProxy ? (window.location.protocol === 'https:' ? 443 : 80) : wsPort;
    let wsHostFinal = useProxy ? window.location.hostname : wsHost;
    let forceTLSFinal = useProxy ? (window.location.protocol === 'https:') : false;

    try {
        
        // WebSockets habilitados para Reverb (Supervisor Pedidos en tiempo real)
        const echoInstance = new window.EchoConstructor({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY || 'mundo-industrial-key',
            wsHost: wsHostFinal,
            wsPort: wsPortFinal,
            wssPort: wsPortFinal,
            forceTLS: forceTLSFinal,
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
        
        // Guardar la instancia en window.Echo (no como constructor)
        window.Echo = echoInstance;
        window.EchoInstance = echoInstance;
        

        
        // Notificar que Echo está listo inmediatamente
        window.notifyEchoReady();
        
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
