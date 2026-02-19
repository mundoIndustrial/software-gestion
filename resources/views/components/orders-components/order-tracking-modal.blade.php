<!-- Overlay Selector de Prendas (fuera del modal) -->
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
                    <span class="tracking-prendas-info-label">Estado:</span>
                    <span class="tracking-prendas-info-value" id="selectorOrderStatus">-</span>
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
    <style>
        #orderTrackingModal.show {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            opacity: 1 !important;
            visibility: visible !important;
            z-index: 9998 !important;
        }
        #orderTrackingModal.show .tracking-modal-content {
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            animation: slideUp 0.3s ease-out !important;
            margin: auto !important;
            position: relative !important;
            background: white !important;
            border-radius: 16px !important;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
            max-width: 1200px !important;
            width: 95% !important;
            max-height: 95vh !important;
        }
    </style>
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
                <button class="tracking-back-btn" id="backToPrendasBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    <span>Volver</span>
                </button>
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
                <!-- Fila 1: Pedido, Cliente, Estado -->
                <div class="tracking-info-row">
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
                            <span class="tracking-info-label">Estado</span>
                            <span class="tracking-info-value" id="trackingOrderStatus">No iniciado</span>
                        </div>
                    </div>
                </div>

                <!-- Fila 2: Fechas -->
                <div class="tracking-info-row">
                    <!-- Fecha de Inicio -->
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
                            <span class="tracking-info-label">Fecha de Inicio</span>
                            <span class="tracking-info-value" id="trackingOrderDate">-</span>
                        </div>
                    </div>

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

                    <!-- Total de Días -->
                    <div class="tracking-info-card tracking-total-days-card">
                        <div class="tracking-info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="tracking-info-content">
                            <span class="tracking-info-label">Total de Días</span>
                            <span class="tracking-info-value" id="trackingTotalDays">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón para abrir modal de agregar proceso -->
            <div class="tracking-add-proceso-trigger">
                <button type="button" id="btnOpenAddProcesoModal" class="tracking-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                    Agregar Área
                </button>
            </div>

            <!-- Lista de Prendas -->
            <div class="tracking-prendas-section" style="display: none;">
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
                            <option value="Bordado">Bordado</option>
                            <option value="Estampado">Estampado</option>
                            <option value="Costura">Costura</option>
                            <option value="Taller">Taller</option>
                            <option value="Lavandería">Lavandería</option>
                            <option value="Control de Calidad">Control de Calidad</option>
                            <option value="Entrega">Entrega</option>
                        </select>
                    </div>
                    <div class="add-proceso-form-group">
                        <label for="procesoEstado">Estado:</label>
                        <select id="procesoEstado" class="add-proceso-select">
                            <option value="Pendiente">Pendiente</option>
                            <option value="En Progreso">En Progreso</option>
                            <option value="Completado">Completado</option>
                            <option value="Pausado">Pausado</option>
                        </select>
                    </div>
                </div>
                <div class="add-proceso-form-row">
                    <div class="add-proceso-form-group">
                        <label for="procesoFechaInicio">Fecha de Inicio:</label>
                        <input type="date" id="procesoFechaInicio" class="add-proceso-input">
                    </div>
                    <div class="add-proceso-form-group">
                        <label for="procesoEncargado">Encargado:</label>
                        <input type="text" id="procesoEncargado" class="add-proceso-input" placeholder="Nombre del encargado">
                    </div>
                </div>
                <div class="add-proceso-form-row">
                    <div class="add-proceso-form-group">
                        <label for="procesoObservaciones">Observaciones:</label>
                        <input type="text" id="procesoObservaciones" class="add-proceso-input" placeholder="Observaciones (opcional)">
                    </div>
                    <div class="add-proceso-form-group">
                        <!-- Espacio vacío para balance -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="add-proceso-footer">
            <button type="button" id="btnCancelAddProceso" class="add-proceso-btn-secondary">Cancelar</button>
            <button type="button" id="btnConfirmAddProceso" class="add-proceso-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 13l4 4L19 7"></path>
                </svg>
                Agregar Proceso
            </button>
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/>
                </svg>
                Eliminar Proceso
            </button>
        </div>
    </div>
