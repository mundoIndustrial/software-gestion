/**
 * Table Manager - Gestiona inicialización de tabla de cartera
 *
 * Importa el módulo ES6 de tabla (cartera-table.js)
 */

export async function initializeCarteraTable() {
    try {
        const { initializeCarteraTable: initTable } = await import('./cartera-table.js');
        await initTable();
        console.log('[TableManager] ✅ Table initialized');
    } catch (error) {
        console.error('[TableManager] Error initializing table:', error);
        throw error;
    }
}
