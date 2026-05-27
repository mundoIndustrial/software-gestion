/**
 * Filter Manager para Insumos/Materiales - SIN URL
 * 
 * Implementa filtrado sin parámetros URL, exactamente como recibos-costura
 * Usa sessionStorage para guardar filtros en memoria
 * Estado local + AJAX para actualizar tabla
 */

// LIMPIAR CUALQUIER RESTO DEL SISTEMA ANTIGUO
if (window.insumosHandlers && window.insumosHandlers.filterManager) {
    console.warn('[FilterManager-NEW] Sistema antiguo detectado, limpiando...');
    window.insumosHandlers.filterManager = null;
}

let activeFilters = {}; // Estado en memoria: { column: [value1, value2, ...] }
const STORAGE_KEY = 'insumos_filters';

/**
 * Inicializa los listeners de filtrado
 */
function initFilterManager() {
    console.log('[FilterManager] Inicializando...');
    
    // Restaurar filtros guardados desde sessionStorage
    restoreFiltersFromStorage();
    
    // Agregar listeners a botones de filtro
    document.addEventListener('click', function(e) {
        const filterBtn = e.target.closest('.filter-btn-insumos');
        if (filterBtn) {
            const column = filterBtn.getAttribute('data-column');
            if (column) {
                openFilterModal(column);
            }
        }
    });
    
    console.log('[FilterManager] Inicializado ✓');
}

/**
 * Restaura filtros desde sessionStorage
 */
function restoreFiltersFromStorage() {
    try {
        const stored = sessionStorage.getItem(STORAGE_KEY);
        if (stored) {
            const restored = JSON.parse(stored);
            activeFilters = restored;
            console.log('[FilterManager] Filtros restaurados desde storage:', activeFilters);
            
            // Actualizar tabla con los filtros restaurados
            applyFiltersToBackend();
            updateFilterBadges();
        }
    } catch (e) {
        console.warn('[FilterManager] Error restaurando filtros:', e);
    }
}

/**
 * Abre modal de filtro para una columna
 */
function openFilterModal(column) {
    console.log('[FilterManager] Abriendo modal para columna:', column);
    
    // Crear modal
    let modal = document.getElementById('filterModalInsumos');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'filterModalInsumos';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        document.body.appendChild(modal);
    }
    
    // Mostrar mensaje de carga
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <p style="text-align: center; color: #999;">Cargando opciones...</p>
        </div>
    `;
    modal.style.display = 'flex';
    
    // Obtener valores del backend (incluye tipo de recibo si está disponible)
    const tipoRecibo = globalThis.tipoRecibo || 'COSTURA';
    const filterUrl = `/insumos/api/filtros/${column}?tipo_recibo=${encodeURIComponent(tipoRecibo)}`;

    fetch(filterUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.valores) {
                renderFilterModal(column, data.valores, modal);
            } else {
                throw new Error('No hay valores disponibles');
            }
        })
        .catch(error => {
            console.error('[FilterManager] Error cargando opciones:', error);
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 500px;">
                    <p style="text-align: center; color: #ef4444;">Error al cargar opciones</p>
                </div>
            `;
        });
}

/**
 * Renderiza el contenido del modal de filtro
 */
