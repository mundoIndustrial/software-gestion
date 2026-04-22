import axios from 'axios';
globalThis.axios = axios;

globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Create storage stubs for edge environments
if (typeof globalThis.localStorage === 'undefined') {
    globalThis.localStorage = {
        getItem: () => null,
        setItem: () => {},
        removeItem: () => {},
        clear: () => {},
        key: () => null,
        length: 0,
    };
}
if (typeof globalThis.sessionStorage === 'undefined') {
    globalThis.sessionStorage = {
        getItem: () => null,
        setItem: () => {},
        removeItem: () => {},
        clear: () => {},
        key: () => null,
        length: 0,
    };
}

/**
 * ============================================
 * ECHO MANAGER - Consolidated WebSocket Handler
 * ============================================
 *
 * Centraliza la inicialización de Laravel Echo
 * Soporta tanto callbacks como promises
 * Lazy-loads Pusher/Echo solo cuando se necesita
 */

// Singleton EchoManager
globalThis.EchoManager = globalThis.EchoManager || {
    instance: null,
    ready: false,
    callbacks: [],
    initPromise: null,

    async init() {
        if (this.instance) {
            return this.instance;
        }

        if (this.initPromise) {
            return this.initPromise;
        }

        this.initPromise = this._doInit();
        return this.initPromise;
    },

    async _doInit() {
        try {
            const [, { default: Echo }] = await Promise.all([
                import('pusher-js'),
                import('laravel-echo'),
            ]);

            const metaReverbHost = document.querySelector('meta[name="reverb-host"]')?.getAttribute('content');
            const metaReverbPort = document.querySelector('meta[name="reverb-port"]')?.getAttribute('content');

            const hostname = globalThis.location.hostname;
            const isProduction = hostname !== 'localhost' && hostname !== '127.0.0.1' && hostname.includes('.');

            let wsHost = metaReverbHost || hostname || import.meta.env.VITE_REVERB_HOST || 'localhost';
            let wsPort = Number(metaReverbPort || import.meta.env.VITE_REVERB_PORT) || 8080;

            const useProxy = isProduction;
            const wsPortFinal = useProxy ? (globalThis.location.protocol === 'https:' ? 443 : 80) : wsPort;
            const wsHostFinal = useProxy ? hostname : wsHost;
            const forceTLSFinal = useProxy && globalThis.location.protocol === 'https:';

            const echoInstance = new Echo({
                broadcaster: 'reverb',
                key: import.meta.env.VITE_REVERB_APP_KEY || 'mundo-industrial-key',
                wsHost: wsHostFinal,
                wsPort: wsPortFinal,
                wssPort: wsPortFinal,
                forceTLS: forceTLSFinal,
                enabledTransports: ['ws', 'wss'],
                disableStats: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                },
            });

            this.instance = echoInstance;
            globalThis.Echo = echoInstance;
            globalThis.EchoInstance = echoInstance;
            this.ready = true;

            this.callbacks.forEach(cb => {
                try {
                    cb(echoInstance);
                } catch (error) {
                    console.error('[EchoManager] Error en callback:', error);
                }
            });
            this.callbacks = [];

            try {
                globalThis.dispatchEvent(new CustomEvent('echo:ready', {
                    detail: { echo: echoInstance },
                }));
            } catch (error) {
                console.error('[EchoManager] Error despachando evento:', error);
            }

            return echoInstance;
        } catch (error) {
            console.error('[EchoManager] Init failed:', error);
            throw error;
        }
    },

    onReady(callback) {
        if (typeof callback !== 'function') return;
        if (this.ready && this.instance) {
            callback(this.instance);
        } else {
            this.callbacks.push(callback);
        }
    },
};

// Compatibilidad con código existente
globalThis.echoReady = false;
globalThis.echoReadyCallbacks = [];
globalThis.EchoConstructor = null;
globalThis.EchoInstance = null;
globalThis.Echo = null;

globalThis.waitForEcho = function (callback) {
    // Delegar a EchoManager
    if (typeof callback === 'function') {
        globalThis.EchoManager.onReady(callback);
    } else {
        return globalThis.EchoManager.init();
    }
};

// Mantener para compatibilidad, pero delegar a EchoManager
globalThis.notifyEchoReady = function () {
    // EchoManager ya maneja los callbacks, esto es solo compatibilidad
    globalThis.echoReady = true;
};

// Alias para código legacy
globalThis.initEcho = function () {
    return globalThis.EchoManager.init();
};

/**
 * Determinar si auto-inicializar Echo
 */
function shouldAutoInitializeEcho() {
    const moduleName = document.body?.dataset?.module || '';
    const notificationsUi = document.body?.dataset?.notificationsUi || '';

    return (
        moduleName === 'asesores' ||
        moduleName === 'supervisor-pedidos' ||
        moduleName === 'insumos-materiales' ||
        notificationsUi === 'asesores'
    );
}

if (shouldAutoInitializeEcho()) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            globalThis.EchoManager.init().catch((error) => {
                console.error('[Bootstrap] Echo init failed:', error);
            });
        });
    } else {
        globalThis.EchoManager.init().catch((error) => {
            console.error('[Bootstrap] Echo init failed:', error);
        });
    }
}