</div>

<style>
.order-tracking-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9998;
}

.tracking-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.tracking-modal-content {
    position: relative;
    background: var(--bg-card, white);
    border-radius: 16px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    max-width: 1200px;
    width: 95%;
    max-height: 95vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
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

.tracking-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 20px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tracking-header-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 8px;
}

.tracking-header-icon svg {
    width: 20px;
    height: 20px;
}

.tracking-modal-title {
    flex: 1;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.tracking-modal-close {
    background: rgba(255, 255, 255, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.3);
    width: 36px;
    height: 36px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.tracking-modal-close:hover {
    background: rgba(255, 255, 255, 0.35);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.tracking-modal-close:active {
    transform: scale(0.95);
}

.tracking-modal-close svg {
    width: 20px;
    height: 20px;
    color: #000;
    stroke-width: 2.5;
}

.tracking-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

.tracking-order-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}

.tracking-info-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tracking-dates-card {
    display: flex;
    flex-direction: column;
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
}

.tracking-dates-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tracking-date-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    height: 70px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tracking-date-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
    border-color: #818cf8;
    background: linear-gradient(135deg, #f8faff 0%, #fafbff 100%);
}

.tracking-info-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    height: 70px;
    background: linear-gradient(135deg, #f0f9ff 0%, #f5f3ff 100%);
    border: 1px solid #e0e7ff;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.tracking-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
    border-color: #c7d2fe;
}

.tracking-info-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 8px;
    flex-shrink: 0;
}

.tracking-info-icon svg {
    width: 20px;
    height: 20px;
    color: white;
    stroke-width: 2;
}

.tracking-info-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.tracking-info-label {
    font-size: 12px;
    font-weight: 600;
    color: #6366f1;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 4px;
}

.tracking-info-value {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.3px;
}

.tracking-timeline {
    margin-bottom: 24px;
}

.tracking-timeline-container {
    position: relative;
    padding-left: 20px;
}

.tracking-timeline-container::before {
    content: '';
    position: absolute;
    left: 7px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
}

.tracking-timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding-bottom: 20px;
}

.tracking-timeline-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
}

.tracking-timeline-item::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 2px;
    width: 16px;
    height: 16px;
    background: white;
    border: 3px solid #3b82f6;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.tracking-timeline-item.completed::before {
    background: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.tracking-timeline-item.pending::before {
    border-color: #d1d5db;
    box-shadow: 0 0 0 3px rgba(209, 213, 219, 0.1);
}

.tracking-area-card {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border: 2px solid #d1d5db;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.tracking-area-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    opacity: 1;
    transition: opacity 0.3s ease;
}

.tracking-area-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    border-color: #9ca3af;
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
}

.tracking-area-card:hover::before {
    opacity: 1;
}

.tracking-area-card.completed {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-color: #d1d5db;
}

.tracking-area-card.completed::before {
    opacity: 1;
}

.tracking-area-card.pending {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    border-color: #9ca3af;
}

.tracking-area-name {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tracking-area-name svg {
    width: 22px;
    height: 22px;
    color: #f59e0b;
    filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.2));
}

.tracking-area-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    font-size: 13px;
}

.tracking-detail-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 8px;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 8px;
    border-left: 3px solid #3b82f6;
}

.tracking-detail-label {
    font-size: 11px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tracking-detail-value {
    font-size: 13px;
    font-weight: 600;
    color: #1f2937;
}

.tracking-days-badge {
    display: inline-block;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.tracking-days-badge-zero {
    display: inline-block;
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    color: white;
    padding: 4px 10px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.2);
}

.tracking-total-days-container {
    display: flex;
    justify-content: center;
    margin: 16px 0;
    padding: 0 16px;
}

.tracking-total-days-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 10px;
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.25);
    min-width: auto;
    max-width: 320px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.tracking-total-days-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
}

