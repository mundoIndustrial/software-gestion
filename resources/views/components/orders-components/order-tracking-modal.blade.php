<!-- Overlay Selector de Prendas (fuera del modal) -->
@once
    <link rel="stylesheet" href="{{ asset('css/order-tracking-modal.css') }}">
@endonce
<div id="trackingPrendasSelectorOverlay" class="tracking-prendas-selector-overlay" style="display: none;" onclick="if(event.target === this) cerrarSelectorPrendas()">
    <div class="tracking-prendas-selector-content">
        <!-- Header -->
        <div class="tracking-prendas-selector-header">
            <div class="tracking-prendas-selector-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                </svg>
            </div>
            <h2 class="tracking-prendas-selector-title">Seleccionar Prenda</h2>
            <button class="tracking-prendas-selector-close" onclick="cerrarSelectorPrendas()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="tracking-prendas-selector-body">
            <div class="tracking-prendas-info">
                <div class="tracking-prendas-info-item">
                    <span class="tracking-prendas-info-label">Pedido:</span>
                    <span class="tracking-prendas-info-value" id="selectorOrderNumber">-</span>
                </div>
                <div class="tracking-prendas-info-item">
                    <span class="tracking-prendas-info-label">Cliente:</span>
                    <span class="tracking-prendas-info-value" id="selectorOrderClient">-</span>
                </div>
                <div class="tracking-prendas-info-item">
                    <span class="tracking-prendas-info-label">Estado del Pedido:</span>
                    <span class="tracking-prendas-info-value" id="selectorOrderStatus">-</span>
                </div>
                <div class="tracking-prendas-info-item">
                    <span class="tracking-prendas-info-label">Fecha de Inicio:</span>
                    <span class="tracking-prendas-info-value" id="selectorOrderStartDate">-</span>
                </div>
                <div class="tracking-prendas-info-item">
                    <span class="tracking-prendas-info-label">Fecha Estimada:</span>
                    <span class="tracking-prendas-info-value" id="selectorOrderEstimatedDate">-</span>
                </div>
            </div>

            <div class="tracking-prendas-list">
                <h3 class="tracking-prendas-list-title">Prendas del Pedido</h3>
                <div id="trackingPrendasSelectorContainer" class="tracking-prendas-selector-container">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Seguimiento del Pedido -->
<div id="orderTrackingModal" class="order-tracking-modal" style="display: none !important;">
    <div class="tracking-modal-overlay" id="trackingModalOverlay"></div>
    <div class="tracking-modal-content">
        <!-- Header -->
        <div class="tracking-modal-header">
            <div class="tracking-header-left">
                <div class="tracking-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="tracking-header-content">
                    <h2 class="tracking-modal-title">Seguimiento de la Prenda</h2>
                    <div class="tracking-header-subtitle" id="trackingPrendaReciboHeader">-</div>
                </div>
            </div>
            <div class="tracking-header-actions">
                <button class="tracking-modal-close" id="closeTrackingModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="tracking-modal-body">
            <!-- Información Unificada del Pedido -->
            <div class="tracking-order-info-unified">
                <!-- Fila 1: N° Recibo, Pedido, Cliente, Estado -->
                <div class="tracking-info-row">
                    <!-- N° Recibo -->
                    <div class="tracking-info-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                <path d="M12 6v4m0 2h2"></path>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">N° Recibo</span>
                            <span class="tracking-info-value" id="trackingOrderRecibo">-</span>
                        </div>
                    </div>

                    <!-- Pedido -->
                    <div class="tracking-info-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">Pedido</span>
                            <span class="tracking-info-value" id="trackingOrderNumber">8</span>
                        </div>
                    </div>

                    <!-- Cliente -->
                    <div class="tracking-info-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">Cliente</span>
                            <span class="tracking-info-value" id="trackingOrderClient">INVERSIONES GOQUIN</span>
                        </div>
                    </div>

                    <!-- Estado del Pedido -->
                    <div class="tracking-info-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">Estado del Pedido</span>
                            <span class="tracking-info-value" id="trackingOrderStatus">No iniciado</span>
                        </div>
                    </div>
                </div>

                <!-- Fila 2: Fechas -->
                <div class="tracking-info-row">
                    <!-- Fecha Estimada -->
                    <div class="tracking-info-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">Fecha Estimada</span>
                            <span class="tracking-info-value" id="trackingEstimatedDate">-</span>
                        </div>
                    </div>

                    <div class="tracking-info-card tracking-days-selector-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">Días</span>
                            <div class="tracking-days-selector" id="trackingDaysSelector">
                                <button type="button" class="tracking-days-selector-trigger" id="trackingDaysSelectorTrigger">
                                    <span class="tracking-days-selector-value" id="trackingDaysSelectorValue">Sin seleccionar</span>
                                    <svg class="tracking-days-selector-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                                <div class="tracking-days-selector-menu" id="trackingDaysSelectorMenu" style="display: none;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Prendas -->
            <div class="tracking-prendas-section" style="display: none; margin: 0; padding: 0;">
                <div id="trackingPrendasContainer" class="tracking-prendas-container">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
            </div>

            <!-- Timeline de Seguimiento por Prenda -->
            <div class="tracking-timeline-section" id="trackingTimelineSection" style="display: block;">
                <div id="trackingTimelineContainer" class="tracking-timeline-container">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal para Agregar Proceso -->
