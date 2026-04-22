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

import { initializeCarteraTable } from './modules/table-manager.js';
import { initializeFilters } from './modules/filter-manager.js';
import { initializeRealtime } from './modules/realtime-manager.js';

const initState = {
    isReady: false,
    initializedAt: null,
    module: 'supervisor-pedidos',
    modules: {
        table: false,
        filters: false,
        realtime: false,
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
 * Inicializador principal
 */
async function initSupervisorPedidos() {
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

        // Cargar tabla y filtros en paralelo (son independientes)
        await Promise.all([
            initTable(),
            initFilters(),
        ]);

        // Realtime: bajo demanda, no bloquea
        await initRealtimeIfNeeded();

        // Mark as ready
        initState.isReady = true;
        initState.initializedAt = new Date().toISOString();

        // Exponer para debugging
        globalThis.supervisorPedidosState = Object.freeze({ ...initState });

        // Event para testing
        document.dispatchEvent(new CustomEvent('supervisor-pedidos:ready', {
            detail: initState,
        }));

        console.log('[SP] ✅ Module ready', initState);
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
    });
} else {
    setTimeout(initSupervisorPedidos, 100);
}

// Exportar para testing/debugging
export {
    initSupervisorPedidos,
    isSupervisorView,
    initState,
};
