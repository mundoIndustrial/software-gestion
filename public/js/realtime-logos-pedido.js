/**
 * Actualización en tiempo real para diseños de logo (asesor ↔ visualizador).
 */
(function () {
    if (window.realtimeLogosPedidoLoaded) {
        return;
    }
    window.realtimeLogosPedidoLoaded = true;

    const ACCIONES_PENDIENTES_ASESOR = new Set([
        'creado',
        'reemplazado',
        'confirmado',
        'devuelto',
        'eliminado',
    ]);

    const ACCIONES_VISTA_VISUALIZADOR = new Set([
        'confirmado',
        'devuelto',
        'reemplazado',
        'revisado',
        'eliminado',
    ]);

    const processedEvents = new Map();
    const PROCESSED_TTL_MS = 4000;

    const NOTIFICACIONES_POR_MODULO = {
        asesores: {
            creado: {
                titulo: 'Nuevo diseño de logo',
                mensaje: (event) => `Hay un diseño pendiente por confirmar en el pedido #${event.pedido_id || '—'}.`,
                url: '/asesores/pendientes-logo',
            },
            reemplazado: {
                titulo: 'Diseño reemplazado',
                mensaje: (event) => `Un diseño fue reemplazado y requiere tu confirmación (pedido #${event.pedido_id || '—'}).`,
                url: '/asesores/pendientes-logo',
            },
        },
        'visualizador-logo': {
            confirmado: {
                titulo: 'Diseño confirmado',
                mensaje: (event) => `El asesor confirmó un diseño del pedido #${event.pedido_id || '—'}.`,
                url: '/visualizador-logo/logos-confirmados',
            },
            devuelto: {
                titulo: 'Diseño devuelto a diseño',
                mensaje: (event) => `El asesor devolvió un diseño del pedido #${event.pedido_id || '—'}.`,
                url: '/visualizador-logo/logos-confirmados',
            },
        },
    };

    function cleanupProcessedEvents() {
        const now = Date.now();
        for (const [key, ts] of processedEvents.entries()) {
            if (now - ts > PROCESSED_TTL_MS) {
                processedEvents.delete(key);
            }
        }
    }

    function shouldProcessEvent(event) {
        cleanupProcessedEvents();
        const key = [
            event?.diseno_id ?? '',
            event?.accion ?? '',
            event?.estado_nuevo ?? '',
            event?.timestamp ?? '',
        ].join('|');

        if (processedEvents.has(key)) {
            return false;
        }

        processedEvents.set(key, Date.now());
        return true;
    }

    function actualizarBadges(event) {
        if (typeof event.conteo_asesor === 'number' && typeof window.__actualizarBadgePendientesLogo === 'function') {
            window.__actualizarBadgePendientesLogo(event.conteo_asesor);
        }

        if (event.conteo_no_revisados && typeof window.__actualizarBadgeLogos === 'function') {
            window.__actualizarBadgeLogos(event.conteo_no_revisados);
        }

        if (event.conteo_no_revisados && typeof window.__actualizarBadgesTabsLogosConfirmados === 'function') {
            window.__actualizarBadgesTabsLogosConfirmados(event.conteo_no_revisados);
        }
    }

    async function solicitarPermisoNotificacionSiAplica() {
        if (!('Notification' in window)) return;
        if (Notification.permission === 'granted' || Notification.permission === 'denied') return;

        try {
            await Notification.requestPermission();
        } catch (error) {
            console.warn('[REALTIME-LOGOS] No se pudo solicitar permiso de notificaciones:', error);
        }
    }

    function mostrarNotificacionNavegador(titulo, mensaje, url) {
        if (!('Notification' in window)) return;
        if (Notification.permission !== 'granted') return;

        try {
            const notification = new Notification(titulo, {
                body: mensaje,
                icon: '/mundo_icon.png',
            });

            notification.onclick = function () {
                window.focus();
                if (url && window.location.pathname !== url) {
                    window.location.href = url;
                }
                this.close();
            };
        } catch (error) {
            console.warn('[REALTIME-LOGOS] Error mostrando notificación de navegador:', error);
        }
    }

    function notificarEventoNavegador(event) {
        const module = document.body?.dataset?.module || '';
        const accion = event?.accion || '';
        const config = NOTIFICACIONES_POR_MODULO[module]?.[accion];

        if (!config) return;

        const mensaje = typeof config.mensaje === 'function' ? config.mensaje(event) : String(config.mensaje || '');
        mostrarNotificacionNavegador(config.titulo, mensaje, config.url);
    }

    function refrescarVistas(event) {
        const accion = event?.accion || '';
        const module = document.body?.dataset?.module || '';

        if (module === 'asesores' && ACCIONES_PENDIENTES_ASESOR.has(accion)) {
            if (typeof window.__refrescarVistaPendientesLogo === 'function') {
                window.__refrescarVistaPendientesLogo(event);
            }
        }

        if (module === 'visualizador-logo' && ACCIONES_VISTA_VISUALIZADOR.has(accion)) {
            if (typeof window.__refrescarVistaLogosConfirmados === 'function') {
                window.__refrescarVistaLogosConfirmados(event);
            }
        }
    }

    function handleDisenoLogoActualizado(event) {
        if (!event || !shouldProcessEvent(event)) {
            return;
        }

        actualizarBadges(event);
        refrescarVistas(event);
        notificarEventoNavegador(event);

        window.dispatchEvent(new CustomEvent('diseno-logo-actualizado', { detail: event }));
    }

    function subscribeToLogosChannels() {
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        const module = document.body?.dataset?.module || '';
        const ws = window.shared?.websocket;

        if (!ws) {
            console.warn('[REALTIME-LOGOS] WebSocket no disponible');
            return;
        }

        if (module === 'asesores' && userId) {
            ws.subscribe(`logos.asesor.${userId}`, '.diseno.logo.actualizado', handleDisenoLogoActualizado);
        }

        if (module === 'visualizador-logo') {
            ws.subscribe('logos.visualizador', '.diseno.logo.actualizado', handleDisenoLogoActualizado);
        }
    }

    function initializeRealtimeLogosPedido() {
        if (!window.shared?.isReady || typeof globalThis.EchoManager?.init !== 'function') {
            setTimeout(initializeRealtimeLogosPedido, 50);
            return;
        }

        solicitarPermisoNotificacionSiAplica();

        globalThis.EchoManager.init()
            .then(() => subscribeToLogosChannels())
            .catch((error) => {
                console.warn('[REALTIME-LOGOS] No se pudo inicializar Echo:', error);
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeRealtimeLogosPedido);
    } else {
        initializeRealtimeLogosPedido();
    }
})();
