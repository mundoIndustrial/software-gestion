/**
 * Search Debounce - Solamente buscar con botón o Enter
 * 
 * Implementa búsqueda SOLO cuando:
 * - Usuario presiona Enter
 * - Usuario hace click en botón "Buscar"
 * 
 * Sin búsqueda automática mientras escribe
 */

const SearchDebounce = {
    searchInput: null,
    searchButton: null,
    
    /**
     * Inicializa los listeners del buscador
     */
    init() {
        this.searchInput = document.querySelector('input[name="search"]');
        this.searchButton = document.querySelector('button[type="submit"]');
        
        if (!this.searchInput) {
            console.warn('[Search] Input de búsqueda no encontrado');
            return false;
        }
        
        // Buscar al presionar Enter
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.doSearch();
            }
        });
        
        // Buscar al hacer click en el botón (se hace automáticamente, pero agregamos log)
        if (this.searchButton) {
            this.searchButton.addEventListener('click', () => {
                console.log('[Search] Búsqueda iniciada por click en botón');
            });
        }
        
        console.log('[Search] Inicializado - Solo busca con Enter o click en botón');
        return true;
    },

    /**
     * Realiza la búsqueda enviando el formulario
     */
    doSearch() {
        const form = this.searchInput.closest('form');
        if (form) {
            console.log(`[Search] Buscando: "${this.searchInput.value}"`);
            form.submit();
        }
    }
};

/**
 * Inicializar cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', function() {
    SearchDebounce.init();
});

/**
 * Exportar para uso en otros módulos
 */
window.SearchDebounce = SearchDebounce;