<div id="addProcesoModal" class="add-proceso-modal" style="display: none !important;">
    <div class="add-proceso-overlay" id="addProcesoOverlay"></div>
    <div class="add-proceso-content">
        <!-- Header -->
        <div class="add-proceso-header">
            <div class="add-proceso-header-left">
                <div class="add-proceso-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                </div>
                <div class="add-proceso-header-content">
                    <h2 class="add-proceso-title">Agregar Proceso de Seguimiento</h2>
                </div>
            </div>
            <div class="add-proceso-header-actions">
                <button class="add-proceso-close" id="closeAddProcesoModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="add-proceso-body">
            <div class="add-proceso-form">
                <div class="add-proceso-form-row">
                    <div class="add-proceso-form-group">
                        <label for="procesoArea">Área/Proceso:</label>
                        <select id="procesoArea" class="add-proceso-select">
                            <option value="">Seleccionar área...</option>
                            <option value="Corte">Corte</option>
                        </select>
                    </div>
                    <div class="add-proceso-form-group">
                        <label for="procesoEncargado">Encargado:</label>
                        <input type="text" id="procesoEncargado" class="add-proceso-input" placeholder="Nombre del encargado" style="text-transform: uppercase;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="add-proceso-footer">
            <button type="button" id="btnCancelAddProceso" class="add-proceso-btn-secondary">Cancelar</button>
            <button type="button" id="btnConfirmAddProceso" class="add-proceso-btn-primary">
                <span id="addProcesoButtonContent" class="btn-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 13l4 4L19 7"></path>
                    </svg>
                    Agregar Proceso
                </span>
                <span id="addProcesoButtonLoading" class="btn-loading" style="display: none;">
                    <svg class="spinner" viewBox="0 0 50 50" width="20" height="20">
                        <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" opacity="0.3"></circle>
                        <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="31.4 94.2" stroke-linecap="round" class="spinner-circle"></circle>
                    </svg>
                    Cargando...
                </span>
            </button>
        </div>
    </div>
</div>

<!-- Modal de asignación de Corte -->
<div id="asignarCorteModal" class="add-proceso-modal" style="display: none !important;">
    <div class="add-proceso-overlay" onclick="cerrarModalAsignacionCorte()"></div>
    <div class="add-proceso-content" style="max-width: 1100px; width: 94%;">
        <div class="add-proceso-header">
            <div class="add-proceso-header-left">
                <div class="add-proceso-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="10" cy="7" r="4"></circle>
                        <path d="M20 8v6"></path>
                        <path d="M23 11h-6"></path>
                    </svg>
                </div>
                <div class="add-proceso-header-content">
                    <h2 class="add-proceso-title">Asignar Corte</h2>
                    <p id="asignarCorteModalSubtitle" style="margin: 0.35rem 0 0; color: #64748b; font-size: 0.92rem;">Seleccione cómo desea asignar este recibo.</p>
                </div>
            </div>
            <div class="add-proceso-header-actions">
                <button class="add-proceso-close" type="button" onclick="cerrarModalAsignacionCorte()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="add-proceso-body" style="display: grid; gap: 1rem;">
            <div id="asignarCorteOpciones" style="display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <button type="button" id="btnAsignarCortadorCompleto" onclick="seleccionarOpcionAsignacionCorte('completo')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; text-align: left;">
                    <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                        <span class="material-symbols-rounded" style="font-size: 1.6rem; color: #2563eb;">person</span>
                        <div>
                            <div style="font-weight: 700; color: #0f172a;">Asignar a cortador</div>
                            <div style="color: #64748b; font-size: 0.9rem;">Todo el recibo queda con un solo encargado de corte.</div>
                        </div>
                    </div>
                </button>
                <button type="button" id="btnDistribuirCortadores" onclick="seleccionarOpcionAsignacionCorte('distribuir')" style="padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white; text-align: left;">
                    <div style="display: flex; gap: 0.85rem; align-items: flex-start;">
                        <span class="material-symbols-rounded" style="font-size: 1.6rem; color: #059669;">group_add</span>
                        <div>
                            <div style="font-weight: 700; color: #0f172a;">Distribuir entre cortadores</div>
                            <div style="color: #64748b; font-size: 0.9rem;">Crea parciales por talla y los asigna a varios cortadores.</div>
                        </div>
                    </div>
                </button>
            </div>

            <button type="button" id="btnVolverAsignacionCorte" onclick="volverAOpcionesAsignacionCorte()" style="display:none; align-self:flex-start; padding: 0.65rem 0.9rem; border-radius: 999px; border: 1px solid #cbd5e1; background: #fff; color: #334155;">
                Volver
            </button>

            <div id="contenidoAsignacionCorte"></div>
        </div>

        <div class="add-proceso-footer">
            <button type="button" class="add-proceso-btn-secondary" onclick="cerrarModalAsignacionCorte()">Cancelar</button>
            <button type="button" id="btnConfirmarAsignacionCorte" class="add-proceso-btn-primary" onclick="confirmarAsignacionCorte()" disabled>Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Eliminar -->
