/**
 * SearchManager
 * Responsabilidad: Gestionar búsqueda en tiempo real
 * SOLID: Single Responsibility
 */
const SearchManager = (() => {
    let abortController = null;

    return {
        // Realizar búsqueda AJAX
        performAjaxSearch: async (term, baseRoute) => {
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            const url = new URL(globalThis.location);
            const params = new URLSearchParams(url.search);
            term ? params.set('search', term) : params.delete('search');
            params.set('page', 1);

            try {
                const response = await fetch(`${baseRoute}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: abortController.signal
                });

                const data = await response.json();
                
                if (data.totalDiasCalculados && Object.keys(data.totalDiasCalculados).length > 0) {
                    console.log('📊 Días calculados por pedido:', data.totalDiasCalculados);
                }
                
                return data;
            } catch (error) {
                if (error.name === 'AbortError') {
                    console.log('⚠️ Búsqueda anterior cancelada');
                    return null;
                }
                console.error('Error en búsqueda:', error);
                throw error;
            }
        },

        // Cancelar búsqueda
        cancelSearch: () => {
            if (abortController) {
                abortController.abort();
            }
        }
    };
})();

globalThis.SearchManager = SearchManager;
