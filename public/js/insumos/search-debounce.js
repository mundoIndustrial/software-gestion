/**
 * Search Debounce - Live Search sin URL y sin recargar la página
 * 
 * Implementa búsqueda en tiempo real mientras escribe (con debounce)
 * Actualiza la tabla por AJAX sin recargar la página
 * Sin usar parámetros URL - estado local puro
 */

const SearchDebounce = {
    searchInput: null,
    clearBtn: null,
    debounceTimer: null,
    debounceDelay: 150, // ms - búsqueda instantánea
    isSearching: false,
    currentSearch: '',
    
    /**
     * Inicializa los listeners del buscador
     */
    init() {
        this.searchInput = document.querySelector('input[name="search"]');
        
        if (!this.searchInput) {
            console.warn('[Search] Input de búsqueda no encontrado');
            return false;
        }
        
        // Crear contenedor para el botón X si no existe
        this.setupClearButton();
        
        // Buscar mientras escribe (con debounce)
        this.searchInput.addEventListener('input', (e) => {
            const searchValue = e.target.value.trim();
            console.log('[Search] Input detectado:', searchValue);
            
            // Mostrar/ocultar botón X
            this.updateClearButton();
            
            // Limpiar el debounce anterior
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }
            
            // Si está vacío, buscar inmediatamente
            if (searchValue === '') {
                this.doSearchAjax();
                return;
            }
            
            // Si hay texto, buscar con debounce rápido (150ms)
            this.debounceTimer = setTimeout(() => {
                console.log('[Search] Debounce completado, buscando:', searchValue);
                this.doSearchAjax();
            }, this.debounceDelay);
        });
        
        // Búsqueda inmediata al presionar Enter
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.debounceTimer) {
                    clearTimeout(this.debounceTimer);
                }
                console.log('[Search] Enter presionado');
                this.doSearchAjax();
            }
        });
        
        // Inicializar estado del botón X
        this.updateClearButton();
        
        console.log('[Search] Inicializado - Búsqueda sin URL, instantánea (debounce: ' + this.debounceDelay + 'ms)');
        return true;
    },

    /**
     * Configura el botón de limpiar dentro del input
     */
    setupClearButton() {
        // Crear contenedor del botón X si no existe
        const wrapper = this.searchInput.parentElement;
        
        // Si el botón ya existe, no lo crear de nuevo
        if (wrapper.querySelector('.search-clear-btn')) {
            this.clearBtn = wrapper.querySelector('.search-clear-btn');
            return;
        }
        
        // Crear botón X
        this.clearBtn = document.createElement('button');
        this.clearBtn.type = 'button';
        this.clearBtn.className = 'search-clear-btn';
        this.clearBtn.innerHTML = '×';
        this.clearBtn.title = 'Limpiar búsqueda';
        this.clearBtn.style.cssText = `
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: color 0.2s;
        `;
        
        // Hover effect
        this.clearBtn.addEventListener('mouseenter', () => {
            this.clearBtn.style.color = '#333';
        });
        
        this.clearBtn.addEventListener('mouseleave', () => {
            this.clearBtn.style.color = '#999';
        });
        
        // Click para limpiar
        this.clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.clearSearch();
        });
        
        wrapper.style.position = 'relative';
        wrapper.appendChild(this.clearBtn);
    },

    /**
     * Actualiza la visibilidad del botón X
     */
    updateClearButton() {
        if (!this.clearBtn) return;
        
        const hasValue = this.searchInput.value.trim().length > 0;
        this.clearBtn.style.display = hasValue ? 'flex' : 'none';
    },

    /**
     * Limpia la búsqueda
     */
    clearSearch() {
        console.log('[Search] Limpiando búsqueda');
        this.searchInput.value = '';
        this.currentSearch = '';
        this.updateClearButton();
        
        // Buscar sin parámetro (mostrar todo)
        this.doSearchAjax();
        
        // Enfocar el input
        this.searchInput.focus();
    },

    /**
     * Realiza la búsqueda por AJAX sin recargar la página
     */
    async doSearchAjax() {
        if (this.isSearching) {
            console.log('[Search] Ya hay una búsqueda en progreso, ignorando...');
            return;
        }
        
        const searchValue = this.searchInput.value.trim();
        
        if (searchValue === this.currentSearch) {
            console.log('[Search] El valor ya fue buscado, ignorando...');
            return;
        }
        
        this.currentSearch = searchValue;
        this.isSearching = true;
        
        console.log('[Search] Iniciando búsqueda AJAX:', searchValue);
        
        try {
            // Construir URL SIN parámetro de búsqueda en URL
            // Pero pasar búsqueda como parámetro AJAX
            const url = new URL(window.location.href);
            // Remover parámetros de filtro y búsqueda
            url.searchParams.delete('search');
            url.searchParams.delete('page');
            url.searchParams.set('page', '1');
            
            // Construir parámetros para AJAX
            let ajaxParams = new URLSearchParams();
            ajaxParams.set('page', '1');
            if (searchValue) {
                ajaxParams.set('search', searchValue);
            }
            
            // Agregar filtros activos si existen
            if (typeof activeFilters !== 'undefined' && Object.keys(activeFilters).length > 0) {
                for (const [column, values] of Object.entries(activeFilters)) {
                    if (values.length > 0) {
                        values.forEach(val => {
                            ajaxParams.append('filter_columns[]', column);
                            ajaxParams.append('filter_values[]', val);
                        });
                    }
                }
            }
            
            const ajaxUrl = url.toString() + (ajaxParams.toString() ? '?' + ajaxParams.toString() : '');
            
            console.log('[Search] URL AJAX:', ajaxUrl);
            
            // Enviar petición AJAX
            const response = await fetch(ajaxUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            // Parsear HTML
            const html = await response.text();
            
            // Actualizar tabla usando la función del filter manager
            if (typeof updateTableFromHtml === 'function') {
                updateTableFromHtml(html);
            } else {
                console.warn('[Search] updateTableFromHtml no está disponible');
            }
            
            // Actualizar URL sin parámetro de búsqueda (solo estado local)
            window.history.replaceState(
                { search: searchValue, filters: typeof activeFilters !== 'undefined' ? activeFilters : {} },
                '',
                url.toString()
            );
            
            console.log('[Search] Búsqueda completada:', searchValue);
            
        } catch (error) {
            console.error('[Search] Error en búsqueda AJAX:', error);
        } finally {
            this.isSearching = false;
        }
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        SearchDebounce.init();
    });
} else {
    SearchDebounce.init();
}

// Reinicializar búsqueda cuando se actualiza la tabla via AJAX
document.addEventListener('insumosTableUpdated', function(e) {
    console.log('[Search] Actualizando búsqueda después de cambios en tabla');
    SearchDebounce.updateClearButton();
});