<div id="confirmDeleteModal" class="confirm-delete-modal" style="display: none !important;">
    <div class="confirm-delete-overlay"></div>
    <div class="confirm-delete-content">
        <!-- Header -->
        <div class="confirm-delete-header">
            <h3>Confirmar Eliminación</h3>
            <button class="confirm-delete-close" id="closeConfirmDeleteModal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Body -->
        <div class="confirm-delete-body">
            <div class="confirm-delete-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                    <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/>
                </svg>
            </div>
            <p>¿Estás seguro de eliminar el proceso "<span id="deleteProcessName"></span>"?</p>
            <p class="confirm-delete-warning">Esta acción no se puede deshacer.</p>
        </div>
        
        <!-- Footer -->
        <div class="confirm-delete-footer">
            <button type="button" id="btnCancelDelete" class="confirm-delete-btn-secondary">Cancelar</button>
            <button type="button" id="btnConfirmDelete" class="confirm-delete-btn-danger">
                <span id="deleteButtonContent" class="btn-content">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"></path>
                    </svg>
                    Eliminar Proceso
                </span>
                <span id="deleteButtonLoading" class="btn-loading" style="display: none;">
                    <svg class="spinner" viewBox="0 0 50 50" width="18" height="18">
                        <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" opacity="0.3"></circle>
                        <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="31.4 94.2" stroke-linecap="round" class="spinner-circle"></circle>
                    </svg>
                    Eliminando...
                </span>
            </button>
        </div>
    </div>
</div>

<script>
// Función para mostrar/ocultar el campo de encargado según el área seleccionada
function toggleEncargadoField() {
    const selectArea = document.getElementById('procesoArea');
    const encargadoGroup = document.querySelector('.add-proceso-form-group:has(#procesoEncargado)');
    
    if (!selectArea || !encargadoGroup) return;
    
    const selectedArea = (selectArea.value || '').toLowerCase();
    
    // Áreas que requieren encargado
    const needsEncargado = ['corte', 'costura', 'control de calidad'];
    
    if (needsEncargado.some(area => selectedArea.includes(area))) {
        encargadoGroup.style.display = 'block';
        // Hacer el campo obligatorio si el área lo requiere
        document.getElementById('procesoEncargado').required = true;
    } else {
        encargadoGroup.style.display = 'none';
        // Hacer el campo opcional si el área no lo requiere
        document.getElementById('procesoEncargado').required = false;
        document.getElementById('procesoEncargado').value = '';
    }
}

// Inicializar el evento change cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    const selectArea = document.getElementById('procesoArea');
    if (selectArea) {
        selectArea.addEventListener('change', toggleEncargadoField);
        // Ejecutar la función al cargar para establecer el estado inicial
        toggleEncargadoField();
    }
});

// También ejecutar cuando se abre el modal desde un área específica
if (typeof window.abrirModalAgregarProcesoDesdeArea !== 'undefined') {
    const originalFunction = window.abrirModalAgregarProcesoDesdeArea;
    window.abrirModalAgregarProcesoDesdeArea = function(areaSeleccionada, pedidoId, prendaId) {
        // Llamar a la función original
        if (originalFunction) {
            originalFunction(areaSeleccionada, pedidoId, prendaId);
        }
        
        // Esperar un poco a que el modal se abra y luego ejecutar la función
        setTimeout(function() {
            toggleEncargadoField();
        }, 100);
    };
}
</script>
