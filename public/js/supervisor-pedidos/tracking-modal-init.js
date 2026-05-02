/**
 * =====================================================
 * SUPERVISOR PEDIDOS - TRACKING MODAL INIT
 * =====================================================
 * Funciones para abrir/cerrar el modal de seguimiento.
 *
 * Requiere: shared/bootstrap.js -> window.shared (http, notify, modal)
 */

if (!window.shared?.isReady) {
    throw new Error('[tracking-modal-init] window.shared no esta disponible. Asegurate de cargar shared/bootstrap.js ANTES de este archivo.');
}

const { http: _trackingHttp, notify: _trackingNotify, modal: _trackingModal } = window.shared;
const _isLocalHost = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);

const TRACKING_LAZY_SCRIPTS = [
    { src: '/js/ordersjs/tracking-modal-utils.js' },
    { src: '/js/ordersjs/tracking/days-selector-handler.js' },
    { src: '/js/ordersjs/tracking/date-utils.js' },
    { src: '/js/ordersjs/tracking/modal-manager.js' },
    { src: '/js/ordersjs/tracking/days-selector.js' },
    { src: '/js/ordersjs/tracking/data-loader.js' },
    { src: '/js/ordersjs/tracking/ui-components.js' },
    { src: '/js/ordersjs/tracking/process-manager.js' },
    { src: '/js/ordersjs/tracking/area-cards.js' },
    { src: '/js/ordersjs/tracking/prendas-renderer.js' },
    { src: '/js/ordersjs/tracking/tracking-main.js' },
    { src: '/js/ordersjs/tracking-modal-handler.js', type: 'module' },
];

let _trackingRuntimePromise = null;
let _trackingRuntimeReady = false;

function _trackingLog(message) {
    if (_isLocalHost) {
        console.log(`[tracking-on-demand] ${message}`);
    }
}

function _loadScript({ src, type = 'text/javascript' }) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[data-tracking-lazy="${src}"]`);
        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve();
                return;
            }
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error(`Error cargando ${src}`)), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.defer = true;
        script.async = false;
        script.type = type;
        script.dataset.trackingLazy = src;
        script.onload = () => {
            script.dataset.loaded = 'true';
            resolve();
        };
        script.onerror = () => reject(new Error(`Error cargando ${src}`));
        document.head.appendChild(script);
    });
}

async function _ensureTrackingRuntimeLoaded() {
    if (_trackingRuntimeReady && typeof window.mostrarTrackingModal === 'function') {
        _trackingLog('runtime ya listo (cache)');
        return;
    }

    if (_trackingRuntimePromise) {
        _trackingLog('reutilizando promise en curso');
        return _trackingRuntimePromise;
    }

    _trackingLog('cargando runtime tracking on-demand...');
    _trackingRuntimePromise = (async () => {
        for (const script of TRACKING_LAZY_SCRIPTS) {
            await _loadScript(script);
        }

        if (typeof window.mostrarTrackingModal !== 'function') {
            throw new Error('mostrarTrackingModal no disponible tras carga lazy');
        }

        _trackingRuntimeReady = true;
        _trackingLog('runtime tracking cargado');
    })().finally(() => {
        _trackingRuntimePromise = null;
    });

    return _trackingRuntimePromise;
}

window.ensureTrackingRuntimeLoaded = _ensureTrackingRuntimeLoaded;

window.openOrderTrackingModal = async function(ordenId) {
    console.log('[openOrderTrackingModal] Abriendo modal para orden:', ordenId);

    try {
        await _ensureTrackingRuntimeLoaded();
    } catch (runtimeError) {
        console.error('[openOrderTrackingModal] Error cargando runtime tracking:', runtimeError);
        _trackingNotify.error('No se pudo cargar el modulo de seguimiento. Intenta nuevamente.');
        return;
    }

    try {
        const pedidoResponse = await _trackingHttp.get(`/api/supervisor-pedidos/ordenes/${ordenId}/datos`);
        console.log('[openOrderTrackingModal] Datos del pedido recibidos');

        // Normalizar respuesta: algunos endpoints devuelven { success, data: {...} }
        const pedidoData = (pedidoResponse && typeof pedidoResponse === 'object' && pedidoResponse.data && typeof pedidoResponse.data === 'object')
            ? { ...pedidoResponse.data }
            : { ...(pedidoResponse || {}) };

        try {
            pedidoData.procesos = await _trackingHttp.get(`/api/ordenes/${ordenId}/procesos`);
        } catch (procError) {
            console.warn('[openOrderTrackingModal] Error al obtener procesos:', procError);
            pedidoData.procesos = [];
        }

        if (!pedidoData.id && ordenId) {
            pedidoData.id = Number(ordenId);
        }
        if (!pedidoData.orderId && pedidoData.id) {
            pedidoData.orderId = Number(pedidoData.id);
        }

        window.mostrarTrackingModal(pedidoData);
        console.log('[openOrderTrackingModal] Modal mostrado exitosamente');

    } catch (error) {
        console.error('[openOrderTrackingModal] Error general:', error);
        _trackingNotify.error('No se puede abrir el seguimiento. Intenta nuevamente.');
    }
};

window.closeOrderTracking = function() {
    console.log('[closeOrderTracking] Cerrando modal de seguimiento');
    _trackingModal.close('orderTrackingModal');
};