function renderFilterModal(column, valores, modal) {
    const columnNames = {
        'numero_pedido': 'N° Pedido',
        'cliente': 'Cliente',
        'estado': 'Estado',
        'area': 'Área',
        'consecutivo_actual': 'N° Recibo',
        'created_at': 'Fecha'
    };
    
    const columnName = columnNames[column] || column;
    const selectedValues = activeFilters[column] || [];
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; font-size: 18px; font-weight: bold;">Filtrar por: ${columnName}</h3>
                <button onclick="document.getElementById('filterModalInsumos').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
            </div>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" id="filterSearchInsumosModal" placeholder="Buscar..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                <button onclick="selectAllFilters('${column}')" style="padding: 10px 12px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">✓ Todos</button>
                <button onclick="deselectAllFilters('${column}')" style="padding: 10px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">✗ Ninguno</button>
            </div>
            
            <div id="filterListInsumos" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">
                ${valores.map(val => `
                    <label style="display: flex; align-items: center; padding: 10px; cursor: pointer;">
                        <input type="checkbox" value="${val}" class="filter-checkbox-item" ${selectedValues.includes(val) ? 'checked' : ''} style="margin-right: 10px;">
                        <span>${val.replaceAll('_', ' ')}</span>
                    </label>
                `).join('')}
                <p style="text-align: center; color: #999; font-size: 12px;">Mostrando ${valores.length} valores</p>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button onclick="applyFiltersFromModal('${column}')" style="flex: 1; padding: 12px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Aplicar Filtro</button>
                <button onclick="document.getElementById('filterModalInsumos').style.display='none'" style="flex: 1; padding: 12px; background: #e5e7eb; color: #333; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancelar</button>
            </div>
        </div>
    `;
    
    // Agregar listener de búsqueda
    const searchInput = document.getElementById('filterSearchInsumosModal');
    if (searchInput) {
        const applyFilterVisibility = () => {
            const searchTerm = String(searchInput.value || '').toLowerCase().trim();
            const checkboxes = modal.querySelectorAll('.filter-checkbox-item');
            checkboxes.forEach(cb => {
                const label = cb.parentElement;
                const text = String(label.textContent || '').toLowerCase();
                label.style.display = text.includes(searchTerm) ? 'flex' : 'none';
            });
        };

        searchInput.addEventListener('input', applyFilterVisibility);
        // Forzar estado inicial visible para evitar interferencias de otros scripts globales.
        applyFilterVisibility();
    }
}

/**
 * Selecciona todos los checkboxes
 */
function selectAllFilters(column) {
    document.querySelectorAll('.filter-checkbox-item').forEach(cb => cb.checked = true);
}

/**
 * Deselecciona todos los checkboxes
 */
function deselectAllFilters(column) {
    document.querySelectorAll('.filter-checkbox-item').forEach(cb => cb.checked = false);
}

/**
 * Aplica filtros desde el modal
 */
function applyFiltersFromModal(column) {
    const selected = Array.from(document.querySelectorAll('.filter-checkbox-item:checked')).map(cb => cb.value);
    
    if (selected.length === 0) {
        delete activeFilters[column];
    } else {
        activeFilters[column] = selected;
    }
    
    // Guardar en sessionStorage
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(activeFilters));
    
    // Aplicar filtros al backend
    applyFiltersToBackend();
    
    // Actualizar UI
    updateFilterBadges();
    
    // Cerrar modal
    document.getElementById('filterModalInsumos').style.display = 'none';
    
    console.log('[FilterManager] Filtros aplicados:', activeFilters);
}

/**
 * Aplica filtros al backend via AJAX
 */
async function applyFiltersToBackend() {
    try {
        // Construir URL limpia (sin parámetros de filtro)
        const baseUrl = window.location.pathname;
        const url = new URL(baseUrl, window.location.origin);
        const tipoRecibo = globalThis.tipoRecibo || 'COSTURA';
        
        // Construir parámetros AJAX
        let ajaxParams = new URLSearchParams();
        ajaxParams.set('page', '1');
        ajaxParams.set('tipo_recibo', tipoRecibo);
        
        // Agregar filtros como parámetros
        for (const [column, values] of Object.entries(activeFilters)) {
            if (values.length > 0) {
                values.forEach(val => {
                    ajaxParams.append('filter_columns[]', column);
                    ajaxParams.append('filter_values[]', val);
                });
            }
        }
        
        const ajaxUrl = url.toString() + (ajaxParams.toString() ? '?' + ajaxParams.toString() : '');
        
        console.log('[FilterManager] 🚀 Enviando AJAX a:', ajaxUrl);
        console.log('[FilterManager] 📦 Parámetros:', {
            filter_columns: Array.from(ajaxParams.entries()).filter(([key]) => key === 'filter_columns[]').map(([,val]) => val),
            filter_values: Array.from(ajaxParams.entries()).filter(([key]) => key === 'filter_values[]').map(([,val]) => val),
        });
        
        const response = await fetch(ajaxUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        });
        
        console.log('[FilterManager] ✓ Response recibida:', { status: response.status, ok: response.ok });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const html = await response.text();
        console.log('[FilterManager]  HTML recibido, longitud:', html.length);
        
        // Actualizar tabla
        if (typeof updateTableFromHtml === 'function') {
            console.log('[FilterManager] 🔄 Llamando updateTableFromHtml...');
            updateTableFromHtml(html);
        } else {
            console.warn('[FilterManager] updateTableFromHtml no disponible');
        }
        
        // Actualizar URL CON parámetros de filtro (para que la paginación los conserve)
        const urlWithFilters = new URL(url);
        urlWithFilters.searchParams.set('tipo_recibo', tipoRecibo);
        for (const [column, values] of Object.entries(activeFilters)) {
            if (values.length > 0) {
                values.forEach(val => {
                    urlWithFilters.searchParams.append('filter_columns[]', column);
                    urlWithFilters.searchParams.append('filter_values[]', val);
                });
            }
        }

        window.history.replaceState(
            { filters: activeFilters },
            '',
            urlWithFilters.toString()
        );

        console.log('[FilterManager] Filtros aplicados exitosamente. URL actualizada con parámetros de filtro.');
        
    } catch (error) {
        console.error('[FilterManager] Error aplicando filtros:', error);
    }
}

/**
 * Limpia todos los filtros
 */
function clearAllTableFilters() {
    console.log('[FilterManager] Limpiando todos los filtros');
    
    activeFilters = {};
    sessionStorage.removeItem(STORAGE_KEY);
    
    // Recargar tabla sin filtros
    applyFiltersToBackend();
    updateFilterBadges();
}

/**
 * Actualiza badges de filtro en botones
 */
function updateFilterBadges() {
    const filterButtons = document.querySelectorAll('.filter-btn-insumos');
    let hasAnyFilters = false;
    
    filterButtons.forEach(btn => {
        const column = btn.getAttribute('data-column');
        
        // Remover badge anterior
        let badge = btn.querySelector('.filter-badge');
        if (badge) badge.remove();
        
        // Agregar badge si hay filtros
        if (activeFilters[column] && activeFilters[column].length > 0) {
            hasAnyFilters = true;
            btn.classList.add('has-filter');
            
            const newBadge = document.createElement('span');
            newBadge.className = 'filter-badge';
            newBadge.textContent = activeFilters[column].length;
            newBadge.style.cssText = `
                display: inline-flex;
                align-items: center;
                justify-content: center;
                position: absolute;
                top: -8px;
                right: -8px;
                background: #ef4444;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 12px;
                font-weight: bold;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            `;
            btn.style.position = 'relative';
            btn.appendChild(newBadge);
        } else {
            btn.classList.remove('has-filter');
        }
    });
    
    // Mostrar/ocultar botón flotante de limpiar
    const clearBtn = document.getElementById('btnClearAllFiltersFloating');
    if (clearBtn) {
        clearBtn.style.display = hasAnyFilters ? 'flex' : 'none';
    }
}

/**
 * Actualiza la tabla desde HTML (respuesta AJAX)
 * Esta función puente hace que updateTableFromHtml sea global
 */
window.updateTableFromHtml = async function(html) {
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // Actualizar tabla
        const newTable = doc.querySelector('table');
        const currentTable = document.querySelector('table');

        if (newTable && currentTable) {
            const newTbody = newTable.querySelector('tbody');
            const currentTbody = currentTable.querySelector('tbody');

            if (newTbody && currentTbody) {
                currentTbody.innerHTML = newTbody.innerHTML;
                console.log('[FilterManager] Tabla actualizada');
            }
        }

        // Actualizar TODA la sección de paginación (#tablePagination contiene la info y los controles)
        const currentPagination = document.querySelector('#tablePagination');
        const newPagination = doc.querySelector('#tablePagination');

        if (currentPagination && newPagination) {
            // Reemplazar TODO el contenido del div de paginación
            currentPagination.innerHTML = newPagination.innerHTML;
            console.log('[FilterManager] Paginación actualizada (info + controles)');
        }

        // Disparar evento para reinicializar
        const event = new CustomEvent('insumosTableUpdated', {
            detail: { action: 'filter' }
        });
        document.dispatchEvent(event);

    } catch (error) {
        console.error('[FilterManager] Error actualizando tabla:', error);
        throw error;
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[FilterManager-NEW] DOMContentLoaded → inicializando');
        initFilterManager();
    });
} else {
    console.log('[FilterManager-NEW] DOM ya cargado → inicializando inmediatamente');
    initFilterManager();
}

// IMPORTANTE: La tabla podría regenerarse por AJAX,
// así que también iniciar cuando se dispare evento personalizado
document.addEventListener('insumosTableUpdated', function(e) {
    console.log('[FilterManager-NEW] Tabla actualizada via AJAX, reinicializando listeners');
    // Los listeners ya están delegados en el document,
    // pero nos aseguramos que los badges se actualicen
    updateFilterBadges();
});

// Reinicializar badges cuando se actualiza la tabla
document.addEventListener('insumosTableUpdated', function(e) {
    console.log('[FilterManager] Actualizando después de cambios en tabla');
    updateFilterBadges();
});