.tracking-total-days-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.25);
    border-radius: 8px;
    flex-shrink: 0;
}

.tracking-total-days-icon svg {
    width: 24px;
    height: 24px;
    color: white;
    stroke-width: 2;
}

.tracking-total-days-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.tracking-total-days-label {
    font-size: 11px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tracking-total-days-value {
    font-size: 22px;
    font-weight: 800;
    color: white;
    letter-spacing: -0.3px;
}

/* Sección de Prendas */
.tracking-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tracking-prendas-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.tracking-prenda-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.tracking-prenda-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.tracking-prenda-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    border-color: #3b82f6;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.tracking-prenda-card:hover::before {
    opacity: 1;
}

.tracking-prenda-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.tracking-prenda-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 8px;
    flex-shrink: 0;
}

.tracking-prenda-icon svg {
    width: 20px;
    height: 20px;
    color: white;
}

.tracking-prenda-name {
    font-size: 15px;
    font-weight: 700;
    color: #1f2937;
    flex: 1;
}

.tracking-prenda-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.tracking-prenda-detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.tracking-prenda-detail-label {
    color: #6b7280;
    font-weight: 600;
}

.tracking-prenda-detail-value {
    color: #1f2937;
    font-weight: 700;
}

.tracking-prenda-seguimientos {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
}

.tracking-seguimiento-badge {
    display: inline-block;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    margin-right: 6px;
    margin-bottom: 6px;
}

.tracking-seguimiento-badge.pendiente {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.tracking-seguimiento-badge.completado {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

/* Sección de Timeline */
.tracking-timeline-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px solid #e2e8f0;
}

.tracking-timeline-section .tracking-section-title {
    color: #3b82f6;
}

.tracking-back-button {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 16px;
}

.tracking-back-button:hover {
    background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
    transform: translateY(-2px);
}

/* Estilos adicionales para áreas */
.tracking-prenda-areas {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #e2e8f0;
}

/* Estilos para procesos de prenda */
.tracking-prenda-procesos {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
}

.tracking-prenda-proceso-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}

.tracking-prenda-proceso-item:hover {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-color: #3b82f6;
    transform: translateX(4px);
}

.tracking-proceso-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 6px;
    flex-shrink: 0;
}

.tracking-proceso-icon svg {
    width: 18px;
    height: 18px;
}

.tracking-proceso-info {
    flex: 1;
    min-width: 0;
}

.tracking-proceso-nombre {
    font-size: 14px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.tracking-proceso-estado {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Badge de bodega */
.tracking-bodega-badge {
    display: inline-block;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 12px;
    font-weight: 700;
    margin-top: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

/* Estilos para formulario de agregar proceso */
.tracking-add-proceso-section {
    margin-top: 24px;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}

.tracking-add-proceso-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 16px 0;
}

.tracking-add-proceso-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.tracking-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.tracking-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.tracking-form-group label {
    font-size: 12px;
    font-weight: 600;
    color: #3b82f6;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tracking-form-select,
.tracking-form-input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: white;
    transition: all 0.3s ease;
}

.tracking-form-select:focus,
.tracking-form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.tracking-form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 8px;
}

.tracking-btn-primary {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.tracking-btn-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
}

.tracking-btn-primary svg {
    width: 16px;
    height: 16px;
}

/* Responsive para formulario */
@media (max-width: 768px) {
    .tracking-form-row {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .tracking-add-proceso-section {
        padding: 16px;
        margin-top: 16px;
    }
}

.tracking-prenda-info {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
}

.tracking-prenda-info h4 {
    margin: 0 0 12px 0;
    color: #1f2937;
    font-size: 16px;
    font-weight: 700;
}

.tracking-prenda-info p {
    margin: 6px 0;
    font-size: 14px;
    color: #4b5563;
}

.tracking-no-prendas,
.tracking-no-seguimiento {
    text-align: center;
    padding: 32px;
    color: #6b7280;
    font-style: italic;
}

/* Estilos para el Overlay Selector de Prendas */
.tracking-prendas-selector-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0, 0, 0, 0.7) !important;
    backdrop-filter: blur(4px) !important;
    z-index: 99999 !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    animation: fadeIn 0.3s ease-out !important;
    padding: 20px !important;
    box-sizing: border-box !important;
    margin: 0 !important;
}

