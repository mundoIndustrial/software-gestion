@extends('layouts.app')

@section('title', 'Recibos de Reflectivo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Table Component (reutiliza el componente de costura, los datos son genéricos) -->
            <x-recibos.recibos-costura-table :recibos="$recibos" :totalCantidadGlobal="$totalCantidadGlobal ?? 0" />
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

<!-- Contenedor para Toast Notifications -->
<div class="toast-container" id="toastContainer"></div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/recibos-costura.css') }}?v={{ time() }}">

<!-- Estilos adicionales para el modal de agregar proceso -->
<style>
.add-proceso-modal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    opacity: 1 !important;
    visibility: visible !important;
    z-index: 10000000 !important;
}

.add-proceso-modal.show .add-proceso-content {
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: slideUp 0.3s ease-out !important;
    margin: auto !important;
    position: relative !important;
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
    max-width: 500px !important;
    width: 90% !important;
    max-height: 90vh !important;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.area-badge-clickable {
    position: relative;
    overflow: hidden;
}

.area-badge-clickable::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.area-badge-clickable:hover::before {
    left: 100%;
}

/* Colores personalizados para badges de área */
.badge.bg-purple {
    background-color: #8b5cf6 !important;
    color: white !important;
}

.badge.bg-teal {
    background-color: #14b8a6 !important;
    color: white !important;
}

.badge.bg-orange {
    background-color: #f97316 !important;
    color: white !important;
}

.badge.bg-pink {
    background-color: #ec4899 !important;
    color: white !important;
}

/* Mejorar contraste para badges existentes */
.badge.bg-success {
    background-color: #22c55e !important;
    color: white !important;
}

.badge.bg-info {
    background-color: #06b6d4 !important;
    color: white !important;
}

.badge.bg-primary {
    background-color: #3b82f6 !important;
    color: white !important;
}

.badge.bg-warning {
    background-color: #f59e0b !important;
    color: white !important;
}

.badge.bg-secondary {
    background-color: #6b7280 !important;
    color: white !important;
}

/* Toast Notifications */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 999999;
    pointer-events: none;
}

.toast {
    background: white;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 300px;
    max-width: 400px;
    pointer-events: auto;
    animation: slideInRight 0.3s ease-out;
    position: relative;
    overflow: hidden;
}

.toast::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    animation: shimmer 2s infinite;
}

.toast.success {
    border-left-color: #22c55e;
    background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
}

.toast.error {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
}

.toast.info {
    border-left-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
}

.toast-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-weight: bold;
    color: white;
    font-size: 14px;
}

.toast.success .toast-icon {
    background: #22c55e;
}

.toast.error .toast-icon {
    background: #ef4444;
}

.toast.info .toast-icon {
    background: #3b82f6;
}

.toast-content {
    flex: 1;
}

.toast-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
    color: #1f2937;
}

.toast-message {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

.toast-close {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 20px;
    height: 20px;
    border: none;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #6b7280;
    transition: all 0.2s ease;
}

.toast-close:hover {
    background: rgba(0, 0, 0, 0.2);
    color: #1f2937;
}

.toast.removing {
    animation: slideOutRight 0.3s ease-out forwards;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

@keyframes shimmer {
    0% {
        left: -100%;
    }
    100% {
        left: 100%;
    }
}
</style>
@endpush

@push('scripts')
<!-- Scripts Component -->
<x-recibos.recibos-costura-scripts />

<!-- Script con funciones globales adicionales para recibos-reflectivo -->
<script>
// Definir funciones de filtro globalmente
window.openFilterModal = function(filterType) {
    console.log('[Filtros] openFilterModal llamado con:', filterType);
    
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        
        modal.setAttribute('data-filter-type', filterType);
        
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
        
        loadFilterOptions(filterType);
    }
};

function loadFilterOptions(filterType) {
    const optionsContainer = document.getElementById('filterOptions');
    if (!optionsContainer) return;
    
    const options = getDynamicFilterOptions(filterType);
    
    if (options.length === 0) {
        optionsContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay datos disponibles para filtrar</div>';
        return;
    }
    
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
}

function getDynamicFilterOptions(filterType) {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) return [];
    
    const options = new Set();
    const columnIndex = getColumnIndex(filterType);
    
    if (columnIndex === -1) return [];
    
    const rows = tbody.querySelectorAll('tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > columnIndex) {
            let cellText = '';
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

function getColumnIndex(filterType) {
    const columnMap = {
        'estado': 1,
        'area': 2,
        'total_dias': 3,
        'numero_recibo': 4,
        'cliente': 5,
        'descripcion': 6,
        'cantidad': 7,
        'novedades': 8,
        'fecha_creacion': 9,
        'fecha_estimada': 10,
        'encargado': 11
    };
    return columnMap[filterType] || -1;
}

window.closeFilterModal = function() {
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
    }
};

window.resetFilters = function() {
    const checkboxes = document.querySelectorAll('#filterOptions input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = false);
    
    const tbody = document.getElementById('tablaRecibosBody');
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => { row.style.display = ''; });
    }
    
    if (window.activeFilters) {
        window.activeFilters = {};
    }
};

