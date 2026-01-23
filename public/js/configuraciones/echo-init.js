/**
 * Laravel Echo initialization for real-time broadcasting
 * Uses Laravel Reverb as the WebSocket server
 * 
 * NOTA: Este archivo es LEGACY. bootstrap.js (vía Vite) es el responsable principal.
 * Este archivo solo actúa como fallback para casos donde bootstrap.js no cargó correctamente.
 */

(function() {
    'use strict';

    // Si Echo ya está inicializado por bootstrap.js, no hacer nada
    if (typeof window.Echo !== 'undefined' && window.Echo !== null) {
        console.log('✅ Echo ya fue inicializado por bootstrap.js, omitiendo echo-init.js');
        return;
    }

    // Verificar si Pusher está disponible
    if (typeof window.Pusher === 'undefined') {
        console.warn('⚠️ Pusher no está disponible, no se puede inicializar Echo');
        return;
    }

    /**
     * Detectar entorno (desarrollo vs producción)
     * En desarrollo: usa localhost:8080 (HTTP)
     * En producción: usa sistemamundoindustrial.online:443 (HTTPS)
     */
    const isProduction = window.location.hostname !== 'localhost' && 
                         window.location.hostname !== '127.0.0.1';

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

    // Obtener configuración de meta tags o usar defaults
    const appKey = document.querySelector('meta[name="reverb-app-key"]')?.content || 'mundo-industrial-key';
    const host = document.querySelector('meta[name="reverb-host"]')?.content || env.host;
    const port = document.querySelector('meta[name="reverb-port"]')?.content || env.port;
    const scheme = document.querySelector('meta[name="reverb-scheme"]')?.content || env.scheme;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    console.log('🔧 echo-init.js - Environment Detection:');
    console.log('Hostname:', window.location.hostname);
    console.log('isProduction:', isProduction);
    console.log('');
    console.log('📡 Configuración de Echo/Reverb (echo-init.js):');
    console.log('Host:', host);
    console.log('Port:', port);
    console.log('Scheme:', scheme);

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

        console.log('✅ Echo inicializado exitosamente por echo-init.js (fallback)');

        // Connection event handlers
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('✅ WebSocket conectado exitosamente a Reverb');
            updateConnectionStatus(true);
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.warn('⚠️ WebSocket desconectado');
            updateConnectionStatus(false);
        });

        window.Echo.connector.pusher.connection.bind('error', (error) => {
            console.error('❌ Error de conexión WebSocket:', error);
            updateConnectionStatus(false);
        });

        window.Echo.connector.pusher.connection.bind('unavailable', () => {
            console.warn('⚠️ WebSocket no disponible');
            updateConnectionStatus(false);
        });

        // Add connection status indicator to page
        addConnectionIndicator();

    } catch (error) {
        console.error('❌ Error al inicializar Echo:', error);
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

})();