.tracking-prendas-selector-overlay.show {
    display: flex !important;
}

.tracking-prendas-selector-content {
    position: relative;
    background: var(--bg-card, white);
    border-radius: 16px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    max-width: 800px;
    width: 90%;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
    margin: auto;
}

.tracking-prendas-selector-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tracking-prendas-selector-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    flex-shrink: 0;
}

.tracking-prendas-selector-icon svg {
    width: 24px;
    height: 24px;
    color: white;
}

.tracking-prendas-selector-title {
    flex: 1;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
}

.tracking-prendas-selector-close {
    background: rgba(255, 255, 255, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.3);
    width: 40px;
    height: 40px;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.tracking-prendas-selector-close:hover {
    background: rgba(255, 255, 255, 0.35);
    border-color: rgba(255, 255, 255, 0.5);
    transform: scale(1.05);
}

.tracking-prendas-selector-close svg {
    width: 20px;
    height: 20px;
    color: white;
    stroke-width: 2.5;
}

.tracking-prendas-selector-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
}

.tracking-prendas-info {
    display: flex;
    gap: 24px;
    margin-bottom: 24px;
    padding: 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    flex-wrap: wrap;
}

.tracking-prendas-info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 120px;
}

.tracking-prendas-info-label {
    font-size: 12px;
    font-weight: 600;
    color: #3b82f6;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tracking-prendas-info-value {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
}

.tracking-prendas-list-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 16px;
}

.tracking-prendas-selector-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

/* Animaciones */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Responsive para móviles */
@media (max-width: 768px) {
    .order-tracking-modal {
        padding: 10px;
    }
    
    .tracking-modal-content {
        max-width: 98%;
        width: 98%;
        max-height: 98vh;
        border-radius: 12px;
    }

    .tracking-prendas-selector-content {
        max-width: 95%;
        max-height: 90vh;
        border-radius: 12px;
    }

    .tracking-prendas-selector-header {
        padding: 16px;
        gap: 12px;
    }

    .tracking-prendas-selector-title {
        font-size: 18px;
    }

    .tracking-prendas-selector-body {
        padding: 16px;
    }

    .tracking-prendas-info {
        gap: 16px;
        padding: 12px;
    }

    .tracking-prendas-selector-container {
        grid-template-columns: 1fr;
    }

    .tracking-prendas-container {
        grid-template-columns: 1fr;
    }
    
    .tracking-prenda-info {
        padding: 12px;
    }
    
    .tracking-prenda-info h4 {
        font-size: 14px;
    }
    
    .tracking-prenda-info p {
        font-size: 13px;
    }
}

/* Responsive para tablets */
@media (max-width: 1024px) {
    .tracking-modal-content {
        max-width: 95%;
        width: 95%;
    }
}

/* Botones de Editar y Eliminar */
.btn-editar-proceso,
.btn-eliminar-proceso {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Asegurar centrado perfecto del modal */
#orderTrackingModal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    display: none !important;
    z-index: 9998 !important;
    background: rgba(0, 0, 0, 0.5) !important;
    padding: 20px !important;
    box-sizing: border-box !important;
    margin: 0 !important;
}

#orderTrackingModal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* Sobreescribir cualquier otro CSS que pueda estar interfiriendo */
#orderTrackingModal.show .tracking-modal-content {
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: slideUp 0.3s ease-out !important;
    margin: auto !important;
    position: relative !important;
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
    max-width: 1200px !important;
    width: 95% !important;
    max-height: 95vh !important;
}

/* Contenido del modal centrado */
#orderTrackingModal.show .tracking-modal-content {
    position: relative !important;
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
    max-width: 1200px !important;
    width: 95% !important;
    max-height: 95vh !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: slideUp 0.3s ease-out !important;
    margin: auto !important;
}

