/**
 * Filter Modal Management - FASE 4a
 * Maneja todo lo relacionado con filtros de tabla en Insumos
 * 
 * Funciones extraídas:
 * - showFilterModal()
 * - renderFilterValues()
 * - selectAllFilters()
 * - deselectAllFilters()
 * - clearAllFilters()
 * - clearAllTableFilters()
 * - applyFilters()
 */

document.addEventListener('DOMContentLoaded', function() {
    let currentFilterColumn = null;
    let currentFilterValues = [];
    let selectedFilters = {};

    // Inicializar event listeners para botones de filtro
    document.querySelectorAll('.filter-btn-insumos').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const column = this.getAttribute('data-column');
            currentFilterColumn = column;
            currentFilterValues = [];
            window.showFilterModal(column, []);
        });
    });

    /**
     * Muestra el modal de filtros con valores disponibles
     */
    window.showFilterModal = function(column, values) {
        // Crear modal si no existe
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
        
        const columnNames = {
            'pedido': 'Pedido',
            'cliente': 'Cliente',
            'estado': 'Estado',
            'area': 'Área',
            'fecha': 'Fecha',
            'fecha_de_creacion_de_orden': 'Fecha de Inicio'
        };

        // Valores predefinidos para ciertos filtros
        const predefinedValues = {
            'area': ['Corte', 'Creación de Orden'],
            'estado': {
                db: ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'pendiente_cartera', 'RECHAZADO_CARTERA', 'DEVUELTO_A_ASESORA'],
                display: ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'Pendiente Supervisor', 'Pendiente Insumos', 'Pendiente Cartera', 'Rechazado Cartera', 'Devuelto a Asesora']
            }
        };

        // Usar valores predefinidos si existen, sino usar los de la tabla
        let displayValues = values;
        if (column === 'estado' && predefinedValues[column]) {
            displayValues = predefinedValues[column].display;
            values = predefinedValues[column].db;
        } else if (predefinedValues[column]) {
            displayValues = predefinedValues[column];
        }
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 24px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; font-size: 18px; font-weight: bold;">Filtrar Insumos por: ${columnNames[column] || column}</h3>
                    <button onclick="document.getElementById('filterModalInsumos').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">×</button>
                </div>
                
                <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: center;">
                    <input type="text" id="filterSearchInsumos" placeholder="Buscar valores..." style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    <button onclick="window.applyFilters()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; white-space: nowrap;">✓ Aplicar</button>
                    <button onclick="window.selectAllFilters()" class="filter-btn-tooltip" data-tooltip="Marcar todos" style="padding: 10px 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);">
                        <i class="fas fa-check-double"></i>
                    </button>
                    <button onclick="window.deselectAllFilters()" class="filter-btn-tooltip" data-tooltip="Desmarcar todos" style="padding: 10px 12px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
                
                <div id="filterListInsumos" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">
                    <p style="text-align: center; color: #999; padding: 20px;">Escribe para buscar valores...</p>
                </div>
            </div>
        `;
        
        modal.style.display = 'flex';
        
        // Agregar tooltips a los botones
        setTimeout(() => {
            document.querySelectorAll('.filter-btn-tooltip').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    const tooltip = this.getAttribute('data-tooltip');
                    const rect = this.getBoundingClientRect();
                    
                    const tooltipEl = document.createElement('div');
                    tooltipEl.textContent = tooltip;
                    tooltipEl.style.cssText = `
                        position: fixed;
                        top: ${rect.top - 40}px;
                        left: ${rect.left + rect.width / 2}px;
                        transform: translateX(-50%);
                        background: #333;
                        color: white;
                        padding: 8px 12px;
                        border-radius: 6px;
                        font-size: 12px;
                        white-space: nowrap;
                        z-index: 10000;
                        pointer-events: none;
                    `;
                    document.body.appendChild(tooltipEl);
                    
                    const removeTooltip = () => {
                        tooltipEl.remove();
                        this.removeEventListener('mouseleave', removeTooltip);
                    };
                    this.addEventListener('mouseleave', removeTooltip);
                });
            });
        }, 100);
        
        // Cargar valores al abrir el modal
        let allValuesLoaded = false;
        let allValues = [];
        const filterList = document.getElementById('filterListInsumos');
        filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">Cargando...</p>';
        
        // Obtener valores del backend
        fetch(`/insumos/api/filtros/${column}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allValues = data.valores;
                    allValuesLoaded = true;
                    window.renderFilterValues(allValues, '', column);
                } else {
                    filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
                }
            })
            .catch(error => {
                filterList.innerHTML = '<p style="text-align: center; color: #f00; padding: 20px;">Error al cargar valores</p>';
            });
        
        // Agregar búsqueda
        document.getElementById('filterSearchInsumos').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            if (allValuesLoaded) {
                window.renderFilterValues(allValues, searchTerm, column);
            }
        });
    };
    
    /**
     * Renderiza los valores de filtro con búsqueda
     */
    window.renderFilterValues = function(values, searchTerm, column) {
        const filterList = document.getElementById('filterListInsumos');
        const urlParams = new URLSearchParams(window.location.search);
        const filterColumns = urlParams.getAll('filter_columns[]') || [];
        const filterValuesArray = urlParams.getAll('filter_values[]') || [];
        
        const estadoMap = {
            'PENDIENTE_SUPERVISOR': 'Pendiente Supervisor',
            'PENDIENTE_INSUMOS': 'Pendiente Insumos',
            'pendiente_cartera': 'Pendiente Cartera',
            'RECHAZADO_CARTERA': 'Rechazado Cartera',
            'DEVUELTO_A_ASESORA': 'Devuelto a Asesora'
        };
        
        const displayMappedValues = values.map(val => {
            if (column === 'estado' && estadoMap[val]) {
                return { db: val, display: estadoMap[val] };
            }
            return { db: val, display: val };
        });
        
        let filteredValues = displayMappedValues.filter(valObj => {
            const valStr = String(valObj.display || '').trim();
            return valStr.length > 0 && valStr.toLowerCase().includes(searchTerm.toLowerCase());
        });
        
        if (filteredValues.length === 0) {
            filterList.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">No se encontraron resultados</p>';
            return;
        }
        
        const displayValues = searchTerm === '' ? filteredValues.slice(0, 15) : filteredValues;
        
        let totalText = '';
        if (searchTerm === '' && filteredValues.length > 15) {
            totalText = `<p style="text-align: center; color: #666; padding: 10px; font-size: 12px;">Mostrando ${Math.min(15, filteredValues.length)} de ${filteredValues.length} valores. Busca para ver más.</p>`;
        }
        
        filterList.innerHTML = totalText + displayValues.map(valObj => {
            const dbVal = String(valObj.db || '').trim();
            const displayVal = String(valObj.display || '').trim();
            
            let isChecked = false;
            filterColumns.forEach((col, idx) => {
                if (col === column && filterValuesArray[idx] === dbVal) {
                    isChecked = true;
                }
            });
            
            return `
                <label style="display: flex; align-items: center; padding: 10px; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                    <input type="checkbox" value="${dbVal}" class="filter-checkbox" ${isChecked ? 'checked' : ''} style="margin-right: 10px; cursor: pointer;">
                    <span style="flex: 1;">${displayVal}</span>
                </label>
            `;
        }).join('');
    };

    /**
     * Selecciona todos los filtros
     */
    window.selectAllFilters = function() {
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = true);
    };

    /**
     * Deselecciona todos los filtros
     */
    window.deselectAllFilters = function() {
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
    };

    /**
     * Limpia todos los filtros locales
     */
    window.clearAllFilters = function() {
        document.querySelectorAll('table tbody tr').forEach(row => row.style.display = '');
        document.getElementById('filterModalInsumos').style.display = 'none';
    };

    /**
     * Limpia todos los filtros de la tabla (redirige sin filtros)
     */
    window.clearAllTableFilters = function() {
        window.location.href = '/insumos/materiales';
    };

    /**
     * Aplica los filtros seleccionados
     */
    window.applyFilters = function() {
        const selected = Array.from(document.querySelectorAll('.filter-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            window.location.href = '/insumos/materiales';
        } else {
            const urlParams = new URLSearchParams(window.location.search);
            const existingFilters = {};
            
            const filterColumns = urlParams.getAll('filter_columns[]') || [];
            const filterValuesArray = urlParams.getAll('filter_values[]') || [];
            filterColumns.forEach((col, idx) => {
                if (!existingFilters[col]) {
                    existingFilters[col] = [];
                }
                if (filterValuesArray[idx]) {
                    existingFilters[col].push(filterValuesArray[idx]);
                }
            });
            
            existingFilters[currentFilterColumn] = selected;
            const filterParams = new URLSearchParams();
            Object.keys(existingFilters).forEach(column => {
                filterParams.append('filter_columns[]', column);
                existingFilters[column].forEach(value => {
                    filterParams.append('filter_values[]', value);
                });
            });
            
            const finalUrl = `/insumos/materiales?${filterParams.toString()}`;
            window.location.href = finalUrl;
        }
        
        document.getElementById('filterModalInsumos').style.display = 'none';
    };

    // Cerrar modal al hacer clic fuera
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('filterModalInsumos');
        if (modal && e.target === modal) {
            modal.style.display = 'none';
        }
    });
});
