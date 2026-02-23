@extends('layouts.app')

@section('title', 'Recibos de Costura')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Table Component -->
            <x-recibos.recibos-costura-table :recibos="$recibos" />
        </div>
    </div>
</div>

<!-- Contenedor de dropdowns dinámicos (igual que en insumos) -->
<div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none;"></div>

<!-- Modal para ver detalles del recibo -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Modal de Novedades -->
<x-modals.novedades-edit-modal />

@endsection

@push('styles')
<!-- Styles Component -->
<x-recibos.recibos-costura-styles />
@endpush

@push('scripts')
<!-- Scripts Component -->
<x-recibos.recibos-costura-scripts />

<!-- Script con funciones globales adicionales (solo las que no están en el componente) -->
<script>
// Definir funciones de filtro globalmente ANTES de cualquier otro código para evitar ReferenceError
window.openFilterModal = function(filterType) {
    console.log('[Filtros] openFilterModal llamado con:', filterType);
    
    // Mostrar modal
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        
        // Guardar el tipo de filtro en el modal
        modal.setAttribute('data-filter-type', filterType);
        
        // Actualizar título
        const title = document.getElementById('filterModalTitle');
        if (title) {
            const titles = {
                'descripcion': 'Filtrar por Descripción',
                'cliente': 'Filtrar por Cliente',
                'estado': 'Filtrar por Estado',
                'area': 'Filtrar por Área',
                'total_dias': 'Filtrar por Total de Días',
                'numero_recibo': 'Filtrar por N° Recibo',
                'cantidad': 'Filtrar por Cantidad',
                'novedades': 'Filtrar por Novedades',
                'fecha_creacion': 'Filtrar por Fecha de Creación',
                'fecha_estimada': 'Filtrar por Fecha Estimada Entrega',
                'encargado': 'Filtrar por Encargado'
            };
            title.textContent = titles[filterType] || 'Filtrar';
        }
        
        // Cargar opciones dinámicas
        loadFilterOptions(filterType);
        
        console.log('[Filtros] Modal mostrado con opciones dinámicas');
    }
};

// Función para cargar opciones dinámicas
function loadFilterOptions(filterType) {
    const optionsContainer = document.getElementById('filterOptions');
    if (!optionsContainer) {
        console.error('[Filtros] No se encontró el contenedor de opciones');
        return;
    }
    
    // Obtener opciones desde la tabla
    const options = getDynamicFilterOptions(filterType);
    
    if (options.length === 0) {
        optionsContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay datos disponibles para filtrar</div>';
        return;
    }
    
    // Generar HTML con checkboxes
    let html = `
        <div style="padding: 12px; border-bottom: 1px solid rgb(229, 231, 235); margin-bottom: 8px;">
            <button type="button" class="btn-select-all" onclick="selectAllCheckboxFilters('${filterType}')" style="width: 100%; padding: 8px 12px; background: linear-gradient(135deg, rgb(59, 130, 246), rgb(37, 99, 235)); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px; transition: 0.2s; box-shadow: rgba(59, 130, 246, 0.2) 0px 2px 4px;">
                Seleccionar todas
            </button>
        </div>
    `;
    
    options.forEach(option => {
        const safeValue = option.replace(/[^a-zA-Z0-9\s]/g, '_');
        html += `
            <div class="filter-option">
                <input type="checkbox" id="filter-${filterType}-${safeValue}" value="${option}">
                <label for="filter-${filterType}-${safeValue}">${option}</label>
            </div>
        `;
    });
    
    optionsContainer.innerHTML = html;
    console.log(`[Filtros] Cargadas ${options.length} opciones para ${filterType}`);
}

// Función para obtener opciones dinámicas desde la tabla
function getDynamicFilterOptions(filterType) {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) {
        console.warn('[Filtros] No se encontró la tabla para generar opciones dinámicas');
        return [];
    }
    
    const options = new Set();
    const columnIndex = getColumnIndex(filterType);
    
    if (columnIndex === -1) return [];
    
    const rows = tbody.querySelectorAll('tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > columnIndex) {
            let cellText = '';
            
            // Para el filtro de descripción, leer el atributo data-descripcion-detallada
            if (filterType === 'descripcion') {
                cellText = cells[columnIndex].getAttribute('data-descripcion-detallada') || '';
            } else {
                cellText = cells[columnIndex].textContent.trim();
            }
            
            if (cellText && cellText !== '-' && cellText !== 'N/A' && cellText !== '') {
                options.add(cellText);
            }
        }
    });
    
    return Array.from(options).sort();
}

