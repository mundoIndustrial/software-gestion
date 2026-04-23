/**
 * Entrypoint transicional para insumos/materiales.
 * Carga scripts legacy fuera de Vite mediante un único punto de entrada.
 */

// Generar un hash corto basado en la fecha de build (versión compilada)
// En producción, esto debería venir de un archivo de versión real
const BUILD_VERSION = '1776951476'; // Versión desde última compilación

const PRELOAD_SCRIPT_CONFIG = [
    // Fase actual: estos scripts se administran desde el entrypoint (no desde el page-loader)
    { src: `/js/insumos/filter-manager-no-url.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/insumos/search-debounce.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/insumos/status-actions-insumos.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/insumos/dropdown-handlers-insumos.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/insumos/modal-handlers-insumos.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/insumos/notifications-realtime-insumos.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/insumos/recibos-selector-insumos.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/insumos/pagination.js?v=${BUILD_VERSION}`, defer: true },

    { src: `/js/ordersjs/order-detail-modal-manager.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/asesores/pedidos-detail-modal.js?v=${BUILD_VERSION}`, defer: true },
    { src: `/js/orders-scripts/image-gallery-zoom.js?v=${BUILD_VERSION}`, defer: true },
];

const BOOTSTRAP_SCRIPT = { src: `/js/insumos/materiales-page-loader.js?v=${BUILD_VERSION}`, defer: true };

function scriptAlreadyLoaded(src) {
    const normalizedSrc = new URL(src, globalThis.location.origin).pathname;
    return Array.from(document.scripts).some((script) => {
        try {
            return new URL(script.src, globalThis.location.origin).pathname === normalizedSrc;
        } catch {
            return false;
        }
    });
}

function loadScript({ src, defer = true, type = 'text/javascript', async = false }) {
    if (scriptAlreadyLoaded(src)) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.defer = defer;
        script.async = async;
        script.type = type;
        script.dataset.entrypoint = 'insumos-materiales-vite';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`No se pudo cargar ${src}`));
        document.body.appendChild(script);
    });
}

async function initLegacyScripts() {
    try {
        // Cargar todos los scripts de precarga en paralelo
        await Promise.all(PRELOAD_SCRIPT_CONFIG.map((scriptDef) => loadScript(scriptDef)));
        // Luego cargar el bootstrap después de que los scripts estén listos
        await loadScript(BOOTSTRAP_SCRIPT);
    } catch (error) {
        console.error('[insumos/materiales.entry] Error en initLegacyScripts:', error);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initLegacyScripts().catch((error) => {
            console.error('[insumos/materiales.entry] Error cargando scripts legacy:', error);
        });
    });
} else {
    initLegacyScripts().catch((error) => {
        console.error('[insumos/materiales.entry] Error cargando scripts legacy:', error);
    });
}
