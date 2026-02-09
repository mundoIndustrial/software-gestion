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

// 🔥 Sistema para esperar a que Echo esté listo
window.echoReady = false;
window.echoReadyCallbacks = [];

/**
 * Esperar a que Echo esté completamente inicializado
 * Uso: window.waitForEcho(() => { callback code })
 */
window.waitForEcho = function(callback) {
    if (window.echoReady && window.Echo) {
        console.log('[Echo] ✅ Echo ya está listo, ejecutando callback inmediatamente');
        callback();
    } else {
        console.log('[Echo] ⏳ Echo no está listo, esperando...');
        window.echoReadyCallbacks.push(callback);
    }
};

/**
 * Notificar que Echo está listo (llamado al final de inicializeEcho)
 */
window.notifyEchoReady = function() {
    console.log('[Echo] ✅ ECHO LISTO - Ejecutando', window.echoReadyCallbacks.length, 'callbacks');
    window.echoReady = true;
    
    // Ejecutar todos los callbacks pendientes
    while (window.echoReadyCallbacks.length > 0) {
        const callback = window.echoReadyCallbacks.shift();
        try {
            callback();
        } catch (error) {
            console.error('[Echo] ❌ Error ejecutando callback:', error);
        }
    }
};

/**
 * Inicializar Echo después de que todo esté cargado
 */
function initializeEcho() {
    console.log('[Echo] 🚀 Iniciando inicialización de Echo...');
    
    // 🔥 Leer config desde meta tags inyectados por Laravel (dinámico, no compilado)
    const metaReverbHost = document.querySelector('meta[name="reverb-host"]')?.getAttribute('content');
    const metaReverbPort = document.querySelector('meta[name="reverb-port"]')?.getAttribute('content');
    
    // Fallback a variables de entorno compiladas (para compatibilidad)
    let wsHost = metaReverbHost || import.meta.env.VITE_REVERB_HOST || 'localhost';
    let wsPort = parseInt(metaReverbPort || import.meta.env.VITE_REVERB_PORT) || 8080;
    
    // DEBUG: Mostrar valores
    console.log('[Echo] Meta tags encontrados:', { metaReverbHost, metaReverbPort });
    console.log('[Echo] import.meta.env:', { 
        VITE_REVERB_HOST: import.meta.env.VITE_REVERB_HOST,
        VITE_REVERB_PORT: import.meta.env.VITE_REVERB_PORT 
    });
    
    // Detectar si está en producción por el hostname
    const hostname = window.location.hostname;
    const isProduction = hostname !== 'localhost' && hostname !== '127.0.0.1' && hostname.includes('.');
    
    // En producción con dominio, usar HTTPS automáticamente
    const forceTLS = isProduction && wsPort === 443;

    console.log('[Echo] Configuración final:', { wsHost, wsPort, forceTLS, isProduction, hostname });

    try {
        // WebSockets habilitados para Reverb (Supervisor Pedidos en tiempo real)
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: import.meta.env.VITE_REVERB_APP_KEY || 'mundo-industrial-key',
            wsHost,
            wsPort,
            wssPort: wsPort,
            forceTLS,
            enabledTransports: ['ws', 'wss'], // ✅ Habilitar WebSockets
            disableStats: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            },
            wsErrorMessage: 'WebSocket connection failed',
        });
        
        console.log('[Echo] ✅ Echo instancia creada exitosamente');
        
        // Notificar que Echo está listo
        setTimeout(() => {
            window.notifyEchoReady();
        }, 100);
        
    } catch (error) {
        console.error('[Echo] ❌ Error inicializando Echo:', error);
    }
}

// Inicializar cuando el documento esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[Echo] DOMContentLoaded disparado');
        initializeEcho();
    });
} else {
    console.log('[Echo] Documento ya cargado, inicializando Echo directamente');
    initializeEcho();
}
