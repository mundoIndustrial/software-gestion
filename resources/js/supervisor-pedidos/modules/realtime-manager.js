/**
 * Realtime Manager - Gestiona inicialización bajo demanda de Echo/WebSocket
 *
 * Este módulo LAZY-LOAD Echo solo cuando es necesario
 * Reduce el bundle inicial de supervisor-pedidos
 */

let isInitialized = false;

export async function initializeRealtime() {
    if (isInitialized) {
        console.log('[RealtimeManager] Ya inicializado');
        return window.Echo;
    }

    try {
        // Esperar a que EchoManager esté disponible
        const echo = await waitForEchoManager();

        if (!echo) {
            throw new Error('EchoManager no disponible después de timeout');
        }

        // Inicializar listeners específicos de supervisor
        setupSupervisorListeners(echo);

        isInitialized = true;
        console.log('[RealtimeManager] ✅ Realtime ready');

        return echo;
    } catch (error) {
        console.error('[RealtimeManager] Error:', error);
        throw error;
    }
}

/**
 * Esperar a que EchoManager esté listo
 */
function waitForEchoManager() {
    return new Promise((resolve) => {
        if (window.EchoManager?.ready) {
            resolve(window.Echo);
            return;
        }

        // Usar callback pattern
        if (window.EchoManager?.onReady) {
            window.EchoManager.onReady((echo) => {
                resolve(echo);
            });
        } else {
            // Fallback: esperar a window.Echo
            const checkInterval = setInterval(() => {
                if (window.Echo) {
                    clearInterval(checkInterval);
                    resolve(window.Echo);
                }
            }, 100);

            // Timeout
            setTimeout(() => {
                clearInterval(checkInterval);
                resolve(null);
            }, 5000);
        }
    });
}

/**
 * Configurar listeners específicos de supervisor
 */
function setupSupervisorListeners(echo) {
    if (!echo) return;

    const userId = document.querySelector('meta[name="user-id"]')?.content;
    const userRole = document.body?.dataset?.userRole || '';

    if (!userId) {
        console.warn('[RealtimeManager] No user-id meta tag found');
        return;
    }

    try {
        // Escuchar notificaciones de pedidos en tiempo real
        echo.private(`App.Models.User.${userId}`)
            .notification((notification) => {
                console.log('[RealtimeManager] Nueva notificación:', notification);
                // Dispatch event para que otros módulos lo maneje
                document.dispatchEvent(new CustomEvent('realtime:notification', {
                    detail: notification,
                }));
            })
            .error((error) => {
                console.error('[RealtimeManager] Error escuchando notificaciones:', error);
            });

        // Si es supervisor_pedidos, escuchar cambios de estado
        if (userRole === 'supervisor_pedidos') {
            echo.channel('pedidos.estado')
                .listen('PedidoEstadoCambiado', (event) => {
                    console.log('[RealtimeManager] Estado pedido cambió:', event);
                    document.dispatchEvent(new CustomEvent('realtime:pedido-estado', {
                        detail: event,
                    }));
                })
                .error((error) => {
                    console.error('[RealtimeManager] Error escuchando pedidos.estado:', error);
                });
        }

        console.log('[RealtimeManager] Listeners configurados para', userRole);
    } catch (error) {
        console.error('[RealtimeManager] Error configurando listeners:', error);
    }
}
