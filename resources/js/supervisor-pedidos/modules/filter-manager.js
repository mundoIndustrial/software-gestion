/**
 * Filter Manager - Gestiona inicialización de filtros
 *
 * Importa el módulo ES6 de filtros (cartera-filters.js)
 */

export async function initializeFilters() {
    try {
        const { initializeFilters: initFilters } = await import('./cartera-filters.js');
        await initFilters();
        console.log('[FilterManager] ✅ Filters initialized');
    } catch (error) {
        console.error('[FilterManager] Error initializing filters:', error);
        throw error;
    }
}