// Función para obtener el índice de columna según el tipo de filtro
function getColumnIndex(filterType) {
    const columnMap = {
        'estado': 1,           // Columna 2: Estado
        'area': 2,            // Columna 3: Área ✅
        'total_dias': 3,       // Columna 4: Total de días
        'numero_recibo': 4,     // Columna 5: N° Recibo
        'cliente': 5,          // Columna 6: Cliente
        'descripcion': 6,      // Columna 7: Descripción ✅
        'cantidad': 7,         // Columna 8: Cantidad
        'novedades': 8,        // Columna 9: Novedades
        'fecha_creacion': 9,  // Columna 10: Fecha de creación
        'fecha_estimada': 10,  // Columna 11: Fecha estimada entrega
        'encargado': 11        // Columna 12: Encargado orden
    };
    return columnMap[filterType] || -1;
}

window.closeFilterModal = function() {
    console.log('[Filtros] closeFilterModal llamado');
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
        console.log('[Filtros] Modal cerrado (implementación básica)');
    }
};

window.resetFilters = function() {
    console.log('[Filtros] resetFilters llamado');
    
    // Limpiar checkboxes del modal
    const checkboxes = document.querySelectorAll('#filterOptions input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);
    
    // Mostrar todas las filas de la tabla
    const tbody = document.getElementById('tablaRecibosBody');
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            row.style.display = '';
        });
        console.log(`[Filtros] Mostrando todas las ${rows.length} filas`);
    }
    
    // Limpiar filtros activos
    if (window.activeFilters) {
        window.activeFilters = {};
    }
    
    console.log('[Filtros] Filtros reiniciados');
};

window.applyFilters = function() {
    console.log('[Filtros] applyFilters llamado');
    
    const modal = document.getElementById('filterModal');
    const filterType = modal ? modal.getAttribute('data-filter-type') : null;
    
    if (!filterType) {
        console.warn('[Filtros] No se encontró el tipo de filtro');
        window.closeFilterModal();
        return;
    }
    
    // Obtener checkboxes seleccionados
    const checkboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
    const selectedValues = Array.from(checkboxes).map(cb => cb.value);
    
    console.log('[Filtros] Aplicando filtro:', filterType, 'valores:', selectedValues);
    
    if (selectedValues.length === 0) {
        console.log('[Filtros] No hay valores seleccionados, mostrando todos');
        resetFilters();
        return;
    }
    
    // Filtrar filas de la tabla
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) {
        console.warn('[Filtros] No se encontró la tabla');
        window.closeFilterModal();
        return;
    }
    
    const columnIndex = getColumnIndex(filterType);
    if (columnIndex === -1) {
        console.warn('[Filtros] Índice de columna no válido para:', filterType);
        window.closeFilterModal();
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > columnIndex) {
            let cellText = '';
            
            // Para el filtro de descripción, leer el atributo data-descripcion-detallada
            if (filterType === 'descripcion') {
                cellText = cells[columnIndex].getAttribute('data-descripcion-detallada') || '';
            } else {
                cellText = cells[columnIndex].textContent.trim();
            }
            
            // Verificar si alguna de las opciones seleccionadas está en el texto de la celda
            const isVisible = selectedValues.some(selectedValue => {
                if (filterType === 'descripcion') {
                    // Para descripción, buscar coincidencia exacta
                    return cellText === selectedValue;
                } else {
                    // Para otros filtros, buscar si contiene el texto
                    return cellText.includes(selectedValue);
                }
            });
            
            if (isVisible) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
    });
    
    console.log(`[Filtros] Filtrado completado: ${visibleCount} filas visibles de ${rows.length}`);
    
    // Guardar filtros activos
    if (!window.activeFilters) {
        window.activeFilters = {};
    }
    window.activeFilters[filterType] = selectedValues;
    
    window.closeFilterModal();
};

