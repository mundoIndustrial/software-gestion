/**
 * Laravel Echo initialization for real-time broadcasting
 * Uses Laravel Reverb as the WebSocket server
 */

(function() {
    'use strict';

    // Check if Echo and Pusher are loaded
    if (typeof window.Pusher === 'undefined') {
        console.error('Pusher no está cargado. Asegúrate de incluir el script de Pusher.');
        return;
    }

    if (typeof window.Echo !== 'undefined') {
        console.log('Echo ya está inicializado');
        return;
    }

    // Get configuration from meta tags or environment
    const appKey = document.querySelector('meta[name="reverb-app-key"]')?.content || 'dummy-key';
    const host = document.querySelector('meta[name="reverb-host"]')?.content || window.location.hostname;
    const port = document.querySelector('meta[name="reverb-port"]')?.content || 8080;
    const scheme = document.querySelector('meta[name="reverb-scheme"]')?.content || (window.location.protocol === 'https:' ? 'https' : 'http');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    console.log('🚀 Inicializando Laravel Echo con Reverb');
    console.log('Configuración:', { appKey, host, port, scheme });

    try {
        // Initialize Echo with Reverb (Pusher protocol)
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: appKey,
            wsHost: host,
            wsPort: port,
            wssPort: port,
            forceTLS: scheme === 'https',
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            }
        });

        // Connection event handlers
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log(' Conectado al servidor WebSocket');
            updateConnectionStatus(true);
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.log(' Desconectado del servidor WebSocket');
            updateConnectionStatus(false);
        });

        window.Echo.connector.pusher.connection.bind('error', (error) => {
            console.error(' Error de conexión WebSocket:', error);
            updateConnectionStatus(false);
        });

        window.Echo.connector.pusher.connection.bind('unavailable', () => {
            console.warn('⚠️ Servidor WebSocket no disponible');
            updateConnectionStatus(false);
        });

        // Add connection status indicator to page
        addConnectionIndicator();

        console.log(' Laravel Echo inicializado correctamente');

    } catch (error) {
        console.error(' Error al inicializar Laravel Echo:', error);
    }

    /**
     * Update connection status indicator
     */
    function updateConnectionStatus(connected) {
        const indicator = document.getElementById('realtime-indicator');
        if (indicator) {
            if (connected) {
                indicator.classList.remove('disconnected');
                indicator.querySelector('span').textContent = 'Conectado en tiempo real';
            } else {
                indicator.classList.add('disconnected');
                indicator.querySelector('span').textContent = 'Desconectado';
            }
        }
    }

    /**
     * Add connection status indicator to page
     */
    function addConnectionIndicator() {
        // Check if indicator already exists
        if (document.getElementById('realtime-indicator')) {
            return;
        }

        const indicator = document.createElement('div');
        indicator.id = 'realtime-indicator';
        indicator.className = 'realtime-indicator';
        indicator.innerHTML = '<span>Conectando...</span>';
        document.body.appendChild(indicator);

        // Hide indicator after 5 seconds if connected
        setTimeout(() => {
            if (!indicator.classList.contains('disconnected')) {
                indicator.style.opacity = '0';
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 300);
            }
        }, 5000);
    }

    // Expose Echo globally
    window.Echo = window.Echo;

})();
