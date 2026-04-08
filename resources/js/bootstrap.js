import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Create storage stubs for edge environments
if (typeof window.localStorage === 'undefined') {
    window.localStorage = {
        getItem: () => null,
        setItem: () => {},
        removeItem: () => {},
        clear: () => {},
        key: () => null,
        length: 0,
    };
}
if (typeof window.sessionStorage === 'undefined') {
    window.sessionStorage = {
        getItem: () => null,
        setItem: () => {},
        removeItem: () => {},
        clear: () => {},
        key: () => null,
        length: 0,
    };
}

// Echo readiness coordination
window.echoReady = window.echoReady || false;
window.echoReadyCallbacks = window.echoReadyCallbacks || [];
window.EchoConstructor = window.EchoConstructor || null;
window.EchoInstance = window.EchoInstance || null;
window.Echo = window.Echo || null;

window.waitForEcho = function (callback) {
    if (window.echoReady && window.Echo) {
        callback();
    } else {
        window.echoReadyCallbacks.push(callback);
    }
};

window.notifyEchoReady = function () {
    window.echoReady = true;

    while (window.echoReadyCallbacks.length > 0) {
        const callback = window.echoReadyCallbacks.shift();
        try {
            callback();
        } catch (error) {
            console.error('[Echo] Error ejecutando callback:', error);
        }
    }
};

let echoInitPromise = null;

async function loadEchoDependencies() {
    if (window.EchoConstructor && window.Pusher) {
        return;
    }

    const [{ default: Pusher }, { default: Echo }] = await Promise.all([
        import('pusher-js'),
        import('laravel-echo'),
    ]);

    window.Pusher = Pusher;
    window.EchoConstructor = Echo;
}

async function initializeEcho() {
    if (window.EchoInstance) {
        window.notifyEchoReady();
        return window.EchoInstance;
    }

    if (echoInitPromise) {
        return echoInitPromise;
    }

    echoInitPromise = (async () => {
        await loadEchoDependencies();

        // Read runtime config from meta tags first
        const metaReverbHost = document.querySelector('meta[name="reverb-host"]')?.getAttribute('content');
        const metaReverbPort = document.querySelector('meta[name="reverb-port"]')?.getAttribute('content');

        const currentHost = window.location.hostname;

        let wsHost = metaReverbHost || currentHost || import.meta.env.VITE_REVERB_HOST || 'localhost';
        let wsPort = parseInt(metaReverbPort || import.meta.env.VITE_REVERB_PORT, 10) || 8080;

        const hostname = window.location.hostname;
        const isProduction = hostname !== 'localhost' && hostname !== '127.0.0.1' && hostname.includes('.');

        const useProxy = isProduction;
        const wsPortFinal = useProxy ? (window.location.protocol === 'https:' ? 443 : 80) : wsPort;
        const wsHostFinal = useProxy ? window.location.hostname : wsHost;
        const forceTLSFinal = useProxy ? window.location.protocol === 'https:' : false;

        try {
            const echoInstance = new window.EchoConstructor({
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

            window.Echo = echoInstance;
            window.EchoInstance = echoInstance;
            window.notifyEchoReady();

            return echoInstance;
        } catch (error) {
            console.error('[Echo] Error inicializando Echo:', error);
            throw error;
        }
    })();

    return echoInitPromise;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeEcho().catch((error) => {
            console.error('[Echo] Fallo inicializacion diferida:', error);
        });
    });
} else {
    initializeEcho().catch((error) => {
        console.error('[Echo] Fallo inicializacion diferida:', error);
    });
}
