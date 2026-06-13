/**
 * SEARCH HANDLER - Lavandería
 * Maneja la búsqueda y autocomplete de recibos
 */

class SearchHandler {
    constructor(apiSearchUrl) {
        this.apiSearchUrl = apiSearchUrl;
        this.currentRecibo = null;
        this.reciboCache = {};
    }

    /**
     * Maneja el evento de búsqueda
     */
    handleSearch(e) {
        const query = e.target.value.trim();
        const searchInput = document.getElementById('searchRecibo');
        const results = searchInput.closest('.search-wrapper').querySelector('.autocomplete-results');
        
        if (query.length < 1) {
            results.classList.remove('active');
            return;
        }

        this.searchRecibos(query);
    }

    /**
     * Busca recibos por número
     */
    searchRecibos(query) {
        const searchInput = document.getElementById('searchRecibo');
        const results = searchInput.closest('.search-wrapper').querySelector('.autocomplete-results');
        
        results.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8;">Buscando...</div>';
        results.classList.add('active');

        // Obtener el tipo de movimiento seleccionado
        const tipoMovimientoSelect = document.getElementById('selectTipoMovimiento');
        const tipoMovimiento = tipoMovimientoSelect ? tipoMovimientoSelect.value : 'SALIDA';

        // Construir URL con parámetros
        const url = new URL(this.apiSearchUrl, window.location.origin);
        url.searchParams.set('q', query);
        url.searchParams.set('tipo', tipoMovimiento);

        fetch(url.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    this.renderSearchResults(data.data);
                } else {
                    results.innerHTML = '<div style="padding: 12px; text-align: center; color: #94a3b8;">No se encontraron recibos</div>';
                }
            })
            .catch(error => {
                console.error('Error en búsqueda:', error);
                results.innerHTML = '<div style="padding: 12px; text-align: center; color: #ef4444;">Error al buscar</div>';
            });
    }

    /**
     * Renderiza los resultados de búsqueda
     */
    renderSearchResults(recibos) {
        const searchInput = document.getElementById('searchRecibo');
        const results = searchInput.closest('.search-wrapper').querySelector('.autocomplete-results');
        
        // Almacenar los datos en memoria en lugar de en atributos HTML
        this.reciboCache = {};
        recibos.forEach(recibo => {
            this.reciboCache[recibo.id] = recibo;
        });
        
        results.innerHTML = recibos.map(recibo => {
            // Determinar color según tipo de recibo
            let colorTipo = '#2450ef'; // Azul para COSTURA
            let bgColorTipo = '#f0f4ff';
            
            if (recibo.tipo_recibo === 'BODEGA') {
                colorTipo = '#059669'; // Verde para BODEGA
                bgColorTipo = '#f0fdf4';
            }
            
            return `
            <div class="autocomplete-item" data-recibo-id="${recibo.id}">
                <div style="display: flex; justify-content: space-between; align-items: start; gap: 8px;">
                    <div style="flex: 1;">
                        <strong style="color: #1e293b; display: block; margin-bottom: 2px;">
                            Recibo #${recibo.numero_recibo}-<span style="color: ${colorTipo}; font-weight: 700;">${recibo.tipo_recibo}</span>
                        </strong>
                        <small style="color: #64748b; display: block; margin-bottom: 4px;">
                            ${recibo.cliente}
                        </small>
                        <small style="color: #94a3b8; display: block;">
                            ${recibo.prenda}
                        </small>
                    </div>
                    <span style="background: ${bgColorTipo}; color: ${colorTipo}; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; white-space: nowrap;">
                        ${recibo.cantidad_total} prendas
                    </span>
                </div>
            </div>
        `;
        }).join('');
        
        const rect = searchInput.getBoundingClientRect();
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        
        results.style.position = 'fixed';
        results.style.top = (rect.bottom + scrollTop) + 'px';
        results.style.left = rect.left + 'px';
        results.style.width = rect.width + 'px';
        results.classList.add('active');

        document.querySelectorAll('.autocomplete-item').forEach(item => {
            item.addEventListener('click', () => this.selectRecibo(item));
        });
    }

    /**
     * Selecciona un recibo del autocomplete
     */
    selectRecibo(item) {
        const searchInput = document.getElementById('searchRecibo');
        const results = searchInput.closest('.search-wrapper').querySelector('.autocomplete-results');
        
        const reciboId = parseInt(item.dataset.reciboId, 10);
        const reciboData = this.reciboCache[reciboId];
        
        if (!reciboData) {
            console.error('Recibo no encontrado en cache:', reciboId);
            return;
        }
        
        this.currentRecibo = reciboData;

        searchInput.value = `Recibo #${reciboData.numero_recibo}`;
        results.classList.remove('active');

        // Disparar evento personalizado
        window.dispatchEvent(new CustomEvent('reciboSelected', { detail: reciboData }));
    }

    /**
     * Limpia la búsqueda
     */
    clearSearch() {
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) searchInput.value = '';
        
        const results = searchInput.closest('.search-wrapper').querySelector('.autocomplete-results');
        results.classList.remove('active');
        if (document.getElementById('reciboInfo')) {
            document.getElementById('reciboInfo').style.display = 'none';
        }
    }
}

export { SearchHandler };
