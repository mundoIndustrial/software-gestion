/**
 * HISTORIAL LOGOS SEARCH
 * Maneja la búsqueda de clientes en la vista de historial de logos
 * Se integra con la barra de búsqueda del layout visualizador-logo
 */

const HistorialLogosSearch = {
    config: {
        debounceDelay: 300,
        minChars: 1,
        resultsPerPage: 9
    },

    state: {
        allClientes: [],
        filteredClientes: [],
        isSearchActive: false,
        currentQuery: '',
        debounceTimer: null,
        originalHTML: ''
    },

    /**
     * Inicializar el módulo de búsqueda
     */
    initialize() {
        // Solo inicializar si estamos en la página de historial-logos
        if (!this.isHistorialLogosPage()) {
            console.log('[HistorialLogosSearch] No estamos en historial-logos, abortando inicialización');
            return;
        }

        console.log('[HistorialLogosSearch] Inicializando en historial-logos...');
        
        // Buscar input de búsqueda (el layout visualizador-logo usa #search-input)
        const searchInput = document.getElementById('search-input');
        if (!searchInput) {
            console.error('[HistorialLogosSearch] Input de búsqueda (#search-input) no encontrado');
            return;
        }

        console.log('[HistorialLogosSearch] Input encontrado:', searchInput);

        // Configurar event listeners
        searchInput.addEventListener('input', (e) => this.handleSearchInput(e));
        searchInput.addEventListener('keypress', (e) => this.handleKeyPress(e));
        searchInput.addEventListener('focus', () => console.log('[HistorialLogosSearch] Input enfocado'));

        // Configurar botón de limpiar
        const clearBtn = document.getElementById('clear-search');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('[HistorialLogosSearch] Botón limpiar presionado');
                this.limpiarBusqueda();
            });
        } else {
            console.warn('[HistorialLogosSearch] Botón clear-search no encontrado');
        }

        // Guardar HTML original del grid
        const grid = document.getElementById('clientesGrid');
        if (grid) {
            this.state.originalHTML = grid.innerHTML;
        }

        // Cargar todos los clientes
        this.cargarTodosLosClientes();

        console.log('[HistorialLogosSearch] Inicialización completada');
    },

    /**
     * Verificar si estamos en la página de historial-logos
     */
    isHistorialLogosPage() {
        return window.location.pathname.includes('historial-logos');
    },

    /**
     * Cargar todos los clientes (extraer del DOM)
     */
    cargarTodosLosClientes() {
        try {
            console.log('[HistorialLogosSearch] Extrayendo clientes del DOM...');
            
            // Obtener todos los cards de clientes del DOM actual
            const clientCards = document.querySelectorAll('[data-cliente-id]');
            
            console.log(`[HistorialLogosSearch] Se encontraron ${clientCards.length} cards en el DOM`);

            this.state.allClientes = Array.from(clientCards).map(card => ({
                id: card.dataset.clienteId,
                nombre: card.dataset.clienteNombre || card.textContent.toLowerCase(),
                cantidad: card.dataset.cantidadLogos || 0,
                element: card
            }));

            console.log(`[HistorialLogosSearch] Se cargaron ${this.state.allClientes.length} clientes:`, this.state.allClientes);
        } catch (error) {
            console.error('[HistorialLogosSearch] Error al cargar clientes del DOM:', error);
        }
    },

    /**
     * Manejar input de búsqueda
     */
    handleSearchInput(e) {
        const query = e.target.value.trim().toLowerCase();

        // Mostrar/ocultar botón de limpiar
        const clearBtn = document.getElementById('clear-search');
        if (clearBtn) {
            clearBtn.style.display = query ? 'block' : 'none';
        }

        // Debounce la búsqueda
        clearTimeout(this.state.debounceTimer);
        
        if (!query) {
            // Si está vacío, restaurar todos los clientes
            this.state.isSearchActive = false;
            this.state.currentQuery = '';
            this.mostrarTodosLosClientes();
            return;
        }

        this.state.debounceTimer = setTimeout(() => {
            this.realizarBusqueda(query);
        }, this.config.debounceDelay);
    },

    /**
     * Manejar presión de tecla
     */
    handleKeyPress(e) {
        if (e.key === 'Escape') {
            this.limpiarBusqueda();
        }
    },

    /**
     * Realizar búsqueda
     */
    realizarBusqueda(query) {
        console.log(`[HistorialLogosSearch] Buscando: "${query}"`);
        console.log(`[HistorialLogosSearch] Total de clientes en allClientes:`, this.state.allClientes.length);

        if (query.length < this.config.minChars) {
            this.mostrarTodosLosClientes();
            return;
        }

        // Filtrar clientes localmente (sin hacer fetch)
        this.state.filteredClientes = this.state.allClientes.filter(cliente => {
            const nombreMatch = cliente.nombre.toLowerCase().includes(query);
            const idMatch = cliente.id.toString().includes(query);
            console.log(`[HistorialLogosSearch] Cliente: ${cliente.nombre} (${cliente.id}) - Nombre match: ${nombreMatch}, ID match: ${idMatch}`);
            return nombreMatch || idMatch;
        });

        this.state.isSearchActive = true;
        this.state.currentQuery = query;

        console.log(`[HistorialLogosSearch] Resultados encontrados: ${this.state.filteredClientes.length}`);

        // Mostrar clientes filtrados
        this.mostrarClientesFiltrados();
    },

    /**
     * Mostrar todos los clientes
     */
    mostrarTodosLosClientes() {
        console.log('[HistorialLogosSearch] Mostrando todos los clientes');

        const grid = document.getElementById('clientesGrid');
        if (!grid) return;

        // Mostrar todos los cards
        const cards = grid.querySelectorAll('[data-cliente-id]');
        cards.forEach(card => {
            card.style.display = '';
        });

        // Ocultar mensaje de "no hay resultados"
        const noResults = grid.querySelector('.sin-resultados');
        if (noResults) {
            noResults.remove();
        }

        // Mostrar paginación original
        this.mostrarPaginacionOriginal();
    },

    /**
     * Mostrar clientes filtrados
     */
    mostrarClientesFiltrados() {
        const grid = document.getElementById('clientesGrid');
        if (!grid) {
            console.error('[HistorialLogosSearch] Grid no encontrado');
            return;
        }

        console.log('[HistorialLogosSearch] Mostrando clientes filtrados en grid');

        if (this.state.filteredClientes.length === 0) {
            console.log('[HistorialLogosSearch] No hay resultados filtrados');
            
            // Mostrar mensaje de "no hay resultados"
            let noResults = grid.querySelector('.sin-resultados');
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'sin-resultados';
                noResults.style.cssText = `
                    grid-column: 1 / -1;
                    padding: 3rem 2rem;
                    text-align: center;
                    color: #64748b;
                    background: white;
                    border-radius: 12px;
                    border: 1px dashed #cbd5e1;
                `;
                noResults.innerHTML = `
                    <div style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;">
                        <span class="material-symbols-rounded" style="font-size: 3.5rem;">search_off</span>
                    </div>
                    <p style="margin: 0; font-size: 1rem; font-weight: 500;">
                        No se encontraron clientes para "${this.state.currentQuery}"
                    </p>
                    <p style="margin: 0.5rem 0 0; font-size: 0.9rem; color: #94a3b8;">
                        Intenta con otro nombre o número de cliente.
                    </p>
                `;
                grid.appendChild(noResults);
            }

            // Ocultar todos los cards
            const cards = grid.querySelectorAll('[data-cliente-id]');
            console.log(`[HistorialLogosSearch] Ocultando ${cards.length} cards`);
            cards.forEach(card => {
                card.style.display = 'none';
            });

            return;
        }

        console.log(`[HistorialLogosSearch] Mostrando ${this.state.filteredClientes.length} clientes`);

        // Obtener IDs de clientes filtrados
        const filteredIds = new Set(this.state.filteredClientes.map(c => c.id));
        console.log('[HistorialLogosSearch] IDs filtrados:', Array.from(filteredIds));

        // Mostrar/ocultar cards
        const cards = grid.querySelectorAll('[data-cliente-id]');
        console.log(`[HistorialLogosSearch] Total de cards en DOM: ${cards.length}`);
        
        let visiblesCount = 0;
        cards.forEach(card => {
            const clienteId = card.dataset.clienteId;
            const shouldShow = filteredIds.has(clienteId);
            card.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visiblesCount++;
        });

        console.log(`[HistorialLogosSearch] Cards mostradas: ${visiblesCount}`);

        // Ocultar mensaje de "no hay resultados" si existe
        const noResults = grid.querySelector('.sin-resultados');
        if (noResults) {
            noResults.remove();
        }

        // Ocultar paginación durante búsqueda
        this.ocultarPaginacion();
    },

    /**
     * Ocultar paginación
     */
    ocultarPaginacion() {
        const paginationNav = document.querySelector('nav[style*="display: flex; justify-content: center;"]');
        if (paginationNav) {
            paginationNav.style.display = 'none';
        }
    },

    /**
     * Mostrar paginación original
     */
    mostrarPaginacionOriginal() {
        const paginationNav = document.querySelector('nav[style*="display: flex; justify-content: center;"]');
        if (paginationNav) {
            paginationNav.style.display = 'flex';
        }
    },

    /**
     * Limpiar búsqueda
     */
    limpiarBusqueda() {
        const searchInput = document.getElementById('search-input');
        const clearBtn = document.getElementById('clear-search');

        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }

        if (clearBtn) {
            clearBtn.style.display = 'none';
        }

        this.state.isSearchActive = false;
        this.state.currentQuery = '';
        this.state.filteredClientes = [];

        this.mostrarTodosLosClientes();
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[HistorialLogosSearch] DOM está listo, inicializando...');
        HistorialLogosSearch.initialize();
    });
} else {
    // Si el DOM ya está cargado (script se carga al final)
    console.log('[HistorialLogosSearch] DOM ya estaba cargado, inicializando directamente...');
    HistorialLogosSearch.initialize();
}

// Exponer globalmente
window.HistorialLogosSearch = HistorialLogosSearch;