.btn-editar-proceso:hover {
    background: #2563eb !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4) !important;
    transform: translateY(-2px);
}

.btn-editar-proceso:active {
    transform: translateY(0);
}

/* Asegurar que el contenido del modal sea visible */
#orderTrackingModal.show .tracking-modal-body {
    display: flex !important;
    flex-direction: column !important;
    overflow-y: auto !important;
    opacity: 1 !important;
    visibility: visible !important;
}

#orderTrackingModal.show .tracking-modal-header,
#orderTrackingModal.show .tracking-modal-body > * {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Forzar visibilidad del contenido */
#orderTrackingModal.show .tracking-timeline-section {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

#orderTrackingModal.show .tracking-add-proceso-section {
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Forzar visibilidad máxima */
#orderTrackingModal.show {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
}

#orderTrackingModal.show * {
    opacity: 1 !important;
    visibility: visible !important;
}

.btn-eliminar-proceso:hover {
    background: #dc2626 !important;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4) !important;
    transform: translateY(-2px);
}

.btn-eliminar-proceso:active {
    transform: translateY(0);
}

/* Mejorar distribución del contenido del modal */
#orderTrackingModal.show .tracking-modal-body {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
    background: #f8fafc;
}

/* Sección de información unificada del pedido */
#orderTrackingModal.show .tracking-order-info-unified {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

#orderTrackingModal.show .tracking-info-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

#orderTrackingModal.show .tracking-info-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

#orderTrackingModal.show .tracking-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

#orderTrackingModal.show .tracking-info-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 8px;
    color: white;
    flex-shrink: 0;
}

#orderTrackingModal.show .tracking-info-content {
    flex: 1;
    min-width: 0;
}

#orderTrackingModal.show .tracking-info-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
    margin-bottom: 4px;
}

#orderTrackingModal.show .tracking-info-value {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    display: block;
}

/* Tarjeta especial para Total de Días */
#orderTrackingModal.show .tracking-total-days-card {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
}

#orderTrackingModal.show .tracking-total-days-card .tracking-info-icon {
    background: rgba(255, 255, 255, 0.2) !important;
}

#orderTrackingModal.show .tracking-total-days-card .tracking-info-content {
    color: white !important;
}

#orderTrackingModal.show .tracking-total-days-card .tracking-info-label {
    color: rgba(255, 255, 255, 0.9) !important;
}

#orderTrackingModal.show .tracking-total-days-card .tracking-info-value {
    color: white !important;
}

/* Modal para Agregar Proceso */
.add-proceso-modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    display: none !important;
    z-index: 9999 !important;
    background: rgba(0, 0, 0, 0.5) !important;
    padding: 20px !important;
    box-sizing: border-box !important;
    margin: 0 !important;
}

.add-proceso-modal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.add-proceso-overlay {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(4px) !important;
}

.add-proceso-content {
    position: relative !important;
    background: white !important;
    border-radius: 16px !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3) !important;
    max-width: 600px !important;
    width: 90% !important;
    max-height: 90vh !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    animation: slideUp 0.3s ease-out !important;
}

/* Header del modal agregar proceso */
.add-proceso-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 20px 24px !important;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    color: white !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.add-proceso-header-left {
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
    flex: 1 !important;
}

.add-proceso-icon {
    width: 40px !important;
    height: 40px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: rgba(255, 255, 255, 0.2) !important;
    border-radius: 12px !important;
    color: white !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
}

.add-proceso-icon svg {
    width: 20px !important;
    height: 20px !important;
}

.add-proceso-header-content {
    flex: 1 !important;
    min-width: 0 !important;
}

.add-proceso-title {
    font-size: 18px !important;
    font-weight: 700 !important;
    margin: 0 !important;
    color: white !important;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2) !important;
}

