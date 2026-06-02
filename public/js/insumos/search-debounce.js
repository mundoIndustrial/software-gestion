/**
 * Search Debounce - Live Search sin URL y sin recargar la pagina
 *
 * Implementa busqueda en tiempo real mientras escribe (con debounce)
 * Actualiza la tabla por AJAX sin recargar la pagina
 * Sin usar parametros URL - estado local puro
 */

const SearchDebounce = {
    searchInput: null,
    clearBtn: null,
    debounceTimer: null,
    debounceDelay: 150, // ms - busqueda instantanea
    isSearching: false,
    currentSearch: '',
    pendingSearchValue: null,
    requestCounter: 0,

    /**
     * Inicializa los listeners del buscador
     */
    init() {
        this.searchInput = document.querySelector('input[name="search"]');

        if (!this.searchInput) {
            console.warn('[Search] Input de busqueda no encontrado');
            return false;
        }

        // Crear contenedor para el boton X si no existe
        this.setupClearButton();

        // Buscar mientras escribe (con debounce)
        this.searchInput.addEventListener('input', (e) => {
            const searchValue = e.target.value.trim();
            console.log('[Search] Input detectado:', searchValue);

            // Mostrar/ocultar boton X
            this.updateClearButton();

            // Limpiar el debounce anterior
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            // Si esta vacio, buscar inmediatamente
            if (searchValue === '') {
                this.doSearchAjax();
                return;
            }

            // Si hay texto, buscar con debounce rapido (150ms)
            this.debounceTimer = setTimeout(() => {
                console.log('[Search] Debounce completado, buscando:', searchValue);
                this.doSearchAjax();
            }, this.debounceDelay);
        });

        // Busqueda inmediata al presionar Enter
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

        // Inicializar estado del boton X
        this.updateClearButton();

        console.log('[Search] Inicializado - Busqueda sin URL, instantanea (debounce: ' + this.debounceDelay + 'ms)');
        return true;
    },

    /**
     * Configura el boton de limpiar dentro del input
     */
    setupClearButton() {
        // Crear contenedor del boton X si no existe
        const wrapper = this.searchInput.parentElement;

        // Si el boton ya existe, no lo crear de nuevo
        if (wrapper.querySelector('.search-clear-btn')) {
            this.clearBtn = wrapper.querySelector('.search-clear-btn');
            return;
        }

        // Crear boton X
        this.clearBtn = document.createElement('button');
        this.clearBtn.type = 'button';
        this.clearBtn.className = 'search-clear-btn';
        this.clearBtn.innerHTML = 'x';
        this.clearBtn.title = 'Limpiar busqueda';
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
            pointer-events: auto;
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
        wrapper.style.isolation = 'isolate';
        wrapper.appendChild(this.clearBtn);
    },

    /**
     * Actualiza la visibilidad del boton X
     */
    updateClearButton() {
        if (!this.clearBtn) return;

        const hasValue = this.searchInput.value.trim().length > 0;
        this.clearBtn.style.display = hasValue ? 'flex' : 'none';
    },

    /**
     * Limpia la busqueda
     */
    clearSearch() {
        console.log('[Search] Limpiando busqueda');
        this.searchInput.value = '';
        // Forzar refresh aunque el valor quede vacio.
        this.currentSearch = null;
        this.updateClearButton();

        // Buscar sin parametro (mostrar todo)
        this.doSearchAjax();

        // Enfocar el input
        this.searchInput.focus();
    },

    /**
     * Realiza la busqueda por AJAX sin recargar la pagina
     */
    async doSearchAjax() {
        if (this.isSearching) {
            this.pendingSearchValue = this.searchInput ? this.searchInput.value.trim() : '';
            console.log('[Search] Ya hay una busqueda en progreso, encolando siguiente termino:', this.pendingSearchValue);
            return;
        }

        const searchValue = this.searchInput.value.trim();
        const traceId = this.generateTraceId(searchValue);
        const startedAt = performance.now();

        if (searchValue === this.currentSearch) {
            console.log(`[Search][${traceId}] El valor ya fue buscado, ignorando...`, {
                searchValue,
                currentSearch: this.currentSearch,
            });
            return;
        }

        this.currentSearch = searchValue;
        this.isSearching = true;

        this.logTableSnapshot('ANTES');
        console.log(`[Search][${traceId}] Iniciando busqueda AJAX`, {
            searchValue,
            pathname: window.location.pathname,
            activeFilters: typeof activeFilters !== 'undefined' ? activeFilters : {},
        });

        try {
            // Partir de la URL actual para conservar filtros como area=ANULADO
            const ajaxUrl = new URL(window.location.href);
            const ajaxParams = ajaxUrl.searchParams;
            ajaxParams.set('page', '1');
            const tipoRecibo = globalThis.tipoRecibo || 'COSTURA';
            ajaxParams.set('tipo_recibo', tipoRecibo);
            if (searchValue) {
                ajaxParams.set('search', searchValue);
            } else {
                ajaxParams.delete('search');
            }

            // Agregar filtros activos si existen
            if (typeof activeFilters !== 'undefined' && Object.keys(activeFilters).length > 0) {
                for (const [column, values] of Object.entries(activeFilters)) {
                    if (values.length > 0) {
                        values.forEach((val) => {
                            ajaxParams.append('filter_columns[]', column);
                            ajaxParams.append('filter_values[]', val);
                        });
                    }
                }
            }

            console.log(`[Search][${traceId}] URL AJAX`, ajaxUrl);

            // Enviar peticion AJAX
            const response = await fetch(ajaxUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                    'X-Insumos-Trace-Id': traceId,
                },
            });

            const fetchDurationMs = Math.round(performance.now() - startedAt);
            console.log(`[Search][${traceId}] Respuesta AJAX`, {
                ok: response.ok,
                status: response.status,
                statusText: response.statusText,
                durationMs: fetchDurationMs,
                contentType: response.headers.get('content-type'),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            // Parsear HTML
            const html = await response.text();
            console.log(`[Search][${traceId}] HTML recibido`, {
                htmlLength: html.length,
                hasTable: html.includes('<table'),
                hasRows: html.includes('<tr'),
            });

            // Actualizar tabla usando la funcion del filter manager
            if (typeof updateTableFromHtml === 'function') {
                updateTableFromHtml(html);
            } else {
                console.warn(`[Search][${traceId}] updateTableFromHtml no esta disponible`);
            }

            // Actualizar URL sin parametro de busqueda (solo estado local)
            window.history.replaceState(
                { search: searchValue, filters: typeof activeFilters !== 'undefined' ? activeFilters : {} },
                '',
                (() => {
                    const urlWithState = new URL(window.location.href);
                    urlWithState.searchParams.set('tipo_recibo', tipoRecibo);
                    urlWithState.searchParams.set('page', '1');
                    if (searchValue) {
                        urlWithState.searchParams.set('search', searchValue);
                    } else {
                        urlWithState.searchParams.delete('search');
                    }
                    return urlWithState.toString();
                })()
            );

            setTimeout(() => {
                this.logTableSnapshot('DESPUES');
                console.log(`[Search][${traceId}] Busqueda completada`, {
                    searchValue,
                    totalDurationMs: Math.round(performance.now() - startedAt),
                });
            }, 0);
        } catch (error) {
            console.error(`[Search][${traceId}] Error en busqueda AJAX`, error);
        } finally {
            this.isSearching = false;

            const nextValue = this.pendingSearchValue;
            this.pendingSearchValue = null;
            if (nextValue !== null && nextValue !== this.currentSearch) {
                console.log('[Search] Ejecutando termino encolado:', nextValue);
                this.doSearchAjax();
            }
        }
    },

    generateTraceId(searchValue) {
        this.requestCounter += 1;
        const normalized = (searchValue || 'empty').toString().slice(0, 12).replace(/\s+/g, '_');
        return `insumos-search-${Date.now()}-${this.requestCounter}-${normalized}`;
    },

    logTableSnapshot(stage) {
        try {
            const rows = Array.from(document.querySelectorAll('tbody tr'));
            const sampleRows = rows.slice(0, 5).map((row) => {
                const cells = row.querySelectorAll('td');
                const reciboCell = cells[1] ? cells[1].textContent.trim() : null;
                return {
                    dataPedido: row.dataset.pedido || null,
                    dataRecibo: row.dataset.recibo || null,
                    reciboCell,
                };
            });

            console.log(`[Search] Snapshot tabla ${stage}`, {
                totalRows: rows.length,
                sampleRows,
            });
        } catch (e) {
            console.warn('[Search] No se pudo generar snapshot de tabla', e);
        }
    },
};

// Inicializar cuando el DOM este listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        SearchDebounce.init();
    });
} else {
    SearchDebounce.init();
}

// Reinicializar busqueda cuando se actualiza la tabla via AJAX
document.addEventListener('insumosTableUpdated', function () {
    console.log('[Search] Actualizando busqueda despues de cambios en tabla');
    SearchDebounce.updateClearButton();
});
