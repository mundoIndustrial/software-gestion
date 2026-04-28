/**
 * Filter Manager - Gestiona inicializacion de filtros
 *
 * Importa el modulo ES6 de filtros (cartera-filters.js)
 */

export async function initializeFilters() {
    try {
        // En supervisor-pedidos index no existen los nodos de filtros de cartera.
        // Si no hay nodos esperados, no bloquear la inicializacion general.
        const hasCarteraFilterNodes =
            !!document.getElementById('filtroFechaInput') ||
            !!document.getElementById('filterBtnDate') ||
            !!document.querySelector('[data-column]');

        if (!hasCarteraFilterNodes) {
            console.log('[FilterManager] Cartera filters skipped (nodos no presentes)');
            return null;
        }

        const { initializeFilters: initFilters } = await import('./cartera-filters.js');
        await initFilters();
        console.log('[FilterManager] Filters initialized');
        return true;
    } catch (error) {
        console.error('[FilterManager] Error initializing filters:', error);
        throw error;
    }
}