.add-proceso-header-actions {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

.add-proceso-close {
    width: 36px !important;
    height: 36px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: rgba(255, 255, 255, 0.15) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 8px !important;
    color: white !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
}

.add-proceso-close:hover {
    background: rgba(255, 255, 255, 0.25) !important;
    transform: scale(1.05) !important;
}

.add-proceso-close svg {
    width: 18px !important;
    height: 18px !important;
}

/* Body del modal agregar proceso */
.add-proceso-body {
    flex: 1 !important;
    padding: 24px !important;
    overflow-y: auto !important;
    background: #f8fafc !important;
}

.add-proceso-form-row {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 16px !important;
    margin-bottom: 16px !important;
}

.add-proceso-form-group {
    display: flex !important;
    flex-direction: column !important;
    gap: 8px !important;
}

.add-proceso-form-group label {
    font-size: 14px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    margin: 0 !important;
}

.add-proceso-select,
.add-proceso-input {
    padding: 12px 16px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    background: white !important;
    color: #1f2937 !important;
    transition: all 0.3s ease !important;
}

.add-proceso-select:focus,
.add-proceso-input:focus {
    outline: none !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
}

/* Footer del modal agregar proceso */
.add-proceso-footer {
    padding: 20px 24px !important;
    background: white !important;
    border-top: 1px solid #e5e7eb !important;
    display: flex !important;
    justify-content: flex-end !important;
    gap: 12px !important;
}

.add-proceso-btn-secondary {
    padding: 10px 20px !important;
    background: #f3f4f6 !important;
    color: #6b7280 !important;
    border: 1px solid #d1d5db !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
}

.add-proceso-btn-secondary:hover {
    background: #e5e7eb !important;
    color: #4b5563 !important;
}

.add-proceso-btn-primary {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 10px 20px !important;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    color: white !important;
    border: none !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
}

.add-proceso-btn-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3) !important;
}

.add-proceso-btn-primary svg {
    width: 16px !important;
    height: 16px !important;
}

/* Botón trigger en el timeline */
.tracking-add-proceso-trigger {
    display: flex !important;
    justify-content: center !important;
    margin-top: 24px !important;
    margin-bottom: 0 !important;
}

/* Botón trigger dentro de la sección de información */
#orderTrackingModal.show .tracking-order-info-unified .tracking-add-proceso-trigger {
    display: flex !important;
    justify-content: center !important;
    margin: 24px 0 0 0 !important;
    padding: 0 20px !important;
}

/* Sección de días */
#orderTrackingModal.show .tracking-total-days-container {
    display: flex;
    justify-content: center;
}

#orderTrackingModal.show .tracking-total-days-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 32px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 12px;
    color: white;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

#orderTrackingModal.show .tracking-total-days-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
}

#orderTrackingModal.show .tracking-total-days-content {
    text-align: center;
}

#orderTrackingModal.show .tracking-total-days-label {
    font-size: 14px;
    font-weight: 600;
    opacity: 0.9;
    display: block;
    margin-bottom: 4px;
}

#orderTrackingModal.show .tracking-total-days-value {
    font-size: 24px;
    font-weight: 800;
    display: block;
}

/* Sección de timeline */
#orderTrackingModal.show .tracking-timeline-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #e2e8f0;
}

#orderTrackingModal.show .tracking-section-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

#orderTrackingModal.show .tracking-section-title::before {
    content: '';
    width: 4px;
    height: 24px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 2px;
}

#orderTrackingModal.show .tracking-timeline-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Botón de volver */
#orderTrackingModal.show .tracking-back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 20px;
    align-self: flex-start;
}

#orderTrackingModal.show .tracking-back-button:hover {
    background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
    transform: translateY(-1px);
}

/* Header mejorado del modal */
#orderTrackingModal.show .tracking-modal-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 16px 24px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: hidden;
}

#orderTrackingModal.show .tracking-modal-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
    pointer-events: none;
}

#orderTrackingModal.show .tracking-header-left {
    display: flex !important;
    align-items: center !important;
    gap: 16px !important;
    flex: 1;
    min-width: 0;
}

#orderTrackingModal.show .tracking-header-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 1;
}

