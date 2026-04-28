import { OrdenesLoader } from './modules/ordenes-loader.js';
import { initializeCarteraTable } from './modules/table-manager.js';
import { initializeFilters } from './modules/filter-manager.js';
import { initializeRealtime } from './modules/realtime-manager.js';
import { initializeInvoiceManager } from './modules/invoice-manager.js';
import { initPerformanceMonitor } from './modules/performance-monitor.js';

const initState = {
    isReady: false,
    initializedAt: null,
    module: 'supervisor-pedidos',
};

const startupOverlayState = {
    coreReady: false,
    ordenesReady: false,
    hidden: false,
};

function hideStartupOverlay(reason = 'ready') {
    if (startupOverlayState.hidden) return;
    const overlay = document.getElementById('sp-loading-overlay');
    startupOverlayState.hidden = true;
    if (!overlay) return;
    overlay.classList.add('is-hidden');
    overlay.setAttribute('aria-hidden', 'true');
    console.log(`[SP] Overlay oculto (${reason})`);
    setTimeout(() => overlay.remove(), 350);
}

function maybeHideStartupOverlay() {
    if (startupOverlayState.coreReady && startupOverlayState.ordenesReady) {
        hideStartupOverlay('core+ordenes');
    }
}

document.addEventListener('supervisor-pedidos:ordenes-loader-ready', () => {
    startupOverlayState.ordenesReady = true;
    maybeHideStartupOverlay();
});

function isSupervisorView() {
    return (document.body?.dataset?.module || '') === 'supervisor-pedidos';
}

function hasLegacySupervisorScript() {
    return typeof window.navegarSupervisorPedidos === 'function'
        || typeof window.renderSupervisorOrdersTable === 'function';
}

async function initSupervisorPedidos() {
    const perfMonitor = initPerformanceMonitor();
    perfMonitor.mark('SUPERVISOR_PEDIDOS_INIT_START');

    if (initState.isReady || !isSupervisorView()) return;

    try {
        console.log('[SP] Iniciando...');
        perfMonitor.mark('SUPERVISOR_VIEW_DETECTED');

        perfMonitor.mark('STARTING_TABLE_AND_FILTERS');
        await Promise.all([
            initializeCarteraTable(),
            initializeFilters(),
        ]);
        perfMonitor.mark('TABLE_AND_FILTERS_READY');

        perfMonitor.mark('STARTING_REALTIME_CHECK');
        const userRole = document.body?.dataset?.userRole || '';
        if (['supervisor_pedidos', 'asesor'].includes(userRole)) {
            await initializeRealtime();
        } else {
            console.log('[SP] Realtime no necesario para rol:', userRole);
        }
        perfMonitor.mark('REALTIME_READY');

        perfMonitor.mark('STARTING_INVOICE_CHECK');
        await new Promise((resolve) => setTimeout(resolve, 100));
        await initializeInvoiceManager();
        perfMonitor.mark('INVOICE_READY');

        initState.isReady = true;
        initState.initializedAt = new Date().toISOString();
        perfMonitor.mark('MODULE_FULLY_READY');
        document.dispatchEvent(new CustomEvent('supervisor-pedidos:ready', { detail: initState }));
        console.log('[SP] Module ready', initState);
    } catch (error) {
        console.error('[SP] Initialization failed:', error);
    } finally {
        startupOverlayState.coreReady = true;
        maybeHideStartupOverlay();
    }
}

function initOrdersLoaderIfNeeded() {
    if (hasLegacySupervisorScript()) {
        console.log('[SP] Loader API omitido: script legacy activo');
        startupOverlayState.ordenesReady = true;
        maybeHideStartupOverlay();
        return;
    }

    const container = document.querySelector('[data-ordenes-body]');
    if (!container) {
        console.warn('[SP] Contenedor [data-ordenes-body] no encontrado');
        startupOverlayState.ordenesReady = true;
        maybeHideStartupOverlay();
        return;
    }

    window.ordenesLoader = new OrdenesLoader({ perPage: 15 });
    console.log('[SP] OrdenesLoader inicializado exitosamente');
}

function boot() {
    setTimeout(() => hideStartupOverlay('fallback-timeout'), 12000);
    setTimeout(initSupervisorPedidos, 100);
    setTimeout(initOrdersLoaderIfNeeded, 300);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { initSupervisorPedidos, isSupervisorView, initState };