window.selectAllCheckboxFilters = function(filterType) {
    console.log('[Filtros] selectAllCheckboxFilters llamado con:', filterType);
    const checkboxes = document.querySelectorAll(`#filterOptions-${filterType} input[type="checkbox"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
};

window.filterCheckboxOptions = function(filterType) {
    console.log('[Filtros] filterCheckboxOptions llamado con:', filterType);
    const searchTerm = document.querySelector('.filter-search').value.toLowerCase();
    const options = document.querySelectorAll(`#filterOptions-${filterType} .filter-option`);
    
    options.forEach(option => {
        const label = option.querySelector('label').textContent.toLowerCase();
        option.style.display = label.includes(searchTerm) ? 'block' : 'none';
    });
};

// Cargar nombres de prendas al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DOMContentLoaded] 📄 Cargando nombres de prendas en recibos-costura');
    
    // Obtener todas las filas de recibos
    const filasRecibos = document.querySelectorAll('#tablaRecibosBody tr[data-orden-id]');
    
    filasRecibos.forEach(fila => {
        const reciboId = fila.getAttribute('data-orden-id');
        const descripcionElemento = fila.querySelector('.descripcion-prenda-texto');
        
        if (descripcionElemento) {
            // Buscar el enlace del pedido para obtener el pedido_produccion_id
            const enlacePedido = fila.querySelector('a[href*="/registros/"]');
            let pedidoProduccionId = null;
            
            if (enlacePedido) {
                const href = enlacePedido.getAttribute('href');
                const match = href.match(/\/registros\/(\d+)/);
                if (match) {
                    pedidoProduccionId = match[1];
                }
            }
            
            if (pedidoProduccionId) {
                // Obtener el nombre de la primera prenda del pedido
                fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                    .then(response => response.json())
                    .then(datos => {
                        if (datos.data && typeof datos.data === 'object') {
                            datos = datos.data;
                        }
                        
                        if (datos.prendas && Array.isArray(datos.prendas) && datos.prendas.length > 0) {
                            const primeraPrenda = datos.prendas[0];
                            const nombrePrenda = primeraPrenda.nombre || primeraPrenda.nombre_prenda || 'Sin nombre';
                            
                            // Actualizar el texto de la descripción
                            descripcionElemento.textContent = nombrePrenda;
                            console.log(`[CargarNombres] ✅ Prenda actualizada para recibo ${reciboId}: ${nombrePrenda}`);
                        } else {
                            descripcionElemento.textContent = 'Sin prendas';
                        }
                    })
                    .catch(error => {
                        console.error(`[CargarNombres] Error cargando prenda para recibo ${reciboId}:`, error);
                        descripcionElemento.textContent = 'Error';
                    });
            } else {
                descripcionElemento.textContent = 'Sin pedido';
            }
        }
    });
});