#orderTrackingModal.show .tracking-header-icon svg {
    width: 20px;
    height: 20px;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

#orderTrackingModal.show .tracking-header-content {
    flex: 1;
    min-width: 0;
    display: block !important;
}

#orderTrackingModal.show .tracking-header-subtitle {
    font-size: 13px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
    margin: 2px 0 0 0;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
}

#orderTrackingModal.show .tracking-modal-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: white;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
    letter-spacing: -0.3px;
    line-height: 1.2;
}

#orderTrackingModal.show .tracking-header-actions {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    flex-shrink: 0;
}

#orderTrackingModal.show .tracking-back-btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 6px !important;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: white;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    position: relative;
    z-index: 1;
    white-space: nowrap;
}

#orderTrackingModal.show .tracking-back-btn svg {
    width: 16px !important;
    height: 16px !important;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
    flex-shrink: 0;
}

#orderTrackingModal.show .tracking-back-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

#orderTrackingModal.show .tracking-back-btn:active {
    transform: translateY(0);
}

#orderTrackingModal.show .tracking-modal-close {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: white;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px);
    position: relative;
    z-index: 1;
}

#orderTrackingModal.show .tracking-modal-close:hover {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.3);
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

#orderTrackingModal.show .tracking-modal-close:active {
    transform: scale(0.95);
}

#orderTrackingModal.show .tracking-modal-close svg {
    width: 18px;
    height: 18px;
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.2));
}

/* Light mode support - cuando el sidebar está en modo claro */
html:not([data-theme="dark"]) .tracking-modal-content,
html[data-theme="light"] .tracking-modal-content {
    background: #f9fafb !important;
}

html:not([data-theme="dark"]) .tracking-info-card,
html[data-theme="light"] .tracking-info-card {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
}

html:not([data-theme="dark"]) .tracking-date-item,
html[data-theme="light"] .tracking-date-item {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
}

html:not([data-theme="dark"]) .tracking-area-card,
html[data-theme="light"] .tracking-area-card {
    background: #ffffff !important;
    border: 1px solid #e5e7eb !important;
}

html:not([data-theme="dark"]) .tracking-area-name,
html[data-theme="light"] .tracking-area-name {
    color: #111827 !important;
}

html[data-theme="dark"] .tracking-area-name {
    color: #ffffff !important;
}

html:not([data-theme="dark"]) .tracking-detail-row,
html[data-theme="light"] .tracking-detail-row {
    background: #f3f4f6 !important;
    border-left: 3px solid #f59e0b;
}

html:not([data-theme="dark"]) .tracking-detail-label,
html[data-theme="light"] .tracking-detail-label {
    color: #6b7280 !important;
}

html:not([data-theme="dark"]) .tracking-detail-value,
html[data-theme="light"] .tracking-detail-value {
    color: #111827 !important;
}

html:not([data-theme="dark"]) .tracking-days-badge-zero,
html[data-theme="light"] .tracking-days-badge-zero {
    background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%) !important;
}

html:not([data-theme="dark"]) .tracking-info-label,
html[data-theme="light"] .tracking-info-label {
    color: #6b7280 !important;
}