window.applyFilters = function() {
    const modal = document.getElementById('filterModal');
    const filterType = modal ? modal.getAttribute('data-filter-type') : null;
    
    if (!filterType) {
        window.closeFilterModal();
        return;
    }
    
    const checkboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
    const selectedValues = Array.from(checkboxes).map(cb => cb.value);
    
    if (selectedValues.length === 0) {
        resetFilters();
        return;
    }
    
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) {
        window.closeFilterModal();
        return;
    }
    
    const columnIndex = getColumnIndex(filterType);
    if (columnIndex === -1) {
        window.closeFilterModal();
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > columnIndex) {
            let cellText = '';
            if (filterType === 'descripcion') {
                cellText = cells[columnIndex].getAttribute('data-descripcion-detallada') || '';
            } else {
                cellText = cells[columnIndex].textContent.trim();
            }
            
            const isVisible = selectedValues.some(selectedValue => {
                if (filterType === 'descripcion') {
                    return cellText === selectedValue;
                } else {
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
    
    if (!window.activeFilters) {
        window.activeFilters = {};
    }
    window.activeFilters[filterType] = selectedValues;
    
    window.closeFilterModal();
};

window.selectAllCheckboxFilters = function(filterType) {
    const checkboxes = document.querySelectorAll(`#filterOptions-${filterType} input[type="checkbox"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
};

window.filterCheckboxOptions = function(filterType) {
    const searchTerm = document.querySelector('.filter-search').value.toLowerCase();
    const options = document.querySelectorAll(`#filterOptions-${filterType} .filter-option`);
    
    options.forEach(option => {
        const label = option.querySelector('label').textContent.toLowerCase();
        option.style.display = label.includes(searchTerm) ? 'block' : 'none';
    });
};

// Cargar nombres de prendas al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DOMContentLoaded]  Cargando nombres de prendas en recibos-reflectivo');
    
    const filasRecibos = document.querySelectorAll('#tablaRecibosBody tr[data-orden-id]');
    
    filasRecibos.forEach(fila => {
        const reciboId = fila.getAttribute('data-orden-id');
        const descripcionElemento = fila.querySelector('.descripcion-prenda-texto');
        
        if (descripcionElemento) {
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
                fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                    .then(response => response.json())
                    .then(datos => {
                        if (datos.data && typeof datos.data === 'object') {
                            datos = datos.data;
                        }
                        
                        if (datos.prendas && Array.isArray(datos.prendas) && datos.prendas.length > 0) {
                            const primeraPrenda = datos.prendas[0];
                            const nombrePrenda = primeraPrenda.nombre || primeraPrenda.nombre_prenda || 'Sin nombre';
                            descripcionElemento.textContent = nombrePrenda;
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
    const fila = document.querySelector(`tr[data-orden-id="${reciboId}"]`);
    if (!fila) {
        alert('No se encontró el recibo');
        return;
    }

    const botonVer = fila.querySelector('.btn-ver-dropdown');
    const esParcial = botonVer
        ? String(botonVer.getAttribute('data-es-parcial') || '').toLowerCase() === 'true'
        : false;
    const pedidoParcialId = botonVer ? Number(botonVer.getAttribute('data-pedido-parcial-id') || 0) : 0;
    const pedidoIdFila = Number(fila.getAttribute('data-pedido-id') || 0);
    const prendaIdFila = botonVer ? Number(botonVer.getAttribute('data-prenda-id') || 0) : 0;

    if (esParcial && pedidoParcialId > 0 && pedidoIdFila > 0 && prendaIdFila > 0 && typeof window.openOrderDetailModalWithParcial === 'function') {
        window.openOrderDetailModalWithParcial(pedidoParcialId, prendaIdFila, 'REFLECTIVO', pedidoIdFila, 'REFLECTIVO ANEXO');
        return;
    }
    
    const enlacePedido = fila.querySelector('a[href*="/registros/"]');
    let pedidoId = null;
    
    if (enlacePedido) {
        const href = enlacePedido.getAttribute('href');
        const pedidoIdMatch = href.match(/\/registros\/(\d+)/);
        if (pedidoIdMatch) {
            pedidoId = parseInt(pedidoIdMatch[1]);
        }
    }
    
    if (!pedidoId) {
        const pedidoIdAttr = fila.getAttribute('data-pedido-id');
        if (pedidoIdAttr) {
            pedidoId = parseInt(pedidoIdAttr);
        }
    }
    
    if (!pedidoId) {
        const dropdownDiaEntrega = fila.querySelector('.dia-entrega-dropdown');
        if (dropdownDiaEntrega) {
            const dropdownIdAttr = dropdownDiaEntrega.getAttribute('data-orden-id');
            if (dropdownIdAttr) {
                pedidoId = parseInt(dropdownIdAttr);
            }
        }
    }
    
    if (!pedidoId) {
        alert('No se encontró información del pedido asociada a este recibo.');
        return;
    }
    
    fetch(`/registros/${pedidoId}/recibos-datos`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            return response.json();
        })
        .then(datos => {
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }
            
            if (!datos.prendas || !Array.isArray(datos.prendas) || datos.prendas.length === 0) {
                alert('No se encontraron prendas para este pedido.');
                return;
            }
            
            const primeraPrenda = datos.prendas[0];
            const prendaId = primeraPrenda.id;
            
            if (window.pedidosRecibosModule) {
                window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, 'reflectivo');
            } else {
                alert('Módulo de recibos no disponible. Por favor recargue la página.');
            }
        })
        .catch(error => {
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
}

function abrirModalSeguimiento(pedidoId, prendaIdTarget) {
    closeDropdownRecibos();
    
    if (typeof openOrderTracking === 'function') {
        openOrderTracking(pedidoId, false).then(() => {
            let prendas = null;
            if (window.currentOrderData && window.currentOrderData.prendas) {
                prendas = window.currentOrderData.prendas;
            } else if (window.currentOrderData && window.currentOrderData.data && window.currentOrderData.data.prendas) {
                prendas = window.currentOrderData.data.prendas;
            } else if (window.prendasData && window.prendasData.length > 0) {
                prendas = window.prendasData;
            }
            
            if (prendas && prendas.length > 0) {
                let prendaSeleccionada = null;
                if (prendaIdTarget) {
                    prendaSeleccionada = prendas.find(p =>
                        String(p.id) === String(prendaIdTarget) ||
                        String(p.prenda_pedido_id) === String(prendaIdTarget)
                    );
                }
                
                if (!prendaSeleccionada) {
                    prendaSeleccionada = prendas[0];
                }
                
                window.currentPrendaData = prendaSeleccionada;
                abrirModalSeguimientoDirecto(pedidoId, prendaIdTarget);
            } else {
                alert('No hay prendas disponibles para este pedido');
            }
        }).catch(error => {
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
    } else {
        alert('Sistema de seguimiento no disponible');
    }
}

function abrirModalSeguimientoDirecto(pedidoId, prendaIdTarget) {
    const trackingOverlay = document.getElementById('trackingModalOverlay');
    if (trackingOverlay) {
        trackingOverlay.style.display = 'block';
    } else {
        alert('Modal de seguimiento no disponible');
        return;
    }
    
    const trackingModal = document.getElementById('orderTrackingModal');
    if (trackingModal) {
        trackingModal.style.display = 'flex';
        trackingModal.classList.add('show');
        
        let urlConsecutivo = `/registros/${pedidoId}/consecutivo-costura`;
        if (prendaIdTarget) {
            urlConsecutivo += `?prenda_id=${prendaIdTarget}`;
        }
        
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
                    if (headerSubtitleElement) headerSubtitleElement.textContent = `REFLECTIVO #${data.consecutivo}`;
                } else {
                    const reciboElement = document.getElementById('trackingOrderRecibo');
                    if (reciboElement) reciboElement.textContent = '-';
                    
                    const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
                    if (headerSubtitleElement) headerSubtitleElement.textContent = 'REFLECTIVO #?';
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
                
                if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                    showPrendaTracking(window.currentPrendaData);
                }
            })
            .catch(error => {
                console.error('Error al obtener consecutivo:', error);
                if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                    showPrendaTracking(window.currentPrendaData);
                }
            });
    }
}

// Función para abrir el modal de agregar proceso desde el badge del área
window.abrirModalAgregarProcesoDesdeArea = function(areaSeleccionada, pedidoId, prendaId) {
    closeDropdownRecibos();
    
    if (!pedidoId) {
        alert('No se puede identificar el pedido asociado');
        return;
    }
    
    //  Cargar datos ANTES de abrir el modal (esperar a que termine)
    cargarDatosParaAgregarProceso(pedidoId, prendaId, areaSeleccionada)
        .then(() => {
            //  Verificar que los datos se cargaron correctamente
            if (!window.currentOrderData || !window.currentPrendaData) {
                throw new Error('No se pudieron cargar los datos necesarios');
            }
            
            //  AHORA sí, abrir el modal
            const modal = document.getElementById('addProcesoModal');
            if (!modal) {
                alert('Modal de agregar proceso no disponible');
                return;
            }
            
            modal.setAttribute('data-pedido-id', pedidoId);
            modal.setAttribute('data-prenda-id', prendaId || '');
            modal.setAttribute('data-area', areaSeleccionada);
            
            modal.style.display = 'flex';
            modal.classList.add('show');
            
            const selectArea = document.getElementById('procesoArea');
            if (selectArea) {
                selectArea.value = areaSeleccionada;
            }
            
            const inputEncargado = document.getElementById('procesoEncargado');
            if (inputEncargado) {
                inputEncargado.value = '';
                inputEncargado.focus();
            }
            
            //  Configurar evento del botón guardar
            const btnConfirm = document.getElementById('btnConfirmAddProceso');
            if (btnConfirm) {
                btnConfirm.removeEventListener('click', handleAgregarProcesoDesdeBadge);
                btnConfirm.addEventListener('click', handleAgregarProcesoDesdeBadge);
            }
        })
        .catch(error => {
            console.error('[abrirModalAgregarProcesoDesdeArea] Error:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
    };

function verificarDatosAntesDeGuardar(event) {
    if (!window.currentOrderData || !window.currentPrendaData) {
        const modal = document.getElementById('addProcesoModal');
        if (modal) {
            const pedidoId = modal.getAttribute('data-pedido-id');
            const prendaId = modal.getAttribute('data-prenda-id');
            const area = modal.getAttribute('data-area');
            
            if (pedidoId) {
                cargarDatosParaAgregarProceso(pedidoId, prendaId, area)
                    .then(() => {
                        if (window.currentOrderData && window.currentPrendaData) {
                            handleAgregarProcesoDesdeBadge();
                        } else {
                            alert('Error: No se pudieron cargar los datos necesarios. Por favor, recarga la página.');
                        }
                    })
                    .catch(error => {
                        alert('Error al cargar los datos: ' + error.message);
                    });
                
                event.preventDefault();
                event.stopPropagation();
                return false;
            }
        }
    }
    
    if (!window.currentOrderData || !window.currentPrendaData) {
        alert('Error: No hay datos de la prenda o pedido. Por favor, recarga la página e intenta nuevamente.');
        event.preventDefault();
        event.stopPropagation();
        return false;
    }
    
    if (typeof handleAgregarProcesoDesdeBadge === 'function') {
        handleAgregarProcesoDesdeBadge();
    } else {
        alert('Error: Sistema no disponible. Por favor, recarga la página.');
    }
}

async function handleAgregarProcesoDesdeBadge() {
    try {
        const btnContent = document.getElementById('addProcesoButtonContent');
        const btnLoading = document.getElementById('addProcesoButtonLoading');
        const btnConfirm = document.getElementById('btnConfirmAddProceso');
        
        if (btnContent && btnLoading && btnConfirm) {
            btnContent.style.display = 'none';
            btnLoading.style.display = 'flex';
            btnConfirm.disabled = true;
        }

        const area = document.getElementById('procesoArea').value;
        const encargado = document.getElementById('procesoEncargado').value.toUpperCase();

        if (!area) {
            showError('Por favor selecciona un área/proceso');
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
            showError('Por favor ingresa el nombre del encargado');
            if (btnContent && btnLoading && btnConfirm) {
                btnContent.style.display = 'flex';
                btnLoading.style.display = 'none';
                btnConfirm.disabled = false;
            }
            return;
        }

        if (!window.currentOrderData || !window.currentPrendaData) {
            showError('No hay datos de la prenda o pedido');
            if (btnContent && btnLoading && btnConfirm) {
                btnContent.style.display = 'flex';
                btnLoading.style.display = 'none';
                btnConfirm.disabled = false;
            }
            return;
        }

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

        if (!response.ok) throw new Error('Error al guardar proceso');

        const result = await response.json();
        
        //  Mostrar mensaje diferente según si fue creado o actualizado
        const mensaje = result.action === 'actualizado'
            ? 'Proceso actualizado correctamente'
            : 'Proceso agregado correctamente';
        showSuccess(mensaje);
        
        // Limpiar formulario
        limpiarFormularioProceso();

        // Cerrar modal de agregar proceso
        const modal = document.getElementById('addProcesoModal');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
        
        setTimeout(() => { window.location.reload(); }, 1500);

    } catch (error) {
        showError('Error al guardar proceso: ' + error.message);
    } finally {
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

function limpiarFormularioProceso() {
    const selectArea = document.getElementById('procesoArea');
    const inputEncargado = document.getElementById('procesoEncargado');
    if (selectArea) selectArea.value = '';
    if (inputEncargado) inputEncargado.value = '';
}

function showSuccess(message, title = 'Éxito') {
    showToast(message, 'success', title);
}

function showError(message, title = 'Error') {
    showToast(message, 'error', title);
}

function showToast(message, type = 'info', title = '') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ');
    const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    toast.id = toastId;
    
    toast.innerHTML = `
        <div class="toast-icon">${icon}</div>
        <div class="toast-content">
            ${title ? `<div class="toast-title">${title}</div>` : ''}
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="removeToast('${toastId}')">×</button>
    `;
    
    container.appendChild(toast);
    setTimeout(() => { removeToast(toastId); }, 5000);
}

function removeToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast && !toast.classList.contains('removing')) {
        toast.classList.add('removing');
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
    }
}

function clearAllToasts() {
    const container = document.getElementById('toastContainer');
    if (container) {
        container.querySelectorAll('.toast').forEach(toast => removeToast(toast.id));
    }
}

async function cargarDatosParaAgregarProceso(pedidoId, prendaId, areaSeleccionada) {
    try {
        //  VALIDAR QUE SE PROPORCIONE UNA PRENDA ESPECÍFICA
        if (!prendaId || prendaId === 'null' || prendaId === null) {
            throw new Error('CRÍTICO: No se proporcionó una prenda específica. No se puede asignar encargado sin prenda definida.');
        }
        
        const response = await fetch(`/registros/${pedidoId}/recibos-datos`);
        if (!response.ok) throw new Error('Error al cargar datos del pedido');
        
        const result = await response.json();
        const data = result.data || result;
        
        const orderData = {
            ...data,
            numero_pedido: data.numero_pedido || data.id || pedidoId,
            pedido: data.numero_pedido || data.id || pedidoId
        };
        
        window.currentOrderData = orderData;
        window.currentPedidoId = pedidoId;
        window.currentPrendaId = prendaId;
        window.currentArea = areaSeleccionada;
        
        if (data.prendas && Array.isArray(data.prendas)) {
            let prendaEncontrada = null;
            
            //  SER ESTRICTO: Buscar EXACTAMENTE la prenda especificada, SIN FALLBACK
            prendaEncontrada = data.prendas.find(p =>
                String(p.id) === String(prendaId) ||
                String(p.prenda_pedido_id) === String(prendaId)
            );
            
            if (prendaEncontrada) {
                window.currentPrendaData = prendaEncontrada;
            } else {
                // 🛑 SIN FALLBACK: Si no se encuentra la prenda específica, lanzar error
                throw new Error(`Prenda con ID ${prendaId} no encontrada en pedido ${pedidoId}. No se puede asignar encargado a una prenda desconocida.`);
            }
        } else {
            throw new Error('El pedido no tiene prendas asociadas');
        }
    } catch (error) {
        console.error('[cargarDatosParaAgregarProceso] Error:', error);
        throw error;
    }
}

function closeModalOverlay() {
    if (window.pedidosRecibosModule) {
        window.pedidosRecibosModule.cerrarRecibo();
    }

    const modal = document.getElementById('modal-overlay');
    if (modal) modal.style.display = 'none';
    const wrapper = document.getElementById('order-detail-modal-wrapper');
    if (wrapper) wrapper.style.display = 'none';

    const galeria = document.getElementById('galeria-modal-costura');
    if (galeria) galeria.remove();
    const btnCerrarInsumos = document.getElementById('btn-cerrar-modal-insumos');
    if (btnCerrarInsumos) btnCerrarInsumos.remove();
    const btnCerrarDinamico = document.getElementById('btn-cerrar-modal-dinamico');
    if (btnCerrarDinamico) btnCerrarDinamico.remove();
    const floatingContainer = document.getElementById('floating-buttons-container');
    if (floatingContainer) floatingContainer.style.display = 'none';
}

function closeDropdownRecibos() {
    document.querySelectorAll('.dropdown-menu-recibos').forEach(menu => {
        menu.style.display = 'none';
        menu.style.pointerEvents = 'none';
    });
}

function abrirDetallesReciboReflectivo(button) {
    const pedidoId = Number(button?.getAttribute('data-pedido-id') || 0);
    const prendaId = Number(button?.getAttribute('data-prenda-id') || 0);
    const numeroRecibo = String(button?.getAttribute('data-numero-recibo') || '').trim();
    const esParcial = String(button?.getAttribute('data-es-parcial') || '').toLowerCase() === 'true';
    const pedidoParcialId = Number(button?.getAttribute('data-pedido-parcial-id') || 0);

    if (!pedidoId || !prendaId) {
        alert('No se pudo identificar el pedido o la prenda asociados al recibo.');
        return;
    }

    if (esParcial && pedidoParcialId > 0 && typeof window.openOrderDetailModalWithParcial === 'function') {
        window.openOrderDetailModalWithParcial(pedidoParcialId, prendaId, 'REFLECTIVO', pedidoId, 'REFLECTIVO ANEXO');
        return;
    }

    if (typeof window.openOrderDetailModalWithProcess === 'function') {
        window.openOrderDetailModalWithProcess(pedidoId, prendaId, 'REFLECTIVO', null, numeroRecibo || null, pedidoParcialId > 0 ? pedidoParcialId : null);
        return;
    }

    alert('Módulo de recibos no disponible. Por favor recargue la página.');
}

function crearDropdownRecibos(button) {
    const menuId = button.getAttribute('data-menu-id');
    const pedidoId = button.getAttribute('data-pedido-id');
    const prendaId = button.getAttribute('data-prenda-id');

    const existing = document.getElementById(menuId);
    if (existing) return existing;

    const container = document.getElementById('dropdowns-container');
    if (!container) return null;

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
        <button onclick="abrirDetallesReciboReflectivo(this); closeDropdownRecibos()" data-pedido-id="${pedidoId}" data-prenda-id="${prendaId || ''}" data-numero-recibo="${button.getAttribute('data-numero-recibo') || ''}" data-es-parcial="${button.getAttribute('data-es-parcial') || 'false'}" data-pedido-parcial-id="${button.getAttribute('data-pedido-parcial-id') || ''}" style="
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

// Ocultar el botón Volver y activar dropdowns
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DOMContentLoaded]  Inicializando dropdowns en recibos-reflectivo');

    if (window.location.pathname.includes('/recibos-reflectivo')) {
        const botonVolver = document.getElementById('backToPrendasBtn');
        if (botonVolver) {
            botonVolver.style.display = 'none';
        }
    }

    document.addEventListener('click', function(e) {
        const btnVer = e.target.closest('.btn-ver-dropdown');

        if (btnVer) {
            e.preventDefault();
            e.stopPropagation();

            closeDropdownRecibos();

            const dropdown = crearDropdownRecibos(btnVer);
            if (!dropdown) return;

            const rect = btnVer.getBoundingClientRect();
            dropdown.style.top = (rect.bottom + 5) + 'px';
            dropdown.style.left = rect.left + 'px';
            dropdown.style.display = 'block';
            dropdown.style.pointerEvents = 'auto';

            setTimeout(() => {
                const dropRect = dropdown.getBoundingClientRect();
                if (dropRect.right > window.innerWidth) {
                    dropdown.style.left = (window.innerWidth - dropRect.width - 10) + 'px';
                }
                if (dropRect.bottom > window.innerHeight) {
                    dropdown.style.top = (rect.top - dropRect.height - 5) + 'px';
                }
            }, 10);

            return;
        }

        if (!e.target.closest('.dropdown-menu-recibos')) {
            closeDropdownRecibos();
        }
    });
});
</script>

<!-- Search Module - Sistema de búsqueda AJAX -->
<script src="{{ asset('js/recibos-costura/search.js') }}?v={{ time() }}"></script>
@endpush
