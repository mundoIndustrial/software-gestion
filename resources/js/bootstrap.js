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

// Echo readiness coordination
globalThis.echoReady = globalThis.echoReady || false;
globalThis.echoReadyCallbacks = globalThis.echoReadyCallbacks || [];
globalThis.EchoConstructor = globalThis.EchoConstructor || null;
globalThis.EchoInstance = globalThis.EchoInstance || null;
globalThis.Echo = globalThis.Echo || null;

globalThis.waitForEcho = function (callback) {
    const isReady = globalThis.echoReady && globalThis.Echo;

    // Compatibilidad 1: callback
    if (typeof callback === 'function') {
        if (isReady) {
            callback(globalThis.Echo);
        } else {
            globalThis.echoReadyCallbacks.push(callback);
        }
        return;
    }

    // Compatibilidad 2: Promise
    if (isReady) {
        return Promise.resolve(globalThis.Echo);
    }

    return new Promise((resolve) => {
        globalThis.echoReadyCallbacks.push(resolve);
    });
};

globalThis.notifyEchoReady = function () {
    globalThis.echoReady = true;

    while (globalThis.echoReadyCallbacks.length > 0) {
        const callback = globalThis.echoReadyCallbacks.shift();
        try {
            if (typeof callback === 'function') {
                callback(globalThis.Echo);
            }
        } catch (error) {
            console.error('[Echo] Error ejecutando callback:', error);
        }
    }

    try {
        globalThis.dispatchEvent(new CustomEvent('echo:ready', {
            detail: { echo: globalThis.Echo },
        }));
    } catch (error) {
        console.error('[Echo] Error despachando evento echo:ready:', error);
    }
};

let echoInitPromise = null;

async function loadEchoDependencies() {
    if (globalThis.EchoConstructor && globalThis.Pusher) {
        return;
    }

    const [{ default: Pusher }, { default: Echo }] = await Promise.all([
        import('pusher-js'),
        import('laravel-echo'),
    ]);

    globalThis.Pusher = Pusher;
    globalThis.EchoConstructor = Echo;
}

async function initializeEcho() {
    if (globalThis.EchoInstance) {
        globalThis.notifyEchoReady();
        return globalThis.EchoInstance;
    }

    if (echoInitPromise) {
        return echoInitPromise;
    }

    echoInitPromise = (async () => {
        await loadEchoDependencies();

        // Read runtime config from meta tags first
        const metaReverbHost = document.querySelector('meta[name="reverb-host"]')?.getAttribute('content');
        const metaReverbPort = document.querySelector('meta[name="reverb-port"]')?.getAttribute('content');

        const currentHost = globalThis.location.hostname;

        let wsHost = metaReverbHost || currentHost || import.meta.env.VITE_REVERB_HOST || 'localhost';
        let wsPort = Number(metaReverbPort || import.meta.env.VITE_REVERB_PORT, 10) || 8080;

        const hostname = globalThis.location.hostname;
        const isProduction = hostname !== 'localhost' && hostname !== '127.0.0.1' && hostname.includes('.');

        const useProxy = isProduction;
        const wsPortFinal = useProxy ? (globalThis.location.protocol === 'https:' ? 443 : 80) : wsPort;
        const wsHostFinal = useProxy ? globalThis.location.hostname : wsHost;
        const forceTLSFinal = useProxy ? globalThis.location.protocol === 'https:' : false;

        try {
            const echoInstance = new globalThis.EchoConstructor({
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
                wsErrorMessage: 'WebSocket connection failed',
            });

            globalThis.Echo = echoInstance;
            globalThis.EchoInstance = echoInstance;
            globalThis.notifyEchoReady();

            return echoInstance;
        } catch (error) {
            console.error('[Echo] Error inicializando Echo:', error);
            throw error;
        }
    })();

    return echoInitPromise;
}

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

globalThis.initEcho = function initEcho() {
    return initializeEcho();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!shouldAutoInitializeEcho()) {
            return;
        }

        initializeEcho().catch((error) => {
            console.error('[Echo] Fallo inicializacion diferida:', error);
        });
    });
} else if (shouldAutoInitializeEcho()) {
    initializeEcho().catch((error) => {
        console.error('[Echo] Fallo inicializacion diferida:', error);
    });
}
