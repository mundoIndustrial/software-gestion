/**
 * LEGACY FUNCTIONS - Compatibilidad Dual
 * 
 * ⚠️ IMPORTANTE: Estas funciones tienen versiones MEJORADAS en bundle.js
 * 
 * DELEGADAS AL BUNDLE (Si bundle.js carga, estas se SOBRESCRIBEN):
 * ✅ setupTableEventListeners() - lines ~400-500
 * ✅ crearDropdownRecibos() - lines ~600-700  
 * ✅ closeDropdownRecibos() - Se sobrescribe por la del bundle
 * ✅ openOrderDetailModal() - Se sobrescribe por la del bundle
 * ✅ closeModalOverlay() - Se sobrescribe (window.closeModalOverlay)
 * 
 * MANTIENEN SU LÓGICA ORIGINAL (no están en bundle):
 * ✅ openFilterModal()
 * ✅ loadFilterOptions()
 * ✅ getDynamicFilterOptions()
 * ✅ getColumnIndex()
 * ✅ closeFilterModal()
 * ✅ resetFilters()
 * ✅ applyFilters()
 * ✅ verDetallesRecibo()
 * ✅ abrirModalSeguimiento()
 * ✅ abrirModalAgregarProcesoDesdeArea()
 * ✅ handleAgregarProcesoDesdeBadge()
 * ✅ showToast(), showSuccess(), showError()
 * ✅ cargarConteoRecibosCorte()
 * ✅ initializeReciboAprobadoListener()
 * 
 * ESTRATEGIA DE MIGRACIÓN:
 * 1. Bundle.js carga primero y proporciona versiones optimizadas
 * 2. Si bundle.js falla, las funciones legacy del blade funcionan
 * 3. Se pueden eliminar de aquí UNA POR UNA una vez validadas
 */

