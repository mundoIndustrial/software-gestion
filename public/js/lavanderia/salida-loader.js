/**
 * SALIDA LOADER - Lavandería
 * Maneja la carga de movimientos de salida y sus prendas para entrada
 */

class SalidaLoader {
    constructor(apiSearchUrl) {
        this.apiSearchUrl = apiSearchUrl;
        this.movimientosSalida = [];
        this.currentSalidaSelected = null;
        this.currentSalidaPrendas = [];
        this.movimientosIndex = {}; // Para búsqueda rápida
    }

    /**
     * Cargar movimientos de salida desde el servidor
     */
    async loadMovimientosSalida() {
        try {
            const apiUrl = this.apiSearchUrl.replace('search-recibos', 'movimientos-salida');
            const response = await fetch(apiUrl);
            const data = await response.json();

            if (data.success) {
                this.movimientosSalida = data.data || [];
                this.buildIndex();
                this.setupSearchInput();
            } else {
                console.error('[SalidaLoader] Error al cargar movimientos:', data.message);
                this.movimientosSalida = [];
                this.setupSearchInput();
            }
        } catch (error) {
            console.error('[SalidaLoader] Error al conectar con API:', error);
            this.movimientosSalida = [];
            this.setupSearchInput();
        }
    }

    /**
     * Construir índice para búsqueda rápida
     */
    buildIndex() {
        this.movimientosIndex = {};
        this.movimientosSalida.forEach(mov => {
            this.movimientosIndex[mov.id] = mov;
        });
    }

    /**
     * Configurar el input de búsqueda
     */
    setupSearchInput() {
        const input = document.getElementById('searchMovimientoSalida');
        const resultsContainer = document.getElementById('autocompleteMovimientosSalida');
        
        if (!input) return;

        input.addEventListener('input', (e) => {
            const query = e.target.value.trim().toLowerCase();
            
            if (query.length === 0) {
                resultsContainer.classList.remove('active');
                resultsContainer.innerHTML = '';
                return;
            }

            // Filtrar movimientos por número
            const filtered = this.movimientosSalida.filter(mov => {
                const numeroStr = String(mov.numero_movimiento).toLowerCase();
                return numeroStr.includes(query);
            });

            this.renderSearchResults(filtered, resultsContainer);
        });

        input.addEventListener('blur', () => {
            setTimeout(() => {
                resultsContainer.classList.remove('active');
            }, 200);
        });
    }

    /**
     * Renderizar resultados de búsqueda
     */
    renderSearchResults(movimientos, container) {
        if (movimientos.length === 0) {
            container.innerHTML = '<div style="padding: 12px; color: #94a3b8; text-align: center;">No se encontraron movimientos</div>';
            container.classList.add('active');
            return;
        }

        const html = movimientos.map(mov => `
            <div class="autocomplete-item" data-movimiento-id="${mov.id}" style="
                padding: 12px;
                border-bottom: 1px solid #e2e8f0;
                cursor: pointer;
                transition: background 0.2s;
            " onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">
                    Movimiento #${mov.numero_movimiento}
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    ${mov.fecha_movimiento} • ${mov.recibos_count} recibos, ${mov.prendas_manuales_count} prendas
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
        container.classList.add('active');

        // Agregar event listeners
        container.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const movId = parseInt(item.dataset.movimientoId);
                this.selectMovimiento(movId);
            });
        });
    }

    /**
     * Seleccionar un movimiento
     */
    selectMovimiento(movimientoId) {
        const movimiento = this.movimientosIndex[movimientoId];
        if (!movimiento) return;

        const input = document.getElementById('searchMovimientoSalida');
        if (input) {
            input.value = `#${movimiento.numero_movimiento}`;
        }

        const resultsContainer = document.getElementById('autocompleteMovimientosSalida');
        if (resultsContainer) {
            resultsContainer.classList.remove('active');
        }

        this.loadPrendasFromSalida(movimientoId);
    }

    /**
     * Cargar prendas de un movimiento de salida
     */
    async loadPrendasFromSalida(movimientoId) {
        try {
            const apiUrl = this.apiSearchUrl.replace(
                'search-recibos',
                `movimiento-salida/${movimientoId}/prendas`
            );
            console.log('[SalidaLoader] Cargando prendas desde:', apiUrl);
            const response = await fetch(apiUrl);
            const data = await response.json();

            console.log('[SalidaLoader] Respuesta del servidor:', data);

            if (data.success) {
                this.currentSalidaSelected = movimientoId;
                this.currentSalidaPrendas = data.data || [];
                console.log('[SalidaLoader] Prendas cargadas:', this.currentSalidaPrendas);
                this.dispatchPrendasLoadedEvent();
                window.dispatchEvent(new CustomEvent('showToast', {
                    detail: {
                        title: 'Prendas Cargadas',
                        message: `Se cargaron ${this.currentSalidaPrendas.length} prenda(s) del movimiento de salida`,
                        type: 'success'
                    }
                }));
            } else {
                console.error('[SalidaLoader] Error al cargar prendas:', data.message);
                window.dispatchEvent(new CustomEvent('showToast', {
                    detail: {
                        title: 'Error',
                        message: data.message,
                        type: 'error'
                    }
                }));
            }
        } catch (error) {
            console.error('[SalidaLoader] Error al conectar con API:', error);
            window.dispatchEvent(new CustomEvent('showToast', {
                detail: {
                    title: 'Error',
                    message: 'Error al cargar prendas del movimiento',
                    type: 'error'
                }
            }));
        }
    }

    /**
     * Disparar evento personalizado con prendas cargadas
     */
    dispatchPrendasLoadedEvent() {
        window.dispatchEvent(new CustomEvent('prendasSalidaLoaded', {
            detail: {
                movimientoId: this.currentSalidaSelected,
                prendas: this.currentSalidaPrendas
            }
        }));
    }

    /**
     * Obtener prendas actuales
     */
    getPrendas() {
        return this.currentSalidaPrendas;
    }

    /**
     * Limpiar selección
     */
    clear() {
        this.currentSalidaSelected = null;
        this.currentSalidaPrendas = [];
        const input = document.getElementById('searchMovimientoSalida');
        if (input) {
            input.value = '';
        }
        const resultsContainer = document.getElementById('autocompleteMovimientosSalida');
        if (resultsContainer) {
            resultsContainer.classList.remove('active');
            resultsContainer.innerHTML = '';
        }
    }
}

export { SalidaLoader };