html:not([data-theme="dark"]) .tracking-info-value,
html[data-theme="light"] .tracking-info-value {
    color: #111827 !important;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .tracking-modal-content {
        background: #1f2937;
    }

    .tracking-order-info {
        gap: 12px;
    }

    .tracking-info-card {
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
        border-color: #3b82f6;
    }

    .tracking-info-card:hover {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        border-color: #60a5fa;
    }

    .tracking-date-item {
        background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
        border-color: #4b5563;
    }

    .tracking-date-item:hover {
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.25);
        border-color: #818cf8;
        background: linear-gradient(135deg, #3f4654 0%, #323d4a 100%);
    }

    .tracking-info-label {
        color: #a5b4fc;
    }

    .tracking-info-value {
        color: #f3f4f6;
    }

    .tracking-info-icon {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .tracking-modal-close {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .tracking-modal-close:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.4);
    }

    .tracking-modal-close svg {
        color: #000;
    }

    .tracking-area-card {
        background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
        border-color: #4b5563;
    }

    .tracking-area-card.completed {
        background: linear-gradient(135deg, #374151 0%, #2d3748 100%);
        border-color: #4b5563;
    }

    .tracking-area-card.pending {
        background: linear-gradient(135deg, #2d3748 0%, #1f2937 100%);
        border-color: #374151;
    }

    .tracking-area-name {
        color: #ffffff;
    }

    .tracking-detail-row {
        background: rgba(0, 0, 0, 0.3);
        border-left-color: #3b82f6;
    }

    .tracking-detail-label {
        color: #d1d5db;
    }

    .tracking-detail-value {
        color: #f3f4f6;
    }

    .tracking-days-badge-zero {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
    }

    .tracking-total-days-card {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

}

/* Responsive */
@media (max-width: 768px) {
    .tracking-modal-content {
        max-width: 95%;
        max-height: 90vh;
        border-radius: 16px;
    }

    .tracking-modal-header {
        padding: 16px;
        gap: 10px;
    }

    .tracking-modal-title {
        font-size: 16px;
    }

    .tracking-modal-body {
        padding: 16px;
    }

    .tracking-order-info {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .tracking-info-section {
        gap: 10px;
    }

    .tracking-dates-card {
        padding: 0 !important;
    }

    .tracking-dates-group {
        gap: 10px;
    }

    .tracking-date-item {
        padding: 12px;
        height: 70px;
    }

    .tracking-info-card {
        padding: 12px;
        height: 70px;
    }

    .tracking-info-icon {
        width: 36px;
        height: 36px;
    }

    .tracking-info-icon svg {
        width: 18px;
        height: 18px;
    }

    .tracking-area-card {
        padding: 14px;
    }

    .tracking-area-name {
        font-size: 14px;
        gap: 8px;
    }

    .tracking-area-name svg {
        width: 20px;
        height: 20px;
    }

    .tracking-area-details {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .tracking-detail-row {
        padding: 6px;
    }

    .tracking-total-days-container {
        margin: 20px 0;
        padding: 0 12px;
    }

    .tracking-total-days-card {
        min-width: 240px;
        padding: 16px 20px;
        gap: 12px;
    }

    .tracking-total-days-icon {
        width: 44px;
        height: 44px;
    }

    .tracking-total-days-icon svg {
        width: 24px;
        height: 24px;
    }

    .tracking-total-days-label {
        font-size: 11px;
    }

    .tracking-total-days-value {
        font-size: 24px;
    }
}

/* Modal de Confirmación para Eliminar */
.confirm-delete-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.confirm-delete-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.confirm-delete-content {
    position: relative;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    max-width: 400px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    animation: confirmDeleteSlideIn 0.3s ease-out;
}

@keyframes confirmDeleteSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.confirm-delete-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.confirm-delete-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.confirm-delete-close {
    background: none;
    border: none;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
}

.confirm-delete-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.confirm-delete-body {
    padding: 24px;
    text-align: center;
}

.confirm-delete-icon {
    margin: 0 auto 16px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef2f2;
    border-radius: 50%;
}

.confirm-delete-body p {
    margin: 0 0 8px;
    font-size: 16px;
    color: #374151;
}

.confirm-delete-warning {
    font-size: 14px !important;
    color: #ef4444 !important;
    font-weight: 500;
}

.confirm-delete-footer {
    display: flex;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.confirm-delete-btn-secondary {
    flex: 1;
    padding: 10px 16px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.confirm-delete-btn-secondary:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.confirm-delete-btn-danger {
    flex: 1;
    padding: 10px 16px;
    background: #ef4444;
    border: 1px solid #ef4444;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.confirm-delete-btn-danger:hover {
    background: #dc2626;
    border-color: #dc2626;
}

.confirm-delete-btn-danger svg {
    width: 16px;
    height: 16px;
}
</style>