function verDetallesRecibo(reciboId) {
    // Buscar la fila del recibo para obtener el pedido_produccion_id
    const fila = document.querySelector(`tr[data-orden-id="${reciboId}"]`);
    if (!fila) {
        alert('No se encontró el recibo');
        return;
    }
    
    console.log(`[verDetallesRecibo] 📌 Fila encontrada para recibo ${reciboId}`);
    
    // Intentar obtener el enlace del pedido para extraer el pedido_produccion_id
    const enlacePedido = fila.querySelector('a[href*="/registros/"]');
    let pedidoId = null;
    
    if (enlacePedido) {
        // Extraer el ID del pedido desde el href
        const href = enlacePedido.getAttribute('href');
        const pedidoIdMatch = href.match(/\/registros\/(\d+)/);
        if (pedidoIdMatch) {
            pedidoId = parseInt(pedidoIdMatch[1]);
            console.log(`[verDetallesRecibo] 📋 Pedido ID encontrado desde enlace: ${pedidoId}`);
        }
    }
    
    // Si no se encontró el pedidoId, intentar obtenerlo del data-pedido-id
    if (!pedidoId) {
        const pedidoIdAttr = fila.getAttribute('data-pedido-id');
        if (pedidoIdAttr) {
            pedidoId = parseInt(pedidoIdAttr);
            console.log(`[verDetallesRecibo] 📋 Pedido ID encontrado desde data-pedido-id: ${pedidoId}`);
        }
    }
    
    // Si todavía no hay pedidoId, intentar obtenerlo del dropdown de día de entrega
    if (!pedidoId) {
        const dropdownDiaEntrega = fila.querySelector('.dia-entrega-dropdown');
        if (dropdownDiaEntrega) {
            const dropdownIdAttr = dropdownDiaEntrega.getAttribute('data-orden-id');
            if (dropdownIdAttr) {
                pedidoId = parseInt(dropdownIdAttr);
                console.log(`[verDetallesRecibo] 📋 Pedido ID encontrado desde dropdown día entrega: ${pedidoId}`);
            }
        }
    }
    
    // Si todavía no hay pedidoId, mostrar error detallado
    if (!pedidoId) {
        console.error(`[verDetallesRecibo] ❌ No se pudo encontrar el ID del pedido para el recibo: ${reciboId}`);
        console.log(`[verDetallesRecibo] 🔍 Contenido de la fila:`, fila.innerHTML);
        alert('No se encontró información del pedido asociada a este recibo. El recibo puede no estar correctamente vinculado a un pedido.');
        return;
    }
    
    console.log(`[verDetallesRecibo] ✅ Pedido ID confirmado: ${pedidoId}`);
    
    // Para recibos de costura, necesitamos encontrar la primera prenda del pedido
    // Hacemos una llamada al endpoint para obtener las prendas del pedido
    fetch(`/registros/${pedidoId}/recibos-datos`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(datos => {
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }
            
            if (!datos.prendas || !Array.isArray(datos.prendas) || datos.prendas.length === 0) {
                console.warn('[verDetallesRecibo] No se encontraron prendas para el pedido:', pedidoId);
                alert('No se encontraron prendas para este pedido. No se puede generar el recibo.');
                return;
            }
            
            // Obtener la primera prenda (asumimos que los recibos de costura son para la primera prenda)
            const primeraPrenda = datos.prendas[0];
            const prendaId = primeraPrenda.id;
            
            console.log(`[verDetallesRecibo] ✅ Prenda encontrada: ${prendaId}`);
            
            // Abrir el recibo de costura usando el módulo
            if (window.pedidosRecibosModule) {
                window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, 'costura');
            } else {
                console.error('[verDetallesRecibo] Módulo de recibos no disponible');
                alert('Módulo de recibos no disponible. Por favor recargue la página.');
            }
        })
        .catch(error => {
            console.error('[verDetallesRecibo] Error al obtener datos del pedido:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
}

// Función para abrir el modal de seguimiento
function abrirModalSeguimiento(pedidoId, prendaIdTarget) {
    // Cerrar cualquier dropdown abierto
    closeDropdownRecibos();
    
    console.log('[abrirModalSeguimiento] Abriendo seguimiento para pedido:', pedidoId, 'prenda:', prendaIdTarget);
    
    // Inicializar datos del pedido para el tracking modal
    if (typeof openOrderTracking === 'function') {
        console.log('[abrirModalSeguimiento] Llamando a openOrderTracking para inicializar datos');
        openOrderTracking(pedidoId, false).then(() => {
            console.log('[abrirModalSeguimiento] Datos inicializados, buscando prenda específica:', prendaIdTarget);
            
            // Intentar encontrar prendas en diferentes estructuras posibles
            let prendas = null;
            if (window.currentOrderData && window.currentOrderData.prendas) {
                prendas = window.currentOrderData.prendas;
            } else if (window.currentOrderData && window.currentOrderData.data && window.currentOrderData.data.prendas) {
                prendas = window.currentOrderData.data.prendas;
            } else if (window.prendasData && window.prendasData.length > 0) {
                prendas = window.prendasData;
            }
            
            if (prendas && prendas.length > 0) {
                // Buscar la prenda específica por ID, si se proporcionó
                let prendaSeleccionada = null;
                if (prendaIdTarget) {
                    prendaSeleccionada = prendas.find(p => 
                        String(p.id) === String(prendaIdTarget) || 
                        String(p.prenda_pedido_id) === String(prendaIdTarget)
                    );
                    console.log('[abrirModalSeguimiento] Prenda encontrada por ID:', prendaSeleccionada?.nombre_prenda || prendaSeleccionada?.nombre);
                }
                
                // Fallback: usar la primera prenda si no se encontró la específica
                if (!prendaSeleccionada) {
                    prendaSeleccionada = prendas[0];
                    console.log('[abrirModalSeguimiento] Usando primera prenda como fallback:', prendaSeleccionada?.nombre_prenda || prendaSeleccionada?.nombre);
                }
                
                // Inicializar currentPrendaData
                window.currentPrendaData = prendaSeleccionada;
                
                // Abrir directamente el modal de seguimiento
                abrirModalSeguimientoDirecto(pedidoId, prendaIdTarget);
            } else {
                console.warn('[abrirModalSeguimiento] No hay prendas disponibles');
                if (typeof showPrendasSelector === 'function') {
                    showPrendasSelector();
                } else {
                    alert('No hay prendas disponibles para este pedido');
                }
            }
        }).catch(error => {
            console.error('[abrirModalSeguimiento] Error al inicializar datos:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
    } else {
        console.warn('[abrirModalSeguimiento] openOrderTracking no disponible');
        alert('Sistema de seguimiento no disponible');
    }
}

// Función para abrir el modal de seguimiento directamente sin selector
function abrirModalSeguimientoDirecto(pedidoId, prendaIdTarget) {
    // Abrir el overlay del modal de seguimiento
    const trackingOverlay = document.getElementById('trackingModalOverlay');
    if (trackingOverlay) {
        trackingOverlay.style.display = 'block';
    } else {
        console.warn('Modal de seguimiento no encontrado');
        alert('Modal de seguimiento no disponible');
        return;
    }
    
    // Abrir el contenido del modal
    const trackingModal = document.getElementById('orderTrackingModal');
    if (trackingModal) {
        trackingModal.style.display = 'flex';
        trackingModal.classList.add('show');
        
        // Construir URL con prenda_id si está disponible
        let urlConsecutivo = `/registros/${pedidoId}/consecutivo-costura`;
        if (prendaIdTarget) {
            urlConsecutivo += `?prenda_id=${prendaIdTarget}`;
        }
        
        // Obtener el consecutivo de costura para esta prenda específica
        fetch(urlConsecutivo)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success && data.consecutivo) {
                    const reciboElement = document.getElementById('trackingOrderRecibo');
                    if (reciboElement) reciboElement.textContent = data.consecutivo;
                    
                    const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
                    if (headerSubtitleElement) headerSubtitleElement.textContent = `COSTURA #${data.consecutivo}`;
                } else {
                    const reciboElement = document.getElementById('trackingOrderRecibo');
                    if (reciboElement) reciboElement.textContent = '-';
                    
                    const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
                    if (headerSubtitleElement) headerSubtitleElement.textContent = 'COSTURA #?';
                }
                
                if (data.fecha_creacion) {
                    const fechaElement = document.getElementById('trackingOrderDate');
                    if (fechaElement) {
                        const fecha = new Date(data.fecha_creacion);
                        fechaElement.textContent = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit', month: '2-digit', year: 'numeric'
                        });
                    }
                }
                
                // Mostrar seguimiento de la prenda seleccionada
                if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                    showPrendaTracking(window.currentPrendaData);
                }
            })
            .catch(error => {
                console.error('Error al obtener consecutivo de costura:', error);
                if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                    showPrendaTracking(window.currentPrendaData);
                }
            });
    } else {
        console.warn('Contenido del modal de seguimiento no encontrado');
    }
}

