/**
 * Supervisor Pedidos - Entrypoint Vite (Optimizado con módulos ES6)
 *
 * Responsabilidades:
 * - Inicializar módulos SOLO para esta vista
 * - Cargar tabla de pedidos
 * - Cargar sistema de filtros
 * - Lazy-load Echo/Realtime bajo demanda
 *
 * Módulos:
 * - table-manager.js → CarteraTable
 * - filter-manager.js → CarteraFilters
 * - realtime-manager.js → Echo setup (on-demand)
 */

import { OrdenesLoader } from './modules/ordenes-loader.js';
import { initializeCarteraTable } from './modules/table-manager.js';
import { initializeFilters } from './modules/filter-manager.js';
import { initializeRealtime } from './modules/realtime-manager.js';
import { initializeInvoiceManager } from './modules/invoice-manager.js';
import { initPerformanceMonitor, getPerformanceMonitor } from './modules/performance-monitor.js';

const initState = {
    isReady: false,
    initializedAt: null,
    module: 'supervisor-pedidos',
    modules: {
        table: false,
        filters: false,
        realtime: false,
        invoice: false,
    },
};

/**
 * Detectar si estamos en vista de supervisor
 */
function isSupervisorView() {
    const module = document.body?.dataset?.module || '';
    return module === 'supervisor-pedidos';
}

/**
 * Inicializar tabla de cartera
 */
async function initTable() {
    try {
        await initializeCarteraTable();
        initState.modules.table = true;
        console.log('[SP] ✅ Tabla inicializada');
    } catch (error) {
        console.error('[SP] ❌ Error en tabla:', error);
        throw error;
    }
}

/**
 * Inicializar filtros
 */
async function initFilters() {
    try {
        await initializeFilters();
        initState.modules.filters = true;
        console.log('[SP] ✅ Filtros inicializados');
    } catch (error) {
        console.error('[SP] ❌ Error en filtros:', error);
        throw error;
    }
}

/**
 * Lazy-load realtime solo si es necesario
 */
async function initRealtimeIfNeeded() {
    const userRole = document.body?.dataset?.userRole || '';
    const needsRealtime = ['supervisor_pedidos', 'asesor'].includes(userRole);

    if (!needsRealtime) {
        console.log('[SP] Realtime no necesario para rol:', userRole);
        return;
    }

    try {
        await initializeRealtime();
        initState.modules.realtime = true;
        console.log('[SP] ✅ Realtime inicializado');
    } catch (error) {
        console.error('[SP] ⚠️ Realtime error (continuando):', error);
        // No lanzar error, realtime es opcional
    }
}

/**
 * Lazy-load invoice manager solo si hay facturas
 * Se ejecuta DESPUÉS de que la tabla cargue datos
 */
async function initInvoiceIfNeeded() {
    try {
        // Esperar un tick para que la tabla se renderice
        await new Promise(resolve => setTimeout(resolve, 100));

        const manager = await initializeInvoiceManager();
        if (manager) {
            initState.modules.invoice = true;
            console.log('[SP] ✅ Invoice manager inicializado');
        }
    } catch (error) {
        console.error('[SP] ⚠️ Invoice error (continuando):', error);
        // No lanzar error, invoice es opcional
    }
}

/**
 * Inicializador principal
 */
async function initSupervisorPedidos() {
    // Inicializar monitor de performance al inicio
    const perfMonitor = initPerformanceMonitor();
    perfMonitor.mark('SUPERVISOR_PEDIDOS_INIT_START');

    if (initState.isReady) {
        console.warn('[SP] Ya inicializado, skip');
        return;
    }

    if (!isSupervisorView()) {
        console.log('[SP] Módulo no activo');
        return;
    }

    try {
        console.log('[SP] 🚀 Iniciando...');
        perfMonitor.mark('SUPERVISOR_VIEW_DETECTED');

        // Cargar tabla y filtros en paralelo (son independientes)
        perfMonitor.mark('STARTING_TABLE_AND_FILTERS');
        await Promise.all([
            initTable(),
            initFilters(),
        ]);
        perfMonitor.mark('TABLE_AND_FILTERS_READY');

        // Realtime: bajo demanda, no bloquea
        perfMonitor.mark('STARTING_REALTIME_CHECK');
        await initRealtimeIfNeeded();
        perfMonitor.mark('REALTIME_READY');

        // Invoice: bajo demanda, se carga después de tabla
        perfMonitor.mark('STARTING_INVOICE_CHECK');
        await initInvoiceIfNeeded();
        perfMonitor.mark('INVOICE_READY');

        // Mark as ready
        initState.isReady = true;
        initState.initializedAt = new Date().toISOString();

        perfMonitor.mark('MODULE_FULLY_READY');

        // Exponer para debugging
        globalThis.supervisorPedidosState = Object.freeze({ ...initState });
        globalThis.supervisorPedidosMonitor = perfMonitor;

        // Event para testing
        document.dispatchEvent(new CustomEvent('supervisor-pedidos:ready', {
            detail: initState,
        }));

        console.log('[SP] ✅ Module ready', initState);
        console.log('[PERF] Typing: perfMonitor.report() in console for details');
    } catch (error) {
        console.error('[SP] ❌ Initialization failed:', error);
        // No re-throw, dejar que el usuario vea la página aunque algo falló
    }
}

// ============================================
// AUTO-INICIALIZACIÓN
// ============================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Pequeño delay para que Blade renderice completamente
        setTimeout(initSupervisorPedidos, 100);
        // Inicializar cargador de órdenes (tabla optimizada con API)
        // Mayor delay para asegurar que el DOM esté completamente listo
        setTimeout(() => {
            if (document.querySelector('[data-ordenes-body]')) {
                window.ordenesLoader = new OrdenesLoader({ perPage: 15 });
            } else {
                console.warn('[SP] Contenedor de órdenes no encontrado');
            }
        }, 300);
    });
} else {
    setTimeout(initSupervisorPedidos, 100);
    setTimeout(() => {
        console.log('[SP] Intentando inicializar OrdenesLoader...');
        const container = document.querySelector('[data-ordenes-body]');
        console.log('[SP] Contenedor encontrado:', !!container);
        if (container) {
            try {
                window.ordenesLoader = new OrdenesLoader({ perPage: 15 });
                console.log('[SP] OrdenesLoader inicializado exitosamente');
            } catch (error) {
                console.error('[SP] Error al inicializar OrdenesLoader:', error);
            }
        } else {
            console.warn('[SP] Contenedor [data-ordenes-body] no encontrado');
        }
    }, 300);
}

// Exportar para testing/debugging
export {
    initSupervisorPedidos,
    isSupervisorView,
    initState,
};