// ========== FUNCIONES DE FILTRO =========
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
    
    // Diagnóstico del sistema de agregar proceso
    console.log('[DIAGNÓSTICO] Verificando sistema de agregar proceso desde badge...');
    console.log('[DIAGNÓSTICO] Elementos disponibles:', {
        modalAddProceso: !!document.getElementById('addProcesoModal'),
        btnConfirmAddProceso: !!document.getElementById('btnConfirmAddProceso'),
        procesoArea: !!document.getElementById('procesoArea'),
        procesoEncargado: !!document.getElementById('procesoEncargado'),
        'typeof handleAgregarProceso': typeof handleAgregarProceso,
        'typeof verificarDatosAntesDeGuardar': typeof verificarDatosAntesDeGuardar
    });
    
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
    
    // Verificar badges clickeables
    const badgesArea = document.querySelectorAll('.area-badge-clickable');
    console.log(`[DIAGNÓSTICO] Encontrados ${badgesArea.length} badges de área clickeables`);
    
    badgesArea.forEach((badge, index) => {
        const onclick = badge.getAttribute('onclick');
    
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
                // Guardar para que tracking-modal-handler.js pueda usar encargado/area como fallback
                window.currentConsecutivoCosturaData = data;
                if (data.success && data.consecutivo) {
                    const reciboElement = document.getElementById('trackingOrderRecibo');
                    if (reciboElement) reciboElement.textContent = data.consecutivo;
                    
                    const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
                    if (headerSubtitleElement) {
                        const area = data.area ? String(data.area) : '';
                        headerSubtitleElement.textContent = area
                            ? `COSTURA #${data.consecutivo} - ${area}`
                            : `COSTURA #${data.consecutivo}`;
                    }
                } else {
                    const reciboElement = document.getElementById('trackingOrderRecibo');
                    if (reciboElement) reciboElement.textContent = '-';
                    
                    const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
                    if (headerSubtitleElement) {
                        const area = data?.area ? String(data.area) : '';
                        headerSubtitleElement.textContent = area
                            ? `COSTURA #? - ${area}`
                            : 'COSTURA #?';
                    }
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

// Función para abrir el modal de agregar proceso desde el badge del área
window.abrirModalAgregarProcesoDesdeArea = function(areaSeleccionada, pedidoId, prendaId) {
    console.log('[abrirModalAgregarProcesoDesdeArea] 📌 Área seleccionada:', areaSeleccionada, 'Pedido:', pedidoId, 'Prenda:', prendaId);
    
    // Cerrar cualquier dropdown abierto
    closeDropdownRecibos();
    
    // Verificar que tengamos los IDs necesarios
    if (!pedidoId) {
        console.error('[abrirModalAgregarProcesoDesdeArea] No se proporcionó ID del pedido');
        alert('No se puede identificar el pedido asociado');
        return;
    }
    
    // Cargar datos del pedido y prenda antes de abrir el modal
    cargarDatosParaAgregarProceso(pedidoId, prendaId, areaSeleccionada)
        .then(() => {
            // Verificación adicional antes de abrir el modal
            console.log('[abrirModalAgregarProcesoDesdeArea] Verificación pre-apertura:', {
                hasOrderData: !!window.currentOrderData,
                hasPrendaData: !!window.currentPrendaData,
                orderNumero: window.currentOrderData?.numero_pedido,
                prendaId: window.currentPrendaData?.id
            });
            
            if (!window.currentOrderData || !window.currentPrendaData) {
                throw new Error('No se pudieron cargar los datos necesarios');
            }
            
            // Abrir el modal de agregar proceso
            const modal = document.getElementById('addProcesoModal');
            if (!modal) {
                console.error('[abrirModalAgregarProcesoDesdeArea] Modal no encontrado');
                alert('Modal de agregar proceso no disponible');
                return;
            }
            
            // Guardar datos en atributos data- para persistencia
            modal.setAttribute('data-pedido-id', pedidoId);
            modal.setAttribute('data-prenda-id', prendaId || '');
            modal.setAttribute('data-area', areaSeleccionada);
            
            // Mostrar el modal
            modal.style.display = 'flex';
            modal.classList.add('show');
            
            // Seleccionar automáticamente el área en el select
            const selectArea = document.getElementById('procesoArea');
            if (selectArea) {
                selectArea.value = areaSeleccionada;
                console.log('[abrirModalAgregarProcesoDesdeArea] ✅ Área seleccionada automáticamente:', areaSeleccionada);
            }
            
            // Limpiar el campo de encargado y enfocarlo
            const inputEncargado = document.getElementById('procesoEncargado');
            if (inputEncargado) {
                inputEncargado.value = '';
                inputEncargado.focus();
            }
            
            // Agregar listener adicional para verificar datos al hacer clic en "Agregar Proceso"
            const btnConfirm = document.getElementById('btnConfirmAddProceso');
            if (btnConfirm) {
                // Remover listener anterior si existe
                btnConfirm.removeEventListener('click', verificarDatosAntesDeGuardar);
                // Agregar nuevo listener
                btnConfirm.addEventListener('click', verificarDatosAntesDeGuardar);
            }
            
            console.log('[abrirModalAgregarProcesoDesdeArea] ✅ Modal abierto con datos cargados');
        })
        .catch(error => {
            console.error('[abrirModalAgregarProcesoDesdeArea] Error al cargar datos:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
};

// Función de verificación antes de guardar
function verificarDatosAntesDeGuardar(event) {
    console.log('[verificarDatosAntesDeGuardar] Verificando datos antes de guardar...');
    console.log('[verificarDatosAntesDeGuardar] Estado actual:', {
        hasOrderData: !!window.currentOrderData,
        hasPrendaData: !!window.currentPrendaData,
        orderData: window.currentOrderData,
        prendaData: window.currentPrendaData,
        'typeof handleAgregarProceso': typeof handleAgregarProceso
    });
    
    // Si no hay datos, intentar cargarlos desde las variables del modal
    if (!window.currentOrderData || !window.currentPrendaData) {
        console.log('[verificarDatosAntesDeGuardar] Intentando recuperar datos desde atributos del modal...');
        
        // Intentar obtener los datos desde los atributos data- que guardamos
        const modal = document.getElementById('addProcesoModal');
        if (modal) {
            const pedidoId = modal.getAttribute('data-pedido-id');
            const prendaId = modal.getAttribute('data-prenda-id');
            const area = modal.getAttribute('data-area');
            
            console.log('[verificarDatosAntesDeGuardar] Datos encontrados en modal:', {pedidoId, prendaId, area});
            
            if (pedidoId) {
                // Intentar cargar datos nuevamente
                cargarDatosParaAgregarProceso(pedidoId, prendaId, area)
                    .then(() => {
                        console.log('[verificarDatosAntesDeGuardar] Datos recargados, reintentando guardar...');
                        if (window.currentOrderData && window.currentPrendaData) {
                            handleAgregarProcesoDesdeBadge();
                        } else {
                            alert('Error: No se pudieron cargar los datos necesarios. Por favor, recarga la página.');
                        }
                    })
                    .catch(error => {
                        console.error('[verificarDatosAntesDeGuardar] Error al recargar datos:', error);
                        alert('Error al cargar los datos: ' + error.message);
                    });
                
                event.preventDefault();
                event.stopPropagation();
                return false;
            }
        }
    }
    
    if (!window.currentOrderData || !window.currentPrendaData) {
        console.error('[verificarDatosAntesDeGuardar] ❌ Faltan datos necesarios');
        alert('Error: No hay datos de la prenda o pedido. Por favor, recarga la página e intenta nuevamente.');
        event.preventDefault();
        event.stopPropagation();
        return false;
    }
    
    console.log('[verificarDatosAntesDeGuardar] ✅ Datos verificados, procediendo con handleAgregarProceso');
    
    // Llamar a la función específica para recibos-costura
    if (typeof handleAgregarProcesoDesdeBadge === 'function') {
        handleAgregarProcesoDesdeBadge();
    } else {
        console.error('[verificarDatosAntesDeGuardar] handleAgregarProcesoDesdeBadge no disponible');
        alert('Error: Sistema no disponible. Por favor, recarga la página.');
    }
}

// Función específica para agregar proceso desde badge en recibos-costura
async function handleAgregarProcesoDesdeBadge() {
    try {
        console.log('[handleAgregarProcesoDesdeBadge] Iniciando agregado de proceso desde badge...');
        
        // Mostrar indicador de carga
        const btnContent = document.getElementById('addProcesoButtonContent');
        const btnLoading = document.getElementById('addProcesoButtonLoading');
        const btnConfirm = document.getElementById('btnConfirmAddProceso');
        
        if (btnContent && btnLoading && btnConfirm) {
            btnContent.style.display = 'none';
            btnLoading.style.display = 'flex';
            btnConfirm.disabled = true;
        }

        const area = document.getElementById('procesoArea').value;
        
        // Obtener encargado - puede ser de un select (ID) o de un input (texto)
        let encargado = '';
        const selectEncargado = document.getElementById('procesoEncargadoSelect');
        const inputEncargado = document.getElementById('procesoEncargado');
        
        if (selectEncargado && selectEncargado.offsetParent !== null) {
            // Es un select - obtener el texto del option seleccionado
            const selectedOption = selectEncargado.options[selectEncargado.selectedIndex];
            encargado = selectedOption ? selectedOption.text : '';
        } else if (inputEncargado) {
            // Es un input - obtener el valor y convertir a mayúsculas
            encargado = inputEncargado.value.toUpperCase();
        }

        if (!area) {
            showError('Por favor selecciona un área/proceso');
            // Ocultar indicador de carga
            if (btnContent && btnLoading && btnConfirm) {
                btnContent.style.display = 'flex';
                btnLoading.style.display = 'none';
                btnConfirm.disabled = false;
            }
            return;
        }

        // Validar encargado solo para áreas que lo requieren
        const areaLower = area.toLowerCase();
        const needsEncargado = ['corte', 'costura', 'control de calidad'];
        const areaRequiresEncargado = needsEncargado.some(reqArea => areaLower.includes(reqArea));
        
        if (areaRequiresEncargado && !encargado.trim()) {
            showError('Por favor selecciona o ingresa el encargado');
            // Ocultar indicador de carga
            if (btnContent && btnLoading && btnConfirm) {
                btnContent.style.display = 'flex';
                btnLoading.style.display = 'none';
                btnConfirm.disabled = false;
            }
            return;
        }

        if (!window.currentOrderData || !window.currentPrendaData) {
            showError('No hay datos de la prenda o pedido');
            // Ocultar indicador de carga
            if (btnContent && btnLoading && btnConfirm) {
                btnContent.style.display = 'flex';
                btnLoading.style.display = 'none';
                btnConfirm.disabled = false;
            }
            return;
        }

        console.log('[handleAgregarProcesoDesdeBadge] Agregando proceso:', {
            area,
            encargado,
            pedido_produccion_id: window.currentOrderData.numero_pedido,
            prenda_id: window.currentPrendaData.id,
            currentOrderData: window.currentOrderData
        });

        // Enviar datos al backend - mismo endpoint que usa el modal de seguimiento
        const response = await fetch('/seguimiento-proceso/guardar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                pedido_produccion_id: window.currentOrderData.numero_pedido,
                prenda_id: window.currentPrendaData.id,
                area: area,
                encargado: encargado,
                estado: 'Pendiente'
            })
        });

        if (!response.ok) {
            throw new Error('Error al agregar proceso');
        }

        const result = await response.json();

        // ✅ Mostrar mensaje diferente según si fue creado o actualizado
        const mensaje = result.action === 'actualizado' 
            ? 'Proceso actualizado correctamente' 
            : 'Proceso agregado correctamente';
        
        console.log('[handleAgregarProcesoDesdeBadge] Mostrando mensaje:', mensaje);
        showSuccess(mensaje);
        
        // Limpiar formulario
        limpiarFormularioProceso();

        // Cerrar modal de agregar proceso
        const modal = document.getElementById('addProcesoModal');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
        
        // Recargar la página para mostrar el proceso (creado o actualizado)
        setTimeout(() => {
            window.location.reload();
        }, 1500);

    } catch (error) {
        console.error('[handleAgregarProcesoDesdeBadge] Error:', error);
        showError('Error al agregar proceso: ' + error.message);
    } finally {
        // Ocultar indicador de carga
        const btnContent = document.getElementById('addProcesoButtonContent');
        const btnLoading = document.getElementById('addProcesoButtonLoading');
        const btnConfirm = document.getElementById('btnConfirmAddProceso');
        
        if (btnContent && btnLoading && btnConfirm) {
            btnContent.style.display = 'flex';
            btnLoading.style.display = 'none';
            btnConfirm.disabled = false;
        }
    }
}

// Función para limpiar formulario de proceso
function limpiarFormularioProceso() {
    const selectArea = document.getElementById('procesoArea');
    const inputEncargado = document.getElementById('procesoEncargado');
    const selectEncargado = document.getElementById('procesoEncargadoSelect');
    
    if (selectArea) selectArea.value = '';
    if (inputEncargado) inputEncargado.value = '';
    if (selectEncargado) selectEncargado.value = '';
}

// Funciones para mostrar mensajes (Toast Notifications)
function showSuccess(message, title = 'Éxito') {
    console.log('[showSuccess] Mostrando toast de éxito:', message);
    showToast(message, 'success', title);
}

function showError(message, title = 'Error') {
    console.log('[showError] Mostrando toast de error:', message);
    showToast(message, 'error', title);
}

// Función principal para mostrar toast notifications
function showToast(message, type = 'info', title = '') {
    // Crear o obtener el contenedor de toasts
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Crear el elemento toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // Determinar el icono según el tipo
    let icon = '';
    if (type === 'success') {
        icon = '✓';
    } else if (type === 'error') {
        icon = '✕';
    } else {
        icon = 'ℹ';
    }
    
    // Generar ID único para este toast
    const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    toast.id = toastId;
    
    // Construir el HTML del toast
    toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-content">
            ${title ? `<div class="toast-title">${title}</div>` : ''}
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="removeToast('${toastId}')">×</button>
    `;
    
    // Agregar el toast al contenedor
    container.appendChild(toast);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        removeToast(toastId);
    }, 5000);
    
    console.log(`[showToast] Toast ${type} mostrado:`, { id: toastId, message, title });
}

// Función para eliminar un toast específico
function removeToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast && !toast.classList.contains('removing')) {
        toast.classList.add('removing');
        
        // Esperar a que termine la animación antes de eliminar
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
        
        console.log(`[removeToast] Toast eliminado:`, toastId);
    }
}

// Función para limpiar todos los toasts
function clearAllToasts() {
    const container = document.getElementById('toastContainer');
    if (container) {
        const toasts = container.querySelectorAll('.toast');
        toasts.forEach(toast => {
            removeToast(toast.id);
        });
    }
}

// Función para cargar los datos necesarios para agregar proceso
async function cargarDatosParaAgregarProceso(pedidoId, prendaId, areaSeleccionada) {
    console.log('[cargarDatosParaAgregarProceso] Cargando datos para pedido:', pedidoId, 'prenda:', prendaId);
    
    try {
        // ⚠️ VALIDAR QUE SE PROPORCIONE UNA PRENDA ESPECÍFICA
        if (!prendaId || prendaId === 'null' || prendaId === null) {
            throw new Error('CRÍTICO: No se proporcionó una prenda específica. No se puede asignar encargado sin prenda definida.');
        }
        
        // Cargar datos básicos del pedido
        const response = await fetch(`/registros/${pedidoId}/recibos-datos`);
        if (!response.ok) throw new Error('Error al cargar datos del pedido');
        
        const result = await response.json();
        const data = result.data || result;
        
        console.log('[cargarDatosParaAgregarProceso] Datos recibidos del endpoint:', data);
        
        // Asegurar que la estructura de datos sea compatible con handleAgregarProceso
        // El endpoint /seguimiento-proceso/guardar espera:
        // - pedido_produccion_id: el ID del pedido (numero_pedido)
        // - prenda_id: el ID de la prenda
        const orderData = {
            ...data,
            numero_pedido: data.numero_pedido || data.id || pedidoId,
            pedido: data.numero_pedido || data.id || pedidoId
        };
        
        // Establecer variables globales para que handleAgregarProceso funcione
        window.currentOrderData = orderData;
        window.currentPedidoId = pedidoId;
        window.currentPrendaId = prendaId;
        window.currentArea = areaSeleccionada;
        
        // Buscar la prenda específica en los datos del pedido
        if (data.prendas && Array.isArray(data.prendas)) {
            let prendaEncontrada = null;
            
            // 🔒 SER ESTRICTO: Buscar EXACTAMENTE la prenda especificada, SIN FALLBACK
            prendaEncontrada = data.prendas.find(p => 
                String(p.id) === String(prendaId) || 
                String(p.prenda_pedido_id) === String(prendaId)
            );
            
            if (prendaEncontrada) {
                window.currentPrendaData = prendaEncontrada;
                console.log('[cargarDatosParaAgregarProceso] ✅ Prenda encontrada:', prendaEncontrada.nombre_prenda || prendaEncontrada.nombre, 'ID:', prendaEncontrada.id);
            } else {
                // 🛑 SIN FALLBACK: Si no se encuentra la prenda específica, lanzar error
                throw new Error(`Prenda con ID ${prendaId} no encontrada en pedido ${pedidoId}. No se puede asignar encargado a una prenda desconocida.`);
            }
        } else {
            throw new Error('El pedido no tiene prendas asociadas');
        }
        
        console.log('[cargarDatosParaAgregarProceso] ✅ Datos cargados correctamente');
        console.log('[cargarDatosParaAgregarProceso] currentOrderData:', window.currentOrderData);
        console.log('[cargarDatosParaAgregarProceso] currentPrendaData:', window.currentPrendaData);
        console.log('[cargarDatosParaAgregarProceso] Verificación final:', {
            hasOrderData: !!window.currentOrderData,
            hasPrendaData: !!window.currentPrendaData,
            orderNumero: window.currentOrderData?.numero_pedido,
            prendaId: window.currentPrendaData?.id,
            'pedido_produccion_id_para_endpoint': window.currentOrderData?.numero_pedido
        });
        
    } catch (error) {
        console.error('[cargarDatosParaAgregarProceso] Error:', error);
        throw error;
    }
}

// ⚠️ closeModalOverlay() eliminada - versión en bundle.js (fallback puro)
// ⚠️ closeDropdownRecibos() original eliminada - versión mejorada a continuación

/**
 * FUNCIÓN MEJORADA: Cierra DROP todos los dropdowns
 */
window.closeDropdownRecibos = function() {
    console.log('[closeDropdownRecibos] Cerrando todos los dropdowns...');
    
    // Remover clase dropdown-opening de TODOS los botones
    const botonesConClase = document.querySelectorAll('.btn-ver-dropdown.dropdown-opening');
    console.log('[closeDropdownRecibos] Botones con clase dropdown-opening:', botonesConClase.length);
    
    botonesConClase.forEach(btn => {
        btn.classList.remove('dropdown-opening');
    });
    
    // Cerrar todos los dropdowns
    const dropdowns = document.querySelectorAll('.dropdown-menu-recibos');
    console.log('[closeDropdownRecibos] Dropdowns encontrados:', dropdowns.length);
    
    let cerradosCount = 0;
    dropdowns.forEach(dropdown => {
        if (dropdown.style.display !== 'none') {
            dropdown.style.display = 'none';
            dropdown.style.pointerEvents = 'none';
            cerradosCount++;
        }
    });
    
    console.log('[closeDropdownRecibos] ✅ Dropdowns cerrados:', cerradosCount);
};

/**
 * FUNCIÓN HELPER 1: Extrae datos del botón
 */
function extraerDataDelBoton(button) {
    return {
        menuId: button.getAttribute('data-menu-id'),
        pedidoId: button.getAttribute('data-pedido-id'),
        prendaId: button.getAttribute('data-prenda-id'),
        tipoRecibo: button.getAttribute('data-tipo-recibo') || 'COSTURA',
        esParcial: String(button.getAttribute('data-es-parcial') || '').toLowerCase() === 'true',
        parcialId: button.getAttribute('data-pedido-parcial-id')
    };
}

/**
 * FUNCIÓN HELPER 2: Construye los botones del dropdown (usa clases CSS en dropdowns-recibos.css)
 */
function construirBotonesDropdown(data) {
    const { pedidoId, prendaId, tipoRecibo, esParcial, parcialId } = data;
    
    const onClickDetalles = esParcial && parcialId
        ? `openOrderDetailModalWithParcial(${parcialId}, ${prendaId || 'null'}, '${tipoRecibo}', ${pedidoId}); closeDropdownRecibos()`
        : `openOrderDetailModalWithProcess(${pedidoId}, ${prendaId || 'null'}, '${tipoRecibo}'); closeDropdownRecibos()`;
    
    return `
        <button class="dropdown-item-btn" onclick="${onClickDetalles}">
            <i class="fas fa-eye"></i> Ver Detalles
        </button>
        <div class="dropdown-divider"></div>
        <button class="dropdown-item-btn" onclick="abrirModalSeguimiento(${pedidoId}, ${prendaId || 'null'}); closeDropdownRecibos()">
            <i class="fas fa-tasks"></i> Seguimiento
        </button>
    `;
}

/**
 * FUNCIÓN HELPER 3: Posiciona el dropdown de forma inteligente
 */
function posicionarDropdown(dropdown, btnVer) {
    const rect = btnVer.getBoundingClientRect();
    dropdown.style.top = (rect.bottom + 5) + 'px';
    dropdown.style.left = rect.left + 'px';
    dropdown.style.display = 'block';
    dropdown.style.pointerEvents = 'auto';

    // Ajustar si se sale de la pantalla por la derecha o abajo
    setTimeout(() => {
        const dropRect = dropdown.getBoundingClientRect();
        if (dropRect.right > window.innerWidth) {
            dropdown.style.left = (window.innerWidth - dropRect.width - 10) + 'px';
        }
        if (dropRect.bottom > window.innerHeight) {
            dropdown.style.top = (rect.top - dropRect.height - 5) + 'px';
        }
    }, 10);
}

/**
 * Crear dropdown dinámico para recibos-costura (refactorizado)
 * Se crea en #dropdowns-container con position:fixed para evitar overflow clipping
 */
function crearDropdownRecibos(button) {
    const data = extraerDataDelBoton(button);
    
    // Verificar si ya existe
    const existing = document.getElementById(data.menuId);
    if (existing) return existing;

    const container = document.getElementById('dropdowns-container');
    if (!container) {
        console.error('[RecibosDropdown] No se encontró #dropdowns-container');
        return null;
    }

    const dropdown = document.createElement('div');
    dropdown.id = data.menuId;
    dropdown.className = 'dropdown-menu-recibos';

    dropdown.innerHTML = construirBotonesDropdown(data);
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
        const dropdownMenu = e.target.closest('.dropdown-menu-recibos');
        const itemBtn = e.target.closest('.dropdown-item-btn');

        if (btnVer) {
            e.preventDefault();
            e.stopPropagation();

            console.log('[RecibosDropdown] Click en botón Ver');

            // ✅ TOGGLE LOGIC: Si el botón ya tiene la clase dropdown-opening, CERRAR
            if (btnVer.classList.contains('dropdown-opening')) {
                console.log('[RecibosDropdown] ✅ TOGGLE: Botón ya estaba abierto, cerrando...');
                closeDropdownRecibos();
                return;
            }

            // ✅ Si no está abierto, abrirlo
            console.log('[RecibosDropdown] Abriendo dropdown...');
            
            // Agregar clase para desactivar transform mientras el dropdown esté abierto
            btnVer.classList.add('dropdown-opening');
            
            // Cerrar todos los dropdowns abiertos primero (remueve clase automáticamente)
            closeDropdownRecibos();

            // Crear dropdown si no existe
            const dropdown = crearDropdownRecibos(btnVer);
            if (!dropdown) {
                btnVer.classList.remove('dropdown-opening');
                return;
            }

            // Re-agregar la clase después de cerrar otros
            btnVer.classList.add('dropdown-opening');

            // Posicionar debajo del botón (con ajustes automáticos)
            posicionarDropdown(dropdown, btnVer);
            
            // Quitar focus del botón para evitar estados hover/focus
            btnVer.blur();

            console.log('[RecibosDropdown] Dropdown abierto');
            return;
        }

        // ✅ CLICK EN ITEM DEL DROPDOWN - ejecutar acción Y cerrar dropdown
        if (itemBtn && dropdownMenu) {
            console.log('[RecibosDropdown] Click en item del dropdown - cerrando después de ejecutar acción');
            
            // Cerrar inmediatamente después de que el click se procese
            setTimeout(() => {
                closeDropdownRecibos();
            }, 50);
            
            return;
        }

        // ✅ CLICK EN DROPDOWN (pero no en item) - NO cerrar
        if (dropdownMenu && !itemBtn) {
            console.log('[RecibosDropdown] Click dentro del dropdown - permitiendo propagación');
            return;
        }

        // ✅ CLICK FUERA - cerrar dropdown
        if (!btnVer && !dropdownMenu) {
            const hayDropdownAbierto = !!document.querySelector('.dropdown-menu-recibos[style*="display: block"]');
            if (hayDropdownAbierto) {
                console.log('[RecibosDropdown] Click fuera - cerrando dropdown');
                closeDropdownRecibos();
            }
        }
    });
});

// ======= CAMPANA DE NOTIFICACIONES PARA RECIBOS DE COSTURA =======
console.log('[🔔 CAMPANA COSTURA] Sistema iniciado');

async function cargarConteoRecibosCorte() {
    try {
        const response = await fetch('/api/recibos-costura/ejecutando-corte', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (response.ok) {
            const data = await response.json();
            const total = data.total || 0;
            const recibos = data.recibos || [];
            
            console.log('[🔔 CAMPANA COSTURA] Total:', total);
            
            // Actualizar badge
            const badge = document.getElementById('costuraBadge');
            if (badge) {
                badge.textContent = total;
                if (total > 0) {
                    badge.classList.add('show');
                } else {
                    badge.classList.remove('show');
                }
            }
            
            // Poblar lista
            const list = document.getElementById('costuraNotifList');
            if (list) {
                if (recibos.length > 0) {
                    list.innerHTML = '';
                    recibos.forEach(function(recibo) {
                        const item = document.createElement('div');
                        item.className = 'costura-notif-item';
                        
                        // Contenido de la notificación
                        const content = document.createElement('div');
                        content.className = 'costura-notif-content';
                        content.innerHTML = 
                            '<p class="costura-notif-number">Recibo #' + recibo.numero_recibo + '</p>' +
                            '<p class="costura-notif-cliente">' + recibo.cliente + '</p>' +
                            '<p class="costura-notif-fecha">' + recibo.fecha + '</p>';
                        
                        // Botón visto
                        const vistaBtn = document.createElement('button');
                        vistaBtn.className = 'costura-notif-visto-btn';
                        vistaBtn.textContent = 'Visto';
                        vistaBtn.dataset.reciboId = recibo.id;
                        vistaBtn.addEventListener('click', async function(e) {
                            e.stopPropagation();
                            await marcarReciboVisto(recibo.id, item);
                        });
                        
                        item.appendChild(content);
                        item.appendChild(vistaBtn);
                        list.appendChild(item);
                    });
                } else {
                    list.innerHTML = '<div class="costura-notif-empty">Sin recibos en ejecución</div>';
                }
            }
        }
    } catch (error) {
        console.error('[🔔 CAMPANA COSTURA] Error:', error);
    }
}

async function marcarReciboVisto(reciboId, itemElement) {
    try {
        const response = await fetch('/api/recibos-costura/' + reciboId + '/marcar-visto-corte', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            if (data.success) {
                // Remover la notificación de forma suave
                itemElement.style.opacity = '0';
                itemElement.style.transform = 'translateX(10px)';
                setTimeout(function() {
                    itemElement.remove();
                    // Recargar el conteo
                    cargarConteoRecibosCorte();
                }, 200);
                console.log('[✓ VISTO] Recibo marcado:', reciboId);
            }
        }
    } catch (error) {
        console.error('[✗ ERROR VISTO] No se pudo marcar el recibo:', error);
    }
}

function setupCosturaNotifications() {
    const bellBtn = document.getElementById('costuraBellBtn');
    const dropdown = document.getElementById('costuraDropdown');
    const clearBtn = document.getElementById('costuraClearBtn');
    
    if (!bellBtn || !dropdown) return;
    
    bellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('show');
    });
    
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.remove('show');
        });
    }
    
    document.addEventListener('click', function(e) {
        if (!bellBtn.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        cargarConteoRecibosCorte();
        setupCosturaNotifications();
        setInterval(cargarConteoRecibosCorte, 30000);
        
        // 🔴 LISTENER EN TIEMPO REAL PARA RECIBOS APROBADOS
        initializeReciboAprobadoListener();
    });
} else {
    cargarConteoRecibosCorte();
    setupCosturaNotifications();
    setInterval(cargarConteoRecibosCorte, 30000);
    
    // 🔴 LISTENER EN TIEMPO REAL PARA RECIBOS APROBADOS
    initializeReciboAprobadoListener();
}

/**
 * 🔴 LISTENER EN TIEMPO REAL - Escucha cuando se aprueban insumos
 * Se conecta al evento 'recibo.aprobado' del canal 'recibos-costura'
 */
function initializeReciboAprobadoListener() {
    console.log('🔴 [ReciboAprobado] Inicializando listener en tiempo real...');
    
    // Esperar a que Echo esté listo
    window.waitForEcho(function() {
        try {
            console.log('🔴 [ReciboAprobado] Echo está listo, conectando al canal...');
            
            // Conectar al canal y escuchar el evento
            window.EchoInstance.channel('recibos-costura')
                .listen('recibo.aprobado', function(data) {
                    console.log('🔴 [ReciboAprobado] ¡Evento recibido en tiempo real!', data);
                    
                    // Mostrar notificación visual
                    showRecibAprobadoNotification(data);
                    
                    // Recargar la tabla dinámicamente
                    recargarTablaRecibosEnTiempoReal(data);
                });
            
            console.log('✅ [ReciboAprobado] Listener configurado correctamente');
            
        } catch (error) {
            console.error('❌ [ReciboAprobado] Error al configurar el listener:', error);
        }
    });
}

/**
 * 🔴 Mostrar notificación visual cuando se aprueba un recibo
 */
function showRecibAprobadoNotification(data) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        z-index: 10000001;
        font-weight: 600;
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
        word-wrap: break-word;
    `;
    
    // Crear contenido con datos del recibo
    const contenido = `
        <div style="margin-bottom: 8px;">
            ✅ <strong>Recibo Aprobado</strong>
        </div>
        <div style="font-size: 13px; opacity: 0.9;">
            <div>📋 Recibo #${data.consecutivo}</div>
            <div>👤 Cliente: ${data.cliente || 'N/A'}</div>
            <div>📦 Área: ${data.area || 'N/A'}</div>
        </div>
    `;
    
    notification.innerHTML = contenido;
    document.body.appendChild(notification);
    
    // Agregar animación CSS si no existe
    if (!document.getElementById('slideInRightStyle')) {
        const style = document.createElement('style');
        style.id = 'slideInRightStyle';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Eliminar la notificación después de 5 segundos
    setTimeout(function() {
        notification.style.animation = 'slideInRight 0.3s ease-out reverse';
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 5000);
}

/**
 * 🔴 Recargar la tabla dinámicamente cuando se aprueba un recibo
 */
function recargarTablaRecibosEnTiempoReal(data) {

    try {
        // Hacer solicitud AJAX para obtener los recibos actualizados
        fetch(window.location.pathname + '?ajax=1', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.recibos && result.recibos.html) {
                console.log('🔴 [ReciboAprobado] HTML recibido, actualizando tabla...');
                
                // Actualizar solo el tbody
                const tbody = document.getElementById('tablaRecibosBody');
                if (tbody) {
                    tbody.innerHTML = result.recibos.html;
                    console.log('✅ [ReciboAprobado] Tabla actualizada correctamente');
                    
                    // Reinicializar event listeners en las nuevas filas
                    reinitializeTableListeners();
                }
            }
        })
        .catch(error => {
            console.error('❌ [ReciboAprobado] Error al recargar tabla:', error);
            // Como fallback, recargar toda la página después de 3 segundos
            setTimeout(() => {
                console.log('🔄 [ReciboAprobado] Recargando página como fallback...');
                window.location.reload();
            }, 3000);
        });
        
    } catch (error) {
        console.error('❌ [ReciboAprobado] Error en recargarTablaRecibosEnTiempoReal:', error);
    }
}