// Función para cerrar el modal overlay
function closeModalOverlay() {
    const modal = document.getElementById('modal-overlay');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Función global para cerrar todos los dropdowns de recibos-costura
function closeDropdownRecibos() {
    document.querySelectorAll('.dropdown-menu-recibos').forEach(menu => {
        menu.style.display = 'none';
        menu.style.pointerEvents = 'none';
    });
}

/**
 * Crear dropdown dinámico para recibos-costura (igual que crearDropdownVer en insumos)
 * Se crea en #dropdowns-container con position:fixed para evitar overflow clipping
 */
function crearDropdownRecibos(button) {
    const menuId = button.getAttribute('data-menu-id');
    const pedidoId = button.getAttribute('data-pedido-id');
    const prendaId = button.getAttribute('data-prenda-id');

    // Verificar si ya existe
    const existing = document.getElementById(menuId);
    if (existing) return existing;

    const container = document.getElementById('dropdowns-container');
    if (!container) {
        console.error('[RecibosDropdown] No se encontró #dropdowns-container');
        return null;
    }

    const dropdown = document.createElement('div');
    dropdown.id = menuId;
    dropdown.className = 'dropdown-menu-recibos';
    dropdown.style.cssText = `
        position: fixed;
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        min-width: 180px;
        display: none;
        z-index: 999999;
        overflow: visible;
        pointer-events: auto;
    `;

    dropdown.innerHTML = `
        <button onclick="openOrderDetailModalWithProcess(${pedidoId}, ${prendaId || 'null'}, 'COSTURA'); closeDropdownRecibos()" style="
            width: 100%;
            text-align: left;
            padding: 0.875rem 1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        " onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'">
            <i class="fas fa-eye" style="color: #3b82f6;"></i> Ver Detalles
        </button>
        <div style="height: 1px; background: #e5e7eb;"></div>
        <button onclick="abrirModalSeguimiento(${pedidoId}, ${prendaId || 'null'}); closeDropdownRecibos()" style="
            width: 100%;
            text-align: left;
            padding: 0.875rem 1rem;
            border: none;
            background: transparent;
            cursor: pointer;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        " onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='transparent'">
            <i class="fas fa-tasks" style="color: #10b981;"></i> Seguimiento
        </button>
    `;

    container.appendChild(dropdown);
    return dropdown;
}

// Ocultar el botón Volver y activar dropdowns estilo insumos
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DOMContentLoaded] 📄 Inicializando dropdowns estilo insumos en recibos-costura');

    if (window.location.pathname.includes('/recibos-costura')) {
        const botonVolver = document.getElementById('backToPrendasBtn');
        if (botonVolver) {
            botonVolver.style.display = 'none';
            console.log('Botón Volver ocultado en recibos-costura');
        }
    }

    // Delegación de clics para botones btn-ver-dropdown (igual que en insumos)
    document.addEventListener('click', function(e) {
        const btnVer = e.target.closest('.btn-ver-dropdown');

        if (btnVer) {
            e.preventDefault();
            e.stopPropagation();

            console.log('[RecibosDropdown] Click en botón Ver');

            // Cerrar todos los dropdowns abiertos primero
            closeDropdownRecibos();

            // Crear dropdown si no existe
            const dropdown = crearDropdownRecibos(btnVer);
            if (!dropdown) return;

            // Posicionar debajo del botón usando getBoundingClientRect (position: fixed)
            const rect = btnVer.getBoundingClientRect();
            dropdown.style.top = (rect.bottom + 5) + 'px';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.display = 'block';
            dropdown.style.pointerEvents = 'auto';

            // Ajustar si se sale de la pantalla por la derecha
            setTimeout(() => {
                const dropRect = dropdown.getBoundingClientRect();
                if (dropRect.right > window.innerWidth) {
                    dropdown.style.left = (window.innerWidth - dropRect.width - 10) + 'px';
                }
                // Ajustar si se sale por abajo
                if (dropRect.bottom > window.innerHeight) {
                    dropdown.style.top = (rect.top - dropRect.height - 5) + 'px';
                }
            }, 10);

            console.log('[RecibosDropdown] Dropdown abierto');
            return;
        }

        // Cerrar al hacer clic fuera
        if (!e.target.closest('.dropdown-menu-recibos')) {
            closeDropdownRecibos();
        }
    });
});
</script>
@endpush
