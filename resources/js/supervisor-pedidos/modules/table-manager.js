/**
 * Table Manager - Gestiona inicializacion de tabla de cartera
 *
 * Importa el modulo ES6 de tabla (cartera-table.js)
 */

export async function initializeCarteraTable() {
    try {
        // En supervisor-pedidos index no existe #tableBody de cartera.
        // Si no esta presente, no bloquear la inicializacion general.
        if (!document.getElementById('tableBody')) {
            console.log('[TableManager] Cartera table skipped (tableBody no presente)');
            return null;
        }

        const { initializeCarteraTable: initTable } = await import('./cartera-table.js');
        await initTable();
        console.log('[TableManager] Table initialized');
        return true;
    } catch (error) {
        console.error('[TableManager] Error initializing table:', error);
        throw error;
    }
}
